<template>
  <div class="dash-page">

    <!-- ── Top bar ──────────────────────────────────────────────── -->
    <div class="dash-topbar">
      <div>
        <div class="flex items-center gap-3 mb-1">
          <div class="w-1 h-8 rounded-full" style="background: linear-gradient(to bottom, #1a4972, #2d6db5);"></div>
          <h1 class="dash-title">Dashboard</h1>
        </div>
        <p class="dash-sub">Welcome back — here's what's happening today.</p>
      </div>
      <div class="topbar-right">
        <span class="date-badge">{{ currentDate }}</span>
        <div class="live-badge">
          <span class="ping-ring"></span>
          <span class="ping-core"></span>
          Live
        </div>
      </div>
    </div>

    <!-- ── Stats ─────────────────────────────────────────────────── -->
    <div class="stats-grid">
      <div
        v-for="(s, i) in statCards"
        :key="i"
        class="stat-card"
        :style="`--accent:${s.accent}; animation-delay:${i*70}ms`"
      >
        <div class="stat-icon">{{ s.icon }}</div>
        <div class="stat-body">
          <p class="stat-label">{{ s.label }}</p>
          <p class="stat-val">{{ s.value }}</p>
        </div>
        <div class="stat-delta" :class="s.delta >= 0 ? 'delta-up' : 'delta-down'">
          <svg v-if="s.delta >= 0" viewBox="0 0 10 10" class="delta-arrow"><path d="M5 2 L9 8 L1 8 Z"/></svg>
          <svg v-else viewBox="0 0 10 10" class="delta-arrow"><path d="M5 8 L9 2 L1 2 Z"/></svg>
          {{ Math.abs(s.delta) }}%
        </div>
        <div class="stat-bar" :style="`width:${s.pct}%`"></div>
      </div>
    </div>

    <!-- ── Mid row ────────────────────────────────────────────────── -->
    <div class="mid-grid">

      <!-- Recent Activity -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Recent Activity</span>
          <button class="btn-ghost">View all</button>
        </div>
        <ul class="activity-list">
          <li
            v-for="(item, i) in recentActivity"
            :key="i"
            class="activity-item"
            :style="`animation-delay:${180 + i*50}ms`"
          >
            <div class="act-dot" :class="`dot--${item.type}`"></div>
            <div class="act-content">
              <span class="act-text">{{ item.text }}</span>
              <span class="act-time">{{ item.time }}</span>
            </div>
            <span class="act-tag" :class="`tag--${item.type}`">{{ item.tag }}</span>
          </li>
        </ul>
      </div>

      <!-- Quick Actions -->
      <div class="panel actions-panel">
        <div class="panel-header">
          <span class="panel-title">Quick Actions</span>
        </div>
        <div class="actions-grid">
          <button
            v-for="(action, i) in quickActions"
            :key="i"
            class="action-btn"
            :style="`animation-delay:${260 + i*40}ms`"
          >
            <div class="action-icon-wrap">
              <component :is="action.icon" class="action-icon" />
            </div>
            <span class="action-label">{{ action.label }}</span>
          </button>
        </div>
      </div>

    </div>

    <!-- ── Users Table ─────────────────────────────────────────────── -->
    <div class="panel" style="animation-delay:350ms">
      <div class="panel-header">
        <span class="panel-title">System Users</span>
        <div class="table-controls">
          <div class="search-wrap">
            <svg class="search-ico" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input v-model="searchQuery" type="text" placeholder="Search users…" class="search-input" />
          </div>
          <button class="btn-primary">+ Add User</button>
        </div>
      </div>

      <div class="table-wrap">
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
            <tr v-for="(user, i) in filteredUsers" :key="i" class="table-row">
              <td>
                <div class="user-cell">
                  <div class="user-avatar">{{ getInitials(user.name) }}</div>
                  <div>
                    <div class="user-name">{{ user.name }}</div>
                    <div class="user-email">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td>
                <span class="role-chip" :class="`role--${user.role.toLowerCase()}`">{{ user.role }}</span>
              </td>
              <td>
                <span class="status-badge" :class="`status--${user.status}`">{{ user.status }}</span>
              </td>
              <td class="td-muted">{{ user.lastLogin }}</td>
              <td>
                <div class="row-actions">
                  <button class="row-btn">Edit</button>
                  <button class="row-btn row-btn--danger">Remove</button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredUsers.length === 0">
              <td colspan="5" class="td-empty">No users found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, defineComponent, h } from 'vue'

