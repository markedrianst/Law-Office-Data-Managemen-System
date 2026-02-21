// composables/useAuth.js
// ─────────────────────────────────────────────────────────────────
// Central auth composable — import this in Header, Sidebar, Account
// Handles your exact backend shape:
//   { id, role_id, full_name, email, status, role: { id, name, ... } }
// ─────────────────────────────────────────────────────────────────
import { ref, computed, onMounted, onUnmounted } from 'vue'

const parseStoredUser = () => {
  try {
    let raw = localStorage.getItem('user')
    if (!raw || raw === 'null' || raw === 'undefined') return null
    for (let i = 0; i < 3; i++) {
      try {
        const parsed = JSON.parse(raw)
        if (typeof parsed === 'string') { raw = parsed; continue }
        if (typeof parsed === 'object' && parsed !== null) return parsed
        break
      } catch { break }
    }
  } catch {}
  return null
}

export function useAuth() {
  const userObj = ref(parseStoredUser())

  const syncUser = () => { userObj.value = parseStoredUser() }

  onMounted(() => {
    window.addEventListener('storage', syncUser)
    // Re-read shortly after mount in case login just wrote to localStorage
    setTimeout(syncUser, 100)
  })
  onUnmounted(() => window.removeEventListener('storage', syncUser))

  // ── name: backend column is full_name ──────────────────────────
  const userName = computed(() => {
    const u = userObj.value
    if (!u) return 'User'
    // Try full_name first (your DB column), then fallbacks
    return u.full_name || u.name || u.username || 'User'
  })

  // ── role: backend returns nested { id, name } object ──────────
  // $user->load('role') → user.role = { id:1, name:'admin' }
  const userRole = computed(() => {
    const u = userObj.value
    if (!u) return 'Staff'
    // Handle nested role object OR flat string
    if (u.role && typeof u.role === 'object') return u.role.name || 'Staff'
    if (typeof u.role === 'string') return u.role
    return 'Staff'
  })

  const userRoleId = computed(() => {
    const u = userObj.value
    if (!u) return null
    if (u.role && typeof u.role === 'object') return u.role.id
    return u.role_id || null
  })

  const userInitials = computed(() => {
    const parts = userName.value.trim().split(/\s+/).filter(Boolean)
    if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    return parts[0]?.[0]?.toUpperCase() || 'U'
  })

  const userEmail = computed(() => userObj.value?.email || '')

  const isAdmin  = computed(() => userRole.value.toLowerCase() === 'admin')
  const isLawyer = computed(() => userRole.value.toLowerCase() === 'lawyer')
  const isClerk  = computed(() => userRole.value.toLowerCase() === 'clerk')

  // Force a re-read (call after login/profile update)
  const refreshUser = () => syncUser()

  return { userObj, userName, userRole, userRoleId, userEmail, userInitials, isAdmin, isLawyer, isClerk, refreshUser }
}