/**
 * documentService.js
 *
 * Mirrors the courtService pattern.
 * Assumes your Axios instance is at @/services/api and already handles:
 *   - baseURL
 *   - withCredentials: true  (Sanctum session cookie)
 *   - CSRF cookie fetch before mutating requests
 */

import api from '@/services/api';

const BASE = 'admin/documents'; // maps to /api/documents on your Laravel backend

// ─────────────────────────────────────────────
// READ
// ─────────────────────────────────────────────

/**
 * Fetch paginated document types.
 *
 * Supported params:
 *   search        string
 *   category      'Pleading'|'Letter'|'Evidence'|'Court Issuance'|'Other'
 *   is_active     'true'|'false'
 *   sort_by       string   (default: sort_order)
 *   sort_direction 'asc'|'desc'
 *   page          number
 *   per_page      number
 */
export const getDocuments = (params = {}) =>
  api.get(BASE, { params });

/**
 * Fetch a single document type by id.
 */
export const getDocument = (id) =>
  api.get(`${BASE}/${id}`);

/**
 * Fetch all active document types — useful for dropdowns.
 * Returns a flat array, not paginated.
 */
export const getActiveDocuments = () =>
  api.get(`${BASE}/active`);

// ─────────────────────────────────────────────
// WRITE
// ─────────────────────────────────────────────

/**
 * Create a new document type.
 *
 * @param {Object} payload
 * @param {string}  payload.type              required
 * @param {string}  payload.category          required  enum: Pleading|Letter|Evidence|Court Issuance|Other
 * @param {boolean} payload.requires_approval default false
 * @param {boolean} payload.is_active         default true
 * @param {number}  payload.sort_order        default 0
 */
export const store = (payload) =>
  api.post(BASE, payload);

/**
 * Update an existing document type.
 *
 * @param {number} id
 * @param {Object} payload  same shape as store()
 */
export const update = (id, payload) =>
  api.put(`${BASE}/${id}`, payload);

/**
 * Toggle is_active on the server (PATCH).
 * Backend handler should flip the boolean and return the updated record.
 *
 * @param {number} id
 */
export const toggleActive = (id) =>
  api.patch(`${BASE}/${id}/toggle-active`);

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────

/**
 * Normalise a raw API record into the shape the UI expects.
 * Call this inside .map() after fetching.
 *
 * @param {Object} raw  raw record from the API
 * @returns {Object}
 */
export const formatDocument = (raw) => ({
  id:               raw.id,
  type:             raw.type              ?? '',
  category:         raw.category          ?? '',
  requires_approval:Boolean(raw.requires_approval),
  is_active:        Boolean(raw.is_active),
  sort_order:       raw.sort_order        ?? 0,
  created_at:       raw.created_at        ?? null,
  updated_at:       raw.updated_at        ?? null,
});