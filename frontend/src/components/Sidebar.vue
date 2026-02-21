<template>
  <aside class="sidebar" :class="{ collapsed }">

    <!-- Logo -->
    <div class="sidebar-logo">
      <div class="logo-icon">
   <svg xmlns="http://www.w3.org/2000/svg" 
     width="20" 
     height="20" 
     viewBox="0 0 24 24"
     fill="none" 
     stroke="currentColor" 
     stroke-width="1.8" 
     stroke-linecap="round" 
     stroke-linejoin="round">

  <!-- Center pole -->
  <line x1="12" y1="3" x2="12" y2="21"/>

  <!-- Top bar -->
  <line x1="5" y1="7" x2="19" y2="7"/>

  <!-- Left scale -->
  <path d="M7 7l-3 5h6l-3-5z"/>

  <!-- Right scale -->
  <path d="M17 7l-3 5h6l-3-5z"/>

  <!-- Base -->
  <line x1="8" y1="21" x2="16" y2="21"/>

</svg>
      </div>
      <transition name="fade-text">
        <div v-if="!collapsed" class="logo-text">
          <span class="logo-main">NICOLAS PINEDA</span>
          <span class="logo-sub">LAW OFFICE</span>
        </div>
      </transition>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
      <transition name="fade-text">
        <p v-if="!collapsed" class="nav-label">MAIN</p>
      </transition>

      <router-link
        v-for="item in visibleNav"
        :key="item.path"
        :to="item.path"
        class="nav-item"
        :class="{ active: route.path === item.path }"
      >
        <span class="nav-icon" v-html="item.icon"></span>
        <transition name="fade-text">
          <span v-if="!collapsed" class="nav-text">{{ item.label }}</span>
        </transition>
        <transition name="fade-text">
          <span v-if="!collapsed && item.badge" class="nav-badge">{{ item.badge }}</span>
        </transition>
      </router-link>
    </nav>

    <!-- Bottom user card -->
    <div class="sidebar-footer">
      <transition name="fade-text">
        <div v-if="!collapsed" class="user-card">
          <div class="uc-avatar">{{ userInitials }}</div>
          <div class="uc-info">
            <p class="uc-name">{{ userName }}</p>
            <p class="uc-role">{{ userRole }}</p>
          </div>
        </div>
      </transition>
      <div v-if="collapsed" class="uc-avatar uc-avatar--solo">{{ userInitials }}</div>

      <!-- Collapse toggle -->
      <button class="collapse-btn" @click="collapsed = !collapsed">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
          fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
          :style="{ transform: collapsed ? 'rotate(180deg)' : 'rotate(0deg)', transition: 'transform .3s' }">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
      </button>
    </div>

  </aside>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const route    = useRoute()
const collapsed = ref(false)
const { userName, userRole, userInitials, isAdmin, isLawyer, isClerk } = useAuth()

// ─── SVG icons as strings ────────────────────────────────────────
const icons = {
  dashboard: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>`,
  users:     `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
  logs:      `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>`,
  clients:   `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
  tasks:     `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>`,
  appointments: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>`,
}

// ─── All possible nav items with role access control ─────────────
// roles: [] means everyone, otherwise only listed roles can see it
const allNav = [
  { path: '/dashboard',      label: 'Dashboard',    icon: icons.dashboard,    roles: [] },
  { path: '/usermanagement', label: 'Users',         icon: icons.users,        roles: ['admin'] },
  { path: '/audittrail',     label: 'Activity Logs', icon: icons.logs,         roles: ['admin'] },
  { path: '/clients',        label: 'Clients',       icon: icons.clients,      roles: [] },
  { path: '/tasks',          label: 'Tasks',         icon: icons.tasks,        roles: [] },
  { path: '/appointments',   label: 'Appointments',  icon: icons.appointments, roles: [] },
]

