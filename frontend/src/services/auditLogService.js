// src/services/auditLogService.js
import api from './api';

export const auditLogService = {
  /**
   * Get audit logs with filters
   * @param {Object} params - { email, status, action, date_from, date_to, page, per_page }
   */
  async getLogs(params = {}) {
    try {
      const response = await api.get('/audit-logs', { params });
      return response.data;
    } catch (error) {
      console.error('Get audit logs error:', error);
      if (error.response) {
        throw error.response.data;
      } else if (error.request) {
        throw { message: 'No response from server' };
      } else {
        throw { message: error.message || 'Network Error' };
      }
    }
  },

  /**
   * Get single audit log by ID
   * @param {number} id
   */
  async getLogById(id) {
    try {
      const response = await api.get(`/audit-logs/${id}`);
      return response.data;
    } catch (error) {
      console.error('Get audit log error:', error);
      if (error.response) {
        throw error.response.data;
      } else {
        throw { message: error.message || 'Network Error' };
      }
    }
  },

  /**
   * Export logs as CSV
   * @param {Object} filters - Same filters as getLogs
   */
  async exportLogs(filters = {}) {
    try {
      const response = await api.get('/audit-logs/export/csv', {
        params: filters,
        responseType: 'blob'
      });
      
      // Create download link
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `audit_logs_${new Date().toISOString().split('T')[0]}.csv`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
      
      return { success: true, message: 'Export successful' };
    } catch (error) {
      console.error('Export logs error:', error);
      if (error.response) {
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
      } else {
        throw { message: error.message || 'Network Error' };
      }
    }
  },

  /**
   * Get available actions for filter dropdown
   */
  async getActions() {
    try {
      const response = await api.get('/audit-logs/filters/actions');
      return response.data;
    } catch (error) {
      console.error('Get actions error:', error);
      return [];
    }
  },

  /**
   * Format action for display
   * @param {string} action
   */
  formatAction(action) {
    const map = {
      login: 'Login',
      logout: 'Logout',
      password_change: 'Password Change',
      user_create: 'User Created',
      user_update: 'User Updated',
      user_delete: 'User Deleted',
      user_create_failed: 'Creation Failed',
      activated: 'Activated',
      deactivated: 'Deactivated'
    };
    return map[action] || action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
  },

  /**
   * Get icon for action
   * @param {string} action
   */
  getActionIcon(action) {
    const map = {
      login: '→',
      logout: '←',
      password_change: '🔑',
      user_create: '+',
      user_update: '✎',
      user_delete: '−',
      user_create_failed: '✗',
      activated: '✓',
      deactivated: '✕'
    };
    return map[action] || '•';
  },

  /**
   * Get CSS class for action
   * @param {string} action
   */
  getActionClass(action) {
    if (action.includes('login')) return 'bg-blue-100 text-blue-700';
    if (action.includes('logout')) return 'bg-slate-100 text-slate-600';
    if (action.includes('password')) return 'bg-amber-100 text-amber-700';
    if (action.includes('create')) return 'bg-emerald-100 text-emerald-700';
    if (action.includes('update')) return 'bg-purple-100 text-purple-700';
    if (action.includes('delete')) return 'bg-red-100 text-red-600';
    if (action.includes('activate')) return 'bg-emerald-100 text-emerald-700';
    if (action.includes('deactivate')) return 'bg-red-100 text-red-600';
    return 'bg-slate-100 text-slate-500';
  },

  /**
   * Format date for display
   * @param {string} dateString
   */
  formatDate(dateString) {
    if (!dateString) return '—';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-PH', {
      year: 'numeric',
      month: 'short',
      day: '2-digit'
    });
  },

  /**
   * Format time for display
   * @param {string} dateString
   */
  formatTime(dateString) {
    if (!dateString) return '—';
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-PH', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: true
    });
  },

  /**
   * Get initials from email
   * @param {string} email
   */
  getInitials(email) {
    if (!email) return '?';
    return email.charAt(0).toUpperCase();
  }
};