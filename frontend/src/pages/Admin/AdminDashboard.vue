<template>
  <div class="min-h-screen p-6 bg-slate-50 font-sans">
    <!-- Top Bar -->
    <div class="flex justify-between items-start mb-6">
      <div class="flex-1">
        <h1 class="text-3xl font-bold text-[#1a4972] mb-1">Dashboard</h1>
        <p class="text-sm text-slate-500">Welcome back, {{ userName }} — here's what's happening today.</p>
      </div>
      <div class="flex items-center gap-3">
        <span class="px-3.5 py-1.5 bg-white border border-slate-200 rounded-full text-xs text-slate-600">{{ currentDate }}</span>
        <div class="flex items-center gap-1.5 px-3.5 py-1.5 bg-white border border-slate-200 rounded-full text-xs font-semibold text-emerald-500">
          <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
          Live
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div v-for="stat in stats" :key="stat.label" 
           class="bg-white rounded-xl p-5 relative overflow-hidden shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
        <div class="text-2xl mb-3">{{ stat.icon }}</div>
        <div class="mb-2">
          <span class="block text-xs text-slate-500 mb-1">{{ stat.label }}</span>
          <span class="text-3xl font-bold text-slate-800">{{ stat.value }}</span>
        </div>
        <span class="absolute top-5 right-5 text-xs font-semibold px-2 py-0.5 rounded-xl"
              :class="stat.trend >= 0 ? 'text-emerald-500 bg-emerald-50' : 'text-red-500 bg-red-50'">
          {{ stat.trend > 0 ? '+' : '' }}{{ stat.trend }}%
        </span>
        <div class="absolute bottom-0 left-0 h-0.5 transition-all duration-300" 
             :style="{ width: stat.progress + '%', backgroundColor: stat.color }"></div>
      </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
      <!-- Recent Activity Panel -->
      <div class="bg-white rounded-xl overflow-hidden shadow-sm">
        <div class="flex justify-between items-center px-5 py-4 border-b border-slate-100">
          <h3 class="text-sm font-semibold text-[#1a4972] m-0">Recent Activity</h3>
          <button @click="viewAllActivities" 
                  class="bg-transparent border border-slate-200 px-3 py-1 rounded-2xl text-xs text-slate-500 cursor-pointer transition-all hover:bg-slate-50 hover:text-[#1a4972]">
            View all
          </button>
        </div>
        <div class="py-2 max-h-96 overflow-y-auto">
          <div v-if="recentActivities.length === 0" class="py-8 px-5 text-center">
            <p class="text-xs text-slate-400 m-0">No recent activities</p>
          </div>
          <div v-for="activity in recentActivities" :key="activity.id" 
               class="flex gap-3 px-5 py-3 transition-colors border-b border-slate-100 last:border-b-0 hover:bg-slate-50">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm flex-shrink-0"
                 :class="activity.type === 'case' ? 'bg-purple-50 text-purple-500' : 'bg-slate-100 text-slate-500'">
              {{ activity.icon }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded uppercase"
                      :class="activity.type === 'case' ? 'bg-purple-50 text-purple-500' : 'bg-slate-100 text-slate-500'">
                  {{ activity.typeLabel }}
                </span>
                <span class="text-[11px] text-slate-400">{{ activity.time }}</span>
              </div>
              <p class="text-xs text-slate-700 mb-1.5 leading-relaxed m-0">
                <strong class="text-[#1a4972] font-semibold">{{ activity.userName }}</strong>
                {{ activity.action }}
                <span v-if="activity.target" class="text-slate-500 font-medium ml-1">{{ activity.target }}</span>
              </p>
              <div v-if="activity.details" class="mt-1.5">
                <span v-if="activity.details.from !== undefined" 
                      class="inline-flex items-center gap-1 bg-slate-100 rounded-2xl px-2 py-1 text-[11px]">
                  <span class="text-red-500 line-through decoration-red-200">{{ formatValue(activity.details.from) }}</span>
                  <svg class="w-3 h-3 text-slate-400" viewBox="0 0 16 16" fill="none" stroke="currentColor">
                    <path d="M5 12L10 8 5 4" stroke-width="1.5"/>
                  </svg>
                  <span class="text-emerald-500 font-semibold">{{ formatValue(activity.details.to) }}</span>
                </span>
                <span v-else-if="activity.details.note" 
                      class="text-[11px] text-slate-500 bg-slate-100 px-2 py-1 rounded-xl inline-block">
                  {{ activity.details.note }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Schedules Panel -->
      <div class="bg-white rounded-xl overflow-hidden shadow-sm">
        <div class="flex justify-between items-center px-5 py-4 border-b border-slate-100">
          <h3 class="text-sm font-semibold text-[#1a4972] m-0">Today's Schedules</h3>
          <button @click="viewAllSchedules" 
                  class="bg-transparent border border-slate-200 px-3 py-1 rounded-2xl text-xs text-slate-500 cursor-pointer transition-all hover:bg-slate-50 hover:text-[#1a4972]">
            View all
          </button>
        </div>
        <div class="py-2 max-h-96 overflow-y-auto">
          <div v-if="schedules.length === 0" class="py-8 px-5 text-center">
            <p class="text-xs text-slate-400 m-0">No schedules for today</p>
          </div>
          <div v-for="schedule in schedules" :key="schedule.id" 
               class="flex items-center gap-4 px-5 py-4 border-b border-slate-100 last:border-b-0 transition-colors hover:bg-slate-50">
            <div class="min-w-[70px]">
              <span class="inline-block px-2 py-1 bg-slate-100 rounded-md text-xs font-semibold text-[#1a4972]">{{ schedule.time }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium text-slate-700 mb-1 m-0 whitespace-nowrap overflow-hidden text-ellipsis">{{ schedule.title }}</p>
              <p class="flex items-center gap-2 text-[11px] m-0">
                <span class="px-1.5 py-0.5 rounded font-semibold uppercase"
                      :class="{
                        'bg-blue-50 text-blue-500': schedule.type === 'meeting',
                        'bg-purple-50 text-purple-500': schedule.type === 'hearing',
                        'bg-red-50 text-red-500': schedule.type === 'deadline'
                      }">
                  {{ schedule.type }}
                </span>
                <span class="text-slate-500">{{ schedule.case_no }}</span>
              </p>
            </div>
            <div class="flex-shrink-0">
              <div class="w-7 h-7 bg-[#1a4972] text-white flex items-center justify-center rounded-md text-[11px] font-semibold">
                {{ getInitials(schedule.participant) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cases Overview Panel -->
      <div class="bg-white rounded-xl overflow-hidden shadow-sm">
        <div class="flex justify-between items-center px-5 py-4 border-b border-slate-100">
          <h3 class="text-sm font-semibold text-[#1a4972] m-0">Cases Overview</h3>
          <button @click="viewAllCases" 
                  class="bg-transparent border border-slate-200 px-3 py-1 rounded-2xl text-xs text-slate-500 cursor-pointer transition-all hover:bg-slate-50 hover:text-[#1a4972]">
            View all
          </button>
        </div>
        <div class="grid grid-cols-2 gap-4 p-5">
          <div class="text-center">
            <span class="block text-xs text-slate-500 mb-1">Active Cases</span>
            <span class="text-2xl font-bold text-slate-800">{{ caseStats.active }}</span>
          </div>
          <div class="text-center">
            <span class="block text-xs text-slate-500 mb-1">Pending</span>
            <span class="text-2xl font-bold text-slate-800">{{ caseStats.pending }}</span>
          </div>
          <div class="text-center">
            <span class="block text-xs text-slate-500 mb-1">Closed</span>
            <span class="text-2xl font-bold text-slate-800">{{ caseStats.closed }}</span>
          </div>
          <div class="text-center">
            <span class="block text-xs text-slate-500 mb-1">Archived</span>
            <span class="text-2xl font-bold text-slate-800">{{ caseStats.archived }}</span>
          </div>
        </div>
        <div class="px-5 pb-5">
          <div class="flex justify-between text-xs text-slate-500 mb-1.5">
            <span>Completion Rate</span>
            <span>{{ caseStats.completionRate }}%</span>
          </div>
          <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full bg-[#1a4972] rounded-full transition-all duration-300" 
                 :style="{ width: caseStats.completionRate + '%' }"></div>
          </div>
        </div>
      </div>

      <!-- Folder Movements Panel -->
      <div class="bg-white rounded-xl overflow-hidden shadow-sm">
        <div class="flex justify-between items-center px-5 py-4 border-b border-slate-100">
          <h3 class="text-sm font-semibold text-[#1a4972] m-0">Folder Movements</h3>
          <button @click="viewAllMovements" 
                  class="bg-transparent border border-slate-200 px-3 py-1 rounded-2xl text-xs text-slate-500 cursor-pointer transition-all hover:bg-slate-50 hover:text-[#1a4972]">
            View all
          </button>
        </div>
        <div class="py-2 max-h-96 overflow-y-auto">
          <div v-if="folderMovements.length === 0" class="py-8 px-5 text-center">
            <p class="text-xs text-slate-400 m-0">No recent folder movements</p>
          </div>
          <div v-for="movement in folderMovements" :key="movement.id" 
               class="flex items-center gap-3 px-5 py-3 border-b border-slate-100 last:border-b-0 transition-colors hover:bg-slate-50">
            <div class="w-8 h-8 flex items-center justify-center rounded-lg flex-shrink-0"
                 :class="movement.type === 'OUT' ? 'bg-orange-50 text-orange-500' : 'bg-emerald-50 text-emerald-500'">
              <svg v-if="movement.type === 'OUT'" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
              </svg>
              <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs text-slate-700 mb-1 m-0 whitespace-nowrap overflow-hidden text-ellipsis">
                <strong>{{ movement.case_code || movement.case_no }}</strong> 
                <span class="text-slate-500 ml-1 font-normal">{{ movement.purpose || movement.task || 'Folder movement' }}</span>
              </p>
              <span class="flex items-center gap-1.5 text-[11px] text-slate-400 flex-wrap">
                <span class="font-medium text-slate-600">{{ movement.handled_by || movement.recorder?.full_name }}</span>
                <span class="text-slate-300">•</span>
                <span>{{ formatMovementDate(movement.date) }}</span>
                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase"
                      :class="{
                        'bg-amber-50 text-amber-500': (movement.approval_status || 'pending').toLowerCase() === 'pending',
                        'bg-emerald-50 text-emerald-500': (movement.approval_status || '').toLowerCase() === 'approved',
                        'bg-red-50 text-red-500': (movement.approval_status || '').toLowerCase() === 'rejected'
                      }">
                  {{ movement.approval_status || 'PENDING' }}
                </span>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl overflow-hidden shadow-sm mt-6">
      <div class="flex justify-between items-center px-5 py-4 border-b border-slate-100">
        <h3 class="text-sm font-semibold text-[#1a4972] m-0">System Users</h3>
        <div class="flex items-center gap-3">
          <div class="relative">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="11" cy="11" r="8"/>
              <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Search users..." 
              class="pl-8 pr-3 py-2 border border-slate-200 rounded-lg text-xs w-48 outline-none transition-colors focus:border-[#1a4972]"
            />
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full border-collapse">
          <thead>
            <tr>
              <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 bg-slate-50 border-b border-slate-200">Name</th>
              <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 bg-slate-50 border-b border-slate-200">Role</th>
              <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 bg-slate-50 border-b border-slate-200">Status</th>
              <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 bg-slate-50 border-b border-slate-200">Last Login</th>
            </tr>
          </thead>
          <tbody v-if="filteredUsers && filteredUsers.length > 0">
            <tr v-for="user in filteredUsers" :key="user.id" class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
              <td class="px-5 py-3">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 bg-[#1a4972] text-white flex items-center justify-center rounded-lg text-xs font-semibold flex-shrink-0">
                    {{ getUserInitials(user?.full_name || user?.name) }}
                  </div>
                  <div>
                    <div class="font-semibold mb-0.5 whitespace-nowrap text-xs">{{ user?.full_name || user?.name || '—' }}</div>
                    <div class="text-[11px] text-slate-400 whitespace-nowrap">{{ user?.email || '—' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-5 py-3 text-xs">
                <span class="text-[11px] font-semibold px-2 py-1 rounded-xl whitespace-nowrap"
                      :class="{
                        'bg-[#1a497214] text-[#1a4972]': (user?.role?.name || user?.role || '').toLowerCase() === 'admin',
                        'bg-blue-50 text-blue-500': (user?.role?.name || user?.role || '').toLowerCase() === 'lawyer',
                        'bg-emerald-50 text-emerald-500': (user?.role?.name || user?.role || '').toLowerCase() === 'clerk',
                        'bg-purple-50 text-purple-500': (user?.role?.name || user?.role || '').toLowerCase() === 'intern',
                        'bg-amber-50 text-amber-500': (user?.role?.name || user?.role || '').toLowerCase() === 'secretary'
                      }">
                  {{ user?.role?.name || user?.role || '—' }}
                </span>
              </td>
              <td class="px-5 py-3 text-xs">
                <span class="text-[11px] font-semibold px-2 py-1 rounded-xl whitespace-nowrap"
                      :class="user?.is_active ? 'bg-emerald-50 text-emerald-500' : 'bg-slate-100 text-slate-500'">
                  {{ user?.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-5 py-3 text-slate-400 text-xs whitespace-nowrap">{{ formatLastLogin(user?.last_login) }}</td>
            </tr>
          </tbody>
          <tbody v-else>
            <tr>
              <td colspan="4" class="text-center text-slate-400 py-4 text-xs">No users found</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Footer -->
    <div class="mt-8 pt-4 border-t border-slate-200 flex justify-between text-xs text-slate-400">
      <p>© {{ currentYear }} Pineda Law Office. All rights reserved.</p>
      <p>Document Management System</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, onActivated, onDeactivated } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import UserService from '@/services/userServices'
import { getCases } from '@/services/caseService'
import { auditLogService } from '@/services/auditLogService'
import { getAllMovements } from '@/services/approvalService'

const router = useRouter()
const route = useRoute()
const { userName, isAdmin, isLawyer } = useAuth()

const CACHE_KEYS = {
  USERS: 'dashboard_users_cache',
  CASES: 'dashboard_cases_cache',
  LOGS: 'dashboard_logs_cache',
  APPROVALS: 'dashboard_approvals_cache'
}
const CACHE_DURATION = 30000; // 30 seconds

// ==================== STATE ====================
const users = ref([])
const cases = ref([])
const activityLogs = ref([])
const approvals = ref([])
const isRefreshing = ref(false)
const lastRefreshTime = ref(Date.now())
const isActive = ref(true) // Track if dashboard is currently active

// Mock schedules
const schedules = ref([
  { id: 1, time: '09:00', title: 'Client Meeting - Smith vs. Jones', type: 'meeting', case_no: '2026-0001', participant: 'John Smith' },
  { id: 2, time: '10:30', title: 'Court Hearing - Regional Trial Court', type: 'hearing', case_no: '2026-0002', participant: 'Judge Reyes' },
  { id: 3, time: '14:00', title: 'Document Review Deadline', type: 'deadline', case_no: '2026-0003', participant: 'Maria Santos' }
])

const searchQuery = ref('')

// Polling timer
let pollTimer = null

// ==================== CACHE FUNCTIONS ====================
const loadFromCache = () => {
  try {
    const cachedUsers = sessionStorage.getItem(CACHE_KEYS.USERS)
    if (cachedUsers) {
      const { data, timestamp } = JSON.parse(cachedUsers)
      if (Date.now() - timestamp < CACHE_DURATION) {
        users.value = data || []
      }
    }

    const cachedCases = sessionStorage.getItem(CACHE_KEYS.CASES)
    if (cachedCases) {
      const { data, timestamp } = JSON.parse(cachedCases)
      if (Date.now() - timestamp < CACHE_DURATION) {
        cases.value = data || []
      }
    }

    const cachedLogs = sessionStorage.getItem(CACHE_KEYS.LOGS)
    if (cachedLogs) {
      const { data, timestamp } = JSON.parse(cachedLogs)
      if (Date.now() - timestamp < CACHE_DURATION) {
        activityLogs.value = data || []
      }
    }

    const cachedApprovals = sessionStorage.getItem(CACHE_KEYS.APPROVALS)
    if (cachedApprovals) {
      const { data, timestamp } = JSON.parse(cachedApprovals)
      if (Date.now() - timestamp < CACHE_DURATION) {
        approvals.value = data || []
      }
    }
  } catch (error) {
    console.error('Failed to load from cache:', error)
  }
}

const saveToCache = () => {
  try {
    sessionStorage.setItem(CACHE_KEYS.USERS, JSON.stringify({
      data: users.value,
      timestamp: Date.now()
    }))
    
    sessionStorage.setItem(CACHE_KEYS.CASES, JSON.stringify({
      data: cases.value,
      timestamp: Date.now()
    }))
    
    sessionStorage.setItem(CACHE_KEYS.LOGS, JSON.stringify({
      data: activityLogs.value,
      timestamp: Date.now()
    }))
    
    sessionStorage.setItem(CACHE_KEYS.APPROVALS, JSON.stringify({
      data: approvals.value,
      timestamp: Date.now()
    }))
  } catch (error) {
    console.error('Failed to save to cache:', error)
  }
}

// ==================== DATA FETCHING ====================
const fetchDashboardData = async (showRefreshing = false) => {
  // Don't fetch if dashboard is not active
  if (!isActive.value) {
    console.log('Dashboard not active, skipping refresh')
    return
  }
  
  if (showRefreshing) isRefreshing.value = true
  
  try {
    // Add timestamp to bust browser cache
    const timestamp = Date.now()
    
    const [usersResponse, casesResponse, logsResponse, approvalsResponse] = await Promise.all([
      UserService.getUsers({ per_page: 10, _t: timestamp }).catch(() => ({ data: [] })),
      getCases({ per_page: 10, _t: timestamp }).catch(() => ({ data: { data: [] } })),
      auditLogService.getLogs({ per_page: 20, _t: timestamp }).catch(() => ({ data: [] })),
      getAllMovements({ limit: 10, _t: timestamp }).catch(() => ({ data: [] }))
    ])
    
    // Check if data actually changed before updating
    const newUsers = usersResponse.data || []
    const newCases = casesResponse.data?.data || []
    const newLogs = logsResponse.data || []
    const newApprovals = approvalsResponse.data || []
    
    // Only update if data changed (prevents unnecessary re-renders)
    if (JSON.stringify(users.value) !== JSON.stringify(newUsers)) {
      users.value = newUsers
    }
    if (JSON.stringify(cases.value) !== JSON.stringify(newCases)) {
      cases.value = newCases
    }
    if (JSON.stringify(activityLogs.value) !== JSON.stringify(newLogs)) {
      activityLogs.value = newLogs
    }
    if (JSON.stringify(approvals.value) !== JSON.stringify(newApprovals)) {
      approvals.value = newApprovals
    }
    
    saveToCache()
    lastRefreshTime.value = Date.now()
    
  } catch (error) {
    console.error('Dashboard data fetch failed:', error)
  } finally {
    if (showRefreshing) {
      setTimeout(() => {
        isRefreshing.value = false
      }, 500)
    }
  }
}

// ==================== POLLING CONTROL ====================
const startPolling = () => {
  stopPolling()
  
  // Only start polling if dashboard is active
  if (!isActive.value) return
  
  console.log('Starting dashboard polling')
  
  // Poll every 10 seconds
  pollTimer = setInterval(() => {
    // Only refresh if page is visible and dashboard is active
    if (document.visibilityState === 'visible' && isActive.value) {
      console.log('Background refresh at:', new Date().toLocaleTimeString())
      fetchDashboardData(true)
    }
  }, 10000) // 10 seconds
}

const stopPolling = () => { 
  if (pollTimer) { 
    console.log('Stopping dashboard polling')
    clearInterval(pollTimer)
    pollTimer = null 
  } 
}

// ==================== ROUTE ACTIVATION ====================
// This runs when component is activated (when navigating back to dashboard)
onActivated(() => {
  console.log('Dashboard activated - resuming')
  isActive.value = true
  
  // Load from cache first (instant)
  loadFromCache()
  
  // Then fetch fresh data
  fetchDashboardData(true)
  
  // Restart polling
  startPolling()
})

// This runs when component is deactivated (when navigating away)
onDeactivated(() => {
  console.log('Dashboard deactivated - pausing')
  isActive.value = false
  stopPolling()
  isRefreshing.value = false // Hide any ongoing refresh indicator
})

// ==================== STATS COMPUTED ====================
const stats = computed(() => {
  const today = new Date().toISOString().split('T')[0]
  const yesterday = new Date(Date.now() - 86400000).toISOString().split('T')[0]
  
  const loginLogs = activityLogs.value.filter(l => l.action === 'login' && l.status === 'success')
  const todayLogins = loginLogs.filter(l => l.created_at?.startsWith(today)).length
  const yesterdayLogins = loginLogs.filter(l => l.created_at?.startsWith(yesterday)).length

  const totalDocuments = approvals.value.filter(a => 
    a._source === 'folder' || a._source === 'checklist'
  ).length

  const loginTrend = yesterdayLogins > 0 
    ? Math.round(((todayLogins - yesterdayLogins) / yesterdayLogins) * 100)
    : todayLogins > 0 ? 100 : 0

  return [
    { label: 'Total Users', value: users.value.length, trend: 12, progress: 100, color: '#1a4972', icon: '👥' },
    { label: 'Active Cases', value: cases.value.filter(c => c.case_status === 'active').length, trend: 8, progress: 72, color: '#3b82f6', icon: '💼' },
    { label: 'Documents', value: totalDocuments, trend: 23, progress: 88, color: '#10b981', icon: '📄' },
    { label: 'Logins Today', value: todayLogins, trend: loginTrend, progress: todayLogins > 0 ? Math.min(100, todayLogins * 10) : 0, color: '#f97316', icon: '🔓' }
  ]
})

const caseStats = computed(() => {
  const c = cases.value
  const active = c.filter(x => x.case_status === 'active').length
  const pending = c.filter(x => x.case_status === 'pending').length
  const closed = c.filter(x => x.case_status === 'closed').length
  const archived = c.filter(x => x.case_status === 'archived').length
  const total = c.length || 1
  
  return { active, pending, closed, archived, completionRate: Math.round((closed / total) * 100) }
})

const recentActivities = computed(() => {
  const userMap = new Map()
  users.value.forEach(user => {
    if (user.email) userMap.set(user.email.toLowerCase(), user.full_name || user.name || user.email.split('@')[0])
  })

  return (activityLogs.value || []).slice(0, 8).map(log => {
    let userName = 'System'
    
    if (log.user?.name) userName = log.user.name
    else if (log.email_attempted) {
      const mappedName = userMap.get(log.email_attempted.toLowerCase())
      userName = mappedName || log.email_attempted.split('@')[0].split('.').map(p => p.charAt(0).toUpperCase() + p.slice(1)).join(' ')
    } else if (log.actor) userName = log.actor

    let type = 'system', typeLabel = 'System', icon = '⚙️', action = '', target = '', details = null

    if (log._type === 'case' || log.case_code) {
      type = 'case'; typeLabel = 'Case'; icon = '📁'
      action = log.action?.includes('status') ? 'updated case status' :
               log.action?.includes('stage') ? 'changed stage' :
               log.action?.includes('create') ? 'created case' :
               log.action?.includes('update') ? 'updated case' : log.action || 'performed action'
      target = log.case_code || ''
      
      if (log.details) {
        if (typeof log.details === 'string') {
          try { details = JSON.parse(log.details) } catch { details = { note: log.details } }
        } else details = log.details
      }
    } else {
      const actionMap = {
        login: { action: 'logged in', icon: '→' }, logout: { action: 'logged out', icon: '←' },
        password_change: { action: 'changed password', icon: '🔑' }, user_create: { action: 'created new user', icon: '➕' },
        user_update: { action: 'updated user', icon: '✏️' }, user_delete: { action: 'deleted user', icon: '🗑️' },
        activated: { action: 'activated account', icon: '✅' }, deactivated: { action: 'deactivated account', icon: '⛔' }
      }
      const mapped = actionMap[log.action] || { action: log.action || 'performed action', icon: '•' }
      action = mapped.action; icon = mapped.icon
      if (log.details && typeof log.details === 'string') details = { note: log.details }
    }

    return {
      id: log.id, userName, action, target, details,
      time: formatRelativeTime(log.created_at), type, typeLabel, icon, status: log.status
    }
  })
})

const folderMovements = computed(() => {
  return (approvals.value || [])
    .filter(m => m._source === 'folder' || m._source === 'checklist')
    .slice(0, 5)
    .map(m => ({
      id: m.id, case_code: m.case_code, case_no: m.case_id, type: m.type,
      purpose: m.purpose, task: m.checklist?.task,
      handled_by: m.handled_by || m.recorder?.full_name, recorder: m.recorder,
      date: m.date || m.created_at, approval_status: m.approval_status
    }))
})

const filteredUsers = computed(() => {
  const u = users.value || []
  if (!searchQuery.value) return u.slice(0, 10)
  const query = searchQuery.value.toLowerCase()
  return u.filter(user => 
    (user?.full_name || user?.name || '').toLowerCase().includes(query) ||
    (user?.email || '').toLowerCase().includes(query) ||
    (user?.role?.name || user?.role || '').toLowerCase().includes(query)
  ).slice(0, 10)
})

// ==================== HELPER FUNCTIONS ====================
const getUserInitials = (name) => {
  if (!name) return '??'
  return name.split(' ').filter(Boolean).map(word => word[0]).join('').toUpperCase().slice(0, 2)
}

const getInitials = getUserInitials

const formatRelativeTime = (date) => {
  if (!date) return '—'
  const now = new Date(); const past = new Date(date); const diffMs = now - past; const diffMins = Math.floor(diffMs / 60000)
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
  return d.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' })
}

const formatMovementDate = (date) => {
  if (!date) return '—'
  const d = new Date(date)
  if (isNaN(d.getTime())) return date
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const formatLastLogin = (date) => {
  if (!date) return 'Never'
  const d = new Date(date)
  if (isNaN(d.getTime())) return date
  return d.toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
}

const formatValue = (val) => {
  if (val === null || val === undefined || val === '') return '(empty)'
  return String(val)
}

const currentDate = computed(() => {
  return new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
})

const currentYear = computed(() => new Date().getFullYear())

// ==================== NAVIGATION ====================
const viewAllActivities = () => router.push('/audittrail')
const viewAllSchedules = () => router.push('/schedules')
const viewAllCases = () => router.push('/casemaster')
const viewAllMovements = () => router.push('/approvals')

// ==================== LIFECYCLE ====================
onMounted(() => {
  if (!isAdmin.value && !isLawyer.value) {
    router.push('/dashboard')
    return
  }
  
  console.log('Dashboard mounted')
  isActive.value = true
  
  // Load from cache FIRST (instant display)
  loadFromCache()
  
  // Then fetch fresh data in background
  fetchDashboardData(true)
  
  // Start background polling
  startPolling()
})

onUnmounted(() => {
  console.log('Dashboard unmounted - cleaning up')
  isActive.value = false
  stopPolling()
})

// Watch for page focus
watch(() => document.visibilityState, () => {
  if (document.visibilityState === 'visible' && isActive.value) {
    // Only refresh if it's been more than 5 seconds since last refresh
    if (Date.now() - lastRefreshTime.value > 5000) {
      console.log('Page focused, refreshing dashboard')
      fetchDashboardData(true)
    }
  }
})
</script>

<style>
/* Only custom animation needed */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>