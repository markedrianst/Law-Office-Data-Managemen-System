import { reactive, readonly, computed } from 'vue';
import { getCases, loadAllLookups, formatCases } from '@/services/caseService';
import UserService from '@/services/userServices';
import * as clientService from '@/services/clientService';
import { auditLogService } from '@/services/auditLogService';
import { getActiveDocuments } from '@/services/documentService';
import * as approvalService from '@/services/approvalService';

// ─────────────────────────────────────────────────────────────────────────────
// STATE DEFINITION
// ─────────────────────────────────────────────────────────────────────────────

const state = reactive({
  // Data Collections
  cases: [],
  users: [],
  clients: [],
  activityLogs: [],
  documentTypes: [],
  approvals: [],
  
  // Lookups/Metadata
  categories: [],
  stages: [],
  courts: [],
  
  // UI & Loading States
  isInitialized: false,
  isLoading: false,
  lastError: null,
  pendingApprovalsCount: 0,
  
  // Pagination/Filters (Global defaults)
  pagination: {
    cases: { current_page: 1, last_page: 1, total: 0, per_page: 15 },
    users: { current_page: 1, last_page: 1, total: 0, per_page: 15 },
    logs:  { current_page: 1, last_page: 1, total: 0, per_page: 15 },
    approvals: { current_page: 1, last_page: 1, total: 0, per_page: 15 },
  }
});

// ─────────────────────────────────────────────────────────────────────────────
// GETTERS (Computed State)
// ─────────────────────────────────────────────────────────────────────────────

const getters = {
  // Performance-optimized lookups using Maps
  userMap:     computed(() => new Map(state.users.map(u => [u.id, u.full_name || u.name]))),
  clientMap:   computed(() => new Map(state.clients.map(c => [c.id, c.full_name || c.name]))),
  categoryMap: computed(() => new Map(state.categories.map(c => [c.id, c.name]))),
  stageMap:    computed(() => new Map(state.stages.map(s => [s.id, s.name]))),

  getClientName:   (id) => getters.clientMap.value.get(id) || '—',
  getUserName:     (id) => getters.userMap.value.get(id) || '—',
  getCategoryName: (id) => getters.categoryMap.value.get(id) || '—',
  getStageName:    (id) => getters.stageMap.value.get(id) || '—',

  // Filtered lists
  activeUsers: computed(() => state.users.filter(u => u.status?.toLowerCase() === 'active')),
  recentLogs: computed(() => state.activityLogs.slice(0, 10)),
};

// ─────────────────────────────────────────────────────────────────────────────
// ACTIONS (State Mutations & API Calls)
// ─────────────────────────────────────────────────────────────────────────────

