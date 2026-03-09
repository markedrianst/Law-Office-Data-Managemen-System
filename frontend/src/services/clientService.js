import api from '@/services/api';

export const getAll = async (params = {}) => {
  try {
    const response = await api.get('/admin/clients', { params });
    return response.data;
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: 'Network Error. Please check your connection.' };
  }
};

export const getClient = async (id) => {
  try {
    const response = await api.get(`/admin/clients/${id}`);
    return response.data;
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: 'Network Error. Please check your connection.' };
  }
};

export const create = async (payload) => {
  try {
    const response = await api.post('/admin/clients', payload);
    return response.data;
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: 'Network Error. Please check your connection.' };
  }
};

export const update = async (id, payload) => {
  try {
    const response = await api.put(`/admin/clients/${id}`, payload);
    return response.data;
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: 'Network Error. Please check your connection.' };
  }
};