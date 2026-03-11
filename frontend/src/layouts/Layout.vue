<template>
  <div class="layout">
    <!-- Mobile Overlay -->
    <Transition name="overlay">
      <div v-if="sidebarOpen && isMobile" class="sidebar-overlay" @click="closeSidebar"></div>
    </Transition>

    <!-- Sidebar -->
    <Transition name="sidebar-slide">
      <Sidebar 
        v-if="!isMobile || sidebarOpen"
        class="sidebar" 
        :class="{ 'sidebar-mobile': isMobile, 'sidebar-desktop': !isMobile }"
        @navigate="handleNavigate" />
    </Transition>

    <!-- Main content area -->
    <div class="main">
      <Header class="header" :sidebar-open="sidebarOpen" @toggle-sidebar="toggleSidebar" />

      <!-- Scrollable content -->
      <div class="content">
        <slot />
      </div>

      <Footer class="footer" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import Sidebar from "@/components/Sidebar.vue";
import Header from "@/components/Header.vue";
import Footer from "@/components/Footer.vue";

const sidebarOpen = ref(false);
const isMobile = ref(false);

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value;
};

const closeSidebar = () => {
  if (isMobile.value) {
    sidebarOpen.value = false;
  }
};

const handleResize = () => {
  isMobile.value = window.innerWidth < 768;
  if (!isMobile.value) {
    sidebarOpen.value = false;
  }
};

const handleEscape = (e) => {
  if (e.key === 'Escape' && sidebarOpen.value && isMobile.value) {
    closeSidebar();
  }
};

onMounted(() => {
  handleResize();
  window.addEventListener('resize', handleResize);
  document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
  document.removeEventListener('keydown', handleEscape);
});
</script>

<style scoped>
/* ===== LAYOUT STRUCTURE ===== */
.layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
  flex-direction: row;
  position: relative;
}

/* ===== SIDEBAR OVERLAY (Mobile backdrop) ===== */
.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 998;
}

.overlay-enter-active,
.overlay-leave-active {
  transition: opacity 0.3s ease;
}

.overlay-enter-from,
.overlay-leave-to {
  opacity: 0;
}

/* ===== SIDEBAR ===== */
.sidebar {
  height: 100vh;
  flex-shrink: 0;
  overflow-y: auto;
  background: linear-gradient(180deg, #1a4972 0%, #0f2f4a 55%, #091e31 100%);
  color: white;
}

/* Desktop sidebar - normal flow */
.sidebar-desktop {
  position: relative;
  z-index: 1;
}

/* Mobile sidebar - slides in from the RIGHT (like the reference image) */
.sidebar-mobile {
  position: fixed;
  right: 0;
  top: 0;
  bottom: 0;
  width: 220px;
  z-index: 999;
  box-shadow: -4px 0 24px rgba(0, 0, 0, 0.3);
}

/* Slide from right on mobile */
.sidebar-slide-enter-active,
.sidebar-slide-leave-active {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-slide-enter-from,
.sidebar-slide-leave-to {
  transform: translateX(100%);
}

/* ===== MAIN CONTENT ===== */
.main {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
  background: #f8fafc;
  min-width: 0;
}

/* ===== HEADER ===== */
.header {
  flex-shrink: 0;
  position: relative;
  z-index: 50;
}

/* ===== SCROLLABLE CONTENT ===== */
.content {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
}

.content::-webkit-scrollbar {
  width: 8px;
}

.content::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.content::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.content::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* ===== FOOTER ===== */
.footer {
  flex-shrink: 0;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 767px) {
  .layout {
    flex-direction: column;
  }
}

body:has(.sidebar-mobile) {
  overflow: hidden;
}
</style>