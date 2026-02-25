<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-50 flex items-start sm:items-center justify-center p-0 sm:p-4" @click.self="close">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div v-if="caseData" class="relative bg-white w-full sm:rounded-2xl shadow-2xl sm:max-w-4xl max-h-screen sm:max-h-[92vh] flex flex-col overflow-hidden">

          <!-- Header -->
          <div class="flex items-center justify-between px-6 sm:px-8 py-5 sm:py-6 border-b border-slate-100 flex-shrink-0">
            <div class="flex items-center gap-4">
              <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center" :class="getCategoryLightBgClass(caseData.category)">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" :class="getCategoryTextClass(caseData.category)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-bold text-slate-800">Case Profile</h2>
                <p class="text-sm text-slate-500">Full case details and assignments</p>
              </div>
            </div>
            <button @click="close" class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Content -->
          <div class="px-6 sm:px-8 py-6 overflow-y-auto space-y-6">

            <!-- Hero Banner -->
            <div class="rounded-2xl px-6 sm:px-8 py-5 sm:py-6 border-2" :class="[getCategoryBorderClass(caseData.category), getCategoryLightBgClass(caseData.category)]">
              <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2.5 mb-2 flex-wrap">
                    <p class="text-sm font-bold tracking-widest" :class="getCategoryTextClass(caseData.category)">{{ caseData.case_code }}</p>
                    <span v-if="caseData.category" class="px-2.5 py-0.5 text-xs font-semibold rounded-full border" :class="getCategoryBadgeClass(caseData.category)">{{ caseData.category }}</span>
                  </div>
                  <p class="text-xl sm:text-2xl font-bold text-slate-800 leading-snug">{{ caseData.title }}</p>
                  <p class="text-sm text-slate-500 mt-1.5 font-medium">Case No. {{ caseData.case_no }}</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
                  <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold rounded-lg" :class="priorityClass(caseData.priority)">
                    <span class="w-2 h-2 rounded-full" :class="priorityDotClass(caseData.priority)"></span>
                    {{ capitalize(caseData.priority) }}
                  </span>
                  <span class="px-3 py-1.5 text-sm font-semibold rounded-lg" :class="caseStatusClass(caseData.case_status)">{{ capitalize(caseData.case_status) }}</span>
                </div>
              </div>
            </div>

            <!-- Stage Progress Stepper -->
            <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 px-5 py-4">
              <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">Current Stage</p>
              </div>
              <div class="flex flex-wrap gap-1.5">
                <template v-for="(s, i) in activeStages" :key="s.id">
                  <span class="flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold transition-all"
                    :class="s.id === caseData.current_stage_id
                      ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200'
                      : isStageCompleted(s.id) ? 'bg-indigo-100 text-indigo-500 line-through' : 'bg-slate-100 text-slate-400'">
                    <svg v-if="isStageCompleted(s.id)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ s.name }}
                  </span>
                  <svg v-if="i < activeStages.length - 1" class="w-3 h-3 text-slate-300 self-center flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </template>
                <span v-if="!caseData.current_stage_id" class="text-xs text-slate-400 italic self-center">No stage assigned yet</span>
              </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-5">
              <div v-for="f in viewFields" :key="f.label">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1.5">{{ f.label }}</p>
                <p class="text-base font-semibold text-slate-800">{{ f.value }}</p>
              </div>
            </div>

            <!-- Summary -->
            <div v-if="caseData.summary" class="rounded-xl bg-slate-50 border border-slate-200 px-5 sm:px-6 py-4">
              <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Summary / Notes</p>
              <p class="text-base text-slate-700 leading-relaxed">{{ caseData.summary }}</p>
            </div>

            <!-- Stage History Timeline -->
            <div class="rounded-xl border border-slate-200 overflow-hidden">
              <div class="flex items-center justify-between px-5 py-3 bg-slate-50 border-b border-slate-100">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Stage History</p>
                <span class="text-xs text-slate-400">{{ stageHistory.length }} change{{ stageHistory.length !== 1 ? 's' : '' }}</span>
              </div>
              <div v-if="stageHistoryLoading" class="px-5 py-6 flex items-center justify-center gap-2 text-slate-400">
                <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-sm">Loading history…</span>
              </div>
              <div v-else-if="stageHistory.length === 0" class="px-5 py-8 text-center">
                <p class="text-sm text-slate-400">No stage changes recorded yet.</p>
              </div>
              <div v-else class="divide-y divide-slate-50">
                <div v-for="h in stageHistory" :key="h.id" class="flex gap-4 px-5 py-3.5 hover:bg-slate-50/50 transition-colors">
                  <div class="flex flex-col items-center gap-1 flex-shrink-0 pt-0.5">
                    <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center">
                      <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                      </svg>
                    </div>
                    <div class="w-px flex-1 bg-slate-100 min-h-[12px]"></div>
                  </div>
                  <div class="flex-1 pb-1">
                    <div class="flex items-center gap-1.5 flex-wrap mb-1">
                      <span class="text-xs text-slate-400 italic">{{ h.from }}</span>
                      <svg class="w-3 h-3 text-slate-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                      </svg>
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
            <button @click="close" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">Close</button>
            <button @click="$emit('edit', caseData)" class="px-5 py-2.5 text-sm font-semibold text-white rounded-xl active:scale-95 bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] shadow-lg shadow-[#1a4972]/30 transition-all">Edit Case</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  caseData: { type: Object, default: null },
  stageHistory: { type: Array, default: () => [] },
  stageHistoryLoading: { type: Boolean, default: false },
  activeStages: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'edit', 'change-stage']);

