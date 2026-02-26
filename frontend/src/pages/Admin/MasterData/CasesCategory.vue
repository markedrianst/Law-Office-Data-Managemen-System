<template>
  <div class="min-h-screen p-4 md:p-6 bg-slate-50" style="font-family: 'Segoe UI', sans-serif;">

    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center gap-3 mb-1">
        <div class="w-1 h-8 rounded-full bg-gradient-to-b from-[#1a4972] to-[#2d6db5]"></div>
        <h1 class="text-2xl font-bold tracking-tight text-[#1a4972]">Case Category</h1>
      </div>
      <p class="text-sm ml-4 pl-3 text-slate-500">Manage and organize case categories</p>
    </div>

    <!-- Filters & Actions -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <div class="relative flex-1 min-w-0 sm:min-w-[200px]">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
          <input v-model="searchQuery" type="text"
            placeholder="Search categories..."
            class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 placeholder-slate-400 transition-all" />
        </div>
        <div class="flex flex-wrap gap-2 sm:gap-3">
          <select v-model="statusFilter"
            class="flex-1 sm:flex-none px-3 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] text-slate-600 min-w-[120px]">
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <button @click="openForm()"
            class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] shadow-lg shadow-[#1a4972]/30 hover:shadow-xl hover:shadow-[#1a4972]/40 active:scale-95 transition-all whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Add Category
          </button>
        </div>
      </div>
    </div>
    <!-- Toast Notification -->
    <Transition name="toast">
      <div v-if="toast.show"
        class="fixed top-5 right-5 z-[100] flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium min-w-[260px] max-w-sm"
        :class="toast.type === 'success'
          ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
          : 'bg-red-50 text-red-800 border border-red-200'">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path v-if="toast.type === 'success'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ toast.message }}
      </div>
    </Transition>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">

      <!-- Skeleton -->
      <div v-if="loading" class="overflow-x-auto">
        <table class="min-w-full">
          <tbody class="divide-y divide-slate-50">
            <tr v-for="i in 5" :key="i" class="animate-pulse">
              <td class="px-4 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-slate-200 flex-shrink-0"></div>
                  <div class="h-4 w-40 bg-slate-200 rounded"></div>
                </div>
              </td>
              <td class="px-4 py-4"><div class="h-6 w-20 bg-slate-200 rounded-lg"></div></td>
              <td class="px-4 py-4"><div class="h-6 w-16 bg-slate-200 rounded-lg"></div></td>
              <td class="px-4 py-4">
                <div class="flex gap-2">
                  <div class="h-7 w-14 bg-slate-200 rounded-lg"></div>
                  <div class="h-7 w-24 bg-slate-100 rounded-lg"></div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Data -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full">
          <thead>
            <tr class="border-b border-slate-100 bg-[#1a4972]/[0.04]">
              <th v-for="col in columns" :key="col.field"
                class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 whitespace-nowrap">
                {{ col.label }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">

            <tr v-for="cat in filteredCategories" :key="cat.id"
              class="hover:bg-blue-50/30 transition-colors duration-100">

              <!-- Name -->
              <td class="px-4 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg navy-bg-10 flex items-center justify-center text-[#1a4972] flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                  </div>
                  <p class="text-sm font-semibold text-slate-800">{{ cat.name }}</p>
                </div>
              </td>

              <!-- Sort Order -->
              <td class="px-4 py-4">
                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-600">
                  Order {{ cat.sort_order }}
                </span>
              </td>

              <!-- Status -->
              <td class="px-4 py-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg"
                  :class="cat.is_active
                    ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                    : 'bg-red-50 text-red-700 ring-1 ring-red-200'">
                  <span class="w-1.5 h-1.5 rounded-full" :class="cat.is_active ? 'bg-emerald-500' : 'bg-red-500'"></span>
                  {{ cat.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>

              <!-- Actions -->
              <td class="px-4 py-4">
                <div class="flex items-center gap-1">
                  <button @click="openForm(cat)"
                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#1a4972] transition-colors hover-navy-bg">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                  </button>
                  <button @click="handleToggle(cat)"
                    :disabled="togglingId === cat.id"
                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="cat.is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50'">
                    <svg v-if="togglingId === cat.id" class="animate-spin w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path v-if="cat.is_active" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                      <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ togglingId === cat.id ? 'Updating...' : (cat.is_active ? 'Deactivate' : 'Activate') }}
                  </button>
                </div>
              </td>
            </tr>

            <!-- Empty -->
            <tr v-if="filteredCategories.length === 0">
              <td :colspan="columns.length" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center">
                  <div class="w-14 h-14 rounded-2xl navy-bg-8 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-[#1a4972] opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                  </div>
                  <p class="text-sm font-semibold text-slate-700 mb-1">No categories found</p>
                  <p class="text-xs text-slate-400">
                    {{ searchQuery || statusFilter !== 'all' ? 'Try adjusting your filters' : 'Click "Add Category" to get started' }}
                  </p>
                </div>
              </td>
            </tr>

          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div v-if="!loading && filteredCategories.length > 0"
        class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3.5 border-t border-slate-100 bg-slate-50/50">
        <p class="text-xs text-slate-500">
          Showing <span class="font-semibold text-slate-700">{{ filteredCategories.length }}</span>
          of <span class="font-semibold text-slate-700">{{ categories.length }}</span> categories
        </p>
        <div class="flex items-center gap-1">
          <button disabled class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-300 cursor-not-allowed">← Prev</button>
          <button class="w-7 h-7 rounded-lg text-xs font-medium bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] text-white">1</button>
          <button disabled class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-300 cursor-not-allowed">Next →</button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <CaseCategoryModal
      :show="showForm"
      :category="selectedCategory"
      :all-categories="categories"
      @close="closeForm"
      @saved="onSaved"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import caseCategoryService from '@/services/caseCategoryService'
import CaseCategoryModal from '@/components/Modals/Admin/CaseMaster/CaseCategoryModal.vue'

// ==================== STATE ====================
const categories       = ref([])
const loading          = ref(false)
const togglingId       = ref(null)
const showForm         = ref(false)
const selectedCategory = ref(null)
const searchQuery      = ref('')
const statusFilter     = ref('all')
const toast            = ref({ show: false, message: '', type: 'success' })
let toastTimer         = null

// ==================== COLUMNS ====================
const columns = [
  { label: 'Category Name', field: 'name'      },
  { label: 'Sort Order',    field: 'sort_order' },
  { label: 'Status',        field: 'status'     },
  { label: 'Actions',       field: 'actions'    },
]


const filteredCategories = computed(() => {
  let list = [...categories.value]
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(c => c.name.toLowerCase().includes(q))
  }
  if (statusFilter.value !== 'all') {
    const active = statusFilter.value === 'active'
    list = list.filter(c => Boolean(c.is_active) === active)
  }
  return list.sort((a, b) => a.sort_order - b.sort_order)
})

// ==================== TOAST ====================
const showToast = (message, type = 'success') => {
  clearTimeout(toastTimer)
  toast.value = { show: true, message, type }
  toastTimer = setTimeout(() => { toast.value.show = false }, 3000)
}

// ==================== API ====================
const fetchCategories = async () => {
  try {
    loading.value = true
    categories.value = await caseCategoryService.getAll()
  } catch (err) {
    showToast(err?.response?.data?.message || 'Failed to load categories', 'error')
  } finally {
    loading.value = false
  }
}

const handleToggle = async (cat) => {
  if (togglingId.value) return
  togglingId.value = cat.id
  try {
    await caseCategoryService.toggle(cat.id)
    const idx = categories.value.findIndex(c => c.id === cat.id)
    if (idx !== -1) categories.value[idx] = { ...categories.value[idx], is_active: !categories.value[idx].is_active }
    showToast(`"${cat.name}" ${!cat.is_active ? 'activated' : 'deactivated'} successfully`)
  } catch (err) {
    showToast(err?.response?.data?.message || 'Failed to update status', 'error')
  } finally {
    togglingId.value = null
  }
}

// ==================== FORM ====================
const openForm = (cat = null) => {
  selectedCategory.value = cat ?? null
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
  selectedCategory.value = null
}

const onSaved = async (isEdit) => {
  closeForm()
  await fetchCategories()
  showToast(isEdit ? 'Category updated successfully' : 'Category created successfully')
}

onMounted(fetchCategories)
</script>

<style scoped>
.hover-navy-bg:hover { background-color: rgba(26, 73, 114, 0.08); }
.navy-bg-5  { background-color: rgba(26, 73, 114, 0.05); }
.navy-bg-8  { background-color: rgba(26, 73, 114, 0.08); }
.navy-bg-10 { background-color: rgba(26, 73, 114, 0.10); }
.toast-enter-active { transition: all 0.3s ease; }
.toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from   { opacity: 0; transform: translateX(20px); }
.toast-leave-to     { opacity: 0; transform: translateX(20px); }
</style>