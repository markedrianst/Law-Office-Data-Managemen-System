<template>
  <div class="layout">
    <!-- Sidebar -->
    <Sidebar class="sidebar" />

    <!-- Main content area -->
    <div class="main">
      <Header class="header" />

      <!-- Scrollable content -->
      <div class="content">
        <slot />
      </div>

      <Footer class="footer" />
    </div>

 
  </div>
</template>

<script setup>
import Sidebar from "@/components/Sidebar.vue";
import Header from "@/components/Header.vue";
import Footer from "@/components/Footer.vue";


</script>

<style scoped>
/* ===== LAYOUT STRUCTURE ===== */
.layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
  flex-direction: row;
}

/* ===== SIDEBAR ===== */
.sidebar {
  width: 250px;
  height: 100vh;
  flex-shrink: 0;
  overflow-y: auto;
  background: #1a4972; /* example color */
  color: white;
}

/* ===== MAIN CONTENT ===== */
.main {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
  background: #f4f6f9;
}

/* ===== HEADER ===== */
.header {
  flex-shrink: 0;
}

/* ===== SCROLLABLE CONTENT ===== */
.content {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}

/* ===== FOOTER ===== */
.footer {
  flex-shrink: 0;
}

/* ===== MODALS (UNCHANGED) ===== */
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 99999;
  background: rgba(10, 20, 35, 0.7);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.modal-card {
  background: white;
  border-radius: 20px;
  padding: 36px 28px 28px;
  width: 100%;
  max-width: 380px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 14px;
  font-family: 'Segoe UI', sans-serif;
}

@media (max-width: 480px) {
  .modal-card {
    max-width: 90%;
    padding: 28px 20px 20px;
  }
}

/* Modal transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>