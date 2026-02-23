<template>
  <div class="layout">
    <Sidebar />
    <div class="main">
      <Header />
      <router-view />
      <Footer />
    </div>

    <Teleport to="body">
      <!-- ── Account Deactivated Modal ──────────────────────── -->
      <Transition name="modal">
        <div v-if="showDeactivatedModal" class="modal-overlay">
          <div class="modal-card">
            <div class="modal-icon modal-icon--red">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
            </div>
            <h2 class="modal-title">Account Deactivated</h2>
            <p class="modal-msg">{{ deactivatedMessage }}</p>
            <button class="modal-btn modal-btn--red" @click="confirmDeactivatedLogout">
              Back to Login
            </button>
          </div>
        </div>
      </Transition>

      <!-- ── Role Changed Modal ─────────────────────────────── -->
      <Transition name="modal">
        <div v-if="showRoleChangedModal" class="modal-overlay">
          <div class="modal-card">
            <div class="modal-icon modal-icon--blue">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
            </div>
            <h2 class="modal-title">Role Updated</h2>
            <p class="modal-msg">{{ roleChangedMessage }}</p>
            <button class="modal-btn modal-btn--blue" @click="confirmRoleChangedLogout">
              Re-login
            </button>
          </div>
        </div>
      </Transition>

      <!-- ── Credentials Changed Modal ─────────────────────── -->
      <Transition name="modal">
        <div v-if="showCredentialModal" class="modal-overlay">
          <div class="modal-card">
            <div class="modal-icon modal-icon--amber">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </div>
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
import Header  from "@/components/Header.vue";
import Footer  from "@/components/Footer.vue";
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
.layout { display: flex; min-height: 100vh; }
.main   { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

.modal-overlay {
  position: fixed; inset: 0; z-index: 99999;
  background: rgba(10, 20, 35, 0.7);
  backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center; padding: 16px;
}
.modal-card {
  background: white; border-radius: 20px;
  padding: 36px 28px 28px;
  width: 100%; max-width: 380px;
  box-shadow: 0 24px 64px rgba(0,0,0,0.25);
  display: flex; flex-direction: column; align-items: center;
  text-align: center; gap: 14px;
  font-family: 'Segoe UI', sans-serif;
}

/* Icons */
.modal-icon {
  width: 60px; height: 60px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
}
.modal-icon--red   { background: rgba(239,68,68,0.1);  border: 1px solid rgba(239,68,68,0.2);  color: #dc2626; }
.modal-icon--blue  { background: rgba(26,73,114,0.1);  border: 1px solid rgba(26,73,114,0.2);  color: #1a4972; }
.modal-icon--amber { background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2); color: #d97706; }

.modal-title { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; }
.modal-msg   { font-size: 13px; color: #64748b; margin: 0; line-height: 1.6; }

/* Buttons */
.modal-btn {
  margin-top: 6px; padding: 11px 32px; border-radius: 12px; border: none;
  color: white; font-size: 13px; font-weight: 700;
  cursor: pointer; transition: opacity .15s;
}
.modal-btn:hover { opacity: .9; }
.modal-btn--red   {
  background: linear-gradient(135deg, #dc2626, #991b1b);
  box-shadow: 0 4px 14px rgba(220,38,38,.3);
}
.modal-btn--blue  {
  background: linear-gradient(135deg, #1a4972, #0f2f4a);
  box-shadow: 0 4px 14px rgba(26,73,114,.3);
}
.modal-btn--amber {
  background: linear-gradient(135deg, #f59e0b, #b45309);
  box-shadow: 0 4px 14px rgba(245,158,11,.3);
}

.modal-enter-active, .modal-leave-active { transition: opacity .25s ease, transform .25s ease; }
.modal-enter-from, .modal-leave-to       { opacity: 0; transform: scale(.95); }
</style>