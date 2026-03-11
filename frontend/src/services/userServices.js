// services/UserService.js
import api from "@/services/api";

const CACHE_KEYS = {
  USERS_PREFIX: 'users_list_',
  USER_PREFIX: 'user_detail_'
};
const CACHE_DURATION = 30000; // 30 seconds

// In-memory cache
const memoryCache = {
  users: new Map(),
  userDetails: new Map()
};

const UserService = {
  /**
   * Get users with pagination
   */
  async getUsers(params = {}) {
    const cacheKey = CACHE_KEYS.USERS_PREFIX + JSON.stringify(params);
    
    // Check memory cache
    if (memoryCache.users.has(cacheKey)) {
      const cached = memoryCache.users.get(cacheKey);
      if (Date.now() - cached.timestamp < CACHE_DURATION) {
        return cached.data;
      }
      memoryCache.users.delete(cacheKey);
    }
    
    // Check sessionStorage
    try {
      const cached = sessionStorage.getItem(cacheKey);
      if (cached) {
        const { data, timestamp } = JSON.parse(cached);
        if (Date.now() - timestamp < CACHE_DURATION) {
          memoryCache.users.set(cacheKey, { data, timestamp: Date.now() });
          return data;
        }
        sessionStorage.removeItem(cacheKey);
      }
    } catch (e) {
      // Ignore cache errors
    }
    
    // Fetch from API
    const response = await api.get("/users", { params });
    
    let result;
    if (response.data?.data && response.data?.meta) {
      result = {
        data: response.data.data,
        meta: response.data.meta
      };
    } else if (Array.isArray(response.data)) {
      result = {
        data: response.data,
        meta: {
          current_page: 1,
          last_page: 1,
          per_page: response.data.length,
          total: response.data.length,
          from: 1,
          to: response.data.length
        }
      };
    } else {
      result = response.data;
    }
    
    // Save to caches
    const cacheData = { data: result, timestamp: Date.now() };
    memoryCache.users.set(cacheKey, cacheData);
    
    try {
      sessionStorage.setItem(cacheKey, JSON.stringify(cacheData));
    } catch (e) {
      // Ignore storage errors
    }
    
    return result;
  },

  /**
   * Get user by ID
   */
  async getUserById(id) {
    const cacheKey = CACHE_KEYS.USER_PREFIX + id;
    
    // Check memory cache
    if (memoryCache.userDetails.has(id)) {
      const cached = memoryCache.userDetails.get(id);
      if (Date.now() - cached.timestamp < CACHE_DURATION) {
        return cached.data;
      }
      memoryCache.userDetails.delete(id);
    }
    
    // Check sessionStorage
    try {
      const cached = sessionStorage.getItem(cacheKey);
      if (cached) {
        const { data, timestamp } = JSON.parse(cached);
        if (Date.now() - timestamp < CACHE_DURATION) {
          memoryCache.userDetails.set(id, { data, timestamp: Date.now() });
          return data;
        }
        sessionStorage.removeItem(cacheKey);
      }
    } catch (e) {
      // Ignore cache errors
    }
    
    // Fetch from API
    const response = await api.get(`/users/${id}`);
    const result = response.data?.data ?? response.data;
    
    // Save to caches
    const cacheData = { data: result, timestamp: Date.now() };
    memoryCache.userDetails.set(id, cacheData);
    
    try {
      sessionStorage.setItem(cacheKey, JSON.stringify(cacheData));
    } catch (e) {
      // Ignore storage errors
    }
    
    return result;
  },

  /**
   * Create user
   */
  async createUser(userData) {
    const formattedData = this.formatUserDataForApi(userData);
    const response = await api.post("/users", formattedData);
    
    // Clear user list caches
    this.clearUserListCaches();
    
    return response.data?.data ?? response.data;
  },

  /**
   * Update user
   */
  async updateUser(id, userData) {
    const formattedData = this.formatUserDataForApi(userData, true);
    const response = await api.put(`/users/${id}`, formattedData);
    
    // Clear caches
    this.clearUserCaches(id);
    
    return response.data?.data ?? response.data;
  },

  /**
   * Delete user
   */
  async deleteUser(id) {
    const response = await api.delete(`/users/${id}`);
    
    // Clear caches
    this.clearUserCaches(id);
    
    return response.data;
  },

  /**
   * Toggle user status
   */
  async toggleUserStatus(id) {
    const response = await api.patch(`/users/${id}/toggle-status`);
    
    // Clear caches
    this.clearUserCaches(id);
    
    return response.data?.data ?? response.data;
  },

  /**
   * Update password
   */
  async updatePassword(id, passwordData) {
    const response = await api.post(`/users/${id}/change-password`, passwordData);
    
    // Clear user cache (security)
    this.clearUserCaches(id);
    
    return response.data;
  },

  /**
   * Bulk delete users
   */
  async bulkDeleteUsers(ids) {
    const response = await api.delete("/users/bulk-delete", { data: { ids } });
    
    // Clear all user caches
    this.clearAllCaches();
    
    return response.data;
  },

  /**
   * Export users
   */
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

  /**
   * Clear user list caches
   */
  clearUserListCaches() {
    memoryCache.users.clear();
    
    try {
      const keysToRemove = [];
      for (let i = 0; i < sessionStorage.length; i++) {
        const key = sessionStorage.key(i);
        if (key?.startsWith(CACHE_KEYS.USERS_PREFIX)) {
          keysToRemove.push(key);
        }
      }
      keysToRemove.forEach(key => sessionStorage.removeItem(key));
    } catch (e) {
      // Ignore storage errors
    }
  },

  /**
   * Clear specific user caches
   */
  clearUserCaches(userId) {
    memoryCache.userDetails.delete(userId);
    memoryCache.users.clear();
    
    try {
      const keysToRemove = [];
      for (let i = 0; i < sessionStorage.length; i++) {
        const key = sessionStorage.key(i);
        if (key?.startsWith(CACHE_KEYS.USERS_PREFIX) || 
            key === CACHE_KEYS.USER_PREFIX + userId) {
          keysToRemove.push(key);
        }
      }
      keysToRemove.forEach(key => sessionStorage.removeItem(key));
    } catch (e) {
      // Ignore storage errors
    }
  },

  /**
   * Clear all caches
   */
  clearAllCaches() {
    memoryCache.users.clear();
    memoryCache.userDetails.clear();
    
    try {
      const keysToRemove = [];
      for (let i = 0; i < sessionStorage.length; i++) {
        const key = sessionStorage.key(i);
        if (key?.startsWith(CACHE_KEYS.USERS_PREFIX) || 
            key?.startsWith(CACHE_KEYS.USER_PREFIX)) {
          keysToRemove.push(key);
        }
      }
      keysToRemove.forEach(key => sessionStorage.removeItem(key));
    } catch (e) {
      // Ignore storage errors
    }
  },

  // ==================== FORMATTING FUNCTIONS ====================
  
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

  // ==================== VALIDATION FUNCTIONS ====================
  
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
    return user.name || user.full_name || 'Unknown User';
  },

  capitalizeFirst(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
  },

  // ==================== ERROR HANDLING ====================
  
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