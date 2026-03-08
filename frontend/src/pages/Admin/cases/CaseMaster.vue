<template>
  <div class="min-h-screen p-4 md:p-6 bg-slate-50" style="font-family: 'Segoe UI', sans-serif;">

    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center gap-3 mb-1">
        <div class="w-1 h-8 rounded-full bg-gradient-to-b from-[#1a4972] to-[#2d6db5]"></div>
        <h1 class="text-2xl font-bold tracking-tight text-[#1a4972]">Case Master</h1>
      </div>
      <p class="text-sm ml-4 pl-3 text-slate-500">Create, assign, and track all legal cases</p>
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
            placeholder="Search by case code, title, or client..."
            class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 placeholder-slate-400 transition-all" />
        </div>
        <div class="flex flex-wrap gap-2 sm:gap-3">
          <select v-model="filterStatus" @change="handleFilterChange"
            class="flex-1 sm:flex-none px-3 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] text-slate-600 min-w-[110px]">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="closed">Closed</option>
            <option value="archived">Archived</option>
          </select>
          <select v-model="filterPriority" @change="handleFilterChange"
            class="flex-1 sm:flex-none px-3 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] text-slate-600 min-w-[110px]">
            <option value="">All Priority</option>
            <option value="urgent">Urgent</option>
            <option value="normal">Normal</option>
            <option value="low">Low</option>
          </select>
          <select v-model="filterStage" @change="handleFilterChange"
            class="flex-1 sm:flex-none px-3 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] text-slate-600 min-w-[130px]">
            <option value="">All Stages</option>
            <option v-for="s in stages" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>

          <!-- New Case -->
          <button @click="openCreate"
            class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] shadow-lg shadow-[#1a4972]/30 hover:shadow-xl hover:shadow-[#1a4972]/40 active:scale-95 transition-all whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            New Case
          </button>

          <!-- Export dropdown -->
          <div class="relative" ref="exportDropdownRef">
            <button
              @click="showExportMenu = !showExportMenu"
              :disabled="exportLoading"
              class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-[#1a4972] border border-[#1a4972]/30 bg-white hover:bg-[#1a4972]/5 active:scale-95 transition-all whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed">
              <svg v-if="!exportLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
              <svg v-else class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 11-8 8z"/>
              </svg>
              Export
              <svg class="w-3 h-3 transition-transform" :class="showExportMenu ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <transition name="dropdown">
              <div v-if="showExportMenu"
                class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-100 z-50 overflow-hidden">
                <div class="px-3 py-2 border-b border-slate-100">
                  <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Export filtered results</p>
                </div>
                <button @click="exportCases('xlsx')"
                  class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">
                  <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                  </svg>
                  Excel (.xlsx)
                </button>
                <button @click="exportCases('pdf')"
                  class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-red-50 hover:text-red-700 transition-colors">
                  <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                  </svg>
                  PDF
                </button>
              </div>
            </transition>
          </div>

        </div>
      </div>
    </div>

    <!-- Cases Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">

      <div class="overflow-x-auto relative">
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
            <tr v-for="c in cases" :key="c.id" class="hover:bg-blue-50/30 transition-colors duration-100">
              <td class="px-4 py-4">
                <div class="flex items-center gap-2 mb-0.5">
                  <p class="text-xs font-bold tracking-wider" :class="getCategoryTextClass(c.category)">{{ c.case_code }}</p>
                  <span v-if="c.category && c.category !== '—'" class="px-2 py-0.5 text-[10px] font-semibold rounded-full border" :class="getCategoryBadgeClass(c.category)">{{ c.category }}</span>
                </div>
                <p class="text-sm font-semibold text-slate-800 max-w-[200px] truncate" :title="c.title">{{ c.title }}</p>
                <p class="text-xs text-slate-400 mt-0.5">Case #{{ c.case_no }}</p>
              </td>
              <td class="px-4 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0" :class="getCategoryBgClass(c.category)">{{ getInitials(c.client) }}</div>
                  <span class="text-sm text-slate-700 font-medium whitespace-nowrap">{{ c.client }}</span>
                </div>
              </td>
              <td class="px-4 py-4">
                <div class="flex flex-col gap-1.5">
                  <div class="flex items-center gap-1.5">
                    <div class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold text-white flex-shrink-0 bg-[#1a4972]">{{ getInitials(c.lawyer) }}</div>
                    <span class="text-xs text-slate-400 w-9 flex-shrink-0">Atty.</span>
                    <span class="text-xs text-slate-700 font-medium whitespace-nowrap">{{ c.lawyer }}</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <div class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold text-white flex-shrink-0 bg-slate-400">{{ getInitials(c.clerk) }}</div>
                    <span class="text-xs text-slate-400 w-9 flex-shrink-0">Clerk</span>
                    <span class="text-xs text-slate-700 font-medium whitespace-nowrap">{{ c.clerk }}</span>
                  </div>
                </div>
              </td>
              <td class="px-4 py-4">
                <span v-if="c.stage" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200 whitespace-nowrap">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                  {{ c.stage }}
                </span>
                <span v-else class="text-xs text-slate-400 italic">No stage set</span>
              </td>
              <td class="px-4 py-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg" :class="priorityClass(c.priority)">
                  <span class="w-1.5 h-1.5 rounded-full" :class="priorityDotClass(c.priority)"></span>
                  {{ capitalize(c.priority) }}
                </span>
              </td>
              <td class="px-4 py-4">
                <span class="px-2.5 py-1 text-xs font-semibold rounded-lg" :class="caseStatusClass(c.case_status)">{{ capitalize(c.case_status) }}</span>
              </td>
              <td class="px-4 py-4">
                <div class="flex items-center gap-1">
                  <button @click="openView(c)" class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#1a4972] transition-colors hover-navy-bg">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    View
                  </button>
                  <button @click="openEdit(c)" class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#1a4972] transition-colors hover-navy-bg">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="cases.length === 0">
              <td :colspan="columns.length" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center">
                  <div class="w-14 h-14 rounded-2xl navy-bg-8 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-[#1a4972] opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                  </div>
                  <p class="text-sm font-semibold text-slate-700 mb-1">No cases found</p>
                  <p class="text-xs text-slate-400">Try adjusting your filters or create a new case</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3.5 border-t border-slate-100 bg-slate-50/50">
        <p class="text-xs text-slate-500">
          Showing <span class="font-semibold text-slate-700">{{ pagination.from }}</span>&ndash;<span class="font-semibold text-slate-700">{{ pagination.to }}</span>
          of <span class="font-semibold text-slate-700">{{ pagination.total }}</span> cases
        </p>
        <div class="flex items-center gap-1">
          <button @click="previousPage" :disabled="pagination.current_page === 1"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
            :class="pagination.current_page === 1 ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-200'">
            &laquo; Prev
          </button>
          <button v-for="page in displayedPages" :key="page" @click="goToPage(page)"
            class="w-7 h-7 rounded-lg text-xs font-medium transition-all"
            :class="pagination.current_page === page ? 'bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] text-white' : 'text-slate-600 hover:bg-slate-200'">
            {{ page }}
          </button>
          <button @click="nextPage" :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
            :class="pagination.current_page === pagination.last_page ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-200'">
            Next &raquo;
          </button>
        </div>
      </div>
    </div>

    <!-- MODALS -->
    <CaseForm
      :show="showFormModal"
      :is-editing="isEditing"
      :form-loading="formLoading"
      :form="form"
      :errors="errors"
      :categories="categories"
      :clients="clients"
      :lawyers="lawyers"
      :clerks="clerks"
      :active-stages="activeStages"
      :preview-code="previewCode"
      :newly-created-client="newlyCreatedClient"
      :init-court-n-a="courtNA"
      :init-docket-n-a="docketNA"
      :init-client-search="clientSearchInit"
      @close="closeForm"
      @submit="submitForm"
      @category-change="onCategoryChange"
      @open-new-client="openNewClient"
    />

    <ClientModal
      :show="showNewClientModal"
      :client-saving="clientSaving"
      :client-form="clientForm"
      :client-errors="clientErrors"
      @close="closeNewClient"
      @save="saveNewClient"
    />

    <CaseViewModal
      ref="viewModalRef"
      :show="showViewModal"
      :view-case="viewCase"
      :active-stages="activeStages"
      :stage-history="stageHistory"
      :checklist="checklist"
      :clerks="clerks"
      :folder-history="folderHistory"
      :checklist-history="checklistHistory"
      :current-user="currentUser"
      @close="showViewModal = false"
      @edit="(c) => { openEdit(c); showViewModal = false; }"
      @add-task="addChecklistTask"
      @update-task="updateChecklistTask"
      @delete-task="deleteChecklistTask"
      @update-stage="updateCaseStage"
      @checklist-movement="handleChecklistMovement"
      @folder-movement="handleFolderMovement"
    />

    <CaseStageModal
      :show="showStageModal"
      :stage-saving="stageSaving"
      :stage-form="stageForm"
      :stage-errors="stageErrors"
      :active-stages="activeStages"
      @close="closeStageModal"
      @save="saveStageChange"
    />

    <CaseCategoryModal
      :show="showCategoryModal"
      :category="null"
      :all-categories="categories"
      @close="showCategoryModal = false"
      @saved="onCategoryCreated"
    />

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import api              from '@/services/api';
import * as CaseService   from '@/services/caseService';
import * as ClientService from '@/services/clientService';

import CaseCategoryModal from '@/components/Modals/Admin/CaseMaster/CaseCategoryModal.vue';
import CaseForm          from '@/components/Modals/Admin/CaseMaster/CaseFormModal.vue';
import CaseViewModal     from '@/components/Modals/Admin/CaseMaster/CaseViewModal.vue';
import ClientModal       from '@/components/Modals/Admin/CaseMaster/NewClientModal.vue';

// ── Columns ─────────────────────────────────────────────────────────────────
const columns = [
  { label: 'Case Code / Title', field: 'case_code',   sortable: true  },
  { label: 'Client',            field: 'client',      sortable: true  },
  { label: 'Assigned To',       field: 'lawyer',      sortable: false },
  { label: 'Current Stage',     field: 'stage',       sortable: false },
  { label: 'Priority',          field: 'priority',    sortable: true  },
  { label: 'Case Status',       field: 'case_status', sortable: true  },
  { label: 'Actions',           field: 'actions',     sortable: false },
];

// ── Category colour map ──────────────────────────────────────────────────────
const categoryMap = {
  'criminal':      { text: 'text-red-700',    bg: 'bg-red-600',    badge: 'bg-red-50 text-red-700 border-red-200' },
  'annulment':     { text: 'text-purple-700', bg: 'bg-purple-600', badge: 'bg-purple-50 text-purple-700 border-purple-200' },
  'civil':         { text: 'text-blue-700',   bg: 'bg-blue-600',   badge: 'bg-blue-50 text-blue-700 border-blue-200' },
  'land issues':   { text: 'text-amber-700',  bg: 'bg-amber-600',  badge: 'bg-amber-50 text-amber-700 border-amber-200' },
  'land transfer': { text: 'text-orange-700', bg: 'bg-orange-600', badge: 'bg-orange-50 text-orange-700 border-orange-200' },
  'pending':       { text: 'text-slate-600',  bg: 'bg-slate-500',  badge: 'bg-slate-100 text-slate-600 border-slate-300' },
  'admin':         { text: 'text-indigo-700', bg: 'bg-indigo-600', badge: 'bg-indigo-50 text-indigo-700 border-indigo-200' },
};
const defaultCat = { text: 'text-[#1a4972]', bg: 'bg-[#1a4972]', badge: 'bg-blue-50 text-[#1a4972] border-blue-200' };

const getCategoryEntry      = (cat) => cat && cat !== '—' ? (categoryMap[cat.toLowerCase().trim()] ?? defaultCat) : defaultCat;
const getCategoryTextClass  = (cat) => getCategoryEntry(cat).text;
const getCategoryBgClass    = (cat) => getCategoryEntry(cat).bg;
const getCategoryBadgeClass = (cat) => getCategoryEntry(cat).badge;

// ── State ────────────────────────────────────────────────────────────────────
const categories = ref([]);
const clients    = ref([]);
const lawyers    = ref([]);
const clerks     = ref([]);
const stages     = ref([]);
const cases      = ref([]);

const showCategoryModal = ref(false);
const prevCategoryId    = ref('');

// Filters
const searchQuery    = ref('');
const filterStatus   = ref('');
const filterPriority = ref('');
const filterStage    = ref('');
const sortField      = ref('created_at');
const sortDirection  = ref('desc');
const currentPage    = ref(1);
const pagination     = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 });

// Modals
const showFormModal      = ref(false);
const isEditing          = ref(false);
const editingId          = ref(null);
const formLoading        = ref(false);
const newlyCreatedClient = ref('');
const courtNA            = ref(false);
const docketNA           = ref(false);
const clientSearchInit   = ref('');

// Export
const showExportMenu    = ref(false);
const exportLoading     = ref(false);
const exportDropdownRef = ref(null);

// Form
const defaultForm = () => ({
  case_no: '', title: '', category_id: '', client_id: '',
  court_or_office: '', docket_no: '',
  assigned_lawyer_id: '', assigned_clerk_id: '',
  priority: 'normal', case_status: 'active',
  current_stage_id: '', summary: '',
});
const form   = reactive(defaultForm());
const errors = reactive({ title: '', assigned_lawyer_id: '', case_no: '', client_id: '' });

// Client modal
const showNewClientModal = ref(false);
const clientSaving       = ref(false);
const defaultCF          = () => ({ first_name: '', middle_name: '', last_name: '', contact_no: '', email: '', address: '' });
const clientForm         = reactive(defaultCF());
const clientErrors       = reactive({ first_name: '', last_name: '', email: '', contact_no: '' });

// View modal
const showViewModal    = ref(false);
const viewCase         = ref(null);
const currentUser      = ref(null);
const stageHistory     = ref([]);
const showStageModal   = ref(false);
const stageSaving      = ref(false);
const stageForm        = reactive({ stage_id: '', remarks: '' });
const stageErrors      = reactive({ stage_id: '' });
const checklist        = ref([]);   // kept for legacy prop binding; modal self-fetches
const folderHistory    = ref([]);   // same
const checklistHistory = ref([]);   // same
const viewModalRef     = ref(null);

// ── Computed ─────────────────────────────────────────────────────────────────
const activeStages = computed(() => stages.value.filter(s => s.is_active));

const displayedPages = computed(() => {
  const max = 5, total = pagination.value.last_page || 1, current = pagination.value.current_page || 1;
  const pages = [];
  if (total <= max) {
    for (let i = 1; i <= total; i++) pages.push(i);
  } else {
    let s = Math.max(1, current - 2);
    let e = Math.min(total, s + max - 1);
    if (e - s + 1 < max) s = Math.max(1, e - max + 1);
    for (let i = s; i <= e; i++) pages.push(i);
  }
  return pages;
});

const previewCode = computed(() =>
  `${new Date().getFullYear()}-${String((pagination.value.total || 0) + 1).padStart(4, '0')}`
);

// ── Utilities ────────────────────────────────────────────────────────────────
const toArray = (v) => {
  if (Array.isArray(v)) return v;
  if (v?.data?.data) return v.data.data;
  if (v?.data)       return v.data;
  return [];
};

const getInitials = (n) => n && n !== '—' ? (n.split(' ')[0]?.[0] || '') + (n.split(' ')[1]?.[0] || '') : '??';
const capitalize  = (s) => s ? s[0].toUpperCase() + s.slice(1) : '';

const priorityClass    = (p) => ({ urgent: 'bg-red-50 text-red-700', normal: 'bg-blue-50 text-blue-700', low: 'bg-slate-100 text-slate-600' }[p] || 'bg-slate-100 text-slate-500');
const priorityDotClass = (p) => ({ urgent: 'bg-red-500', normal: 'bg-blue-500', low: 'bg-slate-400' }[p] || 'bg-slate-400');
const caseStatusClass  = (s) => ({ active: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200', closed: 'bg-red-50 text-red-700 ring-1 ring-red-200', archived: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' }[s] || 'bg-slate-100 text-slate-500');

// ── Export ───────────────────────────────────────────────────────────────────
const exportCases = async (format) => {
  showExportMenu.value = false;
  exportLoading.value  = true;
  try {
    const params = new URLSearchParams({ format });
    if (searchQuery.value)    params.set('search',      searchQuery.value);
    if (filterStatus.value)   params.set('case_status', filterStatus.value);
    if (filterPriority.value) params.set('priority',    filterPriority.value);
    if (filterStage.value)    params.set('stage_id',    filterStage.value);
    params.set('sort_by',        sortField.value);
    params.set('sort_direction', sortDirection.value);

    const res  = await api.get(`/admin/cases/export?${params}`, { responseType: 'blob' });
    const mime = format === 'xlsx'
      ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      : 'application/pdf';
    const url  = URL.createObjectURL(new Blob([res.data], { type: mime }));
    const a    = document.createElement('a');
    a.href     = url;
    a.setAttribute('download', `cases_export_${new Date().toISOString().slice(0, 10)}.${format}`);
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  } catch (e) {
    console.error('Export failed:', e);
    alert('Export failed. Please try again.');
  } finally {
    exportLoading.value = false;
  }
};

// ── Category handlers ────────────────────────────────────────────────────────
const onCategoryChange = (val) => {
  if (val === '__add_new__') {
    form.category_id    = prevCategoryId.value;
    showCategoryModal.value = true;
  } else {
    prevCategoryId.value = val;
    form.category_id     = val;
  }
};

const onCategoryCreated = async () => {
  showCategoryModal.value = false;
  try {
    CaseService.clearCategoriesCache();
    const res = await CaseService.getCategories(true);
    categories.value = toArray(res);
    const newest = [...categories.value].sort((a, b) => b.id - a.id)[0];
    if (newest) {
      form.category_id     = String(newest.id);
      prevCategoryId.value = String(newest.id);
    }
  } catch (e) {
    console.error('Failed to reload categories:', e);
  }
};

// ── Cases loader ─────────────────────────────────────────────────────────────
const casesCacheKey = () =>
  'cm_cases_' + JSON.stringify({
    s:  searchQuery.value    || '',
    st: filterStatus.value   || '',
    p:  filterPriority.value || '',
    sg: filterStage.value    || '',
    sf: sortField.value,
    sd: sortDirection.value,
    pg: currentPage.value,
  });

const applyCasesResponse = (responseData) => {
  const data = responseData.data ?? [];
  const m    = responseData.meta ?? {};
  cases.value = CaseService.formatCases(data);
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
};

const loadCases = async () => {
  const key = casesCacheKey();
  // Show stale data immediately while fresh request runs
  try {
    const cached = sessionStorage.getItem(key);
    if (cached) applyCasesResponse(JSON.parse(cached));
  } catch (_) {}

  try {
    const res  = await CaseService.getCases({
      search:         searchQuery.value    || undefined,
      case_status:    filterStatus.value   || undefined,
      priority:       filterPriority.value || undefined,
      stage_id:       filterStage.value    || undefined,
      sort_by:        sortField.value,
      sort_direction: sortDirection.value,
      page:           currentPage.value,
    });
    const responseData = res.data ?? res;
    applyCasesResponse(responseData);
    try { sessionStorage.setItem(key, JSON.stringify(responseData)); } catch (_) {}
  } catch (e) {
    console.error('loadCases:', e);
    if (!cases.value.length) cases.value = [];
  }
};

// ── Lookups loader ───────────────────────────────────────────────────────────
const applyLookups = (lookups, clientList) => {
  categories.value = lookups.categories || [];
  stages.value     = lookups.stages     || [];
  const users = lookups.users || [];
  lawyers.value = users.filter(u => u?.role === 'lawyer');
  clerks.value  = users.filter(u => u?.role === 'clerk');
  clients.value = clientList || [];
};

const loadLookups = async () => {
  // Show from sessionStorage while network catches up
  try {
    const cachedL = sessionStorage.getItem('cm_lookups');
    const cachedC = sessionStorage.getItem('cm_clients');
    if (cachedL) applyLookups(JSON.parse(cachedL), cachedC ? JSON.parse(cachedC) : []);
  } catch (_) {}

  try {
    // Parallel: lookups + clients together
    const [lookups, clientRes] = await Promise.all([
      CaseService.loadAllLookups(),
      ClientService.getAll(),
    ]);
    const clientList = toArray(clientRes);
    applyLookups(lookups, clientList);
    try {
      sessionStorage.setItem('cm_lookups', JSON.stringify(lookups));
      sessionStorage.setItem('cm_clients', JSON.stringify(clientList));
    } catch (_) {}
  } catch (e) {
    console.error('Failed to load lookups:', e);
  }
};

// Only refreshes users (not categories/stages) — called on modal open
const refreshUsers = async () => {
  try {
    CaseService.clearUsersCache();
    const res   = await CaseService.getAssignableUsers(true);
    const users = res.data || [];
    lawyers.value = users.filter(u => u?.role === 'lawyer');
    clerks.value  = users.filter(u => u?.role === 'clerk');
    sessionStorage.removeItem('cm_lookups');
  } catch (e) {
    console.error('refreshUsers:', e);
  }
};

// Refresh users when any modal opens (ensures fresh lawyer/clerk lists)
watch(showFormModal, (isOpen) => { if (isOpen) refreshUsers(); });
watch(showViewModal, (isOpen) => { if (isOpen) refreshUsers(); });

// ── Stage history / checklist / trackers ─────────────────────────────────────
const unwrapTask = (res) => res?.data?.data ?? res?.data ?? res;

const loadStageHistory = async (caseId) => {
  try {
    stageHistory.value = toArray(await CaseService.getStageHistory(caseId));
  } catch (e) {
    console.error('loadStageHistory:', e);
    stageHistory.value = [];
  }
};

// ── Tracker movement handlers ────────────────────────────────────────────────
const handleFolderMovement = async ({ type, from_to, date, purpose, handled_by }) => {
  try {
    await CaseService.createFolderTrackerEntry(viewCase.value.id, {
      type:       type.toUpperCase(),
      from_to:    from_to    || null,
      date:       date       || null,
      purpose:    purpose    || null,
      handled_by: handled_by || null,
    });
    viewCase.value = { ...viewCase.value, is_out: type.toUpperCase() === 'OUT' ? 1 : 0 };
    // Delegate the re-fetch to the modal's own refresh helper
    viewModalRef.value?.refreshFolderTracker();
  } catch (e) {
    console.error('handleFolderMovement:', e);
  }
};

const handleChecklistMovement = async ({ type, taskId, taskName, person, date, purpose, handledBy }) => {
  if (!viewCase.value) return;
  try {
    await CaseService.createChecklistTrackerEntry(viewCase.value.id, {
      type:         type.toUpperCase(),
      checklist_id: taskId    || null,
      from_to:      person    || null,
      date:         date      || null,
      purpose:      purpose   || null,
      handled_by:   handledBy || null,
    });
    // Modal refreshes its own checklist + tracker data
    viewModalRef.value?.refreshChecklistTracker();
    viewModalRef.value?.refreshChecklist();
  } catch (e) {
    console.error('handleChecklistMovement:', e);
  }
};

// ── Checklist CRUD (delegated to modal's internal refresh) ────────────────────
const addChecklistTask = async (taskData) => {
  if (!viewCase.value) return;
  try {
    await CaseService.createChecklistTask(viewCase.value.id, taskData);
    viewModalRef.value?.refreshChecklist();
  } catch (e) {
    console.error('addChecklistTask:', e);
  }
};

const updateChecklistTask = async (taskData) => {
  if (!viewCase.value) return;
  try {
    await CaseService.updateChecklistTask(viewCase.value.id, taskData.id, taskData);
    viewModalRef.value?.refreshChecklist();
  } catch (e) {
    console.error('updateChecklistTask:', e);
  }
};

const deleteChecklistTask = async (taskId) => {
  if (!viewCase.value) return;
  try {
    await CaseService.deleteChecklistTask(viewCase.value.id, taskId);
    viewModalRef.value?.refreshChecklist();
  } catch (e) {
    console.error('deleteChecklistTask:', e);
  }
};

// ── Filter / sort / pagination ────────────────────────────────────────────────
let _searchTimer = null;
const debouncedSearch   = () => { clearTimeout(_searchTimer); _searchTimer = setTimeout(() => { currentPage.value = 1; loadCases(); }, 300); };
const handleFilterChange = () => { currentPage.value = 1; loadCases(); };
const sortBy = (field) => {
  sortDirection.value = sortField.value === field ? (sortDirection.value === 'asc' ? 'desc' : 'asc') : 'asc';
  sortField.value = field;
  loadCases();
};
const previousPage = () => { if (pagination.value.current_page > 1) { currentPage.value--; loadCases(); } };
const nextPage     = () => { if (pagination.value.current_page < pagination.value.last_page) { currentPage.value++; loadCases(); } };
const goToPage     = (page) => { currentPage.value = page; loadCases(); };

// ── Form operations ───────────────────────────────────────────────────────────
const clearErrors = () => Object.assign(errors, { title: '', assigned_lawyer_id: '', case_no: '', client_id: '' });
const closeForm   = () => { showFormModal.value = false; };

const openCreate = () => {
  isEditing.value = false; editingId.value = null;
  newlyCreatedClient.value = ''; courtNA.value = false; docketNA.value = false;
  Object.assign(form, defaultForm());
  form.current_stage_id  = activeStages.value[0]?.id ?? '';
  prevCategoryId.value   = '';
  clientSearchInit.value = '';
  clearErrors();
  showFormModal.value = true;
};

const openEdit = (c) => {
  isEditing.value = true; editingId.value = c.id;
  newlyCreatedClient.value = '';
  Object.assign(form, {
    case_no:            c.case_no,
    title:              c.title,
    category_id:        c.category_id ? String(c.category_id) : '',
    client_id:          c.client_id,
    court_or_office:    c.court_or_office,
    docket_no:          c.docket_no,
    assigned_lawyer_id: c.assigned_lawyer_id,
    assigned_clerk_id:  c.assigned_clerk_id,
    priority:           c.priority,
    case_status:        c.case_status,
    current_stage_id:   c.current_stage_id ?? '',
    summary:            c.summary || '',
  });
  prevCategoryId.value   = c.category_id ? String(c.category_id) : '';
  courtNA.value          = c.court_or_office === 'N/A';
  docketNA.value         = c.docket_no === 'N/A';
  clientSearchInit.value = clients.value?.find(x => x.id === c.client_id)?.full_name || '';
  clearErrors();
  showFormModal.value = true;
};

const validateForm = () => {
  clearErrors(); let ok = true;
  if (!form.title?.trim())      { errors.title              = 'Case title is required'; ok = false; }
  if (!form.case_no)            { errors.case_no            = 'Case number is required'; ok = false; }
  if (!form.assigned_lawyer_id) { errors.assigned_lawyer_id = 'Assign a lawyer'; ok = false; }
  if (!form.client_id)          { errors.client_id          = 'Client is required'; ok = false; }
  return ok;
};

const submitForm = async () => {
  if (!validateForm()) return;
  formLoading.value = true;
  try {
    const payload = {
      ...form,
      category_id:       form.category_id       || null,
      client_id:         form.client_id         || null,
      assigned_clerk_id: form.assigned_clerk_id || null,
      current_stage_id:  form.current_stage_id  || null,
    };
    if (isEditing.value) {
      await CaseService.update(editingId.value, payload);
    } else {
      await CaseService.store(payload);
    }
    // Bust caches and reload
    CaseService.clearCache();
    for (const k of Object.keys(sessionStorage)) { if (k.startsWith('cm_cases_')) sessionStorage.removeItem(k); }
    await loadCases();
    closeForm();
  } catch (e) {
    const errs = e?.response?.data?.errors ?? e?.errors ?? {};
    if (errs.title)              errors.title              = Array.isArray(errs.title)              ? errs.title[0]              : errs.title;
    if (errs.case_no)            errors.case_no            = Array.isArray(errs.case_no)            ? errs.case_no[0]            : errs.case_no;
    if (errs.assigned_lawyer_id) errors.assigned_lawyer_id = Array.isArray(errs.assigned_lawyer_id) ? errs.assigned_lawyer_id[0] : errs.assigned_lawyer_id;
  } finally {
    formLoading.value = false;
  }
};

// ── Client modal ──────────────────────────────────────────────────────────────
const openNewClient  = () => { Object.assign(clientForm, defaultCF()); Object.assign(clientErrors, { first_name: '', last_name: '', email: '', contact_no: '' }); showNewClientModal.value = true; };
const closeNewClient = () => { showNewClientModal.value = false; };

const validateClient = () => {
  Object.assign(clientErrors, { first_name: '', last_name: '', email: '', contact_no: '' });
  let ok = true;
  if (!clientForm.first_name?.trim()) { clientErrors.first_name = 'Required'; ok = false; }
  if (!clientForm.last_name?.trim())  { clientErrors.last_name  = 'Required'; ok = false; }
  if (clientForm.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(clientForm.email)) { clientErrors.email = 'Invalid email'; ok = false; }
  if (clientForm.contact_no?.length > 0 && clientForm.contact_no.length < 10)   { clientErrors.contact_no = 'Min 10 digits'; ok = false; }
  return ok;
};

const saveNewClient = async () => {
  if (!validateClient()) return;
  clientSaving.value = true;
  try {
    const full_name = [clientForm.first_name.trim(), clientForm.middle_name.trim(), clientForm.last_name.trim()].filter(Boolean).join(' ');
    const res       = await ClientService.create({ full_name, contact_no: clientForm.contact_no, email: clientForm.email, address: clientForm.address });
    const nc        = res?.data?.data ?? res?.data ?? res;
    const client    = { ...nc, full_name: nc.full_name ?? full_name };
    clients.value   = [...clients.value, client];
    form.client_id           = client.id;
    clientSearchInit.value   = client.full_name;
    newlyCreatedClient.value = client.full_name;
    sessionStorage.removeItem('cm_clients');
    closeNewClient();
  } catch (e) {
    const errs = e?.response?.data?.errors ?? e?.errors ?? {};
    if (errs.email)      clientErrors.email      = errs.email[0];
    if (errs.contact_no) clientErrors.contact_no = errs.contact_no[0];
  } finally {
    clientSaving.value = false;
  }
};

// ── View modal ────────────────────────────────────────────────────────────────
// Opens instantly — CaseViewModal self-fetches checklist, folder tracker,
// and checklist tracker in parallel internally. Stage history is still
// fetched here since CaseMaster needs it for the stage-change flow.
const openView = (c) => {
  viewCase.value      = c;
  showViewModal.value = true;
  loadStageHistory(c.id);  // non-blocking, fire-and-forget
};

const closeStageModal = () => { showStageModal.value = false; };

const saveStageChange = async () => {
  stageErrors.stage_id = '';
  if (!stageForm.stage_id) { stageErrors.stage_id = 'Please select a stage'; return; }
  stageSaving.value = true;
  try {
    await CaseService.updateStage(viewCase.value.id, { stage_id: stageForm.stage_id, remarks: stageForm.remarks || undefined });
    const stageName = activeStages.value.find(s => s.id == stageForm.stage_id)?.name || '';
    viewCase.value = { ...viewCase.value, current_stage_id: stageForm.stage_id, stage: stageName };
    const idx = cases.value.findIndex(c => c.id === viewCase.value.id);
    if (idx !== -1) cases.value[idx] = { ...cases.value[idx], current_stage_id: stageForm.stage_id, stage: stageName };
    closeStageModal();
    await loadStageHistory(viewCase.value.id);
  } catch (e) {
    stageErrors.stage_id = e?.response?.data?.message ?? 'Failed to update stage.';
  } finally {
    stageSaving.value = false;
  }
};

const updateCaseStage = async ({ stage_id, stage_name }) => {
  if (!viewCase.value) return;
  try {
    await CaseService.updateStage(viewCase.value.id, { stage_id });
    viewCase.value = { ...viewCase.value, current_stage_id: stage_id, stage: stage_name };
    const idx = cases.value.findIndex(c => c.id === viewCase.value.id);
    if (idx !== -1) cases.value[idx] = { ...cases.value[idx], current_stage_id: stage_id, stage: stage_name };
    for (const k of Object.keys(sessionStorage)) { if (k.startsWith('cm_cases_')) sessionStorage.removeItem(k); }
  } catch (e) {
    console.error('updateCaseStage failed:', e);
  } finally {
    viewModalRef.value?.finishStageUpdate();
  }
};

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(() => {
  nextTick(() => {
    // Fire cases + lookups in parallel on mount — no sequential blocking
    Promise.allSettled([loadCases(), loadLookups()]);
    api.get('/user').then(res => { currentUser.value = res.data; }).catch(() => {});
  });

  document.addEventListener('click', (e) => {
    if (exportDropdownRef.value && !exportDropdownRef.value.contains(e.target)) {
      showExportMenu.value = false;
    }
  });
});

onBeforeUnmount(() => {
  clearTimeout(_searchTimer);
});
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.hover-navy-bg:hover { background-color: rgba(26, 73, 114, 0.08); }
.navy-bg-8 { background-color: rgba(26, 73, 114, 0.08); }
.dropdown-enter-active, .dropdown-leave-active { transition: all 0.15s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-4px); }
</style>