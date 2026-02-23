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
          <select v-model="filterIntake" @change="handleFilterChange"
            class="flex-1 sm:flex-none px-3 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] text-slate-600 min-w-[120px]">
            <option value="">All Intake</option>
            <option value="draft">Draft</option>
            <option value="for_approval">For Approval</option>
            <option value="approved">Approved</option>
            <option value="returned">Returned</option>
          </select>
          <select v-model="filterPriority" @change="handleFilterChange"
            class="flex-1 sm:flex-none px-3 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] text-slate-600 min-w-[110px]">
            <option value="">All Priority</option>
            <option value="urgent">Urgent</option>
            <option value="normal">Normal</option>
            <option value="low">Low</option>
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
      <div v-if="isLoading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#1a4972]"></div>
      </div>

      <div v-else class="overflow-x-auto">
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
                  <span v-if="c.category" class="px-2 py-0.5 text-[10px] font-semibold rounded-full border" :class="getCategoryBadgeClass(c.category)">
                    {{ c.category }}
                  </span>
                </div>
                <p class="text-sm font-semibold text-slate-800 max-w-[200px] truncate" :title="c.title">{{ c.title }}</p>
                <p class="text-xs text-slate-400 mt-0.5">Case #{{ c.case_no }}</p>
              </td>

              <td class="px-4 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0" :class="getCategoryBgClass(c.category)">
                    {{ getInitials(c.client) }}
                  </div>
                  <span class="text-sm text-slate-700 font-medium whitespace-nowrap">{{ c.client }}</span>
                </div>
              </td>

              <td class="px-4 py-4">
                <div class="flex flex-col gap-1.5">
                  <div class="flex items-center gap-1.5">
                    <div class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold text-white flex-shrink-0 bg-[#1a4972]">
                      {{ getInitials(c.lawyer) }}
                    </div>
                    <span class="text-xs text-slate-400 w-9 flex-shrink-0">Atty.</span>
                    <span class="text-xs text-slate-700 font-medium whitespace-nowrap">{{ c.lawyer }}</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <div class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold text-white flex-shrink-0 bg-slate-400">
                      {{ getInitials(c.clerk) }}
                    </div>
                    <span class="text-xs text-slate-400 w-9 flex-shrink-0">Clerk</span>
                    <span class="text-xs text-slate-700 font-medium whitespace-nowrap">{{ c.clerk }}</span>
                  </div>
                </div>
              </td>

              <td class="px-4 py-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg" :class="priorityClass(c.priority)">
                  <span class="w-1.5 h-1.5 rounded-full" :class="priorityDotClass(c.priority)"></span>
                  {{ capitalize(c.priority) }}
                </span>
              </td>

              <td class="px-4 py-4">
                <span class="px-2.5 py-1 text-xs font-semibold rounded-lg" :class="caseStatusClass(c.case_status)">
                  {{ capitalize(c.case_status) }}
                </span>
              </td>

              <td class="px-4 py-4">
                <div class="flex items-center gap-1">
                  <button @click="openView(c)"
                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#1a4972] transition-colors hover-navy-bg">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    View
                  </button>
                  <button @click="openEdit(c)"
                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-[#1a4972] transition-colors hover-navy-bg">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!isLoading && cases.length === 0">
              <td :colspan="columns.length" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center">
                  <div class="w-14 h-14 rounded-2xl navy-bg-8 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-[#1a4972] opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
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
          Showing <span class="font-semibold text-slate-700">{{ pagination.from }}</span>–<span class="font-semibold text-slate-700">{{ pagination.to }}</span>
          of <span class="font-semibold text-slate-700">{{ pagination.total }}</span> cases
        </p>
        <div class="flex items-center gap-1">
          <button @click="previousPage" :disabled="pagination.current_page === 1"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
            :class="pagination.current_page === 1 ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-200'">
            ← Prev
          </button>
          <button v-for="page in displayedPages" :key="page" @click="goToPage(page)"
            class="w-7 h-7 rounded-lg text-xs font-medium transition-all"
            :class="pagination.current_page === page ? 'bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] text-white' : 'text-slate-600 hover:bg-slate-200'">
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
    <!-- CREATE / EDIT MODAL  (max-w-5xl)                            -->
    <!-- ============================================================ -->
    <Transition name="modal">
      <div v-if="showFormModal" class="fixed inset-0 z-50 flex items-start sm:items-center justify-center p-0 sm:p-4" @click.self="closeForm">
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
            <button @click="closeForm" class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Body -->
          <div class="px-6 sm:px-10 py-6 overflow-y-auto space-y-7">

            <!-- Auto-generated Code Banner -->
            <div v-if="!isEditing" class="flex items-center gap-3 px-4 py-3 rounded-xl border border-dashed border-[#1a4972]/30 navy-bg-5">
              <svg class="w-4 h-4 flex-shrink-0 text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
              </svg>
              <div>
                <p class="text-xs text-slate-500 font-medium">Auto-Generated Case Code</p>
                <p class="text-sm font-bold tracking-widest text-[#1a4972]">{{ previewCode }}</p>
              </div>
            </div>

            <!-- SECTION: Case Information -->
            <section>
              <p class="text-xs font-bold uppercase tracking-widest mb-4 pb-2 border-b border-slate-100 text-[#1a4972]">Case Information</p>
              <div class="space-y-4">

                <!-- Title + Case No -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Case Title <span class="text-red-500">*</span></label>
                    <input v-model="form.title" type="text" placeholder="e.g. Cruz vs. Santos — Civil Case for Annulment"
                      class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all"
                      :class="{ 'border-red-400': errors.title }" />
                    <p v-if="errors.title" class="text-xs text-red-500 mt-1">{{ errors.title }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Case Number <span class="text-red-500">*</span></label>
                    <input v-model="form.case_no" type="number" placeholder="e.g. 12345"
                      class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all"
                      :class="{ 'border-red-400': errors.case_no }" />
                    <p v-if="errors.case_no" class="text-xs text-red-500 mt-1">{{ errors.case_no }}</p>
                  </div>
                </div>

                <!-- Category + Client -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Category</label>
                    <select v-model="form.category_id"
                      class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all">
                      <option value="">— Select Category —</option>
                      <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                  </div>
                  <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                      Client <span class="text-slate-400 font-normal text-xs ml-1">(Optional)</span>
                    </label>
                    <div class="flex gap-2">
                      <div class="relative flex-1" ref="clientDropdownRef">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input v-model="clientSearch"
                          @focus="clientDropdownOpen = true"
                          @input="clientDropdownOpen = true; if(!clientSearch) clearClient()"
                          type="text" placeholder="Search client name..."
                          class="w-full pl-9 pr-8 py-2.5 text-sm border rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all"
                          :class="form.client_id ? 'border-[#1a4972] font-medium text-slate-800' : 'border-slate-200 text-slate-500'" />
                        <button v-if="clientSearch || form.client_id" type="button" @click.prevent="clearClient"
                          class="absolute right-2.5 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                          </svg>
                        </button>
                        <Transition name="dropdown">
                          <div v-if="clientDropdownOpen" class="absolute z-20 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                            <div v-if="filteredClients.length > 0" class="max-h-44 overflow-y-auto">
                              <div v-for="cl in filteredClients" :key="cl.id" @mousedown.prevent="selectClient(cl)"
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
                      <button type="button" @click="openNewClient" title="Create new client"
                        class="flex-shrink-0 w-11 h-11 rounded-xl border-2 border-dashed border-[#1a4972]/30 hover:border-[#1a4972] flex items-center justify-center transition-all">
                        <svg class="w-4 h-4 text-[#1a4972]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                      </button>
                    </div>
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

                <!-- Court / Office + Docket No. with N/A toggle -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <div class="flex items-center justify-between mb-1.5">
                      <label class="text-sm font-semibold text-slate-700">Court / Office</label>
                      <label class="flex items-center gap-1.5 cursor-pointer select-none">
                        <input type="checkbox" v-model="courtNA" @change="onCourtNAChange"
                          class="w-3.5 h-3.5 rounded accent-[#1a4972] cursor-pointer" />
                        <span class="text-xs text-slate-500 font-medium">N/A</span>
                      </label>
                    </div>
                    <input v-model="form.court_or_office" type="text"
                      :placeholder="courtNA ? 'Not Applicable' : 'e.g. RTC Branch 7, Manila'"
                      :disabled="courtNA"
                      class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" />
                  </div>
                  <div>
                    <div class="flex items-center justify-between mb-1.5">
                      <label class="text-sm font-semibold text-slate-700">Docket No.</label>
                      <label class="flex items-center gap-1.5 cursor-pointer select-none">
                        <input type="checkbox" v-model="docketNA" @change="onDocketNAChange"
                          class="w-3.5 h-3.5 rounded accent-[#1a4972] cursor-pointer" />
                        <span class="text-xs text-slate-500 font-medium">N/A</span>
                      </label>
                    </div>
                    <input v-model="form.docket_no" type="text"
                      :placeholder="docketNA ? 'Not Applicable' : 'e.g. Civil Case No. 2024-001'"
                      :disabled="docketNA"
                      class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" />
                  </div>
                </div>

              </div>
            </section>

            <!-- SECTION: Assignment & Priority -->
            <section>
              <p class="text-xs font-bold uppercase tracking-widest mb-4 pb-2 border-b border-slate-100 text-[#1a4972]">Assignment & Priority</p>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">Assigned Lawyer <span class="text-red-500">*</span></label>
                  <select v-model="form.assigned_lawyer_id"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all"
                    :class="{ 'border-red-400': errors.assigned_lawyer_id }">
                    <option value="">— Select Lawyer —</option>
                    <option v-for="u in lawyers" :key="u.id" :value="u.id">{{ u.name }}</option>
                  </select>
                  <p v-if="errors.assigned_lawyer_id" class="text-xs text-red-500 mt-1">{{ errors.assigned_lawyer_id }}</p>
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">Assigned Clerk</label>
                  <select v-model="form.assigned_clerk_id"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all">
                    <option value="">— Select Clerk —</option>
                    <option v-for="u in clerks" :key="u.id" :value="u.id">{{ u.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">Priority</label>
                  <select v-model="form.priority"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all">
                    <option value="low">Low</option>
                    <option value="normal">Normal</option>
                    <option value="urgent">Urgent</option>
                  </select>
                </div>
              </div>
            </section>

            <!-- SECTION: Status & Notes -->
            <section>
              <p class="text-xs font-bold uppercase tracking-widest mb-4 pb-2 border-b border-slate-100 text-[#1a4972]">Status & Notes</p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">Intake Status</label>
                  <select v-model="form.intake_status"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all">
                    <option value="draft">Draft</option>
                    <option value="for_approval">For Approval</option>
                    <option value="approved">Approved</option>
                    <option value="returned">Returned</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">Case Status</label>
                  <select v-model="form.case_status"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] text-slate-600 transition-all">
                    <option value="active">Active</option>
                    <option value="closed">Closed</option>
                    <option value="archived">Archived</option>
                  </select>
                </div>
                <div class="sm:col-span-2">
                  <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Notes <span class="text-slate-400 font-normal text-xs ml-1">(Optional)</span>
                  </label>
                  <textarea v-model="form.summary" rows="3" placeholder="Brief summary of the case…"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 transition-all resize-none"></textarea>
                </div>
              </div>
            </section>

          </div>

          <!-- Footer -->
          <div class="flex items-center justify-end gap-3 px-6 sm:px-10 py-4 border-t border-slate-100 bg-slate-50/50 flex-shrink-0">
            <button @click="closeForm" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 transition-all">
              Cancel
            </button>
            <button @click="submitForm" :disabled="formLoading"
              class="px-6 py-2.5 text-sm font-semibold text-white rounded-xl active:scale-95 disabled:opacity-60 flex items-center gap-2 min-w-[130px] justify-center bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] shadow-lg shadow-[#1a4972]/30 hover:shadow-xl transition-all">
              <svg v-if="formLoading" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ formLoading ? 'Saving...' : (isEditing ? 'Save Changes' : 'Create Case') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ============================================================ -->
    <!-- NEW CLIENT MODAL                                             -->
    <!-- ============================================================ -->
    <Transition name="modal">
      <div v-if="showNewClientModal" class="fixed inset-0 z-[60] flex items-start sm:items-center justify-center p-0 sm:p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeNewClient"></div>
        <div class="relative bg-white w-full sm:rounded-2xl shadow-2xl sm:max-w-[520px] overflow-hidden">
          <div class="flex items-center justify-between px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-base font-bold text-slate-800">New Client</h2>
                <p class="text-xs text-slate-500">Quick-create and auto-assign to this case</p>
              </div>
            </div>
            <button @click="closeNewClient" class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
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
            <button @click="closeNewClient" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 transition-all">
              Cancel
            </button>
            <button @click="saveNewClient" :disabled="clientSaving"
              class="px-5 py-2.5 text-sm font-semibold text-white rounded-xl active:scale-95 disabled:opacity-60 flex items-center gap-2 min-w-[130px] justify-center bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg shadow-emerald-500/30 transition-all">
              <svg v-if="clientSaving" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ clientSaving ? 'Saving...' : 'Create Client' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ============================================================ -->
    <!-- VIEW CASE MODAL  (max-w-4xl, large text)                    -->
    <!-- ============================================================ -->
    <Transition name="modal">
      <div v-if="showViewModal" class="fixed inset-0 z-50 flex items-start sm:items-center justify-center p-0 sm:p-4" @click.self="showViewModal = false">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div v-if="viewCase" class="relative bg-white w-full sm:rounded-2xl shadow-2xl sm:max-w-4xl max-h-screen sm:max-h-[92vh] flex flex-col overflow-hidden">

          <!-- Header -->
          <div class="flex items-center justify-between px-6 sm:px-8 py-5 sm:py-6 border-b border-slate-100 flex-shrink-0">
            <div class="flex items-center gap-4">
              <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center" :class="getCategoryLightBgClass(viewCase.category)">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" :class="getCategoryTextClass(viewCase.category)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-bold text-slate-800">Case Profile</h2>
                <p class="text-sm text-slate-500">Full case details and assignments</p>
              </div>
            </div>
            <button @click="showViewModal = false" class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Body -->
          <div class="px-6 sm:px-8 py-6 overflow-y-auto space-y-6">

            <!-- Hero Banner -->
            <div class="rounded-2xl px-6 sm:px-8 py-5 sm:py-6 border-2" :class="getCategoryBorderClass(viewCase.category) + ' ' + getCategoryLightBgClass(viewCase.category)">
              <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2.5 mb-2 flex-wrap">
                    <p class="text-sm font-bold tracking-widest" :class="getCategoryTextClass(viewCase.category)">{{ viewCase.case_code }}</p>
                    <span v-if="viewCase.category" class="px-2.5 py-0.5 text-xs font-semibold rounded-full border" :class="getCategoryBadgeClass(viewCase.category)">
                      {{ viewCase.category }}
                    </span>
                  </div>
                  <p class="text-xl sm:text-2xl font-bold text-slate-800 leading-snug">{{ viewCase.title }}</p>
                  <p class="text-sm text-slate-500 mt-1.5 font-medium">Case No. {{ viewCase.case_no }}</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
                  <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold rounded-lg" :class="priorityClass(viewCase.priority)">
                    <span class="w-2 h-2 rounded-full" :class="priorityDotClass(viewCase.priority)"></span>
                    {{ capitalize(viewCase.priority) }}
                  </span>
                  <span class="px-3 py-1.5 text-sm font-semibold rounded-lg" :class="intakeClass(viewCase.intake_status)">
                    {{ formatIntake(viewCase.intake_status) }}
                  </span>
                  <span class="px-3 py-1.5 text-sm font-semibold rounded-lg" :class="caseStatusClass(viewCase.case_status)">
                    {{ capitalize(viewCase.case_status) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Details Grid — 3 cols on lg -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-5">
              <div v-for="f in viewFields" :key="f.label">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1.5">{{ f.label }}</p>
                <p class="text-base font-semibold text-slate-800">{{ f.value }}</p>
              </div>
            </div>

            <!-- Summary -->
            <div v-if="viewCase.summary" class="rounded-xl bg-slate-50 border border-slate-200 px-5 sm:px-6 py-4">
              <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Summary / Notes</p>
              <p class="text-base text-slate-700 leading-relaxed">{{ viewCase.summary }}</p>
            </div>

          </div>

          <!-- Footer -->
          <div class="flex items-center justify-end gap-3 px-6 sm:px-8 py-4 border-t border-slate-100 bg-slate-50/50 flex-shrink-0">
            <button @click="showViewModal = false" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
              Close
            </button>
            <button @click="openEdit(viewCase); showViewModal = false"
              class="px-5 py-2.5 text-sm font-semibold text-white rounded-xl active:scale-95 bg-gradient-to-br from-[#1a4972] to-[#0f2f4a] shadow-lg shadow-[#1a4972]/30 transition-all">
              Edit Case
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import * as CaseService from '@/services/caseService';
import * as ClientService from '@/services/clientService';

// ==================== COLUMNS ====================
const columns = [
  { label: 'Case Code / Title', field: 'case_code',   sortable: true },
  { label: 'Client',            field: 'client',      sortable: true },
  { label: 'Assigned To',       field: 'lawyer',      sortable: false },
  { label: 'Priority',          field: 'priority',    sortable: true },
  { label: 'Case Status',       field: 'case_status', sortable: true },
  { label: 'Actions',           field: 'actions',     sortable: false },
];

// ==================== CATEGORY MAP ====================
const categoryMap = {
  'criminal':      { text: 'text-red-700',    bg: 'bg-red-600',    badge: 'bg-red-50 text-red-700 border-red-200',         border: 'border-red-200',    lightBg: 'bg-red-50/60' },
  'annulment':     { text: 'text-purple-700', bg: 'bg-purple-600', badge: 'bg-purple-50 text-purple-700 border-purple-200', border: 'border-purple-200', lightBg: 'bg-purple-50/60' },
  'civil':         { text: 'text-blue-700',   bg: 'bg-blue-600',   badge: 'bg-blue-50 text-blue-700 border-blue-200',       border: 'border-blue-200',   lightBg: 'bg-blue-50/60' },
  'land issues':   { text: 'text-amber-700',  bg: 'bg-amber-600',  badge: 'bg-amber-50 text-amber-700 border-amber-200',    border: 'border-amber-200',  lightBg: 'bg-amber-50/60' },
  'land transfer': { text: 'text-orange-700', bg: 'bg-orange-600', badge: 'bg-orange-50 text-orange-700 border-orange-200', border: 'border-orange-200', lightBg: 'bg-orange-50/60' },
  'pending':       { text: 'text-slate-600',  bg: 'bg-slate-500',  badge: 'bg-slate-100 text-slate-600 border-slate-300',   border: 'border-slate-200',  lightBg: 'bg-slate-50/60' },
  'admin':         { text: 'text-indigo-700', bg: 'bg-indigo-600', badge: 'bg-indigo-50 text-indigo-700 border-indigo-200', border: 'border-indigo-200', lightBg: 'bg-indigo-50/60' },
};
const defaultCat = { text: 'text-[#1a4972]', bg: 'bg-[#1a4972]', badge: 'bg-blue-50 text-[#1a4972] border-blue-200', border: 'border-blue-200', lightBg: 'bg-blue-50/40' };

const getCategoryEntry      = (cat) => cat ? (categoryMap[cat.toLowerCase().trim()] ?? defaultCat) : defaultCat;
const getCategoryTextClass  = (cat) => getCategoryEntry(cat).text;
const getCategoryBgClass    = (cat) => getCategoryEntry(cat).bg;
const getCategoryBadgeClass = (cat) => getCategoryEntry(cat).badge;
const getCategoryBorderClass= (cat) => getCategoryEntry(cat).border;
const getCategoryLightBgClass=(cat) => getCategoryEntry(cat).lightBg;

// ==================== STATE ====================
const categories = ref([]);
const clients    = ref([]);
const lawyers    = ref([]);
const clerks     = ref([]);
const cases      = ref([]);
const isLoading  = ref(false);

const searchQuery    = ref('');
const filterStatus   = ref('');
const filterIntake   = ref('');
const filterPriority = ref('');
const sortField      = ref('created_at');
const sortDirection  = ref('desc');
const currentPage    = ref(1);
const pagination     = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 });

const clientSearch       = ref('');
const clientDropdownOpen = ref(false);
const clientDropdownRef  = ref(null);

const showFormModal      = ref(false);
const isEditing          = ref(false);
const editingId          = ref(null);
const formLoading        = ref(false);
const newlyCreatedClient = ref('');
const courtNA            = ref(false);
const docketNA           = ref(false);

const defaultForm = () => ({
  case_no: '', title: '', category_id: '', client_id: '',
  court_or_office: '', docket_no: '',
  assigned_lawyer_id: '', assigned_clerk_id: '',
  priority: 'normal', intake_status: 'for_approval', case_status: 'active', summary: '',
});
const form   = reactive(defaultForm());
const errors = reactive({ title: '', assigned_lawyer_id: '', case_no: '' });

const showNewClientModal = ref(false);
const clientSaving       = ref(false);
const defaultCF = () => ({ first_name: '', middle_name: '', last_name: '', contact_no: '', email: '', address: '' });
const clientForm   = reactive(defaultCF());
const clientErrors = reactive({ first_name: '', last_name: '', email: '', contact_no: '' });

const showViewModal = ref(false);
const viewCase      = ref(null);

// ==================== COMPUTED ====================
const filteredClients = computed(() => {
  const q = clientSearch.value.toLowerCase().trim();
  return q ? clients.value.filter(c => c.full_name.toLowerCase().includes(q)) : clients.value;
});

const displayedPages = computed(() => {
  const pages = [];
  const max   = 5;
  const total = pagination.value.last_page;
  if (total <= max) {
    for (let i = 1; i <= total; i++) pages.push(i);
  } else {
    let s = Math.max(1, pagination.value.current_page - 2);
    let e = Math.min(total, s + max - 1);
    if (e - s + 1 < max) s = Math.max(1, e - max + 1);
    for (let i = s; i <= e; i++) pages.push(i);
  }
  return pages;
});

const previewCode = computed(() => {
  const y = new Date().getFullYear();
  return `${y}-${String(pagination.value.total + 1).padStart(4, '0')}`;
});

const viewFields = computed(() => {
  if (!viewCase.value) return [];
  return [
    { label: 'Client',          value: viewCase.value.client || '—' },
    { label: 'Court / Office',  value: viewCase.value.court_or_office || '—' },
    { label: 'Docket No.',      value: viewCase.value.docket_no || '—' },
    { label: 'Assigned Lawyer', value: 'Atty. ' + (viewCase.value.lawyer || '—') },
    { label: 'Assigned Clerk',  value: viewCase.value.clerk || '—' },
    { label: 'Date Filed',      value: formatDate(viewCase.value.created_at) },
  ];
});

// ==================== UTILITIES ====================
const toArray = (v) => {
  if (Array.isArray(v)) return v;
  if (Array.isArray(v?.data)) return v.data;
  if (Array.isArray(v?.data?.data)) return v.data.data;
  return [];
};

const getInitials  = (n) => n ? n.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase() : '??';
const capitalize   = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
const formatIntake = (s) => ({ draft: 'Draft', for_approval: 'For Approval', approved: 'Approved', returned: 'Returned' }[s] || s);
const formatDate   = (d) => d ? new Date(d).toLocaleString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';

const getCategoryNameById   = (id) => { const cat = categories.value.find(c => c.id === id); return cat ? cat.name : ''; };
const getSelectedCategoryName = () => getCategoryNameById(form.category_id);

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

const intakeClass = (s) => ({
  draft:        'bg-slate-100 text-slate-600',
  for_approval: 'bg-amber-50 text-amber-700',
  approved:     'bg-emerald-50 text-emerald-700',
  returned:     'bg-red-50 text-red-700',
}[s] || 'bg-slate-100 text-slate-500');

const caseStatusClass = (s) => ({
  active:   'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
  closed:   'bg-red-50 text-red-700 ring-1 ring-red-200',
  archived: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
}[s] || 'bg-slate-100 text-slate-500');

// ==================== N/A HANDLERS ====================
const onCourtNAChange  = () => { form.court_or_office = courtNA.value  ? 'N/A' : ''; };
const onDocketNAChange = () => { form.docket_no       = docketNA.value ? 'N/A' : ''; };

// ==================== API ====================
const loadLookups = async () => {
  const [catRes, clientRes, userRes] = await Promise.allSettled([
    CaseService.getCategories(),
    ClientService.getAll(),
    CaseService.getAssignableUsers(),
  ]);
  if (catRes.status === 'fulfilled')    categories.value = toArray(catRes.value);
  if (clientRes.status === 'fulfilled') clients.value    = toArray(clientRes.value);
  if (userRes.status === 'fulfilled') {
    const users = toArray(userRes.value);
    lawyers.value = users.filter(u => u.role === 'lawyer');
    clerks.value  = users.filter(u => u.role === 'clerk');
  }
};

const loadCases = async () => {
  isLoading.value = true;
  try {
    const res = await CaseService.getCases({
      search:         searchQuery.value    || undefined,
      case_status:    filterStatus.value   || undefined,
      intake_status:  filterIntake.value   || undefined,
      priority:       filterPriority.value || undefined,
      sort_by:        sortField.value,
      sort_direction: sortDirection.value,
      page:           currentPage.value,
      per_page:       pagination.value.per_page,
    });
    const data = toArray(res);
    const m    = res?.meta ?? res?.data?.meta ?? {};
    cases.value = data.map(c => CaseService.formatCase(c));
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
  } catch (e) { console.error('loadCases:', e); }
  finally { isLoading.value = false; }
};

// ==================== EVENTS ====================
let searchTimer = null;
const debouncedSearch    = () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => { currentPage.value = 1; loadCases(); }, 300); };
const handleFilterChange = () => { currentPage.value = 1; loadCases(); };
const sortBy = (field) => {
  sortDirection.value = sortField.value === field ? (sortDirection.value === 'asc' ? 'desc' : 'asc') : 'asc';
  sortField.value = field;
  loadCases();
};
const previousPage = () => { if (pagination.value.current_page > 1) { currentPage.value--; loadCases(); } };
const nextPage     = () => { if (pagination.value.current_page < pagination.value.last_page) { currentPage.value++; loadCases(); } };
const goToPage     = (page) => { currentPage.value = page; loadCases(); };

const selectClient       = (cl) => { form.client_id = cl.id; clientSearch.value = cl.full_name; clientDropdownOpen.value = false; newlyCreatedClient.value = ''; };
const clearClient        = ()   => { form.client_id = '';    clientSearch.value = '';            newlyCreatedClient.value = '';     clientDropdownOpen.value = false; };
const handleOutsideClick = (e)  => { if (clientDropdownRef.value && !clientDropdownRef.value.contains(e.target)) clientDropdownOpen.value = false; };

const clearErrors = () => { errors.title = ''; errors.assigned_lawyer_id = ''; errors.case_no = ''; };
const closeForm   = () => { showFormModal.value = false; clientDropdownOpen.value = false; };

const openCreate = () => {
  isEditing.value = false; editingId.value = null; newlyCreatedClient.value = '';
  courtNA.value = false; docketNA.value = false;
  Object.assign(form, defaultForm()); clientSearch.value = ''; clearErrors();
  showFormModal.value = true;
};

const openEdit = (c) => {
  isEditing.value = true; editingId.value = c.id; newlyCreatedClient.value = '';
  Object.assign(form, {
    case_no: c.case_no, title: c.title, category_id: c.category_id, client_id: c.client_id,
    court_or_office: c.court_or_office, docket_no: c.docket_no,
    assigned_lawyer_id: c.assigned_lawyer_id, assigned_clerk_id: c.assigned_clerk_id,
    priority: c.priority, intake_status: c.intake_status, case_status: c.case_status, summary: c.summary || '',
  });
  courtNA.value  = c.court_or_office === 'N/A';
  docketNA.value = c.docket_no === 'N/A';
  clientSearch.value = clients.value.find(x => x.id === c.client_id)?.full_name || '';
  clearErrors(); showFormModal.value = true;
};

const validateForm = () => {
  clearErrors(); let ok = true;
  if (!form.title.trim())       { errors.title = 'Case title is required';        ok = false; }
  if (!form.case_no)            { errors.case_no = 'Case number is required';     ok = false; }
  if (!form.assigned_lawyer_id) { errors.assigned_lawyer_id = 'Assign a lawyer';  ok = false; }
  return ok;
};

const submitForm = async () => {
  if (!validateForm()) return;
  formLoading.value = true;
  try {
    if (isEditing.value) await CaseService.update(editingId.value, form);
    else                 await CaseService.store(form);
    await loadCases(); closeForm();
  } catch (e) {
    if (e.errors?.title)              errors.title              = e.errors.title[0];
    if (e.errors?.case_no)            errors.case_no            = e.errors.case_no[0];
    if (e.errors?.assigned_lawyer_id) errors.assigned_lawyer_id = e.errors.assigned_lawyer_id[0];
  } finally { formLoading.value = false; }
};

const openNewClient  = () => { Object.assign(clientForm, defaultCF()); Object.assign(clientErrors, { first_name: '', last_name: '', email: '', contact_no: '' }); showNewClientModal.value = true; };
const closeNewClient = () => { showNewClientModal.value = false; };

const validateClient = () => {
  Object.assign(clientErrors, { first_name: '', last_name: '', email: '', contact_no: '' });
  let ok = true;
  if (!clientForm.first_name.trim()) { clientErrors.first_name = 'Required'; ok = false; }
  if (!clientForm.last_name.trim())  { clientErrors.last_name  = 'Required'; ok = false; }
  if (clientForm.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(clientForm.email)) { clientErrors.email = 'Invalid email'; ok = false; }
  if (clientForm.contact_no && clientForm.contact_no.length > 0 && clientForm.contact_no.length < 10) { clientErrors.contact_no = 'Min 10 digits'; ok = false; }
  return ok;
};

const saveNewClient = async () => {
  if (!validateClient()) return;
  clientSaving.value = true;
  try {
    const full_name = [clientForm.first_name.trim(), clientForm.middle_name.trim(), clientForm.last_name.trim()].filter(Boolean).join(' ');
    const res    = await ClientService.create({ full_name, contact_no: clientForm.contact_no, email: clientForm.email, address: clientForm.address });
    const nc     = res?.data?.data ?? res?.data ?? res;
    const client = { ...nc, full_name: nc.full_name ?? full_name };
    clients.value = [...clients.value, client];
    form.client_id = client.id; clientSearch.value = client.full_name; newlyCreatedClient.value = client.full_name;
    clientDropdownOpen.value = false; closeNewClient();
  } catch (e) {
    const errs = e?.response?.data?.errors ?? e?.errors ?? {};
    if (errs.email)      clientErrors.email      = errs.email[0];
    if (errs.contact_no) clientErrors.contact_no = errs.contact_no[0];
  } finally { clientSaving.value = false; }
};

const openView = (c) => { viewCase.value = c; showViewModal.value = true; };

onMounted(async () => {
  await Promise.all([loadLookups(), loadCases()]);
  document.addEventListener('mousedown', handleOutsideClick);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleOutsideClick);
  clearTimeout(searchTimer);
});
</script>

<style scoped>
/* Modal transitions */
.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }

/* Dropdown transition */
.dropdown-enter-active { transition: all 0.15s ease; }
.dropdown-enter-from { opacity: 0; transform: translateY(-6px); }
.dropdown-leave-active { transition: all 0.1s ease; }
.dropdown-leave-to { opacity: 0; }

/* Fade slide */
.fade-slide-enter-active { transition: all 0.3s ease; }
.fade-slide-enter-from { opacity: 0; transform: translateY(-4px); }

/* Navy hover background */
.hover-navy-bg:hover { background-color: rgba(26, 73, 114, 0.08); }

/* Navy background utilities */
.navy-bg-5  { background-color: rgba(26, 73, 114, 0.05); }
.navy-bg-8  { background-color: rgba(26, 73, 114, 0.08); }
.navy-bg-10 { background-color: rgba(26, 73, 114, 0.10); }
</style>