<template>
  <Transition name="modal">
    <div v-if="show"
      class="fixed inset-0 z-50 flex items-start sm:items-center justify-center p-0 sm:p-4"
      @click.self="$emit('close')">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
      <div class="relative bg-white w-full sm:rounded-2xl shadow-2xl sm:max-w-md max-h-screen sm:max-h-[92vh] flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 flex-shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center navy-bg-10">
              <svg class="w-5 h-5 text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="isEditing" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-800">
                {{ isEditing ? 'Edit Category' : 'New Category' }}
              </h2>
              <p class="text-sm text-slate-500 hidden sm:block">
                {{ isEditing ? 'Update category details' : 'Create a new case category' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Body -->
        <div class="px-6 py-6 overflow-y-auto space-y-5 flex-1">

          <p class="text-xs font-bold uppercase tracking-widest pb-2 border-b border-slate-100 text-[#1a4972]">
            Category Information
          </p>

          <!-- Name -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
              Category Name <span class="text-red-500">*</span>
            </label>
            <input
              ref="nameInputRef"
              v-model="form.name"
              type="text"
              placeholder="e.g., Civil, Criminal, Corporate"
              @keyup.enter="save"
              class="w-full px-4 py-2.5 text-sm border rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all"
              :class="errors.name ? 'border-red-400' : 'border-slate-200'" />
            <p v-if="errors.name" class="text-xs text-red-500 mt-1 flex items-center gap-1">
              <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ errors.name }}
            </p>
          </div>

          <!-- Sort Order -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
              Sort Order
              <!-- Show reorder hint when editing and value changed -->
              <span v-if="isEditing && orderWillReshift"
                class="ml-2 text-xs font-normal text-amber-600 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">
                ↕ Will reorder other categories
              </span>
            </label>

            <div class="flex items-center gap-2">
              <input
                v-model.number="form.sort_order"
                type="number"
                min="0"
                :max="maxSortOrder"
                class="flex-1 px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all" />

              <!-- Quick nav buttons -->
              <div class="flex flex-col gap-1">
                <button type="button" @click="form.sort_order = Math.max(0, form.sort_order - 1)"
                  class="w-8 h-6 flex items-center justify-center rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-500 transition-colors"
                  title="Move up (lower number)">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
                  </svg>
                </button>
                <button type="button" @click="form.sort_order = Math.min(maxSortOrder, form.sort_order + 1)"
                  class="w-8 h-6 flex items-center justify-center rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-500 transition-colors"
                  title="Move down (higher number)">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Sort order context: show what will be displaced -->
            <div class="mt-2 space-y-1">
              <p class="text-xs text-slate-400">
                Range: 0 – {{ maxSortOrder }}
                <span v-if="!isEditing"> · Next available: <strong class="text-[#1a4972]">{{ nextAvailableOrder }}</strong></span>
              </p>

              <!-- Preview of what gets shifted -->
              <div v-if="isEditing && orderWillReshift && displacedCategory"
                class="flex items-center gap-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg">
                <svg class="w-3.5 h-3.5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <p class="text-xs text-amber-700">
                  <strong>"{{ displacedCategory.name }}"</strong> will move from
                  Order {{ form.sort_order }} → Order {{ originalOrder }}
                </p>
              </div>
            </div>
          </div>

          <!-- Status -->
          <p class="text-xs font-bold uppercase tracking-widest pb-2 border-b border-slate-100 text-[#1a4972]">
            Status
          </p>

          <label class="flex items-center justify-between cursor-pointer p-3.5 bg-slate-50 border border-slate-200 rounded-xl hover:bg-slate-100/60 transition-colors select-none">
            <div>
              <p class="text-sm font-semibold text-slate-700">Active Status</p>
              <p class="text-xs mt-0.5" :class="form.is_active ? 'text-emerald-600' : 'text-slate-400'">
                {{ form.is_active ? 'Visible and usable in cases' : 'Hidden from case assignment' }}
              </p>
            </div>
            <div class="relative flex-shrink-0 ml-4" @click.prevent="form.is_active = !form.is_active">
              <div class="w-10 h-6 rounded-full transition-colors duration-200"
                :class="form.is_active ? 'bg-[#1a4972]' : 'bg-slate-300'">
                <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"
                  :class="{ 'translate-x-4': form.is_active }"></div>
              </div>
            </div>
          </label>

          <!-- Server error -->
          <div v-if="serverError"
            class="flex items-center gap-2 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ serverError }}
          </div>

        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex-shrink-0">
          <button @click="$emit('close')"
            class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 transition-all">
            Cancel
          </button>
          <button @click="save" :disabled="formLoading"
            class="px-6 py-2.5 text-sm font-semibold text-white rounded-xl active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2 min-w-[150px] justify-center bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] shadow-lg shadow-[#1a4972]/30 hover:shadow-xl transition-all">
            <svg v-if="formLoading" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ formLoading ? 'Saving...' : (isEditing ? 'Save Changes' : 'Create Category') }}
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick } from 'vue'
import caseCategoryService from '@/services/caseCategoryService'

