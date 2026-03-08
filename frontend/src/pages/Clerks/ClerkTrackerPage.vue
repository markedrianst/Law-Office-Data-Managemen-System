<template>
  <div class="min-h-screen bg-gray-50">

    <!-- ══ PAGE HEADER ══════════════════════════════════════════════════════ -->
    <div class="bg-white border-b border-gray-100 px-8 py-5">
      <div class="flex items-center justify-between max-w-7xl mx-auto">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center shadow-sm">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
          </div>
          <div>
            <h1 class="text-xl font-bold text-gray-900 leading-tight">In / Out Tracker</h1>
            <p class="text-xs text-gray-400 font-medium">Submit movement requests for folders and checklist items</p>
          </div>
        </div>

        <!-- Pending badge -->
        <div v-if="pendingCount > 0"
          class="flex items-center gap-2 px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-xl">
          <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
          <span class="text-xs font-bold text-amber-700">{{ pendingCount }} pending approval</span>
        </div>
      </div>
    </div>

    <!-- ══ MAIN CONTENT ══════════════════════════════════════════════════════ -->
    <div class="max-w-7xl mx-auto px-8 py-6 space-y-6">

      <!-- ── Tab switcher ──────────────────────────────────────────────────── -->
      <div class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit">
        <button
          v-for="tab in tabs" :key="tab.key"
          @click="activeTab = tab.key"
          :class="[
            'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all',
            activeTab === tab.key
              ? 'bg-white text-gray-900 shadow-sm'
              : 'text-gray-500 hover:text-gray-700'
          ]">
          <span v-html="tab.icon" class="w-4 h-4"></span>
          {{ tab.label }}
          <span v-if="tab.badge > 0"
            class="ml-1 px-1.5 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-700 rounded-full">
            {{ tab.badge }}
          </span>
        </button>
      </div>

      <!-- ══ FOLDER TRACKER TAB ════════════════════════════════════════════ -->
      <div v-if="activeTab === 'folder'" class="space-y-5">

        <!-- Current Status Card -->
        <div class="grid grid-cols-3 gap-5">
          <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm flex items-center gap-4">
            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-sm',
              folderStatus.is_out ? 'bg-red-500' : 'bg-emerald-500']">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
              </svg>
            </div>
            <div>
              <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Folder Status</p>
              <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 text-sm font-bold rounded-lg',
                folderStatus.is_out
                  ? 'bg-red-100 text-red-700 border border-red-200'
                  : 'bg-emerald-100 text-emerald-700 border border-emerald-200']">
                <span :class="['w-2 h-2 rounded-full', folderStatus.is_out ? 'bg-red-500' : 'bg-emerald-500']"></span>
                {{ folderStatus.is_out ? 'OUT – Not in Office' : 'IN – In Office' }}
              </span>
            </div>
          </div>

          <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
            </div>
            <div>
              <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Movements</p>
              <p class="text-2xl font-bold text-gray-900">{{ folderHistory.length }}</p>
            </div>
          </div>

          <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Pending Approval</p>
              <p class="text-2xl font-bold text-gray-900">{{ folderHistory.filter(m => m.approval_status === 'PENDING').length }}</p>
            </div>
          </div>
        </div>

        <!-- Request + History side-by-side -->
        <div class="grid grid-cols-5 gap-5">

          <!-- Request Form -->
          <div class="col-span-2 bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
              <div class="w-1 h-5 bg-blue-600 rounded-full"></div>
              <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">New Movement Request</h3>
            </div>
            <div class="p-6 space-y-4">

              <!-- Movement type -->
              <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Movement Type</label>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    v-for="opt in ['OUT', 'IN']" :key="opt"
                    @click="folderForm.type = opt"
                    :class="[
                      'flex items-center justify-center gap-2 py-2.5 rounded-xl border-2 text-sm font-bold transition-all',
                      folderForm.type === opt
                        ? opt === 'OUT'
                          ? 'border-red-500 bg-red-50 text-red-700'
                          : 'border-emerald-500 bg-emerald-50 text-emerald-700'
                        : 'border-gray-200 text-gray-500 hover:border-gray-300'
                    ]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path v-if="opt === 'OUT'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                      <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16l-4-4m0 0l4-4m-4 4h18m-6 4v1a3 3 0 003 3h1a3 3 0 003-3V7a3 3 0 00-3-3h-1a3 3 0 00-3 3v1"/>
                    </svg>
                    {{ opt === 'OUT' ? 'Taking Out' : 'Returning In' }}
                  </button>
                </div>
              </div>

              <!-- Date -->
              <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Date</label>
                <input type="date" v-model="folderForm.date"
                  class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition"/>
              </div>

              <!-- From/To -->
              <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                  {{ folderForm.type === 'OUT' ? 'Bring To (Person / Office)' : 'Received From' }}
                </label>
                <input type="text" v-model="folderForm.from_to" placeholder="e.g. RTC Branch 7 – Atty. Santos"
                  class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition"/>
              </div>

              <!-- Purpose -->
              <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Purpose</label>
                <textarea v-model="folderForm.purpose" rows="2" placeholder="Reason for movement..."
                  class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition resize-none"/>
              </div>

              <!-- Handled by -->
              <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Handled By</label>
                <input type="text" v-model="folderForm.handled_by" placeholder="Name of person carrying folder"
                  class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition"/>
              </div>

              <!-- Submit -->
              <button @click="submitFolderRequest"
                :disabled="folderSubmitting || !folderForm.type || !folderForm.date"
                class="w-full flex items-center justify-center gap-2 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-bold rounded-xl transition">
                <svg v-if="folderSubmitting" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                {{ folderSubmitting ? 'Submitting…' : 'Submit Request' }}
              </button>

              <!-- Info note -->
              <p class="text-[11px] text-gray-400 text-center">
                Your request will be sent for admin / lawyer approval before taking effect.
              </p>
            </div>
          </div>

          <!-- Movement History -->
          <div class="col-span-3 bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-1 h-5 bg-emerald-500 rounded-full"></div>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Movement History</h3>
              </div>
              <span class="text-xs text-gray-400">{{ folderHistory.length }} records</span>
            </div>

            <!-- Loading -->
            <div v-if="loadingFolder" class="flex-1 flex items-center justify-center py-12">
              <div class="flex items-center gap-3 text-gray-400">
                <svg class="animate-spin w-5 h-5" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-sm">Loading history…</span>
              </div>
            </div>

            <!-- Empty -->
            <div v-else-if="!folderHistory.length" class="flex-1 flex flex-col items-center justify-center py-12 text-gray-400">
              <svg class="w-10 h-10 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
              </svg>
              <p class="text-sm font-medium">No folder movements yet</p>
            </div>

            <!-- Table -->
            <div v-else class="flex-1 overflow-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">From / To</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Purpose</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Handled By</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="row in paginatedFolderHistory" :key="row.id"
                    class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3">
                      <span :class="['inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-xs font-bold border',
                        row.type === 'OUT'
                          ? 'bg-red-50 text-red-700 border-red-200'
                          : 'bg-emerald-50 text-emerald-700 border-emerald-200']">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path v-if="row.type === 'OUT'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7"/>
                          <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
                        </svg>
                        {{ row.type }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-gray-700 font-medium whitespace-nowrap">{{ formatDate(row.date) }}</td>
                    <td class="px-4 py-3 text-gray-600 max-w-[120px] truncate" :title="row.from_to">{{ row.from_to || '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 max-w-[120px] truncate" :title="row.purpose">{{ row.purpose || '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ row.handled_by || '—' }}</td>
                    <td class="px-4 py-3">
                      <span :class="approvalBadgeClass(row.approval_status)"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-bold border">
                        <span :class="approvalDotClass(row.approval_status)" class="w-1.5 h-1.5 rounded-full"></span>
                        {{ row.approval_status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="totalFolderPages > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
              <span class="text-xs text-gray-400">Page {{ folderPage }} of {{ totalFolderPages }}</span>
              <div class="flex gap-1">
                <button @click="folderPage--" :disabled="folderPage === 1"
                  class="p-1.5 rounded-lg hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition text-gray-500">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                  </svg>
                </button>
                <button @click="folderPage++" :disabled="folderPage === totalFolderPages"
                  class="p-1.5 rounded-lg hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition text-gray-500">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ CHECKLIST TRACKER TAB ════════════════════════════════════════ -->
      <div v-if="activeTab === 'checklist'" class="space-y-5">

        <!-- Stats row -->
        <div class="grid grid-cols-3 gap-5">
          <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-violet-50 border border-violet-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
              </svg>
            </div>
            <div>
              <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Checklist Items</p>
              <p class="text-2xl font-bold text-gray-900">{{ checklist.length }}</p>
            </div>
          </div>

          <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-red-50 border border-red-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 16l4-4m0 0l-4-4m4 4H7"/>
              </svg>
            </div>
            <div>
              <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Items Out</p>
              <p class="text-2xl font-bold text-gray-900">{{ checklist.filter(c => c.is_out).length }}</p>
            </div>
          </div>

          <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Pending Approval</p>
              <p class="text-2xl font-bold text-gray-900">{{ checklistHistory.filter(m => m.approval_status === 'PENDING').length }}</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-5 gap-5">

          <!-- Request Form -->
          <div class="col-span-2 bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
              <div class="w-1 h-5 bg-violet-500 rounded-full"></div>
              <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">New Checklist Request</h3>
            </div>
            <div class="p-6 space-y-4">

              <!-- Movement type -->
              <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Movement Type</label>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    v-for="opt in ['OUT', 'IN']" :key="opt"
                    @click="checklistForm.type = opt"
                    :class="[
                      'flex items-center justify-center gap-2 py-2.5 rounded-xl border-2 text-sm font-bold transition-all',
                      checklistForm.type === opt
                        ? opt === 'OUT'
                          ? 'border-red-500 bg-red-50 text-red-700'
                          : 'border-emerald-500 bg-emerald-50 text-emerald-700'
                        : 'border-gray-200 text-gray-500 hover:border-gray-300'
                    ]">
                    {{ opt === 'OUT' ? 'Taking Out' : 'Returning In' }}
                  </button>
                </div>
              </div>

              <!-- Checklist item selector -->
              <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                  Checklist Item <span class="text-gray-400 normal-case font-normal">(optional – leave blank for all)</span>
                </label>
                <select v-model="checklistForm.checklist_id"
                  class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
                  <option :value="null">All checklist items</option>
                  <option v-for="item in checklist" :key="item.id" :value="item.id">
                    {{ item.task }}
                  </option>
                </select>
                <!-- is_out indicator for selected item -->
                <div v-if="selectedChecklistItem" class="mt-1.5 flex items-center gap-1.5">
                  <span :class="['w-2 h-2 rounded-full flex-shrink-0',
                    selectedChecklistItem.is_out ? 'bg-red-500' : 'bg-emerald-500']"></span>
                  <span class="text-[11px] text-gray-400">
                    Currently {{ selectedChecklistItem.is_out ? 'OUT' : 'IN office' }}
                  </span>
                </div>
              </div>

              <!-- Date -->
              <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Date</label>
                <input type="date" v-model="checklistForm.date"
                  class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition"/>
              </div>

              <!-- From/To -->
              <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                  {{ checklistForm.type === 'OUT' ? 'Bring To' : 'Received From' }}
                </label>
                <input type="text" v-model="checklistForm.from_to" placeholder="Person / office"
                  class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition"/>
              </div>

              <!-- Purpose -->
              <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Purpose</label>
                <textarea v-model="checklistForm.purpose" rows="2" placeholder="Reason for movement..."
                  class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition resize-none"/>
              </div>

              <!-- Handled by -->
              <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Handled By</label>
                <input type="text" v-model="checklistForm.handled_by" placeholder="Person carrying the item"
                  class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition"/>
              </div>

              <!-- Submit -->
              <button @click="submitChecklistRequest"
                :disabled="checklistSubmitting || !checklistForm.type || !checklistForm.date"
                class="w-full flex items-center justify-center gap-2 py-2.5 bg-violet-600 hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-bold rounded-xl transition">
                <svg v-if="checklistSubmitting" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                {{ checklistSubmitting ? 'Submitting…' : 'Submit Request' }}
              </button>

              <p class="text-[11px] text-gray-400 text-center">
                Your request will be sent for admin / lawyer approval before taking effect.
              </p>
            </div>
          </div>

          <!-- History Table -->
          <div class="col-span-3 bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-1 h-5 bg-violet-500 rounded-full"></div>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Movement History</h3>
              </div>
              <span class="text-xs text-gray-400">{{ checklistHistory.length }} records</span>
            </div>

            <div v-if="loadingChecklist" class="flex-1 flex items-center justify-center py-12">
              <div class="flex items-center gap-3 text-gray-400">
                <svg class="animate-spin w-5 h-5" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-sm">Loading history…</span>
              </div>
            </div>

            <div v-else-if="!checklistHistory.length" class="flex-1 flex flex-col items-center justify-center py-12 text-gray-400">
              <svg class="w-10 h-10 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>
              </svg>
              <p class="text-sm font-medium">No checklist movements yet</p>
            </div>

            <div v-else class="flex-1 overflow-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Task</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">From / To</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="row in paginatedChecklistHistory" :key="row.id"
                    class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3">
                      <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-bold border',
                        row.type === 'OUT'
                          ? 'bg-red-50 text-red-700 border-red-200'
                          : 'bg-emerald-50 text-emerald-700 border-emerald-200']">
                        {{ row.type }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-gray-700 max-w-[140px] truncate font-medium" :title="row.checklist?.task">
                      {{ row.checklist?.task || 'All items' }}
                    </td>
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ formatDate(row.date) }}</td>
                    <td class="px-4 py-3 text-gray-500 max-w-[100px] truncate" :title="row.from_to">{{ row.from_to || '—' }}</td>
                    <td class="px-4 py-3">
                      <span :class="approvalBadgeClass(row.approval_status)"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-bold border">
                        <span :class="approvalDotClass(row.approval_status)" class="w-1.5 h-1.5 rounded-full"></span>
                        {{ row.approval_status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="totalChecklistPages > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
              <span class="text-xs text-gray-400">Page {{ checklistPage }} of {{ totalChecklistPages }}</span>
              <div class="flex gap-1">
                <button @click="checklistPage--" :disabled="checklistPage === 1"
                  class="p-1.5 rounded-lg hover:bg-gray-100 disabled:opacity-40 transition text-gray-500">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                  </svg>
                </button>
                <button @click="checklistPage++" :disabled="checklistPage === totalChecklistPages"
                  class="p-1.5 rounded-lg hover:bg-gray-100 disabled:opacity-40 transition text-gray-500">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ TOAST ════════════════════════════════════════════════════════════ -->
    <Transition name="toast">
      <div v-if="toast.show" :class="[
        'fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-semibold border',
        toast.type === 'success'
          ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
          : 'bg-red-50 text-red-800 border-red-200'
      ]">
        <svg v-if="toast.type === 'success'" class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <svg v-else class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        {{ toast.message }}
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import {
  recordFolderMovement,
  getFolderMovements,
  recordChecklistMovement,
  getChecklistMovements,
} from '@/services/approvalService';
import { getChecklist } from '@/services/caseService';

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
  caseId: { type: Number, required: true },
});