// ─── Inline SVG Icons ─────────────────────────────────────────────
const IconPlus     = defineComponent({ render: () => h('svg', { viewBox:'0 0 24 24', fill:'none', stroke:'currentColor', 'stroke-width':'2' }, [h('line',{x1:'12',y1:'5',x2:'12',y2:'19'}),h('line',{x1:'5',y1:'12',x2:'19',y2:'12'})])})
const IconExport   = defineComponent({ render: () => h('svg', { viewBox:'0 0 24 24', fill:'none', stroke:'currentColor', 'stroke-width':'2' }, [h('path',{d:'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'}),h('polyline',{points:'17 8 12 3 7 8'}),h('line',{x1:'12',y1:'3',x2:'12',y2:'15'})])})
const IconLogs     = defineComponent({ render: () => h('svg', { viewBox:'0 0 24 24', fill:'none', stroke:'currentColor', 'stroke-width':'2' }, [h('path',{d:'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'}),h('line',{x1:'8',y1:'13',x2:'16',y2:'13'}),h('line',{x1:'8',y1:'17',x2:'12',y2:'17'})])})
const IconSettings = defineComponent({ render: () => h('svg', { viewBox:'0 0 24 24', fill:'none', stroke:'currentColor', 'stroke-width':'2' }, [h('circle',{cx:'12',cy:'12',r:'3'}),h('path',{d:'M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z'})])})

// ─── Data ─────────────────────────────────────────────────────────
const searchQuery = ref('')

const currentDate = computed(() =>
  new Date().toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' })
)

const statCards = [
  { label:'Total Users',   value: 24,  delta: 12, accent:'#1a4972', icon:'👥', pct: 100 },
  { label:'Active Cases',  value: 138, delta:  8, accent:'#3b82f6', icon:'💼', pct: 72  },
  { label:'Documents',     value: 512, delta: 23, accent:'#10b981', icon:'📄', pct: 88  },
  { label:'Logins Today',  value: 9,   delta: -3, accent:'#f97316', icon:'🔓', pct: 40  },
]

const recentActivity = [
  { text:'New user account created',       time:'2 min ago',  tag:'User',     type:'user'     },
  { text:'Case #2241 document uploaded',   time:'18 min ago', tag:'Document', type:'document' },
  { text:'Password changed — j.dela@nplo', time:'45 min ago', tag:'Security', type:'security' },
  { text:'Case #2238 status updated',      time:'1 hr ago',   tag:'Case',     type:'case'     },
  { text:'Failed login attempt detected',  time:'2 hr ago',   tag:'Alert',    type:'alert'    },
  { text:'New case #2242 opened',          time:'3 hr ago',   tag:'Case',     type:'case'     },
]

const quickActions = [
  { label:'Add User',    icon: IconPlus     },
  { label:'Export Logs', icon: IconExport   },
  { label:'View Logs',   icon: IconLogs     },
  { label:'Settings',    icon: IconSettings },
]

const users = ref([
  { name:'Juan dela Cruz',  email:'j.delacruz@nplo.ph', role:'Lawyer',    status:'active',   lastLogin:'Today, 8:42 AM'  },
  { name:'Maria Santos',    email:'m.santos@nplo.ph',   role:'Secretary', status:'active',   lastLogin:'Today, 9:01 AM'  },
  { name:'Roberto Reyes',   email:'r.reyes@nplo.ph',    role:'Admin',     status:'active',   lastLogin:'Yesterday'       },
  { name:'Ana Gonzalez',    email:'a.gonzalez@nplo.ph', role:'Lawyer',    status:'inactive', lastLogin:'3 days ago'      },
  { name:'Paolo Mendoza',   email:'p.mendoza@nplo.ph',  role:'Intern',    status:'active',   lastLogin:'Today, 10:15 AM' },
  { name:'Liza Tan',        email:'l.tan@nplo.ph',      role:'Secretary', status:'inactive', lastLogin:'1 week ago'      },
])

const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value
  const q = searchQuery.value.toLowerCase()
  return users.value.filter(u =>
    u.name.toLowerCase().includes(q) ||
    u.email.toLowerCase().includes(q) ||
    u.role.toLowerCase().includes(q)
  )
})

const getInitials = (name) => name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
</script>

<style scoped>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── Page ── */
.dash-page {
  min-height: 100vh;
  padding: 24px;
  background: #f0f4f8;
  font-family: 'Segoe UI', sans-serif;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* ── Topbar ── */
.dash-topbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}
.dash-title {
  font-size: 24px; font-weight: 800;
  color: #1a4972; letter-spacing: -.02em;
}
.dash-sub {
  font-size: 12px; color: #64748b;
  margin-top: 4px; padding-left: 16px;
}
.topbar-right {
  display: flex; align-items: center; gap: 10px;
}
.date-badge {
  padding: 6px 14px; border-radius: 99px;
  background: white; border: 1px solid rgba(26,73,114,0.12);
  font-size: 11px; font-weight: 600; color: #475569;
  white-space: nowrap;
}

