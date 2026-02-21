<!-- Light Theme User Management with Created_at Sorting (Newest-to-Oldest) -->
<template>
  <!-- Error display -->
  <div v-if="apiError" class="mb-4 p-4 rounded-xl text-sm"
    :style="{
      background: 'rgba(239,68,68,0.08)',
      border: '1px solid rgba(239,68,68,0.25)',
      color: '#b91c1c'
    }">
    {{ apiError }}
  </div>

  <div class="min-h-screen p-6" style="background: #f0f4f8; font-family: 'Segoe UI', sans-serif;">

    <!-- Header Section -->
    <div class="mb-7">
      <div class="flex items-center gap-3 mb-1">
        <div class="w-1 h-8 rounded-full" style="background: linear-gradient(to bottom, #1a4972, #2d6db5);"></div>
        <h1 class="text-2xl font-bold tracking-tight" style="color: #1a4972;">User Management</h1>
      </div>
      <p class="text-sm ml-4 pl-3" style="color: #64748b;">Manage lawyers and clerks</p>
    </div>

    <!-- Search and Add Bar -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-4">
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
          <input v-model="searchQuery" @input="debouncedSearch" type="text"
            placeholder="Search by name or email..."
            class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] transition-all placeholder-slate-400"
            :style="{ '--tw-ring-color': 'rgba(26,73,114,0.2)' }" />
        </div>
        <select v-model="roleFilter" @change="handleFilterChange"
          class="px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] transition-all text-slate-600">
          <option value="">All Roles</option>
          <option value="Lawyer">Lawyer</option>
          <option value="Clerk">Clerk</option>
        </select>
        <button @click="openAddUserModal"
          class="text-white px-5 py-2.5 rounded-xl text-sm font-semibold inline-flex items-center justify-center transition-all duration-200 whitespace-nowrap active:scale-95"
          :style="{ background: 'linear-gradient(135deg, #1a4972, #0f2f4a)', boxShadow: '0 4px 12px rgba(26,73,114,0.3)' }">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
          </svg>
          Add New User
        </button>
      </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
      <div v-if="isLoading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2" style="border-color: #1a4972;"></div>
      </div>

      <table v-else class="min-w-full">
        <thead>
          <tr class="border-b border-slate-100" style="background: rgba(26,73,114,0.04);">
            <th v-for="col in columns" :key="col.field" scope="col"
              class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
              :class="col.sortable ? 'cursor-pointer hover:text-[#1a4972] select-none group' : ''"
              @click="col.sortable ? sortBy(col.field) : null">
              <div class="flex items-center gap-1.5">
                {{ col.label }}
                <svg v-if="col.sortable && sortField === col.field" class="w-3.5 h-3.5" style="color: #1a4972;"
                  fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path :d="sortDirection === 'desc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7'" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg v-else-if="col.sortable" class="w-3.5 h-3.5 text-slate-300 group-hover:text-slate-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>
                </svg>
              </div>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="user in users" :key="user.id"
            class="transition-colors duration-100 hover:bg-blue-50/30 group">
            <td class="px-5 py-4">
              <div>
                <p class="text-sm font-semibold text-slate-800">{{ user.name }}</p>
                <p class="text-xs text-slate-400">{{ user.email }}</p>
              </div>
            </td>
            <td class="px-5 py-4">
              <span class="px-2.5 py-1 text-xs font-semibold rounded-lg"
                :style="user.role === 'Lawyer'
                  ? { background: 'rgba(26,73,114,0.1)', color: '#1a4972' }
                  : { background: 'rgba(16,185,129,0.1)', color: '#065f46' }
                ">
                {{ user.role }}
              </span>
            </td>
            <td class="px-5 py-4">
              <div class="flex items-center gap-1.5">
                <div class="w-1.5 h-1.5 rounded-full"
                  :style="{ background: user.status === 'Active' ? '#10b981' : '#ef4444' }"></div>
                <span class="text-xs font-medium"
                  :style="{ color: user.status === 'Active' ? '#065f46' : '#b91c1c' }">
                  {{ user.status }}
                </span>
              </div>
            </td>
            <td class="px-5 py-4 text-sm text-slate-400">{{ formatDate(user.created_at) }}</td>
            <td class="px-5 py-4 text-sm text-slate-400">{{ formatLastLogin(user.last_login) }}</td>
            <td class="px-5 py-4">
              <div class="flex items-center gap-2">
                <button @click="editUser(user)"
                  class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold transition-all"
                  :style="{ color: '#1a4972' }"
                  @mouseover="e => e.currentTarget.style.background = 'rgba(26,73,114,0.07)'"
                  @mouseleave="e => e.currentTarget.style.background = 'transparent'">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                  Edit
                </button>
                <button @click="confirmDeleteUser(user)"
                  class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-red-600 text-sm font-semibold transition-all hover:bg-red-50">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="users.length === 0 && !isLoading">
            <td :colspan="columns.length" class="px-6 py-16 text-center">
              <div class="flex flex-col items-center">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-3"
                  style="background: rgba(26,73,114,0.07);">
                  <svg class="w-7 h-7" style="color: #1a4972; opacity: 0.5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                  </svg>
                </div>
                <p class="text-sm font-semibold text-slate-700 mb-1">No users found</p>
                <p class="text-xs text-slate-400">Try adjusting your search or add a new user</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="flex items-center justify-between px-5 py-3.5 border-t border-slate-100 bg-slate-50/50">
        <p class="text-xs text-slate-500">
          Showing <span class="font-semibold text-slate-700">{{ pagination.from }}</span> to
          <span class="font-semibold text-slate-700">{{ pagination.to }}</span> of
          <span class="font-semibold text-slate-700">{{ pagination.total }}</span> users
        </p>
        <div class="flex items-center gap-1">
          <button @click="previousPage" :disabled="pagination.current_page === 1"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
            :class="pagination.current_page === 1 ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-200'">
            ← Prev
          </button>
          <button v-for="page in displayedPages" :key="page" @click="goToPage(page)"
            class="w-7 h-7 rounded-lg text-xs font-medium transition-all"
            :style="pagination.current_page === page
              ? { background: 'linear-gradient(135deg,#1a4972,#0f2f4a)', color: 'white' }
              : {}"
            :class="pagination.current_page !== page ? 'text-slate-600 hover:bg-slate-200' : ''">
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

    <!-- Add/Edit Modal -->
    <Transition name="modal">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeModal">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-[1000px] max-h-[90vh] flex flex-col overflow-hidden">

          <!-- Modal Header -->
          <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 flex-shrink-0">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                style="background: rgba(26,73,114,0.1);">
                <svg class="w-6 h-6" style="color: #1a4972;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-lg font-bold text-slate-800">{{ isEditing ? 'Edit User' : 'Add New User' }}</h2>
                <p class="text-sm text-slate-500">{{ isEditing ? 'Update user information' : 'Fill in the details to create a new account' }}</p>
              </div>
            </div>
            <button @click="closeModal" class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Modal Body -->
          <div class="px-8 py-6 space-y-6 overflow-y-auto">
            <div class="grid grid-cols-3 gap-4">
              <div v-for="(field, index) in ['firstName', 'middleName', 'lastName']" :key="field">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                  {{ ['First Name', 'Middle Name', 'Last Name'][index] }}
                  <span v-if="field !== 'middleName'" class="text-red-500">*</span>
                  <span v-else class="text-slate-400 font-normal">(Optional)</span>
                </label>
                <input v-model="form[field]" type="text" :placeholder="'Enter ' + ['first name','middle name','last name'][index]"
                  class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none transition-all"
                  :class="{ 'border-red-400': errors[field] }"
                  :style="{ focusBorderColor: '#1a4972' }" />
                <p v-if="errors[field]" class="text-sm text-red-500 mt-1">{{ errors[field] }}</p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                  Complete Address <span class="text-slate-400 font-normal text-xs ml-1">(Optional)</span>
                </label>
                <input v-model="form.address" type="text" placeholder="Enter complete address"
                  class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none transition-all"
                  :class="{ 'border-red-400': errors.address }" />
                <p v-if="errors.address" class="text-sm text-red-500 mt-1">{{ errors.address }}</p>
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                  Contact Number <span class="text-slate-400 font-normal text-xs ml-1">(Optional)</span>
                </label>
                <input v-model="form.contact" type="tel" placeholder="0000 000 0000"
                  class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none transition-all"
                  :class="{ 'border-red-400': errors.contact }" />
                <p v-if="errors.contact" class="text-sm text-red-500 mt-1">{{ errors.contact }}</p>
              </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                <input v-model="form.email" type="email" placeholder="Enter email address"
                  class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none transition-all"
                  :class="{ 'border-red-400': errors.email }" />
                <p v-if="errors.email" class="text-sm text-red-500 mt-1">{{ errors.email }}</p>
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Role <span class="text-red-500">*</span></label>
                <select v-model="form.role"
                  class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none transition-all text-slate-600"
                  :class="{ 'border-red-400': errors.role }">
                  <option value="" disabled>Select role</option>
                  <option value="Lawyer">Lawyer</option>
                  <option value="Clerk">Clerk</option>
                </select>
                <p v-if="errors.role" class="text-sm text-red-500 mt-1">{{ errors.role }}</p>
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password <span class="text-red-500">*</span></label>
                <div class="relative">
                  <input v-model="form.password" :type="showPassword ? 'text' : 'password'"
                    @keyup.enter="submitForm"
                    :placeholder="isEditing && !resetPassword ? '•••••••• (unchanged)' : 'Enter new password'"
                    class="w-full px-4 py-3 pr-10 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none transition-all"
                    :class="{ 'border-red-400': errors.password }"
                    :disabled="isEditing && !resetPassword" />
                  <button v-if="!isEditing || resetPassword" type="button" @click="showPassword = !showPassword"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path :d="showPassword ? 'M13.875 18.825A10.05 10.05 0 0112 19.5c-4.5 0-8.25-3-9-7.5a9.956 9.956 0 012.16-4.112' : 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'"/>
                    </svg>
                  </button>
                </div>
                <div class="flex gap-2 mt-2">
                  <button v-if="isEditing" @click="toggleResetPassword" type="button"
                    class="flex items-center gap-1 px-3 py-1 text-sm font-semibold rounded-lg transition-all"
                    :class="resetPassword ? 'bg-slate-100 text-slate-700' : 'bg-amber-100 text-amber-700 hover:bg-amber-200'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    {{ resetPassword ? 'Cancel Reset' : 'Reset Password' }}
                  </button>
                </div>
                <p v-if="errors.password" class="text-sm text-red-500 mt-1">{{ errors.password }}</p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">Status <span class="text-red-500">*</span></label>
              <div class="flex items-center gap-4">
                <label v-for="status in ['Active', 'Inactive']" :key="status" class="flex items-center gap-2 cursor-pointer group">
                  <div class="relative">
                    <input type="radio" v-model="form.status" :value="status" class="sr-only"/>
                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all"
                      :style="form.status === status
                        ? { borderColor: '#1a4972', background: '#1a4972' }
                        : { borderColor: '#cbd5e1' }">
                      <div v-if="form.status === status" class="w-2 h-2 bg-white rounded-full"></div>
                    </div>
                  </div>
                  <span class="text-sm text-slate-700 font-medium">{{ status }}</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex-shrink-0">
            <button @click="closeModal" class="px-6 py-3 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95">
              Cancel
            </button>
            <button @click="submitForm" :disabled="formLoading"
              class="px-6 py-3 text-sm font-semibold text-white rounded-xl active:scale-95 disabled:opacity-60 flex items-center gap-2 min-w-[100px] justify-center"
              :style="{ background: 'linear-gradient(135deg,#1a4972,#0f2f4a)', boxShadow: '0 4px 12px rgba(26,73,114,0.3)' }">
              <svg v-if="formLoading" class="animate-spin w-5 h-5" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
              {{ formLoading ? 'Saving...' : (isEditing ? 'Save Changes' : 'Add User') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Delete Confirmation Modal -->
    <Transition name="modal">
      <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
          <div class="p-6 text-center">
            <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center mx-auto mb-4">
              <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800 mb-1">Delete User</h3>
            <p class="text-sm text-slate-500">Are you sure you want to delete <span class="font-semibold text-slate-700">{{ userToDelete?.name }}</span>? This action cannot be undone.</p>
          </div>
          <div class="flex gap-3 px-6 pb-6">
            <button @click="showDeleteModal = false" class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200">Cancel</button>
            <button @click="deleteUser" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl">Delete</button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, reactive, watch, onMounted } from 'vue';
import { debounce } from 'lodash';
import * as UserService from '@/services/userServices';

const columns = [
  { label: 'Name', field: 'name', sortable: true },
  { label: 'Role', field: 'role', sortable: true },
  { label: 'Status', field: 'status', sortable: true },
  { label: 'Created At', field: 'created_at', sortable: true },
  { label: 'Last Login', field: 'last_login', sortable: true },
  { label: 'Actions', field: 'actions', sortable: false },
];

const users = ref([]);
const isLoading = ref(false);
const apiError = ref('');
const pagination = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 });

