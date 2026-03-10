<template>
  <div class="min-h-screen p-4 md:p-6 bg-slate-50" style="font-family: 'Segoe UI', sans-serif;">

    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center gap-3 mb-1">
        <div class="w-1 h-8 rounded-full bg-gradient-to-b from-[#1a4972] to-[#2d6db5]"></div>
        <h1 class="text-2xl font-bold tracking-tight text-[#1a4972]">Court Master</h1>
      </div>
      <p class="text-sm ml-4 pl-3 text-slate-500">Manage courts and offices used across cases</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <div class="relative flex-1 min-w-0 sm:min-w-[200px]">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
          <input v-model="searchQuery" @input="debouncedSearch" type="text"
            placeholder="Search by name or address..."
            class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 placeholder-slate-400 transition-all" />
        </div>
        <div class="flex flex-wrap gap-2 sm:gap-3">
          <select v-model="filterType" @change="handleFilterChange"
            class="flex-1 sm:flex-none px-3 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] text-slate-600 min-w-[130px]">
            <option value="">All Types</option>
            <option v-for="t in ALLOWED_TYPES" :key="t" :value="t">{{ t }}</option>
          </select>
          <select v-model="filterActive" @change="handleFilterChange"
            class="flex-1 sm:flex-none px-3 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] text-slate-600 min-w-[120px]">
            <option value="">All Status</option>
            <option value="true">Active</option>
            <option value="false">Inactive</option>
          </select>
          <button @click="openCreate"
            class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] shadow-lg shadow-[#1a4972]/30 hover:shadow-xl hover:shadow-[#1a4972]/40 active:scale-95 transition-all whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            New Court
          </button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">

      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead>
            <tr class="border-b border-slate-100 bg-[#1a4972]/[0.04]">
              <th v-for="col in columns" :key="col.field"
                class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 whitespace-nowrap"
                :class="col.sortable ? 'cursor-pointer hover:text-[#1a4972] select-none group' : ''"
                @click="col.sortable ? sortBy(col.field) : null">
                <div class="flex items-center gap-1.5">
                  {{ col.label }}
                  <svg v-if="col.sortable && sortField === col.field" class="w-3.5 h-3.5 text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path :d="sortDirection === 'desc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7'" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <svg v-else-if="col.sortable" class="w-3.5 h-3.5 text-slate-300 group-hover:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>
                  </svg>
                </div>
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-50">
            <tr v-for="court in courts" :key="court.id"
              class="hover:bg-blue-50/30 transition-colors duration-100"
              :class="{ 'opacity-60': isLastItem(court) }">

              <!-- Name -->
              <td class="px-4 py-4">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                    :class="getTypeColor(court.type).avatarBg">
                    <svg class="w-4 h-4" :class="getTypeColor(court.type).avatarIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-2 0h-2M5 21H3m2 0h2M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-slate-800">{{ court.name }}</p>
                  </div>
                </div>
              </td>

              <!-- Type -->
              <td class="px-4 py-4">
                <span v-if="court.type"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg border whitespace-nowrap"
                  :class="getTypeColor(court.type).badge">
                  {{ court.type }}
                </span>
                <span v-else class="text-xs text-slate-400 italic">—</span>
              </td>

              <!-- Address -->
              <td class="px-4 py-4">
                <p v-if="court.address" class="text-xs text-slate-600 max-w-[220px] truncate" :title="court.address">
                  <svg class="w-3 h-3 inline mr-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                  {{ court.address }}
                </p>
                <span v-else class="text-xs text-slate-400 italic">No address</span>
              </td>

              <!-- Status -->
              <td class="px-4 py-4">
                <span class="px-2.5 py-1 text-xs font-semibold rounded-lg"
                  :class="court.is_active
                    ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                    : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200'">
                  {{ court.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>

              <!-- Actions -->
              <td class="px-4 py-4">
                <div class="flex items-center gap-1">
                  <button @click="openEdit(court)"
                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#1a4972] transition-colors hover-navy-bg">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                  </button>
                  <button @click="confirmToggle(court)"
                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                    :class="court.is_active
                      ? 'text-amber-600 hover:bg-amber-50'
                      : 'text-emerald-600 hover:bg-emerald-50'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path v-if="court.is_active" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                      <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ court.is_active ? 'Deactivate' : 'Activate' }}
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="courts.length === 0">
              <td :colspan="columns.length" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center">
                  <div class="w-14 h-14 rounded-2xl navy-bg-8 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-[#1a4972] opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/>
                    </svg>
                  </div>
                  <p class="text-sm font-semibold text-slate-700 mb-1">No courts or offices found</p>
                  <p class="text-xs text-slate-400">Try adjusting your filters or add a new entry</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Sort order info banner (visible when list has items) -->
      <div v-if="courts.length > 1" class="px-4 py-2.5 border-t border-slate-100 bg-blue-50/40">
        <p class="text-[11px] text-slate-500 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-[#1a4972] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          New courts are automatically assigned sort order
          <span class="font-semibold text-[#1a4972]">{{ computedNewSortOrder }}</span>
          and inserted before
          <span class="font-semibold text-slate-700">"Others"</span> which always stays last at sort order 9999.
        </p>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3.5 border-t border-slate-100 bg-slate-50/50">
        <p class="text-xs text-slate-500">
          Showing <span class="font-semibold text-slate-700">{{ pagination.from }}</span>–<span class="font-semibold text-slate-700">{{ pagination.to }}</span>
          of <span class="font-semibold text-slate-700">{{ pagination.total }}</span> entries
        </p>
        <div class="flex items-center gap-1">
          <button @click="previousPage" :disabled="pagination.current_page === 1"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
            :class="pagination.current_page === 1 ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-200'">
            ← Prev
          </button>
          <button v-for="page in displayedPages" :key="page" @click="goToPage(page)"
            class="w-7 h-7 rounded-lg text-xs font-medium transition-all"
            :class="pagination.current_page === page
              ? 'bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] text-white'
              : 'text-slate-600 hover:bg-slate-200'">
            {{ page }}
          </button>
          <button @click="nextPage" :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
            :class="pagination.current_page === pagination.last_page ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-200'">
            Next →
          </button>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- CREATE / EDIT MODAL                                          -->
    <!-- ============================================================ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showFormModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeForm"></div>
          <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">

            <!-- Modal Header -->
            <div class="bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] px-6 py-5">
              <div class="flex items-center justify-between">
                <div>
                  <h2 class="text-lg font-bold text-white">{{ isEditing ? 'Edit Court / Office' : 'New Court / Office' }}</h2>
                </div>
                <button @click="closeForm" class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>


            <!-- Modal Body -->
            <div class="p-6 space-y-4 max-h-[65vh] overflow-y-auto">

              <!-- Name -->
              <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                  Court / Office Name <span class="text-red-500">*</span>
                </label>
                <input v-model="form.name" type="text" placeholder="e.g. Regional Trial Court Branch 1"
                  class="w-full px-3.5 py-2.5 text-sm border rounded-xl focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all"
                  :class="errors.name ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-slate-50'" />
                <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name }}</p>
              </div>

              <!-- Type -->
              <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Type</label>
                <select v-model="form.type"
                  class="w-full px-3.5 py-2.5 text-sm border border-slate-200 bg-slate-50 rounded-xl focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 text-slate-700 transition-all">
                  <option value="">— Select type —</option>
                  <option v-for="t in ALLOWED_TYPES" :key="t" :value="t">{{ t }}</option>
                </select>
              </div>

              <!-- Address -->
              <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Address</label>
                <textarea v-model="form.address" rows="2" placeholder="Full address..."
                  class="w-full px-3.5 py-2.5 text-sm border border-slate-200 bg-slate-50 rounded-xl focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 resize-none transition-all">
                </textarea>
              </div>

              <!-- Contact Info -->
              <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Contact Info</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                  </div>
                  <input v-model="form.contact_info" type="tel" placeholder="e.g. +63 912 345 6789"
                    class="w-full pl-10 pr-3.5 py-2.5 text-sm border border-slate-200 bg-slate-50 rounded-xl focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all" />
                </div>
              </div>

              <!-- Active toggle -->
              <div class="flex items-center pb-0.5">
                <label class="flex items-center gap-2.5 cursor-pointer">
                  <div class="relative">
                    <input type="checkbox" v-model="form.is_active" class="sr-only peer" />
                    <div class="w-10 rounded-full transition-colors peer-checked:bg-[#1a4972] bg-slate-300 peer-focus:ring-2 peer-focus:ring-[#1a4972]/30"
                      style="height: 22px;"></div>
                    <div class="absolute top-0.5 left-0.5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-[18px]"
                      style="width: 18px; height: 18px;"></div>
                  </div>
                  <span class="text-xs font-semibold text-slate-600">Active</span>
                </label>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 flex justify-end gap-2.5">
              <button @click="closeForm"
                class="px-5 py-2 rounded-xl text-sm font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors">
                Cancel
              </button>
              <button @click="submitForm" :disabled="formLoading"
                class="flex items-center gap-2 px-6 py-2 rounded-xl text-sm font-semibold text-white bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] shadow-lg shadow-[#1a4972]/30 hover:shadow-xl active:scale-95 transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                <svg v-if="formLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                {{ formLoading ? 'Saving…' : (isEditing ? 'Save Changes' : 'Create Court') }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ============================================================ -->
    <!-- CONFIRM TOGGLE MODAL                                         -->
    <!-- ============================================================ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showConfirmModal = false"></div>
          <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 text-center">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-amber-50">
              <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800 mb-1">{{ confirmTitle }}</h3>
            <p class="text-sm text-slate-500 mb-6">{{ confirmMessage }}</p>
            <div class="flex gap-3">
              <button @click="showConfirmModal = false"
                class="flex-1 px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                Cancel
              </button>
              <button @click="executeToggle" :disabled="actionLoading"
                class="flex-1 px-4 py-2 rounded-xl text-sm font-semibold text-white transition-all active:scale-95 disabled:opacity-60 bg-amber-500 hover:bg-amber-600">
                {{ actionLoading ? '…' : confirmBtnText }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import * as CourtService from '@/services/courtService';

// ==================== CONSTANTS ====================
const ALLOWED_TYPES = ['Court', 'Prosecutor', 'Agency', 'Other'];

const columns = [
  { label: 'Name',    field: 'name',       sortable: true  },
  { label: 'Type',    field: 'type',       sortable: true  },
  { label: 'Address', field: 'address',    sortable: false },
  { label: 'Status',  field: 'is_active',  sortable: true  },
  { label: 'Actions', field: 'actions',    sortable: false },
];

const TYPE_COLORS = {
  'Court':      { badge: 'bg-blue-50 text-blue-700 border-blue-200',     avatarBg: 'bg-blue-100',   avatarIcon: 'text-blue-600'   },
  'Prosecutor': { badge: 'bg-red-50 text-red-700 border-red-200',        avatarBg: 'bg-red-100',    avatarIcon: 'text-red-600'    },
  'Agency':     { badge: 'bg-amber-50 text-amber-700 border-amber-200',  avatarBg: 'bg-amber-100',  avatarIcon: 'text-amber-600'  },
  'Other':      { badge: 'bg-slate-100 text-slate-600 border-slate-200', avatarBg: 'bg-slate-100',  avatarIcon: 'text-slate-500'  },
};
const DEFAULT_COLOR = { badge: 'bg-slate-100 text-slate-600 border-slate-200', avatarBg: 'bg-slate-100', avatarIcon: 'text-slate-500' };
const getTypeColor  = (type) => type ? (TYPE_COLORS[type] ?? DEFAULT_COLOR) : DEFAULT_COLOR;

// ==================== STATE ====================
const courts    = ref([]);   // paginated (display)
const allCourts = ref([]);   // full unfiltered list sorted by sort_order ASC

const searchQuery   = ref('');
const filterType    = ref('');
const filterActive  = ref('');
const sortField     = ref('sort_order');  // default: sort by sort_order ASC
const sortDirection = ref('asc');
const currentPage   = ref(1);
const pagination    = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 });

