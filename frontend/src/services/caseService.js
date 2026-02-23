import api from '@/services/api';

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

export const getActivityLogs = (caseId) =>
  api.get(`/admin/cases/${caseId}/activity-logs`);

export const getCategories = () =>
  api.get('/admin/case-categories');

export const getAssignableUsers = () =>
  api.get('/admin/users/assignable');

export const formatCase = (raw) => ({
  id:                 raw.id,
  case_no:            raw.case_no,
  case_code:          raw.case_code,
  title:              raw.title,
  category:           raw.category_name          ?? '—',
  category_id:        raw.category_id,
  client:             raw.client_name            ?? '—',
  client_id:          raw.client_id,
  lawyer:             raw.lawyer_name            ?? '—',
  assigned_lawyer_id: raw.assigned_lawyer_id,
  clerk:              raw.clerk_name             ?? '—',
  assigned_clerk_id:  raw.assigned_clerk_id,
  priority:           raw.priority,
  intake_status:      raw.intake_status,
  case_status:        raw.case_status,
  court_or_office:    raw.court_or_office,
  docket_no:          raw.docket_no,
  summary:            raw.summary,
  created_at:         raw.created_at,
  updated_at:         raw.updated_at,
});