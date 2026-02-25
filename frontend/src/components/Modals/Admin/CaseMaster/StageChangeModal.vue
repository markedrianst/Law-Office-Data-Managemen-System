<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-[70] flex items-start sm:items-center justify-center p-0 sm:p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="close"></div>
        <div class="relative bg-white w-full sm:rounded-2xl shadow-2xl sm:max-w-md overflow-hidden">
          
          <!-- Header -->
          <div class="flex items-center justify-between px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
              </div>
              <div>
                <h2 class="text-base font-bold text-slate-800">Change Stage</h2>
                <p class="text-xs text-slate-500">Update the current stage of this case</p>
              </div>
            </div>
            <button @click="close" class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Content -->
          <div class="px-5 sm:px-6 py-5 space-y-4">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                Select Stage <span class="text-red-500">*</span>
              </label>
              <select :value="stageForm.stage_id" @change="$emit('update:stage-form', { ...stageForm, stage_id: $event.target.value })" 
                class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition-all"
                :class="{ 'border-red-400': stageErrors?.stage_id }">
                <option value="">— Choose stage —</option>
                <option v-for="s in activeStages" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
              <p v-if="stageErrors?.stage_id" class="text-xs text-red-500 mt-1">{{ stageErrors.stage_id }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                Remarks <span class="text-slate-400 font-normal text-xs ml-1">(Optional)</span>
              </label>
              <textarea :value="stageForm.remarks" @input="$emit('update:stage-form', { ...stageForm, remarks: $event.target.value })" rows="3" placeholder="Add notes about this stage change..."
                class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition-all resize-none"></textarea>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-end gap-3 px-5 sm:px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            <button @click="close" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 transition-all">
              Cancel
            </button>
            <button @click="$emit('save')" :disabled="saving" class="px-5 py-2.5 text-sm font-semibold text-white rounded-xl active:scale-95 disabled:opacity-60 flex items-center gap-2 min-w-[130px] justify-center bg-gradient-to-br from-indigo-500 to-indigo-700 shadow-lg shadow-indigo-500/30 transition-all">
              <svg v-if="saving" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ saving ? 'Saving...' : 'Update Stage' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  show: { type: Boolean, default: false },
  activeStages: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false },
  stageForm: { type: Object, required: true },
  stageErrors: { type: Object, default: () => ({}) }
});

defineEmits(['close', 'save', 'update:stage-form']);
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>