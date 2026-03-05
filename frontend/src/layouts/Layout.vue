<template>
  <div class="layout">
    <!-- Mobile sidebar overlay -->
    <div v-if="sidebarOpen && isMobile" class="sidebar-overlay" @click="sidebarOpen = false"></div>

    <!-- Sidebar (hidden on mobile, shown as drawer) -->
    <Sidebar 
      class="sidebar" 
      :class="{ 'mobile-visible': sidebarOpen && isMobile }"
      @navigate="sidebarOpen = false"
    />

    <!-- Main content area -->
    <div class="main">
      <Header 
        class="header" 
        :sidebar-open="sidebarOpen" 
        @toggle-sidebar="sidebarOpen = !sidebarOpen"
      />

      <!-- Scrollable content -->
      <div class="content">
        <slot />
      </div>

      <Footer class="footer" />
    </div>

    <!-- ===== MODALS ===== -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showDeactivatedModal" class="modal-overlay">
          <div class="modal-card">
            <div class="modal-icon modal-icon--red">
              <!-- svg kept same -->
            </div>
            <h2 class="modal-title">Account Deactivated</h2>
            <p class="modal-msg">{{ deactivatedMessage }}</p>
            <button class="modal-btn modal-btn--red" @click="confirmDeactivatedLogout">
              Back to Login
            </button>
          </div>
        </div>
      </Transition>

      <Transition name="modal">
        <div v-if="showRoleChangedModal" class="modal-overlay">
          <div class="modal-card">
            <div class="modal-icon modal-icon--blue"></div>
            <h2 class="modal-title">Role Updated</h2>
            <p class="modal-msg">{{ roleChangedMessage }}</p>
            <button class="modal-btn modal-btn--blue" @click="confirmRoleChangedLogout">
              Re-login
            </button>
          </div>
        </div>
      </Transition>

      <Transition name="modal">
        <div v-if="showCredentialModal" class="modal-overlay">
          <div class="modal-card">
            <div class="modal-icon modal-icon--amber"></div>
            <h2 class="modal-title">Session Expired</h2>
            <p class="modal-msg">{{ credentialMessage }}</p>
            <button class="modal-btn modal-btn--amber" @click="confirmCredentialLogout">
              Back to Login
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import Sidebar from "@/components/Sidebar.vue";
import Header from "@/components/Header.vue";
import Footer from "@/components/Footer.vue";
import { useStatusCheck } from "@/composables/useStatusCheck";

const sidebarOpen = ref(false);
const isMobile = ref(false);

const {
  showDeactivatedModal,
  deactivatedMessage,
  confirmDeactivatedLogout,
  showRoleChangedModal,
  roleChangedMessage,
  confirmRoleChangedLogout,
  showCredentialModal,
  credentialMessage,
  confirmCredentialLogout,
} = useStatusCheck(30000);

// Detect mobile screen size
const handleResize = () => {
  isMobile.value = window.innerWidth < 768;
  // Close sidebar when switching to desktop
  if (!isMobile.value) {
    sidebarOpen.value = false;
  }
};

onMounted(() => {
  handleResize();
  window.addEventListener('resize', handleResize);
  // Close sidebar on route change
  window.addEventListener('popstate', () => {
    sidebarOpen.value = false;
  });
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
  window.removeEventListener('popstate', () => {
    sidebarOpen.value = false;
  });
});
</script>

<style scoped>
/* ===== LAYOUT STRUCTURE ===== */
.layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
  position: relative;
  flex-direction: row;
}

/* ===== SIDEBAR ===== */
.sidebar {
  width: 250px;
  height: 100vh;
  flex-shrink: 0;
  overflow-y: auto;
}

/* Mobile sidebar - convert to drawer */
@media (max-width: 767px) {
  .sidebar {
    position: fixed;
    left: 0;
    top: 0;
    z-index: 50;
    width: 75vw;
    max-width: 280px;
    height: 100vh;
    transform: translateX(-100%);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 2px 0 12px rgba(0, 0, 0, 0.2);
  }

  .sidebar.mobile-visible {
    transform: translateX(0);
  }
}

/* ===== SIDEBAR OVERLAY (MOBILE) ===== */
.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 40;
  backdrop-filter: blur(2px);
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
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

/* Tablet and smaller phones */
@media (max-width: 640px) {
  .content {
    padding: 16px;
  }
}

/* Small phones */
@media (max-width: 480px) {
  .content {
    padding: 12px;
  }
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
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.25);
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