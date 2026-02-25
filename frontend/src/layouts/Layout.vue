<template>
  <div class="layout">
    <Sidebar class="sidebar" />

    <div class="main">
      <Header class="header" />

      <!-- Scrollable Area -->
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
import Sidebar from "@/components/Sidebar.vue";
import Header from "@/components/Header.vue";
import Footer from "@/components/Footer.vue";
import { useStatusCheck } from "@/composables/useStatusCheck";

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
</script>

<style scoped>
/* ===== LAYOUT STRUCTURE ===== */
.layout {
  display: flex;
  height: 100vh;
  overflow: hidden; /* prevent whole page scroll */
}

/* ===== SIDEBAR ===== */
.sidebar {
  width: 250px;
  height: 100vh;
  flex-shrink: 0;
}

/* ===== RIGHT SIDE ===== */
.main {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: 100vh;
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
  background: #f4f6f9;
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
  box-shadow: 0 24px 64px rgba(0,0,0,0.25);
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 14px;
  font-family: 'Segoe UI', sans-serif;
}
</style>