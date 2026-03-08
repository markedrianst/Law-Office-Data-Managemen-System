<template>
  <Transition name="modal">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="$emit('close')">

      <div v-if="viewCase" class="relative bg-white w-full max-w-6xl max-h-[92vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden">

        <!-- ══ HEADER ══════════════════════════════════════════════════════ -->
        <div class="flex items-center justify-between px-8 py-5 border-b border-gray-100 bg-white">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center shadow-sm">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <div>
              <h1 class="text-xl font-bold text-gray-900 leading-tight">Case Profile</h1>
              <p class="text-xs text-gray-400 font-medium">{{ viewCase.case_code || 'No Code' }} · {{ viewCase.case_no || 'No Number' }}</p>
            </div>
          </div>
          <button @click="$emit('close')" class="p-2 hover:bg-gray-100 rounded-xl transition text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- ══ BODY ════════════════════════════════════════════════════════ -->
        <div class="flex-1 overflow-y-auto px-8 py-6 space-y-5 bg-gray-50">

          <!-- ── Row 1: Case Information + Folder Status ─────────────────── -->
          <div class="grid grid-cols-3 gap-5">

            <!-- Case Information -->
            <div class="col-span-2 bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
              <div class="flex items-center gap-2 mb-5">
                <div class="w-1 h-5 bg-blue-600 rounded-full"></div>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Case Information</h3>
              </div>
              <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                <!-- Row 1: Code | Number -->
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Case Code</p>
                  <p class="text-sm font-bold text-gray-900">{{ viewCase.case_code || '—' }}</p>
                </div>
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Case Number</p>
                  <p class="text-sm font-bold text-gray-900">{{ viewCase.case_no || '—' }}</p>
                </div>
                <!-- Row 2: Title (full) -->
                <div class="col-span-1">
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Case Title</p>
                  <p class="text-sm font-bold text-gray-900">{{ viewCase.title || '—' }}</p>
                </div>
                <!-- Row 3: Client | Docket No -->
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Client</p>
                  <p class="text-sm font-bold text-gray-900">{{ viewCase.client || '—' }}</p>
                </div>
                <!-- Row 4: Lawyer | Clerk -->
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Assigned Lawyer</p>
                  <p class="text-sm font-bold text-gray-900">{{ viewCase.lawyer ? 'Atty. ' + viewCase.lawyer : '—' }}</p>
                </div>
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Assigned Clerk</p>
                  <p class="text-sm font-bold text-gray-900">{{ viewCase.clerk || '—' }}</p>
                </div>
                <!-- Row 5: Category | Court -->
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Category</p>
                  <p class="text-sm font-bold text-gray-900">{{ viewCase.category || '—' }}</p>
                </div>
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Court / Office</p>
                  <p class="text-sm font-bold text-gray-900">{{ viewCase.court_or_office || '—' }}</p>
                </div>
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Stage</p>
                  <div class="relative inline-block">
                    <select
                      :value="viewCase.current_stage_id ?? viewCase.stage"
                      @change="onStageChange($event.target.value)"
                      :disabled="stageUpdating"
                      class="appearance-none pl-3 pr-8 py-1.5 text-sm font-semibold text-gray-700 border border-gray-200 rounded-lg bg-white hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 cursor-pointer transition disabled:opacity-60 disabled:cursor-wait">
                      <option v-if="!activeStages.length" :value="viewCase.current_stage_id ?? viewCase.stage">{{ viewCase.stage || '—' }}</option>
                      <option v-for="stage in activeStages" :key="stage.id" :value="stage.id">{{ stage.name }}</option>
                    </select>
                    <svg v-if="!stageUpdating" class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"/>
                    </svg>
                    <svg v-else class="animate-spin absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-500" viewBox="0 0 24 24" fill="none">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <!-- Folder Status -->
            <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm flex flex-col">
              <div class="flex items-center gap-2 mb-5">
                <div class="w-1 h-5 bg-emerald-500 rounded-full"></div>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Folder Status</h3>
              </div>
              <div class="space-y-4 flex-1">

                <!-- Docket Number -->
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Docket Number</p>
                  <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 text-gray-700 border border-gray-200 text-sm font-bold rounded-lg">
                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    {{ viewCase.docket_no || '—' }}
                  </span>
                </div>

                <!-- Case status -->
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Current Status</p>
                  <span :class="statusBadgeClass(viewCase.case_status)" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-bold rounded-lg">
                    <span class="w-2 h-2 rounded-full bg-white/70"></span>
                    {{ formatStatus(viewCase.case_status) }}
                  </span>
                </div>

                <!-- Current Holder — only when folder is OUT -->
                <div v-if="viewCase.is_out">
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Current Holder</p>
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-[#1a4972] to-[#2a5a8c] flex items-center justify-center text-xs font-bold text-white shadow-sm">
                      {{ getInitials(viewCase.clerk) }}
                    </div>
                    <p class="text-sm font-bold text-gray-900">{{ viewCase.clerk || '—' }}</p>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <!-- ── Case Checklist ──────────────────────────────────────────── -->
          <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">

            <!-- Checklist Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-white">
              <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-violet-500 rounded-full"></div>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Case Checklist</h3>
                <span v-if="internalChecklist.length" class="px-2 py-0.5 text-xs font-bold bg-violet-100 text-violet-700 rounded-full">
                  {{ internalChecklist.length }}
                </span>
              </div>
              <div class="flex items-center gap-3">
                <!-- Progress bar -->
                <div v-if="internalChecklist.length" class="flex items-center gap-2">
                  <div class="w-32 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full transition-all duration-500"
                      :style="{ width: `${donePercent}%` }"></div>
                  </div>
                  <span class="text-xs font-bold text-gray-500">{{ donePercent }}%</span>
                </div>
                <button @click="openTaskModal(null, 'add')"
                  class="flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-sm font-semibold rounded-xl transition shadow-sm">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                  </svg>
                  Add Task
                </button>
              </div>
            </div>

            <!-- Filter Bar -->
            <div class="px-6 py-3 bg-gray-50/80 border-b border-gray-100 flex flex-wrap gap-2.5 items-center">
              <div class="relative flex-1 min-w-[180px] max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" v-model="searchQuery" placeholder="Search tasks…"
                  class="w-full pl-9 pr-8 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-300 bg-white transition"/>
                <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>
              <select v-model="statusFilter" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white min-w-[120px] focus:ring-2 focus:ring-blue-100 focus:border-blue-300">
                <option value="all">All Status</option>
                <option value="todo">To-do</option>
                <option value="in-progress">In Progress</option>
                <option value="done">Done</option>
              </select>
              <input v-model="clerkFilter" type="text" placeholder="Filter by assignee…"
                class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white w-40 focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition"/>
              <select v-model="dateFilter" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white min-w-[130px] focus:ring-2 focus:ring-blue-100 focus:border-blue-300">
                <option value="all">All Dates</option>
                <option value="overdue">Overdue</option>
                <option value="today">Due Today</option>
                <option value="week">Due This Week</option>
                <option value="month">Due This Month</option>
                <option value="no-date">No Due Date</option>
              </select>
              <button v-if="hasActiveFilters" @click="clearFilters"
                class="px-3 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50 rounded-lg transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                Clear
              </button>
              <span class="ml-auto text-xs font-semibold text-gray-400 bg-white border border-gray-200 px-2.5 py-1.5 rounded-lg">
                {{ filteredChecklist.length }} task{{ filteredChecklist.length !== 1 ? 's' : '' }}
              </span>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
              <div v-if="checklistLoading_" class="py-16 flex items-center justify-center gap-3 text-gray-400">
                <svg class="animate-spin w-5 h-5" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-sm">Loading tasks…</span>
              </div>

              <div v-else-if="!filteredChecklist.length" class="py-14 text-center">
                <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
                  <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                  </svg>
                </div>
                <p class="text-sm font-semibold text-gray-400">{{ internalChecklist.length ? 'No matching tasks' : 'No tasks added yet' }}</p>
                <button v-if="internalChecklist.length" @click="clearFilters" class="mt-1 text-xs text-blue-500 hover:text-blue-700 font-medium">Clear filters</button>
              </div>

              <table v-else class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                  <tr>
                    <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 w-10"></th>
                    <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Task</th>
                    <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Status</th>
                    <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 whitespace-nowrap">Due Date</th>
                    <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 whitespace-nowrap">Assigned Clerk</th>
                    <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Notes</th>
                    <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="(task, idx) in paginatedChecklist" :key="task.id"
                    class="hover:bg-blue-50/20 transition-colors group"
                    :class="[task.status === 'done' ? 'opacity-60' : '']">
                    <td class="px-5 py-3.5">
                      <input type="checkbox" :checked="task.status === 'done'" @change="toggleDone(task)"
                        class="w-4.5 h-4.5 rounded border-2 border-gray-300 text-blue-600 cursor-pointer accent-blue-600"/>
                    </td>
                    <!-- task name — uses `task` field from schema -->
                    <td class="px-5 py-3.5 min-w-[180px] max-w-[260px]">
                      <p class="text-sm font-semibold truncate"
                        :class="task.status === 'done' ? 'line-through text-gray-400' : 'text-gray-800'"
                        :title="task.task">
                        {{ task.task || '—' }}
                      </p>
                    </td>
                    <!-- status -->
                    <td class="px-5 py-3.5">
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold rounded-full whitespace-nowrap"
                        :class="taskStatusClass(task.status)">
                        <span class="w-1.5 h-1.5 rounded-full" :class="taskStatusDot(task.status)"></span>
                        {{ taskStatusLabel(task.status) }}
                      </span>
                    </td>
                    <!-- due_date -->
                    <td class="px-5 py-3.5">
                      <div class="flex items-center gap-1.5">
                        <span class="text-sm text-gray-600 whitespace-nowrap">{{ formatDate(task.due_date) }}</span>
                        <span v-if="isOverdue(task.due_date) && task.status !== 'done'"
                          class="px-1.5 py-0.5 text-[9px] font-bold bg-red-100 text-red-600 rounded-full uppercase tracking-wide">Overdue</span>
                      </div>
                    </td>
                    <!-- assigned_to — plain string in schema -->
                    <td class="px-5 py-3.5">
                      <div v-if="task.assigned_to" class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0 bg-gradient-to-br from-[#1a4972] to-[#2a5a8c] shadow-sm">
                          {{ getInitials(task.assigned_to) }}
                        </div>
                        <span class="text-sm text-gray-700 whitespace-nowrap font-medium">{{ task.assigned_to }}</span>
                      </div>
                      <span v-else class="text-sm text-gray-300 italic">—</span>
                    </td>
                    <td class="px-5 py-3.5 max-w-[180px]">
                      <span class="text-sm text-gray-400 italic truncate block" :title="task.notes || ''">{{ task.notes || '—' }}</span>
                    </td>
                    <td class="px-5 py-3.5">
                      <button v-if="task.status !== 'done'" @click="openTaskModal(task, 'edit')"
                        class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-blue-600 hover:bg-blue-50 border border-blue-200 transition">
                        Edit
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                      </button>
                      <button v-else @click="openTaskModal(task, 'view')"
                        class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-gray-500 hover:bg-gray-100 border border-gray-200 transition">
                        View
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Checklist Footer: centered pagination + completion -->
            <div v-if="internalChecklist.length" class="px-6 py-4 bg-gray-50/80 border-t border-gray-100">
              <!-- Completion row -->
              <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-gray-400 font-medium">
                  Showing {{ (checklistPage - 1) * PAGE_SIZE + 1 }}–{{ Math.min(checklistPage * PAGE_SIZE, filteredChecklist.length) }} of {{ filteredChecklist.length }} tasks
                </span>
                <span class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                  {{ internalChecklist.filter(t => t.status === 'done').length }} of {{ internalChecklist.length }} completed
                </span>
              </div>
              <!-- Centered pagination -->
              <div class="flex items-center justify-center gap-1">
                <button @click="checklistPage = 1" :disabled="checklistPage === 1"
                  class="w-8 h-8 flex items-center justify-center rounded-lg border text-xs font-bold transition"
                  :class="checklistPage === 1 ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">
                  «
                </button>
                <button @click="checklistPage--" :disabled="checklistPage === 1"
                  class="w-8 h-8 flex items-center justify-center rounded-lg border transition"
                  :class="checklistPage === 1 ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button v-for="page in totalChecklistPages" :key="page"
                  @click="checklistPage = page"
                  class="w-8 h-8 flex items-center justify-center rounded-lg border text-sm font-bold transition"
                  :class="checklistPage === page
                    ? 'bg-blue-600 border-blue-600 text-white shadow-sm'
                    : 'border-gray-200 text-gray-600 hover:bg-gray-100'">
                  {{ page }}
                </button>
                <button @click="checklistPage++" :disabled="checklistPage === totalChecklistPages"
                  class="w-8 h-8 flex items-center justify-center rounded-lg border transition"
                  :class="checklistPage === totalChecklistPages ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button @click="checklistPage = totalChecklistPages" :disabled="checklistPage === totalChecklistPages"
                  class="w-8 h-8 flex items-center justify-center rounded-lg border text-xs font-bold transition"
                  :class="checklistPage === totalChecklistPages ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">
                  »
                </button>
              </div>
            </div>
          </div>

          <!-- ══ TABS ════════════════════════════════════════════════════════ -->
          <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <!-- Tab bar -->
            <div class="flex border-b border-gray-100 px-2 pt-1">
              <button v-for="tab in tabs" :key="tab"
                @click="activeTab = tab"
                class="px-5 py-3.5 text-sm font-semibold border-b-2 transition -mb-px"
                :class="activeTab === tab
                  ? 'text-blue-600 border-blue-600'
                  : 'text-gray-400 border-transparent hover:text-gray-700 hover:border-gray-300'">
                {{ tab }}
              </button>
            </div>

            <!-- ── Folder Tracker panel ──────────────────────────────────── -->
            <div v-if="activeTab === 'Folder Tracker'">
              <!-- Section label + action buttons -->
              <div class="px-6 pt-5 pb-3">
                <h4 class="text-sm font-bold text-gray-800 mb-3">IN / OUT Folder Tracker</h4>
                <div class="flex gap-2 items-center">
                  <!-- Release (OUT) -->
                  <button @click="openFolderModal('out')"
                    class="px-4 py-1.5 bg-orange-500 hover:bg-orange-600 active:scale-95 text-white text-sm font-semibold rounded-md transition shadow-sm">
                    Release Folder (OUT)
                  </button>
                  <!-- Receive (IN) with dropdown -->
                  <div class="relative" ref="inDropdownRef">
                    <div class="flex">
                      <button @click="openFolderModal('in')"
                        class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-sm font-semibold rounded-l-md transition shadow-sm">
                        Receive Folder (IN)
                      </button>
                      <button @click="inDropdownOpen = !inDropdownOpen"
                        class="px-2 py-1.5 bg-emerald-600 hover:bg-emerald-700 border-l border-emerald-500 text-white rounded-r-md transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"/>
                        </svg>
                      </button>
                    </div>
                    <!-- Dropdown menu -->
                    <div v-if="inDropdownOpen"
                      class="absolute left-0 top-full mt-1 w-52 bg-white border border-gray-200 rounded-xl shadow-lg z-10 overflow-hidden">
                      <button @click="openFolderModal('in'); inDropdownOpen = false"
                        class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Receive from Court
                      </button>
                      <button @click="openFolderModal('in'); inDropdownOpen = false"
                        class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Receive from Client
                      </button>
                      <button @click="openFolderModal('in'); inDropdownOpen = false"
                        class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Receive from Filing
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Table -->
              <div class="overflow-x-auto">
                <!-- Folder tracker skeleton -->
                <div v-if="folderLoading_" class="py-10 flex flex-col gap-3 px-6">
                  <div v-for="n in 3" :key="n" class="flex gap-4 items-center animate-pulse">
                    <div class="h-4 bg-gray-100 rounded w-24"></div>
                    <div class="h-5 bg-gray-100 rounded w-10"></div>
                    <div class="h-4 bg-gray-100 rounded w-20"></div>
                    <div class="h-4 bg-gray-100 rounded flex-1"></div>
                    <div class="h-4 bg-gray-100 rounded w-28"></div>
                  </div>
                </div>
                <table v-else class="w-full">
                  <thead class="border-y border-gray-100">
                    <tr class="bg-white">
                      <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500">Date</th>
                      <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500">Type</th>
                      <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500">Approval Status</th>
                      <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500">From / To</th>
                      <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500">Purpose</th>
                      <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500">Handled By</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(record, idx) in paginatedFolderHistory" :key="record.id"
                      class="border-b border-gray-50 hover:bg-gray-50/60 transition"
                      :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50/30'">
                      <td class="px-6 py-3 text-sm text-gray-700">{{ formatDate(record.date) }}</td>
                      <td class="px-6 py-3">
                        <span :class="record.type === 'OUT'
                            ? 'bg-orange-500 text-white'
                            : 'bg-emerald-500 text-white'"
                          class="inline-block px-2.5 py-0.5 text-xs font-bold rounded">
                          {{ record.type }}
                        </span>
                      </td>
                       <td>
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border"
                          :class="record.approval_status === 'PENDING'
                            ? 'border-amber-400 text-amber-400'
                            : record.approval_status === 'APPROVED'
                              ? 'border-emerald-400 text-emerald-400'
                              : 'border-rose-400 text-rose-400'">
                          <span v-if="record.approval_status === 'PENDING'" class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                          {{ record.approval_status }}
                        </span>
                        <p v-if="record.approval_status !== 'PENDING' && record.approver?.full_name"
                          class="text-[10px] text-slate-400 mt-0.5">
                          by {{ record.approver.full_name }}
                        </p>
                      </td>
                      <td class="px-6 py-3 text-sm text-gray-700">
                        <span class="text-gray-400 mr-1">{{ record.type === 'OUT' ? 'From:' : 'To:' }}</span>{{ record.from_to || '—' }}
                      </td>
                      <td class="px-6 py-3 text-sm text-gray-700">{{ record.purpose || '—' }}</td>
                      <td class="px-6 py-3 text-sm font-semibold text-gray-800">{{ record.handled_by || '—' }}</td>
                    </tr>
                    <tr v-if="!internalFolderHistory.length">
                      <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-400">
                        No folder movements recorded
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <!-- Folder Tracker Pagination -->
              <div v-if="internalFolderHistory.length > TRACKER_SIZE" class="px-6 py-3 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-400">
                  {{ (folderTrackerPage - 1) * TRACKER_SIZE + 1 }}–{{ Math.min(folderTrackerPage * TRACKER_SIZE, internalFolderHistory.length) }} of {{ internalFolderHistory.length }}
                </span>
                <div class="flex items-center gap-1">
                  <button @click="folderTrackerPage = 1" :disabled="folderTrackerPage === 1"
                    class="px-2 py-1 text-xs border rounded-lg"
                    :class="folderTrackerPage === 1 ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">«</button>
                  <button @click="folderTrackerPage--" :disabled="folderTrackerPage === 1"
                    class="px-2 py-1 text-xs border rounded-lg"
                    :class="folderTrackerPage === 1 ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">‹</button>
                  <button v-for="page in totalFolderTrackerPages" :key="page"
                    @click="folderTrackerPage = page"
                    class="px-2.5 py-1 text-xs border rounded-lg"
                    :class="folderTrackerPage === page ? 'bg-blue-500 text-white border-blue-500' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">{{ page }}</button>
                  <button @click="folderTrackerPage++" :disabled="folderTrackerPage === totalFolderTrackerPages"
                    class="px-2 py-1 text-xs border rounded-lg"
                    :class="folderTrackerPage === totalFolderTrackerPages ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">›</button>
                  <button @click="folderTrackerPage = totalFolderTrackerPages" :disabled="folderTrackerPage === totalFolderTrackerPages"
                    class="px-2 py-1 text-xs border rounded-lg"
                    :class="folderTrackerPage === totalFolderTrackerPages ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">»</button>
                </div>
              </div>
            </div>

            <!-- ── Checklist Tracker panel ───────────────────────────────── -->
            <div v-if="activeTab === 'Checklist Tracker'">
              <!-- Section label + action buttons -->
              <div class="px-6 pt-5 pb-3">
                <h4 class="text-sm font-bold text-gray-800 mb-3">IN / OUT Checklist Tracker</h4>
                <div class="flex gap-2 items-center">
                  <!-- Release (OUT) -->
                  <button @click="openChecklistTracker('out')"
                    class="px-4 py-1.5 bg-orange-500 hover:bg-orange-600 active:scale-95 text-white text-sm font-semibold rounded-md transition shadow-sm">
                    Release Checklist (OUT)
                  </button>
                  <!-- Receive (IN) split button -->
                  <div class="relative">
                    <div class="flex">
                      <button @click="openChecklistTracker('in')"
                        class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-sm font-semibold rounded-l-md transition shadow-sm">
                        Receive Checklist (IN)
                      </button>
                      <button @click="inChecklistDropdownOpen = !inChecklistDropdownOpen"
                        class="px-2 py-1.5 bg-emerald-600 hover:bg-emerald-700 border-l border-emerald-500 text-white rounded-r-md transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"/>
                        </svg>
                      </button>
                    </div>
                    <div v-if="inChecklistDropdownOpen"
                      class="absolute left-0 top-full mt-1 w-52 bg-white border border-gray-200 rounded-xl shadow-lg z-10 overflow-hidden">
                      <button @click="openChecklistTracker('in'); inChecklistDropdownOpen = false"
                        class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>From Court
                      </button>
                      <button @click="openChecklistTracker('in'); inChecklistDropdownOpen = false"
                        class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>From Client
                      </button>
                      <button @click="openChecklistTracker('in'); inChecklistDropdownOpen = false"
                        class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>From Filing
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Movement history table — same style as Folder Tracker -->
              <div class="overflow-x-auto">
                <!-- Checklist tracker skeleton -->
                <div v-if="checklistTrackerLoading_" class="py-10 flex flex-col gap-3 px-6">
                  <div v-for="n in 3" :key="n" class="flex gap-4 items-center animate-pulse">
                    <div class="h-4 bg-gray-100 rounded w-24"></div>
                    <div class="h-5 bg-gray-100 rounded w-10"></div>
                    <div class="h-4 bg-gray-100 rounded w-20"></div>
                    <div class="h-4 bg-gray-100 rounded w-32"></div>
                    <div class="h-4 bg-gray-100 rounded flex-1"></div>
                  </div>
                </div>
                <table v-else class="w-full">
                  <thead class="border-y border-gray-100">
                    <tr class="bg-white">
                      <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500">Date</th>
                      <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500">Type</th>
                      <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500">Approval</th>
                      <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500">Document / Task</th>
                      <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500">From / To</th>
                      <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500">Purpose</th>
                      <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500">Handled By</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(record, idx) in paginatedChecklistHistory" :key="'ch-' + record.id"
                      class="border-b border-gray-50 hover:bg-gray-50/60 transition"
                      :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50/30'">
                      <td class="px-6 py-3 text-sm text-gray-700">{{ formatDate(record.date) }}</td>
                      <td class="px-6 py-3">
                        <span :class="record.type === 'OUT' ? 'bg-orange-500 text-white' : 'bg-emerald-500 text-white'"
                          class="inline-block px-2.5 py-0.5 text-xs font-bold rounded">
                          {{ record.type }}
                        </span>
                      </td>
                      <td class="px-6 py-3 text-sm text-gray-700 font-medium">
                        {{ record.approval_status === 'APPROVED' ? 'Approved' : record.approval_status === 'REJECTED' ? 'Rejected' : 'Pending' }}
                      </td>
                      <td class="px-6 py-3 text-sm text-gray-700 font-medium">
                        {{ record.task_name || record.checklist?.task || 'All / General' }}
                      </td>
                      <td class="px-6 py-3 text-sm text-gray-700">
                        <span class="text-gray-400 mr-1">{{ record.type === 'OUT' ? 'To:' : 'From:' }}</span>{{ record.from_to || '—' }}
                      </td>
                      <td class="px-6 py-3 text-sm text-gray-700">{{ record.purpose || '—' }}</td>
                      <td class="px-6 py-3 text-sm font-semibold text-gray-800">{{ record.handled_by || '—' }}</td>
                    </tr>
                    <tr v-if="!internalChecklistHistory.length">
                      <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">
                        No checklist movements recorded
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <!-- Checklist Tracker Pagination -->
              <div v-if="internalChecklistHistory.length > TRACKER_SIZE" class="px-6 py-3 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-400">
                  {{ (checklistTrackerPage - 1) * TRACKER_SIZE + 1 }}–{{ Math.min(checklistTrackerPage * TRACKER_SIZE, internalChecklistHistory.length) }} of {{ internalChecklistHistory.length }}
                </span>
                <div class="flex items-center gap-1">
                  <button @click="checklistTrackerPage = 1" :disabled="checklistTrackerPage === 1"
                    class="px-2 py-1 text-xs border rounded-lg"
                    :class="checklistTrackerPage === 1 ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">«</button>
                  <button @click="checklistTrackerPage--" :disabled="checklistTrackerPage === 1"
                    class="px-2 py-1 text-xs border rounded-lg"
                    :class="checklistTrackerPage === 1 ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">‹</button>
                  <button v-for="page in totalChecklistTrackerPages" :key="page"
                    @click="checklistTrackerPage = page"
                    class="px-2.5 py-1 text-xs border rounded-lg"
                    :class="checklistTrackerPage === page ? 'bg-blue-500 text-white border-blue-500' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">{{ page }}</button>
                  <button @click="checklistTrackerPage++" :disabled="checklistTrackerPage === totalChecklistTrackerPages"
                    class="px-2 py-1 text-xs border rounded-lg"
                    :class="checklistTrackerPage === totalChecklistTrackerPages ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">›</button>
                  <button @click="checklistTrackerPage = totalChecklistTrackerPages" :disabled="checklistTrackerPage === totalChecklistTrackerPages"
                    class="px-2 py-1 text-xs border rounded-lg"
                    :class="checklistTrackerPage === totalChecklistTrackerPages ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">»</button>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </Transition>

  <!-- ══ ALERT MODAL ══════════════════════════════════════════════════════ -->
  <Transition name="toast">
    <div v-if="toast.show"
      class="fixed inset-0 z-[110] flex items-center justify-center p-4"
      style="background: rgba(0,0,0,0.35); backdrop-filter: blur(2px);"
      @click.self="toast.show = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        <!-- Coloured top bar -->
        <div :class="toast.type === 'error' ? 'bg-gradient-to-r from-red-500 to-red-600' : 'bg-gradient-to-r from-blue-500 to-blue-600'"
          class="px-6 py-5 flex items-center gap-4">
          <!-- Icon circle -->
          <div class="w-11 h-11 rounded-2xl bg-white/20 flex items-center justify-center flex-shrink-0">
            <svg v-if="toast.type === 'error'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <svg v-else class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="flex-1">
            <p class="text-white font-bold text-base leading-tight">
              {{ toast.type === 'error' ? 'Action Not Allowed' : 'Nothing to Do' }}
            </p>
            <p class="text-white/70 text-xs mt-0.5">Folder tracker guard</p>
          </div>
          <button @click="toast.show = false" class="text-white/60 hover:text-white transition p-1 rounded-lg hover:bg-white/10">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <!-- Body -->
        <div class="px-6 py-5">
          <p class="text-sm text-gray-700 leading-relaxed">{{ toast.message }}</p>
        </div>
        <!-- Footer -->
        <div class="px-6 pb-5">
          <button @click="toast.show = false"
            :class="toast.type === 'error' ? 'bg-red-500 hover:bg-red-600' : 'bg-blue-500 hover:bg-blue-600'"
            class="w-full py-2.5 text-sm font-bold text-white rounded-xl transition active:scale-95 shadow-sm">
            Got it
          </button>
        </div>
      </div>
    </div>
  </Transition>

  <!-- ══ TASK MODAL ══════════════════════════════════════════════════════════ -->
  <CaseTaskModal
    :show="tm.show"
    :mode="tm.mode"
    :task="tm.task"
    :clerks="clerks"
    @close="tm.show = false"
    @save="onTaskSave"
    @switch-to-edit="tm.mode = 'edit'"
  />

  <!-- ══ FOLDER MOVEMENT MODAL ══════════════════════════════════════════════ -->
  <Transition name="modal">
    <div v-if="fm.show" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="fm.show = false">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <div :class="fm.type === 'out' ? 'bg-orange-500' : 'bg-emerald-600'" class="w-8 h-8 rounded-xl flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="fm.type === 'out'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
            </div>
            <div>
              <h2 class="text-base font-bold text-gray-900">{{ fm.type === 'out' ? 'Release Folder (OUT)' : 'Receive Folder (IN)' }}</h2>
              <p class="text-xs text-gray-400">Record folder movement</p>
            </div>
          </div>
          <button @click="fm.show = false" class="p-2 hover:bg-gray-100 rounded-xl text-gray-400 hover:text-gray-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <!-- Form -->
        <div class="px-6 py-5 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                {{ fm.type === 'out' ? 'From (Creator)' : 'To (Creator)' }}
              </label>
              <input v-model="fm.form.person" type="text" disabled
                placeholder="Creator name…"
                class="w-full px-3 py-2 border border-gray-100 rounded-xl text-sm bg-gray-50 text-gray-500 cursor-not-allowed"/>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Date <span class="text-red-400">*</span></label>
              <input v-model="fm.form.date" disabled type="date"
                class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition"/>
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Purpose / Remarks</label>
            <input v-model="fm.form.purpose" type="text" placeholder="e.g. For Review, For Submission…"
              class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition"/>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Handled By</label>
            <input v-model="fm.form.handledBy" type="text" placeholder="Staff name…"
              class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition"/>
          </div>
        </div>
        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50">
          <button @click="fm.show = false"
            class="px-5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition border border-gray-200">Cancel</button>
          <button @click="submitFolderModal"
            :class="fm.type === 'out' ? 'bg-orange-500 hover:bg-orange-600' : 'bg-emerald-600 hover:bg-emerald-700'"
            class="px-5 py-2 text-sm font-bold text-white rounded-xl transition shadow-sm active:scale-95">
            Confirm {{ fm.type === 'out' ? 'Release' : 'Receive' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
  <Transition name="modal">
    <div v-if="ctm.show" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="ctm.show = false">
      <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <div :class="ctm.type === 'out' ? 'bg-orange-500' : 'bg-emerald-600'" class="w-8 h-8 rounded-xl flex items-center justify-center shadow-sm">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="ctm.type === 'out'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
            </div>
            <div>
              <h2 class="text-base font-bold text-gray-900">
                {{ ctm.type === 'out' ? 'Release Checklist (OUT)' : 'Receive Checklist (IN)' }}
              </h2>
              <p class="text-xs text-gray-400">{{ ctm.task ? (ctm.task.task ?? ctm.task.document_type) : 'All checklist items' }}</p>
            </div>
          </div>
          <button @click="ctm.show = false" class="p-2 hover:bg-gray-100 rounded-xl text-gray-400 hover:text-gray-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Form -->
        <div class="px-6 py-5 space-y-4">
            <!-- Checklist item selector -->
        <div>
          <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Document / Task</label>
          <select v-model="ctm.form.taskId" @change="onCtmTaskChange"
            class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition bg-white">
            <option value="">All / General</option>
            <!-- OUT: only items currently IN (is_out = false) -->
            <template v-if="ctm.type === 'out'">
              <option
                v-for="task in internalChecklist.filter(t => !t.is_out)"
                :key="task.id"
                :value="task.id"
              >
                {{ task.task ?? '—' }}
              </option>
            </template>
            <!-- IN: only items currently OUT (is_out = true) -->
            <template v-else-if="ctm.type === 'in'">
              <option
                v-for="task in internalChecklist.filter(t => !!t.is_out)"
                :key="task.id"
                :value="task.id"
              >
                {{ task.task ?? '—' }}
              </option>
            </template>
          </select>
        </div>
          <!-- Form fields -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                {{ ctm.type === 'out' ? 'From (Creator)' : 'To (Creator)' }}
              </label>
              <input v-model="ctm.form.person" type="text" disabled
                placeholder="Creator name…"
                class="w-full px-3 py-2 border border-gray-100 rounded-xl text-sm bg-gray-50 text-gray-500 cursor-not-allowed"/>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Date <span class="text-red-400">*</span></label>
              <input v-model="ctm.form.date" disabled type="date"
                class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition"/>
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Purpose / Remarks</label>
            <input v-model="ctm.form.purpose" type="text" placeholder="e.g. For Review, For Submission…"
              class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition"/>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Handled By</label>
            <input v-model="ctm.form.handledBy" disabled type="text" placeholder="Staff name…"  
              class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition"/>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50">
          <button @click="ctm.show = false"
            class="px-5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition border border-gray-200">
            Cancel
          </button>
          <button @click="submitChecklistTracker"
            :class="ctm.type === 'out' ? 'bg-orange-500 hover:bg-orange-600' : 'bg-emerald-600 hover:bg-emerald-700'"
            class="px-5 py-2 text-sm font-bold text-white rounded-xl transition shadow-sm active:scale-95">
            Confirm {{ ctm.type === 'out' ? 'Release' : 'Receive' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>


<script setup>
import { ref, computed, reactive, watch } from 'vue';
import CaseTaskModal from './CaseTaskModal.vue';
import * as CaseService from '@/services/caseService';

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
  show:         { type: Boolean, default: false },
  viewCase:     { type: Object,  default: null  },
  activeStages: { type: Array,   default: () => [] },
  clerks:       { type: Array,   default: () => [] },
  currentUser:  { type: Object,  default: null  },
  // kept for backwards-compat but no longer used — modal fetches its own data
  stageHistory:        { type: Array,   default: () => [] },
  stageHistoryLoading: { type: Boolean, default: false },
  checklist:           { type: Array,   default: () => [] },
  checklistLoading:    { type: Boolean, default: false },
  folderHistory:       { type: Array,   default: () => [] },
  checklistHistory:    { type: Array,   default: () => [] },
});

const emit = defineEmits(['close', 'edit', 'add-task', 'update-task', 'delete-task', 'update-stage', 'checklist-movement', 'folder-movement', 'update:viewCase']);

// ── Internal data (self-fetched) ───────────────────────────────────────────
const internalChecklist        = ref([]);
const internalFolderHistory    = ref([]);
const internalChecklistHistory = ref([]);

const checklistLoading_        = ref(false);
const folderLoading_           = ref(false);
const checklistTrackerLoading_ = ref(false);

const unwrap = (res) => res?.data?.data ?? res?.data ?? res ?? [];

const fetchChecklist = async (caseId) => {
  checklistLoading_.value = true;
  try {
    internalChecklist.value = unwrap(await CaseService.getChecklist(caseId)) || [];
  } catch (e) {
    console.error('CaseViewModal fetchChecklist:', e);
    internalChecklist.value = [];
  } finally {
    checklistLoading_.value = false;
  }
};

const fetchFolderTracker = async (caseId) => {
  folderLoading_.value = true;
  try {
    internalFolderHistory.value = unwrap(await CaseService.getFolderTracker(caseId)) || [];
  } catch (e) {
    console.error('CaseViewModal fetchFolderTracker:', e);
    internalFolderHistory.value = [];
  } finally {
    folderLoading_.value = false;
  }
};

const fetchChecklistTracker = async (caseId) => {
  checklistTrackerLoading_.value = true;
  try {
    internalChecklistHistory.value = unwrap(await CaseService.getChecklistTracker(caseId)) || [];
  } catch (e) {
    console.error('CaseViewModal fetchChecklistTracker:', e);
    internalChecklistHistory.value = [];
  } finally {
    checklistTrackerLoading_.value = false;
  }
};

// Kick off all three fetches in parallel the moment the modal opens
watch(
  () => props.show,
  (visible) => {
    if (visible && props.viewCase?.id) {
      const id = props.viewCase.id;
      // Fire all three simultaneously — each section renders as its data arrives
      fetchChecklist(id);
      fetchFolderTracker(id);
      fetchChecklistTracker(id);
    }
    if (!visible) {
      // Reset on close
      internalChecklist.value        = [];
      internalFolderHistory.value    = [];
      internalChecklistHistory.value = [];
      tm.show = false; ctm.show = false; fm.show = false;
      closeDropdowns(); clearFilters();
      folderTrackerPage.value = 1; checklistTrackerPage.value = 1;
    }
  },
  { immediate: false }
);

// Also re-fetch when viewCase changes while modal is open (e.g. navigating cases)
watch(
  () => props.viewCase?.id,
  (newId) => {
    if (props.show && newId) {
      fetchChecklist(newId);
      fetchFolderTracker(newId);
      fetchChecklistTracker(newId);
    }
  }
);

// ── Expose refresh helpers so parent can trigger targeted re-fetches ───────
const refreshChecklist        = () => props.viewCase?.id && fetchChecklist(props.viewCase.id);
const refreshFolderTracker    = () => props.viewCase?.id && fetchFolderTracker(props.viewCase.id);
const refreshChecklistTracker = () => props.viewCase?.id && fetchChecklistTracker(props.viewCase.id);

const tabs      = ['Folder Tracker', 'Checklist Tracker'];
const activeTab = ref('Folder Tracker');

// ── Dropdown state ─────────────────────────────────────────────────────────
const inDropdownOpen          = ref(false);
const inChecklistDropdownOpen = ref(false);
const closeDropdowns = () => { inDropdownOpen.value = false; inChecklistDropdownOpen.value = false; };

// ── Toast notification ─────────────────────────────────────────────────────
const toast = reactive({ show: false, message: '', type: 'error', timer: null });
const showToast = (message, type = 'error') => {
  if (toast.timer) clearTimeout(toast.timer);
  toast.message = message;
  toast.type    = type;
  toast.show    = true;
  toast.timer   = setTimeout(() => { toast.show = false; }, 4000);
};

// ── Folder IN/OUT modal ────────────────────────────────────────────────────
const fm = reactive({ show: false, type: 'out', form: { person: '', date: '', purpose: '', handledBy: '' } });

const openFolderModal = (type) => {
  type = type.toLowerCase();
  const folderIsOut = !!props.viewCase?.is_out;
  if (type === 'out' && folderIsOut) {
    showToast('The folder is already OUT of the office. Receive it back IN first.');
    return;
  }
  if (type === 'in' && !folderIsOut) {
    showToast('The folder is already IN the office. Nothing to receive.', 'info');
    return;
  }
  fm.type = type;
  const creatorName = props.currentUser ? (props.currentUser.full_name ?? props.currentUser.name ?? '') : '';
  const handledBy = props.viewCase?.clerk || creatorName;
  fm.form = { person: creatorName, date: new Date().toISOString().slice(0, 10), purpose: '', handledBy };
  fm.show = true;
  inDropdownOpen.value = false;
};

const submitFolderModal = () => {
  emit('folder-movement', {
    type:       fm.type,
    from_to:    fm.form.person,
    date:       fm.form.date,
    purpose:    fm.form.purpose,
    handled_by: fm.form.handledBy,
  });
  emit('update:viewCase', { ...props.viewCase, is_out: fm.type === 'out' ? 1 : 0 });
  fm.show = false;
  // Optimistically re-fetch folder tracker after a short delay for the server to commit
  setTimeout(() => refreshFolderTracker(), 600);
};

// ── Stage update ───────────────────────────────────────────────────────────
const stageUpdating = ref(false);
const onStageChange = (stageId) => {
  const stage = props.activeStages.find(s => String(s.id) === String(stageId));
  if (!stage) return;
  stageUpdating.value = true;
  emit('update-stage', { stage_id: stage.id, stage_name: stage.name });
};
const finishStageUpdate = () => { stageUpdating.value = false; };
defineExpose({ finishStageUpdate, refreshChecklist, refreshFolderTracker, refreshChecklistTracker });

// ── Task modal ─────────────────────────────────────────────────────────────
const tm = reactive({ show: false, mode: 'add', task: null });
const openTaskModal = (task, mode) => {
  tm.task = task ? { ...task } : null;
  tm.mode = mode;
  tm.show = true;
};
const onTaskSave = ({ mode, data }) => {
  if (mode === 'add')  emit('add-task',    data);
  if (mode === 'edit') emit('update-task', data);
  tm.show = false;
  // Re-fetch checklist immediately after save so new task appears without waiting for parent
  setTimeout(() => refreshChecklist(), 300);
};
const toggleDone = (task) => {
  emit('update-task', { ...task, status: task.status === 'done' ? 'todo' : 'done' });
  // Optimistic update in local list
  const idx = internalChecklist.value.findIndex(t => t.id === task.id);
  if (idx !== -1) internalChecklist.value[idx] = { ...internalChecklist.value[idx], status: task.status === 'done' ? 'todo' : 'done' };
};

// ── Checklist Tracker Modal ────────────────────────────────────────────────
const ctm = reactive({
  show: false,
  type: 'out',
  form: { taskId: '', person: '', date: '', purpose: '', handledBy: '' },
});

const openChecklistTracker = (type) => {
  type = type.toLowerCase();
  const folderIsOut = !!props.viewCase?.is_out;
  if (type === 'out' && folderIsOut) {
    showToast('The case folder is currently OUT of the office. Receive it back IN before releasing checklist items.');
    return;
  }
  const anyIn  = internalChecklist.value.some(t => !t.is_out);
  const anyOut = internalChecklist.value.some(t => !!t.is_out);
  if (type === 'out' && !anyIn) {
    showToast('All checklist items are already OUT of the office. Nothing to release.');
    return;
  }
  if (type === 'in' && !anyOut) {
    showToast('No checklist items are currently OUT. Nothing to receive.', 'info');
    return;
  }
  ctm.type = type;
  const creatorName = props.currentUser ? (props.currentUser.full_name ?? props.currentUser.name ?? '') : '';
  ctm.form = { taskId: '', person: creatorName, date: new Date().toISOString().slice(0, 10), purpose: '', handledBy: '' };
  ctm.show = true;
  inChecklistDropdownOpen.value = false;
};

const submitChecklistTracker = () => {
  const taskName = ctm.form.taskId
    ? (internalChecklist.value.find(t => t.id === ctm.form.taskId)?.task
      ?? internalChecklist.value.find(t => t.id === ctm.form.taskId)?.document_type
      ?? '—')
    : null;
  emit('checklist-movement', { type: ctm.type, taskName, ...ctm.form });
  ctm.show = false;
  setTimeout(() => refreshChecklistTracker(), 600);
};

const onCtmTaskChange = () => {
  const task = internalChecklist.value.find(t => t.id === ctm.form.taskId);
  ctm.form.handledBy = task?.assigned_to ?? '';
};

// ── Checklist filters ──────────────────────────────────────────────────────
const searchQuery  = ref('');
const statusFilter = ref('all');
const clerkFilter  = ref('');
const dateFilter   = ref('all');

const clearFilters = () => {
  searchQuery.value   = '';
  statusFilter.value  = 'all';
  clerkFilter.value   = '';
  dateFilter.value    = 'all';
  checklistPage.value = 1;
};

const hasActiveFilters = computed(() =>
  searchQuery.value || statusFilter.value !== 'all' || clerkFilter.value !== '' || dateFilter.value !== 'all'
);

const filteredChecklist = computed(() => {
  return internalChecklist.value.filter(task => {
    if (searchQuery.value) {
      const q     = searchQuery.value.toLowerCase();
      const name  = (task.task ?? '').toLowerCase();
      const notes = (task.notes ?? '').toLowerCase();
      const clerk = (task.assigned_to ?? '').toLowerCase();
      if (!name.includes(q) && !notes.includes(q) && !clerk.includes(q)) return false;
    }
    if (statusFilter.value !== 'all' && task.status !== statusFilter.value) return false;
    if (clerkFilter.value) {
      if (!(task.assigned_to ?? '').toLowerCase().includes(clerkFilter.value.toLowerCase())) return false;
    }
    if (dateFilter.value !== 'all') {
      const today = new Date(); today.setHours(0, 0, 0, 0);
      const due   = task.due_date ? (() => { const d = new Date(task.due_date); d.setHours(0,0,0,0); return d; })() : null;
      switch (dateFilter.value) {
        case 'overdue':  if (!due || due >= today || task.status === 'done') return false; break;
        case 'today':    if (!due || due.getTime() !== today.getTime()) return false; break;
        case 'week':  { const end = new Date(today); end.setDate(today.getDate() + 7); if (!due || due < today || due > end) return false; break; }
        case 'month': { const end = new Date(today); end.setMonth(today.getMonth() + 1); if (!due || due < today || due > end) return false; break; }
        case 'no-date': if (task.due_date) return false; break;
      }
    }
    return true;
  });
});

// ── Pagination ─────────────────────────────────────────────────────────────
const PAGE_SIZE     = 5;
const TRACKER_SIZE  = 5;
const checklistPage = ref(1);

const totalChecklistPages = computed(() =>
  Math.max(1, Math.ceil(filteredChecklist.value.length / PAGE_SIZE))
);
const paginatedChecklist = computed(() => {
  const start = (checklistPage.value - 1) * PAGE_SIZE;
  return filteredChecklist.value.slice(start, start + PAGE_SIZE);
});
watch(filteredChecklist, () => { checklistPage.value = 1; });

const folderTrackerPage    = ref(1);
const checklistTrackerPage = ref(1);

const totalFolderTrackerPages = computed(() =>
  Math.max(1, Math.ceil(internalFolderHistory.value.length / TRACKER_SIZE))
);
const totalChecklistTrackerPages = computed(() =>
  Math.max(1, Math.ceil(internalChecklistHistory.value.length / TRACKER_SIZE))
);
const paginatedFolderHistory = computed(() => {
  const start = (folderTrackerPage.value - 1) * TRACKER_SIZE;
  return internalFolderHistory.value.slice(start, start + TRACKER_SIZE);
});
const paginatedChecklistHistory = computed(() => {
  const start = (checklistTrackerPage.value - 1) * TRACKER_SIZE;
  return internalChecklistHistory.value.slice(start, start + TRACKER_SIZE);
});

watch(internalFolderHistory,    () => { folderTrackerPage.value    = 1; });
watch(internalChecklistHistory, () => { checklistTrackerPage.value = 1; });

// ── Done percent ───────────────────────────────────────────────────────────
const donePercent = computed(() => {
  if (!internalChecklist.value.length) return 0;
  return Math.round((internalChecklist.value.filter(t => t.status === 'done').length / internalChecklist.value.length) * 100);
});

// ── Helpers ────────────────────────────────────────────────────────────────
const resolveClerk = (id) => {
  if (!id) return '—';
  const found = props.clerks.find(c => c.id === id);
  return found ? (found.name ?? found.full_name ?? '—') : '—';
};
const getInitials = (name) =>
  name && name !== '—' ? name.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase() : '?';
const isOverdue = (d) => {
  if (!d) return false;
  const today = new Date(); today.setHours(0, 0, 0, 0);
  const due   = new Date(d); due.setHours(0, 0, 0, 0);
  return due < today;
};
const formatDate = (d) => {
  if (!d) return '—';
  const dt = new Date(d);
  if (isNaN(dt)) return d;
  return dt.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
};
const formatStatus = (status) => {
  if (!status) return '—';
  return { active: 'IN: In Office', closed: 'CLOSED', archived: 'ARCHIVED' }[status] || status;
};
const statusBadgeClass = (status) => ({
  active:   'bg-emerald-600 text-white',
  closed:   'bg-red-500 text-white',
  archived: 'bg-amber-500 text-white',
}[status] ?? 'bg-gray-200 text-gray-700');

const taskStatusLabel = (s) => ({ todo: 'To-Do', 'in-progress': 'In Progress', done: 'Done' }[s] ?? s);
const taskStatusClass = (s) => ({
  todo:          'bg-blue-100 text-blue-700 border border-blue-200',
  'in-progress': 'bg-orange-100 text-orange-700 border border-orange-200',
  done:          'bg-emerald-100 text-emerald-700 border border-emerald-200',
}[s] ?? 'bg-gray-100 text-gray-500');
const taskStatusDot = (s) => ({
  todo:          'bg-blue-500',
  'in-progress': 'bg-orange-500',
  done:          'bg-emerald-500',
}[s] ?? 'bg-gray-400');

const CATEGORY_COLORS = {
  'Pleading':       'bg-blue-50 text-blue-700',
  'Letter':         'bg-sky-50 text-sky-700',
  'Evidence':       'bg-emerald-50 text-emerald-700',
  'Court Issuance': 'bg-amber-50 text-amber-700',
  'Other':          'bg-slate-100 text-slate-600',
};
const categoryBadgeClass = (c) => CATEGORY_COLORS[c] ?? 'bg-slate-100 text-slate-600';
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from, .modal-leave-to       { opacity: 0; transform: scale(0.97) translateY(8px); }

.overflow-y-auto::-webkit-scrollbar       { width: 5px; }
.overflow-y-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

.toast-enter-active, .toast-leave-active { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.toast-enter-from, .toast-leave-to       { opacity: 0; transform: scale(0.95) translateY(8px); }
</style>