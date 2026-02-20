<script setup>
import Header from "@/components/Header.vue";
import Footer from "@/components/Footer.vue";
import Sidebar from "@/components/Sidebar.vue";
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <Header />
    <div class="flex flex-1">
      <Sidebar />
      <main class="flex-1 p-6 bg-gray-50">
        <router-view />
      </main>
    </div>
    <Footer />
  </div>
</template>