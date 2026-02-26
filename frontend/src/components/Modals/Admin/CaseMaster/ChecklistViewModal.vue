<template>
  <Transition name="modal">
    <div v-if="show" class="fixed inset-0 z-[60] flex items-start sm:items-center justify-center p-0 sm:p-4" @click.self="$emit('close')">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>

      <div class="relative bg-white w-full sm:rounded-2xl shadow-2xl sm:max-w-3xl max-h-screen sm:max-h-[88vh] flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 sm:px-8 py-5 border-b border-slate-100 flex-shrink-0">
          <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-indigo-100 flex items-center justify-center">
              <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
              </svg>
            </div>
            <div>
              <h2 class="text-xl font-bold text-slate-800">Case Checklist</h2>
              <p class="text-sm text-slate-500">
                <span v-if="viewCase">{{ viewCase.case_code }} · </span>
                <span v-if="checklist.length">{{ checklist.filter(t => t.status === 'done').length }}/{{ checklist.length }} completed</span>
                <span v-else>No tasks yet</span>
              </p>
            </div>
          </div>
          <button @click="$emit('close')" class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-5">

          <!-- Loading -->
          <div v-if="checklistLoading" class="py-16 flex items-center justify-center gap-2 text-slate-400">
            <svg class="animate-spin w-5 h-5" viewBox="0 0 24 24" fill="none">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span class="text-sm">Loading tasks…</span>
          </div>

          <!-- Empty -->
          <div v-else-if="!checklist.length" class="py-20 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
              </svg>
            </div>
            <p class="text-sm font-semibold text-slate-500">No tasks yet</p>
            <p class="text-xs text-slate-400 mt-1">Click <span class="font-semibold text-[#1a4972]">Add Task</span> below to get started.</p>
          </div>

          <!-- Table -->
          <div v-else class="rounded-xl border border-slate-200 overflow-hidden">
            <table class="min-w-full">
              <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                  <th class="px-4 py-2.5 w-8"></th>
                  <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Task</th>
                  <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Status</th>
                  <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400 whitespace-nowrap">Due Date</th>
                  <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400 whitespace-nowrap">Assigned To</th>
                  <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Notes</th>
                  <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <tr v-for="task in checklist" :key="task.id"
                  class="hover:bg-slate-50/60 transition-colors"
                  :class="task.status === 'done' ? 'opacity-60' : ''">

                  <!-- Checkbox -->
                  <td class="px-4 py-3 text-center">
                    <button @click="toggleDone(task)"
                      class="w-5 h-5 rounded flex items-center justify-center border-2 transition-all mx-auto"
                      :class="task.status === 'done' ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 hover:border-emerald-400'">
                      <svg v-if="task.status === 'done'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                      </svg>
                    </button>
                  </td>

                  <!-- Task name -->
                  <td class="px-4 py-3 max-w-[160px]">
                    <p class="text-sm font-medium truncate" :class="task.status === 'done' ? 'line-through text-slate-400' : 'text-slate-700'" :title="task.task">{{ task.task }}</p>
                  </td>

                  <!-- Status badge -->
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full whitespace-nowrap" :class="taskStatusClass(task.status)">
                      <span class="w-1.5 h-1.5 rounded-full" :class="taskStatusDot(task.status)"></span>
                      {{ taskStatusLabel(task.status) }}
                    </span>
                  </td>

                  <!-- Due date -->
                  <td class="px-4 py-3">
                    <span class="text-xs text-slate-600 whitespace-nowrap">{{ formatDate(task.due_date) }}</span>
                  </td>

                  <!-- Assigned to -->
                  <td class="px-4 py-3">
                    <span class="text-xs text-slate-600">{{ task.assigned_to || '—' }}</span>
                  </td>

                  <!-- Notes -->
                  <td class="px-4 py-3 max-w-[140px]">
                    <span class="text-xs text-slate-400 italic truncate block" :title="task.notes">{{ task.notes || '—' }}</span>
                  </td>

                  <!-- Actions -->
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-0.5">
                      <button @click="openEdit(task)" class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold text-[#1a4972] hover:bg-[#1a4972]/8 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Update
                      </button>
                      <button @click="openView(task)" class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold text-slate-500 hover:bg-slate-100 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        View
                      </button>
                      <button @click="$emit('delete-task', task.id)" class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between gap-3 px-6 sm:px-8 py-4 border-t border-slate-100 bg-slate-50/50 flex-shrink-0">
          <button @click="openAdd"
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-[#1a4972] border border-[#1a4972]/20 bg-[#1a4972]/5 rounded-xl hover:bg-[#1a4972]/10 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Add Task
          </button>
          <button @click="$emit('close')" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">Close</button>
        </div>

      </div>
    </div>
  </Transition>

  <!-- Task Modal — z-[70] sits above this modal -->
  <CaseTaskModal
    :show="tm.show"
    :mode="tm.mode"
    :task="tm.task"
    @close="tm.show = false"
    @save="onTaskSave"
    @switch-to-edit="tm.mode = 'edit'"
  />
</template>

<script setup>
import { reactive, watch } from 'vue';
import CaseTaskModal from './CaseTaskModal.vue';

const props = defineProps({
  show:            { type: Boolean, default: false },
  viewCase:        { type: Object,  default: null  },
  checklist:       { type: Array,   default: () => [] },
  checklistLoading:{ type: Boolean, default: false },
  initialMode:     { type: String,  default: 'view' }, // 'view' | 'add'
});

const emit = defineEmits(['close', 'add-task', 'update-task', 'delete-task']);

// Task modal state
const tm = reactive({ show: false, mode: 'add', task: null });

const openAdd  = ()  => { tm.task = null; tm.mode = 'add';  tm.show = true; };
const openEdit = (t) => { tm.task = t;    tm.mode = 'edit'; tm.show = true; };
const openView = (t) => { tm.task = t;    tm.mode = 'view'; tm.show = true; };

const onTaskSave = ({ mode, data }) => {
  if (mode === 'add')  emit('add-task',    data);
  if (mode === 'edit') emit('update-task', data);
  tm.show = false;
};

const toggleDone = (task) =>
  emit('update-task', { ...task, status: task.status === 'done' ? 'todo' : 'done' });

// Auto-open add task form if initialMode is 'add'
watch(() => props.show, (v) => {
  if (v && props.initialMode === 'add') openAdd();
  if (!v) tm.show = false;
});

// Helpers
const taskStatusLabel = (s) => ({ todo: 'To-do', 'in-progress': 'In-progress', done: 'Done' }[s] ?? s);
const taskStatusClass = (s) => ({ todo: 'bg-slate-100 text-slate-600', 'in-progress': 'bg-amber-50 text-amber-700', done: 'bg-emerald-50 text-emerald-700' }[s] ?? 'bg-slate-100 text-slate-500');
const taskStatusDot   = (s) => ({ todo: 'bg-slate-400', 'in-progress': 'bg-amber-400', done: 'bg-emerald-500' }[s] ?? 'bg-slate-400');
const formatDate = (d) => { if (!d) return '—'; const dt = new Date(d); return isNaN(dt) ? d : dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); };
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>