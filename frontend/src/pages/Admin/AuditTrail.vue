<template>
  <div class="min-h-screen bg-slate-100 p-6 font-sans">

    <!-- ── Top bar ── -->
    <div class="flex items-start justify-between mb-5">
      <div class="flex items-center gap-3">
        <div class="w-1 h-8 rounded-full bg-gradient-to-b from-[#1a4972] to-[#2d6db5]"></div>
        <h1 class="text-2xl font-bold tracking-tight text-[#1a4972]">Activity Logs</h1>
      </div>

      <div class="flex items-center gap-2">
        <div class="relative" v-click-outside="closeExportMenu">
          <button
            @click="toggleExportMenu"
            :disabled="isExporting || mergedLogs.length === 0"
            :class="[
              'inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#1a4972] text-white text-xs font-semibold transition-all',
              isExporting || mergedLogs.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-[#163d5e] cursor-pointer'
            ]"
          >
            <svg v-if="!isExporting" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            <span v-if="isExporting" class="w-3 h-3 rounded-full border-2 border-white/30 border-t-white animate-spin"></span>
            {{ isExporting ? 'Exporting…' : 'Export' }}
            <svg v-if="!isExporting" xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </button>

          <transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 -translate-y-1 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0 -translate-y-1 scale-95"
          >
            <div v-if="showExportMenu" class="absolute top-full right-0 mt-2 w-64 bg-white border border-slate-200 rounded-2xl shadow-xl p-2 z-50">
              <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-2 pt-1 pb-2">Export as</p>
              <button @click="exportExcel('current')" class="flex items-center gap-3 w-full px-2.5 py-2 rounded-xl hover:bg-slate-50 transition-colors text-left cursor-pointer border-none bg-transparent">
                <span class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>
                </span>
                <div>
                  <p class="text-xs font-semibold text-slate-800">Excel (.xlsx) — Current page</p>
                  <p class="text-[11px] text-slate-400 mt-0.5">{{ mergedLogs.length }} rows visible</p>
                </div>
              </button>
              <button @click="exportExcel('all')" class="flex items-center gap-3 w-full px-2.5 py-2 rounded-xl hover:bg-slate-50 transition-colors text-left cursor-pointer border-none bg-transparent">
                <span class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>
                </span>
                <div>
                  <p class="text-xs font-semibold text-slate-800">Excel (.xlsx) — All pages</p>
                  <p class="text-[11px] text-slate-400 mt-0.5">{{ pagination.total }} total rows</p>
                </div>
              </button>
            </div>
          </transition>
        </div>
      </div>
    </div>

    <!-- ── Filters ── -->
    <div class="bg-white border border-slate-200 rounded-2xl p-4 mb-4">
      <div class="relative mb-3">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4 pointer-events-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
          v-model="filters.search"
          @input="debouncedApply"
          type="text"
          placeholder="Search user, case code, action, IP…"
          class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-[13px] text-slate-700 outline-none transition-all focus:border-[#1a4972] focus:bg-white focus:ring-2 focus:ring-[#1a4972]/10"
        />
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <!-- Time chips -->
        <button
          v-for="f in timeFilters" :key="f.value"
          @click="filterByTime(f.value)"
          :class="[
            'px-3.5 py-1.5 rounded-full border text-xs font-semibold transition-all cursor-pointer',
            timeFilter === f.value
              ? 'bg-[#1a4972] border-[#1a4972] text-white'
              : 'bg-slate-50 border-slate-200 text-slate-600 hover:border-[#1a4972] hover:text-[#1a4972]'
          ]"
        >{{ f.label }}</button>

        <!-- Type filter -->
        <select
          v-model="filters.type"
          @change="applyFilters"
          class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600 cursor-pointer outline-none focus:border-[#1a4972] transition-colors"
        >
          <option value="">All Types</option>
          <option value="system">🛡 System</option>
          <option value="case">📁 Case Activity</option>
        </select>

        <!-- Status filter (system only) -->
        <select
          v-model="filters.status"
          @change="applyFilters"
          class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600 cursor-pointer outline-none focus:border-[#1a4972] transition-colors"
        >
          <option value="">All Status</option>
          <option value="success">✓ Success</option>
          <option value="failed">✗ Failed</option>
        </select>

        <button
          v-if="hasActiveFilters"
          @click="clearFilters"
          class="px-3.5 py-1.5 rounded-full bg-red-100 text-red-600 text-xs font-semibold hover:bg-red-200 transition-colors cursor-pointer border-none"
        >Clear</button>
      </div>

      <!-- Active filter tags -->
      <div v-if="hasActiveFilters" class="flex flex-wrap gap-1.5 mt-3 pt-3 border-t border-slate-100">
        <span v-if="filters.search" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-[11px] font-semibold">
          "{{ filters.search }}"
          <button @click="filters.search='';applyFilters()" class="opacity-60 hover:opacity-100 bg-transparent border-none cursor-pointer text-sm leading-none p-0">×</button>
        </span>
        <span v-if="filters.type" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-50 text-purple-700 text-[11px] font-semibold">
          {{ filters.type === 'system' ? '🛡 System' : '📁 Case Activity' }}
          <button @click="filters.type='';applyFilters()" class="opacity-60 hover:opacity-100 bg-transparent border-none cursor-pointer text-sm leading-none p-0">×</button>
        </span>
        <span v-if="filters.status" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-semibold">
          {{ filters.status }}
          <button @click="filters.status='';applyFilters()" class="opacity-60 hover:opacity-100 bg-transparent border-none cursor-pointer text-sm leading-none p-0">×</button>
        </span>
      </div>
    </div>

    <!-- ── Pagination info ── -->
    <div v-if="pagination.total > 0" class="flex items-center justify-between bg-white border border-slate-200 rounded-xl px-4 py-2 mb-4 text-xs text-slate-500">
      <span>
        Showing <strong class="text-slate-700">{{ pagination.from }}–{{ pagination.to }}</strong>
        of <strong class="text-slate-700">{{ pagination.total }}</strong> activities
        <span class="mx-2 text-slate-300">·</span>
        Page {{ pagination.current_page }}/{{ pagination.last_page }}
      </span>
      <div class="flex items-center gap-2">
        Show:
        <select v-model="perPage" @change="changePerPage" class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600 cursor-pointer outline-none">
          <option :value="10">10</option>
          <option :value="15">15</option>
          <option :value="20">20</option>
          <option :value="50">50</option>
        </select>
      </div>
    </div>

 
    <div  class="flex flex-col">
      <template v-for="(group, date) in groupedMergedLogs" :key="date">

        <!-- Date separator -->
        <div class="flex items-center py-5 sticky top-4 z-10">
          <div class="inline-flex items-center gap-2 bg-white border border-[#1a4972]/10 rounded-full px-4 py-1.5 text-xs font-bold text-[#1a4972] shadow-sm">
            <span class="bg-[#1a4972] text-white px-2 py-0.5 rounded-full text-[10px]">{{ getDateWd(date) }}</span>
            {{ formatDateHeader(date) }}
          </div>
        </div>

        <!-- Each log entry -->
        <div v-for="log in group" :key="`${log._type}-${log.id}`" class="flex">
          <!-- Timeline icon col -->
          <div class="flex flex-col items-center px-4 flex-shrink-0">
            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center text-base flex-shrink-0 mt-4', getIconBg(log)]">
              {{ getIcon(log) }}
            </div>
            <div class="flex-1 w-0.5 bg-slate-200 my-1.5 min-h-3"></div>
          </div>

          <!-- Card -->
          <div class="flex-1 bg-white border border-slate-200 rounded-2xl p-4 my-2 transition-all hover:shadow-lg hover:border-slate-300">

            <!-- Header row -->
            <div class="flex items-start justify-between gap-3 mb-2">
              <div class="flex items-center gap-2 flex-wrap min-w-0">
                <!-- Type badge -->
                <span v-if="log._type === 'case'" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-[#1a4972]/8 text-[#1a4972] text-[10px] font-bold flex-shrink-0">
                  📁 Case
                </span>
                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[10px] font-bold flex-shrink-0">
                  🛡 System
                </span>
                <p class="text-[13px] font-semibold text-slate-800 leading-snug">{{ getTitle(log) }}</p>
              </div>
              <div class="flex items-center gap-2 flex-shrink-0">
                <span v-if="log.status" :class="['px-2.5 py-0.5 rounded-full text-[11px] font-bold', log.status === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700']">
                  {{ log.status === 'success' ? '✓' : '✗' }} {{ log.status }}
                </span>
                <span class="text-[11px] text-slate-400 whitespace-nowrap">{{ timeAgo(log.created_at) }}</span>
              </div>
            </div>

            <!-- Case badge (for case logs) -->
            <div v-if="log._type === 'case' && log.case_code" class="mb-2">
              <span class="inline-flex items-center gap-1.5 bg-[#1a4972]/5 border border-[#1a4972]/10 text-[#1a4972] rounded-lg px-2.5 py-1 text-[11px] font-bold">
                {{ log.case_code }}
                <span v-if="log.case_title" class="font-normal text-slate-500 max-w-xs truncate">— {{ log.case_title }}</span>
              </span>
            </div>

            <!-- System: email hint -->
            <p v-if="log._type === 'system' && !log.user?.name && log.email_attempted" class="text-xs text-slate-400 mb-1.5">
              📧 {{ log.email_attempted }}
            </p>

            <!-- System: details text -->
            <p v-if="log._type === 'system' && log.details" class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 mb-2.5 leading-relaxed">
              {{ fmtDetails(log.details) }}
            </p>

            <!-- Case: from → to change -->
            <div
              v-if="log._type === 'case' && log.details && (log.details.from !== undefined || log.details.to !== undefined)"
              class="inline-flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 mb-2 text-xs"
            >
              <span class="bg-red-50 text-red-700 px-2 py-0.5 rounded-md font-semibold line-through decoration-red-300">{{ fmtVal(log.details.from) }}</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-slate-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
              <span class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-md font-semibold">{{ fmtVal(log.details.to) }}</span>
            </div>
            <p v-else-if="log._type === 'case' && log.details?.note" class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 mb-2 leading-relaxed">
              {{ log.details.note }}
            </p>

            <!-- Meta row -->
            <div class="flex flex-wrap gap-3.5 text-[11px] text-slate-400">
              <span v-if="log._type === 'system'">🌐 {{ log.ip_address || 'Unknown IP' }}</span>
              <span v-if="log._type === 'system'">🖥 {{ fmtAgent(log.user_agent) }}</span>
              <span v-if="log._type === 'case'">👤 {{ log.actor }}</span>
              <span>📅 {{ fmtDate(log.created_at) }}</span>
            </div>

            <!-- Expand raw details (system only) -->
            <div v-if="log._type === 'system' && log.details && log.details.length > 150">
              <button @click="toggleExpand(log.id)" class="mt-2 text-[11px] font-bold text-[#1a4972] opacity-70 hover:opacity-100 bg-transparent border-none cursor-pointer transition-opacity">
                {{ expanded.includes(log.id) ? '▲ Less' : '▼ More' }}
              </button>
              <transition
                enter-active-class="transition-all duration-200"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
              >
                <pre v-if="expanded.includes(log.id)" class="mt-2 px-3 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-[11px] font-mono whitespace-pre-wrap text-slate-600">{{ log.details }}</pre>
              </transition>
            </div>
          </div>
        </div>
      </template>

      <!-- Empty state -->
      <div v-if="mergedLogs.length === 0" class="text-center py-16">
        <div class="text-5xl mb-3 opacity-30">📋</div>
        <p class="text-base font-bold text-slate-500 mb-1">No activities found</p>
        <p class="text-[13px] text-slate-400">Try adjusting your search or filters</p>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center gap-1.5 justify-center pt-6 pb-2">
        <button :disabled="pagination.current_page === 1" @click="changePage(pagination.current_page - 1)"
          class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:border-[#1a4972] hover:text-[#1a4972] disabled:opacity-35 disabled:cursor-not-allowed transition-all cursor-pointer">
          ← Prev
        </button>
        <button v-for="p in displayedPages" :key="p" @click="changePage(p)"
          :class="['w-9 h-9 rounded-xl border text-xs font-semibold transition-all cursor-pointer', pagination.current_page === p ? 'bg-[#1a4972] border-[#1a4972] text-white shadow-md' : 'border-slate-200 bg-white text-slate-600 hover:border-[#1a4972] hover:text-[#1a4972]']">
          {{ p }}
        </button>
        <button :disabled="pagination.current_page === pagination.last_page" @click="changePage(pagination.current_page + 1)"
          class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:border-[#1a4972] hover:text-[#1a4972] disabled:opacity-35 disabled:cursor-not-allowed transition-all cursor-pointer">
          Next →
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { debounce } from 'lodash'
import { auditLogService } from '@/services/auditLogService'
import * as XLSX from 'xlsx'

// ── State ─────────────────────────────────────────────────────────
const systemLogs  = ref([])
const caseLogs    = ref([])
const expanded    = ref([])
const timeFilter  = ref('')
const currentPage = ref(1)
const perPage     = ref(15)

// We track a combined total for pagination display
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 })

const filters = reactive({ search: '', type: '', status: '', dateFrom: '', dateTo: '' })

// Export
const showExportMenu = ref(false)
const isExporting    = ref(false)
const toggleExportMenu = () => { showExportMenu.value = !showExportMenu.value }
const closeExportMenu  = () => { showExportMenu.value = false }

// v-click-outside
const vClickOutside = {
  mounted(el, binding) {
    el._out = (e) => { if (!el.contains(e.target)) binding.value(e) }
    document.addEventListener('mousedown', el._out)
  },
  unmounted(el) { document.removeEventListener('mousedown', el._out) }
}

// ── Merge + sort both log streams by date desc ────────────────────
const mergedLogs = computed(() => {
  const sys  = systemLogs.value.map(l => ({ ...l, _type: 'system' }))
  const case_ = caseLogs.value.map(l => ({ ...l, _type: 'case' }))
  return [...sys, ...case_].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const groupedMergedLogs = computed(() => {
  const g = {}
  mergedLogs.value.forEach(l => {
    if (!l.created_at) return
    const d = new Date(l.created_at).toDateString()
    if (!g[d]) g[d] = []
    g[d].push(l)
  })
  return g
})

// ── Pagination ────────────────────────────────────────────────────
const displayedPages = computed(() => {
  const pages = []; const max = 5; const t = pagination.value.last_page; const c = pagination.value.current_page
  if (t <= max) { for (let i = 1; i <= t; i++) pages.push(i) }
  else { let s = Math.max(1, c - 2); let e = Math.min(t, s + max - 1); if (e - s + 1 < max) s = Math.max(1, e - max + 1); for (let i = s; i <= e; i++) pages.push(i) }
  return pages
})

const hasActiveFilters = computed(() =>
  !!(filters.search || filters.type || filters.status || filters.dateFrom || filters.dateTo)
)

// ── Load both sources in parallel ────────────────────────────────
const loadAll = async () => {
  try {
    const params = {
      search:    filters.search   || undefined,
      status:    filters.status   || undefined,
      date_from: filters.dateFrom || undefined,
      date_to:   filters.dateTo   || undefined,
      page:      currentPage.value,
      per_page:  perPage.value,
    }

    const fetchSystem = filters.type === 'case'
      ? Promise.resolve({ data: [], meta: { total: 0, current_page: 1, last_page: 1, from: 0, to: 0 } })
      : auditLogService.getLogs(params)

    const fetchCase = filters.type === 'system'
      ? Promise.resolve({ data: [], meta: { total: 0 } })
      : auditLogService.getCaseActivityLogs(params)

    const [sysRes, caseRes] = await Promise.all([fetchSystem, fetchCase])

    systemLogs.value = sysRes.data  || []
    caseLogs.value   = caseRes.data || []

    // Combine totals for pagination info
    const sysTotal  = sysRes.meta?.total  || 0
    const caseTotal = caseRes.meta?.total || 0
    const combined  = sysTotal + caseTotal

    // Use system meta for page structure (both use same page/perPage)
    const meta = sysRes.meta || caseRes.meta || {}
    pagination.value = {
      current_page: meta.current_page || currentPage.value,
      last_page:    meta.last_page    || 1,
      per_page:     perPage.value,
      total:        combined,
      from:         meta.from || 0,
      to:           meta.to   || 0,
    }
    currentPage.value = pagination.value.current_page
  } catch (e) {
    systemLogs.value = []
    caseLogs.value   = []
  } finally {
  }
}

// ── Controls ──────────────────────────────────────────────────────
const applyFilters   = () => { currentPage.value = 1; loadAll() }
const debouncedApply = debounce(applyFilters, 450)
const clearFilters   = () => { filters.search=''; filters.type=''; filters.status=''; filters.dateFrom=''; filters.dateTo=''; timeFilter.value=''; applyFilters() }
const changePage     = p => { if (p>=1&&p<=pagination.value.last_page) { currentPage.value=p; loadAll(); window.scrollTo({top:0,behavior:'smooth'}) } }
const changePerPage  = () => { currentPage.value=1; loadAll() }
const toggleExpand   = id => expanded.value.includes(id) ? (expanded.value=expanded.value.filter(x=>x!==id)) : expanded.value.push(id)

const fmt = d => d.toISOString().split('T')[0]
const filterByTime = period => {
  timeFilter.value = period; const now = new Date()
  if (period==='today') { filters.dateFrom=filters.dateTo=fmt(now) }
  if (period==='week')  { const w=new Date(now); w.setDate(w.getDate()-7); filters.dateFrom=fmt(w); filters.dateTo=fmt(now) }
  if (period==='month') { const m=new Date(now); m.setMonth(m.getMonth()-1); filters.dateFrom=fmt(m); filters.dateTo=fmt(now) }
  applyFilters()
}

const timeFilters = [{ label:'Today',value:'today' },{ label:'Week',value:'week' },{ label:'Month',value:'month' }]

// ── Icon / styling helpers ────────────────────────────────────────
const getIcon = log => {
  if (log._type === 'system') {
    return ({ login:'→', logout:'←', password_change:'🔑', user_create:'➕', user_update:'✏️', user_delete:'🗑️', user_view:'👁', activated:'✅', deactivated:'⛔' }[log.action] || '•')
  }
  const l = (log.action||'').toLowerCase()
  if (l.includes('creat'))  return '➕'
  if (l.includes('archiv')) return '📦'
  if (l.includes('assign')) return '👤'
  if (l.includes('updat') || l.includes('edit')) return '✏️'
  if (l.includes('delet'))  return '🗑️'
  if (l.includes('status')) return '🔄'
  if (l.includes('priority')) return '🚨'
  return '•'
}

const getIconBg = log => {
  if (log._type === 'system') {
    return ({ login:'bg-blue-50', logout:'bg-orange-50', password_change:'bg-amber-50', user_create:'bg-emerald-50', user_update:'bg-purple-50', user_delete:'bg-red-50', user_view:'bg-slate-50', activated:'bg-emerald-50', deactivated:'bg-red-50' }[log.action] || 'bg-slate-50')
  }
  const l = (log.action||'').toLowerCase()
  if (l.includes('creat'))  return 'bg-emerald-50'
  if (l.includes('archiv')) return 'bg-slate-100'
  if (l.includes('assign')) return 'bg-blue-50'
  if (l.includes('updat') || l.includes('edit') || l.includes('title')) return 'bg-purple-50'
  if (l.includes('delet'))  return 'bg-red-50'
  if (l.includes('status')) return 'bg-amber-50'
  if (l.includes('priority')) return 'bg-orange-50'
  return 'bg-slate-50'
}

const getTitle = log => {
  if (log._type === 'case') return `${log.actor || 'System'} ${log.action || ''}`

  const a = log.action; let name = 'Unknown User'
  if (log.user?.name) name = log.user.name
  else if (log.email_attempted) { const p=log.email_attempted.split('@')[0]; name=p.charAt(0).toUpperCase()+p.slice(1) }
  const em = log.email_attempted || ''
  if (a==='login' && log.status==='failed') return `Failed login attempt${em?' for '+em:''}`
  if (a==='password_change' && log.status==='failed') return `${name} failed to change password`
  const t = { login:`${name} logged in`, logout:`${name} logged out`, password_change:`${name} changed password`, user_create:`Admin created ${em}`, user_update:`Admin updated ${em}`, user_delete:`Admin deleted ${em}`, user_view:`Admin viewed ${em}`, activated:`${name}'s account activated`, deactivated:`${name}'s account deactivated` }
  return t[a] || `${a} by ${name}`
}

// ── Date / text formatters ────────────────────────────────────────
const getDateWd        = ds => new Date(ds).toLocaleDateString('en-US', { weekday:'short' })
const formatDateHeader = ds => {
  const d=new Date(ds); const t=new Date(); const y=new Date(t); y.setDate(y.getDate()-1)
  if (d.toDateString()===t.toDateString()) return 'Today'
  if (d.toDateString()===y.toDateString()) return 'Yesterday'
  return d.toLocaleDateString('en-US', { weekday:'long', month:'long', day:'numeric', year:'numeric' })
}
const timeAgo = ds => {
  if (!ds) return ''; const s=Math.floor((new Date()-new Date(ds))/1000)
  if (s<5) return 'just now'; if (s<60) return `${s}s`
  const m=Math.floor(s/60); if (m<60) return `${m}m ago`
  const h=Math.floor(m/60); if (h<24) return `${h}h ago`
  const dy=Math.floor(h/24); if (dy<7) return `${dy}d ago`
  return fmtDate(ds)
}
const fmtDate   = ds => { if (!ds) return ''; return new Date(ds).toLocaleDateString('en-US', { month:'2-digit', day:'2-digit', year:'numeric' }) }
const fmtAgent  = ua => { if (!ua) return 'Unknown'; if (ua.includes('Firefox')) return 'Firefox'; if (ua.includes('Chrome')) return 'Chrome'; if (ua.includes('Safari')) return 'Safari'; if (ua.includes('Edge')) return 'Edge'; return ua.slice(0,20)+'…' }
const fmtDetails = d => {
  if (!d) return ''
  return d.split('\n').filter(l=>{ const t=l.trim(); return t&&!t.includes('SUCCESSFUL')&&!t.includes('FAILED')&&!t.includes('LOGIN')&&!t.includes('PASSWORD')&&!t.match(/^[-]+$/) }).map(l=>l.replace(/^[-•*]\s*/,'').trim()).join('\n')
}
const fmtVal = v => (v===null||v===undefined||v==='') ? '(empty)' : String(v)

// ── Export ────────────────────────────────────────────────────────
const buildRow = log => {
  if (log._type === 'case') return {
    'Type':       'Case Activity',
    'Date/Time':  log.created_at ? new Date(log.created_at).toLocaleString('en-US') : '',
    'Actor':      log.actor || '',
    'Case Code':  log.case_code || '',
    'Case Title': log.case_title || '',
    'Action':     log.action || '',
    'Status':     '',
    'Details':    log.details ? (log.details.note || `${fmtVal(log.details.from)} → ${fmtVal(log.details.to)}`) : '',
    'IP Address': '',
    'Browser':    '',
  }
  return {
    'Type':       'System',
    'Date/Time':  log.created_at ? new Date(log.created_at).toLocaleString('en-US') : '',
    'Actor':      log.user?.name || '',
    'Case Code':  '',
    'Case Title': '',
    'Action':     log.action || '',
    'Status':     log.status || '',
    'Details':    log.details ? fmtDetails(log.details) : '',
    'IP Address': log.ip_address || '',
    'Browser':    fmtAgent(log.user_agent),
  }
}

const styleSheet = (ws, colCount) => {
  const hs = { font:{bold:true,color:{rgb:'FFFFFF'},name:'Arial',sz:11}, fill:{fgColor:{rgb:'1A4972'},patternType:'solid'}, alignment:{horizontal:'center',vertical:'center'} }
  for (let c=0;c<colCount;c++) { const r=XLSX.utils.encode_cell({r:0,c}); if (ws[r]) ws[r].s=hs }
  const range = XLSX.utils.decode_range(ws['!ref'])
  for (let r=1;r<=range.e.r;r++) for (let c=0;c<=range.e.c;c++) {
    const ref=XLSX.utils.encode_cell({r,c}); if (!ws[ref]) ws[ref]={v:'',t:'s'}
    ws[ref].s={font:{name:'Arial',sz:10},fill:{fgColor:{rgb:r%2===0?'F0F4F8':'FFFFFF'},patternType:'solid'},alignment:{vertical:'center'}}
  }
}

const exportExcel = async (scope) => {
  closeExportMenu(); isExporting.value = true
  try {
    let rows
    if (scope === 'all') {
      const bigParams = { search:filters.search||undefined, status:filters.status||undefined, date_from:filters.dateFrom||undefined, date_to:filters.dateTo||undefined, page:1, per_page:9999 }
      const [sysAll, caseAll] = await Promise.all([
        filters.type === 'case'   ? Promise.resolve({ data:[] }) : auditLogService.getLogs(bigParams),
        filters.type === 'system' ? Promise.resolve({ data:[] }) : auditLogService.getCaseActivityLogs(bigParams),
      ])
      const combined = [
        ...(sysAll.data||[]).map(l=>({...l,_type:'system'})),
        ...(caseAll.data||[]).map(l=>({...l,_type:'case'})),
      ].sort((a,b)=>new Date(b.created_at)-new Date(a.created_at))
      rows = combined.map(buildRow)
    } else {
      rows = mergedLogs.value.map(buildRow)
    }
    if (!rows.length) return

    const ws = XLSX.utils.json_to_sheet(rows)
    ws['!cols'] = [{wch:10},{wch:20},{wch:22},{wch:12},{wch:30},{wch:20},{wch:10},{wch:40},{wch:16},{wch:12}]
    ws['!rows'] = [{ hpt:22 }]
    styleSheet(ws, Object.keys(rows[0]).length)

    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Activity Logs')
    XLSX.writeFile(wb, `activity-logs_${new Date().toISOString().slice(0,10)}.xlsx`)
  } catch (e) { console.error('Export failed', e) }
  finally { isExporting.value = false }
}

onMounted(loadAll)
</script>