<template>
  <div class="al-page">

    <!-- ── Top bar ──────────────────────────────────────────────── -->
    <div class="al-topbar">
      <div class="mb-7">
        <div class="flex items-center gap-3 mb-1">
          <div class="w-1 h-8 rounded-full" style="background: linear-gradient(to bottom, #1a4972, #2d6db5);"></div>
          <h1 class="text-2xl font-bold tracking-tight" style="color: #1a4972;">Activity Logs</h1>
        </div>
      </div>

      <div class="topbar-right">
        <!-- Export Button -->
        <div class="export-wrap">
          <button
            class="export-btn"
            :class="{ 'export-btn--loading': isExporting }"
            :disabled="isExporting || logs.length === 0"
            @click="toggleExportMenu"
          >
            <svg v-if="!isExporting" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
              <polyline points="7 10 12 15 17 10"/>
              <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            <span v-if="isExporting" class="export-spinner"></span>
            {{ isExporting ? 'Exporting…' : 'Export' }}
            <svg v-if="!isExporting" xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </button>

          <!-- Dropdown -->
          <transition name="drop">
            <div v-if="showExportMenu" class="export-dropdown" v-click-outside="closeExportMenu">
              <div class="export-dropdown-header">Export as</div>

              <button class="export-option" @click="exportExcel('current')">
                <span class="export-option-icon export-option-icon--green">
                  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>
                </span>
                <div>
                  <p class="export-option-label">Excel (.xlsx) — Current page</p>
                  <p class="export-option-sub">{{ logs.length }} rows visible</p>
                </div>
              </button>

              <button class="export-option" @click="exportExcel('all')">
                <span class="export-option-icon export-option-icon--green">
                  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>
                </span>
                <div>
                  <p class="export-option-label">Excel (.xlsx) — All pages</p>
                  <p class="export-option-sub">{{ pagination.total }} total rows</p>
                </div>
              </button>

              <div class="export-dropdown-divider"></div>
            </div>
          </transition>
        </div>

        <!-- Live Badge -->
        <div class="live-badge">
          <span class="ping-ring"></span>
          <span class="ping-core"></span>
          Live
        </div>
      </div>
    </div>

    <!-- ── Stats ─────────────────────────────────────────────────── -->
    <div class="stats-grid">
      <div v-for="(s, i) in statCards" :key="i" class="stat-card" :style="`--accent:${s.accent}`">
        <div class="stat-icon">{{ s.icon }}</div>
        <div class="stat-body">
          <p class="stat-label">{{ s.label }}</p>
          <p class="stat-val">{{ s.value }}</p>
        </div>
        <div class="stat-bar" :style="`width:${s.pct}%`"></div>
      </div>
    </div>

    <!-- ── Filters ────────────────────────────────────────────────── -->
    <div class="filter-card">
      <div class="search-wrap">
        <svg class="search-ico" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
          fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input v-model="filters.search" @input="debouncedApply" type="text"
          placeholder="Search user, action, IP…" class="search-input" />
      </div>

      <div class="chip-row">
        <button v-for="f in timeFilters" :key="f.value"
          class="chip" :class="{ active: timeFilter === f.value }"
          @click="filterByTime(f.value)">{{ f.label }}</button>

        <select v-model="filters.action" @change="applyFilters" class="flt-select">
          <option value="">All Actions</option>
          <option v-for="a in availableActions" :key="a.value" :value="a.value">{{ a.label }}</option>
        </select>
        <select v-model="filters.status" @change="applyFilters" class="flt-select">
          <option value="">All Status</option>
          <option value="success">✓ Success</option>
          <option value="failed">✗ Failed</option>
        </select>

        <button v-if="hasActiveFilters" @click="clearFilters" class="clear-btn">Clear</button>
      </div>

      <div v-if="hasActiveFilters" class="tag-row">
        <span v-if="filters.search" class="ftag ftag--blue">
          "{{ filters.search }}" <button @click="filters.search='';applyFilters()">×</button>
        </span>
        <span v-if="filters.action" class="ftag ftag--purple">
          {{ getActionLabel(filters.action) }} <button @click="filters.action='';applyFilters()">×</button>
        </span>
        <span v-if="filters.status" class="ftag ftag--green">
          {{ filters.status }} <button @click="filters.status='';applyFilters()">×</button>
        </span>
      </div>
    </div>

    <!-- ── Pagination info ────────────────────────────────────────── -->
    <div v-if="!isLoading && pagination.total > 0" class="pg-info">
      <span>
        Showing <strong>{{ pagination.from }}–{{ pagination.to }}</strong>
        of <strong>{{ pagination.total }}</strong> activities
        <span class="pg-sep">·</span>
        Page {{ pagination.current_page }}/{{ pagination.last_page }}
      </span>
      <div class="per-page">
        Show:
        <select v-model="perPage" @change="changePerPage" class="flt-select">
          <option :value="10">10</option><option :value="15">15</option>
          <option :value="20">20</option><option :value="50">50</option>
        </select>
      </div>
    </div>

    <!-- ── Loading skeleton ───────────────────────────────────────── -->
    <div v-if="isLoading" class="skeletons">
      <div v-for="n in 5" :key="n" class="skeleton-row">
        <div class="sk-circle"></div>
        <div class="sk-lines">
          <div class="sk-line sk-line--lg"></div>
          <div class="sk-line sk-line--md"></div>
          <div class="sk-line sk-line--sm"></div>
        </div>
      </div>
    </div>

    <!-- ── Timeline feed ──────────────────────────────────────────── -->
    <div v-else class="timeline">
      <template v-for="(group, date) in groupedLogs" :key="date">
        <div class="date-sep">
          <div class="date-pill">
            <span class="date-wd">{{ getDateWd(date) }}</span>
            {{ formatDateHeader(date) }}
          </div>
          <span class="date-count">{{ group.length }} events</span>
        </div>

        <div v-for="log in group" :key="log.id" class="log-card">
          <div class="log-icon-col">
            <div class="log-icon" :class="iconClass(log.action)">{{ actionIcon(log.action) }}</div>
            <div class="log-line"></div>
          </div>
          <div class="log-content">
            <div class="log-header">
              <p class="log-title">{{ actionTitle(log) }}</p>
              <div class="log-header-right">
                <span v-if="log.status" class="status-badge" :class="log.status === 'success' ? 'status--ok' : 'status--fail'">
                  {{ log.status === 'success' ? '✓' : '✗' }} {{ log.status }}
                </span>
                <span class="log-time">{{ timeAgo(log.created_at) }}</span>
              </div>
            </div>
            <p v-if="!log.user?.name && log.email_attempted" class="log-email">
              📧 {{ log.email_attempted }}
            </p>
            <p v-if="log.details" class="log-details">{{ fmtDetails(log.details) }}</p>
            <div class="log-meta">
              <span>🌐 {{ log.ip_address || 'Unknown IP' }}</span>
              <span>🖥 {{ fmtAgent(log.user_agent) }}</span>
              <span>📅 {{ fmtDate(log.created_at) }}</span>
            </div>
            <div v-if="log.details && log.details.length > 150">
              <button class="expand-btn" @click="toggleExpand(log.id)">
                {{ expanded.includes(log.id) ? '▲ Less' : '▼ More' }}
              </button>
              <transition name="slide">
                <pre v-if="expanded.includes(log.id)" class="raw-details">{{ log.details }}</pre>
              </transition>
            </div>
          </div>
        </div>
      </template>

      <div v-if="!isLoading && logs.length === 0" class="empty-state">
        <div class="empty-icon">📋</div>
        <p class="empty-title">No activities found</p>
        <p class="empty-sub">Try adjusting your search or filters</p>
      </div>

      <div v-if="pagination.last_page > 1" class="pagination">
        <button class="pg-btn" :disabled="pagination.current_page === 1"
          @click="changePage(pagination.current_page - 1)">← Prev</button>
        <button v-for="p in displayedPages" :key="p"
          class="pg-num" :class="{ 'pg-num--active': pagination.current_page === p }"
          @click="changePage(p)">{{ p }}</button>
        <button class="pg-btn" :disabled="pagination.current_page === pagination.last_page"
          @click="changePage(pagination.current_page + 1)">Next →</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { debounce } from 'lodash'