// Form
const showFormModal = ref(false);
const isEditing     = ref(false);
const editingId     = ref(null);
const formLoading   = ref(false);

const defaultForm = () => ({ name: '', type: '', address: '', contact_info: '', is_active: true, sort_order: 1, notes: '' });
const form   = reactive(defaultForm());
const errors = reactive({ name: '' });

// Confirm toggle modal
const showConfirmModal = ref(false);
const confirmTitle     = ref('');
const confirmMessage   = ref('');
const confirmBtnText   = ref('');
const actionLoading    = ref(false);
const actionTarget     = ref(null);

const OTHERS_SORT_ORDER = 9999;

/** The "Others" anchor — identified by sort_order 9999 OR name "Others" */
const othersItem = computed(() =>
  allCourts.value.find(c => c.sort_order === OTHERS_SORT_ORDER || c.name === 'Others') ?? null
);

/** All courts except Others, sorted ASC — used to find the next available slot */
const nonOtherCourts = computed(() =>
  allCourts.value
    .filter(c => c.id !== othersItem.value?.id)
    .sort((a, b) => a.sort_order - b.sort_order)
);

/** Highest sort_order among non-Others courts. Falls back to 0 so first court gets 1. */
const maxNonOtherSortOrder = computed(() =>
  nonOtherCourts.value.length
    ? nonOtherCourts.value[nonOtherCourts.value.length - 1].sort_order
    : 0   // 0 + 1 = 1, so the very first court starts at 1
);

