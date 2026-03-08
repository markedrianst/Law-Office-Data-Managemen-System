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

    <!-- Stats Cards -->
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
      <!-- Recent Activity Panel -->
      <div class="panel">
        <div class="panel-header">
          <h3 class="panel-title">Recent Activity</h3>
          <button class="panel-link">View all</button>
        </div>
        <div class="activity-list">
          <div v-for="activity in recentActivities" :key="activity.id" class="activity-item">
            <div class="activity-dot" :class="`dot-${activity.type}`"></div>
            <div class="activity-content">
              <p class="activity-text">{{ activity.text }}</p>
              <span class="activity-time">{{ activity.time }}</span>
            </div>
            <span class="activity-tag" :class="`tag-${activity.type}`">{{ activity.tag }}</span>
          </div>
        </div>
      </div>

      <!-- Quick Actions Panel -->
      <div class="panel">
        <div class="panel-header">
          <h3 class="panel-title">Quick Actions</h3>
        </div>
        <div class="quick-actions-grid">
          <button v-for="action in quickActions" :key="action.label" class="quick-action-btn" @click="handleQuickAction(action.action)">
            <div class="action-icon">{{ action.icon }}</div>
            <span class="action-label">{{ action.label }}</span>
          </button>
        </div>
      </div>

      <!-- Cases Overview Panel -->
      <div class="panel">
        <div class="panel-header">
          <h3 class="panel-title">Cases Overview</h3>
          <button class="panel-link">View all</button>
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
          <button class="panel-link">View all</button>
        </div>
        <div class="movement-list">
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
                <strong>{{ movement.case_no }}</strong> - {{ movement.purpose }}
              </p>
              <span class="movement-meta">{{ movement.handled_by }} • {{ formatDate(movement.date) }}</span>
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
          <button class="btn-primary" @click="handleAddUser">+ Add User</button>
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
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in filteredUsers" :key="user.id" class="table-row">
              <td>
                <div class="user-cell">
                  <div class="user-avatar">{{ getUserInitials(user.name) }}</div>
                  <div>
                    <div class="user-name">{{ user.name }}</div>
                    <div class="user-email">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td>
                <span class="role-badge" :class="`role-${user.role.toLowerCase()}`">{{ user.role }}</span>
              </td>
              <td>
                <span class="status-badge" :class="`status-${user.status}`">{{ user.status }}</span>
              </td>
              <td class="text-muted">{{ user.last_login || 'Never' }}</td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn edit-btn" @click="handleEditUser(user)">Edit</button>
                  <button class="action-btn delete-btn" @click="handleDeleteUser(user)">Remove</button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredUsers.length === 0">
              <td colspan="5" class="text-center text-muted py-4">No users found</td>
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
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// State
const searchQuery = ref('')
const users = ref([])
const stats = ref([
  { label: 'Total Users', value: 0, trend: 12, progress: 100, color: '#1a4972', icon: '👥' },
  { label: 'Active Cases', value: 0, trend: 8, progress: 72, color: '#3b82f6', icon: '💼' },
  { label: 'Documents', value: 0, trend: 23, progress: 88, color: '#10b981', icon: '📄' },
  { label: 'Logins Today', value: 0, trend: -3, progress: 40, color: '#f97316', icon: '🔓' }
])

const caseStats = ref({
  active: 0,
  pending: 0,
  closed: 0,
  archived: 0,
  completionRate: 0
})

const recentActivities = ref([
  { id: 1, text: 'New user account created', time: '2 min ago', tag: 'User', type: 'user' },
  { id: 2, text: 'Case #2241 document uploaded', time: '18 min ago', tag: 'Document', type: 'document' },
  { id: 3, text: 'Password changed — j.dela@nplo', time: '45 min ago', tag: 'Security', type: 'security' },
  { id: 4, text: 'Case #2238 status updated', time: '1 hr ago', tag: 'Case', type: 'case' },
  { id: 5, text: 'Failed login attempt detected', time: '2 hr ago', tag: 'Alert', type: 'alert' },
  { id: 6, text: 'New case #2242 opened', time: '3 hr ago', tag: 'Case', type: 'case' }
])

const folderMovements = ref([
  { id: 1, case_no: 'CASE-2024-001', type: 'OUT', purpose: 'For Court Filing', handled_by: 'Maria Santos', date: '2024-03-06' },
  { id: 2, case_no: 'CASE-2024-002', type: 'IN', purpose: 'Received from Court', handled_by: 'Juan dela Cruz', date: '2024-03-06' },
  { id: 3, case_no: 'CASE-2024-003', type: 'OUT', purpose: 'For Client Review', handled_by: 'Paolo Mendoza', date: '2024-03-05' }
])

const quickActions = [
  { label: 'Add User', icon: '➕', action: 'add-user' },
  { label: 'Export Logs', icon: '📤', action: 'export-logs' },
  { label: 'View Logs', icon: '📋', action: 'view-logs' },
  { label: 'Settings', icon: '⚙️', action: 'settings' }
]

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
  if (!searchQuery.value) return users.value
  const query = searchQuery.value.toLowerCase()
  return users.value.filter(user => 
    user.name.toLowerCase().includes(query) ||
    user.email.toLowerCase().includes(query) ||
    user.role.toLowerCase().includes(query)
  )
})

// Methods
const getUserInitials = (name) => {
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric' 
  })
}

const handleQuickAction = (action) => {
  console.log('Quick action:', action)
  // Implement based on your needs
}

