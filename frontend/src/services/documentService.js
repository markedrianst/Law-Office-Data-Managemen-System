import api from '@/services/api';

const BASE = 'admin/documents';

// ─────────────────────────────────────────────
// READ
// ─────────────────────────────────────────────

export const getDocuments = async (params = {}) => {
  try {
    const response = await api.get(BASE, { params });
    return response.data;
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: 'Network Error. Please check your connection.' };
  }
};

export const getDocument = async (id) => {
  try {
    const response = await api.get(`${BASE}/${id}`);
    return response.data;
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: 'Network Error. Please check your connection.' };
  }
};

export const getActiveDocuments = async () => {
  try {
    const response = await api.get(`${BASE}/active`);
    return response.data;
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: 'Network Error. Please check your connection.' };
  }
};

// ─────────────────────────────────────────────
// WRITE
// ─────────────────────────────────────────────

export const store = async (payload) => {
  try {
    const response = await api.post(BASE, payload);
    return response.data;
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: 'Network Error. Please check your connection.' };
  }
};

export const update = async (id, payload) => {
  try {
    const response = await api.put(`${BASE}/${id}`, payload);
    return response.data;
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: 'Network Error. Please check your connection.' };
  }
};

  export const toggleActive = async (id) => {
    try {
      const response = await api.patch(`${BASE}/${id}/toggle-active`);
      return response.data;
    } catch (error) {
      if (error.response?.data) throw error.response.data;
      throw { message: 'Network Error. Please check your connection.' };
    }
};

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────

export const formatDocument = (raw) => ({
  id:                raw.id,
  type:              raw.type               ?? '',
  category:          raw.category           ?? '',
  requires_approval: Boolean(raw.requires_approval),
  is_active:         Boolean(raw.is_active),
  sort_order:        raw.sort_order         ?? 0,
  created_at:        raw.created_at         ?? null,
  updated_at:        raw.updated_at         ?? null,
});