// ── State ──────────────────────────────────────────────────────────────────
const activeTab        = ref('folder');
const folderHistory    = ref([]);
const checklistHistory = ref([]);
const checklist        = ref([]);
const folderStatus     = ref({ is_out: false });

const loadingFolder    = ref(false);
const loadingChecklist = ref(false);

const folderSubmitting    = ref(false);
const checklistSubmitting = ref(false);

const folderPage    = ref(1);
const checklistPage = ref(1);
const PAGE_SIZE     = 8;

const today = new Date().toISOString().split('T')[0];

const folderForm = ref({
  type:       'OUT',
  date:       today,
  from_to:    '',
  purpose:    '',
  handled_by: '',
});

const checklistForm = ref({
  type:         'OUT',
  checklist_id: null,
  date:         today,
  from_to:      '',
  purpose:      '',
  handled_by:   '',
});

const toast = ref({ show: false, message: '', type: 'success' });

// ── Tabs ───────────────────────────────────────────────────────────────────
const tabs = computed(() => [
  {
    key:   'folder',
    label: 'Folder',
    badge: folderHistory.value.filter(m => m.approval_status === 'PENDING').length,
    icon:  '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>',
  },
  {
    key:   'checklist',
    label: 'Checklist',
    badge: checklistHistory.value.filter(m => m.approval_status === 'PENDING').length,
    icon:  '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-6 9l2 2 4-4"/></svg>',
  },
]);