// Default sort by created_at descending (newest first)
const searchQuery = ref('');
const roleFilter = ref('');
const sortField = ref('created_at');
const sortDirection = ref('desc');
const currentPage = ref(1);
const itemsPerPage = ref(10);

const showModal = ref(false);
const isEditing = ref(false);
const formLoading = ref(false);
const showPassword = ref(false);
const editingUserId = ref(null);
const resetPassword = ref(false);
const showDeleteModal = ref(false);
const userToDelete = ref(null);

const form = reactive({ firstName: '', middleName: '', lastName: '', address: '', contact: '', email: '', role: '', password: '', status: 'Active' });
const errors = reactive({ firstName: '', lastName: '', address: '', contact: '', email: '', role: '', password: '' });

const displayedPages = computed(() => {
  const pages = [];
  const max = 5;
  const total = pagination.value.last_page;
  if (total <= max) { for (let i = 1; i <= total; i++) pages.push(i); }
  else {
    let start = Math.max(1, pagination.value.current_page - 2);
    let end = Math.min(total, start + max - 1);
    if (end - start + 1 < max) start = Math.max(1, end - max + 1);
    for (let i = start; i <= end; i++) pages.push(i);
  }
  return pages;
});

onMounted(async () => { await loadUsers(); });

watch([searchQuery, roleFilter, sortField, sortDirection, currentPage, itemsPerPage], () => { loadUsers(); });

