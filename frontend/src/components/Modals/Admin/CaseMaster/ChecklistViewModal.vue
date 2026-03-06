<template>
  <Transition name="modal">
    <div v-if="show" class="fixed inset-0 z-[60] flex items-start sm:items-center justify-center p-0 sm:p-6" @click.self="$emit('close')">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

      <div class="relative bg-white w-full sm:rounded-2xl shadow-2xl sm:max-w-6xl max-h-screen sm:max-h-[90vh] flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-8 sm:px-10 py-6 border-b border-slate-100 flex-shrink-0 bg-gradient-to-r from-slate-50 to-white">
          <div class="flex items-center gap-5">
            <div class="w-14 h-14 rounded-xl bg-indigo-100 flex items-center justify-center shadow-sm">
              <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
              </svg>
            </div>
            <div>
              <h2 class="text-2xl font-bold text-slate-800">Case Checklist</h2>
              <p class="text-base text-slate-500 mt-1">
                <span v-if="viewCase" class="font-medium">{{ viewCase.case_code }}</span>
                <span v-if="checklist.length" class="ml-2 px-2.5 py-1 bg-slate-100 rounded-full text-sm">
                  {{ filteredChecklist.length }}/{{ checklist.length }} tasks
                </span>
                <span v-else class="ml-2 text-sm">No tasks yet</span>
              </p>
            </div>
          </div>

          <!-- Progress bar -->
          <div v-if="checklist.length" class="flex items-center gap-3">
            <div class="w-32 h-2 bg-slate-200 rounded-full overflow-hidden">
              <div class="h-full bg-emerald-500 rounded-full transition-all duration-300"
                :style="{ width: `${donePercent}%` }">
              </div>
            </div>
            <span class="text-sm font-semibold text-slate-600">{{ donePercent }}%</span>
          </div>
        </div>

        <!-- Filter Bar -->
        <div class="px-8 sm:px-10 py-4 border-b border-slate-100 bg-white flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between flex-shrink-0">
          <div class="flex flex-wrap gap-3 items-center w-full sm:w-auto">

            <!-- Search -->
            <div class="relative flex-1 sm:flex-none sm:w-72">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
              <input type="text" v-model="searchQuery" placeholder="Search tasks..."
                class="w-full pl-9 pr-8 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-100 focus:border-indigo-300 transition-all"/>
              <button v-if="searchQuery" @click="searchQuery = ''"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- Status Filter -->
            <select v-model="statusFilter"
              class="px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-100 focus:border-indigo-300 bg-white min-w-[130px]">
              <option value="all">All Status</option>
              <option value="todo">To-do</option>
              <option value="in-progress">In Progress</option>
              <option value="done">Done</option>
            </select>

            <!-- Clerk Filter -->
            <select v-model="clerkFilter"
              class="px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-100 focus:border-indigo-300 bg-white min-w-[150px]">
              <option value="all">All Clerks</option>
              <option v-for="clerk in clerks" :key="clerk.id" :value="clerk.id">
                {{ clerk.name ?? clerk.full_name }}
              </option>
            </select>

            <!-- Due Date Filter -->
            <select v-model="dateFilter"
              class="px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-100 focus:border-indigo-300 bg-white min-w-[140px]">
              <option value="all">All Dates</option>
              <option value="overdue">Overdue</option>
              <option value="today">Due Today</option>
              <option value="week">Due This Week</option>
              <option value="month">Due This Month</option>
              <option value="no-date">No Due Date</option>
            </select>

            <!-- Clear Filters -->
            <button v-if="hasActiveFilters" @click="clearFilters"
              class="px-3.5 py-2.5 text-sm font-medium text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-xl transition-colors flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
              Clear
            </button>
          </div>

          <div class="text-sm text-slate-500 bg-slate-50 px-3.5 py-2 rounded-lg flex-shrink-0">
            {{ filteredChecklist.length }} task{{ filteredChecklist.length !== 1 ? 's' : '' }} found
          </div>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto px-8 sm:px-10 py-6 bg-slate-50/30">

          <!-- Loading -->
          <div v-if="checklistLoading" class="py-24 flex flex-col items-center justify-center gap-4 text-slate-400">
            <svg class="animate-spin w-8 h-8" viewBox="0 0 24 24" fill="none">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span class="text-base">Loading tasks…</span>
          </div>

          <!-- Empty -->
          <div v-else-if="!filteredChecklist.length" class="py-24 text-center">
            <div class="w-20 h-20 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-5">
              <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
              </svg>
            </div>
            <p class="text-base font-semibold text-slate-500">
              {{ checklist.length ? 'No matching tasks' : 'No tasks yet' }}
            </p>
            <p class="text-sm text-slate-400 mt-2">
              <template v-if="checklist.length">
                Try adjusting filters or
                <button @click="clearFilters" class="text-indigo-600 hover:text-indigo-700 font-medium">clear all</button>
              </template>
              <template v-else>
                Click <span class="font-semibold text-[#1a4972]">Add New Task</span> below to get started.
              </template>
            </p>
          </div>

          <!-- Table -->
          <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="min-w-full">
              <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                  <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 w-10">
                    <span class="sr-only">Toggle done</span>
                  </th>
                  <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Documents</th>
                  <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                  <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Due Date</th>
                  <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">Assigned Clerk</th>
                  <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Notes</th>
                  <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="task in filteredChecklist" :key="task.id"
                  class="hover:bg-slate-50/80 transition-colors"
                  :class="task.status === 'done' ? 'opacity-70' : ''">

                  <!-- Done Toggle Checkbox -->
                  <td class="px-5 py-4">
                    <div class="flex items-center justify-center">
                      <input 
                        type="checkbox"
                        :checked="task.status === 'done'"
                        @change="toggleDone(task)"
                        class="w-5 h-5 rounded border-2 border-slate-300 text-emerald-600 focus:ring-emerald-500 focus:ring-offset-0 transition-all cursor-pointer hover:border-emerald-400"
                        :class="{ 'opacity-100': task.status === 'done', 'opacity-60': task.status !== 'done' }"
                      />
                    </div>
                  </td>

                  <!-- Task name -->
                  <td class="px-5 py-4 max-w-[220px]">
                    <p class="text-sm font-medium truncate"
                      :class="task.status === 'done' ? 'line-through text-slate-400' : 'text-slate-800'"
                      :title="task.task">{{ task.task }}</p>
                  </td>

                  <!-- Status -->
                  <td class="px-5 py-4">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full whitespace-nowrap"
                      :class="taskStatusClass(task.status)">
                      <span class="w-1.5 h-1.5 rounded-full" :class="taskStatusDot(task.status)"></span>
                      {{ taskStatusLabel(task.status) }}
                    </span>
                  </td>

                  <!-- Due date -->
                  <td class="px-5 py-4">
                    <div class="flex items-center gap-1.5">
                      <span class="text-sm text-slate-700 whitespace-nowrap">{{ formatDate(task.due_date) }}</span>
                      <span v-if="isOverdue(task.due_date) && task.status !== 'done'"
                        class="px-1.5 py-0.5 text-[9px] font-bold bg-red-100 text-red-700 rounded-full uppercase">
                        Overdue
                      </span>
                    </div>
                  </td>

                  <!-- Assigned clerk -->
                  <td class="px-5 py-4">
                    <div v-if="task.assigned_to || task.assigned_clerk_id" class="flex items-center gap-2">
                      <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0 bg-gradient-to-br from-[#1a4972] to-[#2a5a8c] shadow-sm">
                        {{ getInitials(task.assigned_to || resolveClerk(task.assigned_clerk_id)) }}
                      </div>
                      <span class="text-sm text-slate-700 whitespace-nowrap font-medium">
                        {{ task.assigned_to || resolveClerk(task.assigned_clerk_id) }}
                      </span>
                    </div>
                    <span v-else class="text-sm text-slate-400">—</span>
                  </td>

                  <!-- Notes -->
                  <td class="px-5 py-4 max-w-[200px]">
                    <span class="text-sm text-slate-500 italic truncate block" :title="task.notes || ''">
                      {{ task.notes || '—' }}
                    </span>
                  </td>

                  <!-- Actions -->
                  <td class="px-5 py-4">
                    <div class="flex items-center gap-1">
                      <!-- Edit — only for non-done tasks -->
                      <button v-if="task.status !== 'done'" @click="openEdit(task)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-[#1a4972] hover:bg-[#1a4972]/10 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                      </button>

                      <!-- View — only for done tasks -->
                      <button v-else @click="openView(task)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        View
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between gap-3 px-8 sm:px-10 py-5 border-t border-slate-200 bg-white flex-shrink-0">
          <button @click="openAdd"
            class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-[#1a4972] rounded-xl hover:bg-[#0f3a5a] transition-all shadow-sm hover:shadow">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Add New Task
          </button>
          <button @click="$emit('close')"
            class="px-6 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-all">
            Close
          </button>
        </div>
      </div>
    </div>
  </Transition>

  <!-- Task Modal (nested) -->
  <CaseTaskModal
    :show="tm.show"
    :mode="tm.mode"
    :task="tm.task"
    :clerks="clerks"
    @close="tm.show = false"
    @save="onTaskSave"
    @switch-to-edit="switchToEdit"
  />
</template>

<script setup>
import { reactive, watch, ref, computed } from 'vue';
import CaseTaskModal from './CaseTaskModal.vue';

const props = defineProps({
  show:             { type: Boolean, default: false },
  viewCase:         { type: Object,  default: null  },
  checklist:        { type: Array,   default: () => [] },
  checklistLoading: { type: Boolean, default: false },
  initialMode:      { type: String,  default: 'view' },
  clerks:           { type: Array,   default: () => [] },
});

const emit = defineEmits(['close', 'add-task', 'update-task', 'delete-task']);

// ── Filters ────────────────────────────────────────────────────────────────
const searchQuery  = ref('');
const statusFilter = ref('all');
const clerkFilter  = ref('all');
const dateFilter   = ref('all');

// ── Task sub-modal state ───────────────────────────────────────────────────
const tm = reactive({ show: false, mode: 'add', task: null });

// ── Computed ───────────────────────────────────────────────────────────────
const donePercent = computed(() => {
  if (!props.checklist.length) return 0;
  return Math.round(
    (props.checklist.filter(t => t.status === 'done').length / props.checklist.length) * 100
  );
});

const filteredChecklist = computed(() => {
  return props.checklist.filter(task => {
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase();
      const match =
        task.task?.toLowerCase().includes(q) ||
        task.notes?.toLowerCase().includes(q) ||
        task.assigned_to?.toLowerCase().includes(q) ||
        resolveClerk(task.assigned_clerk_id)?.toLowerCase().includes(q);
      if (!match) return false;
    }

    if (statusFilter.value !== 'all' && task.status !== statusFilter.value) return false;
    if (clerkFilter.value !== 'all' && task.assigned_clerk_id !== clerkFilter.value) return false;

    if (dateFilter.value !== 'all') {
      const today = new Date(); today.setHours(0, 0, 0, 0);
      const due   = task.due_date ? (() => { const d = new Date(task.due_date); d.setHours(0,0,0,0); return d; })() : null;

      switch (dateFilter.value) {
        case 'overdue':  if (!due || due >= today || task.status === 'done') return false; break;
        case 'today':    if (!due || due.getTime() !== today.getTime()) return false; break;
        case 'week': {
          const end = new Date(today); end.setDate(today.getDate() + 7);
          if (!due || due < today || due > end) return false; break;
        }
        case 'month': {
          const end = new Date(today); end.setMonth(today.getMonth() + 1);
          if (!due || due < today || due > end) return false; break;
        }
        case 'no-date': if (task.due_date) return false; break;
      }
    }

    return true;
  });
});

const hasActiveFilters = computed(() =>
  searchQuery.value || statusFilter.value !== 'all' || clerkFilter.value !== 'all' || dateFilter.value !== 'all'
);

// ── Actions ────────────────────────────────────────────────────────────────
const clearFilters = () => {
  searchQuery.value  = '';
  statusFilter.value = 'all';
  clerkFilter.value  = 'all';
  dateFilter.value   = 'all';
};

const openAdd  = () => { tm.task = null; tm.mode = 'add';  tm.show = true; };
const openEdit = (t) => { tm.task = { ...t }; tm.mode = 'edit'; tm.show = true; };
const openView = (t) => { tm.task = { ...t }; tm.mode = 'view'; tm.show = true; };

// FIX: Only change the mode — do NOT replace tm.task.
// CaseTaskModal's watcher only reacts to task identity changes, not mode changes,
// so the already-loaded clerk assignment is preserved when switching view → edit.
const switchToEdit = () => { tm.mode = 'edit'; };

const onTaskSave = ({ mode, data }) => {
  if (mode === 'add')  emit('add-task',    data);
  if (mode === 'edit') emit('update-task', data);
  tm.show = false;
};

const toggleDone = (task) => {
  const newStatus = task.status === 'done' ? 'todo' : 'done';
  emit('update-task', { ...task, status: newStatus });
};

watch(() => props.show, (v) => {
  if (v && props.initialMode === 'add') openAdd();
  if (!v) { tm.show = false; clearFilters(); }
});

// ── Helpers ────────────────────────────────────────────────────────────────
const resolveClerk = (id) => {
  if (!id) return '—';
  const found = props.clerks.find(c => c.id === id);
  return found ? (found.name ?? found.full_name ?? '—') : '—';
};

const getInitials = (name) =>
  name && name !== '—'
    ? name.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase()
    : '?';

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
  const today    = new Date(); today.setHours(0, 0, 0, 0);
  const tomorrow = new Date(today); tomorrow.setDate(today.getDate() + 1);
  const due      = new Date(dt); due.setHours(0, 0, 0, 0);
  if (due.getTime() === today.getTime())    return 'Today';
  if (due.getTime() === tomorrow.getTime()) return 'Tomorrow';
  return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const taskStatusLabel = (s) => ({ todo: 'To-do', 'in-progress': 'In Progress', done: 'Done' }[s] ?? s);
const taskStatusClass = (s) => ({
  todo:          'bg-slate-100 text-slate-700 border border-slate-200',
  'in-progress': 'bg-amber-50 text-amber-700 border border-amber-200',
  done:          'bg-emerald-50 text-emerald-700 border border-emerald-200',
}[s] ?? 'bg-slate-100 text-slate-500');
const taskStatusDot = (s) => ({
  todo: 'bg-slate-500', 'in-progress': 'bg-amber-500', done: 'bg-emerald-500',
}[s] ?? 'bg-slate-400');
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to       { opacity: 0; transform: scale(0.98); }

.overflow-y-auto::-webkit-scrollbar       { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>