// ── Computed ───────────────────────────────────────────────────────────────
const pendingCount = computed(() =>
  folderHistory.value.filter(m => m.approval_status === 'PENDING').length +
  checklistHistory.value.filter(m => m.approval_status === 'PENDING').length
);

const totalFolderPages = computed(() =>
  Math.max(1, Math.ceil(folderHistory.value.length / PAGE_SIZE))
);
const totalChecklistPages = computed(() =>
  Math.max(1, Math.ceil(checklistHistory.value.length / PAGE_SIZE))
);

const paginatedFolderHistory = computed(() => {
  const start = (folderPage.value - 1) * PAGE_SIZE;
  return folderHistory.value.slice(start, start + PAGE_SIZE);
});
const paginatedChecklistHistory = computed(() => {
  const start = (checklistPage.value - 1) * PAGE_SIZE;
  return checklistHistory.value.slice(start, start + PAGE_SIZE);
});

const selectedChecklistItem = computed(() =>
  checklistForm.value.checklist_id
    ? checklist.value.find(c => c.id === checklistForm.value.checklist_id)
    : null
);

// ── Data fetching ──────────────────────────────────────────────────────────
const loadFolderHistory = async () => {
  loadingFolder.value = true;
  try {
    const res = await getFolderMovements(props.caseId);
    folderHistory.value = res.data ?? [];
    if (res.case) folderStatus.value = res.case;
  } catch (e) {
    showToast(e.message || 'Failed to load folder history.', 'error');
  } finally {
    loadingFolder.value = false;
  }
};