/* ── Live badge (identical to activity logs) ── */
.live-badge {
  display: flex; align-items: center; gap: 8px;
  padding: 6px 14px; border-radius: 99px;
  background: white; border: 1px solid rgba(26,73,114,0.12);
  font-size: 11px; font-weight: 700; color: #1a4972;
  position: relative;
}
.ping-ring {
  position: absolute; left: 12px;
  width: 8px; height: 8px; border-radius: 50%; background: #10b981;
  animation: ping 1.2s ease infinite;
}
.ping-core { width: 8px; height: 8px; border-radius: 50%; background: #10b981; flex-shrink: 0; }
@keyframes ping { 0%{transform:scale(1);opacity:.8} 80%{transform:scale(2.2);opacity:0} 100%{opacity:0} }

/* ── Stats (same card as activity logs) ── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}
@media(max-width:900px){ .stats-grid{ grid-template-columns: repeat(2,1fr); } }

.stat-card {
  background: white; border-radius: 16px;
  border: 1px solid #e8edf2;
  padding: 16px; position: relative; overflow: hidden;
  transition: box-shadow .2s, transform .2s;
  animation: fadeUp .4s both ease-out;
}
.stat-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.09); transform: translateY(-2px); }
.stat-icon  { font-size: 22px; margin-bottom: 10px; }
.stat-label { font-size: 10px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 4px; }
.stat-val   { font-size: 26px; font-weight: 800; color: #1e293b; }
.stat-bar   { position: absolute; bottom: 0; left: 0; height: 3px; background: var(--accent); border-radius: 0 3px 3px 0; transition: width .6s ease; }

.stat-delta {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: 11px; font-weight: 700;
  padding: 2px 8px; border-radius: 99px;
  margin-top: 6px;
}
.delta-up   { color: #16a34a; background: rgba(22,163,74,0.1);  }
.delta-down { color: #dc2626; background: rgba(220,38,38,0.1);  }
.delta-arrow { width: 7px; height: 7px; fill: currentColor; }

/* ── Panel (identical to activity logs card style) ── */
.panel {
  background: white; border-radius: 16px;
  border: 1px solid #e8edf2;
  overflow: hidden;
  animation: fadeUp .4s both ease-out;
}
.panel-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 18px;
  border-bottom: 1px solid #f1f5f9;
}
.panel-title {
  font-size: 13px; font-weight: 700;
  color: #1a4972; letter-spacing: -.01em;
}
.btn-ghost {
  padding: 5px 14px; border-radius: 99px;
  border: 1.5px solid rgba(26,73,114,0.2);
  background: rgba(26,73,114,0.05);
  color: #1a4972; font-size: 12px; font-weight: 600;
  cursor: pointer; transition: .15s;
}
.btn-ghost:hover { background: rgba(26,73,114,0.1); }