const debouncedSearch = debounce(() => { currentPage.value = 1; loadUsers(); }, 500);
const handleFilterChange = () => { currentPage.value = 1; loadUsers(); };

const loadUsers = async () => {
  isLoading.value = true; apiError.value = '';
  const params = { search: searchQuery.value || undefined, role: roleFilter.value || undefined, sort_by: sortField.value, sort_direction: sortDirection.value, page: currentPage.value, per_page: itemsPerPage.value };
  try {
    const response = await UserService.getUsers(params);
    if (response.data && response.meta) {
      users.value = response.data;
      pagination.value = { current_page: response.meta.current_page, last_page: response.meta.last_page, per_page: response.meta.per_page, total: response.meta.total, from: (response.meta.current_page - 1) * response.meta.per_page + 1, to: Math.min(response.meta.current_page * response.meta.per_page, response.meta.total) };
    } else if (Array.isArray(response)) {
      users.value = response;
      pagination.value = { current_page: 1, last_page: 1, per_page: response.length, total: response.length, from: 1, to: response.length };
    } else { users.value = []; }
  } catch (error) { apiError.value = error.message || 'Failed to load users.'; }
  finally { isLoading.value = false; }
};

const sortBy = (field) => {
  if (sortField.value === field) sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  else { sortField.value = field; sortDirection.value = 'asc'; }
};