const computedNewSortOrder = computed(() =>
  Math.min(maxNonOtherSortOrder.value + 1, OTHERS_SORT_ORDER - 1)
);

// Template aliases
const lastCourtName     = computed(() => othersItem.value?.name ?? 'Others');
const secondToLastOrder = computed(() => computedNewSortOrder.value);

/** True when this row is the permanent "Others" anchor */
const isLastItem = (court) =>
  othersItem.value != null && court.id === othersItem.value.id;

// ==================== COMPUTED ====================
const displayedPages = computed(() => {
  const pages = []; const max = 5; const total = pagination.value.last_page;
  if (total <= max) { for (let i = 1; i <= total; i++) pages.push(i); }
  else {
    let s = Math.max(1, pagination.value.current_page - 2);
    let e = Math.min(total, s + max - 1);
    if (e - s + 1 < max) s = Math.max(1, e - max + 1);
    for (let i = s; i <= e; i++) pages.push(i);
  }
  return pages;
});

// ==================== API ====================

/** Always loads the full list sorted by sort_order ASC for accurate anchor detection */
const loadAllCourts = async () => {
  try {
    const res = await CourtService.getCourts({
      sort_by: 'sort_order',
      sort_direction: 'asc',
      per_page: 9999,
    });
    allCourts.value = (res.data?.data ?? []).map(CourtService.formatCourt);
  } catch (e) { console.error('loadAllCourts:', e); }
};

