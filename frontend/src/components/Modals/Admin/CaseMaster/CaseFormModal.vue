<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-50 flex items-start sm:items-center justify-center p-0 sm:p-4" @click.self="close">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div class="relative bg-white w-full sm:rounded-2xl shadow-2xl sm:max-w-5xl max-h-screen sm:max-h-[92vh] flex flex-col overflow-hidden">

          <!-- Header -->
          <div class="flex items-center justify-between px-6 sm:px-8 py-4 sm:py-5 border-b border-slate-100 flex-shrink-0">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center navy-bg-10">
                <svg class="w-5 h-5 text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-lg font-bold text-slate-800">{{ isEditing ? 'Edit Case' : 'Create New Case' }}</h2>
                <p class="text-sm text-slate-500 hidden sm:block">
                  {{ isEditing ? 'Update case information and assignments' : 'Fill in the details. Case code is auto-generated.' }}
                </p>
              </div>
            </div>
            <button @click="close" class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Content -->
          <div class="px-6 sm:px-10 py-6 overflow-y-auto space-y-7">

            <!-- Auto-generated Code Preview -->
            <div v-if="!isEditing" class="flex items-center gap-3 px-4 py-3 rounded-xl border border-dashed border-[#1a4972]/30 navy-bg-5">
              <svg class="w-4 h-4 flex-shrink-0 text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
              </svg>
              <div>
                <p class="text-xs text-slate-500 font-medium">Auto-Generated Case Code</p>
                <p class="text-sm font-bold tracking-widest text-[#1a4972]">{{ previewCode }}</p>
              </div>
            </div>

            <!-- Case Information -->
            <section>
              <p class="text-xs font-bold uppercase tracking-widest mb-4 pb-2 border-b border-slate-100 text-[#1a4972]">Case Information</p>
              
              <!-- Title & Case Number -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <div class="sm:col-span-2">
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Case Title <span class="text-red-500">*</span>
                  </label>
                  <input :value="form.title" @input="$emit('update:form', { ...form, title: $event.target.value })" type="text" placeholder="e.g. Cruz vs. Santos — Civil Case for Annulment"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all"
                    :class="{ 'border-red-400': errors?.title }" />
                  <p v-if="errors?.title" class="text-xs text-red-500 mt-1">{{ errors.title }}</p>
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Case Number <span class="text-red-500">*</span>
                  </label>
                  <input :value="form.case_no" @input="$emit('update:form', { ...form, case_no: $event.target.value })" type="text" placeholder="e.g. Civil Case No. 2024-001"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all"
                    :class="{ 'border-red-400': errors?.case_no }" />
                  <p v-if="errors?.case_no" class="text-xs text-red-500 mt-1">{{ errors.case_no }}</p>
                </div>
              </div>

              <!-- Category & Client -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">Category</label>
                  <select :value="form.category_id" @change="$emit('update:form', { ...form, category_id: $event.target.value })" class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all">
                    <option value="">— Select Category —</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                  </select>
                </div>
                <div class="sm:col-span-2">
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Client <span class="text-red-500">*</span>
                  </label>
                  <div class="flex gap-2">
                    <div class="relative flex-1" ref="clientDropdownRef">
                      <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                      </svg>
                      <input :value="clientSearch" @input="$emit('update:client-search', $event.target.value); $emit('update:client-dropdown-open', true)" @focus="$emit('update:client-dropdown-open', true)"
                        type="text" placeholder="Search client name..."
                        class="w-full pl-9 pr-8 py-2.5 text-sm border rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all"
                        :class="form.client_id ? 'border-[#1a4972] font-medium text-slate-800' : errors?.client_id ? 'border-red-400' : 'border-slate-200 text-slate-500'" />
                      <button v-if="clientSearch || form.client_id" type="button" @click.prevent="$emit('clear-client')" class="absolute right-2.5 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                      </button>
                      
                      <!-- Dropdown -->
                      <Transition name="dropdown">
                        <div v-if="clientDropdownOpen" class="absolute z-20 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                          <div v-if="filteredClients?.length > 0" class="max-h-44 overflow-y-auto">
                            <div v-for="cl in filteredClients" :key="cl.id" @mousedown.prevent="$emit('select-client', cl)"
                              class="flex items-center gap-2.5 px-3.5 py-2.5 cursor-pointer hover:bg-blue-50/70 transition-colors"
                              :class="{ 'bg-blue-50/60': form.client_id === cl.id }">
                              <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0 bg-[#1a4972]">
                                {{ getInitials(cl.full_name) }}
                              </div>
                              <span class="text-sm text-slate-700 flex-1">{{ cl.full_name }}</span>
                              <svg v-if="form.client_id === cl.id" class="w-3.5 h-3.5 flex-shrink-0 text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                              </svg>
                            </div>
                          </div>
                          <div v-else class="px-4 py-4 text-center">
                            <p class="text-xs text-slate-500 mb-0.5">No clients match "<span class="font-medium">{{ clientSearch }}</span>"</p>
                            <p class="text-xs text-slate-400">Click <strong>+</strong> to create a new client</p>
                          </div>
                        </div>
                      </Transition>
                    </div>
                    
                    <button type="button" @click="$emit('open-new-client')" title="Create new client" 
                      class="flex-shrink-0 w-11 h-11 rounded-xl border-2 border-dashed border-[#1a4972]/30 hover:border-[#1a4972] flex items-center justify-center transition-all">
                      <svg class="w-4 h-4 text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                      </svg>
                    </button>
                  </div>
                  <p v-if="errors?.client_id" class="text-xs text-red-500 mt-1">{{ errors.client_id }}</p>
                  <Transition name="fade-slide">
                    <div v-if="newlyCreatedClient" class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-emerald-700">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                      </svg>
                      "{{ newlyCreatedClient }}" created and selected
                    </div>
                  </Transition>
                </div>
              </div>

              <!-- Court/Office & Docket No -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <div class="flex items-center justify-between mb-1.5">
                    <label class="text-sm font-semibold text-slate-700">Court / Office
                        &nbsp;
                        <input type="checkbox" :checked="courtNA" @change="$emit('update:court-na', $event.target.checked)" class="w-3.5 h-3.5 rounded accent-[#1a4972] cursor-pointer" />
                      <span class="text-xs text-slate-500 font-medium">N/A</span>
                    </label>
                  </div>
                  <input :value="form.court_or_office" @input="$emit('update:form', { ...form, court_or_office: $event.target.value })" type="text" :placeholder="courtNA ? 'Not Applicable' : 'e.g. RTC Branch 7, Manila'" :disabled="courtNA"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" />
                </div>
                <div>
                  <div class="flex items-center justify-between mb-1.5">
                    <label class="text-sm font-semibold text-slate-700">Docket No. 
                        &ensp;
                         <input type="checkbox" :checked="docketNA" @change="$emit('update:docket-na', $event.target.checked)" class="w-3.5 h-3.5 rounded accent-[#1a4972] cursor-pointer" />
                      <span class="text-xs text-slate-500 font-medium">N/A</span>
                    </label>
                  </div>
                  <input :value="form.docket_no" @input="$emit('update:form', { ...form, docket_no: $event.target.value })" type="text" :placeholder="docketNA ? 'Not Applicable' : 'e.g. Blue-123'" :disabled="docketNA"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" />
                </div>
              </div>
            </section>

            <!-- Assignment & Priority -->
            <section>
              <p class="text-xs font-bold uppercase tracking-widest mb-4 pb-2 border-b border-slate-100 text-[#1a4972]">Assignment & Priority</p>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Assigned Lawyer <span class="text-red-500">*</span>
                  </label>
                  <select :value="form.assigned_lawyer_id" @change="$emit('update:form', { ...form, assigned_lawyer_id: $event.target.value })" class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all"
                    :class="{ 'border-red-400': errors?.assigned_lawyer_id }">
                    <option value="">— Select Lawyer —</option>
                    <option v-for="u in lawyers" :key="u.id" :value="u.id">{{ u.name }}</option>
                  </select>
                  <p v-if="errors?.assigned_lawyer_id" class="text-xs text-red-500 mt-1">{{ errors.assigned_lawyer_id }}</p>
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">Assigned Clerk</label>
                  <select :value="form.assigned_clerk_id" @change="$emit('update:form', { ...form, assigned_clerk_id: $event.target.value })" class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all">
                    <option value="">— Select Clerk —</option>
                    <option v-for="u in clerks" :key="u.id" :value="u.id">{{ u.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">Priority</label>
                  <select :value="form.priority" @change="$emit('update:form', { ...form, priority: $event.target.value })" class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all">
                    <option value="low">Low</option>
                    <option value="normal">Normal</option>
                    <option value="urgent">Urgent</option>
                  </select>
                </div>
              </div>
            </section>

            <!-- Status & Notes -->
            <section>
              <p class="text-xs font-bold uppercase tracking-widest mb-4 pb-2 border-b border-slate-100 text-[#1a4972]">Status & Notes</p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">Case Status</label>
                  <select :value="form.case_status" @change="$emit('update:form', { ...form, case_status: $event.target.value })" class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all">
                    <option value="active">Active</option>
                    <option value="closed">Closed</option>
                    <option value="archived">Archived</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    {{ isEditing ? 'Current Stage' : 'Current Stage' }}
                  </label>
                  <select :value="form.current_stage_id" @change="$emit('update:form', { ...form, current_stage_id: $event.target.value })" class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all">
                    <option value="">— No stage yet —</option>
                    <option v-for="s in activeStages" :key="s.id" :value="s.id">{{ s.name }}</option>
                  </select>
                  <p v-if="!isEditing" class="text-xs text-slate-400 mt-1">
                    Defaults to the first stage. Use <strong>Change Stage</strong> in Case View for quick updates.
                  </p>
                  <p v-else class="text-xs text-slate-400 mt-1">
                    Changing stage here will also log a history entry.
                  </p>
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Notes <span class="text-slate-400 font-normal text-xs ml-1">(Optional)</span>
                  </label>
                  <textarea :value="form.summary" @input="$emit('update:form', { ...form, summary: $event.target.value })" rows="3" placeholder="Brief summary of the case…"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all resize-none"></textarea>
                </div>
              </div>
            </section>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-end gap-3 px-6 sm:px-10 py-4 border-t border-slate-100 bg-slate-50/50 flex-shrink-0">
            <button @click="close" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 transition-all">
              Cancel
            </button>
            <button @click="$emit('submit')" :disabled="loading" class="px-6 py-2.5 text-sm font-semibold text-white rounded-xl active:scale-95 disabled:opacity-60 flex items-center gap-2 min-w-[130px] justify-center bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] shadow-lg shadow-[#1a4972]/30 hover:shadow-xl transition-all">
              <svg v-if="loading" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ loading ? 'Saving...' : (isEditing ? 'Save Changes' : 'Create Case') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
const props = defineProps({
  show: { type: Boolean, default: false },
  isEditing: { type: Boolean, default: false },
  form: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
  lawyers: { type: Array, default: () => [] },
  clerks: { type: Array, default: () => [] },
  activeStages: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  previewCode: { type: String, default: '' },
  clientSearch: { type: String, default: '' },
  filteredClients: { type: Array, default: () => [] },
  clientDropdownOpen: { type: Boolean, default: false },
  newlyCreatedClient: { type: String, default: '' },
  courtNA: { type: Boolean, default: false },
  docketNA: { type: Boolean, default: false }
});

const emit = defineEmits([
  'close', 'submit', 'update:form', 'update:client-search', 'update:client-dropdown-open',
  'update:court-na', 'update:docket-na', 'open-new-client', 'select-client', 'clear-client'
]);

const clientDropdownRef = ref(null);

const close = () => emit('close');
const getInitials = (name) => name ? name.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase() : '??';

// Close dropdown on outside click
watch(() => props.clientDropdownOpen, (isOpen) => {
  if (isOpen) {
    const handler = (e) => {
      if (clientDropdownRef.value && !clientDropdownRef.value.contains(e.target)) {
        emit('update:client-dropdown-open', false);
      }
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }
});
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.dropdown-enter-active { transition: all 0.15s ease; }
.dropdown-enter-from { opacity: 0; transform: translateY(-6px); }
.dropdown-leave-active { transition: all 0.1s ease; }
.dropdown-leave-to { opacity: 0; }
.fade-slide-enter-active { transition: all 0.3s ease; }
.fade-slide-enter-from { opacity: 0; transform: translateY(-4px); }
.navy-bg-5  { background-color: rgba(26, 73, 114, 0.05); }
.navy-bg-10 { background-color: rgba(26, 73, 114, 0.10); }
</style>