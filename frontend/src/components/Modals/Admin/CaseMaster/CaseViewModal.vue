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

          <div class="flex items-center gap-3">
            <!-- Live badge -->
            <button @click="$emit('close')" class="p-2 hover:bg-gray-100 rounded-xl transition text-gray-400 hover:text-gray-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
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
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Case Code</p>
                  <p class="text-sm font-bold text-gray-900">{{ viewCase.case_code || '—' }}</p>
                </div>
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Case Number</p>
                  <p class="text-sm font-bold text-gray-900">{{ viewCase.case_no || '—' }}</p>
                </div>
                <div class="col-span-1">
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Case Title</p>
                  <p class="text-sm font-bold text-gray-900">{{ viewCase.title || '—' }}</p>
                </div>
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Client</p>
                  <p class="text-sm font-bold text-gray-900">{{ viewCase.client || '—' }}</p>
                </div>
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Assigned Lawyer</p>
                  <p class="text-sm font-bold text-gray-900">{{ viewCase.lawyer ? 'Atty. ' + viewCase.lawyer : '—' }}</p>
                </div>
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Assigned Clerk</p>
                  <p class="text-sm font-bold text-gray-900">{{ viewCase.clerk || '—' }}</p>
                </div>
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
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Docket Number</p>
                  <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 text-gray-700 border border-gray-200 text-sm font-bold rounded-lg">
                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    {{ viewCase.docket_no || '—' }}
                  </span>
                </div>
                <div>
                  <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Current Status</p>
                  <span :class="statusBadgeClass(viewCase.case_status)" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-bold rounded-lg">
                    <span class="w-2 h-2 rounded-full bg-white/70"></span>
                    {{ formatStatus(viewCase.case_status) }}
                  </span>
                </div>
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
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-white">
              <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-violet-500 rounded-full"></div>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Case Checklist</h3>
                <span v-if="internalChecklist.length" class="px-2 py-0.5 text-xs font-bold bg-violet-100 text-violet-700 rounded-full">
                  {{ internalChecklist.length }}
                </span>
              </div>
              <div class="flex items-center gap-3">
                <div v-if="internalChecklist.length" class="flex items-center gap-2">
                  <div class="w-32 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" :style="{ width: `${donePercent}%` }"></div>
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
              <div v-if="!filteredChecklist.length" class="py-14 text-center">
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
                    :class="[task.status === 'done' ? 'opacity-60' : '', task._flash ? 'animate-flash' : '']">
                    <td class="px-5 py-3.5">
                      <input type="checkbox" :checked="task.status === 'done'" @change="toggleDone(task)"
                        class="w-4.5 h-4.5 rounded border-2 border-gray-300 text-blue-600 cursor-pointer accent-blue-600"/>
                    </td>
                    <td class="px-5 py-3.5 min-w-[180px] max-w-[260px]">
                      <p class="text-sm font-semibold truncate"
                        :class="task.status === 'done' ? 'line-through text-gray-400' : 'text-gray-800'"
                        :title="task.task">{{ task.task || '—' }}</p>
                    </td>
                    <td class="px-5 py-3.5">
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold rounded-full whitespace-nowrap" :class="taskStatusClass(task.status)">
                        <span class="w-1.5 h-1.5 rounded-full" :class="taskStatusDot(task.status)"></span>
                        {{ taskStatusLabel(task.status) }}
                      </span>
                    </td>
                    <td class="px-5 py-3.5">
                      <div class="flex items-center gap-1.5">
                        <span class="text-sm text-gray-600 whitespace-nowrap">{{ formatDate(task.due_date) }}</span>
                        <span v-if="isOverdue(task.due_date) && task.status !== 'done'"
                          class="px-1.5 py-0.5 text-[9px] font-bold bg-red-100 text-red-600 rounded-full uppercase tracking-wide">Overdue</span>
                      </div>
                    </td>
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
                        Edit <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
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

            <!-- Checklist Footer / Pagination -->
            <div v-if="internalChecklist.length" class="px-6 py-4 bg-gray-50/80 border-t border-gray-100">
              <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-gray-400 font-medium">
                  Showing {{ (checklistPage - 1) * PAGE_SIZE + 1 }}–{{ Math.min(checklistPage * PAGE_SIZE, filteredChecklist.length) }} of {{ filteredChecklist.length }} tasks
                </span>
                <span class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                  {{ internalChecklist.filter(t => t.status === 'done').length }} of {{ internalChecklist.length }} completed
                </span>
              </div>
              <div class="flex items-center justify-center gap-1">
                <button @click="checklistPage = 1" :disabled="checklistPage === 1" class="w-8 h-8 flex items-center justify-center rounded-lg border text-xs font-bold transition" :class="checklistPage === 1 ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">«</button>
                <button @click="checklistPage--" :disabled="checklistPage === 1" class="w-8 h-8 flex items-center justify-center rounded-lg border transition" :class="checklistPage === 1 ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button v-for="page in totalChecklistPages" :key="page" @click="checklistPage = page" class="w-8 h-8 flex items-center justify-center rounded-lg border text-sm font-bold transition" :class="checklistPage === page ? 'bg-blue-600 border-blue-600 text-white shadow-sm' : 'border-gray-200 text-gray-600 hover:bg-gray-100'">{{ page }}</button>
                <button @click="checklistPage++" :disabled="checklistPage === totalChecklistPages" class="w-8 h-8 flex items-center justify-center rounded-lg border transition" :class="checklistPage === totalChecklistPages ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button @click="checklistPage = totalChecklistPages" :disabled="checklistPage === totalChecklistPages" class="w-8 h-8 flex items-center justify-center rounded-lg border text-xs font-bold transition" :class="checklistPage === totalChecklistPages ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">»</button>
              </div>
            </div>
          </div>

          <!-- ══ TABS ════════════════════════════════════════════════════════ -->
          <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="flex border-b border-gray-100 px-2 pt-1">
              <button v-for="tab in tabs" :key="tab" @click="activeTab = tab"
                class="px-5 py-3.5 text-sm font-semibold border-b-2 transition -mb-px"
                :class="activeTab === tab ? 'text-blue-600 border-blue-600' : 'text-gray-400 border-transparent hover:text-gray-700 hover:border-gray-300'">
                {{ tab }}
              </button>
            </div>

            <!-- ── Folder Tracker ──────────────────────────────────────── -->
            <div v-if="activeTab === 'Folder Tracker'">
              <div class="px-6 pt-5 pb-3">
                <h4 class="text-sm font-bold text-gray-800 mb-3">IN / OUT Folder Tracker</h4>
                <div class="flex gap-2 items-center">
                  <button @click="openFolderModal('out')" class="px-4 py-1.5 bg-orange-500 hover:bg-orange-600 active:scale-95 text-white text-sm font-semibold rounded-md transition shadow-sm">Release Folder (OUT)</button>
                  <div class="relative" ref="inDropdownRef">
                    <div class="flex">
                      <button @click="openFolderModal('in')" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-sm font-semibold rounded-l-md transition shadow-sm">Receive Folder (IN)</button>
                      <button @click="inDropdownOpen = !inDropdownOpen" class="px-2 py-1.5 bg-emerald-600 hover:bg-emerald-700 border-l border-emerald-500 text-white rounded-r-md transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"/></svg>
                      </button>
                    </div>
                    <div v-if="inDropdownOpen" class="absolute left-0 top-full mt-1 w-52 bg-white border border-gray-200 rounded-xl shadow-lg z-10 overflow-hidden">
                      <button @click="openFolderModal('in'); inDropdownOpen = false" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Receive from Court</button>
                      <button @click="openFolderModal('in'); inDropdownOpen = false" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Receive from Client</button>
                      <button @click="openFolderModal('in'); inDropdownOpen = false" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Receive from Filing</button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full">
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
                      :class="[idx % 2 === 0 ? 'bg-white' : 'bg-gray-50/30', record._flash ? 'animate-flash' : '']">
                      <td class="px-6 py-3 text-sm text-gray-700">{{ formatDate(record.date) }}</td>
                      <td class="px-6 py-3"><span :class="record.type === 'OUT' ? 'bg-orange-500 text-white' : 'bg-emerald-500 text-white'" class="inline-block px-2.5 py-0.5 text-xs font-bold rounded">{{ record.type }}</span></td>
                      <td class="px-6 py-3">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border"
                          :class="record.approval_status === 'PENDING' ? 'border-amber-400 text-amber-400' : record.approval_status === 'APPROVED' ? 'border-emerald-400 text-emerald-400' : 'border-rose-400 text-rose-400'">
                          <span v-if="record.approval_status === 'PENDING'" class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                          {{ record.approval_status }}
                        </span>
                        <p v-if="record.approval_status !== 'PENDING' && record.approver?.full_name" class="text-[10px] text-slate-400 mt-0.5">by {{ record.approver.full_name }}</p>
                      </td>
                      <td class="px-6 py-3 text-sm text-gray-700"><span class="text-gray-400 mr-1">{{ record.type === 'OUT' ? 'From:' : 'To:' }}</span>{{ record.from_to || '—' }}</td>
                      <td class="px-6 py-3 text-sm text-gray-700">{{ record.purpose || '—' }}</td>
                      <td class="px-6 py-3 text-sm font-semibold text-gray-800">{{ record.handled_by || '—' }}</td>
                    </tr>
                    <tr v-if="!internalFolderHistory.length">
                      <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">No folder movements recorded</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-if="internalFolderHistory.length > TRACKER_SIZE" class="px-6 py-3 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-400">{{ (folderTrackerPage - 1) * TRACKER_SIZE + 1 }}–{{ Math.min(folderTrackerPage * TRACKER_SIZE, internalFolderHistory.length) }} of {{ internalFolderHistory.length }}</span>
                <div class="flex items-center gap-1">
                  <button @click="folderTrackerPage = 1" :disabled="folderTrackerPage === 1" class="px-2 py-1 text-xs border rounded-lg" :class="folderTrackerPage === 1 ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">«</button>
                  <button @click="folderTrackerPage--" :disabled="folderTrackerPage === 1" class="px-2 py-1 text-xs border rounded-lg" :class="folderTrackerPage === 1 ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">‹</button>
                  <button v-for="page in totalFolderTrackerPages" :key="page" @click="folderTrackerPage = page" class="px-2.5 py-1 text-xs border rounded-lg" :class="folderTrackerPage === page ? 'bg-blue-500 text-white border-blue-500' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">{{ page }}</button>
                  <button @click="folderTrackerPage++" :disabled="folderTrackerPage === totalFolderTrackerPages" class="px-2 py-1 text-xs border rounded-lg" :class="folderTrackerPage === totalFolderTrackerPages ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">›</button>
                  <button @click="folderTrackerPage = totalFolderTrackerPages" :disabled="folderTrackerPage === totalFolderTrackerPages" class="px-2 py-1 text-xs border rounded-lg" :class="folderTrackerPage === totalFolderTrackerPages ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">»</button>
                </div>
              </div>
            </div>

            <!-- ── Checklist Tracker ───────────────────────────────────── -->
            <div v-if="activeTab === 'Checklist Tracker'">
              <div class="px-6 pt-5 pb-3">
                <h4 class="text-sm font-bold text-gray-800 mb-3">IN / OUT Checklist Tracker</h4>
                <div class="flex gap-2 items-center">
                  <button @click="openChecklistTracker('out')" class="px-4 py-1.5 bg-orange-500 hover:bg-orange-600 active:scale-95 text-white text-sm font-semibold rounded-md transition shadow-sm">Release Checklist (OUT)</button>
                  <div class="relative">
                    <div class="flex">
                      <button @click="openChecklistTracker('in')" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-sm font-semibold rounded-l-md transition shadow-sm">Receive Checklist (IN)</button>
                      <button @click="inChecklistDropdownOpen = !inChecklistDropdownOpen" class="px-2 py-1.5 bg-emerald-600 hover:bg-emerald-700 border-l border-emerald-500 text-white rounded-r-md transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"/></svg>
                      </button>
                    </div>
                    <div v-if="inChecklistDropdownOpen" class="absolute left-0 top-full mt-1 w-52 bg-white border border-gray-200 rounded-xl shadow-lg z-10 overflow-hidden">
                      <button @click="openChecklistTracker('in'); inChecklistDropdownOpen = false" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>From Court</button>
                      <button @click="openChecklistTracker('in'); inChecklistDropdownOpen = false" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>From Client</button>
                      <button @click="openChecklistTracker('in'); inChecklistDropdownOpen = false" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>From Filing</button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full">
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
                      :class="[idx % 2 === 0 ? 'bg-white' : 'bg-gray-50/30', record._flash ? 'animate-flash' : '']">
                      <td class="px-6 py-3 text-sm text-gray-700">{{ formatDate(record.date) }}</td>
                      <td class="px-6 py-3"><span :class="record.type === 'OUT' ? 'bg-orange-500 text-white' : 'bg-emerald-500 text-white'" class="inline-block px-2.5 py-0.5 text-xs font-bold rounded">{{ record.type }}</span></td>
                      <td class="px-6 py-3 text-sm text-gray-700 font-medium">{{ record.approval_status === 'APPROVED' ? 'Approved' : record.approval_status === 'REJECTED' ? 'Rejected' : 'Pending' }}</td>
                      <td class="px-6 py-3 text-sm text-gray-700 font-medium">{{ record.task_name || record.checklist?.task || 'All / General' }}</td>
                      <td class="px-6 py-3 text-sm text-gray-700"><span class="text-gray-400 mr-1">{{ record.type === 'OUT' ? 'To:' : 'From:' }}</span>{{ record.from_to || '—' }}</td>
                      <td class="px-6 py-3 text-sm text-gray-700">{{ record.purpose || '—' }}</td>
                      <td class="px-6 py-3 text-sm font-semibold text-gray-800">{{ record.handled_by || '—' }}</td>
                    </tr>
                    <tr v-if="!internalChecklistHistory.length">
                      <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">No checklist movements recorded</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-if="internalChecklistHistory.length > TRACKER_SIZE" class="px-6 py-3 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-400">{{ (checklistTrackerPage - 1) * TRACKER_SIZE + 1 }}–{{ Math.min(checklistTrackerPage * TRACKER_SIZE, internalChecklistHistory.length) }} of {{ internalChecklistHistory.length }}</span>
                <div class="flex items-center gap-1">
                  <button @click="checklistTrackerPage = 1" :disabled="checklistTrackerPage === 1" class="px-2 py-1 text-xs border rounded-lg" :class="checklistTrackerPage === 1 ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">«</button>
                  <button @click="checklistTrackerPage--" :disabled="checklistTrackerPage === 1" class="px-2 py-1 text-xs border rounded-lg" :class="checklistTrackerPage === 1 ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">‹</button>
                  <button v-for="page in totalChecklistTrackerPages" :key="page" @click="checklistTrackerPage = page" class="px-2.5 py-1 text-xs border rounded-lg" :class="checklistTrackerPage === page ? 'bg-blue-500 text-white border-blue-500' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">{{ page }}</button>
                  <button @click="checklistTrackerPage++" :disabled="checklistTrackerPage === totalChecklistTrackerPages" class="px-2 py-1 text-xs border rounded-lg" :class="checklistTrackerPage === totalChecklistTrackerPages ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">›</button>
                  <button @click="checklistTrackerPage = totalChecklistTrackerPages" :disabled="checklistTrackerPage === totalChecklistTrackerPages" class="px-2 py-1 text-xs border rounded-lg" :class="checklistTrackerPage === totalChecklistTrackerPages ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-500 hover:bg-gray-100'">»</button>
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
    <div v-if="toast.show" class="fixed inset-0 z-[110] flex items-center justify-center p-4" style="background:rgba(0,0,0,0.35);backdrop-filter:blur(2px)" @click.self="toast.show = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div :class="toast.type === 'error' ? 'bg-gradient-to-r from-red-500 to-red-600' : 'bg-gradient-to-r from-blue-500 to-blue-600'" class="px-6 py-5 flex items-center gap-4">
          <div class="w-11 h-11 rounded-2xl bg-white/20 flex items-center justify-center flex-shrink-0">
            <svg v-if="toast.type === 'error'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <svg v-else class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="flex-1">
            <p class="text-white font-bold text-base">{{ toast.type === 'error' ? 'Action Not Allowed' : 'Nothing to Do' }}</p>
            <p class="text-white/70 text-xs mt-0.5">Folder tracker guard</p>
          </div>
          <button @click="toast.show = false" class="text-white/60 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div class="px-6 py-5"><p class="text-sm text-gray-700 leading-relaxed">{{ toast.message }}</p></div>
        <div class="px-6 pb-5">
          <button @click="toast.show = false" :class="toast.type === 'error' ? 'bg-red-500 hover:bg-red-600' : 'bg-blue-500 hover:bg-blue-600'" class="w-full py-2.5 text-sm font-bold text-white rounded-xl transition active:scale-95">Got it</button>
        </div>
      </div>
    </div>
  </Transition>

  <!-- ══ TASK MODAL ══════════════════════════════════════════════════════════ -->
  <CaseTaskModal
    :show="tm.show" :mode="tm.mode" :task="tm.task" :clerks="clerks"
    @close="tm.show = false" @save="onTaskSave" @switch-to-edit="tm.mode = 'edit'"
  />

  <!-- ══ FOLDER MOVEMENT MODAL ══════════════════════════════════════════════ -->
  <Transition name="modal">
    <div v-if="fm.show" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="fm.show = false">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
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
          <button @click="fm.show = false" class="p-2 hover:bg-gray-100 rounded-xl text-gray-400 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="px-6 py-5 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">{{ fm.type === 'out' ? 'From (Creator)' : 'To (Creator)' }}</label>
              <input v-model="fm.form.person" disabled class="w-full px-3 py-2 border border-gray-100 rounded-xl text-sm bg-gray-50 text-gray-500 cursor-not-allowed"/>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Date <span class="text-red-400">*</span></label>
              <input v-model="fm.form.date" disabled type="date" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm"/>
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Purpose / Remarks</label>
            <input v-model="fm.form.purpose" type="text" placeholder="e.g. For Review, For Submission…" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition"/>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Handled By</label>
            <input v-model="fm.form.handledBy" type="text" placeholder="Staff name…" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition"/>
          </div>
        </div>
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50">
          <button @click="fm.show = false" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl border border-gray-200 transition">Cancel</button>
          <button @click="submitFolderModal" :class="fm.type === 'out' ? 'bg-orange-500 hover:bg-orange-600' : 'bg-emerald-600 hover:bg-emerald-700'" class="px-5 py-2 text-sm font-bold text-white rounded-xl transition shadow-sm active:scale-95">
            Confirm {{ fm.type === 'out' ? 'Release' : 'Receive' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>

  <!-- ══ CHECKLIST TRACKER MODAL ════════════════════════════════════════════ -->
  <Transition name="modal">
    <div v-if="ctm.show" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="ctm.show = false">
      <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <div :class="ctm.type === 'out' ? 'bg-orange-500' : 'bg-emerald-600'" class="w-8 h-8 rounded-xl flex items-center justify-center shadow-sm">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="ctm.type === 'out'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
            </div>
            <div>
              <h2 class="text-base font-bold text-gray-900">{{ ctm.type === 'out' ? 'Release Checklist (OUT)' : 'Receive Checklist (IN)' }}</h2>
              <p class="text-xs text-gray-400">{{ ctm.task ? (ctm.task.task ?? ctm.task.document_type) : 'All checklist items' }}</p>
            </div>
          </div>
          <button @click="ctm.show = false" class="p-2 hover:bg-gray-100 rounded-xl text-gray-400 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="px-6 py-5 space-y-4">
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Document / Task</label>
            <select v-model="ctm.form.taskId" @change="onCtmTaskChange" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-300 bg-white transition">
              <option value="">All / General</option>
              <template v-if="ctm.type === 'out'">
                <option v-for="task in internalChecklist.filter(t => !t.is_out)" :key="task.id" :value="task.id">{{ task.task ?? '—' }}</option>
              </template>
              <template v-else>
                <option v-for="task in internalChecklist.filter(t => !!t.is_out)" :key="task.id" :value="task.id">{{ task.task ?? '—' }}</option>
              </template>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">{{ ctm.type === 'out' ? 'From (Creator)' : 'To (Creator)' }}</label>
              <input v-model="ctm.form.person" disabled class="w-full px-3 py-2 border border-gray-100 rounded-xl text-sm bg-gray-50 text-gray-500 cursor-not-allowed"/>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Date <span class="text-red-400">*</span></label>
              <input v-model="ctm.form.date" disabled type="date" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm"/>
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Purpose / Remarks</label>
            <input v-model="ctm.form.purpose" type="text" placeholder="e.g. For Review, For Submission…" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition"/>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Handled By</label>
            <input v-model="ctm.form.handledBy" disabled class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm"/>
          </div>
        </div>
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50">
          <button @click="ctm.show = false" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl border border-gray-200 transition">Cancel</button>
          <button @click="submitChecklistTracker" :class="ctm.type === 'out' ? 'bg-orange-500 hover:bg-orange-600' : 'bg-emerald-600 hover:bg-emerald-700'" class="px-5 py-2 text-sm font-bold text-white rounded-xl transition shadow-sm active:scale-95">
            Confirm {{ ctm.type === 'out' ? 'Release' : 'Receive' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>


<script setup>
import { ref, computed, reactive, watch, onUnmounted } from 'vue';
import CaseTaskModal from './CaseTaskModal.vue';
import * as CaseService from '@/services/caseService';

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
  show:         { type: Boolean, default: false },
  viewCase:     { type: Object,  default: null  },
  activeStages: { type: Array,   default: () => [] },
  clerks:       { type: Array,   default: () => [] },
  currentUser:  { type: Object,  default: null  },
});

const emit = defineEmits([
  'close', 'edit', 'add-task', 'update-task', 'delete-task',
  'update-stage', 'checklist-movement', 'folder-movement', 'update:viewCase',
]);

// ─────────────────────────────────────────────────────────────────────────────
// SMART SYNC LAYER
//
// Three-layer approach (no third-party service needed):
//
//  Layer 1 — BroadcastChannel (instant, same browser, any role/tab)
//    When User A adds a task in Tab 1, Tab 2 receives the message in <1 ms
//    and re-fetches only that slice. Zero delay, zero extra server load.
//
//  Layer 2 — Visibility revalidation (same machine, tab regains focus)
//    Catches changes made while this tab was in the background.
//    Only re-fetches slices that were mutated (dirty flags).
//
//  Layer 3 — 15-second poll (cross-machine, different browsers/roles)
//    Catches changes by users on completely different devices.
//    Only runs while the modal is open and the tab is visible.
//    15 s is a comfortable balance: feels near-real-time, minimal server load.
// ─────────────────────────────────────────────────────────────────────────────

const unwrap = (res) => res?.data?.data ?? res?.data ?? res ?? [];

// ── Reactive data ──────────────────────────────────────────────────────────
const internalChecklist        = ref([]);
const internalFolderHistory    = ref([]);
const internalChecklistHistory = ref([]);

// ── Dirty flags (one per slice) ────────────────────────────────────────────
const dirty = reactive({ checklist: false, folder: false, checklistTracker: false });

// ── In-flight guards — prevents duplicate concurrent requests per slice ────
const _fetching = { checklist: false, folder: false, checklistTracker: false };

// True once the first loadAll() completes; gates visibility revalidation
let initialLoadDone = false;

// ─── FETCHERS ─────────────────────────────────────────────────────────────
// Each fetcher is guarded: if a request is already in-flight for that slice,
// the second caller simply returns — no duplicate network call is made.
// New rows get _flash = true so they glow for 1.5 s in multi-tab scenarios.

async function fetchChecklist(id) {
  if (_fetching.checklist) return;
  _fetching.checklist = true;
  try {
    const fresh    = unwrap(await CaseService.getChecklist(id)) || [];
    const existing = new Set(internalChecklist.value.map(t => t.id));
    internalChecklist.value = fresh.map(t => ({ ...t, _flash: initialLoadDone && !existing.has(t.id) }));
    if (initialLoadDone) setTimeout(() => { internalChecklist.value = internalChecklist.value.map(t => ({ ...t, _flash: false })); }, 1500);
    dirty.checklist = false;
  } catch (e) { console.error('[CaseViewModal] fetchChecklist:', e); }
  finally { _fetching.checklist = false; }
}

async function fetchFolderTracker(id) {
  if (_fetching.folder) return;
  _fetching.folder = true;
  try {
    const fresh    = unwrap(await CaseService.getFolderTracker(id)) || [];
    const existing = new Set(internalFolderHistory.value.map(r => r.id));
    internalFolderHistory.value = fresh.map(r => ({ ...r, _flash: initialLoadDone && !existing.has(r.id) }));
    if (initialLoadDone) setTimeout(() => { internalFolderHistory.value = internalFolderHistory.value.map(r => ({ ...r, _flash: false })); }, 1500);
    dirty.folder = false;
  } catch (e) { console.error('[CaseViewModal] fetchFolderTracker:', e); }
  finally { _fetching.folder = false; }
}

async function fetchChecklistTracker(id) {
  if (_fetching.checklistTracker) return;
  _fetching.checklistTracker = true;
  try {
    const fresh    = unwrap(await CaseService.getChecklistTracker(id)) || [];
    const existing = new Set(internalChecklistHistory.value.map(r => r.id));
    internalChecklistHistory.value = fresh.map(r => ({ ...r, _flash: initialLoadDone && !existing.has(r.id) }));
    if (initialLoadDone) setTimeout(() => { internalChecklistHistory.value = internalChecklistHistory.value.map(r => ({ ...r, _flash: false })); }, 1500);
    dirty.checklistTracker = false;
  } catch (e) { console.error('[CaseViewModal] fetchChecklistTracker:', e); }
  finally { _fetching.checklistTracker = false; }
}

// Full parallel load — called ONCE on modal open.
// Poll and visibility revalidation are both blocked until this completes.
async function loadAll(id) {
  await Promise.allSettled([
    fetchChecklist(id),
    fetchFolderTracker(id),
    fetchChecklistTracker(id),
  ]);
  initialLoadDone = true;
}

// Re-fetch only dirty slices — called on tab focus regain (Layer 2)
async function revalidateDirty(id) {
  if (!id || !initialLoadDone) return;
  const tasks = [];
  if (dirty.checklist)        tasks.push(fetchChecklist(id));
  if (dirty.folder)           tasks.push(fetchFolderTracker(id));
  if (dirty.checklistTracker) tasks.push(fetchChecklistTracker(id));
  if (tasks.length) await Promise.allSettled(tasks);
}

// ─── LAYER 1: BroadcastChannel — instant cross-tab sync ───────────────────
// A unique channel ID per session prevents self-echo: messages sent by THIS
// tab are ignored (BroadcastChannel does not deliver to the sender by spec,
// but the guard on _fetching provides a second layer of protection).
let bc = null;

function openChannel(caseId) {
  closeChannel();
  bc = new BroadcastChannel(`case_modal_${caseId}`);
  bc.onmessage = ({ data }) => {
    const id = props.viewCase?.id;
    if (!id || !initialLoadDone) return;   // ignore signals before initial load
    if (data.slice === 'checklist')        fetchChecklist(id);
    if (data.slice === 'folder')           fetchFolderTracker(id);
    if (data.slice === 'checklistTracker') fetchChecklistTracker(id);
  };
}

function closeChannel() { bc?.close(); bc = null; }
function broadcast(slice) { bc?.postMessage({ slice }); }

// ─── LAYER 2: Visibility revalidation ─────────────────────────────────────
// Uses dirty flags — only re-fetches slices that were mutated while hidden.
// Guarded by initialLoadDone so it never fires during the initial open.
function onVisibilityChange() {
  if (document.visibilityState !== 'visible') return;
  const id = props.viewCase?.id;
  if (props.show && id && initialLoadDone) revalidateDirty(id);
}
document.addEventListener('visibilitychange', onVisibilityChange);

// ─── LAYER 3: 30-second poll (cross-machine sync) ─────────────────────────
// Increased to 30 s (was 15 s) — halves background request count.
// Poll only starts AFTER loadAll() completes to prevent overlap.
// Skipped entirely if tab is hidden.
let pollTimer = null;

function startPolling(id) {
  stopPolling();
  pollTimer = setInterval(() => {
    if (document.visibilityState !== 'visible' || !initialLoadDone) return;
    fetchChecklist(id);
    fetchFolderTracker(id);
    fetchChecklistTracker(id);
  }, 30_000);
}

function stopPolling() { clearInterval(pollTimer); pollTimer = null; }

// ─── Lifecycle: open / close / case switch ────────────────────────────────
function resetState() {
  internalChecklist.value        = [];
  internalFolderHistory.value    = [];
  internalChecklistHistory.value = [];
  dirty.checklist = dirty.folder = dirty.checklistTracker = false;
  _fetching.checklist = _fetching.folder = _fetching.checklistTracker = false;
  initialLoadDone = false;
}

onUnmounted(() => {
  stopPolling();
  closeChannel();
  document.removeEventListener('visibilitychange', onVisibilityChange);
});

// ── Expose for parent ──────────────────────────────────────────────────────
// open(id) replaces watch([show, viewCase.id]) — called directly from openView()
// in CaseMaster. Zero watcher overhead, instant response.
const stageUpdating = ref(false);
const finishStageUpdate = () => { stageUpdating.value = false; };

const openModal = (id) => {
  resetState();
  // Start polling only after initial load completes — prevents poll from
  // firing concurrently with the initial 3 requests on modal open.
  loadAll(id).then(() => startPolling(id));
  openChannel(id);
};

const closeModal = () => {
  stopPolling();
  closeChannel();
  resetState();
  tm.show = false; ctm.show = false; fm.show = false;
  closeDropdowns(); clearFilters();
  folderTrackerPage.value = 1; checklistTrackerPage.value = 1;
};

defineExpose({
  openModal,
  closeModal,
  finishStageUpdate,
  refreshChecklist:        () => props.viewCase?.id && fetchChecklist(props.viewCase.id),
  refreshFolderTracker:    () => props.viewCase?.id && fetchFolderTracker(props.viewCase.id),
  refreshChecklistTracker: () => props.viewCase?.id && fetchChecklistTracker(props.viewCase.id),
});

// ─────────────────────────────────────────────────────────────────────────────
// UI STATE
// ─────────────────────────────────────────────────────────────────────────────

const tabs      = ['Folder Tracker', 'Checklist Tracker'];
const activeTab = ref('Folder Tracker');

const inDropdownOpen          = ref(false);
const inChecklistDropdownOpen = ref(false);
const closeDropdowns = () => { inDropdownOpen.value = false; inChecklistDropdownOpen.value = false; };

// ── Toast ──────────────────────────────────────────────────────────────────
const toast = reactive({ show: false, message: '', type: 'error', timer: null });
const showToast = (msg, type = 'error') => {
  if (toast.timer) clearTimeout(toast.timer);
  Object.assign(toast, { message: msg, type, show: true });
  toast.timer = setTimeout(() => { toast.show = false; }, 4000);
};

// ── Stage ──────────────────────────────────────────────────────────────────
const onStageChange = (stageId) => {
  const stage = props.activeStages.find(s => String(s.id) === String(stageId));
  if (!stage) return;
  stageUpdating.value = true;
  emit('update-stage', { stage_id: stage.id, stage_name: stage.name });
};

// ── Task modal ─────────────────────────────────────────────────────────────
const tm = reactive({ show: false, mode: 'add', task: null });
const openTaskModal = (task, mode) => { tm.task = task ? { ...task } : null; tm.mode = mode; tm.show = true; };

const onTaskSave = ({ mode, data }) => {
  if (mode === 'add')  emit('add-task',    data);
  if (mode === 'edit') emit('update-task', data);
  tm.show = false;
  dirty.checklist = true;
  // Fetch immediately — in-flight guard prevents any duplicate call.
  // broadcast() notifies other tabs after the local fetch settles.
  fetchChecklist(props.viewCase.id).then(() => broadcast('checklist'));
};

const toggleDone = (task) => {
  const newStatus = task.status === 'done' ? 'todo' : 'done';
  emit('update-task', { ...task, status: newStatus });
  // Optimistic update — instant feedback, no waiting for server
  const idx = internalChecklist.value.findIndex(t => t.id === task.id);
  if (idx !== -1) internalChecklist.value[idx] = { ...internalChecklist.value[idx], status: newStatus };
  dirty.checklist = true;
  broadcast('checklist');
};

// ── Folder modal ───────────────────────────────────────────────────────────
const fm = reactive({ show: false, type: 'out', form: { person: '', date: '', purpose: '', handledBy: '' } });

const openFolderModal = (type) => {
  type = type.toLowerCase();
  const isOut = !!props.viewCase?.is_out;
  if (type === 'out' && isOut)  { showToast('The folder is already OUT. Receive it back IN first.'); return; }
  if (type === 'in'  && !isOut) { showToast('The folder is already IN the office. Nothing to receive.', 'info'); return; }
  const creator = props.currentUser?.full_name ?? props.currentUser?.name ?? '';
  fm.type = type;
  fm.form = { person: creator, date: new Date().toISOString().slice(0, 10), purpose: '', handledBy: props.viewCase?.clerk || creator };
  fm.show = true;
  inDropdownOpen.value = false;
};

const submitFolderModal = () => {
  emit('folder-movement', { type: fm.type, from_to: fm.form.person, date: fm.form.date, purpose: fm.form.purpose, handled_by: fm.form.handledBy });
  emit('update:viewCase', { ...props.viewCase, is_out: fm.type === 'out' ? 1 : 0 });
  fm.show = false;
  dirty.folder = true;
  fetchFolderTracker(props.viewCase.id).then(() => broadcast('folder'));
};

// ── Checklist tracker modal ────────────────────────────────────────────────
const ctm = reactive({ show: false, type: 'out', task: null, form: { taskId: '', person: '', date: '', purpose: '', handledBy: '' } });

const openChecklistTracker = (type) => {
  type = type.toLowerCase();
  if (type === 'out' && !!props.viewCase?.is_out) { showToast('Case folder is OUT. Receive it back IN before releasing checklist items.'); return; }
  const anyIn  = internalChecklist.value.some(t => !t.is_out);
  const anyOut = internalChecklist.value.some(t => !!t.is_out);
  if (type === 'out' && !anyIn)  { showToast('All checklist items are already OUT. Nothing to release.'); return; }
  if (type === 'in'  && !anyOut) { showToast('No checklist items are currently OUT. Nothing to receive.', 'info'); return; }
  const creator = props.currentUser?.full_name ?? props.currentUser?.name ?? '';
  ctm.type = type; ctm.task = null;
  ctm.form = { taskId: '', person: creator, date: new Date().toISOString().slice(0, 10), purpose: '', handledBy: '' };
  ctm.show = true;
  inChecklistDropdownOpen.value = false;
};

const onCtmTaskChange = () => {
  const task = internalChecklist.value.find(t => t.id === ctm.form.taskId);
  ctm.form.handledBy = task?.assigned_to ?? '';
};

const submitChecklistTracker = () => {
  const found    = internalChecklist.value.find(t => t.id === ctm.form.taskId);
  const taskName = ctm.form.taskId ? (found?.task ?? found?.document_type ?? '—') : null;
  emit('checklist-movement', { type: ctm.type, taskName, ...ctm.form });
  ctm.show = false;
  dirty.checklistTracker = true;
  fetchChecklistTracker(props.viewCase.id).then(() => broadcast('checklistTracker'));
};

// ── Checklist filters ──────────────────────────────────────────────────────
const searchQuery  = ref('');
const statusFilter = ref('all');
const clerkFilter  = ref('');
const dateFilter   = ref('all');

const clearFilters = () => { searchQuery.value = ''; statusFilter.value = 'all'; clerkFilter.value = ''; dateFilter.value = 'all'; checklistPage.value = 1; };
const hasActiveFilters = computed(() => searchQuery.value || statusFilter.value !== 'all' || clerkFilter.value || dateFilter.value !== 'all');

const filteredChecklist = computed(() => internalChecklist.value.filter(task => {
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    if (!(task.task ?? '').toLowerCase().includes(q) && !(task.notes ?? '').toLowerCase().includes(q) && !(task.assigned_to ?? '').toLowerCase().includes(q)) return false;
  }
  if (statusFilter.value !== 'all' && task.status !== statusFilter.value) return false;
  if (clerkFilter.value && !(task.assigned_to ?? '').toLowerCase().includes(clerkFilter.value.toLowerCase())) return false;
  if (dateFilter.value !== 'all') {
    const today = new Date(); today.setHours(0,0,0,0);
    const due   = task.due_date ? (() => { const d = new Date(task.due_date); d.setHours(0,0,0,0); return d; })() : null;
    if (dateFilter.value === 'overdue' && (!due || due >= today || task.status === 'done')) return false;
    if (dateFilter.value === 'today'   && (!due || due.getTime() !== today.getTime())) return false;
    if (dateFilter.value === 'week')  { const e = new Date(today); e.setDate(today.getDate() + 7);   if (!due || due < today || due > e) return false; }
    if (dateFilter.value === 'month') { const e = new Date(today); e.setMonth(today.getMonth() + 1); if (!due || due < today || due > e) return false; }
    if (dateFilter.value === 'no-date' && task.due_date) return false;
  }
  return true;
}));

// ── Pagination ─────────────────────────────────────────────────────────────
const PAGE_SIZE    = 5;
const TRACKER_SIZE = 5;

const checklistPage           = ref(1);
const folderTrackerPage       = ref(1);
const checklistTrackerPage    = ref(1);

const totalChecklistPages        = computed(() => Math.max(1, Math.ceil(filteredChecklist.value.length / PAGE_SIZE)));
const totalFolderTrackerPages    = computed(() => Math.max(1, Math.ceil(internalFolderHistory.value.length / TRACKER_SIZE)));
const totalChecklistTrackerPages = computed(() => Math.max(1, Math.ceil(internalChecklistHistory.value.length / TRACKER_SIZE)));

const paginatedChecklist        = computed(() => filteredChecklist.value.slice((checklistPage.value - 1) * PAGE_SIZE, checklistPage.value * PAGE_SIZE));
const paginatedFolderHistory    = computed(() => internalFolderHistory.value.slice((folderTrackerPage.value - 1) * TRACKER_SIZE, folderTrackerPage.value * TRACKER_SIZE));
const paginatedChecklistHistory = computed(() => internalChecklistHistory.value.slice((checklistTrackerPage.value - 1) * TRACKER_SIZE, checklistTrackerPage.value * TRACKER_SIZE));

watch(filteredChecklist,         () => { checklistPage.value        = 1; });
watch(internalFolderHistory,     () => { folderTrackerPage.value    = 1; });
watch(internalChecklistHistory,  () => { checklistTrackerPage.value = 1; });

const donePercent = computed(() => {
  if (!internalChecklist.value.length) return 0;
  return Math.round(internalChecklist.value.filter(t => t.status === 'done').length / internalChecklist.value.length * 100);
});

// ── Helpers ────────────────────────────────────────────────────────────────
const getInitials      = (n) => n && n !== '—' ? n.split(' ').map(p => p[0]).join('').slice(0,2).toUpperCase() : '?';
const isOverdue        = (d) => { if (!d) return false; const t = new Date(); t.setHours(0,0,0,0); const due = new Date(d); due.setHours(0,0,0,0); return due < t; };
const formatDate       = (d) => { if (!d) return '—'; const dt = new Date(d); return isNaN(dt) ? d : dt.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }); };
const formatStatus     = (s) => ({ active: 'IN: In Office', closed: 'CLOSED', archived: 'ARCHIVED' }[s] || s || '—');
const statusBadgeClass = (s) => ({ active: 'bg-emerald-600 text-white', closed: 'bg-red-500 text-white', archived: 'bg-amber-500 text-white' }[s] ?? 'bg-gray-200 text-gray-700');
const taskStatusLabel  = (s) => ({ todo: 'To-Do', 'in-progress': 'In Progress', done: 'Done' }[s] ?? s);
const taskStatusClass  = (s) => ({ todo: 'bg-blue-100 text-blue-700 border border-blue-200', 'in-progress': 'bg-orange-100 text-orange-700 border border-orange-200', done: 'bg-emerald-100 text-emerald-700 border border-emerald-200' }[s] ?? 'bg-gray-100 text-gray-500');
const taskStatusDot    = (s) => ({ todo: 'bg-blue-500', 'in-progress': 'bg-orange-500', done: 'bg-emerald-500' }[s] ?? 'bg-gray-400');
</script>


<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.25s cubic-bezier(0.4,0,0.2,1); }
.modal-enter-from,   .modal-leave-to     { opacity: 0; transform: scale(0.97) translateY(8px); }

.toast-enter-active, .toast-leave-active { transition: all 0.25s cubic-bezier(0.4,0,0.2,1); }
.toast-enter-from,   .toast-leave-to     { opacity: 0; transform: scale(0.95) translateY(8px); }

.overflow-y-auto::-webkit-scrollbar       { width: 5px; }
.overflow-y-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

/* New rows added by other users flash a soft blue highlight */
@keyframes flash-highlight {
  0%   { background-color: #eff6ff; }
  60%  { background-color: #dbeafe; }
  100% { background-color: transparent; }
}
.animate-flash { animation: flash-highlight 1.5s ease-out forwards; }
</style>