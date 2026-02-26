<template>
  <Transition name="task-modal">
    <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center p-4" @click.self="$emit('close')">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

      <div class="relative bg-white w-full max-w-lg rounded-2xl shadow-2xl flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center"
              :class="mode === 'add' ? 'bg-[#1a4972]/10' : mode === 'edit' ? 'bg-amber-50' : 'bg-emerald-50'">
              <svg v-if="mode === 'add'" class="w-4 h-4 text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
              </svg>
              <svg v-else-if="mode === 'edit'" class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              <svg v-else class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-800">
                {{ mode === 'add' ? 'Add New Task' : mode === 'edit' ? 'Update Task' : 'Task Details' }}
              </h3>
              <p class="text-xs text-slate-500">
                {{ mode === 'add' ? 'Fill in the task information' : mode === 'edit' ? 'Edit the task details' : 'View-only task information' }}
              </p>
            </div>
          </div>
          <button @click="$emit('close')" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Body -->
        <div class="px-6 py-5 space-y-4">

          <!-- VIEW MODE -->
          <template v-if="mode === 'view'">
            <div class="rounded-xl border border-slate-200 overflow-hidden">
              <div class="bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-3">
                <p class="text-sm font-semibold text-slate-700 flex-1">{{ localTask.task }}</p>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full flex-shrink-0"
                  :class="statusClass(localTask.status)">
                  <span class="w-1.5 h-1.5 rounded-full" :class="statusDot(localTask.status)"></span>
                  {{ statusLabel(localTask.status) }}
                </span>
              </div>
              <div class="grid grid-cols-2 gap-px bg-slate-100">
                <div class="bg-white px-4 py-3">
                  <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Due Date</p>
                  <p class="text-sm font-semibold text-slate-700">{{ formatDate(localTask.due_date) }}</p>
                </div>
                <div class="bg-white px-4 py-3">
                  <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Assigned To</p>
                  <p class="text-sm font-semibold text-slate-700">{{ localTask.assigned_to || '—' }}</p>
                </div>
              </div>
              <div v-if="localTask.notes" class="bg-white px-4 py-3 border-t border-slate-100">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Notes</p>
                <p class="text-sm text-slate-600 italic">{{ localTask.notes }}</p>
              </div>
            </div>
          </template>

          <!-- ADD / EDIT MODE -->
          <template v-else>
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                Task <span class="text-red-400">*</span>
              </label>
              <input v-model="localTask.task" type="text" placeholder="e.g. Draft complaint"
                class="w-full px-3.5 py-2.5 text-sm border rounded-xl focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all"
                :class="errors.task ? 'border-red-300 bg-red-50' : 'border-slate-200'" />
              <p v-if="errors.task" class="text-xs text-red-500 mt-1">{{ errors.task }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Status</label>
                <select v-model="localTask.status"
                  class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 bg-white transition-all">
                  <option value="todo">To-do</option>
                  <option value="in-progress">In-progress</option>
                  <option value="done">Done</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Due Date</label>
                <input v-model="localTask.due_date" type="date"
                  class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 bg-white transition-all" />
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Assigned To</label>
              <input v-model="localTask.assigned_to" type="text" placeholder="e.g. Clerk, Atty. Santos"
                class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all" />
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Notes</label>
              <textarea v-model="localTask.notes" rows="3" placeholder="Optional notes or remarks..."
                class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 resize-none transition-all"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-1">
              <p class="text-xs text-slate-400">Status preview:</p>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full"
                :class="statusClass(localTask.status)">
                <span class="w-1.5 h-1.5 rounded-full" :class="statusDot(localTask.status)"></span>
                {{ statusLabel(localTask.status) }}
              </span>
            </div>
          </template>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/60">
          <button @click="$emit('close')"
            class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
            {{ mode === 'view' ? 'Close' : 'Cancel' }}
          </button>
          <button v-if="mode !== 'view'" @click="handleSave"
            class="px-5 py-2.5 text-sm font-semibold text-white rounded-xl active:scale-95 transition-all bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] shadow-lg shadow-[#1a4972]/20">
            {{ mode === 'add' ? 'Save Task' : 'Save Changes' }}
          </button>
          <button v-if="mode === 'view'" @click="$emit('switch-to-edit')"
            class="px-5 py-2.5 text-sm font-semibold text-white rounded-xl active:scale-95 transition-all bg-gradient-to-br from-amber-500 to-amber-600 shadow-lg shadow-amber-200">
            Edit Task
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  mode: { type: String,  default: 'add' }, // 'add' | 'edit' | 'view'
  task: { type: Object,  default: null  },
});

const emit = defineEmits(['close', 'save', 'switch-to-edit']);

const blank      = () => ({ task: '', status: 'todo', due_date: '', assigned_to: '', notes: '' });
const localTask  = reactive(blank());
const errors     = reactive({ task: '' });

watch(() => [props.show, props.task, props.mode], () => {
  errors.task = '';
  if (props.mode === 'add') {
    Object.assign(localTask, blank());
  } else if (props.task) {
    Object.assign(localTask, {
      task:        props.task.task        ?? '',
      status:      props.task.status      ?? 'todo',
      due_date:    props.task.due_date    ?? '',
      assigned_to: props.task.assigned_to ?? '',
      notes:       props.task.notes       ?? '',
    });
  }
}, { immediate: true });

const handleSave = () => {
  errors.task = '';
  if (!localTask.task.trim()) { errors.task = 'Task name is required.'; return; }
  const payload = { ...localTask };
  if (props.mode === 'edit' && props.task?.id) payload.id = props.task.id;
  emit('save', { mode: props.mode, data: payload });
};

const statusLabel = (s) => ({ todo: 'To-do', 'in-progress': 'In-progress', done: 'Done' }[s] ?? s);
const statusClass = (s) => ({ todo: 'bg-slate-100 text-slate-600', 'in-progress': 'bg-amber-50 text-amber-700', done: 'bg-emerald-50 text-emerald-700' }[s] ?? 'bg-slate-100 text-slate-500');
const statusDot   = (s) => ({ todo: 'bg-slate-400', 'in-progress': 'bg-amber-400', done: 'bg-emerald-500' }[s] ?? 'bg-slate-400');
const formatDate  = (d) => { if (!d) return '—'; const dt = new Date(d); return isNaN(dt) ? d : dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); };
</script>

<style scoped>
.task-modal-enter-active, .task-modal-leave-active { transition: all 0.2s ease; }
.task-modal-enter-from, .task-modal-leave-to { opacity: 0; transform: scale(0.97) translateY(6px); }
</style>