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