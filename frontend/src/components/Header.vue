<template>
  <header class="header-bar">
    <!-- Page title -->
    <h1 class="page-title">{{ pageTitle }}</h1>

    <div class="header-right">
      <!-- Bell -->
      <button class="icon-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
          fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        <span class="bell-dot"></span>
      </button>

      <!-- User dropdown -->
      <div class="user-menu" ref="dropdownRef">
        <button class="user-btn" :class="{ active: isOpen }" @click="isOpen = !isOpen">
          <div class="avatar">{{ userInitials }}</div>
          <div class="user-info">
            <span class="user-name">{{ userName }}</span>
            <span class="user-role">{{ userRole }}</span>
          </div>
          <svg class="chevron" :class="{ flipped: isOpen }"
            xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </button>

        <transition name="menu">
          <div v-if="isOpen" class="dropdown">
            <div class="dropdown-header">
              <div class="dh-avatar">{{ userInitials }}</div>
              <div>
                <p class="dh-name">{{ userName }}</p>
                <p class="dh-role">{{ userRole }}</p>
              </div>
            </div>

            <div class="dropdown-body">
              <router-link to="/account" @click="isOpen = false" class="dd-item"
                :class="{ 'dd-item--active': route.path === '/account' }">
                <span class="dd-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                </span>
                Account Settings
              </router-link>

              <div class="dd-divider"></div>

              <button class="dd-item dd-item--danger" @click="logoutUser">
                <span class="dd-icon dd-icon--danger">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                  </svg>
                </span>
                Logout
              </button>
            </div>
          </div>
        </transition>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import {logout} from '@/services/auth'

const router = useRouter()
const route  = useRoute()
const { userName, userRole, userInitials } = useAuth()

const isOpen     = ref(false)
const dropdownRef = ref(null)

const pageTitle = computed(() => ({
  '/dashboard':      'Dashboard',
  '/usermanagement': 'User Management',
  '/audittrail':     'Activity Logs',
  '/clients':        'Clients',
  '/tasks':          'Tasks',
  '/appointments':   'Appointments',
  '/account':        'Account Settings',
}[route.path] || 'Dashboard'))

// Click outside
const handleOutside = e => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) isOpen.value = false
}
onMounted(()  => document.addEventListener('mousedown', handleOutside))
onUnmounted(() => document.removeEventListener('mousedown', handleOutside))

const logoutUser = async () => {
  isOpen.value = false
  try {
    await logout()
  } catch {}
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/')
}
</script>

<style scoped>
.header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  height: 64px;
  background: linear-gradient(135deg, #1a4972 0%, #0f2f4a 100%);
  border-bottom: 1px solid rgba(255,255,255,0.07);
  font-family: 'Segoe UI', sans-serif;
  position: relative;
  z-index: 40;
  flex-shrink: 0;
}

.page-title {
  font-size: 13px;
  font-weight: 600;
  color: rgba(255,255,255,0.9);
  letter-spacing: 0.02em;
  margin: 0;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Bell */
.icon-btn {
  position: relative;
  width: 36px; height: 36px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.12);
  background: rgba(255,255,255,0.07);
  color: rgba(255,255,255,0.75);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: background .15s;
}
.icon-btn:hover { background: rgba(255,255,255,0.13); }
.bell-dot {
  position: absolute; top: 7px; right: 7px;
  width: 7px; height: 7px; border-radius: 50%;
  background: #ef4444;
  border: 1.5px solid #0f2f4a;
}

/* User button */
.user-menu { position: relative; }
.user-btn {
  display: flex; align-items: center; gap: 10px;
  padding: 6px 12px 6px 6px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.16);
  background: rgba(255,255,255,0.08);
  cursor: pointer; transition: background .15s;
}
.user-btn:hover, .user-btn.active { background: rgba(255,255,255,0.15); }

.avatar {
  width: 32px; height: 32px; border-radius: 50%;
  background: linear-gradient(135deg, rgba(255,255,255,0.28), rgba(255,255,255,0.1));
  border: 1.5px solid rgba(255,255,255,0.32);
  color: white; font-size: 13px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.user-info {
  display: none;
  flex-direction: column; gap: 1px; text-align: left;
  max-width: 130px;
}
@media(min-width:640px) { .user-info { display: flex; } }

.user-name {
  font-size: 12px; font-weight: 600; color: white;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.user-role {
  font-size: 11px; color: rgba(255,255,255,0.5);
  text-transform: capitalize; white-space: nowrap;
}

.chevron { color: rgba(255,255,255,0.45); transition: transform .2s; flex-shrink: 0; }
.chevron.flipped { transform: rotate(180deg); }

/* Dropdown */
.dropdown {
  position: absolute; right: 0; top: calc(100% + 8px);
  width: 220px;
  background: rgba(7, 22, 38, 0.97);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 20px 50px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.07);
  z-index: 9999;
}

.dropdown-header {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}
.dh-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: linear-gradient(135deg, #1a4972, #2d6db5);
  color: white; font-size: 14px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.dh-name { font-size: 12px; font-weight: 700; color: white; margin: 0; }
.dh-role { font-size: 11px; color: rgba(255,255,255,0.4); margin: 2px 0 0; text-transform: capitalize; }

.dropdown-body { padding: 6px; }

.dd-item {
  display: flex; align-items: center; gap: 10px;
  width: 100%; padding: 9px 10px;
  border-radius: 10px; border: none; background: transparent;
  color: rgba(255,255,255,0.75); font-size: 13px; font-weight: 500;
  cursor: pointer; text-decoration: none; transition: background .15s, color .15s;
}
.dd-item:hover { background: rgba(255,255,255,0.09); color: white; }
.dd-item--active { background: rgba(255,255,255,0.1) !important; color: white !important; }
.dd-item--danger { color: rgba(255,160,160,0.85); }
.dd-item--danger:hover { background: rgba(239,68,68,0.12) !important; color: #fca5a5 !important; }

.dd-icon {
  width: 28px; height: 28px; border-radius: 8px;
  background: rgba(255,255,255,0.07);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.dd-icon--danger { background: rgba(239,68,68,0.12); }

.dd-divider { height: 1px; background: rgba(255,255,255,0.07); margin: 4px 6px; }

/* Transition */
.menu-enter-active, .menu-leave-active { transition: opacity .15s ease, transform .15s ease; }
.menu-enter-from, .menu-leave-to { opacity: 0; transform: translateY(-6px) scale(.97); }
</style>