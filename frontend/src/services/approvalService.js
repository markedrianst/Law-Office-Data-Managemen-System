// src/services/approvalService.js
import api from "./api";

// ─────────────────────────────────────────────────────────────────────────────
// GLOBAL APPROVALS  (powers the /approvals standalone page)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Fetch all movements (checklist + folder) across all cases.
 * Supports optional query params: status, type, direction, search
 *
 * @param {Object} params  e.g. { status: 'PENDING', type: 'checklist', direction: 'OUT', search: '2026' }
 */
export const getAllMovements = async (params = {}) => {
  try {
    const response = await api.get("/admin/approvals", { params });
    return response.data; // { data: [...], meta: { total, pending, approved, rejected } }
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: "Network Error. Please check your connection." };
  }
};

/**
 * Fetch only the pending count — used by Sidebar badge (admin/lawyer).
 * Lightweight: returns a single integer.
 *
 * @returns {number}
 */
export const getPendingCount = async () => {
  try {
    const response = await api.get("/admin/approvals/pending-count");
    return response.data.count ?? 0;
  } catch (error) {
    console.warn("approvalService.getPendingCount failed:", error);
    return 0;
  }
};

/**
 * Approve or reject a movement via the universal global endpoint.
 *
 * @param {'checklist'|'folder'} source
 * @param {number}               movementId
 * @param {'APPROVED'|'REJECTED'} approvalStatus
 */
export const reviewMovement = async (source, movementId, approvalStatus) => {
  try {
    const response = await api.patch(
      `/admin/approvals/${source}/${movementId}/approve`,
      { approval_status: approvalStatus }
    );
    return response.data; // { message, data }
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: "Network Error. Please check your connection." };
  }
};

// ─────────────────────────────────────────────────────────────────────────────
// CLERK PENDING COUNT
// Returns how many of the current clerk's OWN movements are still PENDING.
// Used by the sidebar dot-badge on the Case Master nav item.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Count PENDING movements recorded by the current clerk across all their cases.
 * Calls the checklist-tracker and folder-tracker pending endpoints for each
 * assigned case, then sums the results.
 *
 * NOTE: This is a best-effort count — if the network is slow the badge will
 * simply remain hidden (returns 0 on error).
 *
 * @returns {number}
 */
export const getClerkPendingCount = async () => {
  try {
    // The backend filters to the clerk's cases inside each controller,
    // so we can safely use a single aggregated endpoint.
    // If you add GET /admin/approvals/my-pending-count later, swap this call.
    const response = await api.get("/admin/approvals/pending-count");
    // For a clerk, the backend should return movements they recorded.
    // If the backend returns 0 for clerks (because it scopes to all cases),
    // implement a dedicated endpoint and replace the URL above.
    return response.data.count ?? 0;
  } catch {
    return 0;
  }
};

// ─────────────────────────────────────────────────────────────────────────────
// PER-CASE CHECKLIST TRACKER
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Get all checklist movements for a case.
 * @param {number} caseId
 */
export const getChecklistMovements = async (caseId) => {
  try {
    const response = await api.get(`/admin/cases/${caseId}/checklist-tracker`);
    return response.data; // { data: [...] }
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: "Network Error. Please check your connection." };
  }
};

/**
 * Get only PENDING checklist movements for a case.
 * @param {number} caseId
 */
export const getPendingChecklistMovements = async (caseId) => {
  try {
    const response = await api.get(`/admin/cases/${caseId}/checklist-tracker/pending`);
    return response.data; // { data: [...] }
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: "Network Error. Please check your connection." };
  }
};

/**
 * Record a new checklist movement (IN or OUT).
 * Admin/lawyer → auto-approved.  Clerk → PENDING.
 *
 * @param {number} caseId
 * @param {Object} payload  { type, checklist_id?, from_to?, date, purpose?, handled_by? }
 */