const previousPage = () => { if (pagination.value.current_page > 1) currentPage.value = pagination.value.current_page - 1; };
const nextPage = () => { if (pagination.value.current_page < pagination.value.last_page) currentPage.value = pagination.value.current_page + 1; };
const goToPage = (page) => { currentPage.value = page; };

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A';
  return new Date(dateStr).toLocaleString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: true });
};

const formatLastLogin = (dateStr) => {
  if (!dateStr) return 'Never';
  return new Date(dateStr).toLocaleString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: true });
};

const openAddUserModal = () => { resetForm(); isEditing.value = false; showModal.value = true; };
const closeModal = () => { showModal.value = false; resetForm(); };
const resetForm = () => {
  Object.assign(form, { firstName: '', middleName: '', lastName: '', address: '', contact: '', email: '', role: '', password: '', status: 'Active' });
  Object.keys(errors).forEach(k => errors[k] = '');
  editingUserId.value = null; resetPassword.value = false; showPassword.value = false;
};
const clearErrors = () => { Object.keys(errors).forEach(key => errors[key] = ''); };

const editUser = async (user) => {
  try {
    const response = await UserService.getUserById(user.id);
    const userData = response.data || response;
    const formattedData = UserService.formatUserDataForForm(userData);
    Object.assign(form, formattedData);
    isEditing.value = true; editingUserId.value = user.id; resetPassword.value = false; form.password = '';
    clearErrors(); showModal.value = true;
  } catch (error) { apiError.value = error.message || 'Failed to load user details'; }
};

