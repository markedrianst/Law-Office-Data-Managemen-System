import api from '@/services/api';

// ─────────────────────────────────────────────────────────────────────────────
// INTERNAL CACHE STORE
// ─────────────────────────────────────────────────────────────────────────────

const TTL = 5 * 60 * 1000; // 5 minutes

const _cache = {
  categories:        null,
  stages:            null,
  users:             null,
  categoriesTs:      0,
  stagesTs:          0,
  usersTs:           0,

  // In-flight promise refs — prevents duplicate concurrent requests
  categoriesPromise: null,
  stagesPromise:     null,
  usersPromise:      null,
};

/** True if a cached value exists and is still within TTL. */
const _fresh = (ts) => ts > 0 && Date.now() - ts < TTL;

// ─────────────────────────────────────────────────────────────────────────────
// CASES  (CaseMaster.vue → CaseController@index / show / store / update / archive)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * GET /admin/courts-offices
 * Used by CaseFormModal.vue court/office dropdown.
 */
export const getCourts = (params = {}) =>
  api.get('/admin/courts-offices', { params });

/**
 * GET /admin/cases
 * Used by CaseMaster.vue table. Strips undefined/empty params so the
 * URL stays clean and the backend cache key is deterministic.
 */
export const getCases = (params = {}) => {
  const defaults = {
    per_page:       10,
    sort_by:        'created_at',
    sort_direction: 'desc',
    page:           1,
  };

  // Merge defaults then strip any undefined / empty-string values
  const clean = {};
  for (const [k, v] of Object.entries({ ...defaults, ...params })) {
    if (v !== undefined && v !== '') clean[k] = v;
  }

  return api.get('/admin/cases', { params: clean });
};

/**
 * GET /admin/cases/:id
 * Used by CaseViewModal.vue when opening a case detail.
 */
export const getCase = (id) =>
  api.get(`/admin/cases/${id}`);

/**
 * POST /admin/cases
 * Used by CaseMaster.vue submitForm() when isEditing === false.
 */
export const store = (payload) =>
  api.post('/admin/cases', payload);

/**
 * PUT /admin/cases/:id
 * Used by CaseMaster.vue submitForm() when isEditing === true.
 * After success CaseMaster calls clearCache() + reloads the list.
 */
export const update = (id, payload) =>
  api.put(`/admin/cases/${id}`, payload);

/**
 * PATCH /admin/cases/:id/archive
 * Used by CaseMaster.vue (archive action button).
 */
export const archive = (id) =>
  api.patch(`/admin/cases/${id}/archive`);

// ─────────────────────────────────────────────────────────────────────────────
// ACTIVITY LOGS  (not rendered yet in provided files, but endpoint exists)
// ─────────────────────────────────────────────────────────────────────────────

export const getActivityLogs = (caseId, params = {}) =>
  api.get(`/admin/cases/${caseId}/activity-logs`, { params });

// ─────────────────────────────────────────────────────────────────────────────
// STAGES  (CaseFormModal.vue dropdown + CaseViewModal.vue inline stage change)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * GET /admin/master-data/case-stages
 *
 * Returns a promise that resolves to { data: Stage[] }.
 * - Synchronous cache hit: resolves immediately, no network call.
 * - In-flight deduplication: callers waiting while a request is already
 *   running receive the same promise.
 * - forceRefresh=true: busts cache and fires a fresh request.
 */
export const getStages = (forceRefresh = false) => {
  if (forceRefresh) {
    _cache.stages        = null;
    _cache.stagesTs      = 0;
    _cache.stagesPromise = null;
  }

  // Serve from cache if still fresh
  if (_cache.stages && _fresh(_cache.stagesTs)) {
    return Promise.resolve({ data: _cache.stages });
  }

  // Deduplicate in-flight
  if (_cache.stagesPromise) return _cache.stagesPromise;

  _cache.stagesPromise = api
    .get('/admin/master-data/case-stages', { params: { fields: 'id,name,is_active' } })
    .then(res => {
      const data        = res.data?.data ?? res.data ?? [];
      _cache.stages     = data;
      _cache.stagesTs   = Date.now();
      _cache.stagesPromise = null;
      return { data };
    })
    .catch(err => {
      _cache.stagesPromise = null;
      return Promise.reject(err);
    });

  return _cache.stagesPromise;
};

/**
 * GET /admin/cases/:caseId/stages/history
 * Used by CaseMaster.vue → loadStageHistory() → CaseViewModal.vue stageHistory prop.
 */
export const getStageHistory = (caseId) =>
  api.get(`/admin/cases/${caseId}/stages/history`);

/**
 * PUT /admin/cases/:caseId/stage
 * Used by CaseViewModal.vue inline stage dropdown → CaseMaster.vue updateCaseStage().
 */
export const updateStage = (caseId, payload) =>
  api.put(`/admin/cases/${caseId}/stage`, payload);

