// services/UserService.js
import api from "@/services/api";

const UserService = {
  async getUsers(params = {}) {
    const response = await api.get("/users", { params });
    if (response.data?.data && response.data?.meta) {
      return {
        data: response.data.data,
        meta: response.data.meta
      };
    }
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
  },

  async getUserById(id) {
    const response = await api.get(`/users/${id}`);
    return response.data?.data ?? response.data;
  },

  async createUser(userData) {
    const formattedData = this.formatUserDataForApi(userData);
    const response = await api.post("/users", formattedData);
    return response.data?.data ?? response.data;
  },

  async updateUser(id, userData) {
    const formattedData = this.formatUserDataForApi(userData, true);
    const response = await api.put(`/users/${id}`, formattedData);
    return response.data?.data ?? response.data;
  },

  async deleteUser(id) {
    const response = await api.delete(`/users/${id}`);
    return response.data;
  },

  async toggleUserStatus(id) {
    const response = await api.patch(`/users/${id}/toggle-status`);
    return response.data?.data ?? response.data;
  },

  async updatePassword(id, passwordData) {
    const response = await api.post(`/users/${id}/change-password`, passwordData);
    return response.data;
  },

  async bulkDeleteUsers(ids) {
    const response = await api.delete("/users/bulk-delete", { data: { ids } });
    return response.data;
  },

  async exportUsers(format = 'csv', filters = {}) {
    const response = await api.get("/users/export", {
      params: { format, ...filters },
      responseType: 'blob'
    });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `users_export.${format}`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    return { success: true, message: 'Export successful' };
  },

 formatUserDataForApi(userData, isUpdate = false) {
    const { firstName, middleName, lastName, address, contact, email, role, password, status } = userData;
    const nameParts = [firstName, middleName, lastName].filter(n => n?.trim());
    const fullName = nameParts.join(' ').trim();
    const formattedData = {
        name: fullName || undefined,
        email: email || undefined,
        role: role || undefined,
        address: address || undefined,
        contact_number: contact?.replace(/\D/g, '') || undefined,
    };
    
    // Handle password - only send if it's provided AND not empty
    if (password?.trim()) {
        formattedData.password = password;
    }
    
    if (status) {
        formattedData.status = status;
    }
    
    // Remove undefined/empty values
    Object.keys(formattedData).forEach(key => {
        if (formattedData[key] === undefined || formattedData[key] === '') {
            delete formattedData[key];
        }
    });
    
    return formattedData;
},

  formatUserDataForForm(apiData) {
    if (!apiData) return {};
    let firstName = '', middleName = '', lastName = '';
    const fullName = apiData.name || apiData.full_name || '';
    if (fullName) {
      const parts = fullName.split(' ').filter(Boolean);
      if (parts.length === 1) {
        firstName = parts[0];
      } else if (parts.length === 2) {
        firstName = parts[0];
        lastName = parts[1];
      } else {
        firstName = parts[0];
        lastName = parts[parts.length - 1];
        middleName = parts.slice(1, -1).join(' ');
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
      status: apiData.status ? this.capitalizeFirst(apiData.status) : 'Active',
      password: ''
    };
  },

  isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  },

  validatePassword(password, options = {}) {
    if (!password && options.required) {
      return { valid: false, message: 'Password is required' };
    }
    if (password && password.length < 6) {
      return { valid: false, message: 'Password must be at least 6 characters' };
    }
    return { valid: true };
  },

  isValidPHMobileNumber(number) {
    const digits = number.replace(/\D/g, '');
    return /^09\d{9}$/.test(digits);
  },

  formatPHMobileNumber(number) {
    const digits = number.replace(/\D/g, '').slice(0, 11);
    if (digits.length <= 4) return digits;
    if (digits.length <= 7) return `${digits.slice(0, 4)} ${digits.slice(4)}`;
    return `${digits.slice(0, 4)} ${digits.slice(4, 7)} ${digits.slice(7)}`;
  },

  getUserDisplayName(user) {
    if (!user) return '';
    return user.name || user.full_name || `${user.firstName || ''} ${user.lastName || ''}`.trim() || 'Unknown User';
  },

  capitalizeFirst(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
  },

  handleApiError(error) {
    if (error.response?.data?.errors) {
      const errors = {};
      Object.keys(error.response.data.errors).forEach(key => {
        errors[key] = error.response.data.errors[key][0];
      });
      return {
        message: error.response.data.message || 'Validation failed',
        errors
      };
    }
    return {
      message: error.response?.data?.message || error.message || 'An error occurred',
      errors: {}
    };
  }
};

export default UserService;