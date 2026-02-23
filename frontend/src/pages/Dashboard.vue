<template>
  <AdminDashboard  v-if="userRole === 'admin'" />
  <LawyerDashboard v-else-if="userRole === 'lawyer'" />
  <ClerksDashboard v-else-if="userRole === 'clerk'" />

  <!-- Fallback: role not recognized -->
  <div v-else class="error">
    <p>Access denied. Your role is not recognized.</p>
    <button @click="logout">Logout</button>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import AdminDashboard  from "@/pages/Admin/AdminDashboard.vue";
import LawyerDashboard from "@/pages/Lawyer/LawyerDashboard.vue";
import ClerksDashboard from "@/pages/Clerks/ClerksDashboard.vue";

const router = useRouter();

const userRole = computed(() => {
  try {
    return JSON.parse(localStorage.getItem("user"))?.role?.name ?? null;
  } catch {
    return null;
  }
});

function logout() {
  localStorage.removeItem("token");
  localStorage.removeItem("user");
  router.push("/");
}
</script>

<style scoped>
.error {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f0f4f8;
  font-family: 'Segoe UI', sans-serif;
  padding: 20px;
  text-align: center;
}

.error p {
  color: #1e293b;
  font-size: 16px;
  margin-bottom: 16px;
}

.error button {
  padding: 8px 16px;
  background: linear-gradient(135deg, #1a4972, #0f2f4a);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

@media (max-width: 480px) {
  .error p { font-size: 14px; }
}
</style>