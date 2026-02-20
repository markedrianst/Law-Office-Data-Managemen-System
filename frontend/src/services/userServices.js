// services/UserService.js
import api from "@/services/api";

// Get all users with optional filters
export const getUsers = async (params = {}) => {
  try {
    const response = await api.get("/users", { params });
    return response.data;
  } catch (error) {
    if (error.response) {
      throw error.response.data;
    } else {
      throw { message: "Network Error" };
    }
  }
};

// Get single user by ID
export const getUserById = async (id) => {
  try {
    const response = await api.get(`/users/${id}`);
    return response.data;
  } catch (error) {
    if (error.response) {
      throw error.response.data;
    } else {
      throw { message: "Network Error" };
    }
  }
};

// Create new user
export const createUser = async (userData) => {
  try {
    const formattedData = formatUserDataForApi(userData);
    const response = await api.post("/users", formattedData);
    return response.data;
  } catch (error) {
    if (error.response) {
      throw error.response.data;
    } else {
      throw { message: "Network Error" };
    }
  }
};

// Update existing user
export const updateUser = async (id, userData) => {
  try {
    const formattedData = formatUserDataForApi(userData);
    const response = await api.put(`/users/${id}`, formattedData);
    return response.data;
  } catch (error) {
    if (error.response) {
      throw error.response.data;
    } else {
      throw { message: "Network Error" };
    }
  }
};

// Delete user
export const deleteUser = async (id) => {
  try {
    const response = await api.delete(`/users/${id}`);
    return response.data;
  } catch (error) {
    if (error.response) {
      throw error.response.data;
    } else {
      throw { message: "Network Error" };
    }
  }
};

// Toggle user status
export const toggleUserStatus = async (id) => {
  try {
    const response = await api.patch(`/users/${id}/toggle-status`);
    return response.data;
  } catch (error) {
    if (error.response) {
      throw error.response.data;
    } else {
      throw { message: "Network Error" };
    }
  }
};

// Update user password
export const updatePassword = async (id, passwordData) => {
  try {
    const response = await api.post(`/users/${id}/change-password`, passwordData);
    return response.data;
  } catch (error) {
    if (error.response) {
      throw error.response.data;
    } else {
      throw { message: "Network Error" };
    }
  }
};

// Bulk delete users
export const bulkDeleteUsers = async (ids) => {
  try {
    const response = await api.delete("/users/bulk-delete", { data: { ids } });
    return response.data;
  } catch (error) {
    if (error.response) {
      throw error.response.data;
    } else {
      throw { message: "Network Error" };
    }
  }
};

// Export users
export const exportUsers = async (format = 'csv', filters = {}) => {
  try {
    const response = await api.get("/users/export", {
      params: { format, ...filters },
      responseType: 'blob'
    });
    return response.data;
  } catch (error) {
    if (error.response) {
      throw error.response.data;
    } else {
      throw { message: "Network Error" };
    }
  }
};

// Helper: Format user data for API
export const formatUserDataForApi = (userData) => {
  const { firstName, middleName, lastName, address, contact, email, role, password, status } = userData;
  
  // Combine names
  const name = [firstName, middleName, lastName].filter(Boolean).join(' ');
  
  const formattedData = {
    name,
    email,
    role,
    status: status || 'Active',
    address,
    contact_number: contact, // Map to your Laravel field name
    password: password || undefined
  };

  // Remove empty fields
  Object.keys(formattedData).forEach(key => 
    formattedData[key] === undefined && delete formattedData[key]
  );

  return formattedData;
};

// Helper: Format user data from API for form
export const formatUserDataForForm = (apiData) => {
  const nameParts = apiData.name.split(' ');
  const firstName = nameParts[0] || '';
  const lastName = nameParts.length > 1 ? nameParts[nameParts.length - 1] : '';
  const middleName = nameParts.length > 2 ? nameParts.slice(1, -1).join(' ') : '';

  return {
    firstName,
    middleName,
    lastName,
    address: apiData.address || '',
    contact: apiData.contact_number || '',
    email: apiData.email,
    role: apiData.role,
    status: apiData.status || 'Active',
    password: ''
  };
};