const actions = {
  /**
   * Initial Data Load
   * Fetches core data needed for the SPA to function smoothly.
   */
  async initialize(role = null, force = false) {
    if (state.isInitialized && !force) return;
    
    state.isLoading = true;
    state.lastError = null;
    
    try {
      // 1. Core data that everyone needs
      const promises = [
        getCases({ per_page: state.pagination.cases.per_page }),
        UserService.getUsers({ per_page: 100 }), // Reduced for speed
        clientService.getAll({ per_page: 100 }), // Reduced for speed
        lookupsCache.fetch(),
        getActiveDocuments()
      ];

      // 2. Role-based data
      const isAdmin = role?.toLowerCase() === 'admin';
      const isLawyer = role?.toLowerCase() === 'lawyer';

      if (isAdmin) {
        promises.push(auditLogService.getLogs({ per_page: 20 }));
        promises.push(auditLogService.getCaseActivityLogs({ per_page: 20 }));
      }
      
      if (isAdmin || isLawyer) {
        promises.push(approvalService.getPendingCount());
        promises.push(approvalService.getAllMovements({ status: 'PENDING', per_page: 20 }));
      }

      const results = await Promise.all(promises);
      const [casesRes, usersRes, clientsRes, lookups, docsRes] = results;

      // Process Lookups First
      state.categories = lookups.categories;
      state.stages = lookups.stages;
      state.courts = lookups.courts;
      state.users = usersRes.data || usersRes || [];
      state.clients = clientsRes.data || clientsRes || [];
      state.documentTypes = docsRes.data?.data || docsRes.data || [];

      // Process Cases with Lookups
      const rawCases = casesRes.data?.data || casesRes.data || [];
      state.cases = formatCases(rawCases).map(c => ({
        ...c,
        client: c.client === '—' ? getters.getClientName(c.client_id) : c.client,
        lawyer: c.lawyer === '—' ? getters.getUserName(c.assigned_lawyer_id) : c.lawyer,
        clerk: c.clerk === '—' ? getters.getUserName(c.assigned_clerk_id) : c.clerk,
      }));
      
      if (casesRes.data?.meta) state.pagination.cases = casesRes.data.meta;
      if (usersRes.meta) state.pagination.users = usersRes.meta;

      // Process Role-based data results
      let nextIdx = 5;
      if (isAdmin) {
        const sysRes = results[nextIdx++];
        const caseRes = results[nextIdx++];
        
        const sysData = (sysRes.data || []).map(l => ({ ...l, _type: 'system' }));
        const caseData = (caseRes.data || []).map(l => ({ ...l, _type: 'case' }));
        
        state.activityLogs = [...sysData, ...caseData].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        
        if (sysRes.meta) {
          state.pagination.logs = {
            ...sysRes.meta,
            total: (sysRes.meta.total || 0) + (caseRes.meta?.total || 0)
          };
        }
      }

      if (isAdmin || isLawyer) {
        state.pendingApprovalsCount = results[nextIdx++];
        const appRes = results[nextIdx++];
        state.approvals = appRes.data || [];
        if (appRes.meta) state.pagination.approvals = appRes.meta;
      }

      state.isInitialized = true;
    } catch (error) {
      console.error('Store initialization failed:', error);
      state.lastError = error.message || 'Failed to load initial data';
    } finally {
      state.isLoading = false;
    }
  },

  // --- Case Actions ---
  
  async refreshCases(params = {}) {
    state.isLoading = true;
    try {
      const res = await getCases({ ...params, per_page: state.pagination.cases.per_page });
      const raw = res.data?.data || res.data || [];
      state.cases = formatCases(raw).map(c => ({
        ...c,
        client: c.client === '—' ? getters.getClientName(c.client_id) : c.client,
        lawyer: c.lawyer === '—' ? getters.getUserName(c.assigned_lawyer_id) : c.lawyer,
        clerk: c.clerk === '—' ? getters.getUserName(c.assigned_clerk_id) : c.clerk,
      }));
      if (res.data?.meta) state.pagination.cases = res.data.meta;
    } finally {
      state.isLoading = false;
    }
  },

  // Optimistic UI helper for case updates
  updateCaseOptimistically(id, updates) {
    const index = state.cases.findIndex(c => c.id === id);
    if (index !== -1) {
      state.cases[index] = { ...state.cases[index], ...updates };
    }
  },

  // --- User Actions ---

  async refreshUsers(params = {}) {
    try {
      const res = await UserService.getUsers(params);
      state.users = res.data || [];
      if (res.meta) state.pagination.users = res.meta;
    } catch (error) {
      console.error('Failed to refresh users:', error);
    }
  },

  // --- Audit Logs ---

  async refreshLogs(params = {}) {
    const { type, ...rest } = params;
    try {
      const [sysRes, caseRes] = await Promise.all([
        (!type || type === 'system') 
          ? auditLogService.getLogs(rest) 
          : Promise.resolve({ data: [], meta: { total: 0 } }),
        (!type || type === 'case') 
          ? auditLogService.getCaseActivityLogs(rest) 
          : Promise.resolve({ data: [], meta: { total: 0 } })
      ]);

      const sysData = (sysRes.data || []).map(l => ({ ...l, _type: 'system' }));
      const caseData = (caseRes.data || []).map(l => ({ ...l, _type: 'case' }));
      
      state.activityLogs = [...sysData, ...caseData].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
      
      const meta = (sysRes.meta?.total !== undefined) ? sysRes.meta : caseRes.meta;
      if (meta) {
        state.pagination.logs = {
          ...meta,
          total: (sysRes.meta?.total || 0) + (caseRes.meta?.total || 0)
        };
      }
    } catch (error) {
      console.error('Failed to refresh logs:', error);
    }
  },

  // --- Approvals ---

  async refreshApprovals(params = {}) {
    try {
      const res = await approvalService.getAllMovements({ 
        ...params, 
        per_page: state.pagination.approvals.per_page 
      });
      state.approvals = res.data || [];
      if (res.meta) state.pagination.approvals = res.meta;
    } catch (error) {
      console.error('Failed to refresh approvals:', error);
    }
  },

  async refreshPendingCount() {
    try {
      state.pendingApprovalsCount = await approvalService.getPendingCount();
    } catch (error) {
      console.error('Failed to refresh pending count:', error);
    }
  },

  /**
   * Reset Store
   * Clears all state and stops initialization flags.
   * Call this on logout to prevent background requests.
   */
  reset() {
    state.cases = [];
    state.users = [];
    state.clients = [];
    state.activityLogs = [];
    state.documentTypes = [];
    state.approvals = [];
    state.categories = [];
    state.stages = [];
    state.courts = [];
    state.isInitialized = false;
    state.isLoading = false;
    state.lastError = null;
    state.pendingApprovalsCount = 0;
    
    // Reset pagination to defaults
    state.pagination = {
      cases: { current_page: 1, last_page: 1, total: 0, per_page: 15 },
      users: { current_page: 1, last_page: 1, total: 0, per_page: 15 },
      logs:  { current_page: 1, last_page: 1, total: 0, per_page: 15 },
      approvals: { current_page: 1, last_page: 1, total: 0, per_page: 15 },
    };
  }
};

// ─────────────────────────────────────────────────────────────────────────────
// INTERNAL HELPERS
// ─────────────────────────────────────────────────────────────────────────────

const lookupsCache = {
  async fetch() {
    return await loadAllLookups();
  }
};

// ─────────────────────────────────────────────────────────────────────────────
// EXPORTS
// ─────────────────────────────────────────────────────────────────────────────

export default {
  state: readonly(state),
  getters,
  actions
};