import { auditLogService } from '@/services/auditLogService'
import * as XLSX from 'xlsx'

// ── Existing state ────────────────────────────────────────────────
const logs           = ref([])
const isLoading      = ref(false)
const expanded       = ref([])
const timeFilter     = ref('')
const availableActions = ref([])
const currentPage    = ref(1)
const perPage        = ref(15)
const pagination     = ref({ current_page:1, last_page:1, per_page:15, total:0, from:0, to:0 })
const filters        = reactive({ search:'', action:'', status:'', dateFrom:'', dateTo:'' })
const stats          = ref({ total:0, logins:0, logouts:0, passwordChanges:0, userChanges:0 })

// ── Export state ──────────────────────────────────────────────────
const showExportMenu = ref(false)
const isExporting    = ref(false)

const toggleExportMenu = () => { showExportMenu.value = !showExportMenu.value }
const closeExportMenu  = () => { showExportMenu.value = false }

// ── v-click-outside directive ─────────────────────────────────────
const vClickOutside = {
  mounted(el, binding) {
    el._clickOutsideHandler = (e) => { if (!el.contains(e.target)) binding.value(e) }
    document.addEventListener('mousedown', el._clickOutsideHandler)
  },
  unmounted(el) { document.removeEventListener('mousedown', el._clickOutsideHandler) }
}

