<template>
  <div class="p-6 bg-gray-100 min-h-screen">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Lawyer Dashboard</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">My Assigned Cases</h2>
        <div v-if="loadingCases">Loading...</div>
        <div v-else-if="assignedCases.length === 0">No assigned cases.</div>
        <ul v-else>
          <li v-for="caseItem in assignedCases" :key="caseItem.id" class="mb-2">
            <router-link :to="`/casemaster`" class="text-blue-600 hover:underline">{{ caseItem.title }}</router-link>
          </li>
        </ul>
      </div>
      <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Pending Case Requests</h2>
        <div v-if="loadingRequests">Loading...</div>
        <div v-else-if="pendingRequests.length === 0">No pending requests.</div>
        <ul v-else>
          <li v-for="request in pendingRequests" :key="request.id" class="mb-2">
            <router-link :to="`/case-requests`" class="text-blue-600 hover:underline">{{ request.request_type }} - {{ request.case ? request.case.title : 'New Case' }}</router-link>
          </li>
        </ul>
      </div>
      <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Recent Activity</h2>
        <div v-if="loadingActivities">Loading...</div>
        <div v-else-if="recentActivities.length === 0">No recent activity.</div>
        <ul v-else>
          <li v-for="activity in recentActivities" :key="activity.id" class="mb-2">
            {{ activity.action }} on {{ activity.case.title }}
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import { useAuth } from '@/composables/useAuth';

const { user } = useAuth();

const assignedCases = ref([]);
const pendingRequests = ref([]);
const recentActivities = ref([]);
const loadingCases = ref(true);
const loadingRequests = ref(true);
const loadingActivities = ref(true);

const fetchAssignedCases = async () => {
  try {
    const response = await api.get(`/cases?assigned_lawyer_id=${user.value.id}`);
    assignedCases.value = response.data.data;
  } catch (error) {
    console.error('Error fetching assigned cases:', error);
  } finally {
    loadingCases.value = false;
  }
};

const fetchPendingRequests = async () => {
  try {
    const response = await api.get('/case-requests?status=pending');
    pendingRequests.value = response.data.data;
  } catch (error) {
    console.error('Error fetching pending requests:', error);
  } finally {
    loadingRequests.value = false;
  }
};

const fetchRecentActivities = async () => {
  try {
    const response = await api.get(`/audit-logs/case-activity?user_id=${user.value.id}&limit=5`);
    recentActivities.value = response.data.data;
  } catch (error) {
    console.error('Error fetching recent activities:', error);
  } finally {
    loadingActivities.value = false;
  }
};

onMounted(() => {
  fetchAssignedCases();
  fetchPendingRequests();
  fetchRecentActivities();
});
</script>