const loadCourts = async () => {
  try {
    const [res] = await Promise.all([
      CourtService.getCourts({
        ...(searchQuery.value         ? { search:    searchQuery.value  } : {}),
        ...(filterType.value          ? { type:       filterType.value   } : {}),
        ...(filterActive.value !== '' ? { is_active:  filterActive.value } : {}),
        sort_by:        sortField.value,
        sort_direction: sortDirection.value,
        page:           currentPage.value,
        per_page:       pagination.value.per_page,
      }),
      loadAllCourts(),
    ]);
    // Map then client-side sort so the table is always ordered by sort_order ASC,
    // regardless of what the backend returns.
    const mapped = (res.data?.data ?? []).map(CourtService.formatCourt);
    courts.value = sortField.value === 'sort_order'
      ? [...mapped].sort((a, b) =>
          sortDirection.value === 'asc'
            ? a.sort_order - b.sort_order
            : b.sort_order - a.sort_order
        )
      : mapped;
    const m = res.data?.meta ?? {};
    if (m.current_page) {
      pagination.value = {
        current_page: m.current_page,
        last_page:    m.last_page,
        per_page:     m.per_page,
        total:        m.total,
        from:         (m.current_page - 1) * m.per_page + 1,
        to:           Math.min(m.current_page * m.per_page, m.total),
      };
    }
  } catch (e) { console.error('loadCourts:', e); }
};

