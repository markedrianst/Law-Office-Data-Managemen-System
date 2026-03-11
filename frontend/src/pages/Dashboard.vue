<template>
  <Suspense>
    <template #default>
      <component :is="dashboardComponent" />
    </template>
    <template #fallback>
      <div class="dashboard-loading">
        <div class="loading-spinner"></div>
        <p>Loading dashboard...</p>
      </div>
    </template>
  </Suspense>
</template>

<script setup>
import { ref, computed, defineAsyncComponent } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { userRole, clearSession } = useAuth()

// Lazy load dashboards based on role
const dashboardComponent = computed(() => {
  const role = userRole.value?.toLowerCase()
  
  switch(role) {
    case 'admin':
      return defineAsyncComponent(() => import('@/pages/Admin/AdminDashboard.vue'))
    case 'lawyer':
      return defineAsyncComponent(() => import('@/pages/Lawyer/LawyerDashboard.vue'))
    case 'clerk':
      return defineAsyncComponent(() => import('@/pages/Clerks/ClerksDashboard.vue'))
    default:
      return null
  }
})

// Handle logout if role not recognized
const logout = () => {
  clearSession()
  router.push('/')
}
</script>

<style scoped>
.dashboard-loading {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  font-family: 'Segoe UI', sans-serif;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #1a4972;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.dashboard-loading p {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

@media (max-width: 768px) {
  .dashboard-loading {
    padding: 16px;
  }
  
  .loading-spinner {
    width: 32px;
    height: 32px;
  }
  
  .dashboard-loading p {
    font-size: 13px;
  }
}
</style>