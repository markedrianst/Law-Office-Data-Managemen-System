import api from '@/services/api';

export const getAll = (params = {}) =>
  api.get('/admin/clients', { params });

export const getClient = (id) =>
  api.get(`/admin/clients/${id}`);

export const create = (payload) =>
  api.post('/admin/clients', payload);

export const update = (id, payload) =>
  api.put(`/admin/clients/${id}`, payload);