// ─────────────────────────────────────────────────────────────────────────────
// LOOKUPS  (CaseFormModal.vue dropdowns — categories, users, clients)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * GET /admin/case-categories
 *
 * Same deduplication + TTL pattern as getStages().
 * forceRefresh=true is called from CaseMaster.vue onCategoryCreated().
 */
export const getCategories = (forceRefresh = false) => {
  if (forceRefresh) {
    _cache.categories        = null;
    _cache.categoriesTs      = 0;
    _cache.categoriesPromise = null;
  }

  if (_cache.categories && _fresh(_cache.categoriesTs)) {
    return Promise.resolve({ data: _cache.categories });
  }

  if (_cache.categoriesPromise) return _cache.categoriesPromise;

  _cache.categoriesPromise = api
    .get('/admin/case-categories', { params: { fields: 'id,name' } })
    .then(res => {
      const data             = res.data?.data ?? res.data ?? [];
      _cache.categories      = data;
      _cache.categoriesTs    = Date.now();
      _cache.categoriesPromise = null;
      return { data };
    })
    .catch(err => {
      _cache.categoriesPromise = null;
      return Promise.reject(err);
    });

  return _cache.categoriesPromise;
};

/**
 * GET /admin/users/assignable
 *
 * forceRefresh=true is used by CaseMaster.vue refreshUsers() which fires
 * whenever the form or view modal opens to ensure the user list is current.
 * Cache-busting adds _t param so api.js's 5-second axios cache is also skipped.
 */
export const getAssignableUsers = (forceRefresh = false) => {
  if (forceRefresh) {
    _cache.users        = null;
    _cache.usersTs      = 0;
    _cache.usersPromise = null;
  }

  if (_cache.users && _fresh(_cache.usersTs)) {
    return Promise.resolve({ data: _cache.users });
  }

  if (_cache.usersPromise) return _cache.usersPromise;

  const params = { limit: 100 };
  // _t bypasses both the axios in-memory cache AND pendingRequests dedup
  if (forceRefresh) params._t = Date.now();

  _cache.usersPromise = api
    .get('/admin/users/assignable', { params })
    .then(res => {
      const data          = res.data?.data ?? res.data ?? [];
      _cache.users        = data;
      _cache.usersTs      = Date.now();
      _cache.usersPromise = null;
      return { data };
    })
    .catch(err => {
      _cache.usersPromise = null;
      return Promise.reject(err);
    });

  return _cache.usersPromise;
};

/**
 * Load ALL lookups in parallel.
 * Called once on CaseMaster.vue mount via loadLookups().
 * Returns already-cached data immediately if still fresh (zero network calls).
 *
 * Returns: { categories: [], stages: [], users: [] }
 */
export const loadAllLookups = async (forceRefresh = false) => {
  // Serve fully from cache — no network at all
  if (
    !forceRefresh &&
    _cache.categories && _fresh(_cache.categoriesTs) &&
    _cache.stages     && _fresh(_cache.stagesTs)     &&
    _cache.users      && _fresh(_cache.usersTs)
  ) {
    return {
      categories: _cache.categories,
      stages:     _cache.stages,
      users:      _cache.users,
    };
  }

  // Fire all three requests concurrently
  const [catRes, stageRes, userRes] = await Promise.allSettled([
    getCategories(forceRefresh),
    getStages(forceRefresh),
    getAssignableUsers(forceRefresh),
  ]);

  return {
    categories: catRes.status   === 'fulfilled' ? (catRes.value.data   ?? []) : (_cache.categories ?? []),
    stages:     stageRes.status === 'fulfilled' ? (stageRes.value.data ?? []) : (_cache.stages     ?? []),
    users:      userRes.status  === 'fulfilled' ? (userRes.value.data  ?? []) : (_cache.users      ?? []),
  };
};

// ─────────────────────────────────────────────────────────────────────────────
// CHECKLIST  (CaseViewModal.vue → CaseMaster.vue checklist handlers)
// ─────────────────────────────────────────────────────────────────────────────

/** GET  /admin/cases/:caseId/checklist */
export const getChecklist = (caseId) =>
  api.get(`/admin/cases/${caseId}/checklist`);

/** POST /admin/cases/:caseId/checklist */
export const createChecklistTask = (caseId, payload) =>
  api.post(`/admin/cases/${caseId}/checklist`, payload);

/** GET  /admin/cases/:caseId/checklist/:taskId */
export const getChecklistTask = (caseId, taskId) =>
  api.get(`/admin/cases/${caseId}/checklist/${taskId}`);

/** PUT  /admin/cases/:caseId/checklist/:taskId */
export const updateChecklistTask = (caseId, taskId, payload) =>
  api.put(`/admin/cases/${caseId}/checklist/${taskId}`, payload);

