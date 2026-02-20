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