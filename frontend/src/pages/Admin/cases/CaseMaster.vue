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
          <button @click="openCreate"
            class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] shadow-lg shadow-[#1a4972]/30 hover:shadow-xl hover:shadow-[#1a4972]/40 active:scale-95 transition-all whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            New Case
          </button>
        </div>
      </div>
    </div>

    <!-- Cases Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">

      <!-- Data table -->
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
              <!-- Case Code / Title -->
              <td class="px-4 py-4">
                <div class="flex items-center gap-2 mb-0.5">
                  <p class="text-xs font-bold tracking-wider" :class="getCategoryTextClass(c.category)">{{ c.case_code }}</p>
                  <span v-if="c.category" class="px-2 py-0.5 text-[10px] font-semibold rounded-full border" :class="getCategoryBadgeClass(c.category)">{{ c.category }}</span>
                </div>
                <p class="text-sm font-semibold text-slate-800 max-w-[200px] truncate" :title="c.title">{{ c.title }}</p>
                <p class="text-xs text-slate-400 mt-0.5">Case #{{ c.case_no }}</p>
              </td>
              <!-- Client -->
              <td class="px-4 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0" :class="getCategoryBgClass(c.category)">{{ getInitials(c.client) }}</div>
                  <span class="text-sm text-slate-700 font-medium whitespace-nowrap">{{ c.client }}</span>
                </div>
              </td>
              <!-- Assigned To -->
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
              <!-- Stage -->
              <td class="px-4 py-4">
                <span v-if="c.stage" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200 whitespace-nowrap">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                  {{ c.stage }}
                </span>
                <span v-else class="text-xs text-slate-400 italic">No stage set</span>
              </td>
              <!-- Priority -->
              <td class="px-4 py-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg" :class="priorityClass(c.priority)">
                  <span class="w-1.5 h-1.5 rounded-full" :class="priorityDotClass(c.priority)"></span>
                  {{ capitalize(c.priority) }}
                </span>
              </td>
              <!-- Status -->
              <td class="px-4 py-4">
                <span class="px-2.5 py-1 text-xs font-semibold rounded-lg" :class="caseStatusClass(c.case_status)">{{ capitalize(c.case_status) }}</span>
              </td>
              <!-- Actions -->
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

            <!-- Empty state -->
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

    <!-- ================================================================ -->
    <!-- MODALS                                                            -->
    <!-- ================================================================ -->

    <!-- Create / Edit Case -->
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

    <!-- New Client -->
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
  @close="showViewModal = false"
  @edit="(c) => { openEdit(c); showViewModal = false; }"
  @add-task="addChecklistTask"
  @update-task="updateChecklistTask"
  @delete-task="deleteChecklistTask"
  @update-stage="updateCaseStage"
  @checklist-movement="handleChecklistMovement"
  @folder-movement="handleFolderMovement"
/>

    <!-- Stage Change -->
    <CaseStageModal
      :show="showStageModal"
      :stage-saving="stageSaving"
      :stage-form="stageForm"
      :stage-errors="stageErrors"
      :active-stages="activeStages"
      @close="closeStageModal"
      @save="saveStageChange"
    />

    <!-- Add New Category -->
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
import * as CaseService   from '@/services/caseService';
import * as ClientService from '@/services/clientService';

// Import modals (same as before)
import CaseCategoryModal from '@/components/Modals/Admin/CaseMaster/CaseCategoryModal.vue';
import CaseForm          from '@/components/Modals/Admin/CaseMaster/CaseFormModal.vue';
import CaseViewModal     from '@/components/Modals/Admin/CaseMaster/CaseViewModal.vue';
import ClientModal       from '@/components/Modals/Admin/CaseMaster/NewClientModal.vue';

// ── Columns (static - never changes)
const columns = [
  { label: 'Case Code / Title', field: 'case_code',   sortable: true  },
  { label: 'Client',            field: 'client',      sortable: true  },
  { label: 'Assigned To',       field: 'lawyer',      sortable: false },
  { label: 'Current Stage',     field: 'stage',       sortable: false },
  { label: 'Priority',          field: 'priority',    sortable: true  },
  { label: 'Case Status',       field: 'case_status', sortable: true  },
  { label: 'Actions',           field: 'actions',     sortable: false },
];

// ── Category map (static)
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

const getCategoryEntry    = (cat) => cat ? (categoryMap[cat.toLowerCase().trim()] ?? defaultCat) : defaultCat;
const getCategoryTextClass  = (cat) => getCategoryEntry(cat).text;
const getCategoryBgClass    = (cat) => getCategoryEntry(cat).bg;
const getCategoryBadgeClass = (cat) => getCategoryEntry(cat).badge;

// ── State
const categories = ref([]);
const clients    = ref([]);
const lawyers    = ref([]);
const clerks     = ref([]);
const stages     = ref([]);
const cases      = ref([]);

const showCategoryModal = ref(false);
const prevCategoryId    = ref('');

// Filter state
const searchQuery    = ref('');
const filterStatus   = ref('');
const filterPriority = ref('');
const filterStage    = ref('');
const sortField      = ref('created_at');
const sortDirection  = ref('desc');
const currentPage    = ref(1);
const pagination     = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 });

