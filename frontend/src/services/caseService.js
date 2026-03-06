import api from '@/services/api';

// Cache for lookup data to avoid repeated requests
const cache = {
  categories: null,
  stages: null,
  users: null,
  categoriesPromise: null,
  stagesPromise: null,
  usersPromise: null,
  lastFetch: 0,
  TTL: 300000 // 5 minutes cache TTL
};

// ─────────────────────────────────────────────────────────────────────────────
// CASES - ULTRA FAST with minimal payload
// ─────────────────────────────────────────────────────────────────────────────

export const getCourts = (params = {}) =>
  api.get('/admin/courts-offices', { params });

export const getCases = (params = {}) => {
  // Strip undefined/empty values so the URL stays clean.
  // No `fields` param needed — the backend JOIN query always returns the
  // exact columns the table needs in one fast query.
  const cleanParams = Object.entries({
    per_page: 10,
    sort_by: 'created_at',
    sort_direction: 'desc',
    page: 1,
    ...params
  }).reduce((acc, [key, value]) => {
    if (value !== undefined && value !== '') acc[key] = value;
    return acc;
  }, {});

  return api.get('/admin/cases', { params: cleanParams });
};

export const getCase = (id) =>
  api.get(`/admin/cases/${id}`);

export const store = (payload) =>
  api.post('/admin/cases', payload);

export const update = (id, payload) =>
  api.put(`/admin/cases/${id}`, payload);

export const archive = (id) =>
  api.patch(`/admin/cases/${id}/archive`);

// ─────────────────────────────────────────────────────────────────────────────
// ACTIVITY LOGS
// ─────────────────────────────────────────────────────────────────────────────

export const getActivityLogs = (caseId, params = {}) =>
  api.get(`/admin/cases/${caseId}/activity-logs`, { params });

// ─────────────────────────────────────────────────────────────────────────────
// STAGES - ULTRA FAST with caching
// ─────────────────────────────────────────────────────────────────────────────

// OPTIMIZATION: Synchronous cache check - no async if cached
export const getStages = (forceRefresh = false) => {
  if (forceRefresh) {
    cache.stages = null;
    cache.stagesPromise = null;
  }
  
  // Return cached data immediately if available
  if (cache.stages) {
    return Promise.resolve({ data: cache.stages });
  }
  
  // Return existing promise if request in progress
  if (cache.stagesPromise) {
    return cache.stagesPromise;
  }
  
  // Make new request
  cache.stagesPromise = api.get('/admin/master-data/case-stages', {
    params: { fields: 'id,name,is_active' } // Only get needed fields
  }).then(response => {
    const stages = response.data?.data ?? response.data ?? [];
    cache.stages = stages;
    cache.stagesPromise = null;
    return { data: stages };
  });
  
  return cache.stagesPromise;
};

export const getStageHistory = (caseId) =>
  api.get(`/admin/cases/${caseId}/stages/history`);

export const updateStage = (caseId, payload) =>
  api.put(`/admin/cases/${caseId}/stage`, payload);

// ─────────────────────────────────────────────────────────────────────────────
// LOOKUPS - ULTRA FAST with parallel loading and minimal fields
// ─────────────────────────────────────────────────────────────────────────────

// OPTIMIZATION: Load only what's needed, minimal fields
export const loadAllLookups = async (forceRefresh = false) => {
  const now = Date.now();
  if (!forceRefresh && cache.lastFetch && (now - cache.lastFetch) < cache.TTL) {
    return {
      categories: cache.categories || [],
      stages: cache.stages || [],
      users: cache.users || []
    };
  }

  // Parallel requests with minimal payloads
  const [categoriesRes, stagesRes, usersRes] = await Promise.allSettled([
    getCategories(),
    getStages(),
    getAssignableUsers()
  ]);

  const result = {
    categories: categoriesRes.status === 'fulfilled' ? (categoriesRes.value.data || []) : [],
    stages: stagesRes.status === 'fulfilled' ? (stagesRes.value.data || []) : [],
    users: usersRes.status === 'fulfilled' ? (usersRes.value.data || []) : []
  };

  // Update cache
  cache.categories = result.categories;
  cache.stages = result.stages;
  cache.users = result.users;
  cache.lastFetch = now;

  return result;
};

// OPTIMIZATION: Minimal fields for categories
export const getCategories = (forceRefresh = false) => {
  if (forceRefresh) {
    cache.categories = null;
    cache.categoriesPromise = null;
  }
  
  if (cache.categories) {
    return Promise.resolve({ data: cache.categories });
  }
  
  if (cache.categoriesPromise) {
    return cache.categoriesPromise;
  }
  
  cache.categoriesPromise = api.get('/admin/case-categories', {
    params: { fields: 'id,name' } // Only need id and name
  }).then(response => {
    const categories = response.data?.data ?? response.data ?? [];
    cache.categories = categories;
    cache.categoriesPromise = null;
    return { data: categories };
  });
  
  return cache.categoriesPromise;
};