// ── Export helpers ────────────────────────────────────────────────
const buildExportRow = (log) => ({
  'Date/Time':     log.created_at ? new Date(log.created_at).toLocaleString('en-US') : '',
  'User':          log.user?.name || '',
  'Email':         log.email_attempted || '',
  'Action':        log.action || '',
  'Status':        log.status || '',
  'IP Address':    log.ip_address || '',
  'Browser':       fmtAgent(log.user_agent),
  'User Agent':    log.user_agent || '',
  'Details':       log.details ? fmtDetails(log.details) : '',
})

const buildFilenameBase = () => {
  const ts = new Date().toISOString().slice(0, 10)
  const parts = ['activity-logs', ts]
  if (filters.action)   parts.push(filters.action)
  if (filters.status)   parts.push(filters.status)
  if (filters.dateFrom) parts.push(filters.dateFrom)
  return parts.join('_')
}

// Style the header row
const styleSheet = (ws, colCount) => {
  const headerStyle = {
    font:      { bold: true, color: { rgb: 'FFFFFF' }, name: 'Arial', sz: 11 },
    fill:      { fgColor: { rgb: '1A4972' }, patternType: 'solid' },
    alignment: { horizontal: 'center', vertical: 'center' },
    border: {
      bottom: { style: 'thin', color: { rgb: 'FFFFFF' } },
      right:  { style: 'thin', color: { rgb: 'FFFFFF' } },
    }
  }
  for (let c = 0; c < colCount; c++) {
    const cellRef = XLSX.utils.encode_cell({ r: 0, c })
    if (ws[cellRef]) ws[cellRef].s = headerStyle
  }
  // Alternate row shading
  const range = XLSX.utils.decode_range(ws['!ref'])
  for (let r = 1; r <= range.e.r; r++) {
    for (let c = 0; c <= range.e.c; c++) {
      const cellRef = XLSX.utils.encode_cell({ r, c })
      if (!ws[cellRef]) ws[cellRef] = { v: '', t: 's' }
      ws[cellRef].s = {
        font:      { name: 'Arial', sz: 10 },
        fill:      r % 2 === 0
          ? { fgColor: { rgb: 'F0F4F8' }, patternType: 'solid' }
          : { fgColor: { rgb: 'FFFFFF' }, patternType: 'solid' },
        alignment: { vertical: 'center', wrapText: false },
      }
    }
  }
}

