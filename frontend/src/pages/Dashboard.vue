<template>
  <AdminDashboard v-if="userRole === 'admin'" />
  <LawyerDashboard v-else-if="userRole === 'lawyer'" />
  <ClerksDashboard v-else-if="userRole === 'clerk'" />

  <!-- Fallback: role not recognized -->
  <div v-else class="error">
    <p>Access denied. Your role is not recognized.</p>
    <button @click="logout">Logout</button>
  </div>
</template>

<script setup>
import { useRouter } from "vue-router";
import { useAuth } from "@/composables/useAuth";
import AdminDashboard from "@/pages/Admin/AdminDashboard.vue";
import LawyerDashboard from "@/pages/Lawyer/LawyerDashboard.vue";
import ClerksDashboard from "@/pages/Clerks/ClerksDashboard.vue";

const router = useRouter();
const { userRole, clearSession } = useAuth();

function logout() {
  clearSession();
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
  line-height: 1.5;
}

.error button {
  padding: 10px 20px;
  background: linear-gradient(135deg, #1a4972, #0f2f4a);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(26, 73, 114, 0.2);
}

.error button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(26, 73, 114, 0.3);
}

.error button:active {
  transform: translateY(0);
}

/* Tablet responsiveness */
@media (max-width: 768px) {
  .error {
    padding: 16px;
  }

  .error p {
    font-size: 15px;
  }

  .error button {
    font-size: 13px;
    padding: 9px 18px;
  }
}

/* Mobile responsiveness */
@media (max-width: 480px) {
  .error {
    padding: 12px;
    min-height: auto;
  }

  .error p {
    font-size: 14px;
    margin-bottom: 12px;
  }

  .error button {
    font-size: 12px;
    padding: 8px 16px;
    width: 100%;
    max-width: 200px;
  }
}
</style>