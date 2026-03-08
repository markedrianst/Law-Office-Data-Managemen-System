<template>
  <div class="min-h-screen p-4 md:p-6 bg-slate-50" style="font-family: 'Segoe UI', sans-serif;">

    <!-- ── Page Header ── -->
    <div class="mb-5">
      <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
          <div class="flex items-center gap-3 mb-1">
            <div class="w-1 h-8 rounded-full bg-gradient-to-b from-[#1a4972] to-[#2d6db5]"></div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-[#1a4972]">Approvals</h1>
          </div>
          <p class="text-sm ml-4 pl-3 text-slate-500">Review and approve pending document movements from clerks</p>
        </div>
        <!-- Summary chips — wrap on small screens -->
        <div class="flex items-center gap-2 flex-wrap">
          <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold"
            :class="stats.pending > 0 ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-slate-100 border-slate-200 text-slate-500'">
            <span class="w-1.5 h-1.5 rounded-full" :class="stats.pending > 0 ? 'bg-amber-500 animate-pulse' : 'bg-slate-400'"></span>
            {{ stats.pending }} Pending
          </div>
          <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-700">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            {{ stats.approved }} Approved
          </div>
          <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-red-50 border border-red-200 text-xs font-semibold text-red-600">
            <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
            {{ stats.rejected }} Rejected
          </div>
        </div>
      </div>
    </div>

    <!-- ── Filter Bar ── -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-4">
      <!-- Row 1: Search (full width) -->
      <div class="relative mb-3">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
          <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </div>
        <input v-model="searchQuery" type="text"
          placeholder="Search case code, task, clerk…"
          class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] focus:ring-2 focus:ring-[#1a4972]/10 placeholder-slate-400 transition-all"/>
      </div>
      <!-- Row 2: Dropdowns -->
      <div class="flex flex-wrap gap-2">
        <select v-model="filterStatus"
          class="flex-1 min-w-[110px] px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] text-slate-600">
          <option value="ALL">All Status</option>
          <option value="PENDING">Pending</option>
          <option value="APPROVED">Approved</option>
          <option value="REJECTED">Rejected</option>
        </select>
        <select v-model="filterType"
          class="flex-1 min-w-[110px] px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] text-slate-600">
          <option value="ALL">All Types</option>
          <option value="checklist">📋 Checklist</option>
          <option value="folder">📁 Folder</option>
        </select>
        <select v-model="filterDirection"
          class="flex-1 min-w-[100px] px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#1a4972] text-slate-600">
          <option value="ALL">In & Out</option>
          <option value="OUT">OUT only</option>
          <option value="IN">IN only</option>
        </select>
        <button v-if="hasActiveFilters" @click="clearFilters"
          class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors whitespace-nowrap">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
          Clear
        </button>
      </div>
    </div>

    <!-- ── Tab bar ── -->
    <div class="flex mb-4 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-x-auto">
      <button v-for="t in tabs" :key="t.key"
        @click="activeTab = t.key"
        class="flex-1 flex items-center justify-center gap-1.5 px-4 py-3 text-sm font-medium transition-colors border-b-2 whitespace-nowrap min-w-[80px]"
        :class="activeTab === t.key
          ? 'text-[#1a4972] border-[#1a4972] bg-[#1a4972]/[0.04]'
          : 'text-slate-500 border-transparent hover:text-slate-700 hover:bg-slate-50'">
        {{ t.label }}
        <span v-if="t.count !== null"
          class="px-1.5 py-0.5 rounded-full text-xs font-bold"
          :class="activeTab === t.key ? 'bg-[#1a4972]/10 text-[#1a4972]' : 'bg-slate-100 text-slate-500'">
          {{ t.count }}
        </span>
      </button>
    </div>

    <!-- ── Loading ── -->
    <div v-if="loading" class="bg-white rounded-2xl shadow-sm border border-slate-100 py-24 flex flex-col items-center gap-4 text-slate-400">
      <svg class="animate-spin w-8 h-8" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
      <span class="text-sm">Loading movements…</span>
    </div>

    <!-- ── Empty ── -->
    <div v-else-if="!visibleRows.length"
      class="bg-white rounded-2xl shadow-sm border border-slate-100 py-20 flex flex-col items-center gap-4 text-center px-4">
      <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center">
        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <div>
        <p class="text-sm font-semibold text-slate-500">
          {{ activeTab === 'PENDING' ? 'No pending approvals' : 'No records found' }}
        </p>
        <p class="text-xs text-slate-400 mt-1">
          {{ activeTab === 'PENDING' ? 'All clerk movements have been reviewed.' : 'Try adjusting your filters.' }}
        </p>
      </div>
      <button v-if="hasActiveFilters" @click="clearFilters"
        class="text-xs text-[#1a4972] hover:underline font-medium">Clear all filters</button>
    </div>

    <!-- ── Content ── -->
    <div v-else class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">

      <!-- DESKTOP: table (hidden on mobile) -->
      <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full">
          <thead>
            <tr class="border-b border-slate-100 bg-[#1a4972]/[0.04]">
              <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 whitespace-nowrap">Case</th>
              <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 whitespace-nowrap">Type</th>
              <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 whitespace-nowrap">Dir.</th>
              <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Document / Task</th>
              <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 whitespace-nowrap">From / To</th>
              <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 whitespace-nowrap">Recorded By</th>
              <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 whitespace-nowrap">Date</th>
              <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 whitespace-nowrap">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="mv in visibleRows" :key="`${mv._source}-${mv.id}`"
              class="hover:bg-slate-50/70 transition-colors"
              :class="mv.approval_status === 'PENDING' ? 'bg-amber-50/20' : ''">

              <td class="px-5 py-4 whitespace-nowrap">
                <span class="text-sm font-semibold text-[#1a4972]">{{ mv.case_code ?? `Case #${mv.case_id}` }}</span>
              </td>

              <td class="px-5 py-4 whitespace-nowrap">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border"
                  :class="mv._source === 'checklist' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-100 text-slate-600 border-slate-200'">
                  {{ mv._source === 'checklist' ? '📋 Checklist' : '📁 Folder' }}
                </span>
              </td>

              <td class="px-5 py-4 whitespace-nowrap">
                <span class="inline-flex items-center justify-center w-12 py-1 rounded-lg text-xs font-bold border"
                  :class="mv.type === 'OUT' ? 'bg-rose-50 text-rose-600 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'">
                  {{ mv.type }}
                </span>
              </td>

              <td class="px-5 py-4 max-w-[180px]">
                <p class="text-sm text-slate-800 font-medium truncate">
                  {{ mv.checklist?.task ?? (mv.type === 'OUT' ? 'Physical folder sent out' : 'Physical folder returned') }}
                </p>
                <p v-if="mv.purpose" class="text-xs text-slate-400 truncate mt-0.5">{{ mv.purpose }}</p>
              </td>

              <td class="px-5 py-4 whitespace-nowrap">
                <span class="text-sm text-slate-600">{{ mv.from_to ?? '—' }}</span>
              </td>

              <td class="px-5 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full bg-[#1a4972]/10 flex items-center justify-center text-[10px] font-bold text-[#1a4972] flex-shrink-0">
                    {{ getInitials(mv.recorder?.full_name) }}
                  </div>
                  <span class="text-sm text-slate-700">{{ mv.recorder?.full_name ?? '—' }}</span>
                </div>
              </td>

              <td class="px-5 py-4 whitespace-nowrap">
                <span class="text-sm text-slate-500">{{ formatDate(mv.date) }}</span>
              </td>

              <td class="px-5 py-4 whitespace-nowrap">
                <div v-if="mv.approval_status === 'PENDING'" class="flex items-center gap-2">
                  <button @click="decide(mv, 'APPROVED')"
                    :disabled="processingId === `${mv._source}-${mv.id}`"
                    class="flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-40 transition-colors shadow-sm">
                    <svg v-if="processingId === `${mv._source}-${mv.id}`" class="animate-spin w-3 h-3" viewBox="0 0 24 24" fill="none">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Approve
                  </button>
                  <button @click="decide(mv, 'REJECTED')"
                    :disabled="processingId === `${mv._source}-${mv.id}`"
                    class="flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg border border-red-200 text-red-600 hover:bg-red-50 disabled:opacity-40 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Reject
                  </button>
                </div>
                <span v-else class="text-xs text-slate-400 italic">Reviewed</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- MOBILE: cards (hidden on md+) -->
      <div class="md:hidden divide-y divide-slate-100">
        <div v-for="mv in visibleRows" :key="`m-${mv._source}-${mv.id}`"
          class="p-4 transition-colors"
          :class="mv.approval_status === 'PENDING' ? 'bg-amber-50/30' : 'bg-white'">

          <!-- Card top row: case + badges -->
          <div class="flex items-start justify-between gap-2 mb-3">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-sm font-bold text-[#1a4972]">{{ mv.case_code ?? `Case #${mv.case_id}` }}</span>
              <!-- Type badge -->
              <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full border"
                :class="mv._source === 'checklist' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-100 text-slate-600 border-slate-200'">
                {{ mv._source === 'checklist' ? '📋' : '📁' }}
                {{ mv._source === 'checklist' ? 'Checklist' : 'Folder' }}
              </span>
              <!-- Direction badge -->
              <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-lg text-xs font-bold border"
                :class="mv.type === 'OUT' ? 'bg-rose-50 text-rose-600 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'">
                {{ mv.type }}
              </span>
            </div>
            <!-- Status badge -->
            <span class="flex-shrink-0 inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border"
              :class="statusClass(mv.approval_status)">
              <span v-if="mv.approval_status === 'PENDING'" class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
              {{ mv.approval_status }}
            </span>
          </div>

          <!-- Task / document name -->
          <p class="text-sm font-semibold text-slate-800 mb-2">
            {{ mv.checklist?.task ?? (mv.type === 'OUT' ? 'Physical folder sent out' : 'Physical folder returned') }}
          </p>

          <!-- Meta grid -->
          <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 mb-3">
            <div v-if="mv.from_to">
              <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-0.5">{{ mv.type === 'OUT' ? 'To' : 'From' }}</p>
              <p class="text-xs text-slate-700 font-medium">{{ mv.from_to }}</p>
            </div>
            <div v-if="mv.handled_by">
              <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-0.5">Handled By</p>
              <p class="text-xs text-slate-700 font-medium">{{ mv.handled_by }}</p>
            </div>
            <div>
              <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-0.5">Recorded By</p>
              <p class="text-xs text-slate-700 font-medium">{{ mv.recorder?.full_name ?? '—' }}</p>
            </div>
            <div>
              <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-0.5">Date</p>
              <p class="text-xs text-slate-700 font-medium">{{ formatDate(mv.date) }}</p>
            </div>
            <div v-if="mv.purpose" class="col-span-2">
              <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-0.5">Purpose</p>
              <p class="text-xs text-slate-600">{{ mv.purpose }}</p>
            </div>
          </div>

          <!-- Reviewed by (approved/rejected) -->
          <p v-if="mv.approval_status !== 'PENDING' && mv.approver?.full_name"
            class="text-xs text-slate-400 mb-3">
            {{ mv.approval_status === 'APPROVED' ? '✓ Approved' : '✗ Rejected' }} by
            <span class="font-medium text-slate-600">{{ mv.approver.full_name }}</span>
          </p>

          <!-- Action buttons -->
          <div v-if="mv.approval_status === 'PENDING'" class="flex gap-2 pt-2 border-t border-amber-100">
            <button @click="decide(mv, 'APPROVED')"
              :disabled="processingId === `${mv._source}-${mv.id}`"
              class="flex-1 flex items-center justify-center gap-1.5 py-2 text-sm font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-40 transition-colors shadow-sm">
              <svg v-if="processingId === `${mv._source}-${mv.id}`" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
              </svg>
              Approve
            </button>
            <button @click="decide(mv, 'REJECTED')"
              :disabled="processingId === `${mv._source}-${mv.id}`"
              class="flex-1 flex items-center justify-center gap-1.5 py-2 text-sm font-semibold rounded-xl border border-red-200 text-red-600 hover:bg-red-50 disabled:opacity-40 transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
              Reject
            </button>
          </div>
          <div v-else class="pt-2 border-t border-slate-100">
            <span class="text-xs text-slate-400 italic">Reviewed — no further action needed</span>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-5 py-3.5 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
        <p class="text-xs text-slate-500">
          Showing <span class="font-semibold text-slate-700">{{ visibleRows.length }}</span> of
          <span class="font-semibold text-slate-700">{{ allMovements.length }}</span> movements
        </p>
        <button @click="loadAll"
          class="text-xs text-[#1a4972] hover:underline font-medium flex items-center gap-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          Refresh
        </button>
      </div>
    </div>

    <!-- ── Confirm dialog ── -->
    <Transition name="modal">
      <div v-if="confirm.show"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
        @click.self="confirm.show = false">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div class="relative bg-white w-full sm:rounded-2xl rounded-t-2xl shadow-2xl sm:max-w-md p-6">
          <div class="flex items-start gap-4">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
              :class="confirm.action === 'APPROVED' ? 'bg-emerald-100' : 'bg-red-100'">
              <svg class="w-5 h-5" :class="confirm.action === 'APPROVED' ? 'text-emerald-600' : 'text-red-500'"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="confirm.action === 'APPROVED'"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                <path v-else
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="text-base font-bold text-slate-800">
                {{ confirm.action === 'APPROVED' ? 'Approve movement?' : 'Reject movement?' }}
              </h3>
              <p class="text-sm text-slate-500 mt-1">
                <template v-if="confirm.action === 'APPROVED'">
                  This will mark the movement as <strong class="text-emerald-700">approved</strong>
                  and update the document's location status.
                </template>
                <template v-else>
                  This will <strong class="text-red-600">reject</strong> the movement.
                  The document location will <strong>not</strong> be changed.
                </template>
              </p>
              <div v-if="confirm.mv" class="mt-3 p-3 bg-slate-50 rounded-xl text-xs text-slate-600 space-y-1 border border-slate-200">
                <p><span class="font-semibold">Case:</span> {{ confirm.mv.case_code ?? `#${confirm.mv.case_id}` }}</p>
                <p><span class="font-semibold">Type:</span> {{ confirm.mv._source === 'checklist' ? '📋 Checklist' : '📁 Folder' }} · {{ confirm.mv.type }}</p>
                <p v-if="confirm.mv.checklist?.task || confirm.mv.from_to">
                  <span class="font-semibold">{{ confirm.mv.checklist?.task ? 'Task' : 'To/From' }}:</span>
                  {{ confirm.mv.checklist?.task ?? confirm.mv.from_to }}
                </p>
              </div>
            </div>
          </div>
          <div class="flex gap-3 mt-6">
            <button @click="confirm.show = false"
              class="flex-1 sm:flex-none px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors border border-slate-200">
              Cancel
            </button>
            <button @click="confirmDecide"
              :disabled="confirm.processing"
              class="flex-1 flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-xl text-white disabled:opacity-50 transition-colors"
              :class="confirm.action === 'APPROVED' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-500 hover:bg-red-600'">
              <svg v-if="confirm.processing" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ confirm.action === 'APPROVED' ? 'Yes, Approve' : 'Yes, Reject' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Toast ── -->
    <Transition name="toast">
      <div v-if="toast.show"
        class="fixed bottom-4 right-4 left-4 sm:left-auto sm:right-6 sm:bottom-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium border"
        :class="toast.type === 'success'
          ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
          : 'bg-red-50 text-red-800 border-red-200'">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path v-if="toast.type === 'success'"
            stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          <path v-else
            stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        {{ toast.message }}
      </div>
    </Transition>

  </div>
</template>
<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import {
  getAllMovements,
  reviewChecklistMovement,
  reviewFolderMovement,
} from '@/services/approvalService';

// ── State ─────────────────────────────────────────────────────────────────────
const allMovements = ref([]);
const loading      = ref(false);
const processingId = ref(null);

// Filters
const searchQuery     = ref('');
const filterStatus    = ref('PENDING');
const filterType      = ref('ALL');
const filterDirection = ref('ALL');
const activeTab       = ref('PENDING');

// Confirm dialog
const confirm = reactive({ show: false, mv: null, action: null, processing: false });

// Toast
const toast = reactive({ show: false, message: '', type: 'success' });

// ── Load all movements via global approvals endpoint ──────────────────────────
const loadAll = async () => {
  loading.value = true;
  try {
    const res = await getAllMovements();
    allMovements.value = res.data;
  } catch (e) {
    showToast(e.message ?? 'Failed to load movements.', 'error');
  } finally {
    loading.value = false;
  }
};

// ── Stats ─────────────────────────────────────────────────────────────────────
const stats = computed(() => ({
  pending:  allMovements.value.filter(m => m.approval_status === 'PENDING').length,
  approved: allMovements.value.filter(m => m.approval_status === 'APPROVED').length,
  rejected: allMovements.value.filter(m => m.approval_status === 'REJECTED').length,
}));

// ── Tabs ──────────────────────────────────────────────────────────────────────
const tabs = computed(() => [
  { key: 'PENDING',  label: 'Pending',  count: stats.value.pending  },
  { key: 'APPROVED', label: 'Approved', count: stats.value.approved },
  { key: 'REJECTED', label: 'Rejected', count: stats.value.rejected },
  { key: 'ALL',      label: 'All',      count: allMovements.value.length },
]);

// ── Filtered rows ─────────────────────────────────────────────────────────────
const hasActiveFilters = computed(() =>
  searchQuery.value || filterType.value !== 'ALL' || filterDirection.value !== 'ALL'
);

const visibleRows = computed(() => {
  let rows = allMovements.value;

  // Tab filter
  if (activeTab.value !== 'ALL') {
    rows = rows.filter(m => m.approval_status === activeTab.value);
  }

  // Status dropdown (secondary, only shows when tab is ALL)
  if (activeTab.value === 'ALL' && filterStatus.value !== 'ALL') {
    rows = rows.filter(m => m.approval_status === filterStatus.value);
  }

  if (filterType.value !== 'ALL') {
    rows = rows.filter(m => m._source === filterType.value);
  }

  if (filterDirection.value !== 'ALL') {
    rows = rows.filter(m => m.type === filterDirection.value);
  }

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    rows = rows.filter(m =>
      m.case_code?.toLowerCase().includes(q) ||
      m.checklist?.task?.toLowerCase().includes(q) ||
      m.from_to?.toLowerCase().includes(q) ||
      m.handled_by?.toLowerCase().includes(q) ||
      m.recorder?.full_name?.toLowerCase().includes(q) ||
      m.purpose?.toLowerCase().includes(q)
    );
  }

  return rows;
});

const clearFilters = () => {
  searchQuery.value     = '';
  filterType.value      = 'ALL';
  filterDirection.value = 'ALL';
  filterStatus.value    = 'ALL';
};

// ── Approve / Reject ──────────────────────────────────────────────────────────
const decide = (mv, action) => {
  confirm.mv         = mv;
  confirm.action     = action;
  confirm.processing = false;
  confirm.show       = true;
};

const confirmDecide = async () => {
  const mv     = confirm.mv;
  const action = confirm.action;
  const key    = `${mv._source}-${mv.id}`;

  confirm.processing = true;
  processingId.value = key;

  try {
    const res = mv._source === 'checklist'
      ? await reviewChecklistMovement(mv.case_id, mv.id, action)
      : await reviewFolderMovement(mv.case_id, mv.id, action);

    // Update in-place so the row re-renders immediately
    const idx = allMovements.value.findIndex(m => m._source === mv._source && m.id === mv.id);
    if (idx !== -1) {
      allMovements.value[idx] = {
        ...allMovements.value[idx],
        ...res.data,
        _source:   mv._source,
        case_code: mv.case_code,
      };
    }

    confirm.show = false;
    showToast(
      action === 'APPROVED' ? 'Movement approved successfully.' : 'Movement rejected.',
      action === 'APPROVED' ? 'success' : 'error'
    );

  } catch (e) {
    showToast(e.message ?? 'Something went wrong. Please try again.', 'error');
  } finally {
    confirm.processing = false;
    processingId.value = null;
  }
};

// ── Helpers ───────────────────────────────────────────────────────────────────
const showToast = (message, type = 'success') => {
  toast.message = message;
  toast.type    = type;
  toast.show    = true;
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
  PENDING:  'bg-amber-50 text-amber-700 border-amber-200',
  APPROVED: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  REJECTED: 'bg-red-50 text-red-600 border-red-200',
}[s] ?? 'bg-slate-100 text-slate-500 border-slate-200');

// ── Sync tab ↔ filterStatus ───────────────────────────────────────────────────
// Clicking a tab sets the dropdown too so they stay in sync
const syncTab = (key) => {
  activeTab.value    = key;
  filterStatus.value = key; // keeps dropdown aligned
};

// Override activeTab setter to also sync
const setTab = (key) => { activeTab.value = key; };

// ── Init ──────────────────────────────────────────────────────────────────────
onMounted(loadAll);
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to       { opacity: 0; transform: scale(0.97); }

/* Modal slides up from bottom on mobile */
@media (max-width: 639px) {
  .modal-enter-from, .modal-leave-to { opacity: 0; transform: translateY(100%); }
}

.toast-enter-active { transition: all 0.25s ease; }
.toast-leave-active { transition: all 0.2s ease; }
.toast-enter-from   { opacity: 0; transform: translateY(8px) scale(0.98); }
.toast-leave-to     { opacity: 0; transform: translateY(4px); }

/* Table scrollbar */
.overflow-x-auto::-webkit-scrollbar       { height: 4px; }
.overflow-x-auto::-webkit-scrollbar-track { background: #f1f5f9; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

/* Tab bar scrollbar (mobile) */
.overflow-x-auto::-webkit-scrollbar { height: 0; }
</style>