<template>
  <div class="p-3 sm:p-4 md:p-6" style="font-family: 'Inter', 'Segoe UI', sans-serif;">

    <!-- ── Enhanced Page Header ── -->
    <div class="mb-5 md:mb-6">
      <div class="flex items-start justify-between flex-wrap gap-4">
        <div>
          <div class="flex items-center gap-3 mb-1.5">
            <div class="w-1 h-8 rounded-full bg-blue-600"></div>
            <div>
              <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900">
                Approvals Dashboard
              </h1>
              <p class="text-xs md:text-sm text-slate-500 mt-0.5">Review and manage pending requests</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- DOCUMENT MOVEMENTS VIEW -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeView === 'movements'">
      <!-- ── Enhanced Filter Bar ── -->
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5 mb-4">
        <!-- Search -->
        <div class="relative mb-4">
          <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
          <input v-model="searchQuery" type="text"
            placeholder="Search by case code, task, clerk name, or purpose..."
            class="w-full pl-11 pr-4 py-3 text-sm bg-slate-50/80 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100 placeholder-slate-400 transition-all duration-200"/>
        </div>
        
        <!-- Filter Dropdowns -->
        <div class="flex flex-wrap gap-2.5">
          <select v-model="filterStatus"
            class="flex-1 min-w-[130px] px-4 py-2.5 text-sm bg-slate-50/80 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100 text-slate-700 font-medium cursor-pointer transition-all duration-200">
            <option value="ALL">All Status</option>
            <option value="PENDING">⏳ Pending</option>
            <option value="APPROVED">✓ Approved</option>
            <option value="REJECTED">✗ Rejected</option>
          </select>
          
          <select v-model="filterType"
            class="flex-1 min-w-[130px] px-4 py-2.5 text-sm bg-slate-50/80 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100 text-slate-700 font-medium cursor-pointer transition-all duration-200">
            <option value="ALL">All Types</option>
            <option value="checklist">📋 Checklist</option>
            <option value="folder">📁 Folder</option>
          </select>
          
          <select v-model="filterDirection"
            class="flex-1 min-w-[120px] px-4 py-2.5 text-sm bg-slate-50/80 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100 text-slate-700 font-medium cursor-pointer transition-all duration-200">
            <option value="ALL">In & Out</option>
            <option value="OUT">↗ OUT only</option>
            <option value="IN">↙ IN only</option>
          </select>
          
          <button v-if="hasActiveFilters" @click="clearFilters"
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all duration-200 whitespace-nowrap border border-slate-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Clear Filters
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!visibleRows.length"
        class="bg-white rounded-xl shadow-sm border border-slate-200 py-16 flex flex-col items-center gap-5 text-center px-4">
        <div class="w-20 h-20 rounded-xl bg-slate-100 flex items-center justify-center">
          <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div>
          <p class="text-base font-bold text-slate-700 mb-1">No records found</p>
          <p class="text-sm text-slate-500">Try adjusting your filters to see more results.</p>
        </div>
        <button v-if="hasActiveFilters" @click="clearFilters"
          class="px-5 py-2.5 text-sm font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-xl transition-all duration-200 border border-blue-200">
          Clear all filters
        </button>
      </div>

      <!-- Content -->
      <div v-else class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- DESKTOP: Table -->
        <div class="hidden md:block overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">Case</th>
                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">Type</th>
                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">Direction</th>
                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600">Document / Task</th>
                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">From / To</th>
                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">Recorded By</th>
                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">Date</th>
                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="mv in visibleRows" :key="`${mv._source}-${mv.id}`"
                class="group transition-all duration-200"
                :class="mv.approval_status === 'PENDING' ? 'bg-amber-50/50 hover:bg-amber-50/70' : 'hover:bg-slate-50/70'">
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm font-bold text-blue-700">{{ mv.case_code ?? `Case #${mv.case_id}` }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg border"
                    :class="mv._source === 'checklist' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-100 text-slate-700 border-slate-300'">
                    {{ mv._source === 'checklist' ? '📋 Checklist' : '📁 Folder' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center justify-center w-14 py-1.5 rounded-lg text-xs font-black border"
                    :class="mv.type === 'OUT' ? 'bg-rose-50 text-rose-700 border-rose-300' : 'bg-emerald-50 text-emerald-700 border-emerald-300'">
                    {{ mv.type }}
                  </span>
                </td>
                <td class="px-6 py-4 max-w-[200px]">
                  <p class="text-sm text-slate-800 font-semibold truncate">
                    {{ mv.checklist?.task ?? (mv.type === 'OUT' ? 'Physical folder sent out' : 'Physical folder returned') }}
                  </p>
                  <p v-if="mv.purpose" class="text-xs text-slate-500 truncate mt-1">{{ mv.purpose }}</p>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm text-slate-700 font-medium">{{ mv.from_to ?? '—' }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-xs font-black text-blue-700 flex-shrink-0 border border-blue-200">
                      {{ getInitials(mv.recorder?.full_name) }}
                    </div>
                    <span class="text-sm text-slate-700 font-medium">{{ mv.recorder?.full_name ?? '—' }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm text-slate-600">{{ formatDate(mv.date) }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="mv.approval_status === 'PENDING'" class="flex items-center gap-2">
                    <button @click="decideMovement(mv, 'APPROVED')"
                      :disabled="processingId === `${mv._source}-${mv.id}`"
                      class="flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-40 transition-all duration-200 shadow-sm">
                      <svg v-if="processingId === `${mv._source}-${mv.id}`" class="animate-spin w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                      </svg>
                      <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                      </svg>
                      Approve
                    </button>
                    <button @click="decideMovement(mv, 'REJECTED')"
                      :disabled="processingId === `${mv._source}-${mv.id}`"
                      class="flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-lg border-2 border-red-300 text-red-700 hover:bg-red-50 hover:border-red-400 disabled:opacity-40 transition-all duration-200">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                      Reject
                    </button>
                  </div>
                  <span v-else class="text-xs text-slate-400 font-semibold italic">✓ Reviewed</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- MOBILE: Cards -->
        <div class="md:hidden divide-y divide-slate-200">
          <div v-for="mv in visibleRows" :key="`m-${mv._source}-${mv.id}`"
            class="p-4 transition-all duration-200"
            :class="mv.approval_status === 'PENDING' ? 'bg-amber-50/50' : 'bg-white hover:bg-slate-50/50'">
            <div class="flex items-start justify-between gap-3 mb-3">
              <div class="flex items-center gap-2 flex-wrap flex-1">
                <span class="text-sm font-black text-blue-700">{{ mv.case_code ?? `Case #${mv.case_id}` }}</span>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-lg border"
                  :class="mv._source === 'checklist' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-100 text-slate-700 border-slate-300'">
                  {{ mv._source === 'checklist' ? '📋 Checklist' : '📁 Folder' }}
                </span>
                <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg text-xs font-black border"
                  :class="mv.type === 'OUT' ? 'bg-rose-50 text-rose-700 border-rose-300' : 'bg-emerald-50 text-emerald-700 border-emerald-300'">
                  {{ mv.type }}
                </span>
              </div>
              <span class="flex-shrink-0 inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg border"
                :class="statusClass(mv.approval_status)">
                <span v-if="mv.approval_status === 'PENDING'" class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                {{ mv.approval_status }}
              </span>
            </div>
            <p class="text-sm font-bold text-slate-800 mb-3 leading-snug">
              {{ mv.checklist?.task ?? (mv.type === 'OUT' ? 'Physical folder sent out' : 'Physical folder returned') }}
            </p>
            <div class="grid grid-cols-2 gap-x-4 gap-y-2.5 mb-4">
              <div v-if="mv.from_to">
                <p class="text-[10px] uppercase tracking-wider text-slate-500 font-black mb-1">{{ mv.type === 'OUT' ? 'To' : 'From' }}</p>
                <p class="text-xs text-slate-800 font-semibold">{{ mv.from_to }}</p>
              </div>
              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-500 font-black mb-1">Recorded By</p>
                <p class="text-xs text-slate-800 font-semibold">{{ mv.recorder?.full_name ?? '—' }}</p>
              </div>
              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-500 font-black mb-1">Date</p>
                <p class="text-xs text-slate-800 font-semibold">{{ formatDate(mv.date) }}</p>
              </div>
            </div>
            <div v-if="mv.approval_status === 'PENDING'" class="flex gap-2.5 pt-3 border-t border-amber-200">
              <button @click="decideMovement(mv, 'APPROVED')"
                :disabled="processingId === `${mv._source}-${mv.id}`"
                class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-40 transition-all duration-200 shadow-sm">
                <svg v-if="processingId === `${mv._source}-${mv.id}`" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                Approve
              </button>
              <button @click="decideMovement(mv, 'REJECTED')"
                :disabled="processingId === `${mv._source}-${mv.id}`"
                class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-bold rounded-xl border-2 border-red-300 text-red-700 hover:bg-red-50 hover:border-red-400 disabled:opacity-40 transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Reject
              </button>
            </div>
            <div v-else class="pt-3 border-t border-slate-200">
              <span class="text-xs text-slate-500 font-semibold italic">✓ Reviewed</span>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-5 md:px-6 py-4 border-t border-slate-200 flex items-center justify-between bg-slate-50">
          <p class="text-xs text-slate-600 font-medium">
            Showing <span class="font-bold text-blue-700">{{ visibleRows.length }}</span> of
            <span class="font-bold text-blue-700">{{ allMovements.length }}</span> movements
          </p>
          <button @click="loadAll()"
            class="flex items-center gap-2 text-xs text-blue-700 hover:text-blue-800 font-bold hover:underline transition-all duration-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Refresh Data
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- CASE REQUESTS VIEW -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div v-else-if="activeView === 'case-requests'">
        <!-- Filter Bar -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5 mb-4">
          <div class="flex flex-wrap gap-2.5">
            <select v-model="caseRequestStatusFilter" @change="loadCaseRequests"
              class="flex-1 min-w-[130px] px-4 py-2.5 text-sm bg-slate-50/80 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100 text-slate-700 font-medium cursor-pointer transition-all duration-200">
              <option value="ALL">All Status</option>
              <option value="PENDING">⏳ Pending</option>
              <option value="APPROVED">✓ Approved</option>
              <option value="REJECTED">✗ Rejected</option>
            </select>
          </div>
        </div>
      <!-- Loading State -->
      <div v-if="caseRequestsLoading" class="bg-white rounded-xl shadow-sm border border-slate-200 py-16 flex flex-col items-center gap-5 text-center px-4">
        <div class="w-16 h-16 rounded-xl bg-slate-100 flex items-center justify-center">
          <svg class="animate-spin w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
        </div>
        <p class="text-sm text-slate-500">Loading case requests...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="!caseRequests.length" class="bg-white rounded-xl shadow-sm border border-slate-200 py-16 flex flex-col items-center gap-5 text-center px-4">
        <div class="w-20 h-20 rounded-xl bg-slate-100 flex items-center justify-center">
          <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
        </div>
        <div>
          <p class="text-base font-bold text-slate-700 mb-1">No pending case requests</p>
          <p class="text-sm text-slate-500">All case requests have been reviewed.</p>
        </div>
      </div>

      <!-- Content -->
      <div v-else class="space-y-4">
        <div v-for="request in caseRequests" :key="request.id" 
          class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
          
          <!-- Desktop View -->
          <div class="hidden md:block p-6">
            <div class="flex items-start justify-between gap-6 mb-4">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <h3 class="text-lg font-bold text-slate-900">
                    {{ request.case ? request.case.title : request.request_data.title }}
                  </h3>
                  <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border bg-blue-50 text-blue-700 border-blue-200 capitalize">
                    {{ request.request_type }}
                  </span>
                </div>
                <div class="flex items-center gap-4 text-sm text-slate-600">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center text-xs font-black text-blue-700 border border-blue-200">
                      {{ getInitials(request.requested_by?.full_name) }}
                    </div>
                    <span class="font-medium">{{ request.requested_by?.full_name }}</span>
                  </div>
                  <span class="text-slate-400">•</span>
                  <span>{{ formatDate(request.created_at) }}</span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <template v-if="request.status === 'PENDING'">
                  <button @click="approveCaseRequest(request)"
                    :disabled="processingCaseRequestId === request.id"
                    class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-40 transition-all duration-200 shadow-sm">
                    <svg v-if="processingCaseRequestId === request.id" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Approve
                  </button>
                  <button @click="rejectCaseRequest(request)"
                    :disabled="processingCaseRequestId === request.id"
                    class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-bold rounded-lg border-2 border-red-300 text-red-700 hover:bg-red-50 hover:border-red-400 disabled:opacity-40 transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Reject
                  </button>
                </template>
                <span v-else class="text-xs text-slate-400 font-semibold italic">✓ Reviewed</span>
              </div>
            </div>
            
            <!-- Request Details -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
              <h4 class="text-sm font-bold text-slate-700 mb-3">Request Details</h4>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                <div v-for="detail in getFormattedRequestDetails(request.request_data)" :key="detail.label">
                  <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">{{ detail.label }}</p>
                  <p class="text-slate-800 font-medium capitalize">{{ detail.value }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Mobile View -->
          <div class="md:hidden p-4">
            <div class="flex items-start justify-between gap-3 mb-3">
              <div class="flex-1">
                <h3 class="text-base font-bold text-slate-900 mb-1">
                  {{ request.case ? request.case.title : request.request_data.title }}
                </h3>
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border bg-blue-50 text-blue-700 border-blue-200 capitalize">
                  {{ request.request_type }}
                </span>
              </div>
              <span class="flex-shrink-0 inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg border"
                :class="statusClass(request.status)">
                <span v-if="request.status === 'PENDING'" class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                {{ request.status }}
              </span>
            </div>
            
            <div class="flex items-center gap-3 mb-4 text-sm text-slate-600">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center text-xs font-black text-blue-700 border border-blue-200">
                  {{ getInitials(request.requested_by?.full_name) }}
                </div>
                <span class="font-medium">{{ request.requested_by?.full_name }}</span>
              </div>
            </div>
            
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 mb-4">
              <h4 class="text-sm font-bold text-slate-700 mb-3">Request Details</h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div v-for="detail in getFormattedRequestDetails(request.request_data)" :key="detail.label">
                  <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">{{ detail.label }}</p>
                  <p class="text-slate-800 font-medium capitalize">{{ detail.value }}</p>
                </div>
              </div>
            </div>

            <div class="flex gap-2.5 pt-3 border-t border-amber-200" v-if="request.status === 'PENDING'">
              <button @click="approveCaseRequest(request)"
                :disabled="processingCaseRequestId === request.id"
                class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-40 transition-all duration-200 shadow-sm">
                <svg v-if="processingCaseRequestId === request.id" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                Approve
              </button>
              <button @click="rejectCaseRequest(request)"
                :disabled="processingCaseRequestId === request.id"
                class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-bold rounded-xl border-2 border-red-300 text-red-700 hover:bg-red-50 hover:border-red-400 disabled:opacity-40 transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Reject
              </button>
            </div>
            <div v-else class="pt-3 border-t border-slate-200">
              <span class="text-xs text-slate-500 font-semibold italic">✓ Reviewed</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Movement Confirm Dialog ── -->
    <Transition name="modal">
      <div v-if="movementConfirm.show"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
        @click.self="movementConfirm.show = false">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md"></div>
        <div class="relative bg-white w-full sm:rounded-2xl rounded-t-2xl shadow-xl sm:max-w-lg p-6 sm:p-7 border border-slate-200">
          <div class="flex items-start gap-4 mb-5">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center flex-shrink-0"
              :class="movementConfirm.action === 'APPROVED' ? 'bg-emerald-100 border-2 border-emerald-300' : 'bg-red-100 border-2 border-red-300'">
              <svg class="w-7 h-7" :class="movementConfirm.action === 'APPROVED' ? 'text-emerald-700' : 'text-red-600'"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="movementConfirm.action === 'APPROVED'"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                <path v-else
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-black text-slate-900 mb-1">
                {{ movementConfirm.action === 'APPROVED' ? 'Approve this movement?' : 'Reject this movement?' }}
              </h3>
              <p class="text-sm text-slate-600 leading-relaxed">
                <template v-if="movementConfirm.action === 'APPROVED'">
                  This will mark the movement as <strong class="text-emerald-700 font-bold">approved</strong>
                  and update the document's location status in the system.
                </template>
                <template v-else>
                  This will <strong class="text-red-600 font-bold">reject</strong> the movement.
                  The document location will <strong class="font-bold">not</strong> be changed.
                </template>
              </p>
            </div>
          </div>

          <div v-if="movementConfirm.mv" class="mb-6 p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold text-slate-500">CASE:</span>
              <span class="text-xs font-black text-blue-700">{{ movementConfirm.mv.case_code ?? `#${movementConfirm.mv.case_id}` }}</span>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-xs font-bold text-slate-500">TYPE:</span>
              <span class="text-xs font-bold text-slate-700">{{ movementConfirm.mv._source === 'checklist' ? '📋 Checklist' : '📁 Folder' }}</span>
              <span class="px-2 py-0.5 rounded text-xs font-bold"
                :class="movementConfirm.mv.type === 'OUT' ? 'bg-rose-100 text-rose-700 border border-rose-300' : 'bg-emerald-100 text-emerald-700 border border-emerald-300'">
                {{ movementConfirm.mv.type }}
              </span>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row gap-3">
            <button @click="movementConfirm.show = false"
              class="flex-1 px-5 py-3 text-sm font-bold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all duration-200 border border-slate-300">
              Cancel
            </button>
            <button @click="confirmDecideMovement"
              :disabled="movementConfirm.processing"
              class="flex-1 flex items-center justify-center gap-2 px-6 py-3 text-sm font-bold rounded-xl text-white disabled:opacity-50 transition-all duration-200 shadow-md"
              :class="movementConfirm.action === 'APPROVED' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700'">
              <svg v-if="movementConfirm.processing" class="animate-spin w-5 h-5" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ movementConfirm.action === 'APPROVED' ? 'Yes, Approve' : 'Yes, Reject' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Toast ── -->
    <Transition name="toast">
      <div v-if="toast.show"
        class="fixed bottom-4 right-4 left-4 sm:left-auto sm:right-6 sm:bottom-6 z-50 flex items-center gap-3 px-5 py-4 rounded-xl shadow-lg text-sm font-bold border-2"
        :class="toast.type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-red-50 text-red-800 border-red-300'">
        <div class="w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0"
          :class="toast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path v-if="toast.type === 'success'"
              stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            <path v-else
              stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </div>
        {{ toast.message }}
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, reactive, watch } from 'vue';
import { useRouter } from 'vue-router';
import {
  reviewChecklistMovement,
  reviewFolderMovement,
} from '@/services/approvalService';
import api from '@/services/api';
import store from '@/store';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const { userRole, isAdmin, isLawyer } = useAuth();

// ── State ─────────────────────────────────────────────────────────────────
const activeView = ref('movements');
const processingId = ref(null);

// Document Movements
const allMovements = computed(() => store.state.approvals);
const searchQuery = ref('');
const filterStatus = ref('ALL');
const filterType = ref('ALL');
const filterDirection = ref('ALL');
const currentPage = ref(1);

// Dialogs
const movementConfirm = reactive({ show: false, mv: null, action: null, processing: false });
const toast = reactive({ show: false, message: '', type: 'success' });

// ── Polling & Sync ────────────────────────────────────────────────────────
let initialLoadDone = false;
let pollTimer = null;
let bc = null;

const loadAll = async (silent = false) => {
  if (!store.state.isInitialized) return;
  if (!isAdmin.value && !isLawyer.value) return;
  
  try {
    await store.actions.refreshApprovals({
      search: searchQuery.value || undefined,
      status: filterStatus.value !== 'ALL' ? filterStatus.value : undefined,
      type: filterType.value !== 'ALL' ? filterType.value : undefined,
      direction: filterDirection.value !== 'ALL' ? filterDirection.value : undefined,
      page: currentPage.value
    });
    store.actions.refreshPendingCount();
  } catch (e) {
    showToast(e.message ?? 'Failed to load movements.', 'error');
  }
};

watch([filterStatus, filterType, filterDirection, searchQuery], () => {
  currentPage.value = 1;
  loadAll();
});

const openChannel = () => {
  closeChannel();
  bc = new BroadcastChannel('approvals_sync');
  bc.onmessage = () => {
    if (!initialLoadDone) return;
    if (document.visibilityState === 'visible') {
      loadAll(true);
    }
  };
};

const closeChannel = () => { bc?.close(); bc = null; };

const onVisibilityChange = () => {
  if (document.visibilityState !== 'visible') return;
  if (initialLoadDone) {
    loadAll(true);
  }
};

function startPolling() {
  stopPolling();
  pollTimer = setInterval(() => {
    if (document.visibilityState !== 'visible' || !initialLoadDone) return;
    loadAll(true);
  }, 30_000);
}

function stopPolling() { clearInterval(pollTimer); pollTimer = null; }

// ── Computed ──────────────────────────────────────────────────────────────
const hasActiveFilters = computed(() =>
  searchQuery.value || filterStatus.value !== 'ALL' || filterType.value !== 'ALL' || filterDirection.value !== 'ALL'
);

const visibleRows = computed(() => allMovements.value);

const clearFilters = () => {
  searchQuery.value = '';
  filterType.value = 'ALL';
  filterDirection.value = 'ALL';
  filterStatus.value = 'ALL';
};

// ── Movement Actions ──────────────────────────────────────────────────────
const decideMovement = (mv, action) => {
  movementConfirm.mv = mv;
  movementConfirm.action = action;
  movementConfirm.processing = false;
  movementConfirm.show = true;
};

const confirmDecideMovement = async () => {
  const mv = movementConfirm.mv;
  const action = movementConfirm.action;
  const key = `${mv._source}-${mv.id}`;

  movementConfirm.processing = true;
  processingId.value = key;

  try {
    const res = mv._source === 'checklist'
      ? await reviewChecklistMovement(mv.case_id, mv.id, action)
      : await reviewFolderMovement(mv.case_id, mv.id, action);

    await loadAll(true);
    movementConfirm.show = false;
    showToast(
      action === 'APPROVED' ? 'Movement approved successfully.' : 'Movement rejected.',
      action === 'APPROVED' ? 'success' : 'error'
    );
    if (bc) {
      bc.postMessage('refresh');
    }
  } catch (e) {
    showToast(e.message ?? 'Something went wrong. Please try again.', 'error');
  } finally {
    movementConfirm.processing = false;
    processingId.value = null;
  }
};

// ── Helpers ───────────────────────────────────────────────────────────────
const showToast = (message, type = 'success') => {
  toast.message = message;
  toast.type = type;
  toast.show = true;
  setTimeout(() => { toast.show = false; }, 3500);
};

const formatDate = (d) => {
  if (!d) return '—';
  const dt = new Date(d);
  if (isNaN(dt)) return d;
  return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const getInitials = (name) => {
  if (!name) return '?';
  return name.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase();
};

const statusClass = (s) => ({
  PENDING: 'bg-amber-50 text-amber-700 border-amber-300',
  APPROVED: 'bg-emerald-50 text-emerald-700 border-emerald-300',
  REJECTED: 'bg-red-50 text-red-700 border-red-300',
}[s] ?? 'bg-slate-100 text-slate-500 border-slate-200');

const formatRequestData = (data) => {
  if (typeof data === 'string') return data;
  return JSON.stringify(data, null, 2);
};

// ── Lifecycle ─────────────────────────────────────────────────────────────
onMounted(() => {
  if (!isAdmin.value && !isLawyer.value) {
    router.push('/dashboard');
    return;
  }

  if (!store.state.isInitialized) {
    store.actions.initialize(userRole.value).then(() => {
      initialLoadDone = true;
      openChannel();
      document.addEventListener('visibilitychange', onVisibilityChange);
      startPolling();
      loadAll(true);
    });
  } else {
    initialLoadDone = true;
    openChannel();
    document.addEventListener('visibilitychange', onVisibilityChange);
    startPolling();
    loadAll(true);
  }
});

onUnmounted(() => {
  stopPolling();
  closeChannel();
  document.removeEventListener('visibilitychange', onVisibilityChange);
});
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { 
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
}
.modal-enter-from, .modal-leave-to { 
  opacity: 0; 
  transform: scale(0.95); 
}

@media (max-width: 639px) {
  .modal-enter-from, .modal-leave-to { 
    opacity: 0; 
    transform: translateY(100%); 
  }
}

.toast-enter-active { 
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); 
}
.toast-leave-active { 
  transition: all 0.2s ease-out; 
}
.toast-enter-from { 
  opacity: 0; 
  transform: translateY(12px) scale(0.95); 
}
.toast-leave-to { 
  opacity: 0; 
  transform: translateY(-8px) scale(0.98); 
}

.overflow-x-auto::-webkit-scrollbar { 
  height: 6px; 
}
.overflow-x-auto::-webkit-scrollbar-track { 
  background: #f1f5f9; 
  border-radius: 3px;
}
.overflow-x-auto::-webkit-scrollbar-thumb { 
  background: linear-gradient(to right, #cbd5e1, #94a3b8); 
  border-radius: 3px; 
}
.overflow-x-auto::-webkit-scrollbar-thumb:hover { 
  background: linear-gradient(to right, #94a3b8, #64748b); 
}

@media (max-width: 768px) {
  .overflow-x-auto::-webkit-scrollbar { 
    height: 0; 
  }
}
</style>