// ==================== EVENTS ====================
let searchTimer = null;
const debouncedSearch    = () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => { currentPage.value = 1; loadCourts(); }, 300); };
const handleFilterChange = () => { currentPage.value = 1; loadCourts(); };
const sortBy = (field) => {
  sortDirection.value = sortField.value === field
    ? (sortDirection.value === 'asc' ? 'desc' : 'asc')
    : 'asc';
  sortField.value = field;
  // For sort_order, apply immediately client-side (no round-trip needed)
  if (field === 'sort_order') {
    courts.value = [...courts.value].sort((a, b) =>
      sortDirection.value === 'asc'
        ? a.sort_order - b.sort_order
        : b.sort_order - a.sort_order
    );
  } else {
    loadCourts();
  }
};
const previousPage = () => { if (pagination.value.current_page > 1)                          { currentPage.value--; loadCourts(); } };
const nextPage     = () => { if (pagination.value.current_page < pagination.value.last_page) { currentPage.value++; loadCourts(); } };
const goToPage     = (page) => { currentPage.value = page; loadCourts(); };

// ==================== FORM ====================
const clearErrors = () => { errors.name = ''; };
const closeForm   = () => { showFormModal.value = false; };

const openCreate = () => {
  isEditing.value = false; editingId.value = null;
  Object.assign(form, defaultForm());
  clearErrors();
  showFormModal.value = true;
};

const openEdit = (court) => {
  isEditing.value = true; editingId.value = court.id;
  Object.assign(form, {
    name:         court.name,
    type:         court.type         ?? '',
    address:      court.address      ?? '',
    contact_info: court.contact_info ?? '',
    is_active:    court.is_active,
    sort_order:   court.sort_order   ?? 1,
    notes:        court.notes        ?? '',
  });
  clearErrors();
  showFormModal.value = true;
};

const validateForm = () => {
  clearErrors(); let ok = true;
  if (!form.name.trim()) { errors.name = 'Name is required'; ok = false; }
  return ok;
};

/**
 * CREATE: auto-assign sort_order = computedNewSortOrder (maxNonOther + 1, starts at 1).
 * No shifting needed — Others stays at 9999 permanently.
 *
 * Backend contract (CourtController@store) — no increment needed:
 *   Court::create([..., 'sort_order' => $request->sort_order]);
 */
const submitForm = async () => {
  if (!validateForm()) return;
  formLoading.value = true;
  try {
    const payload = {
      ...form,
      type:    form.type    || null,
      address: form.address || null,
      notes:   form.notes   || null,
    };

    if (isEditing.value) {
      await CourtService.update(editingId.value, payload);
    } else {
      // Auto-assign sort_order: starts at 1, increments with each new court
      payload.sort_order = computedNewSortOrder.value;
      await CourtService.store(payload);
    }

    await loadCourts();
    closeForm();
  } catch (e) {
    const errs = e?.response?.data?.errors ?? e?.errors ?? {};
    if (errs.name) errors.name = Array.isArray(errs.name) ? errs.name[0] : errs.name;
  } finally { formLoading.value = false; }
};

// ==================== TOGGLE ACTIVE ====================
const confirmToggle = (court) => {
  actionTarget.value   = court;
  confirmTitle.value   = court.is_active ? 'Deactivate Court?' : 'Activate Court?';
  confirmMessage.value = `"${court.name}" will be ${court.is_active ? 'hidden from case selections' : 'available for case assignments'}.`;
  confirmBtnText.value = court.is_active ? 'Deactivate' : 'Activate';
  showConfirmModal.value = true;
};

const executeToggle = async () => {
  if (!actionTarget.value) return;
  actionLoading.value = true;
  try {
    await CourtService.toggleActive(actionTarget.value.id);
    showConfirmModal.value = false;
    await loadCourts();
  } catch (e) { console.error('executeToggle:', e); }
  finally { actionLoading.value = false; }
};

// ==================== LIFECYCLE ====================
onMounted(() => loadCourts());
onBeforeUnmount(() => clearTimeout(searchTimer));
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .relative, .modal-leave-to .relative { transform: scale(0.96); }
.hover-navy-bg:hover { background-color: rgba(26, 73, 114, 0.08); }
.navy-bg-8 { background-color: rgba(26, 73, 114, 0.08); }
</style>