// ==================== PROPS & EMITS ====================
const props = defineProps({
  show:           { type: Boolean, default: false },
  category:       { type: Object,  default: null  },
  allCategories:  { type: Array,   default: () => [] }, // full list from parent
})

const emit = defineEmits(['close', 'saved'])

// ==================== STATE ====================
const formLoading  = ref(false)
const serverError  = ref('')
const nameInputRef = ref(null)
const originalOrder = ref(0) // the category's order before editing

const form   = reactive({ name: '', sort_order: 0, is_active: true })
const errors = reactive({ name: '' })

// ==================== COMPUTED ====================
const isEditing = computed(() => !!props.category?.id)

// Other categories excluding the one being edited
const otherCategories = computed(() =>
  props.allCategories.filter(c => c.id !== props.category?.id)
)

// The highest sort_order in the list
const maxSortOrder = computed(() => {
  if (props.allCategories.length === 0) return 0
  return Math.max(...props.allCategories.map(c => c.sort_order))
})

// Next available slot = max + 1 (for create mode)
const nextAvailableOrder = computed(() => maxSortOrder.value + 1)

// The category that currently occupies the chosen sort_order slot (for edit mode)
const displacedCategory = computed(() => {
  if (!isEditing.value) return null
  return otherCategories.value.find(c => c.sort_order === form.sort_order) ?? null
})

// Whether the sort order was actually changed and will cause a shift
const orderWillReshift = computed(() =>
  isEditing.value &&
  form.sort_order !== originalOrder.value &&
  displacedCategory.value !== null
)

// ==================== WATCHERS ====================
watch(() => props.show, async (val) => {
  if (!val) return
  serverError.value = ''
  errors.name = ''

  if (props.category) {
    form.name       = props.category.name       ?? ''
    form.sort_order = props.category.sort_order ?? 0
    form.is_active  = Boolean(props.category.is_active)
    originalOrder.value = props.category.sort_order ?? 0
  } else {
    form.name       = ''
    form.sort_order = nextAvailableOrder.value
    form.is_active  = true
    originalOrder.value = 0
  }

  await nextTick()
  nameInputRef.value?.focus()
})

// ==================== VALIDATION ====================
const validate = () => {
  errors.name = ''
  serverError.value = ''

  if (!form.name.trim()) {
    errors.name = 'Category name is required'
    return false
  }
  if (form.name.trim().length > 255) {
    errors.name = 'Name must be 255 characters or fewer'
    return false
  }

  const dupe = props.allCategories.find(c =>
    c.name.toLowerCase() === form.name.trim().toLowerCase() &&
    c.id !== props.category?.id
  )
  if (dupe) {
    errors.name = 'A category with this name already exists'
    return false
  }

  return true
}

// ==================== SAVE ====================
const save = async () => {
  if (!validate()) return
  formLoading.value = true

  try {
    const newOrder = Number(form.sort_order)

    if (isEditing.value) {
      // ── EDIT: update this category first ──────────────────────────────────
      await caseCategoryService.update(props.category.id, {
        name:       form.name.trim(),
        sort_order: newOrder,
        is_active:  form.is_active,
      })

      // ── REORDER: if another category occupied the target slot, shift it ───
      // Strategy: move all categories between old and new order by ±1
      if (newOrder !== originalOrder.value) {
        const others = otherCategories.value
          .filter(c => {
            if (newOrder < originalOrder.value) {
              // Moving UP: shift categories in [newOrder, originalOrder-1] down by 1
              return c.sort_order >= newOrder && c.sort_order < originalOrder.value
            } else {
              // Moving DOWN: shift categories in [originalOrder+1, newOrder] up by 1
              return c.sort_order > originalOrder.value && c.sort_order <= newOrder
            }
          })

        const direction = newOrder < originalOrder.value ? 1 : -1

        // Fire all shift updates in parallel
        await Promise.allSettled(
          others.map(c =>
            caseCategoryService.update(c.id, {
              name:       c.name,
              sort_order: c.sort_order + direction,
              is_active:  c.is_active,
            })
          )
        )
      }
    } else {
      // ── CREATE ─────────────────────────────────────────────────────────────
      await caseCategoryService.create({
        name:       form.name.trim(),
        sort_order: newOrder,
        is_active:  form.is_active,
      })
    }

    emit('saved', isEditing.value)
  } catch (err) {
    const laravelErrors = err?.response?.data?.errors ?? {}
    if (laravelErrors.name) {
      errors.name = Array.isArray(laravelErrors.name) ? laravelErrors.name[0] : laravelErrors.name
    } else {
      serverError.value = err?.response?.data?.message || 'Something went wrong. Please try again.'
    }
  } finally {
    formLoading.value = false
  }
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to       { opacity: 0; }
.navy-bg-10 { background-color: rgba(26, 73, 114, 0.10); }
</style>