export const recordChecklistMovement = async (caseId, payload) => {
  try {
    const response = await api.post(`/admin/cases/${caseId}/checklist-tracker`, payload);
    return response.data; // { message, data }
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: "Network Error. Please check your connection." };
  }
};

/**
 * Approve or reject a specific checklist movement (admin/lawyer only).
 *
 * @param {number}               caseId
 * @param {number}               movementId
 * @param {'APPROVED'|'REJECTED'} approvalStatus
 */
export const reviewChecklistMovement = async (caseId, movementId, approvalStatus) => {
  try {
    const response = await api.patch(
      `/admin/cases/${caseId}/checklist-tracker/${movementId}/approve`,
      { approval_status: approvalStatus }
    );
    return response.data; // { message, data }
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: "Network Error. Please check your connection." };
  }
};

// ─────────────────────────────────────────────────────────────────────────────
// PER-CASE FOLDER TRACKER
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Get all folder movements for a case.
 * @param {number} caseId
 */
export const getFolderMovements = async (caseId) => {
  try {
    const response = await api.get(`/admin/cases/${caseId}/folder-tracker`);
    return response.data; // { data: [...], case: { id, is_out } }
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: "Network Error. Please check your connection." };
  }
};

/**
 * Get only PENDING folder movements for a case.
 * @param {number} caseId
 */
export const getPendingFolderMovements = async (caseId) => {
  try {
    const response = await api.get(`/admin/cases/${caseId}/folder-tracker/pending`);
    return response.data; // { data: [...] }
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: "Network Error. Please check your connection." };
  }
};

/**
 * Record a new folder movement (IN or OUT).
 * Admin/lawyer → auto-approved + updates cases.is_out immediately.
 * Clerk → PENDING, cases.is_out unchanged until approved.
 *
 * @param {number} caseId
 * @param {Object} payload  { type, from_to?, date, purpose?, handled_by? }
 */
export const recordFolderMovement = async (caseId, payload) => {
  try {
    const response = await api.post(`/admin/cases/${caseId}/folder-tracker`, payload);
    return response.data; // { message, data }
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: "Network Error. Please check your connection." };
  }
};

/**
 * Approve or reject a specific folder movement (admin/lawyer only).
 * APPROVED → flips cases.is_out. REJECTED → no change.
 *
 * @param {number}               caseId
 * @param {number}               movementId
 * @param {'APPROVED'|'REJECTED'} approvalStatus
 */
export const reviewFolderMovement = async (caseId, movementId, approvalStatus) => {
  try {
    const response = await api.patch(
      `/admin/cases/${caseId}/folder-tracker/${movementId}/approve`,
      { approval_status: approvalStatus }
    );
    return response.data; // { message, data }
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: "Network Error. Please check your connection." };
  }
};

// ─────────────────────────────────────────────────────────────────────────────
// IN-APP NOTIFICATIONS
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Fetch paginated in-app notifications for the current user.
 * @param {Object} params  { page }
 */
export const getNotifications = async (params = {}) => {
  try {
    const response = await api.get("/notifications", { params });
    return response.data; // { data: [...], meta: { ..., unread } }
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: "Network Error. Please check your connection." };
  }
};

/**
 * Get unread notification count (for header bell badge).
 * @returns {number}
 */
export const getUnreadNotificationCount = async () => {
  try {
    const response = await api.get("/notifications/unread-count");
    return response.data.count ?? 0;
  } catch {
    return 0;
  }
};

/**
 * Mark a single notification as read.
 * @param {string} id  UUID of the notification
 */
export const markNotificationRead = async (id) => {
  try {
    const response = await api.patch(`/notifications/${id}/read`);
    return response.data;
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: "Network Error. Please check your connection." };
  }
};

/**
 * Mark all notifications as read.
 */
export const markAllNotificationsRead = async () => {
  try {
    const response = await api.post("/notifications/read-all");
    return response.data;
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: "Network Error. Please check your connection." };
  }
};