const toggleResetPassword = () => {
  resetPassword.value = !resetPassword.value;
  form.password = resetPassword.value ? 'temppass1' : '';
  if (!resetPassword.value) errors.password = '';
};

const validateForm = () => {
  clearErrors(); let valid = true;
  if (!form.firstName.trim()) { errors.firstName = 'First name is required'; valid = false; }
  if (!form.lastName.trim()) { errors.lastName = 'Last name is required'; valid = false; }
  if (!form.email.trim()) { errors.email = 'Email is required'; valid = false; }
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) { errors.email = 'Invalid email format'; valid = false; }
  if (!form.role) { errors.role = 'Role is required'; valid = false; }
  if (!isEditing.value && !form.password.trim()) { errors.password = 'Password is required'; valid = false; }
  else if (isEditing.value && resetPassword.value && !form.password.trim()) { errors.password = 'New password is required'; valid = false; }
  else if (form.password && form.password.length < 6 && form.password !== 'temppass1') { errors.password = 'Password must be at least 6 characters'; valid = false; }
  return valid;
};

const submitForm = async () => {
  if (!validateForm()) return;
  formLoading.value = true; apiError.value = '';
  try {
    if (isEditing.value) await UserService.updateUser(editingUserId.value, form);
    else await UserService.createUser(form);
    await loadUsers(); closeModal();
  } catch (error) {
    apiError.value = error.message || 'Failed to save user';
    if (error.errors) {
      const fieldMap = { name: 'firstName', contact_number: 'contact', password: 'password', email: 'email', role: 'role', address: 'address' };
      Object.keys(error.errors).forEach(key => { const f = fieldMap[key] || key; if (errors.hasOwnProperty(f)) errors[f] = error.errors[key][0]; });
    }
  } finally { formLoading.value = false; }
};

const confirmDeleteUser = (user) => { userToDelete.value = user; showDeleteModal.value = true; };
const deleteUser = async () => {
  if (!userToDelete.value) return;
  try { await UserService.deleteUser(userToDelete.value.id); await loadUsers(); showDeleteModal.value = false; userToDelete.value = null; }
  catch (error) { apiError.value = error.message || 'Failed to delete user'; }
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
input:focus, select:focus { border-color: #1a4972 !important; box-shadow: 0 0 0 3px rgba(26,73,114,0.1); }
</style>