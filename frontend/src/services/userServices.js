// services/UserService.js
import api from "@/services/api";

// Get all users with optional filters
export const getUsers = async (params = {}) => {
  try {
    const response = await api.get("/users", { params });
    
    // Handle different response structures
    if (response.data && response.data.data) {
      // Laravel pagination format with data wrapper
      return {
        data: response.data.data,
        meta: response.data.meta || {
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
          per_page: response.data.per_page || 10,
          total: response.data.total || 0
        }
      };
    }
    
    // If response is directly the array
    if (Array.isArray(response.data)) {
      return {
        data: response.data,
        meta: {
          current_page: 1,
          last_page: 1,
          per_page: response.data.length,
          total: response.data.length
        }
      };
    }
    
    return response.data;
  } catch (error) {
    console.error('Get users error:', error);
    if (error.response) {
      throw error.response.data;
    } else if (error.request) {
      throw { message: "No response from server" };
    } else {
      throw { message: error.message || "Network Error" };
    }
  }
};

// Get single user by ID
export const getUserById = async (id) => {
  try {
    const response = await api.get(`/users/${id}`);
    
    // Handle response with data wrapper
    if (response.data && response.data.data) {
      return response.data.data;
    }
    
    return response.data;
  } catch (error) {
    console.error('Get user by ID error:', error);
    if (error.response) {
      throw error.response.data;
    } else if (error.request) {
      throw { message: "No response from server" };
    } else {
      throw { message: error.message || "Network Error" };
    }
  }
};

// Create new user
export const createUser = async (userData) => {
  try {
    const formattedData = formatUserDataForApi(userData);
    console.log('Creating user with data:', formattedData); // For debugging
    
    const response = await api.post("/users", formattedData);
    
    // Handle response with data wrapper
    if (response.data && response.data.data) {
      return response.data.data;
    }
    
    return response.data;
  } catch (error) {
    console.error('Create user error:', error);
    if (error.response) {
      // Format validation errors for frontend
      if (error.response.status === 422) {
        const validationErrors = {};
        const errors = error.response.data.errors || {};
        
        Object.keys(errors).forEach(key => {
          // Map Laravel field names to form field names
          const fieldMap = {
            full_name: 'firstName',
            email: 'email',
            password: 'password',
            role: 'role',
            address: 'address',
            contact_number: 'contact',
            status: 'status'
          };
          
          const formField = fieldMap[key] || key;
          validationErrors[formField] = errors[key][0];
        });
        
        throw {
          message: error.response.data.message || 'Validation failed',
          errors: validationErrors
        };
      }
      throw error.response.data;
    } else if (error.request) {
      throw { message: "No response from server. Please check your connection." };
    } else {
      throw { message: error.message || "Network Error" };
    }
  }
};

// Update existing user
export const updateUser = async (id, userData) => {
  try {
    const formattedData = formatUserDataForApi(userData);
    console.log('Updating user with data:', formattedData); // For debugging
    
    const response = await api.put(`/users/${id}`, formattedData);
    
    // Handle response with data wrapper
    if (response.data && response.data.data) {
      return response.data.data;
    }
    
    return response.data;
  } catch (error) {
    console.error('Update user error:', error);
    if (error.response) {
      // Format validation errors for frontend
      if (error.response.status === 422) {
        const validationErrors = {};
        const errors = error.response.data.errors || {};
        
        Object.keys(errors).forEach(key => {
          // Map Laravel field names to form field names
          const fieldMap = {
            full_name: 'firstName',
            email: 'email',
            password: 'password',
            role: 'role',
            address: 'address',
            contact_number: 'contact',
            status: 'status'
          };
          
          const formField = fieldMap[key] || key;
          validationErrors[formField] = errors[key][0];
        });
        
        throw {
          message: error.response.data.message || 'Validation failed',
          errors: validationErrors
        };
      }
      throw error.response.data;
    } else if (error.request) {
      throw { message: "No response from server. Please check your connection." };
    } else {
      throw { message: error.message || "Network Error" };
    }
  }
};

// Delete user
export const deleteUser = async (id) => {
  try {
    const response = await api.delete(`/users/${id}`);
    return response.data;
  } catch (error) {
    console.error('Delete user error:', error);
    if (error.response) {
      throw error.response.data;
    } else if (error.request) {
      throw { message: "No response from server" };
    } else {
      throw { message: error.message || "Network Error" };
    }
  }
};