// Export current page logs to Excel
const exportExcel = async (scope) => {
  closeExportMenu()
  isExporting.value = true

  try {
    let rows

    if (scope === 'all' && pagination.value.last_page > 1) {
      // Fetch ALL pages with current filters
      const r = await auditLogService.getLogs({
        search:    filters.search   || undefined,
        action:    filters.action   || undefined,
        status:    filters.status   || undefined,
        date_from: filters.dateFrom || undefined,
        date_to:   filters.dateTo   || undefined,
        page:      1,
        per_page:  pagination.value.total, // fetch everything
      })
      rows = (r.data || []).map(buildExportRow)
    } else {
      rows = logs.value.map(buildExportRow)
    }

    if (!rows.length) return

    const ws = XLSX.utils.json_to_sheet(rows)

    // Column widths
    ws['!cols'] = [
      { wch: 20 }, // Date/Time
      { wch: 22 }, // User
      { wch: 28 }, // Email
      { wch: 16 }, // Action
      { wch: 10 }, // Status
      { wch: 16 }, // IP Address
      { wch: 12 }, // Browser
      { wch: 40 }, // User Agent
      { wch: 50 }, // Details
    ]
    ws['!rows'] = [{ hpt: 22 }] // Header row height

    styleSheet(ws, Object.keys(rows[0]).length)

    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Activity Logs')

    // Summary sheet
    const summaryData = [
      ['Nicolas Pineda Law Office — Activity Log Export'],
      [''],
      ['Generated',      new Date().toLocaleString('en-US')],
      ['Total Records',  rows.length],
      ['Filter: Search', filters.search  || '(none)'],
      ['Filter: Action', filters.action  || '(none)'],
      ['Filter: Status', filters.status  || '(none)'],
      ['Filter: From',   filters.dateFrom || '(none)'],
      ['Filter: To',     filters.dateTo   || '(none)'],
    ]
    const wsSummary = XLSX.utils.aoa_to_sheet(summaryData)
    wsSummary['!cols'] = [{ wch: 22 }, { wch: 40 }]
    if (wsSummary['A1']) wsSummary['A1'].s = { font: { bold: true, sz: 13, name: 'Arial', color: { rgb: '1A4972' } } }
    XLSX.utils.book_append_sheet(wb, wsSummary, 'Export Info')

    XLSX.writeFile(wb, `${buildFilenameBase()}.xlsx`)
  } catch (e) {
    console.error('Export failed', e)
  } finally {
    isExporting.value = false
  }
}

// ── Existing computed / methods (unchanged) ───────────────────────
const timeFilters = [
  { label:'Today', value:'today' },
  { label:'Week',  value:'week'  },
  { label:'Month', value:'month' },
]

const hasActiveFilters = computed(() =>
  !!(filters.search||filters.action||filters.status||filters.dateFrom||filters.dateTo)
)

const displayedPages = computed(() => {
  const pages=[]; const max=5; const t=pagination.value.last_page; const c=pagination.value.current_page
  if(t<=max){ for(let i=1;i<=t;i++) pages.push(i) }
  else { let s=Math.max(1,c-2); let e=Math.min(t,s+max-1); if(e-s+1<max) s=Math.max(1,e-max+1); for(let i=s;i<=e;i++) pages.push(i) }
  return pages
})

const statCards = computed(() => {
  const t = stats.value.total || 1
  return [
    { label:'Total',        value:stats.value.total,           icon:'📊', accent:'#1a4972', pct:100 },
    { label:'Logins',       value:stats.value.logins,          icon:'🔓', accent:'#3b82f6', pct:Math.round(stats.value.logins/t*100) },
    { label:'Logouts',      value:stats.value.logouts,         icon:'🔒', accent:'#f97316', pct:Math.round(stats.value.logouts/t*100) },
    { label:'Pw Changes',   value:stats.value.passwordChanges, icon:'🔑', accent:'#eab308', pct:Math.round(stats.value.passwordChanges/t*100) },
    { label:'User Changes', value:stats.value.userChanges,     icon:'👥', accent:'#a855f7', pct:Math.round(stats.value.userChanges/t*100) },
  ]
})

const groupedLogs = computed(() => {
  const g = {}
  logs.value.forEach(l => {
    if (!l.created_at) return
    const d = new Date(l.created_at).toDateString()
    if (!g[d]) g[d] = []
    g[d].push(l)
  })
  return g
})

const loadLogs = async () => {
  isLoading.value = true
  try {
    const r = await auditLogService.getLogs({
      search:    filters.search    || undefined,
      action:    filters.action    || undefined,
      status:    filters.status    || undefined,
      date_from: filters.dateFrom  || undefined,
      date_to:   filters.dateTo    || undefined,
      page:      currentPage.value,
      per_page:  perPage.value
    })
    logs.value       = r.data  || []
    pagination.value = r.meta  || { current_page:1, last_page:1, per_page:perPage.value, total:0, from:0, to:0 }
    if (r.stats) stats.value = r.stats
    currentPage.value = pagination.value.current_page
  } catch(e) { logs.value = [] }
  finally { isLoading.value = false }
}

