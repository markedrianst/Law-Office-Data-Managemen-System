<template>
  <Transition name="modal">
    <div v-if="show" class="fixed inset-0 z-[60] flex items-start sm:items-center justify-center p-0 sm:p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="relative bg-white w-full sm:rounded-2xl shadow-2xl sm:max-w-[520px] overflow-hidden">

        <div class="flex items-center justify-between px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
              <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <div>
              <h2 class="text-base font-bold text-slate-800">New Client</h2>
              <p class="text-xs text-slate-500">Quick-create and auto-assign to this case</p>
            </div>
          </div>
          <button @click="$emit('close')" class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="px-5 sm:px-6 py-5 space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
              <input v-model="clientForm.first_name" type="text" placeholder="First name"
                class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/10 transition-all"
                :class="{ 'border-red-400': clientErrors.first_name }" />
              <p v-if="clientErrors.first_name" class="text-xs text-red-500 mt-1">{{ clientErrors.first_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1.5">Last Name <span class="text-red-500">*</span></label>
              <input v-model="clientForm.last_name" type="text" placeholder="Last name"
                class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/10 transition-all"
                :class="{ 'border-red-400': clientErrors.last_name }" />
              <p v-if="clientErrors.last_name" class="text-xs text-red-500 mt-1">{{ clientErrors.last_name }}</p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Middle Name <span class="text-slate-400 font-normal text-xs ml-1">(Optional)</span></label>
            <input v-model="clientForm.middle_name" type="text" placeholder="Middle name"
              class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-emerald-500 transition-all" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1.5">Contact No. <span class="text-slate-400 font-normal text-xs">(Optional)</span></label>
              <input v-model="clientForm.contact_no"
                @keypress="(e) => { if (!/[0-9]/.test(e.key)) e.preventDefault() }"
                @input="clientForm.contact_no = clientForm.contact_no.replace(/\D/g, '')"
                type="text" inputmode="numeric" maxlength="11" placeholder="09XXXXXXXXX"
                class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-emerald-500 transition-all"
                :class="{ 'border-red-400': clientErrors.contact_no }" />
              <p v-if="clientErrors.contact_no" class="text-xs text-red-500 mt-1">{{ clientErrors.contact_no }}</p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email <span class="text-slate-400 font-normal text-xs">(Optional)</span></label>
              <input v-model="clientForm.email" type="email" placeholder="email@example.com"
                class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-emerald-500 transition-all"
                :class="{ 'border-red-400': clientErrors.email }" />
              <p v-if="clientErrors.email" class="text-xs text-red-500 mt-1">{{ clientErrors.email }}</p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Address <span class="text-slate-400 font-normal text-xs ml-1">(Optional)</span></label>
            <input v-model="clientForm.address" type="text" placeholder="Complete address"
              class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-emerald-500 transition-all" />
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 px-5 sm:px-6 py-4 border-t border-slate-100 bg-slate-50/50">
          <button @click="$emit('close')" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 transition-all">Cancel</button>
          <button @click="$emit('save')" :disabled="clientSaving" class="px-5 py-2.5 text-sm font-semibold text-white rounded-xl active:scale-95 disabled:opacity-60 flex items-center gap-2 min-w-[130px] justify-center bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg shadow-emerald-500/30 transition-all">
            <svg v-if="clientSaving" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            {{ clientSaving ? 'Saving...' : 'Create Client' }}
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup>
defineProps({
  show:         { type: Boolean, default: false },
  clientSaving: { type: Boolean, default: false },
  clientForm:   { type: Object,  required: true },
  clientErrors: { type: Object,  required: true },
});

defineEmits(['close', 'save']);
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>