// Toggle user status
export const toggleUserStatus = async (id) => {
  try {
    const response = await api.patch(`/users/${id}/toggle-status`);
    
    // Handle response with data wrapper
    if (response.data && response.data.data) {
      return response.data.data;
    }
    
    return response.data;
  } catch (error) {
    console.error('Toggle user status error:', error);
    if (error.response) {
      throw error.response.data;
    } else if (error.request) {
      throw { message: "No response from server" };
    } else {
      throw { message: error.message || "Network Error" };
    }
  }
};

// Update user password
export const updatePassword = async (id, passwordData) => {
  try {
    const response = await api.post(`/users/${id}/change-password`, passwordData);
    return response.data;
  } catch (error) {
    console.error('Update password error:', error);
    if (error.response) {
      // Format validation errors for frontend
      if (error.response.status === 422) {
        const validationErrors = {};
        const errors = error.response.data.errors || {};
        
        Object.keys(errors).forEach(key => {
          validationErrors[key] = errors[key][0];
        });
        
        throw {
          message: error.response.data.message || 'Password validation failed',
          errors: validationErrors
        };
      }
      throw error.response.data;
    } else if (error.request) {
      throw { message: "No response from server" };
    } else {
      throw { message: error.message || "Network Error" };
    }
  }
};

// Bulk delete users
export const bulkDeleteUsers = async (ids) => {
  try {
    const response = await api.delete("/users/bulk-delete", { 
      data: { ids } 
    });
    return response.data;
  } catch (error) {
    console.error('Bulk delete users error:', error);
    if (error.response) {
      throw error.response.data;
    } else if (error.request) {
      throw { message: "No response from server" };
    } else {
      throw { message: error.message || "Network Error" };
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
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `users_export.${format}`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    
    return { success: true, message: 'Export successful' };
  } catch (error) {
    console.error('Export users error:', error);
    if (error.response) {
      // Handle blob error response
      if (error.response.data instanceof Blob) {
        const text = await error.response.data.text();
        try {
          const json = JSON.parse(text);
          throw json;
        } catch {
          throw { message: 'Export failed' };
        }
      }
      throw error.response.data;
    } else if (error.request) {
      throw { message: "No response from server" };
    } else {
      throw { message: error.message || "Network Error" };
    }
  }
};

// Helper: Format user data for API
export const formatUserDataForApi = (userData) => {
  const { firstName, middleName, lastName, address, contact, email, role, password, status } = userData;
  
  // Combine names - handle empty middle name gracefully
  const nameParts = [];
  if (firstName && firstName.trim()) nameParts.push(firstName.trim());
  if (middleName && middleName.trim()) nameParts.push(middleName.trim());
  if (lastName && lastName.trim()) nameParts.push(lastName.trim());
  const name = nameParts.join(' ').trim();
  
  const formattedData = {
    name: name || undefined,
    email: email || undefined,
    role: role || undefined,
    status: status || 'Active',
    address: address || undefined,
    contact_number: contact || undefined, // Map to your Laravel field name
  };

  // FIXED: Always include password if it's provided and not empty
  // Removed the exclusion for 'temppass1'
  if (password && password.trim()) {
    formattedData.password = password;
  }

  // Remove undefined fields
  Object.keys(formattedData).forEach(key => 
    formattedData[key] === undefined && delete formattedData[key]
  );

  return formattedData;
};

// Helper: Format user data from API for form
export const formatUserDataForForm = (apiData) => {
  if (!apiData) return {};
  
  // Handle different possible name structures
  let firstName = '';
  let middleName = '';
  let lastName = '';
  
  if (apiData.name) {
    const nameParts = apiData.name.split(' ').filter(Boolean);
    
    if (nameParts.length === 1) {
      firstName = nameParts[0];
    } else if (nameParts.length === 2) {
      firstName = nameParts[0];
      lastName = nameParts[1];
    } else if (nameParts.length >= 3) {
      firstName = nameParts[0];
      lastName = nameParts[nameParts.length - 1];
      middleName = nameParts.slice(1, -1).join(' ');
    }
  }

  return {
    firstName,
    middleName,
    lastName,
    address: apiData.address || '',
    contact: apiData.contact_number || '',
    email: apiData.email || '',
    role: apiData.role || '',
    status: apiData.status || 'Active',
    password: '' // Always empty for security
  };
};

// Helper: Validate email format
export const isValidEmail = (email) => {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
};

// Helper: Validate password strength
export const validatePassword = (password) => {
  if (!password) return { valid: false, message: 'Password is required' };
  if (password.length < 6) return { valid: false, message: 'Password must be at least 6 characters' };
  return { valid: true };
};

// Helper: Get user display name
export const getUserDisplayName = (user) => {
  if (!user) return '';
  return user.name || `${user.firstName || ''} ${user.lastName || ''}`.trim() || 'Unknown User';
};