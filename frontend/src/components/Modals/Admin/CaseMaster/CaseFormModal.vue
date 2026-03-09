<template>
  <Transition name="modal">
    <div v-if="show" class="fixed inset-0 z-50 flex items-start sm:items-center justify-center p-0 sm:p-4" @click.self="$emit('close')">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
      <div class="relative bg-white w-full sm:rounded-2xl shadow-2xl sm:max-w-5xl max-h-screen sm:max-h-[92vh] flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 sm:px-8 py-4 sm:py-5 border-b border-slate-100 flex-shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center navy-bg-10">
              <svg class="w-5 h-5 text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-800">{{ isEditing ? 'Edit Case' : 'Create New Case' }}</h2>
              <p class="text-sm text-slate-500 hidden sm:block">{{ isEditing ? 'Update case information and assignments' : 'Fill in the details. Case code is auto-generated.' }}</p>
            </div>
          </div>
        
        </div>

        <!-- Body -->
        <div class="px-6 sm:px-10 py-6 overflow-y-auto space-y-7">

          <!-- Auto-code preview -->
          <div v-if="!isEditing" class="flex items-center gap-3 px-4 py-3 rounded-xl border border-dashed border-[#1a4972]/30 navy-bg-5">
            <svg class="w-4 h-4 flex-shrink-0 text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
            <div>
              <p class="text-xs text-slate-500 font-medium">Auto-Generated Case Code</p>
              <p class="text-sm font-bold tracking-widest text-[#1a4972]">{{ previewCode }}</p>
            </div>
          </div>

          <!-- Case Information -->
          <section>
            <p class="text-xs font-bold uppercase tracking-widest mb-4 pb-2 border-b border-slate-100 text-[#1a4972]">Case Information</p>
            <div class="space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">Case Title <span class="text-red-500">*</span></label>
                  <input v-model="form.title" type="text" placeholder="e.g. Cruz vs. Santos — Civil Case for Annulment"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all" :class="{ 'border-red-400': errors.title }" />
                  <p v-if="errors.title" class="text-xs text-red-500 mt-1">{{ errors.title }}</p>
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">Case Number <span class="text-red-500">*</span></label>
                  <input v-model="form.case_no" type="text" placeholder="e.g. Civil Case No. 2024-001"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all" :class="{ 'border-red-400': errors.case_no }" />
                  <p v-if="errors.case_no" class="text-xs text-red-500 mt-1">{{ errors.case_no }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">Category</label>
                  <select
                    :value="form.category_id"
                    @change="$emit('category-change', $event.target.value)"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all">
                    <option value="">— Select Category —</option>
                    <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">{{ cat.name }}</option>
                  </select>
                  <div v-if="form.category_id && !['', '__add_new__'].includes(String(form.category_id))" class="mt-1.5 flex items-center gap-1">
                    <svg class="w-3 h-3 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span class="text-xs font-medium text-emerald-700">{{ categories.find(c => String(c.id) === String(form.category_id))?.name }}</span>
                  </div>
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">Client <span class="text-red-500">*</span></label>
                  <div class="flex gap-2">
                    <div class="relative flex-1" ref="clientDropdownRef">
                      <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                      <input v-model="clientSearch" @focus="clientDropdownOpen = true" @input="clientDropdownOpen = true; if(!clientSearch) clearClient()"
                        type="text" placeholder="Search client name..."
                        class="w-full pl-9 pr-8 py-2.5 text-sm border rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all"
                        :class="form.client_id ? 'border-[#1a4972] font-medium text-slate-800' : errors.client_id ? 'border-red-400' : 'border-slate-200 text-slate-500'" />
                      <button v-if="clientSearch || form.client_id" type="button" @click.prevent="clearClient" class="absolute right-2.5 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                      </button>
                      <Transition name="dropdown">
                        <div v-if="clientDropdownOpen" class="absolute z-20 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                          <div v-if="filteredClients.length > 0" class="max-h-44 overflow-y-auto">
                            <div v-for="cl in filteredClients" :key="cl.id" @mousedown.prevent="selectClient(cl)"
                              class="flex items-center gap-2.5 px-3.5 py-2.5 cursor-pointer hover:bg-blue-50/70 transition-colors" :class="{ 'bg-blue-50/60': form.client_id === cl.id }">
                              <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0 bg-[#1a4972]">{{ getInitials(cl.full_name) }}</div>
                              <span class="text-sm text-slate-700 flex-1">{{ cl.full_name }}</span>
                              <svg v-if="form.client_id === cl.id" class="w-3.5 h-3.5 flex-shrink-0 text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                          </div>
                          <div v-else class="px-4 py-4 text-center">
                            <p class="text-xs text-slate-500 mb-0.5">No clients match "<span class="font-medium">{{ clientSearch }}</span>"</p>
                            <p class="text-xs text-slate-400">Click <strong>+</strong> to create a new client</p>
                          </div>
                        </div>
                      </Transition>
                    </div>
                    <button type="button" @click="$emit('open-new-client')" title="Create new client" class="flex-shrink-0 w-11 h-11 rounded-xl border-2 border-dashed border-[#1a4972]/30 hover:border-[#1a4972] flex items-center justify-center transition-all">
                      <svg class="w-4 h-4 text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    </button>
                  </div>
                  <p v-if="errors.client_id" class="text-xs text-red-500 mt-1">{{ errors.client_id }}</p>
                  <Transition name="fade-slide">
                    <div v-if="newlyCreatedClient" class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-emerald-700">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                      "{{ newlyCreatedClient }}" created and selected
                    </div>
                  </Transition>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
               <div>
                  <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-1.5">
                       <label class="text-sm font-semibold text-slate-700">Court / Office</label>
                       <input type="checkbox" v-model="courtNA" @change="onCourtNAChange" id="courtNACheck"
                        class="w-3.5 h-3.5 rounded accent-[#1a4972] cursor-pointer" />
                      <label for="courtNACheck" class="text-xs text-slate-500 font-medium cursor-pointer select-none">N/A</label>
                    </div>
                  </div>

                  <!-- Active dropdown input -->
                  <div v-if="!courtNA" class="relative" ref="courtDropdownRef">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                      v-model="courtSearch"
                      @focus="courtDropdownOpen = true"
                      @input="courtDropdownOpen = true; if (!courtSearch) clearCourt()"
                      type="text"
                      placeholder="Search or type court / office..."
                      class="w-full pl-9 pr-8 py-2.5 text-sm border rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all"
                      :class="form.court_or_office && form.court_or_office !== 'N/A'
                        ? 'border-[#1a4972] font-medium text-slate-800'
                        : 'border-slate-200 text-slate-500'" />
                    <button v-if="courtSearch || form.court_or_office" type="button" @click.prevent="clearCourt"
                      class="absolute right-2.5 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>

                    <!-- Dropdown list -->
                    <Transition name="dropdown">
                      <div v-if="courtDropdownOpen && (filteredCourts.length > 0 || courtSearch.trim() || courtLoading)"
                        class="absolute z-20 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                        <div class="max-h-48 overflow-y-auto">

                          <!-- Loading state -->
                          <div v-if="courtLoading" class="px-4 py-3 flex items-center gap-2 text-xs text-slate-400">
                            <svg class="animate-spin w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Loading courts...
                          </div>

                          <!-- Matching options -->
                          <template v-if="!courtLoading">
                            <div v-for="court in filteredCourts" :key="court.id ?? court.name"
                              @mousedown.prevent="selectCourt(court)"
                              class="flex items-center gap-2.5 px-3.5 py-2.5 cursor-pointer hover:bg-blue-50/70 transition-colors"
                              :class="{ 'bg-blue-50/60': form.court_or_office === court.name }">
                              <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                              <span class="text-sm text-slate-700 flex-1">{{ court.name }}</span>
                              <svg v-if="form.court_or_office === court.name" class="w-3.5 h-3.5 text-[#1a4972] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>

                            <!-- "Use custom" option when typed text doesn't exactly match any option -->
                            <div v-if="courtSearch.trim() && !exactCourtMatch"
                              @mousedown.prevent="useCustomCourt"
                              class="flex items-center gap-2.5 px-3.5 py-2.5 cursor-pointer hover:bg-indigo-50/70 transition-colors border-t border-slate-100">
                              <svg class="w-3.5 h-3.5 text-[#1a4972] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                              <span class="text-sm flex-1">Use "<span class="font-bold text-[#1a4972]">{{ courtSearch.trim() }}</span>"</span>
                            </div>

                            <!-- Empty hint when nothing typed -->
                            <div v-if="filteredCourts.length === 0 && !courtSearch.trim()" class="px-4 py-4 text-center">
                              <p class="text-xs text-slate-400">Type to search or enter a custom court / office</p>
                            </div>
                          </template>
                        </div>
                      </div>
                    </Transition>
                  </div>

                  <!-- N/A disabled input -->
                  <input v-else type="text" value="Not Applicable" disabled
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-100 text-slate-400 cursor-not-allowed opacity-60" />

                  <!-- Confirmation pill -->
                  <div v-if="form.court_or_office && form.court_or_office !== 'N/A'" class="mt-1.5 flex items-center gap-1">
                    <svg class="w-3 h-3 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span class="text-xs font-medium text-emerald-700 truncate max-w-[220px]">{{ form.court_or_office }}</span>
                  </div>
                </div>

                <div>
                  <div class="flex items-center justify-between mb-1.5">
                    <label class="text-sm font-semibold text-slate-700">Docket No.
                     &ensp;
                      <input type="checkbox" v-model="docketNA" @change="onDocketNAChange" class="w-3.5 h-3.5 rounded accent-[#1a4972] cursor-pointer" />
                      <span class="text-xs text-slate-500 font-medium">N/A</span>
                    </label>
                  </div>
                  <input v-model="form.docket_no" type="text" :placeholder="docketNA ? 'Not Applicable' : 'e.g. Blue-123'" :disabled="docketNA"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" />
                </div>
              </div>
            </div>
          </section>

          <!-- Assignment & Priority -->
          <section>
            <p class="text-xs font-bold uppercase tracking-widest mb-4 pb-2 border-b border-slate-100 text-[#1a4972]">Assignment & Priority</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Assigned Lawyer <span class="text-red-500">*</span></label>
                <select v-model="form.assigned_lawyer_id" class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all" :class="{ 'border-red-400': errors.assigned_lawyer_id }">
                  <option value="">— Select Lawyer —</option>
                  <option v-for="u in lawyers" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
                <p v-if="errors.assigned_lawyer_id" class="text-xs text-red-500 mt-1">{{ errors.assigned_lawyer_id }}</p>
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Assigned Clerk</label>
                <select v-model="form.assigned_clerk_id" class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all">
                  <option value="">— Select Clerk —</option>
                  <option v-for="u in clerks" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Priority</label>
                <select v-model="form.priority" class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all">
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
                <select v-model="form.case_status" class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all">
                  <option value="active">Active</option>
                  <option value="closed">Closed</option>
                  <option value="archived">Archived</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                  {{ isEditing ? 'Current Stage' : 'Initial Stage' }}
                  <span class="text-slate-400 font-normal text-xs ml-1">(Optional)</span>
                </label>
                <select v-model="form.current_stage_id" class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all">
                  <option value="">— No stage yet —</option>
                  <option v-for="s in activeStages" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <p v-if="!isEditing" class="text-xs text-slate-400 mt-1">Defaults to the first stage. Use <strong>Change Stage</strong> in Case View for quick updates.</p>
                <p v-else class="text-xs text-slate-400 mt-1">Changing stage here will also log a history entry.</p>
              </div>
              <div class="sm:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Notes <span class="text-slate-400 font-normal text-xs ml-1">(Optional)</span></label>
                <textarea v-model="form.summary" rows="3" placeholder="Brief summary of the case…"
                  class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all resize-none"></textarea>
              </div>
            </div>
          </section>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 px-6 sm:px-10 py-4 border-t border-slate-100 bg-slate-50/50 flex-shrink-0">
          <button @click="$emit('close')" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 transition-all">Cancel</button>
          <button @click="$emit('submit')" :disabled="formLoading" class="px-6 py-2.5 text-sm font-semibold text-white rounded-xl active:scale-95 disabled:opacity-60 flex items-center gap-2 min-w-[130px] justify-center bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] shadow-lg shadow-[#1a4972]/30 hover:shadow-xl transition-all">
            <svg v-if="formLoading" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            {{ formLoading ? 'Saving...' : (isEditing ? 'Save Changes' : 'Create Case') }}
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import api from '@/services/api';

const props = defineProps({
  show:               { type: Boolean, default: false },
  isEditing:          { type: Boolean, default: false },
  formLoading:        { type: Boolean, default: false },
  form:               { type: Object,  required: true },
  errors:             { type: Object,  required: true },
  categories:         { type: Array,   default: () => [] },
  clients:            { type: Array,   default: () => [] },
  lawyers:            { type: Array,   default: () => [] },
  clerks:             { type: Array,   default: () => [] },
  activeStages:       { type: Array,   default: () => [] },
  previewCode:        { type: String,  default: '' },
  newlyCreatedClient: { type: String,  default: '' },
  initCourtNA:        { type: Boolean, default: false },
  initDocketNA:       { type: Boolean, default: false },
  initClientSearch:   { type: String,  default: '' },
});

const emit = defineEmits(['close', 'submit', 'category-change', 'open-new-client']);

// ==================== COURT DROPDOWN STATE ====================
const courts = ref([]);
const courtSearch = ref('');
const courtDropdownOpen = ref(false);
const courtDropdownRef = ref(null);
const courtLoading = ref(false);

// ==================== CLIENT DROPDOWN STATE ====================
const courtNA = ref(props.initCourtNA);
const docketNA = ref(props.initDocketNA);
const clientSearch = ref(props.initClientSearch);
const clientDropdownOpen = ref(false);
const clientDropdownRef = ref(null);

// Keep clientSearch in sync when parent injects a newly-created client name
watch(() => props.initClientSearch, (v) => { if (v) clientSearch.value = v; });

// ── Public method: called by parent right before showing the modal ─────────
// Replaces watch(props.show) — zero delay, no watcher overhead.
const syncFromProps = () => {
  const val      = props.form.court_or_office;
  courtNA.value  = props.initCourtNA  || val === 'N/A';
  docketNA.value = props.initDocketNA || props.form.docket_no === 'N/A';
  courtSearch.value          = (val && val !== 'N/A') ? val : '';
  courtDropdownOpen.value    = false;
  clientSearch.value         = props.initClientSearch || '';
  clientDropdownOpen.value   = false;
  // Reload courts list if it failed to load earlier (no extra call if list is fresh)
  if (courts.value.length === 0 && !courtLoading.value) loadCourts();
};
defineExpose({ syncFromProps });
// ==================== COMPUTED PROPERTIES ====================
const filteredCourts = computed(() => {
  if (!courtSearch.value) return courts.value;
  
  const searchLower = courtSearch.value.toLowerCase().trim();
  return courts.value.filter(court => 
    court.name.toLowerCase().includes(searchLower)
  );
});

const exactCourtMatch = computed(() => {
  if (!courtSearch.value) return false;
  const searchLower = courtSearch.value.toLowerCase().trim();
  return courts.value.some(court => 
    court.name.toLowerCase() === searchLower
  );
});

const filteredClients = computed(() => {
  const q = clientSearch.value.toLowerCase().trim();
  return q ? props.clients.filter(c => c.full_name.toLowerCase().includes(q)) : props.clients;
});

// ==================== METHODS ====================
const getInitials = (n) => n ? n.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase() : '??';

// Court Methods
const loadCourts = async () => {
  courtLoading.value = true;
  try {
    const res = await api.get('/admin/courts-offices');
    // CourtController returns { success: true, data: [...] }
    const list = res.data?.data ?? res.data ?? [];
    courts.value = Array.isArray(list) ? list.filter(c => c.is_active !== false) : [];
  } catch (error) {
    console.error('[CaseFormModal] Failed to load courts:', error);
    courts.value = [];
  } finally {
    courtLoading.value = false;
  }
};

const selectCourt = (court) => {
  props.form.court_or_office = court.name;
  courtSearch.value = court.name;
  courtDropdownOpen.value = false;
  courtNA.value = false; // Ensure N/A is unchecked
};

const clearCourt = () => {
  props.form.court_or_office = '';
  courtSearch.value = '';
  courtDropdownOpen.value = false;
};

const useCustomCourt = () => {
  if (courtSearch.value.trim()) {
    props.form.court_or_office = courtSearch.value.trim();
    courtDropdownOpen.value = false;
    courtNA.value = false; // Ensure N/A is unchecked
  }
};

const onCourtNAChange = () => {
  if (courtNA.value) {
    props.form.court_or_office = 'N/A';
    courtSearch.value = '';
  } else {
    props.form.court_or_office = '';
  }
};

// Client Methods
const selectClient = (cl) => {
  props.form.client_id = cl.id;
  clientSearch.value = cl.full_name;
  clientDropdownOpen.value = false;
};

const clearClient = () => {
  props.form.client_id = '';
  clientSearch.value = '';
  clientDropdownOpen.value = false;
};

// Docket Methods
const onDocketNAChange = () => {
  props.form.docket_no = docketNA.value ? 'N/A' : '';
};

// Click Outside Handlers
const handleClickOutside = (event) => {
  if (courtDropdownRef.value && !courtDropdownRef.value.contains(event.target)) {
    courtDropdownOpen.value = false;
  }
  if (clientDropdownRef.value && !clientDropdownRef.value.contains(event.target)) {
    clientDropdownOpen.value = false;
  }
};

// Eagerly load courts at mount — list is ready before the modal ever opens
onMounted(() => {
  loadCourts();
  document.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside);
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