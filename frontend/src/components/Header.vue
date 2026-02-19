<template>
  <header class="bg-[#183e68] text-white px-6 py-4 flex justify-between items-center">
    <h1 class="font-semibold">
      Pineda Law Office DMS
    </h1>

    <div class="flex items-center gap-4">
      <span v-if="isAuthenticated">Welcome</span>

      <button
        v-if="isAuthenticated"
        @click="logoutUser"
        class="bg-white text-[#183e68] px-3 py-1 rounded-md text-sm font-semibold"
      >
        Logout
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import { logout } from "@/services/auth";

const router = useRouter();

const isAuthenticated = computed(() => {
  return localStorage.getItem("token");
});

const logoutUser = async () => {
  try {
    await logout();
    router.push("/");
  } catch (error) {
    console.error("Logout failed:", error);
    localStorage.removeItem("token");
    localStorage.removeItem("user");
    router.push("/");
  }
};
</script>