const loadChecklistHistory = async () => {
  loadingChecklist.value = true;
  try {
    const res = await getChecklistMovements(props.caseId);
    checklistHistory.value = res.data ?? [];
  } catch (e) {
    showToast(e.message || 'Failed to load checklist history.', 'error');
  } finally {
    loadingChecklist.value = false;
  }
};

const loadChecklist = async () => {
  try {
    const res = await getChecklist(props.caseId);
    checklist.value = res.data?.data ?? res.data ?? [];
  } catch { /* silent */ }
};

// ── Submit handlers ────────────────────────────────────────────────────────
const submitFolderRequest = async () => {
  if (!folderForm.value.type || !folderForm.value.date) return;
  folderSubmitting.value = true;
  try {
    await recordFolderMovement(props.caseId, {
      type:       folderForm.value.type,
      date:       folderForm.value.date,
      from_to:    folderForm.value.from_to   || undefined,
      purpose:    folderForm.value.purpose   || undefined,
      handled_by: folderForm.value.handled_by || undefined,
    });
    showToast('Request submitted! Awaiting approval.', 'success');
    folderForm.value = { type: 'OUT', date: today, from_to: '', purpose: '', handled_by: '' };
    await loadFolderHistory();
  } catch (e) {
    showToast(e.message || 'Failed to submit request.', 'error');
  } finally {
    folderSubmitting.value = false;
  }
};