const loadActions = async () => {
  try { availableActions.value = await auditLogService.getActions() } catch {}
}

const changePage    = p => { if(p>=1&&p<=pagination.value.last_page){ currentPage.value=p; loadLogs(); window.scrollTo({top:0,behavior:'smooth'}) } }
const changePerPage = () => { currentPage.value=1; loadLogs() }
const applyFilters  = () => { currentPage.value=1; loadLogs() }
const debouncedApply = debounce(applyFilters, 450)
const clearFilters  = () => { filters.search=''; filters.action=''; filters.status=''; filters.dateFrom=''; filters.dateTo=''; timeFilter.value=''; applyFilters() }

const fmt = d => d.toISOString().split('T')[0]
const filterByTime = period => {
  timeFilter.value = period; const now = new Date()
  if(period==='today') { filters.dateFrom=filters.dateTo=fmt(now) }
  if(period==='week')  { const w=new Date(now); w.setDate(w.getDate()-7); filters.dateFrom=fmt(w); filters.dateTo=fmt(now) }
  if(period==='month') { const m=new Date(now); m.setMonth(m.getMonth()-1); filters.dateFrom=fmt(m); filters.dateTo=fmt(now) }
  applyFilters()
}

const toggleExpand = id =>
  expanded.value.includes(id) ? (expanded.value=expanded.value.filter(x=>x!==id)) : expanded.value.push(id)

const getDateWd       = ds => new Date(ds).toLocaleDateString('en-US',{weekday:'short'})
const formatDateHeader = ds => {
  const d=new Date(ds); const t=new Date(); const y=new Date(t); y.setDate(y.getDate()-1)
  if(d.toDateString()===t.toDateString()) return 'Today'
  if(d.toDateString()===y.toDateString()) return 'Yesterday'
  return d.toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric',year:'numeric'})
}
const timeAgo = ds => {
  if(!ds) return ''
  const s=Math.floor((new Date()-new Date(ds))/1000)
  if(s<5) return 'just now'; if(s<60) return `${s}s`
  const m=Math.floor(s/60); if(m<60) return `${m}m ago`
  const h=Math.floor(m/60); if(h<24) return `${h}h ago`
  const dy=Math.floor(h/24); if(dy<7) return `${dy}d ago`
  return fmtDate(ds)
}
const fmtDate   = ds => { if(!ds) return ''; return new Date(ds).toLocaleDateString('en-US',{month:'2-digit',day:'2-digit',year:'numeric'}) }
const fmtAgent  = ua => { if(!ua) return 'Unknown'; if(ua.includes('Firefox')) return 'Firefox'; if(ua.includes('Chrome')) return 'Chrome'; if(ua.includes('Safari')) return 'Safari'; if(ua.includes('Edge')) return 'Edge'; return ua.slice(0,20)+'…' }
const fmtDetails = d => {
  if(!d) return ''
  return d.split('\n').filter(l=>{ const t=l.trim(); return t&&!t.includes('SUCCESSFUL')&&!t.includes('FAILED')&&!t.includes('LOGIN')&&!t.includes('PASSWORD')&&!t.match(/^[-]+$/) }).map(l=>l.replace(/^[-•*]\s*/,'').trim()).join('\n')
}
const actionTitle = log => {
  const a=log.action; let name='Unknown User'
  if(log.user?.name) name=log.user.name
  else if(log.email_attempted){ const p=log.email_attempted.split('@')[0]; name=p.charAt(0).toUpperCase()+p.slice(1) }
  const em=log.email_attempted||''
  if(a==='login'&&log.status==='failed') return `Failed login attempt${em?' for '+em:''}`
  if(a==='password_change'&&log.status==='failed') return `${name} failed to change password`
  const t={ login:`${name} logged in`, logout:`${name} logged out`, password_change:`${name} changed password`, user_create:`Admin created ${em}`, user_update:`Admin updated ${em}`, user_delete:`Admin deleted ${em}`, user_view:`Admin viewed ${em}`, activated:`${name}'s account activated`, deactivated:`${name}'s account deactivated` }
  return t[a]||`${a} by ${name}`
}
const actionIcon  = a => ({login:'→',logout:'←',password_change:'🔑',user_create:'➕',user_update:'✏️',user_delete:'🗑️',user_view:'👁',activated:'✅',deactivated:'⛔'}[a]||'•')
const iconClass   = a => ({login:'icon--blue',logout:'icon--orange',password_change:'icon--amber',user_create:'icon--green',user_update:'icon--purple',user_delete:'icon--red',user_view:'icon--slate',activated:'icon--green',deactivated:'icon--red'}[a]||'icon--slate')
const getActionLabel = a => { const f=availableActions.value.find(x=>x.value===a); return f?f.label:a }

