<!-- User Management - Following Dashboard Pattern (No Pinia) -->
<template>
  <div class="min-h-screen p-6 bg-slate-50 font-sans">

    <!-- Header Section -->
    <div class="mb-7">
      <div class="flex items-center gap-3 mb-1">
        <div class="w-1 h-8 rounded-full bg-gradient-to-b from-[#1a4972] to-[#2d6db5]"></div>
        <h1 class="text-2xl font-bold tracking-tight text-[#1a4972]">User Management</h1>
      </div>
      <p class="text-sm ml-4 pl-3 text-slate-500">Manage lawyers and clerks</p>
    </div>

    <!-- Search and Add Bar -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-4 transition-all duration-300 hover:shadow-md">
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1 group">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
            <svg class="h-4 w-4 text-slate-400 transition-colors duration-200 group-focus-within:text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
          <input v-model="searchQuery" @input="debouncedSearch" type="text"
            placeholder="Search by name or email..."
            class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] focus:bg-white transition-all duration-200 placeholder-slate-400" />
        </div>
        
        <!-- Dynamic Role Filter -->
        <select v-model="roleFilter" @change="handleFilterChange"
          class="px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] focus:bg-white transition-all duration-200 text-slate-600 cursor-pointer hover:bg-slate-100">
          <option value="">All Roles</option>
          <option v-for="role in availableRoles" :key="role.id" :value="role.name">
            {{ role.name }}
          </option>
        </select>

        <button @click="openAddUserModal" :disabled="isAdding"
          class="text-white px-5 py-2.5 rounded-xl text-sm font-semibold inline-flex items-center justify-center transition-all duration-200 whitespace-nowrap hover:shadow-lg active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:shadow-none disabled:active:scale-100 bg-gradient-to-r from-[#1a4972] to-[#0f2f4a] shadow-md shadow-[#1a4972]/30">
          <svg v-if="!isAdding" class="w-4 h-4 mr-2 transition-transform duration-200 group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
          </svg>
          <svg v-else class="animate-spin w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          {{ isAdding ? 'Adding...' : 'Add New User' }}
        </button>
      </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden transition-all duration-300 hover:shadow-md">
      <table class="min-w-full">
        <thead>
          <tr class="border-b border-slate-100 bg-[#1a4972]/5">
            <th v-for="col in columns" :key="col.field" scope="col"
              class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
              :class="col.sortable ? 'cursor-pointer hover:text-[#1a4972] select-none group' : ''"
              @click="col.sortable ? sortBy(col.field) : null">
              <div class="flex items-center gap-1.5">
                {{ col.label }}
                <svg v-if="col.sortable && sortField === col.field" class="w-3.5 h-3.5 transition-transform duration-200 text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path :d="sortDirection === 'desc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7'" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg v-else-if="col.sortable" class="w-3.5 h-3.5 text-slate-300 group-hover:text-slate-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>
                </svg>
              </div>
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-50" v-if="users && users.length > 0">
          <!-- Rows with fade-in animation -->
          <tr v-for="(user, index) in users" :key="user.id" 
            class="transition-all duration-300 hover:bg-blue-50/30 group"
            :style="{ animation: `fadeIn 0.3s ease-out ${index * 0.03}s both` }">
            <td class="px-5 py-4">
              <p class="text-sm font-semibold text-slate-800">{{ user?.name || '—' }}</p>
              <p class="text-xs text-slate-400">{{ user?.email || '—' }}</p>
            </td>
            <td class="px-5 py-4">
              <span class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all duration-200 hover:scale-105 inline-block"
                :class="{
                  'bg-[#1a4972]/10 text-[#1a4972]': user?.role === 'Lawyer',
                  'bg-emerald-50 text-emerald-700': user?.role === 'Clerk'
                }">
                {{ user?.role || '—' }}
              </span>
            </td>
            <td class="px-5 py-4">
              <div class="flex items-center gap-1.5">
                <div class="w-1.5 h-1.5 rounded-full transition-all duration-300" 
                  :class="{
                    'bg-emerald-500': user?.status === 'Active',
                    'bg-red-500': user?.status !== 'Active'
                  }"></div>
                <span class="text-xs font-medium" :class="user?.status === 'Active' ? 'text-emerald-700' : 'text-red-700'">
                  {{ user?.status || '—' }}
                </span>
              </div>
            </td>
            <td class="px-5 py-4 text-sm text-slate-400">{{ formatDate(user?.created_at) }}</td>
            <td class="px-5 py-4 text-sm text-slate-400">{{ formatLastLogin(user?.last_login) }}</td>
            <td class="px-5 py-4">
              <div class="flex items-center gap-2 opacity-80 group-hover:opacity-100 transition-opacity duration-200">
                <button @click="editUser(user)" :disabled="isEditingUser === user.id"
                  class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold transition-all hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 text-[#1a4972] hover:bg-[#1a4972]/10">
                  <svg v-if="isEditingUser !== user.id" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                  <svg v-else class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  {{ isEditingUser === user.id ? 'Editing...' : 'Edit' }}
                </button>
                <button @click="confirmDeleteUser(user)" :disabled="isDeletingUser === user.id"
                  class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-red-600 text-sm font-semibold transition-all hover:bg-red-50 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                  <svg v-if="isDeletingUser !== user.id" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                  <svg v-else class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  {{ isDeletingUser === user.id ? 'Deleting...' : 'Delete' }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>

        <!-- Empty state -->
        <tbody v-else>
          <tr>
            <td :colspan="columns.length" class="px-6 py-16 text-center">
              <div class="flex flex-col items-center">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-3 bg-[#1a4972]/10">
                  <svg class="w-7 h-7 text-[#1a4972] opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200"
            :class="pagination.current_page === 1 
              ? 'text-slate-300 cursor-not-allowed' 
              : 'text-slate-600 hover:bg-slate-200 hover:scale-105 active:scale-95'">
            ← Prev
          </button>
          <button v-for="page in displayedPages" :key="page" @click="goToPage(page)"
            class="w-7 h-7 rounded-lg text-xs font-medium transition-all duration-200 hover:scale-110 active:scale-95"
            :class="pagination.current_page === page 
              ? 'bg-gradient-to-r from-[#1a4972] to-[#0f2f4a] text-white shadow-md shadow-[#1a4972]/30' 
              : 'text-slate-600 hover:bg-slate-200'">
            {{ page }}
          </button>
          <button @click="nextPage" :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200"
            :class="pagination.current_page === pagination.last_page 
              ? 'text-slate-300 cursor-not-allowed' 
              : 'text-slate-600 hover:bg-slate-200 hover:scale-105 active:scale-95'">
            Next →
          </button>
        </div>
      </div>
    </div>

    <!-- ADD / EDIT MODAL -->
    <Transition name="modal">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeModal">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity duration-300"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-[1000px] max-h-[90vh] flex flex-col overflow-hidden transform transition-all duration-300 scale-100 opacity-100">

          <!-- Modal Header -->
          <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 flex-shrink-0">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-[#1a4972]/10">
                <svg class="w-6 h-6 text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-lg font-bold text-slate-800">{{ isEditing ? 'Edit User' : 'Add New User' }}</h2>
                <p class="text-sm text-slate-500">{{ isEditing ? 'Update user information' : 'Fill in the details to create a new account' }}</p>
              </div>
            </div>
            <button @click="closeModal" :disabled="formLoading" class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all duration-200 hover:scale-110 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Modal Body -->
          <div class="px-8 py-6 space-y-6 overflow-y-auto">
            <!-- Name fields -->
            <div class="grid grid-cols-3 gap-4">
              <div v-for="(field, index) in ['firstName', 'middleName', 'lastName']" :key="field">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                  {{ ['First Name', 'Middle Name', 'Last Name'][index] }}
                  <span v-if="field !== 'middleName'" class="text-red-500">*</span>
                  <span v-else class="text-slate-400 font-normal text-xs ml-1">(Optional)</span>
                </label>
                <input v-model="form[field]" type="text" :disabled="formLoading"
                  :placeholder="'Enter ' + ['first name','middle name','last name'][index]"
                  class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none transition-all duration-200 hover:border-[#1a4972] focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 disabled:opacity-50 disabled:cursor-not-allowed"
                  :class="{ 'border-red-400': errors[field] }" />
                <p v-if="errors[field]" class="text-sm text-red-500 mt-1">{{ errors[field] }}</p>
              </div>
            </div>

            <!-- Address + Contact -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                  Complete Address <span class="text-slate-400 font-normal text-xs ml-1">(Optional)</span>
                </label>
                <input v-model="form.address" type="text" :disabled="formLoading" placeholder="Enter complete address"
                  class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none transition-all duration-200 hover:border-[#1a4972] focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 disabled:opacity-50 disabled:cursor-not-allowed"
                  :class="{ 'border-red-400': errors.address }" />
                <p v-if="errors.address" class="text-sm text-red-500 mt-1">{{ errors.address }}</p>
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                  Contact Number <span class="text-slate-400 font-normal text-xs ml-1">(Optional)</span>
                </label>
                <input
                  v-model="form.contact"
                  @keypress="onContactKeypress"
                  @input="onContactInput"
                  type="text" inputmode="numeric" maxlength="13" :disabled="formLoading"
                  placeholder="09XX XXX XXXX"
                  class="w-full px-4 py-3 text-sm border rounded-xl bg-slate-50 focus:bg-white focus:outline-none transition-all duration-200 hover:border-[#1a4972] focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 disabled:opacity-50 disabled:cursor-not-allowed"
                  :class="errors.contact ? 'border-red-400' : 'border-slate-200'"
                />
                <p v-if="errors.contact" class="text-sm text-red-500 mt-1">{{ errors.contact }}</p>
                <p v-else-if="form.contact && isPHContactValid(form.contact)"
                  class="text-xs text-emerald-600 mt-1 flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                  </svg>
                  Valid PH number
                </p>
              </div>
            </div>

            <!-- Email, Role, Password -->
            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                <input v-model="form.email" type="email" :disabled="formLoading" placeholder="Enter email address"
                  class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none transition-all duration-200 hover:border-[#1a4972] focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 disabled:opacity-50 disabled:cursor-not-allowed"
                  :class="{ 'border-red-400': errors.email }" />
                <p v-if="errors.email" class="text-sm text-red-500 mt-1">{{ errors.email }}</p>
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Role <span class="text-red-500">*</span></label>
                <select v-model="form.role" :disabled="formLoading"
                  class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none transition-all duration-200 hover:border-[#1a4972] focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 text-slate-600 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                  :class="{ 'border-red-400': errors.role }">
                  <option value="" disabled>Select role</option>
                  <option v-for="role in availableRoles" :key="role.id" :value="role.name">
                    {{ role.name }}
                  </option>
                </select>
                <p v-if="errors.role" class="text-sm text-red-500 mt-1">{{ errors.role }}</p>
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password <span class="text-red-500">*</span></label>
                <div class="relative">
                  <input v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    @keyup.enter="submitForm"
                    :placeholder="isEditing && !resetPassword ? '•••••••• (unchanged)' : 'Enter new password'"
                    class="w-full px-4 py-3 pr-10 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none transition-all duration-200 hover:border-[#1a4972] focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="{ 'border-red-400': errors.password }"
                    :disabled="formLoading || (isEditing && !resetPassword)" />
                  <button v-if="!isEditing || resetPassword" type="button" @click="showPassword = !showPassword" :disabled="formLoading"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-all duration-200 hover:scale-110 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path :d="showPassword
                        ? 'M13.875 18.825A10.05 10.05 0 0112 19.5c-4.5 0-8.25-3-9-7.5a9.956 9.956 0 012.16-4.112'
                        : 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'"/>
                    </svg>
                  </button>
                </div>
                <div class="flex gap-2 mt-2">
                  <button v-if="isEditing" @click="toggleResetPassword" type="button" :disabled="formLoading"
                    class="flex items-center gap-1 px-3 py-1 text-sm font-semibold rounded-lg transition-all duration-200 hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="resetPassword ? 'bg-slate-100 text-slate-700' : 'bg-amber-100 text-amber-700 hover:bg-amber-200'">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    {{ resetPassword ? 'Cancel Reset' : 'Reset Password' }}
                  </button>
                </div>
                <p v-if="errors.password" class="text-sm text-red-500 mt-1">{{ errors.password }}</p>
              </div>
            </div>

            <!-- Status -->
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">Status <span class="text-red-500">*</span></label>
              <div class="flex items-center gap-4">
                <label v-for="status in ['Active', 'Inactive']" :key="status" 
                  class="flex items-center gap-2 cursor-pointer group transition-all duration-200 hover:scale-105 disabled:opacity-50">
                  <div class="relative">
                    <input type="radio" v-model="form.status" :value="status" :disabled="formLoading" class="sr-only"/>
                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all duration-200"
                      :class="form.status === status 
                        ? 'border-[#1a4972] bg-[#1a4972] shadow-[0_0_0_3px_rgba(26,73,114,0.2)]' 
                        : 'border-slate-300'">
                      <div v-if="form.status === status" class="w-2 h-2 bg-white rounded-full"></div>
                    </div>
                  </div>
                  <span class="text-sm text-slate-700 font-medium group-hover:text-[#1a4972] transition-colors duration-200">{{ status }}</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex-shrink-0">
            <button @click="closeModal" :disabled="formLoading"
              class="px-6 py-3 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 transition-all duration-200 hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
              Cancel
            </button>
            <button @click="submitForm" :disabled="formLoading"
              class="px-6 py-3 text-sm font-semibold text-white rounded-xl active:scale-95 transition-all duration-200 hover:shadow-lg hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 bg-gradient-to-r from-[#1a4972] to-[#0f2f4a] shadow-md shadow-[#1a4972]/30 min-w-[120px] flex items-center justify-center gap-2">
              <svg v-if="formLoading" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ formLoading ? (isEditing ? 'Saving...' : 'Adding...') : (isEditing ? 'Save Changes' : 'Add User') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- DELETE CONFIRMATION MODAL -->
    <Transition name="modal">
      <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity duration-300"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all duration-300 scale-100 opacity-100">
          <div class="p-6 text-center">
            <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center mx-auto mb-4">
              <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800 mb-1">Delete User</h3>
            <p class="text-sm text-slate-500">
              Are you sure you want to delete
              <span class="font-semibold text-slate-700">{{ userToDelete?.name }}</span>?
              This action cannot be undone.
            </p>
          </div>
          <div class="flex gap-3 px-6 pb-6">
            <button @click="showDeleteModal = false" :disabled="isDeletingUser === userToDelete?.id"
              class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-all duration-200 hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
              Cancel
            </button>
            <button @click="deleteUser" :disabled="isDeletingUser === userToDelete?.id"
              class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl transition-all duration-200 hover:scale-105 active:scale-95 hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
              <svg v-if="isDeletingUser === userToDelete?.id" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ isDeletingUser === userToDelete?.id ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, onUnmounted, watch, onActivated, onDeactivated } from 'vue';
import { debounce } from 'lodash';
import UserService from '@/services/userServices';
import api from '@/services/api';
import Swal from 'sweetalert2';

// ==================== CACHE KEYS ====================
const CACHE_KEYS = {
  USERS: 'user_management_users_cache',
  PAGINATION: 'user_management_pagination_cache',
  ROLES: 'user_management_roles_cache'
};
const CACHE_DURATION = 30000; // 30 seconds

// ==================== COLUMNS ====================
const columns = [
  { label: 'Name',       field: 'name',       sortable: true  },
  { label: 'Role',       field: 'role',       sortable: true  },
  { label: 'Status',     field: 'status',     sortable: true  },
  { label: 'Created At', field: 'created_at', sortable: true  },
  { label: 'Last Login', field: 'last_login', sortable: true  },
  { label: 'Actions',    field: 'actions',    sortable: false },
];

// ==================== PHONE FORMATTER (PH) ====================
const formatPHNumber = (raw) => {
  const digits = raw.replace(/\D/g, '').slice(0, 11);
  if (digits.length <= 4) return digits;
  if (digits.length <= 7) return `${digits.slice(0, 4)} ${digits.slice(4)}`;
  return `${digits.slice(0, 4)} ${digits.slice(4, 7)} ${digits.slice(7)}`;
};

const isPHContactValid = (value) => /^09\d{9}$/.test(value.replace(/\D/g, ''));

// ==================== STATE ====================
const users = ref([]);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0
});

// Dynamic roles
const availableRoles = ref([]);

const searchQuery = ref('');
const roleFilter = ref('');
const sortField = ref('created_at');
const sortDirection = ref('desc');
const currentPage = ref(1);
const itemsPerPage = ref(10);
const lastRefreshTime = ref(Date.now());
const isActive = ref(true);

// Loading states
const isAdding = ref(false);
const isEditingUser = ref(null);
const isDeletingUser = ref(null);
const formLoading = ref(false);

const showModal = ref(false);
const isEditing = ref(false);
const showPassword = ref(false);
const editingUserId = ref(null);
const resetPassword = ref(false);
const showDeleteModal = ref(false);
const userToDelete = ref(null);

// Cache for user details
const userDetailsCache = ref(new Map());

const form = reactive({
  firstName: '', middleName: '', lastName: '',
  address: '', contact: '',
  email: '', role: '', password: '', status: 'Active',
});

const errors = reactive({
  firstName: '', lastName: '', address: '', contact: '',
  email: '', role: '', password: '',
});

// Polling timer
let pollTimer = null;

// ==================== CACHE FUNCTIONS ====================
const loadFromCache = () => {
  try {
    const cachedUsers = sessionStorage.getItem(CACHE_KEYS.USERS);
    if (cachedUsers) {
      const { data, timestamp } = JSON.parse(cachedUsers);
      if (Date.now() - timestamp < CACHE_DURATION) {
        users.value = data || [];
      }
    }

    const cachedPagination = sessionStorage.getItem(CACHE_KEYS.PAGINATION);
    if (cachedPagination) {
      const { data, timestamp } = JSON.parse(cachedPagination);
      if (Date.now() - timestamp < CACHE_DURATION) {
        pagination.value = data || {
          current_page: 1,
          last_page: 1,
          per_page: 10,
          total: 0,
          from: 0,
          to: 0
        };
      }
    }

    const cachedRoles = sessionStorage.getItem(CACHE_KEYS.ROLES);
    if (cachedRoles) {
      const { data, timestamp } = JSON.parse(cachedRoles);
      if (Date.now() - timestamp < CACHE_DURATION * 6) { // Roles cache longer (3 min)
        availableRoles.value = data || [];
      }
    }
  } catch (error) {
    console.error('Failed to load from cache:', error);
  }
};

const saveToCache = () => {
  try {
    sessionStorage.setItem(CACHE_KEYS.USERS, JSON.stringify({
      data: users.value,
      timestamp: Date.now()
    }));
    
    sessionStorage.setItem(CACHE_KEYS.PAGINATION, JSON.stringify({
      data: pagination.value,
      timestamp: Date.now()
    }));
  } catch (error) {
    console.error('Failed to save to cache:', error);
  }
};

// ==================== COMPUTED ====================
const displayedPages = computed(() => {
  const pages = [];
  const max = 5;
  const total = pagination.value.last_page || 1;
  const current = pagination.value.current_page || 1;
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

// ==================== FETCH ROLES ====================
const fetchRoles = async () => {
  try {
    const response = await api.get('/roles');
    availableRoles.value = response.data.data || [];
    // Cache roles
    sessionStorage.setItem(CACHE_KEYS.ROLES, JSON.stringify({
      data: availableRoles.value,
      timestamp: Date.now()
    }));
  } catch (error) {
    console.error('Failed to fetch roles:', error);
    // Fallback to default roles
    availableRoles.value = [
      { id: 1, name: 'Lawyer' },
      { id: 2, name: 'Clerk' }
    ];
  }
};

// ==================== LIFECYCLE ====================
onMounted(async () => {
  isActive.value = true;
  // Load from cache first (instant)
  loadFromCache();
  // Then fetch fresh data
  await fetchRoles();
  await loadUsers();
  // Start polling
  startPolling();
});

onUnmounted(() => {
  isActive.value = false;
  stopPolling();
});

// Route activation/deactivation
onActivated(() => {
  console.log('User Management activated');
  isActive.value = true;
  loadFromCache();
  loadUsers();
  startPolling();
});

onDeactivated(() => {
  console.log('User Management deactivated');
  isActive.value = false;
  stopPolling();
});

// ==================== POLLING ====================
const startPolling = () => {
  stopPolling();
  if (!isActive.value) return;
  
  pollTimer = setInterval(() => {
    if (document.visibilityState === 'visible' && isActive.value) {
      // Only refresh if on first page with no active filters
      if (currentPage.value === 1 && !searchQuery.value && !roleFilter.value) {
        loadUsers();
      }
    }
  }, 15000); // 15 seconds
};

const stopPolling = () => {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
};

// Watch for page focus
watch(() => document.visibilityState, () => {
  if (document.visibilityState === 'visible' && isActive.value) {
    if (Date.now() - lastRefreshTime.value > 10000) {
      loadUsers();
    }
  }
});

// ==================== PHONE HANDLERS ====================
const onContactKeypress = (e) => { if (!/[\d]/.test(e.key)) e.preventDefault(); };
const onContactInput = () => {
  form.contact = formatPHNumber(form.contact);
  errors.contact = form.contact && !isPHContactValid(form.contact) ? 'Use format: 09XX XXX XXXX' : '';
};

// ==================== API ====================
const loadUsers = async () => {
  if (!isActive.value) return;
  
  try {
    const params = {
      search: searchQuery.value || undefined,
      role: roleFilter.value || undefined,
      sort_by: sortField.value,
      sort_direction: sortDirection.value,
      page: currentPage.value,
      per_page: itemsPerPage.value,
      _t: Date.now()
    };
    
    const response = await UserService.getUsers(params);
    
    users.value = response.data || [];
    pagination.value = response.meta || {
      current_page: currentPage.value,
      last_page: 1,
      per_page: itemsPerPage.value,
      total: users.value.length,
      from: 1,
      to: users.value.length
    };
    
    saveToCache();
    lastRefreshTime.value = Date.now();
    
  } catch (error) {
    console.error('Failed to load users:', error);
    users.value = [];
  }
};

// ==================== FILTER / SORT / PAGINATE ====================
const debouncedSearch = debounce(() => { 
  currentPage.value = 1; 
  loadUsers(); 
}, 500);

const handleFilterChange = () => { 
  currentPage.value = 1; 
  loadUsers(); 
};

const sortBy = (field) => {
  sortDirection.value = sortField.value === field 
    ? (sortDirection.value === 'asc' ? 'desc' : 'asc') 
    : 'asc';
  sortField.value = field;
  loadUsers();
};

const previousPage = () => { 
  if (currentPage.value > 1) { 
    currentPage.value--; 
    loadUsers(); 
  } 
};

const nextPage = () => { 
  if (currentPage.value < pagination.value.last_page) { 
    currentPage.value++; 
    loadUsers(); 
  } 
};

const goToPage = (page) => { 
  currentPage.value = page; 
  loadUsers(); 
};

// Watch for pagination changes
watch(currentPage, () => {
  if (isActive.value) loadUsers();
});

// ==================== UTILITIES ====================
const formatDate = (d) => d 
  ? new Date(d).toLocaleString('en-US', { 
      year: 'numeric', 
      month: '2-digit', 
      day: '2-digit', 
      hour: '2-digit', 
      minute: '2-digit', 
      hour12: true 
    }) 
  : 'N/A';

const formatLastLogin = (d) => d 
  ? new Date(d).toLocaleString('en-US', { 
      year: 'numeric', 
      month: '2-digit', 
      day: '2-digit', 
      hour: '2-digit', 
      minute: '2-digit', 
      hour12: true 
    }) 
  : 'Never';

// Helper to format user data from table/cache
const formatUserDataForForm = (userData) => {
  let firstName = '';
  let middleName = '';
  let lastName = '';
  
  if (userData.first_name) {
    firstName = userData.first_name;
  } else if (userData.name) {
    const nameParts = userData.name.split(' ');
    firstName = nameParts[0] || '';
    lastName = nameParts.slice(1).join(' ') || '';
  }
  
  if (userData.middle_name) {
    middleName = userData.middle_name;
  }
  
  if (userData.last_name) {
    lastName = userData.last_name;
  }
  
  return {
    firstName: firstName,
    middleName: middleName,
    lastName: lastName,
    address: userData.address || '',
    contact: userData.contact_number || userData.contact || '',
    email: userData.email || '',
    role: userData.role || '',
    status: userData.status || 'Active',
  };
};

// ==================== MODAL ====================
const resetForm = () => {
  Object.assign(form, { 
    firstName: '', middleName: '', lastName: '', 
    address: '', contact: '', email: '', 
    role: '', password: '', status: 'Active' 
  });
  Object.keys(errors).forEach(k => errors[k] = '');
  editingUserId.value = null; 
  resetPassword.value = false; 
  showPassword.value = false;
};

const clearErrors = () => Object.keys(errors).forEach(k => errors[k] = '');

const openAddUserModal = () => {
  resetForm();
  isEditing.value = false;
  form.password = 'temporary123';
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  resetForm();
};

// ==================== EDIT USER ====================
const editUser = (user) => {
  resetForm();
  isEditing.value = true;
  editingUserId.value = user.id;
  
  // Populate with data from the table (instant)
  const tableData = formatUserDataForForm(user);
  if (tableData.contact) tableData.contact = formatPHNumber(tableData.contact);
  Object.assign(form, tableData);
  form.password = '';
  
  showModal.value = true;

  // Check cache
  const cachedData = userDetailsCache.value.get(user.id);
  
  if (cachedData) {
    const enhancedData = formatUserDataForForm(cachedData);
    if (enhancedData.contact) enhancedData.contact = formatPHNumber(enhancedData.contact);
    Object.assign(form, enhancedData);
  } else {
    fetchUserDetails(user.id);
  }
};

const fetchUserDetails = async (userId) => {
  try {
    const response = await UserService.getUserById(userId);
    const userData = response.data || response;
    userDetailsCache.value.set(userId, userData);
    
    const enhancedData = formatUserDataForForm(userData);
    if (enhancedData.contact) enhancedData.contact = formatPHNumber(enhancedData.contact);
    
    if (showModal.value && editingUserId.value === userId) {
      Object.assign(form, enhancedData);
    }
  } catch (error) {
    console.error('Background fetch failed:', error);
  }
};

const toggleResetPassword = () => {
  resetPassword.value = !resetPassword.value;
  form.password = resetPassword.value ? 'temppass1' : '';
  if (!resetPassword.value) errors.password = '';
};

// ==================== VALIDATION ====================
const validateForm = () => {
  clearErrors(); 
  let ok = true;
  
  if (!form.firstName.trim()) { 
    errors.firstName = 'First name is required'; 
    ok = false; 
  }
  if (!form.lastName.trim()) { 
    errors.lastName = 'Last name is required'; 
    ok = false; 
  }
  if (!form.email.trim()) { 
    errors.email = 'Email is required'; 
    ok = false; 
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) { 
    errors.email = 'Invalid email format'; 
    ok = false; 
  }
  if (!form.role) { 
    errors.role = 'Role is required'; 
    ok = false; 
  }
  if (!isEditing.value && !form.password.trim()) { 
    errors.password = 'Password is required'; 
    ok = false; 
  } else if (isEditing.value && resetPassword.value && !form.password.trim()) { 
    errors.password = 'New password is required'; 
    ok = false; 
  } else if (form.password && form.password.length < 6 && form.password !== 'temppass1') { 
    errors.password = 'Minimum 6 characters'; 
    ok = false; 
  }
  if (form.contact && !isPHContactValid(form.contact)) { 
    errors.contact = 'Use format: 09XX XXX XXXX'; 
    ok = false; 
  }
  
  return ok;
};

// ==================== SUBMIT ====================
const submitForm = async () => {
  if (!validateForm()) return;
  
  formLoading.value = true;
  
  try {
    const payload = { ...form };
    
    if (isEditing.value) {
      isEditingUser.value = editingUserId.value;
      await UserService.updateUser(editingUserId.value, payload);
      if (userDetailsCache.value.has(editingUserId.value)) {
        userDetailsCache.value.delete(editingUserId.value);
      }
      
      // Update the user in the list immediately (optimistic update)
      const index = users.value.findIndex(u => u.id === editingUserId.value);
      if (index !== -1) {
        users.value[index] = {
          ...users.value[index],
          name: payload.firstName + ' ' + (payload.middleName ? payload.middleName + ' ' : '') + payload.lastName,
          email: payload.email,
          role: payload.role,
          status: payload.status,
          address: payload.address,
          contact_number: payload.contact
        };
      }
      
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'User updated successfully',
        confirmButtonColor: '#1a4972',
        confirmButtonText: 'OK'
      });
    } else {
      isAdding.value = true;
      const response = await UserService.createUser(payload);
      
      // Add new user to the list immediately
      if (response.data) {
        users.value.unshift(response.data);
      }
      
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'User created successfully',
        confirmButtonColor: '#1a4972',
        confirmButtonText: 'OK'
      });
    }
    
    await loadUsers(); // Refresh from server in background
    closeModal();
  } catch (error) {
    const handledError = UserService.handleApiError(error);
    
    if (handledError.errors) {
      const map = { 
        name: 'firstName', 
        contact_number: 'contact', 
        password: 'password', 
        email: 'email', 
        role: 'role', 
        address: 'address' 
      };
      Object.keys(handledError.errors).forEach(k => {
        const field = map[k] || k;
        if (Object.prototype.hasOwnProperty.call(errors, field)) {
          errors[field] = handledError.errors[k];
        }
      });
    }
    
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: handledError.message || 'An error occurred',
      confirmButtonColor: '#dc2626',
      confirmButtonText: 'OK'
    });
  } finally {
    formLoading.value = false;
    isAdding.value = false;
    isEditingUser.value = null;
  }
};