const submitChecklistRequest = async () => {
  if (!checklistForm.value.type || !checklistForm.value.date) return;
  checklistSubmitting.value = true;
  try {
    await recordChecklistMovement(props.caseId, {
      type:         checklistForm.value.type,
      checklist_id: checklistForm.value.checklist_id || undefined,
      date:         checklistForm.value.date,
      from_to:      checklistForm.value.from_to    || undefined,
      purpose:      checklistForm.value.purpose    || undefined,
      handled_by:   checklistForm.value.handled_by || undefined,
    });
    showToast('Request submitted! Awaiting approval.', 'success');
    checklistForm.value = { type: 'OUT', checklist_id: null, date: today, from_to: '', purpose: '', handled_by: '' };
    await loadChecklistHistory();
  } catch (e) {
    showToast(e.message || 'Failed to submit request.', 'error');
  } finally {
    checklistSubmitting.value = false;
  }
};

// ── Helpers ────────────────────────────────────────────────────────────────
const formatDate = (d) => {
  if (!d) return '—';
  const dt = new Date(d);
  if (isNaN(dt)) return d;
  return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const approvalBadgeClass = (status) => ({
  PENDING:  'bg-amber-50 text-amber-700 border-amber-200',
  APPROVED: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  REJECTED: 'bg-red-50 text-red-700 border-red-200',
}[status] ?? 'bg-gray-50 text-gray-600 border-gray-200');

const approvalDotClass = (status) => ({
  PENDING:  'bg-amber-500',
  APPROVED: 'bg-emerald-500',
  REJECTED: 'bg-red-500',
}[status] ?? 'bg-gray-400');

let toastTimer = null;
const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type };
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => { toast.value.show = false; }, 3500);
};

watch(() => folderHistory.value.length, () => { folderPage.value = 1; });
watch(() => checklistHistory.value.length, () => { checklistPage.value = 1; });

// ── Lifecycle ──────────────────────────────────────────────────────────────
onMounted(async () => {
  await Promise.all([
    loadFolderHistory(),
    loadChecklistHistory(),
    loadChecklist(),
  ]);
});
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.toast-enter-from, .toast-leave-to       { opacity: 0; transform: translateY(8px) scale(0.95); }
</style>
