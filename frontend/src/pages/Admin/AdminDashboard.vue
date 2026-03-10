<template>
  <div class="dashboard-container">
    <!-- Top Bar -->
    <div class="top-bar">
      <div class="welcome-section">
        <h1 class="page-title">Dashboard</h1>
        <p class="welcome-text">Welcome back — here's what's happening today.</p>
      </div>
      <div class="top-bar-right">
        <span class="date-badge">{{ currentDate }}</span>
        <div class="live-badge">
          <span class="live-dot"></span>
          Live
        </div>
      </div>
    </div>

    <!-- Stats Cards with Real Data -->
    <div class="stats-grid">
      <div class="stat-card" v-for="stat in stats" :key="stat.label">
        <div class="stat-icon">{{ stat.icon }}</div>
        <div class="stat-content">
          <span class="stat-label">{{ stat.label }}</span>
          <span class="stat-value">{{ stat.value }}</span>
        </div>
        <span class="stat-trend" :class="stat.trend >= 0 ? 'trend-up' : 'trend-down'">
          {{ stat.trend > 0 ? '+' : '' }}{{ stat.trend }}%
        </span>
        <div class="stat-progress" :style="{ width: stat.progress + '%', backgroundColor: stat.color }"></div>
      </div>
    </div>

    <!-- Main Grid -->
    <div class="main-grid">
      <!-- Recent Activity Panel with Real User Names -->
      <div class="panel">
        <div class="panel-header">
          <h3 class="panel-title">Recent Activity</h3>
          <button class="panel-link" @click="viewAllActivities">View all</button>
        </div>
        <div class="activity-list">
          <div v-if="recentActivities.length === 0" class="empty-state">
            <p class="empty-text">No recent activities</p>
          </div>
          <div v-for="activity in recentActivities" :key="activity.id" class="activity-item">
            <div class="activity-icon" :class="activity.type">
              {{ activity.icon }}
            </div>
            <div class="activity-content">
              <div class="activity-header">
                <span class="activity-type-badge" :class="activity.type">{{ activity.typeLabel }}</span>
                <span class="activity-time">{{ activity.time }}</span>
              </div>
              <p class="activity-text">
                <strong class="activity-user">{{ activity.userName }}</strong>
                {{ activity.action }}
                <span v-if="activity.target" class="activity-target">{{ activity.target }}</span>
              </p>
              <div v-if="activity.details" class="activity-details">
                <span v-if="activity.details.from !== undefined" class="change-badge">
                  <span class="from-value">{{ formatValue(activity.details.from) }}</span>
                  <svg class="arrow-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor">
                    <path d="M5 12L10 8 5 4" stroke-width="1.5"/>
                  </svg>
                  <span class="to-value">{{ formatValue(activity.details.to) }}</span>
                </span>
                <span v-else-if="activity.details.note" class="note">{{ activity.details.note }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Schedules Panel -->
      <div class="panel">
        <div class="panel-header">
          <h3 class="panel-title">Today's Schedules</h3>
          <button class="panel-link" @click="viewAllSchedules">View all</button>
        </div>
        <div class="schedule-list">
          <div v-if="schedules.length === 0" class="empty-state">
            <p class="empty-text">No schedules for today</p>
          </div>
          <div v-for="schedule in schedules" :key="schedule.id" class="schedule-item">
            <div class="schedule-time">
              <span class="time-badge">{{ schedule.time }}</span>
            </div>
            <div class="schedule-content">
              <p class="schedule-title">{{ schedule.title }}</p>
              <p class="schedule-meta">
                <span class="schedule-type" :class="`type-${schedule.type}`">{{ schedule.type }}</span>
                <span class="schedule-case">{{ schedule.case_no }}</span>
              </p>
            </div>
            <div class="schedule-participant">
              <div class="participant-avatar">{{ getInitials(schedule.participant) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cases Overview Panel -->
      <div class="panel">
        <div class="panel-header">
          <h3 class="panel-title">Cases Overview</h3>
          <button class="panel-link" @click="viewAllCases">View all</button>
        </div>
        <div class="cases-overview">
          <div class="case-stat-item">
            <span class="case-stat-label">Active Cases</span>
            <span class="case-stat-value">{{ caseStats.active }}</span>
          </div>
          <div class="case-stat-item">
            <span class="case-stat-label">Pending</span>
            <span class="case-stat-value">{{ caseStats.pending }}</span>
          </div>
          <div class="case-stat-item">
            <span class="case-stat-label">Closed</span>
            <span class="case-stat-value">{{ caseStats.closed }}</span>
          </div>
          <div class="case-stat-item">
            <span class="case-stat-label">Archived</span>
            <span class="case-stat-value">{{ caseStats.archived }}</span>
          </div>
        </div>
        <div class="case-progress">
          <div class="progress-label">
            <span>Completion Rate</span>
            <span>{{ caseStats.completionRate }}%</span>
          </div>
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: caseStats.completionRate + '%' }"></div>
          </div>
        </div>
      </div>

      <!-- Folder Movements Panel -->
      <div class="panel">
        <div class="panel-header">
          <h3 class="panel-title">Folder Movements</h3>
          <button class="panel-link" @click="viewAllMovements">View all</button>
        </div>
        <div class="movement-list">
          <div v-if="folderMovements.length === 0" class="empty-state">
            <p class="empty-text">No recent folder movements</p>
          </div>
          <div v-for="movement in folderMovements" :key="movement.id" class="movement-item">
            <div class="movement-icon" :class="movement.type.toLowerCase()">
              <svg v-if="movement.type === 'OUT'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
              </svg>
              <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
            </div>
            <div class="movement-content">
              <p class="movement-text">
                <strong>{{ movement.case_code || movement.case_no }}</strong> 
                <span class="movement-purpose">{{ movement.purpose || movement.task || 'Folder movement' }}</span>
              </p>
              <span class="movement-meta">
                <span class="handler">{{ movement.handled_by || movement.recorder?.full_name }}</span>
                <span class="dot">•</span>
                <span class="date">{{ formatMovementDate(movement.date) }}</span>
                <span class="approval-badge" :class="`approval-${(movement.approval_status || 'pending').toLowerCase()}`">
                  {{ movement.approval_status || 'PENDING' }}
                </span>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Users Table -->
    <div class="panel users-panel">
      <div class="panel-header">
        <h3 class="panel-title">System Users</h3>
        <div class="table-controls">
          <div class="search-box">
            <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="11" cy="11" r="8"/>
              <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Search users..." 
              class="search-input"
            />
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Role</th>
              <th>Status</th>
              <th>Last Login</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in filteredUsers" :key="user.id" class="table-row">
              <td>
                <div class="user-cell">
                  <div class="user-avatar">{{ getUserInitials(user.full_name || user.name) }}</div>
                  <div>
                    <div class="user-name">{{ user.full_name || user.name }}</div>
                    <div class="user-email">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td>
                <span class="role-badge" :class="`role-${(user.role?.name || user.role || '').toLowerCase()}`">
                  {{ user.role?.name || user.role || '—' }}
                </span>
              </td>
              <td>
                <span class="status-badge" :class="`status-${user.is_active ? 'active' : 'inactive'}`">
                  {{ user.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="text-muted">{{ formatLastLogin(user.last_login) }}</td>
            </tr>
            <tr v-if="filteredUsers.length === 0">
              <td colspan="4" class="text-center text-muted py-4">No users found</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Footer -->
    <div class="footer">
      <p>© {{ currentYear }} Pineda Law Office. All rights reserved.</p>
      <p>Document Management System</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import store from '@/store'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { isAdmin, isLawyer, userRole } = useAuth()

// Global Store Integration
const users = computed(() => store.state.users || [])
const cases = computed(() => store.state.cases || [])
const activityLogs = computed(() => store.state.activityLogs || [])
const approvals = computed(() => store.state.approvals || [])
const folders = computed(() => store.state.folders || [])
const documents = computed(() => store.state.documents || [])

// Stats with Real Data
const stats = computed(() => {
  const today = new Date().toISOString().split('T')[0]
  
  // Real login count for today
  const todayLogins = activityLogs.value.filter(l => 
    l.action === 'login' && 
    l.status === 'success' && 
    l.created_at?.startsWith(today)
  ).length

  // Real document count (folders + checklist items)
  const folderCount = approvals.value.filter(a => a._source === 'folder').length
  const checklistCount = approvals.value.filter(a => a._source === 'checklist').length
  const totalDocuments = folderCount + checklistCount

  console.log('folderCount:', folderCount)
  console.log('checklistCount:', checklistCount)
  console.log('totalDocuments:', totalDocuments)
  console.log('todayLogins:', todayLogins)

  // Calculate trends (compare with yesterday's data)
  const yesterday = new Date()
  yesterday.setDate(yesterday.getDate() - 1)
  const yesterdayStr = yesterday.toISOString().split('T')[0]
  
  const yesterdayLogins = activityLogs.value.filter(l => 
    l.action === 'login' && 
    l.status === 'success' && 
    l.created_at?.startsWith(yesterdayStr)
  ).length

  const loginTrend = yesterdayLogins > 0 
    ? Math.round(((todayLogins - yesterdayLogins) / yesterdayLogins) * 100)
    : todayLogins > 0 ? 100 : 0

  return [
    { 
      label: 'Total Users', 
      value: users.value.length, 
      trend: 12, // You can calculate this from user creation logs
      progress: 100, 
      color: '#1a4972', 
      icon: '👥' 
    },
    { 
      label: 'Active Cases', 
      value: cases.value.filter(c => c.case_status === 'active').length, 
      trend: 8, 
      progress: 72, 
      color: '#3b82f6', 
      icon: '💼' 
    },
    { 
      label: 'Documents', 
      value: totalDocuments, 
      trend: 23, 
      progress: 88, 
      color: '#10b981', 
      icon: '📄' 
    },
    { 
      label: 'Logins Today', 
      value: todayLogins, 
      trend: loginTrend, 
      progress: todayLogins > 0 ? Math.min(100, todayLogins * 10) : 0, 
      color: '#f97316', 
      icon: '🔓' 
    }
  ]
})

const caseStats = computed(() => {
  const c = cases.value
  const active = c.filter(x => x.case_status === 'active').length
  const pending = c.filter(x => x.case_status === 'pending').length
  const closed = c.filter(x => x.case_status === 'closed').length
  const archived = c.filter(x => x.case_status === 'archived').length
  const total = c.length || 1
  
  return {
    active,
    pending,
    closed,
    archived,
    completionRate: Math.round((closed / total) * 100)
  }
})

// Recent Activities with Proper User Names
const recentActivities = computed(() => {
  // Create a map of emails to user names for quick lookup
  const userMap = new Map()
  users.value.forEach(user => {
    if (user.email) {
      userMap.set(user.email.toLowerCase(), user.full_name || user.name || user.email.split('@')[0])
    }
  })

  return activityLogs.value.slice(0, 8).map(log => {
    // Find user name from various sources
    let userName = 'System'
    let userEmail = ''

    if (log.user?.name) {
      userName = log.user.name
      userEmail = log.user.email
    } else if (log.email_attempted) {
      userEmail = log.email_attempted
      // Try to find user by email
      const mappedName = userMap.get(log.email_attempted.toLowerCase())
      if (mappedName) {
        userName = mappedName
      } else {
        // Extract name from email
        const namePart = log.email_attempted.split('@')[0]
        userName = namePart.split('.').map(p => p.charAt(0).toUpperCase() + p.slice(1)).join(' ')
      }
    } else if (log.actor) {
      userName = log.actor
    }

    // Format action text and determine type
    let type = 'system'
    let typeLabel = 'System'
    let icon = '⚙️'
    let action = ''
    let target = ''
    let details = null

    if (log._type === 'case' || log.case_code) {
      type = 'case'
      typeLabel = 'Case'
      icon = '📁'
      
      // Parse case action
      if (log.action?.includes('status')) {
        action = 'updated case status'
      } else if (log.action?.includes('stage')) {
        action = 'changed stage'
      } else if (log.action?.includes('create')) {
        action = 'created case'
      } else if (log.action?.includes('update')) {
        action = 'updated case'
      } else {
        action = log.action || 'performed action'
      }
      
      target = log.case_code || ''
      
      // Parse details for before/after
      if (log.details) {
        if (typeof log.details === 'string') {
          try {
            details = JSON.parse(log.details)
          } catch {
            details = { note: log.details }
          }
        } else {
          details = log.details
        }
      }
    } else {
      // System actions
      const actionMap = {
        login: { action: 'logged in', icon: '→' },
        logout: { action: 'logged out', icon: '←' },
        password_change: { action: 'changed password', icon: '🔑' },
        user_create: { action: 'created new user', icon: '➕' },
        user_update: { action: 'updated user', icon: '✏️' },
        user_delete: { action: 'deleted user', icon: '🗑️' },
        activated: { action: 'activated account', icon: '✅' },
        deactivated: { action: 'deactivated account', icon: '⛔' }
      }
      
      const mapped = actionMap[log.action] || { action: log.action || 'performed action', icon: '•' }
      action = mapped.action
      icon = mapped.icon
      
      if (log.details && typeof log.details === 'string') {
        details = { note: log.details }
      }
    }

    return {
      id: log.id,
      userName,
      userEmail,
      action,
      target,
      details,
      time: formatRelativeTime(log.created_at),
      type,
      typeLabel,
      icon,
      status: log.status
    }
  })
})

// Folder Movements from Approvals
const folderMovements = computed(() => {
  return approvals.value
    .filter(m => m._source === 'folder' || m._source === 'checklist')
    .slice(0, 5)
    .map(m => ({
      id: m.id,
      case_code: m.case_code,
      case_no: m.case_id,
      type: m.type,
      purpose: m.purpose,
      task: m.checklist?.task,
      handled_by: m.handled_by || m.recorder?.full_name,
      recorder: m.recorder,
      date: m.date || m.created_at,
      approval_status: m.approval_status
    }))
})

// Schedules (mock data - replace with actual schedules)
const schedules = ref([
  { 
    id: 1, 
    time: '09:00', 
    title: 'Client Meeting - Smith vs. Jones', 
    type: 'meeting',
    case_no: '2026-0001',
    participant: 'John Smith'
  },
  { 
    id: 2, 
    time: '10:30', 
    title: 'Court Hearing - Regional Trial Court', 
    type: 'hearing',
    case_no: '2026-0002',
    participant: 'Judge Reyes'
  },
  { 
    id: 3, 
    time: '14:00', 
    title: 'Document Review Deadline', 
    type: 'deadline',
    case_no: '2026-0003',
    participant: 'Maria Santos'
  }
])

// UI State
const searchQuery = ref('')

// Polling for real-time updates
let pollTimer = null
let bc = null

const openChannel = () => {
  closeChannel()
  bc = new BroadcastChannel('dashboard_sync')
  bc.onmessage = () => {
    refreshData()
  }
}

const closeChannel = () => {
  if (bc) bc.close()
  bc = null
}

const startPolling = () => {
  stopPolling()
  pollTimer = setInterval(() => {
    if (document.visibilityState === 'visible') {
      refreshData()
    }
  }, 30000)
}

const stopPolling = () => {
  if (pollTimer) clearInterval(pollTimer)
  pollTimer = null
}

const refreshData = () => {
  store.actions.refreshApprovals({ limit: 10 })
  store.actions.refreshLogs({ limit: 20 })
  store.actions.refreshCases({ limit: 10 })
}

// Computed
const currentDate = computed(() => {
  return new Date().toLocaleDateString('en-US', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
})

const currentYear = computed(() => new Date().getFullYear())

const filteredUsers = computed(() => {
  const u = users.value
  if (!searchQuery.value) return u.slice(0, 10)
  const query = searchQuery.value.toLowerCase()
  return u.filter(user => 
    (user.full_name || user.name || '').toLowerCase().includes(query) ||
    (user.email || '').toLowerCase().includes(query) ||
    (user.role?.name || user.role || '').toLowerCase().includes(query)
  ).slice(0, 10)
})

// Helper Methods
const getUserInitials = (name) => {
  if (!name) return '??'
  return name
    .split(' ')
    .filter(Boolean)
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const getInitials = (name) => {
  return getUserInitials(name)
}

const formatRelativeTime = (date) => {
  if (!date) return '—'
  const now = new Date()
  const past = new Date(date)
  const diffMs = now - past
  const diffMins = Math.floor(diffMs / 60000)
  
  if (diffMins < 1) return 'just now'
  if (diffMins < 60) return `${diffMins}m ago`
  const diffHours = Math.floor(diffMins / 60)
  if (diffHours < 24) return `${diffHours}h ago`
  const diffDays = Math.floor(diffHours / 24)
  if (diffDays < 7) return `${diffDays}d ago`
  return formatDate(date)
}

const formatDate = (date) => {
  if (!date) return '—'
  const d = new Date(date)
  if (isNaN(d.getTime())) return date
  return d.toLocaleDateString('en-US', { 
    month: '2-digit', 
    day: '2-digit',
    year: 'numeric'
  })
}

const formatMovementDate = (date) => {
  if (!date) return '—'
  const d = new Date(date)
  if (isNaN(d.getTime())) return date
  return d.toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatLastLogin = (date) => {
  if (!date) return 'Never'
  const d = new Date(date)
  if (isNaN(d.getTime())) return date
  return d.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: '2-digit', 
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatValue = (val) => {
  if (val === null || val === undefined || val === '') return '(empty)'
  return String(val)
}

// Navigation handlers
const viewAllActivities = () => router.push('/logs')
const viewAllSchedules = () => router.push('/schedules')
const viewAllCases = () => router.push('/cases')
const viewAllMovements = () => router.push('/approvals')

// Lifecycle
onMounted(() => {
  if (!isAdmin.value && !isLawyer.value) {
    router.push('/dashboard')
    return
  }

  // initialize() already fetches initial logs, approvals, and cases.
  // We only need to trigger it if it hasn't run yet.
  if (!store.state.isInitialized) {
    store.actions.initialize(userRole.value);
  }
  
  openChannel()
  startPolling()
})

onUnmounted(() => {
  stopPolling()
  closeChannel()
})
</script>

<style scoped>
.dashboard-container {
  min-height: 100vh;
  padding: 24px;
  background: #f8fafc;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* Top Bar */
.top-bar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
}

.welcome-section {
  flex: 1;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1a4972;
  margin: 0 0 4px 0;
}

.welcome-text {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

.top-bar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.date-badge {
  padding: 6px 14px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  font-size: 13px;
  color: #475569;
}

.live-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  color: #10b981;
}

.live-dot {
  width: 8px;
  height: 8px;
  background: #10b981;
  border-radius: 50%;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(1.2); }
  100% { opacity: 1; transform: scale(1); }
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.stat-icon {
  font-size: 24px;
  margin-bottom: 12px;
}

.stat-content {
  margin-bottom: 8px;
}

.stat-label {
  display: block;
  font-size: 12px;
  color: #64748b;
  margin-bottom: 4px;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
}

.stat-trend {
  position: absolute;
  top: 20px;
  right: 20px;
  font-size: 12px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 12px;
}

.trend-up {
  color: #10b981;
  background: rgba(16, 185, 129, 0.1);
}

.trend-down {
  color: #ef4444;
  background: rgba(239, 68, 68, 0.1);
}

.stat-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  transition: width 0.3s ease;
}

/* Main Grid */
.main-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  margin-bottom: 24px;
}

/* Panels */
.panel {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #f1f5f9;
}

.panel-title {
  font-size: 15px;
  font-weight: 600;
  color: #1a4972;
  margin: 0;
}

.panel-link {
  background: none;
  border: 1px solid #e2e8f0;
  padding: 4px 12px;
  border-radius: 16px;
  font-size: 12px;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.panel-link:hover {
  background: #f8fafc;
  color: #1a4972;
}

/* Empty State */
.empty-state {
  padding: 32px 20px;
  text-align: center;
}

.empty-text {
  font-size: 13px;
  color: #94a3b8;
  margin: 0;
}

/* Activity List */
.activity-list {
  padding: 8px 0;
  max-height: 400px;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  gap: 12px;
  padding: 12px 20px;
  transition: background 0.2s;
  border-bottom: 1px solid #f1f5f9;
}

.activity-item:hover {
  background: #f8fafc;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}

.activity-icon.case {
  background: rgba(139, 92, 246, 0.1);
  color: #8b5cf6;
}

.activity-icon.system {
  background: rgba(100, 116, 139, 0.1);
  color: #64748b;
}

.activity-content {
  flex: 1;
  min-width: 0;
}

.activity-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.activity-type-badge {
  font-size: 10px;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 4px;
  text-transform: uppercase;
}

.activity-type-badge.case {
  background: rgba(139, 92, 246, 0.1);
  color: #8b5cf6;
}

.activity-type-badge.system {
  background: rgba(100, 116, 139, 0.1);
  color: #64748b;
}

.activity-time {
  font-size: 11px;
  color: #94a3b8;
}

.activity-text {
  font-size: 13px;
  color: #334155;
  margin: 0 0 6px 0;
  line-height: 1.4;
}

.activity-user {
  color: #1a4972;
  font-weight: 600;
}

.activity-target {
  color: #64748b;
  font-weight: 500;
  margin-left: 4px;
}

.activity-details {
  margin-top: 6px;
}

.change-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: #f1f5f9;
  border-radius: 16px;
  padding: 4px 8px;
  font-size: 11px;
}

.from-value {
  color: #ef4444;
  text-decoration: line-through;
  text-decoration-color: rgba(239, 68, 68, 0.3);
}

.arrow-icon {
  width: 12px;
  height: 12px;
  color: #94a3b8;
}

.to-value {
  color: #10b981;
  font-weight: 600;
}

.note {
  font-size: 11px;
  color: #64748b;
  background: #f1f5f9;
  padding: 4px 8px;
  border-radius: 12px;
  display: inline-block;
}

/* Schedule List */
.schedule-list {
  padding: 8px 0;
  max-height: 400px;
  overflow-y: auto;
}

.schedule-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.2s;
}

.schedule-item:hover {
  background: #f8fafc;
}

.schedule-item:last-child {
  border-bottom: none;
}

.schedule-time {
  min-width: 70px;
}

.time-badge {
  display: inline-block;
  padding: 4px 8px;
  background: #f1f5f9;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #1a4972;
}

.schedule-content {
  flex: 1;
  min-width: 0;
}

.schedule-title {
  font-size: 13px;
  font-weight: 500;
  color: #334155;
  margin: 0 0 4px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.schedule-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
}

.schedule-type {
  padding: 2px 6px;
  border-radius: 4px;
  font-weight: 600;
  text-transform: uppercase;
}

.type-meeting { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.type-hearing { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
.type-deadline { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

.schedule-case {
  color: #64748b;
}

.schedule-participant {
  flex-shrink: 0;
}

.participant-avatar {
  width: 28px;
  height: 28px;
  background: #1a4972;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
}

/* Cases Overview */
.cases-overview {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  padding: 20px;
}

.case-stat-item {
  text-align: center;
}

.case-stat-label {
  display: block;
  font-size: 12px;
  color: #64748b;
  margin-bottom: 4px;
}

.case-stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
}

.case-progress {
  padding: 0 20px 20px 20px;
}

.progress-label {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #64748b;
  margin-bottom: 6px;
}

.progress-bar {
  height: 6px;
  background: #f1f5f9;
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #1a4972;
  border-radius: 3px;
  transition: width 0.3s ease;
}

/* Movement List */
.movement-list {
  padding: 8px 0;
  max-height: 400px;
  overflow-y: auto;
}

.movement-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.2s;
}

.movement-item:hover {
  background: #f8fafc;
}

.movement-item:last-child {
  border-bottom: none;
}

.movement-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  flex-shrink: 0;
}

.movement-icon.out {
  background: rgba(249, 115, 22, 0.1);
  color: #f97316;
}

.movement-icon.in {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.movement-content {
  flex: 1;
  min-width: 0;
}

.movement-text {
  font-size: 13px;
  color: #334155;
  margin: 0 0 4px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.movement-purpose {
  color: #64748b;
  margin-left: 4px;
  font-weight: normal;
}

.movement-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #94a3b8;
  flex-wrap: wrap;
}

.handler {
  font-weight: 500;
  color: #475569;
}

.dot {
  color: #cbd5e1;
}

.approval-badge {
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
}

.approval-pending {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.approval-approved {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.approval-rejected {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

/* Users Panel */
.users-panel {
  margin-top: 24px;
}

.table-controls {
  display: flex;
  align-items: center;
  gap: 12px;
}

.search-box {
  position: relative;
}

.search-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

.search-input {
  padding: 8px 12px 8px 32px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 13px;
  width: 200px;
  outline: none;
  transition: border-color 0.2s;
}

.search-input:focus {
  border-color: #1a4972;
}

/* Table */
.table-responsive {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  text-align: left;
  padding: 12px 20px;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.table-row {
  border-bottom: 1px solid #f1f5f9;
}

.table-row:hover {
  background: #f8fafc;
}

.data-table td {
  padding: 12px 20px;
  font-size: 13px;
  color: #334155;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-avatar {
  width: 32px;
  height: 32px;
  background: #1a4972;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  flex-shrink: 0;
}

.user-name {
  font-weight: 600;
  margin-bottom: 2px;
  white-space: nowrap;
}

.user-email {
  font-size: 11px;
  color: #94a3b8;
  white-space: nowrap;
}

.role-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 12px;
  white-space: nowrap;
}

.role-admin { background: rgba(26, 73, 114, 0.1); color: #1a4972; }
.role-lawyer { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.role-clerk { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.role-intern { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
.role-secretary { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

.status-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 12px;
  white-space: nowrap;
}

.status-active { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.status-inactive { background: rgba(100, 116, 139, 0.1); color: #64748b; }

.text-muted {
  color: #94a3b8;
  font-size: 12px;
  white-space: nowrap;
}

.text-center {
  text-align: center;
}

.py-4 {
  padding-top: 16px;
  padding-bottom: 16px;
}

/* Footer */
.footer {
  margin-top: 32px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #94a3b8;
}

/* Scrollbar Styling */
.activity-list::-webkit-scrollbar,
.movement-list::-webkit-scrollbar,
.schedule-list::-webkit-scrollbar {
  width: 4px;
}

.activity-list::-webkit-scrollbar-track,
.movement-list::-webkit-scrollbar-track,
.schedule-list::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.activity-list::-webkit-scrollbar-thumb,
.movement-list::-webkit-scrollbar-thumb,
.schedule-list::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 2px;
}

/* Responsive */
@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .main-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .dashboard-container {
    padding: 16px;
  }
  
  .top-bar {
    flex-direction: column;
    gap: 12px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .table-controls {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-box {
    width: 100%;
  }
  
  .search-input {
    width: 100%;
  }
}
</style>