/** PATCH /admin/cases/:caseId/checklist/:taskId/status */
export const updateChecklistTaskStatus = (caseId, taskId, status) =>
  api.patch(`/admin/cases/${caseId}/checklist/${taskId}/status`, { status });

/** DELETE /admin/cases/:caseId/checklist/:taskId */
export const deleteChecklistTask = (caseId, taskId) =>
  api.delete(`/admin/cases/${caseId}/checklist/${taskId}`);

// ─────────────────────────────────────────────────────────────────────────────
// CHECKLIST TRACKER  (CaseViewModal.vue Checklist Tracker tab)
// ─────────────────────────────────────────────────────────────────────────────

/** GET  /admin/cases/:caseId/checklist-tracker */
export const getChecklistTracker = (caseId) =>
  api.get(`/admin/cases/${caseId}/checklist-tracker`);

/** POST /admin/cases/:caseId/checklist-tracker */
export const createChecklistTrackerEntry = (caseId, payload) =>
  api.post(`/admin/cases/${caseId}/checklist-tracker`, payload);

/** PATCH /admin/cases/:caseId/checklist-tracker/:movementId/approve */
export const approveChecklistMovement = (caseId, movementId, approval_status) =>
  api.patch(
    `/admin/cases/${caseId}/checklist-tracker/${movementId}/approve`,
    { approval_status }
  );

// ─────────────────────────────────────────────────────────────────────────────
// FOLDER TRACKER  (CaseViewModal.vue Folder Tracker tab)
// ─────────────────────────────────────────────────────────────────────────────

/** GET  /admin/cases/:caseId/folder-tracker */
export const getFolderTracker = (caseId) =>
  api.get(`/admin/cases/${caseId}/folder-tracker`);

/** POST /admin/cases/:caseId/folder-tracker */
export const createFolderTrackerEntry = (caseId, payload) =>
  api.post(`/admin/cases/${caseId}/folder-tracker`, payload);

// ─────────────────────────────────────────────────────────────────────────────
// FORMATTERS  (used by CaseMaster.vue applyCasesResponse)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Convert raw API case rows into the shape the UI table expects.
 * Uses a pre-allocated array and a plain for-loop — fastest JS option.
 * Called by CaseMaster.vue → applyCasesResponse().
 */
export const formatCases = (rawArray) => {
  if (!Array.isArray(rawArray) || rawArray.length === 0) return [];

  const result = new Array(rawArray.length);
  for (let i = 0; i < rawArray.length; i++) {
    const r = rawArray[i];
    result[i] = {
      id:                 r.id,
      case_no:            r.case_no,
      case_code:          r.case_code,
      title:              r.title,
      category:           r.category_name       ?? '—',
      category_id:        r.category_id         ?? null,
      client:             r.client_name         ?? '—',
      client_id:          r.client_id           ?? null,
      lawyer:             r.lawyer_name         ?? '—',
      assigned_lawyer_id: r.assigned_lawyer_id  ?? null,
      clerk:              r.clerk_name          ?? '—',
      assigned_clerk_id:  r.assigned_clerk_id   ?? null,
      priority:           r.priority,
      case_status:        r.case_status,
      stage:              r.stage_name          ?? null,
      current_stage_id:   r.current_stage_id    ?? null,
      court_or_office:    r.court_or_office     ?? null,
      docket_no:          r.docket_no           ?? null,
      summary:            r.summary             ?? null,
      is_out:             r.is_out              ?? 0,
      created_at:         r.created_at,
      updated_at:         r.updated_at,
    };
  }
  return result;
};

/** Single-item wrapper — used by CaseMaster.vue openEdit(c) response. */
export const formatCase = (raw) => {
  if (!raw) return {};
  return formatCases([raw])[0];
};

// ─────────────────────────────────────────────────────────────────────────────
// CACHE MANAGEMENT
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Clear all lookup caches.
 * Called by CaseMaster.vue:
 *   - submitForm() after store/update
 *   - onCategoryCreated()
 * Also invalidates sessionStorage case-list keys.
 */
export const clearCache = () => {
  _cache.categories        = null;
  _cache.stages            = null;
  _cache.users             = null;
  _cache.categoriesTs      = 0;
  _cache.stagesTs          = 0;
  _cache.usersTs           = 0;
  _cache.categoriesPromise = null;
  _cache.stagesPromise     = null;
  _cache.usersPromise      = null;
};

/**
 * Bust only the users cache.
 * Called by CaseMaster.vue refreshUsers() on modal open.
 */
export const clearUsersCache = () => {
  _cache.users        = null;
  _cache.usersTs      = 0;
  _cache.usersPromise = null;
};

/**
 * Bust only the categories cache.
 * Called by CaseMaster.vue onCategoryCreated().
 */
export const clearCategoriesCache = () => {
  _cache.categories        = null;
  _cache.categoriesTs      = 0;
  _cache.categoriesPromise = null;
};