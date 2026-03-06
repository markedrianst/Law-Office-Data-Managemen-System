import api from '@/services/api';

// ─────────────────────────────────────────────────────────────────────────────
// COURTS & OFFICES
// ─────────────────────────────────────────────────────────────────────────────

export const getCourts = (params = {}) =>
  api.get('/admin/courts', { params });

export const getCourt = (id) =>
  api.get(`/admin/courts/${id}`);

export const store = (payload) =>
  api.post('/admin/courts', payload);

export const update = (id, payload) =>
  api.put(`/admin/courts/${id}`, payload);

export const toggleActive = (id) =>
  api.patch(`/admin/courts/${id}/toggle-active`);

export const reorder = (items) =>
  api.post('/admin/courts/reorder', { items });

// ─────────────────────────────────────────────────────────────────────────────
// LOOKUPS
// ─────────────────────────────────────────────────────────────────────────────

export const getActiveCourts = () =>
  api.get('/admin/courts/active');

export const getCourtTypes = () =>
  api.get('/admin/courts/types');

// ─────────────────────────────────────────────────────────────────────────────
// FORMATTER
// ─────────────────────────────────────────────────────────────────────────────

export const formatCourt = (raw) => ({
  id:         raw.id,
  name:       raw.name,
  type:       raw.type        ?? null,
  address:    raw.address     ?? null,
  is_active:  raw.is_active   ?? true,
  sort_order: raw.sort_order  ?? 0,
  notes:      raw.notes       ?? null,
  created_at: raw.created_at,
  updated_at: raw.updated_at,
});