const handleAddUser = () => {
  console.log('Add user')
  // Navigate to add user page or open modal
}

const handleEditUser = (user) => {
  console.log('Edit user:', user)
  // Open edit modal
}

const handleDeleteUser = (user) => {
  console.log('Delete user:', user)
  // Show confirmation modal
}

// Data fetching
const fetchDashboardData = async () => {
  try {
    // Fetch users
    const usersResponse = await axios.get('/api/users')
    users.value = usersResponse.data

    // Fetch stats
    const statsResponse = await axios.get('/api/dashboard/stats')
    if (statsResponse.data) {
      stats.value[0].value = statsResponse.data.total_users || 24
      stats.value[1].value = statsResponse.data.active_cases || 138
      stats.value[2].value = statsResponse.data.documents || 512
      stats.value[3].value = statsResponse.data.logins_today || 9
    }

    // Fetch case stats
    const caseStatsResponse = await axios.get('/api/cases/stats')
    if (caseStatsResponse.data) {
      caseStats.value = caseStatsResponse.data
    }

    // Fetch recent activities
    const activitiesResponse = await axios.get('/api/activities/recent')
    if (activitiesResponse.data) {
      recentActivities.value = activitiesResponse.data
    }

    // Fetch folder movements
    const movementsResponse = await axios.get('/api/folder-movements/recent')
    if (movementsResponse.data) {
      folderMovements.value = movementsResponse.data
    }

  } catch (error) {
    console.error('Failed to fetch dashboard data:', error)
    // Use mock data if API fails
    loadMockData()
  }
}

const loadMockData = () => {
  // Mock users data
  users.value = [
    { id: 1, name: 'Juan dela Cruz', email: 'j.delacruz@nplo.ph', role: 'Lawyer', status: 'active', last_login: 'Today, 8:42 AM' },
    { id: 2, name: 'Maria Santos', email: 'm.santos@nplo.ph', role: 'Secretary', status: 'active', last_login: 'Today, 9:01 AM' },
    { id: 3, name: 'Roberto Reyes', email: 'r.reyes@nplo.ph', role: 'Admin', status: 'active', last_login: 'Yesterday' },
    { id: 4, name: 'Ana Gonzalez', email: 'a.gonzalez@nplo.ph', role: 'Lawyer', status: 'inactive', last_login: '3 days ago' },
    { id: 5, name: 'Paolo Mendoza', email: 'p.mendoza@nplo.ph', role: 'Intern', status: 'active', last_login: 'Today, 10:15 AM' },
    { id: 6, name: 'Liza Tan', email: 'l.tan@nplo.ph', role: 'Secretary', status: 'inactive', last_login: '1 week ago' }
  ]

  // Mock case stats
  caseStats.value = {
    active: 98,
    pending: 23,
    closed: 45,
    archived: 12,
    completionRate: 68
  }
}

onMounted(() => {
  fetchDashboardData()
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
  grid-template-columns: 2fr 1fr;
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

/* Activity List */
.activity-list {
  padding: 8px 0;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  transition: background 0.2s;
}

.activity-item:hover {
  background: #f8fafc;
}

.activity-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.dot-user { background: #3b82f6; }
.dot-document { background: #10b981; }
.dot-security { background: #eab308; }
.dot-case { background: #8b5cf6; }
.dot-alert { background: #ef4444; }

.activity-content {
  flex: 1;
}

.activity-text {
  font-size: 13px;
  color: #334155;
  margin: 0 0 2px 0;
}

.activity-time {
  font-size: 11px;
  color: #94a3b8;
}

.activity-tag {
  font-size: 10px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 12px;
}

.tag-user { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.tag-document { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.tag-security { background: rgba(234, 179, 8, 0.1); color: #eab308; }
.tag-case { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
.tag-alert { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

/* Quick Actions */
.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1px;
  background: #f1f5f9;
}

.quick-action-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 20px;
  background: white;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.quick-action-btn:hover {
  background: #f8fafc;
}

.quick-action-btn:hover .action-icon {
  background: #1a4972;
  color: white;
}

.action-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 18px;
  color: #1a4972;
  transition: all 0.2s;
}

.action-label {
  font-size: 12px;
  font-weight: 500;
  color: #475569;
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
}

.movement-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  border-bottom: 1px solid #f1f5f9;
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
}

.movement-text {
  font-size: 13px;
  color: #334155;
  margin: 0 0 2px 0;
}

.movement-meta {
  font-size: 11px;
  color: #94a3b8;
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

.btn-primary {
  padding: 8px 16px;
  background: #1a4972;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #0f3a5a;
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
}

.user-name {
  font-weight: 600;
  margin-bottom: 2px;
}

.user-email {
  font-size: 11px;
  color: #94a3b8;
}

.role-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 12px;
}

.role-admin { background: rgba(26, 73, 114, 0.1); color: #1a4972; }
.role-lawyer { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.role-secretary { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.role-intern { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

.status-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 12px;
}

.status-active { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.status-inactive { background: rgba(100, 116, 139, 0.1); color: #64748b; }

.text-muted {
  color: #94a3b8;
}

.action-buttons {
  display: flex;
  gap: 6px;
}

.action-btn {
  padding: 4px 10px;
  font-size: 11px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
}

.edit-btn:hover {
  background: #1a4972;
  color: white;
  border-color: #1a4972;
}

.delete-btn:hover {
  background: #ef4444;
  color: white;
  border-color: #ef4444;
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