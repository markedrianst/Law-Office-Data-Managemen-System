<template>
  <div class="h-screen w-64 bg-white text-black flex flex-col">
    <!-- Logo / Brand -->
    <div class="p-6 flex items-center justify-center border-b border-gray-700">
      <img src="@/assets/images/lawofficelogo.png" alt="Logo" class="h-12 w-12 mr-2" />
      <span class="text-lg font-bold">Law Office</span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-2">
      <router-link
        v-for="item in navItems"
        :key="item.name"
        :to="item.path"
        class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors"
        :class="{ 'bg-gray-200': isActive(item.path) }"
      >
        <span class="mr-3">{{ item.icon }}</span>
        <span>{{ item.name }}</span>
      </router-link>
    </nav>

    <!-- Logout
    <div class="p-4 border-t border-gray-700">
      <button
        @click="logout"
        class="w-full flex items-center justify-center px-4 py-2 bg-red-600 rounded-lg hover:bg-red-500 transition-colors"
      >
        Logout
      </button>
    </div> -->
  </div>
</template>

<script setup>
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();

// Example nav items
const navItems = [
  { name: "Dashboard", path: "/dashboard", icon: "🏠" },
  { name: "Users", path: "/usermanagement", icon: "👥" },
  { name: "Schedules", path: "/schedules", icon: "📅" },
  { name: "Cases", path: "/cases", icon: "⚖️" },
];

const isActive = (path) => route.path === path;

const logout = () => {
  localStorage.removeItem("token");
  localStorage.removeItem("user");
  router.push("/login");
};
</script>

<style scoped>
/* Optional: scrollbar styling */
nav::-webkit-scrollbar {
  width: 6px;
}
nav::-webkit-scrollbar-thumb {
  background-color: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
}
</style>