// ==================== DELETE ====================
const confirmDeleteUser = (user) => { 
  userToDelete.value = user; 
  showDeleteModal.value = true; 
};

const deleteUser = async () => {
  if (!userToDelete.value) return;
  
  isDeletingUser.value = userToDelete.value.id;
  
  try {
    await UserService.deleteUser(userToDelete.value.id);
    
    // Remove from list immediately (optimistic update)
    users.value = users.value.filter(u => u.id !== userToDelete.value.id);
    
    if (userDetailsCache.value.has(userToDelete.value.id)) {
      userDetailsCache.value.delete(userToDelete.value.id);
    }
    
    showDeleteModal.value = false;
    userToDelete.value = null;
    
    Swal.fire({
      icon: 'success',
      title: 'Deleted!',
      text: 'User deleted successfully',
      confirmButtonColor: '#1a4972',
      confirmButtonText: 'OK'
    });
    
    await loadUsers(); // Refresh from server in background
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: error.message || 'Failed to delete user',
      confirmButtonColor: '#dc2626',
      confirmButtonText: 'OK'
    });
  } finally {
    isDeletingUser.value = null;
  }
};
</script>

<style scoped>
/* Modal transitions */
.modal-enter-active, .modal-leave-active { 
  transition: all 0.25s ease; 
}
.modal-enter-from, .modal-leave-to { 
  opacity: 0; 
  transform: scale(0.95);
}

/* Row fade-in animation */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>