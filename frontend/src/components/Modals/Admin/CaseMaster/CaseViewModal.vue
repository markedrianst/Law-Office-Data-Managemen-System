<template>
  <Transition name="modal">
    <div v-if="show" class="fixed inset-0 z-50 flex items-start sm:items-center justify-center p-0 sm:p-4" @click.self="$emit('close')">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

      <div v-if="viewCase" class="relative bg-white w-full sm:rounded-2xl shadow-2xl sm:max-w-4xl max-h-screen sm:max-h-[92vh] flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 sm:px-8 py-5 border-b border-slate-100 flex-shrink-0">
          <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center" :class="getCategoryLightBg(viewCase.category)">
              <svg class="w-5 h-5" :class="getCategoryText(viewCase.category)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <div>
              <h2 class="text-xl font-bold text-slate-800">Case Profile</h2>
              <p class="text-sm text-slate-500">Full case details and assignments</p>
            </div>
          </div>
          <button @click="$emit('close')" class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Body -->
        <div class="px-6 sm:px-8 py-6 overflow-y-auto space-y-6">

          <!-- Hero banner -->
          <div class="rounded-2xl px-6 py-5 border-2" :class="getCategoryBorder(viewCase.category) + ' ' + getCategoryLightBg(viewCase.category)">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2.5 mb-2 flex-wrap">
                  <p class="text-sm font-bold tracking-widest" :class="getCategoryText(viewCase.category)">{{ viewCase.case_code }}</p>
                  <span v-if="viewCase.category" class="px-2.5 py-0.5 text-xs font-semibold rounded-full border" :class="getCategoryBadge(viewCase.category)">{{ viewCase.category }}</span>
                </div>
                <p class="text-xl sm:text-2xl font-bold text-slate-800 leading-snug">{{ viewCase.title }}</p>
                <p class="text-sm text-slate-500 mt-1.5">Case No. {{ viewCase.case_no }}</p>
              </div>
              <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold rounded-lg" :class="priorityClass(viewCase.priority)">
                  <span class="w-2 h-2 rounded-full" :class="priorityDot(viewCase.priority)"></span>
                  {{ cap(viewCase.priority) }}
                </span>
                <span class="px-3 py-1.5 text-sm font-semibold rounded-lg" :class="statusClass(viewCase.case_status)">{{ cap(viewCase.case_status) }}</span>
              </div>
            </div>
          </div>

          <!-- Stage pipeline -->
          <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 px-5 py-4">
            <p class="text-xs font-bold uppercase tracking-widest text-indigo-600 mb-3">Current Stage</p>
            <div class="flex flex-wrap gap-1.5">
              <template v-for="(s, i) in activeStages" :key="s.id">
                <span class="flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold transition-all"
                  :class="s.id === viewCase.current_stage_id
                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200'
                    : isCompleted(s.id) ? 'bg-indigo-100 text-indigo-500 line-through' : 'bg-slate-100 text-slate-400'">
                  <svg v-if="isCompleted(s.id)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                  {{ s.name }}
                </span>
                <svg v-if="i < activeStages.length - 1" class="w-3 h-3 text-slate-300 self-center" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </template>
              <span v-if="!viewCase.current_stage_id" class="text-xs text-slate-400 italic">No stage assigned yet.</span>
            </div>
          </div>

          <!-- Case Information -->
      
          <!-- ── Case Information ──────────────────────────────────────── -->
          <div class="rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-100">
              <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Case Information</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-5 px-5 py-5">
              <div>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1.5">Case Code</p>
                <p class="text-base font-semibold text-slate-800">{{ viewCase.case_code || '—' }}</p>
              </div>
              <div class="sm:col-span-1">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1.5">Case Number</p>
                <p class="text-base font-semibold text-slate-800">{{ viewCase.case_no || '—' }}</p>
              </div>
              <div class="sm:col-span-1">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1.5">Case Title</p>
                <p class="text-base font-semibold text-slate-800">{{ viewCase.title || '—' }}</p>
              </div>
               <div class="sm:col-span-1">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1.5">Court/Office</p>
                <p class="text-base font-semibold text-slate-800">{{ viewCase.court_or_office || '—' }}</p>
              </div>
              <div class="sm:col-span-1">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1.5">Docket No.</p>
                <p class="text-base font-semibold text-slate-800">{{ viewCase.docket_no || '—' }}</p>
              </div>
              
              <div>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1.5">Client</p>
                <p class="text-base font-semibold text-slate-800">{{ viewCase.client || '—' }}</p>
              </div>
              <div>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1.5">Lawyer</p>
                <p class="text-base font-semibold text-slate-800">{{ viewCase.lawyer ? 'Atty. ' + viewCase.lawyer : '—' }}</p>
              </div>
              <div>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1.5">Clerk</p>
                <p class="text-base font-semibold text-slate-800">{{ viewCase.clerk || '—' }}</p>
              </div>
              <div>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1.5">Case Category</p>
                <p class="text-base font-semibold text-slate-800">{{ viewCase.category || '—' }}</p>
              </div>
            </div>
          </div>


          <!-- Summary -->
          <div v-if="viewCase.summary" class="rounded-xl bg-slate-50 border border-slate-200 px-5 py-4">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Summary / Notes</p>
            <p class="text-sm text-slate-700 leading-relaxed">{{ viewCase.summary }}</p>
          </div>

          <!-- Checklist Quick Card -->
          <div class="rounded-xl border border-slate-200 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 bg-slate-50">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center">
                  <svg class="w-4.5 h-4.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-bold text-slate-700">Case Checklist</p>
                  <p class="text-xs text-slate-400">
                    <span v-if="checklist.length">
                      {{ checklist.filter(t => t.status === 'done').length }} of {{ checklist.length }} tasks completed
                    </span>
                    <span v-else>No tasks yet</span>
                  </p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <!-- Progress pill -->
            
                <!-- Add Task button -->
                <button @click="openChecklist('add')"
                  class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-[#1a4972] border border-[#1a4972]/20 bg-[#1a4972]/5 hover:bg-[#1a4972]/10 transition-colors">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                  Add Task
                </button>
                <!-- View Checklist button -->
                <button @click="openChecklist('view')"
                  class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] shadow shadow-[#1a4972]/30 hover:opacity-90 transition-all">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  View Checklist
                  <span v-if="checklist.length" class="ml-0.5 px-1.5 py-0.5 rounded-full bg-white/20 text-[10px] font-bold">{{ checklist.length }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Stage History -->
          <div class="rounded-xl border border-slate-200 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 bg-slate-50 border-b border-slate-100">
              <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Stage History</p>
              <span class="text-xs text-slate-400">{{ stageHistory.length }} change{{ stageHistory.length !== 1 ? 's' : '' }}</span>
            </div>
            <div v-if="stageHistoryLoading" class="px-5 py-6 flex items-center justify-center gap-2 text-slate-400">
              <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
              <span class="text-sm">Loading history…</span>
            </div>
            <div v-else-if="!stageHistory.length" class="px-5 py-8 text-center">
              <p class="text-sm text-slate-400">No stage changes recorded yet.</p>
            </div>
            <div v-else class="divide-y divide-slate-50">
              <div v-for="h in stageHistory" :key="h.id" class="flex gap-4 px-5 py-3.5 hover:bg-slate-50/50 transition-colors">
                <div class="flex flex-col items-center gap-1 flex-shrink-0 pt-0.5">
                  <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                  </div>
                  <div class="w-px flex-1 bg-slate-100 min-h-[12px]"></div>
                </div>
                <div class="flex-1 pb-1">
                  <div class="flex items-center gap-1.5 flex-wrap mb-1">
                    <span class="text-xs text-slate-400 italic">{{ h.from }}</span>
                    <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-xs font-bold text-indigo-700">{{ h.to }}</span>
                  </div>
                  <p class="text-xs text-slate-500"><span class="font-semibold text-slate-700">{{ h.changed_by }}</span> · {{ h.time }}</p>
                  <p v-if="h.remarks" class="text-xs text-slate-400 italic mt-0.5">"{{ h.remarks }}"</p>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 px-6 sm:px-8 py-4 border-t border-slate-100 bg-slate-50/50 flex-shrink-0">
          <button @click="$emit('close')" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">Close</button>
          <button @click="$emit('edit', viewCase)" class="px-5 py-2.5 text-sm font-semibold text-white rounded-xl active:scale-95 bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] shadow-lg shadow-[#1a4972]/30 transition-all">Edit Case</button>
        </div>

      </div>
    </div>
  </Transition>

  <!-- Checklist Modal — z-[60] sits above this modal -->
  <CaseChecklistModal
    :show="checklistModalShow"
    :view-case="viewCase"
    :checklist="checklist"
    :checklist-loading="checklistLoading"
    :initial-mode="checklistInitialMode"
    @close="checklistModalShow = false"
    @add-task="$emit('add-task', $event)"
    @update-task="$emit('update-task', $event)"
    @delete-task="$emit('delete-task', $event)"
  />
</template>

<script setup>
import { ref, watch } from 'vue';
import CaseChecklistModal from './ChecklistViewModal.vue';

const props = defineProps({
  show:                { type: Boolean, default: false },
  viewCase:            { type: Object,  default: null  },
  activeStages:        { type: Array,   default: () => [] },
  stageHistory:        { type: Array,   default: () => [] },
  stageHistoryLoading: { type: Boolean, default: false },
  checklist:           { type: Array,   default: () => [] },
  checklistLoading:    { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'edit', 'add-task', 'update-task', 'delete-task']);

// Checklist modal state
const checklistModalShow    = ref(false);
const checklistInitialMode  = ref('view'); // 'view' | 'add'

const openChecklist = (mode = 'view') => {
  checklistInitialMode.value = mode;
  checklistModalShow.value = true;
};

watch(() => props.show, (v) => { if (!v) checklistModalShow.value = false; });

// Helpers
const catMap = {
  'criminal':      { text: 'text-red-700',    badge: 'bg-red-50 text-red-700 border-red-200',         border: 'border-red-200',    lightBg: 'bg-red-50/60'    },
  'annulment':     { text: 'text-purple-700', badge: 'bg-purple-50 text-purple-700 border-purple-200', border: 'border-purple-200', lightBg: 'bg-purple-50/60' },
  'civil':         { text: 'text-blue-700',   badge: 'bg-blue-50 text-blue-700 border-blue-200',       border: 'border-blue-200',   lightBg: 'bg-blue-50/60'   },
  'land issues':   { text: 'text-amber-700',  badge: 'bg-amber-50 text-amber-700 border-amber-200',    border: 'border-amber-200',  lightBg: 'bg-amber-50/60'  },
  'land transfer': { text: 'text-orange-700', badge: 'bg-orange-50 text-orange-700 border-orange-200', border: 'border-orange-200', lightBg: 'bg-orange-50/60' },
  'pending':       { text: 'text-slate-600',  badge: 'bg-slate-100 text-slate-600 border-slate-300',   border: 'border-slate-200',  lightBg: 'bg-slate-50/60'  },
  'admin':         { text: 'text-indigo-700', badge: 'bg-indigo-50 text-indigo-700 border-indigo-200', border: 'border-indigo-200', lightBg: 'bg-indigo-50/60' },
};
const defCat         = { text: 'text-[#1a4972]', badge: 'bg-blue-50 text-[#1a4972] border-blue-200', border: 'border-blue-200', lightBg: 'bg-blue-50/40' };
const cat            = (c) => c ? (catMap[c.toLowerCase().trim()] ?? defCat) : defCat;
const getCategoryText    = (c) => cat(c).text;
const getCategoryBadge   = (c) => cat(c).badge;
const getCategoryBorder  = (c) => cat(c).border;
const getCategoryLightBg = (c) => cat(c).lightBg;

const cap           = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
const priorityClass = (p) => ({ urgent: 'bg-red-50 text-red-700', normal: 'bg-blue-50 text-blue-700', low: 'bg-slate-100 text-slate-600' }[p] || 'bg-slate-100 text-slate-500');
const priorityDot   = (p) => ({ urgent: 'bg-red-500', normal: 'bg-blue-500', low: 'bg-slate-400' }[p] || 'bg-slate-400');
const statusClass   = (s) => ({ active: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200', closed: 'bg-red-50 text-red-700 ring-1 ring-red-200', archived: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' }[s] || 'bg-slate-100 text-slate-500');

const isCompleted = (stageId) => {
  if (!props.viewCase?.current_stage_id) return false;
  const ci = props.activeStages.findIndex(s => s.id === props.viewCase.current_stage_id);
  const ti = props.activeStages.findIndex(s => s.id === stageId);
  return ti < ci;
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>