// Modal states
const showFormModal       = ref(false);
const isEditing           = ref(false);
const editingId           = ref(null);
const formLoading         = ref(false);
const newlyCreatedClient  = ref('');
const courtNA             = ref(false);
const docketNA            = ref(false);
const clientSearchInit    = ref('');

// Form defaults
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
const showViewModal  = ref(false);
const viewCase       = ref(null);
const stageHistory   = ref([]);
const showStageModal = ref(false);
const stageSaving    = ref(false);
const stageForm      = reactive({ stage_id: '', remarks: '' });
const stageErrors    = reactive({ stage_id: '' });
const checklist      = ref([]);
const viewModalRef = ref(null);
const folderHistory = ref([]);
const checklistHistory = ref([]);
// ── Computed
const activeStages = computed(() => {
  const s = stages.value;
  return s && s.length ? s.filter(s => s.is_active) : [];
});

const displayedPages = computed(() => {
  const pages = [];
  const max   = 5;
  const total   = pagination.value?.last_page    || 1;
  const current = pagination.value?.current_page || 1;
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
  `${new Date().getFullYear()}-${String((pagination.value?.total || 0) + 1).padStart(4, '0')}`
);

// ── Utilities
const toArray = (v) => {
  if (Array.isArray(v)) return v;
  if (v?.data?.data) return v.data.data;
  if (v?.data)       return v.data;
  return [];
};

const getInitials  = (n) => n ? (n.split(' ')[0]?.[0] || '') + (n.split(' ')[1]?.[0] || '') : '??';
const capitalize   = (s) => s ? s[0].toUpperCase() + s.slice(1) : '';

const priorityClass = (p) => ({
  urgent: 'bg-red-50 text-red-700',
  normal: 'bg-blue-50 text-blue-700',
  low:    'bg-slate-100 text-slate-600',
}[p] || 'bg-slate-100 text-slate-500');

const priorityDotClass = (p) => ({
  urgent: 'bg-red-500',
  normal: 'bg-blue-500',
  low:    'bg-slate-400',
}[p] || 'bg-slate-400');

const caseStatusClass = (s) => ({
  active:   'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
  closed:   'bg-red-50 text-red-700 ring-1 ring-red-200',
  archived: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
}[s] || 'bg-slate-100 text-slate-500');

// ── Category handlers
const onCategoryChange = (val) => {
  if (val === '__add_new__') {
    form.category_id = prevCategoryId.value;
    showCategoryModal.value = true;
  } else {
    prevCategoryId.value = val;
    form.category_id     = val;
  }
};

const onCategoryCreated = async () => {
  showCategoryModal.value = false;
  try {
    CaseService.clearCache?.();
    const res = await CaseService.getCategories(true);
    categories.value = toArray(res);
    const newest = categories.value.sort((a, b) => b.id - a.id)[0];
    if (newest) {
      form.category_id     = String(newest.id);
      prevCategoryId.value = String(newest.id);
    }
  } catch (e) {
    console.error('Failed to reload categories:', e);
  }
};

// ── Cases loader
// ── Stale-while-revalidate cache key for the current filter state
const casesCacheKey = () =>
  'cm_cases_' + JSON.stringify({
    s: searchQuery.value    || '',
    st: filterStatus.value  || '',
    p: filterPriority.value || '',
    sg: filterStage.value   || '',
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

  // 1. Paint instantly from sessionStorage if we have cached data
  try {
    const cached = sessionStorage.getItem(key);
    if (cached) applyCasesResponse(JSON.parse(cached));
  } catch (_) {}

  // 2. Fetch fresh data in the background — update silently when ready
  try {
    const params = {
      search:         searchQuery.value    || undefined,
      case_status:    filterStatus.value   || undefined,
      priority:       filterPriority.value || undefined,
      stage_id:       filterStage.value    || undefined,
      sort_by:        sortField.value,
      sort_direction: sortDirection.value,
      page:           currentPage.value,
    };
    const res          = await CaseService.getCases(params);
    const responseData = res.data ?? res;
    applyCasesResponse(responseData);
    // Persist for next visit / navigation
    try { sessionStorage.setItem(key, JSON.stringify(responseData)); } catch (_) {}
  } catch (e) {
    console.error('loadCases:', e);
    if (!cases.value.length) cases.value = [];
  }
};

// ── Lookups loader — fetches users (clerks + lawyers) fresh from the API.
// Called on mount AND every time a modal that has a clerk/lawyer picker opens,
// so newly-added users always appear without a page refresh.
const applyLookups = (lookups, clientList) => {
  categories.value = lookups.categories || [];
  stages.value     = lookups.stages     || [];
  const users = lookups.users || [];
  lawyers.value = users.filter(u => u?.role === 'lawyer');
  clerks.value  = users.filter(u => u?.role === 'clerk');
  clients.value = clientList || [];
};

const loadLookups = async () => {
  // 1. Paint instantly from sessionStorage
  try {
    const cachedL = sessionStorage.getItem('cm_lookups');
    const cachedC = sessionStorage.getItem('cm_clients');
    if (cachedL) applyLookups(JSON.parse(cachedL), cachedC ? JSON.parse(cachedC) : []);
  } catch (_) {}

  // 2. Fetch fresh in background
  try {
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

// HOT-RELOAD: Force-refresh users by passing forceRefresh=true to bust the
// 5-minute cache in caseService.js. Without this, loadAllLookups() returns
// stale data for up to 5 minutes after a new clerk/lawyer is added.
const refreshUsers = async () => {
  try {
    const res   = await CaseService.getAssignableUsers(true); // true = bust cache
    const users = res.data || [];
    lawyers.value = users.filter(u => u?.role === 'lawyer');
    clerks.value  = users.filter(u => u?.role === 'clerk');
    // Also bust the lookups sessionStorage so stale user lists don't show on next mount
    sessionStorage.removeItem('cm_lookups');
  } catch (e) {
    console.error('refreshUsers:', e);
  }
};

// ── HOT-RELOAD watchers:
// Whenever the CaseForm modal (create/edit) opens, refresh the user list so
// any clerks added in User Management since the last load are available.
watch(showFormModal, (isOpen) => {
  if (isOpen) refreshUsers();
});

// Same for the CaseViewModal — it passes :clerks to the inner TaskModal.
watch(showViewModal, (isOpen) => {
  if (isOpen) refreshUsers();
});

// ── Stage history / checklist
const loadStageHistory = async (caseId) => {
  try {
    const res         = await CaseService.getStageHistory(caseId);
    stageHistory.value = toArray(res);
  } catch (e) {
    console.error('loadStageHistory:', e);
    stageHistory.value = [];
  }
};

const unwrapTask = (res) => res?.data?.data ?? res?.data ?? res;

const loadChecklist = async (caseId) => {
  try {
    const res       = await CaseService.getChecklist(caseId);
    checklist.value = unwrapTask(res) || [];
  } catch (e) {
    console.error('loadChecklist:', e);
    checklist.value = [];
  }
};

const loadFolderTracker = async (caseId) => {
  try {
    const res          = await CaseService.getFolderTracker(caseId);
    folderHistory.value = unwrapTask(res) || [];
  } catch (e) {
    console.error('loadFolderTracker:', e);
    folderHistory.value = [];
  }
};

const loadChecklistTracker = async (caseId) => {
  try {
    const res              = await CaseService.getChecklistTracker(caseId);
    checklistHistory.value = unwrapTask(res) || [];
  } catch (e) {
    console.error('loadChecklistTracker:', e);
    checklistHistory.value = [];
  }
};

const handleFolderMovement = async ({ type, person, date, purpose, handledBy }) => {
  if (!viewCase.value) return;
  try {
    await CaseService.createFolderTrackerEntry(viewCase.value.id, {
      type:       type.toUpperCase(),
      from_to:    person     || null,
      date:       date       || null,
      purpose:    purpose    || null,
      handled_by: handledBy  || null,
    });
    await loadFolderTracker(viewCase.value.id);
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
    await loadChecklistTracker(viewCase.value.id);
  } catch (e) {
    console.error('handleChecklistMovement:', e);
  }
};

// ── Checklist operations (optimistic)
const addChecklistTask = async (taskData) => {
  if (!viewCase.value) return;
  const tempId   = `__temp_${Date.now()}`;
  const tempTask = {
    id: tempId,
    task:               taskData.task,
    status:             taskData.status             ?? 'todo',
    due_date:           taskData.due_date           ?? null,
    assigned_clerk_id:  taskData.assigned_clerk_id  ?? null,
    notes:              taskData.notes              ?? '',
  };
  checklist.value = [...checklist.value, tempTask];
  try {
    const res     = await CaseService.createChecklistTask(viewCase.value.id, taskData);
    const created = unwrapTask(res);
    const idx     = checklist.value.findIndex(t => t.id === tempId);
    if (idx !== -1) checklist.value.splice(idx, 1, created);
  } catch (e) {
    console.error('addChecklistTask:', e);
    checklist.value = checklist.value.filter(t => t.id !== tempId);
  }
};

const updateChecklistTask = async (taskData) => {
  if (!viewCase.value) return;
  const idx  = checklist.value.findIndex(t => t.id === taskData.id);
  const prev = idx !== -1 ? { ...checklist.value[idx] } : null;
  if (idx !== -1) checklist.value.splice(idx, 1, { ...checklist.value[idx], ...taskData });
  try {
    const res     = await CaseService.updateChecklistTask(viewCase.value.id, taskData.id, taskData);
    const updated = unwrapTask(res);
    const i2      = checklist.value.findIndex(t => t.id === taskData.id);
    if (i2 !== -1) checklist.value.splice(i2, 1, updated);
  } catch (e) {
    console.error('updateChecklistTask:', e);
    if (idx !== -1 && prev) checklist.value.splice(idx, 1, prev);
  }
};

const deleteChecklistTask = async (taskId) => {
  if (!viewCase.value) return;
  try {
    await CaseService.deleteChecklistTask(viewCase.value.id, taskId);
    checklist.value = checklist.value.filter(t => t.id !== taskId);
  } catch (e) {
    console.error('deleteChecklistTask:', e);
  }
};

// ── Debounced search + filter events
let searchTimer = null;
const debouncedSearch = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { currentPage.value = 1; loadCases(); }, 300);
};

const handleFilterChange = () => { currentPage.value = 1; loadCases(); };

const sortBy = (field) => {
  sortDirection.value = sortField.value === field
    ? (sortDirection.value === 'asc' ? 'desc' : 'asc')
    : 'asc';
  sortField.value = field;
  loadCases();
};

const previousPage = () => {
  if (pagination.value.current_page > 1) { currentPage.value--; loadCases(); }
};

const nextPage = () => {
  if (pagination.value.current_page < pagination.value.last_page) { currentPage.value++; loadCases(); }
};

const goToPage = (page) => { currentPage.value = page; loadCases(); };

// ── Form operations
const clearErrors = () => {
  errors.title = '';
  errors.assigned_lawyer_id = '';
  errors.case_no  = '';
  errors.client_id = '';
};

const closeForm = () => { showFormModal.value = false; };

const openCreate = () => {
  isEditing.value          = false;
  editingId.value          = null;
  newlyCreatedClient.value = '';
  courtNA.value            = false;
  docketNA.value           = false;
  Object.assign(form, defaultForm());
  form.current_stage_id = activeStages.value[0]?.id ?? '';
  prevCategoryId.value  = '';
  clientSearchInit.value = '';
  clearErrors();
  showFormModal.value = true;   // watcher fires → refreshUsers()
};

const openEdit = (c) => {
  isEditing.value          = true;
  editingId.value          = c.id;
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
  showFormModal.value = true;   // watcher fires → refreshUsers()
};

const validateForm = () => {
  clearErrors();
  let ok = true;
  if (!form.title?.trim())          { errors.title               = 'Case title is required'; ok = false; }
  if (!form.case_no)                { errors.case_no             = 'Case number is required'; ok = false; }
  if (!form.assigned_lawyer_id)     { errors.assigned_lawyer_id  = 'Assign a lawyer'; ok = false; }
  if (!form.client_id)              { errors.client_id           = 'Client is required'; ok = false; }
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
    CaseService.clearCache?.();
    // Bust the sessionStorage cases cache so the table reloads fresh after mutation
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

// ── Client operations
const openNewClient = () => {
  Object.assign(clientForm, defaultCF());
  Object.assign(clientErrors, { first_name: '', last_name: '', email: '', contact_no: '' });
  showNewClientModal.value = true;
};

const closeNewClient = () => { showNewClientModal.value = false; };

const validateClient = () => {
  Object.assign(clientErrors, { first_name: '', last_name: '', email: '', contact_no: '' });
  let ok = true;
  if (!clientForm.first_name?.trim()) { clientErrors.first_name = 'Required'; ok = false; }
  if (!clientForm.last_name?.trim())  { clientErrors.last_name  = 'Required'; ok = false; }
  if (clientForm.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(clientForm.email)) {
    clientErrors.email = 'Invalid email'; ok = false;
  }
  if (clientForm.contact_no?.length > 0 && clientForm.contact_no.length < 10) {
    clientErrors.contact_no = 'Min 10 digits'; ok = false;
  }
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
    form.client_id      = client.id;
    clientSearchInit.value  = client.full_name;
    newlyCreatedClient.value = client.full_name;
    // Bust client sessionStorage so next mount shows the new client
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

// ── View modal operations
const openView = async (c) => {
  viewCase.value      = c;
  showViewModal.value = true;   // watcher fires → refreshUsers()
  await Promise.allSettled([
    loadStageHistory(c.id),
    loadChecklist(c.id),
    loadFolderTracker(c.id),
    loadChecklistTracker(c.id),
  ]);
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

    // Update the modal's viewCase so the dropdown reflects the new value instantly
    viewCase.value = { ...viewCase.value, current_stage_id: stage_id, stage: stage_name };

    // Update the matching row in the cases table
    const idx = cases.value.findIndex(c => c.id === viewCase.value.id);
    if (idx !== -1) {
      cases.value[idx] = { ...cases.value[idx], current_stage_id: stage_id, stage: stage_name };
    }

    // Bust stale sessionStorage cache
    for (const k of Object.keys(sessionStorage)) {
      if (k.startsWith('cm_cases_')) sessionStorage.removeItem(k);
    }
  } catch (e) {
    console.error('updateCaseStage failed:', e);
  } finally {
    // Always stop the spinner in the modal
    viewModalRef.value?.finishStageUpdate();
  }
};

// ── Lifecycle
onMounted(() => {
  // Wait for nextTick so Vue Router's initial navigation is fully settled
  // before firing API requests. Without this, requests fire mid-navigation
  // and get aborted ("Request aborted" AxiosError).
  nextTick(() => {
    loadCases();
    loadLookups();
  });
});

onBeforeUnmount(() => {
  clearTimeout(searchTimer);
});
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.hover-navy-bg:hover { background-color: rgba(26, 73, 114, 0.08); }
.navy-bg-8 { background-color: rgba(26, 73, 114, 0.08); }
</style>