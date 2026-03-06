<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";
import MainLayout from "@/layouts/Layout.vue";

const route = useRoute();

const useLayout = computed(() => {
  const noLayoutNames = ["Login", "Register", "NotFound"];
  return !noLayoutNames.includes(route.name);
});
</script>

<template>
  <MainLayout v-if="useLayout">
    <router-view />
  </MainLayout>

  <router-view v-else />
</template>

<style>
* {
  box-sizing: border-box;
}

html,
body {
  margin: 0;
  padding: 0;
  width: 100%;
  height: 100%;
  font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
  background-color: #f4f6f9;
  overflow: hidden;
}

#app {
  width: 100%;
  height: 100%;
}

/* Mobile-friendly adjustments */
@media (max-width: 768px) {
  html,
  body {
    font-size: 14px;
  }
}

@media (max-width: 480px) {
  html,
  body {
    font-size: 13px;
  }
}
</style>