import api from '@/services/api';

// ─────────────────────────────────────────────────────────────────────────────
// CASES
// ─────────────────────────────────────────────────────────────────────────────

export const getCases = (params = {}) =>
  api.get('/admin/cases', { params });

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

export const getActivityLogs = (caseId) =>
  api.get(`/admin/cases/${caseId}/activity-logs`);

// ─────────────────────────────────────────────────────────────────────────────
// STAGES
// ─────────────────────────────────────────────────────────────────────────────

export const getStages = () =>
  api.get('/admin/master-data/case-stages');

export const getStageHistory = (caseId) =>
  api.get(`/admin/cases/${caseId}/stages/history`);

export const updateStage = (caseId, payload) =>
  api.put(`/admin/cases/${caseId}/stage`, payload);

// ─────────────────────────────────────────────────────────────────────────────
// LOOKUPS
// ─────────────────────────────────────────────────────────────────────────────

export const getCategories = () =>
  api.get('/admin/case-categories');

export const getAssignableUsers = () =>
  api.get('/admin/users/assignable');

// ─────────────────────────────────────────────────────────────────────────────
// FORMATTER
// ─────────────────────────────────────────────────────────────────────────────

export const formatCase = (raw) => ({
  id:                 raw.id,
  case_no:            raw.case_no,
  case_code:          raw.case_code,
  title:              raw.title,
  category:           raw.category_name      ?? '—',
  category_id:        raw.category_id        ?? null,
  client:             raw.client_name        ?? '—',
  client_id:          raw.client_id          ?? null,
  lawyer:             raw.lawyer_name        ?? '—',
  assigned_lawyer_id: raw.assigned_lawyer_id ?? null,
  clerk:              raw.clerk_name         ?? '—',
  assigned_clerk_id:  raw.assigned_clerk_id  ?? null,
  priority:           raw.priority,
  case_status:        raw.case_status,
  stage:              raw.stage_name         ?? null,
  current_stage_id:   raw.current_stage_id   ?? null,
  court_or_office:    raw.court_or_office    ?? null,
  docket_no:          raw.docket_no          ?? null,
  summary:            raw.summary            ?? null,
  created_at:         raw.created_at,
  updated_at:         raw.updated_at,
});