.btn-primary {
  padding: 6px 14px; border-radius: 10px;
  background: #1a4972; color: white;
  border: none; font-size: 12px; font-weight: 600;
  cursor: pointer; transition: background .15s;
}
.btn-primary:hover { background: #163d5e; }

/* ── Mid grid ── */
.mid-grid {
  display: grid;
  grid-template-columns: 1fr 260px;
  gap: 12px;
}
@media(max-width:900px){ .mid-grid{ grid-template-columns: 1fr; } }

/* ── Activity list (matches timeline card style) ── */
.activity-list { list-style: none; padding: 4px 0; }
.activity-item {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 18px;
  transition: background .12s;
  animation: fadeUp .35s both ease-out;
}
.activity-item:hover { background: #f8fafc; }

.act-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.dot--user     { background: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,.2); }
.dot--document { background: #10b981; box-shadow: 0 0 0 2px rgba(16,185,129,.2); }
.dot--security { background: #eab308; box-shadow: 0 0 0 2px rgba(234,179,8,.2);  }
.dot--case     { background: #a855f7; box-shadow: 0 0 0 2px rgba(168,85,247,.2); }
.dot--alert    { background: #ef4444; box-shadow: 0 0 0 2px rgba(239,68,68,.2);  }

.act-content { flex: 1; }
.act-text { display: block; font-size: 13px; color: #334155; }
.act-time { display: block; font-size: 11px; color: #94a3b8; margin-top: 1px; }

.act-tag {
  font-size: 10px; font-weight: 700;
  letter-spacing: .06em; text-transform: uppercase;
  padding: 3px 8px; border-radius: 99px;
}
.tag--user     { background: rgba(59,130,246,.08);  color: #1d4ed8; }
.tag--document { background: rgba(16,185,129,.08);  color: #065f46; }
.tag--security { background: rgba(234,179,8,.1);    color: #854d0e; }
.tag--case     { background: rgba(168,85,247,.08);  color: #6b21a8; }
.tag--alert    { background: rgba(239,68,68,.08);   color: #b91c1c; }

/* ── Quick Actions ── */
.actions-panel { display: flex; flex-direction: column; }
.actions-grid {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 1px; background: #f1f5f9; flex: 1;
}
.action-btn {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 8px; padding: 22px 12px;
  background: white; border: none; cursor: pointer;
  transition: background .15s;
  animation: fadeUp .35s both ease-out;
}
.action-btn:hover { background: #f8fafc; }
.action-icon-wrap {
  width: 40px; height: 40px; border-radius: 12px;
  background: rgba(26,73,114,0.06);
  border: 1px solid rgba(26,73,114,0.1);
  display: flex; align-items: center; justify-content: center;
  color: #1a4972;
  transition: background .15s, color .15s;
}
.action-btn:hover .action-icon-wrap {
  background: #1a4972; color: white;
}
.action-icon  { width: 17px; height: 17px; }
.action-label { font-size: 11px; font-weight: 600; color: #475569; }

/* ── Table ── */
.table-controls { display: flex; align-items: center; gap: 10px; }
.search-wrap { position: relative; display: flex; align-items: center; }
.search-ico {
  position: absolute; left: 11px; color: #94a3b8; pointer-events: none;
}
.search-input {
  width: 200px; padding: 8px 12px 8px 32px;
  background: #f8fafc; border: 1.5px solid #e2e8f0;
  border-radius: 12px; font-size: 13px; color: #334155;
  outline: none; transition: border-color .15s;
  font-family: 'Segoe UI', sans-serif;
}
.search-input::placeholder { color: #94a3b8; }
.search-input:focus { border-color: #1a4972; background: white; box-shadow: 0 0 0 3px rgba(26,73,114,.08); }

.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table thead th {
  padding: 9px 18px;
  font-size: 10px; font-weight: 700; letter-spacing: .08em;
  text-transform: uppercase; color: #94a3b8;
  text-align: left; background: #f8fafc;
  border-bottom: 1px solid #e8edf2;
}
.table-row { border-bottom: 1px solid #f1f5f9; transition: background .12s; }
.table-row:last-child { border-bottom: none; }
.table-row:hover { background: #f8fafc; }
.data-table td { padding: 11px 18px; font-size: 13px; }

.user-cell { display: flex; align-items: center; gap: 10px; }
.user-avatar {
  width: 32px; height: 32px; border-radius: 10px;
  background: #1a4972; color: white;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700; flex-shrink: 0;
}
.user-name  { font-size: 13px; font-weight: 600; color: #1e293b; }
.user-email { font-size: 11px; color: #94a3b8; margin-top: 1px; }

.role-chip {
  font-size: 10px; font-weight: 700;
  letter-spacing: .05em; text-transform: uppercase;
  padding: 3px 9px; border-radius: 99px;
}
.role--admin     { color: #1a4972; background: rgba(26,73,114,.1);  }
.role--lawyer    { color: #1d4ed8; background: rgba(59,130,246,.1); }
.role--secretary { color: #065f46; background: rgba(16,185,129,.1); }
.role--intern    { color: #6b21a8; background: rgba(168,85,247,.1); }

.status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 700; text-transform: capitalize;
  padding: 3px 10px; border-radius: 99px;
}
.status-badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; }
.status--active            { color: #065f46; background: rgba(16,185,129,.1); }
.status--active::before    { background: #10b981; box-shadow: 0 0 0 2px rgba(16,185,129,.2); }
.status--inactive          { color: #94a3b8; background: #f1f5f9; }
.status--inactive::before  { background: #cbd5e1; }

.td-muted { color: #94a3b8; font-size: 12px; }
.td-empty { text-align: center; color: #94a3b8; padding: 32px; font-size: 13px; }

.row-actions { display: flex; gap: 6px; }
.row-btn {
  font-size: 11px; font-weight: 600;
  padding: 4px 12px; border-radius: 8px;
  border: 1.5px solid #e2e8f0;
  background: white; color: #475569;
  cursor: pointer; transition: .15s;
}
.row-btn:hover { border-color: #1a4972; color: #1a4972; }
.row-btn--danger { color: #dc2626; }
.row-btn--danger:hover { background: rgba(220,38,38,.06); border-color: rgba(220,38,38,.3); color: #dc2626; }

/* ── Animation ── */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── Responsive ── */
@media(max-width:640px){
  .dash-topbar { flex-direction: column; gap: 12px; }
  .stats-grid  { grid-template-columns: 1fr; }
  .mid-grid    { grid-template-columns: 1fr; }
}
</style>