// Category styling
const categoryMap = {
  'criminal': { text: 'text-red-700', bg: 'bg-red-600', badge: 'bg-red-50 text-red-700 border-red-200', border: 'border-red-200', lightBg: 'bg-red-50/60' },
  'annulment': { text: 'text-purple-700', bg: 'bg-purple-600', badge: 'bg-purple-50 text-purple-700 border-purple-200', border: 'border-purple-200', lightBg: 'bg-purple-50/60' },
  'civil': { text: 'text-blue-700', bg: 'bg-blue-600', badge: 'bg-blue-50 text-blue-700 border-blue-200', border: 'border-blue-200', lightBg: 'bg-blue-50/60' },
  'land issues': { text: 'text-amber-700', bg: 'bg-amber-600', badge: 'bg-amber-50 text-amber-700 border-amber-200', border: 'border-amber-200', lightBg: 'bg-amber-50/60' },
  'land transfer': { text: 'text-orange-700', bg: 'bg-orange-600', badge: 'bg-orange-50 text-orange-700 border-orange-200', border: 'border-orange-200', lightBg: 'bg-orange-50/60' },
  'pending': { text: 'text-slate-600', bg: 'bg-slate-500', badge: 'bg-slate-100 text-slate-600 border-slate-300', border: 'border-slate-200', lightBg: 'bg-slate-50/60' },
  'admin': { text: 'text-indigo-700', bg: 'bg-indigo-600', badge: 'bg-indigo-50 text-indigo-700 border-indigo-200', border: 'border-indigo-200', lightBg: 'bg-indigo-50/60' },
};
const defaultCat = { text: 'text-[#1a4972]', bg: 'bg-[#1a4972]', badge: 'bg-blue-50 text-[#1a4972] border-blue-200', border: 'border-blue-200', lightBg: 'bg-blue-50/40' };
const getCategoryEntry = (cat) => cat ? (categoryMap[cat.toLowerCase().trim()] ?? defaultCat) : defaultCat;

const getCategoryTextClass = (cat) => getCategoryEntry(cat).text;
const getCategoryBgClass = (cat) => getCategoryEntry(cat).bg;
const getCategoryBadgeClass = (cat) => getCategoryEntry(cat).badge;
const getCategoryBorderClass = (cat) => getCategoryEntry(cat).border;
const getCategoryLightBgClass = (cat) => getCategoryEntry(cat).lightBg;

const capitalize = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';

const priorityClass = (p) => ({ 
  urgent: 'bg-red-50 text-red-700', normal: 'bg-blue-50 text-blue-700', low: 'bg-slate-100 text-slate-600' 
}[p] || 'bg-slate-100 text-slate-500');

const priorityDotClass = (p) => ({ 
  urgent: 'bg-red-500', normal: 'bg-blue-500', low: 'bg-slate-400' 
}[p] || 'bg-slate-400');

const caseStatusClass = (s) => ({
  active: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
  closed: 'bg-red-50 text-red-700 ring-1 ring-red-200',
  archived: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
}[s] || 'bg-slate-100 text-slate-500');

const isStageCompleted = (stageId) => {
  if (!props.caseData?.current_stage_id) return false;
  const currentIdx = props.activeStages.findIndex(s => s.id === props.caseData.current_stage_id);
  const thisIdx = props.activeStages.findIndex(s => s.id === stageId);
  return thisIdx < currentIdx;
};

const viewFields = computed(() => {
  if (!props.caseData) return [];
  return [
    { label: 'Client', value: props.caseData.client || '—' },
    { label: 'Court / Office', value: props.caseData.court_or_office || '—' },
    { label: 'Docket No.', value: props.caseData.docket_no || '—' },
    { label: 'Assigned Lawyer', value: 'Atty. ' + (props.caseData.lawyer || '—') },
    { label: 'Assigned Clerk', value: props.caseData.clerk || '—' },
    { label: 'Date Filed', value: 'N/A' },
  ];
});

const close = () => emit('close');
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>