// OPTIMIZATION: Minimal fields for users
export const getAssignableUsers = (forceRefresh = false) => {
  if (forceRefresh) {
    cache.users = null;
    cache.usersPromise = null;
    cache.lastFetch = 0; // also reset TTL so loadAllLookups re-fetches too
  }
  
  if (cache.users) {
    return Promise.resolve({ data: cache.users });
  }
  
  if (cache.usersPromise) {
    return cache.usersPromise;
  }
  
  cache.usersPromise = api.get('/admin/users/assignable', {
    params: {
      limit: 100,
      // _t busts both the axios requestCache AND pendingRequests dedup
      // so forceRefresh always fires a real network request.
      _t: Date.now()
    }
  }).then(response => {
    const users = response.data?.data ?? response.data ?? [];
    cache.users = users;
    cache.usersPromise = null;
    return { data: users };
  }).catch(err => {
    // Clear promise on error so next call retries
    cache.usersPromise = null;
    return Promise.reject(err);
  });
  
  return cache.usersPromise;
};

// ─────────────────────────────────────────────────────────────────────────────
// CHECKLIST
// ─────────────────────────────────────────────────────────────────────────────

export const getChecklist = (caseId) =>
  api.get(`/admin/cases/${caseId}/checklist`);

export const createChecklistTask = (caseId, payload) =>
  api.post(`/admin/cases/${caseId}/checklist`, payload);

export const getChecklistTask = (caseId, taskId) =>
  api.get(`/admin/cases/${caseId}/checklist/${taskId}`);

export const updateChecklistTask = (caseId, taskId, payload) =>
  api.put(`/admin/cases/${caseId}/checklist/${taskId}`, payload);

export const updateChecklistTaskStatus = (caseId, taskId, status) =>
  api.patch(`/admin/cases/${caseId}/checklist/${taskId}/status`, { status });

export const deleteChecklistTask = (caseId, taskId) =>
  api.delete(`/admin/cases/${caseId}/checklist/${taskId}`);

// ─────────────────────────────────────────────────────────────────────────────
// ULTRA FAST FORMATTERS (pre-compiled, no function calls in loops)
// ─────────────────────────────────────────────────────────────────────────────

// OPTIMIZATION: Inline formatter - fastest possible
export const formatCases = (rawArray) => {
  if (!Array.isArray(rawArray)) return [];
  
  const result = new Array(rawArray.length);
  for (let i = 0; i < rawArray.length; i++) {
    const raw = rawArray[i];
    result[i] = {
      id: raw.id,
      case_no: raw.case_no,
      case_code: raw.case_code,
      title: raw.title,
      category: raw.category_name || '—',
      category_id: raw.category_id || null,
      client: raw.client_name || '—',
      client_id: raw.client_id || null,
      lawyer: raw.lawyer_name || '—',
      assigned_lawyer_id: raw.assigned_lawyer_id || null,
      clerk: raw.clerk_name || '—',
      assigned_clerk_id: raw.assigned_clerk_id || null,
      priority: raw.priority,
      case_status: raw.case_status,
      stage: raw.stage_name || null,
      current_stage_id: raw.current_stage_id || null,
      court_or_office: raw.court_or_office || null,
      docket_no: raw.docket_no || null,
      summary: raw.summary || null,
      created_at: raw.created_at,
      updated_at: raw.updated_at,
    };
  }
  return result;
};

// Single formatter (uses the fast one)
export const formatCase = (raw) => formatCases([raw])[0] || {};

// Clear cache
export const clearCache = () => {
  cache.categories = null;
  cache.stages = null;
  cache.users = null;
  cache.categoriesPromise = null;
  cache.stagesPromise = null;
  cache.usersPromise = null;
  cache.lastFetch = 0;
};
// ─────────────────────────────────────────────────────────────────────────────
// CHECKLIST TRACKER (IN/OUT movements)
// ─────────────────────────────────────────────────────────────────────────────

export const getChecklistTracker = (caseId) =>
  api.get(`/admin/cases/${caseId}/checklist-tracker`);

export const createChecklistTrackerEntry = (caseId, payload) =>
  api.post(`/admin/cases/${caseId}/checklist-tracker`, payload);

export const approveChecklistMovement = (caseId, movementId, approval_status) =>
  api.patch(`/admin/cases/${caseId}/checklist-tracker/${movementId}/approve`, { approval_status });

// ─────────────────────────────────────────────────────────────────────────────
// FOLDER TRACKER (IN/OUT movements)
// ─────────────────────────────────────────────────────────────────────────────

export const getFolderTracker = (caseId) =>
  api.get(`/admin/cases/${caseId}/folder-tracker`);

export const createFolderTrackerEntry = (caseId, payload) =>
  api.post(`/admin/cases/${caseId}/folder-tracker`, payload);