watch(()=>({...filters}), ()=>applyFilters(), {deep:true})
onMounted(()=>{ loadLogs(); loadActions() })
</script>

<style scoped>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.al-page {
  min-height: 100vh;
  padding: 24px;
  background: #f0f4f8;
  font-family: 'Segoe UI', sans-serif;
}

/* ── Topbar ── */
.al-topbar {
  display: flex; align-items: flex-start; justify-content: space-between;
  margin-bottom: 24px;
}
.topbar-right { display: flex; align-items: center; gap: 10px; }

/* ── Export button ── */
.export-wrap { position: relative; }

.export-btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 7px 14px; border-radius: 10px;
  background: #1a4972; color: white;
  border: none; font-size: 12px; font-weight: 600;
  cursor: pointer; transition: background .15s, opacity .15s;
  white-space: nowrap;
}
.export-btn:hover:not(:disabled) { background: #163d5e; }
.export-btn:disabled { opacity: .5; cursor: not-allowed; }
.export-btn--loading { opacity: .7; }

.export-spinner {
  width: 12px; height: 12px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,.3);
  border-top-color: white;
  animation: spin .7s linear infinite;
  flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Export dropdown ── */
.export-dropdown {
  position: absolute; top: calc(100% + 8px); right: 0;
  background: white; border: 1px solid #e2e8f0;
  border-radius: 14px; padding: 8px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.14);
  min-width: 240px; z-index: 50;
}
.export-dropdown-header {
  font-size: 10px; font-weight: 700; letter-spacing: .08em;
  text-transform: uppercase; color: #94a3b8;
  padding: 4px 8px 8px;
}
.export-dropdown-divider { height: 1px; background: #f1f5f9; margin: 6px 0; }

.export-option {
  display: flex; align-items: center; gap: 10px;
  width: 100%; padding: 8px 10px; border-radius: 8px;
  background: none; border: none; cursor: pointer;
  text-align: left; transition: background .12s;
}
.export-option:hover { background: #f8fafc; }

.export-option-icon {
  width: 28px; height: 28px; border-radius: 7px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
}
.export-option-icon--green { background: rgba(16,185,129,.1); color: #065f46; }
.export-option-icon--blue  { background: rgba(59,130,246,.1); color: #1d4ed8; }

.export-option-label {
  font-size: 12px; font-weight: 600; color: #1e293b; line-height: 1.3;
}
.export-option-sub {
  font-size: 11px; color: #94a3b8; margin-top: 1px;
}

/* ── Dropdown transition ── */
.drop-enter-active, .drop-leave-active { transition: all .18s ease; }
.drop-enter-from, .drop-leave-to { opacity: 0; transform: translateY(-6px) scale(.97); }

/* ── Live badge ── */
.live-badge {
  display:flex; align-items:center; gap:8px;
  padding:6px 14px; border-radius:99px;
  background:white; border:1px solid rgba(26,73,114,0.12);
  font-size:11px; font-weight:700; color:#1a4972;
  position:relative;
}
.ping-ring {
  position:absolute; left:12px;
  width:8px; height:8px; border-radius:50%; background:#10b981;
  animation:ping 1.2s ease infinite;
}
.ping-core { width:8px; height:8px; border-radius:50%; background:#10b981; flex-shrink:0; }
@keyframes ping { 0%{transform:scale(1);opacity:.8} 80%{transform:scale(2.2);opacity:0} 100%{opacity:0} }

/* ── Stats ── */
.stats-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:900px){ .stats-grid{ grid-template-columns:repeat(2,1fr); } }
.stat-card { background:white; border-radius:16px; border:1px solid #e8edf2; padding:16px; position:relative; overflow:hidden; transition:box-shadow .2s, transform .2s; }
.stat-card:hover { box-shadow:0 8px 24px rgba(0,0,0,0.09); transform:translateY(-2px); }
.stat-icon  { font-size:22px; margin-bottom:10px; }
.stat-label { font-size:10px; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px; }
.stat-val   { font-size:26px; font-weight:800; color:#1e293b; }
.stat-bar   { position:absolute; bottom:0; left:0; height:3px; background:var(--accent); border-radius:0 3px 3px 0; transition:width .6s ease; }

/* ── Filters ── */
.filter-card { background:white; border-radius:16px; border:1px solid #e8edf2; padding:16px; margin-bottom:16px; }
.search-wrap { position:relative; margin-bottom:12px; }
.search-ico  { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.search-input { width:100%; padding:10px 14px 10px 38px; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:12px; font-size:13px; color:#334155; outline:none; transition:border-color .15s; }
.search-input:focus { border-color:#1a4972; background:white; box-shadow:0 0 0 3px rgba(26,73,114,.08); }
.chip-row { display:flex; flex-wrap:wrap; align-items:center; gap:8px; }
.chip { padding:6px 14px; border-radius:99px; border:1.5px solid #e2e8f0; background:#f8fafc; color:#475569; font-size:12px; font-weight:600; cursor:pointer; transition:.15s; }
.chip:hover { border-color:#1a4972; color:#1a4972; }
.chip.active { background:#1a4972; border-color:#1a4972; color:white; }
.flt-select { padding:6px 10px; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:10px; font-size:12px; color:#475569; cursor:pointer; outline:none; }
.flt-select:focus { border-color:#1a4972; }
.clear-btn { padding:6px 14px; border-radius:99px; border:none; background:#fee2e2; color:#dc2626; font-size:12px; font-weight:600; cursor:pointer; transition:.15s; }
.clear-btn:hover { background:#fca5a5; }
.tag-row { display:flex; flex-wrap:wrap; gap:6px; margin-top:10px; padding-top:10px; border-top:1px solid #f1f5f9; }
.ftag { display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:99px; font-size:11px; font-weight:600; }
.ftag button { background:none; border:none; cursor:pointer; font-size:13px; line-height:1; opacity:.6; }
.ftag button:hover { opacity:1; }
.ftag--blue   { background:rgba(26,73,114,.08);  color:#1a4972; }
.ftag--purple { background:rgba(168,85,247,.08); color:#7e22ce; }
.ftag--green  { background:rgba(16,185,129,.08); color:#065f46; }

/* ── Pagination info ── */
.pg-info { display:flex; align-items:center; justify-content:space-between; background:white; border-radius:12px; border:1px solid #e8edf2; padding:8px 16px; margin-bottom:16px; font-size:12px; color:#64748b; }
.pg-sep { margin:0 8px; color:#cbd5e1; }
.per-page { display:flex; align-items:center; gap:8px; }

/* ── Skeletons ── */
.skeletons { display:flex; flex-direction:column; gap:12px; }
.skeleton-row { display:flex; gap:16px; padding:20px; background:white; border-radius:16px; border:1px solid #e8edf2; }
.sk-circle { width:44px; height:44px; border-radius:50%; background:#f1f5f9; flex-shrink:0; animation:shimmer 1.5s infinite; }
.sk-lines { flex:1; display:flex; flex-direction:column; gap:10px; }
.sk-line { height:12px; border-radius:6px; background:#f1f5f9; animation:shimmer 1.5s infinite; }
.sk-line--lg { width:40%; } .sk-line--md { width:60%; } .sk-line--sm { width:30%; }
@keyframes shimmer { 0%,100%{opacity:1} 50%{opacity:.5} }

/* ── Timeline ── */
.timeline { display:flex; flex-direction:column; gap:0; }
.date-sep { display:flex; align-items:center; justify-content:space-between; padding:20px 0 10px; position:sticky; top:16px; z-index:5; }
.date-pill { display:inline-flex; align-items:center; gap:8px; background:white; border:1px solid rgba(26,73,114,.12); border-radius:99px; padding:5px 14px; font-size:12px; font-weight:700; color:#1a4972; box-shadow:0 2px 10px rgba(0,0,0,.07); }
.date-wd { background:#1a4972; color:white; padding:2px 8px; border-radius:99px; font-size:10px; }
.date-count { font-size:11px; color:#94a3b8; font-weight:500; }
.log-card { display:flex; gap:0; padding:0; }
.log-icon-col { display:flex; flex-direction:column; align-items:center; padding:0 16px; flex-shrink:0; }
.log-icon { width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; margin-top:16px; }
.log-line { flex:1; width:2px; background:#e8edf2; margin:6px 0; min-height:12px; }
.icon--blue   { background:#eff6ff; } .icon--orange { background:#fff7ed; } .icon--amber  { background:#fffbeb; }
.icon--green  { background:#f0fdf4; } .icon--purple { background:#faf5ff; } .icon--red    { background:#fef2f2; }
.icon--slate  { background:#f8fafc; }
.log-content { flex:1; background:white; border-radius:14px; border:1px solid #e8edf2; padding:16px 18px; margin:8px 0; transition:box-shadow .2s, border-color .2s; }
.log-content:hover { box-shadow:0 4px 20px rgba(0,0,0,.07); border-color:#d0dae5; }
.log-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:6px; }
.log-title  { font-size:13px; font-weight:600; color:#1e293b; line-height:1.4; }
.log-header-right { display:flex; align-items:center; gap:8px; flex-shrink:0; }
.status-badge { padding:2px 10px; border-radius:99px; font-size:11px; font-weight:700; }
.status--ok   { background:rgba(16,185,129,.1); color:#065f46; }
.status--fail { background:rgba(239,68,68,.1);  color:#b91c1c; }
.log-time { font-size:11px; color:#94a3b8; white-space:nowrap; }
.log-email { font-size:12px; color:#94a3b8; margin-bottom:6px; }
.log-details { font-size:12px; color:#64748b; background:#f8fafc; border:1px solid #e8edf2; border-radius:10px; padding:10px 12px; margin-bottom:10px; line-height:1.6; }
.log-meta { display:flex; flex-wrap:wrap; gap:14px; font-size:11px; color:#94a3b8; }
.expand-btn { margin-top:8px; font-size:11px; font-weight:700; color:#1a4972; background:none; border:none; cursor:pointer; opacity:.7; transition:opacity .15s; }
.expand-btn:hover { opacity:1; }
.raw-details { margin-top:8px; padding:10px 12px; background:#f0f4f8; border:1px solid #dde3ea; border-radius:10px; font-size:11px; font-family:monospace; white-space:pre-wrap; color:#475569; }
.empty-state { text-align:center; padding:64px 20px; }
.empty-icon  { font-size:48px; margin-bottom:12px; opacity:.3; }
.empty-title { font-size:16px; font-weight:700; color:#475569; margin-bottom:4px; }
.empty-sub   { font-size:13px; color:#94a3b8; }
.pagination  { display:flex; align-items:center; gap:6px; justify-content:center; padding:24px 0 8px; }
.pg-btn { padding:7px 14px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; color:#475569; font-size:12px; font-weight:600; cursor:pointer; transition:.15s; }
.pg-btn:hover:not(:disabled) { border-color:#1a4972; color:#1a4972; }
.pg-btn:disabled { opacity:.35; cursor:not-allowed; }
.pg-num { width:34px; height:34px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; color:#475569; font-size:12px; font-weight:600; cursor:pointer; transition:.15s; }
.pg-num:hover { border-color:#1a4972; color:#1a4972; }
.pg-num--active { background:#1a4972 !important; border-color:#1a4972 !important; color:white !important; box-shadow:0 4px 12px rgba(26,73,114,.3); }
.slide-enter-active,.slide-leave-active { transition:all .2s ease; }
.slide-enter-from,.slide-leave-to { opacity:0; transform:translateY(-6px); }
</style>