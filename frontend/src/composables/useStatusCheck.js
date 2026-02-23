// src/composables/useStatusCheck.js
import { ref, onMounted, onUnmounted } from "vue";
import axios from "axios";
import api, { onAccountDeactivated } from "@/services/api";
import { useRouter } from "vue-router";
import { useAuth } from "@/composables/useAuth";

export function useStatusCheck(intervalMs = 30000) {
  const router = useRouter();
  const { patchStoredUser, userRole } = useAuth();

  const showDeactivatedModal = ref(false);
  const showRoleChangedModal = ref(false);
  const showCredentialModal  = ref(false);
  const deactivatedMessage   = ref("");
  const roleChangedMessage   = ref("");
  const credentialMessage    = ref("");
  let intervalId = null;

  const checkStatus = async () => {
    const token = sessionStorage.getItem("token");
    if (!token) return;

    try {
      // KEY FIX: Use raw axios instead of the `api` instance so the global
      // interceptor in api.js does NOT swallow 401 before our catch sees it.
      //
      // Why roles/status worked but password didn't:
      //   Role change   → 200 with new role  → api instance fine ✓
      //   Deactivation  → 403                → api interceptor fires event ✓
      //   Password change → 401              → api interceptor was silently
      //                                        redirecting to "/" with no modal ✗
      //
      // Raw axios with manual Bearer token bypasses the interceptor entirely.
      const baseURL = api.defaults.baseURL ?? "";
      const res = await axios.get(`${baseURL}/check-status`, {
        params: { _t: Date.now() },
        headers: {
          Authorization: `Bearer ${token}`,
          "Cache-Control": "no-cache",
          "Pragma": "no-cache",
        },
      });

      const freshRole = res.data?.role;

      // Role changed while logged in → show modal, force re-login
      if (freshRole && freshRole !== userRole.value) {
        roleChangedMessage.value = `Your role has been changed to "${freshRole}". Please log in again for the changes to take effect.`;
        showRoleChangedModal.value = true;
        patchStoredUser({ role: { name: freshRole } });
      }

    } catch (err) {
      const status  = err?.response?.status;
      const message = err?.response?.data?.message ?? "";

      if (status === 401) {
        // Password was changed by admin (tokens deleted) or token expired
        credentialMessage.value =
          message || "Your session is no longer valid. Please log in again.";
        showCredentialModal.value = true;
        clearInterval(intervalId);
      } else if (status === 403) {
        // Account deactivated
        deactivatedMessage.value =
          message || "Your account has been deactivated by an administrator.";
        showDeactivatedModal.value = true;
        clearInterval(intervalId);
      }
      // Other errors (network, 500) — silently ignore, retry next interval
    }
  };

  const handleVisibilityChange = () => {
    if (document.visibilityState === "visible") checkStatus();
  };

  // Keep event bus for deactivation triggered by OTHER api calls
  const unsubscribe = onAccountDeactivated((msg) => {
    deactivatedMessage.value = msg || "Your account has been deactivated by an administrator.";
    showDeactivatedModal.value = true;
  });

  const confirmDeactivatedLogout = () => {
    showDeactivatedModal.value = false;
    sessionStorage.removeItem("token");
    sessionStorage.removeItem("user");
    router.push("/");
  };

  const confirmRoleChangedLogout = () => {
    showRoleChangedModal.value = false;
    sessionStorage.removeItem("token");
    sessionStorage.removeItem("user");
    router.push("/");
  };

  const confirmCredentialLogout = () => {
    showCredentialModal.value = false;
    sessionStorage.removeItem("token");
    sessionStorage.removeItem("user");
    router.push("/");
  };

  onMounted(() => {
    setTimeout(() => {
      checkStatus();
      intervalId = setInterval(checkStatus, intervalMs);
      document.addEventListener("visibilitychange", handleVisibilityChange);
    }, 1000);
  });

  onUnmounted(() => {
    clearInterval(intervalId);
    document.removeEventListener("visibilitychange", handleVisibilityChange);
    unsubscribe();
  });

  return {
    showDeactivatedModal,
    deactivatedMessage,
    confirmDeactivatedLogout,
    showRoleChangedModal,
    roleChangedMessage,
    confirmRoleChangedLogout,
    showCredentialModal,
    credentialMessage,
    confirmCredentialLogout,
  };
}