const visibleNav = computed(() => {
  const role = userRole.value.toLowerCase()
  return allNav.filter(item => {
    if (item.roles.length === 0) return true          // everyone
    return item.roles.includes(role)                  // role-gated
  })
})
</script>

<style scoped>
.sidebar {
  width: 240px;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, #1a4972 0%, #0f2f4a 55%, #091e31 100%);
  transition: width .3s cubic-bezier(.4,0,.2,1);
  flex-shrink: 0;
  font-family: 'Segoe UI', sans-serif;
  position: relative;
  overflow: hidden;
}
.sidebar.collapsed { width: 68px; }

/* Logo */
.sidebar-logo {
  display: flex; align-items: center; gap: 12px;
  padding: 20px 16px 18px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  min-height: 68px;
}
.logo-icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: rgba(255,255,255,0.12);
  border: 1px solid rgba(255,255,255,0.18);
  display: flex; align-items: center; justify-content: center;
  color: white; flex-shrink: 0;
}
.logo-text { display: flex; flex-direction: column; gap: 1px; white-space: nowrap; overflow: hidden; }
.logo-main { font-size: 11px; font-weight: 700; color: white; letter-spacing: 0.06em; }
.logo-sub  { font-size: 9px;  font-weight: 500; color: rgba(255,255,255,0.4); letter-spacing: 0.1em; }

/* Nav */
.sidebar-nav { flex: 1; padding: 16px 10px; display: flex; flex-direction: column; gap: 2px; }
.nav-label {
  font-size: 9px; font-weight: 700; letter-spacing: 0.12em;
  color: rgba(255,255,255,0.3); padding: 0 8px; margin-bottom: 6px;
  white-space: nowrap; overflow: hidden;
}

.nav-item {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 10px;
  border-radius: 10px;
  text-decoration: none;
  color: rgba(255,255,255,0.6);
  font-size: 13px; font-weight: 500;
  transition: background .15s, color .15s;
  white-space: nowrap; overflow: hidden;
  position: relative;
}
.nav-item:hover {
  background: rgba(255,255,255,0.09);
  color: rgba(255,255,255,0.9);
}
.nav-item.active {
  background: rgba(255,255,255,0.15);
  color: white;
}
.nav-item.active::before {
  content: '';
  position: absolute; left: 0; top: 20%; bottom: 20%;
  width: 3px; border-radius: 0 3px 3px 0;
  background: rgba(255,255,255,0.7);
}

.nav-icon { display: flex; align-items: center; justify-content: center; flex-shrink: 0; width: 18px; }
.nav-text  { flex: 1; }
.nav-badge {
  font-size: 10px; font-weight: 700; padding: 1px 7px;
  border-radius: 99px; background: rgba(255,255,255,0.15); color: rgba(255,255,255,0.8);
}

/* Footer */
.sidebar-footer {
  padding: 12px 10px;
  border-top: 1px solid rgba(255,255,255,0.08);
  display: flex; align-items: center; gap: 10px;
}
.user-card { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
.uc-avatar {
  width: 30px; height: 30px; border-radius: 50%;
  background: rgba(255,255,255,0.15);
  border: 1.5px solid rgba(255,255,255,0.25);
  color: white; font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.uc-avatar--solo { margin: 0 auto 8px; }
.uc-info { min-width: 0; }
.uc-name { font-size: 11px; font-weight: 600; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0; }
.uc-role { font-size: 10px; color: rgba(255,255,255,0.4); text-transform: capitalize; margin: 1px 0 0; }

.collapse-btn {
  width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
  background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1);
  color: rgba(255,255,255,0.5); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background .15s;
}
.collapse-btn:hover { background: rgba(255,255,255,0.13); color: white; }

/* Text fade transition */
.fade-text-enter-active { transition: opacity .2s .05s, transform .2s .05s; }
.fade-text-leave-active { transition: opacity .12s, transform .12s; }
.fade-text-enter-from, .fade-text-leave-to { opacity: 0; transform: translateX(-4px); }
</style>