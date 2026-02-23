<template>
  <footer
    class="flex items-center justify-between px-6 py-3 flex-shrink-0"
    :style="{
      background: 'linear-gradient(135deg, #1a4972 0%, #0f2f4a 100%)',
      borderTop: '1px solid rgba(255,255,255,0.08)',
      fontFamily: '\'Segoe UI\', sans-serif'
    }"
  >
    <p class="text-xs" style="color: rgba(255,255,255,0.45);">
      &copy; {{ currentYear }} Pineda Law Office. All rights reserved.
    </p>
    <p class="text-xs" style="color: rgba(255,255,255,0.35);">
      Document Management System
    </p>
  </footer>
</template>

<script setup>
const currentYear = new Date().getFullYear();
</script>