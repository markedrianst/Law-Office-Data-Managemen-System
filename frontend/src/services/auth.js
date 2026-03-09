// src/services/authService.js
import api from "@/services/api";

// ─────────────────────────────────────────────────────────────────────────────
// INTERCEPTOR BOOTSTRAP
// Attaches the Bearer token from sessionStorage to every outgoing request.
// Called once on app boot (from main.js or App.vue) via initAuthInterceptor().
// Also called automatically after a successful login.
// ─────────────────────────────────────────────────────────────────────────────

let _interceptorId = null;

export const initAuthInterceptor = () => {
  // Eject any previously registered interceptor to avoid stacking duplicates
  if (_interceptorId !== null) {
    api.interceptors.request.eject(_interceptorId);
  }

  _interceptorId = api.interceptors.request.use((config) => {
    const token = sessionStorage.getItem("token");
    if (token) {
      config.headers = config.headers ?? {};
      config.headers["Authorization"] = `Bearer ${token}`;
    }
    return config;
  });
};

// Register on module load so existing sessions are covered immediately
initAuthInterceptor();

// ─────────────────────────────────────────────────────────────────────────────
// AUTH FUNCTIONS
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Login — stores token & user in sessionStorage, then refreshes the interceptor.
 */
export const login = async (credentials) => {
  try {
    const response = await api.post("/login", credentials);
    const data = response.data;

    // Only persist when a real token is returned
    // (won't be present when must_change_password = true)
    if (data.token) {
      sessionStorage.setItem("token", data.token);
      sessionStorage.setItem("user", JSON.stringify(data.user));
      // Re-register interceptor so the new token is picked up immediately
      initAuthInterceptor();
    }

    return data;
  } catch (error) {
    if (error.response?.data) {
      throw error.response.data;
    }
    throw { message: "Network Error. Please check your connection." };
  }
};

/**
 * Logout — revokes server token, then clears sessionStorage.
 */
export const logout = async () => {
  try {
    await api.post("/logout");
  } catch (error) {
    // Always clear client-side session even if server call fails
    console.error("Logout error:", error);
  } finally {
    sessionStorage.removeItem("token");
    sessionStorage.removeItem("user");
    // Eject the interceptor so no token is sent after logout
    if (_interceptorId !== null) {
      api.interceptors.request.eject(_interceptorId);
      _interceptorId = null;
    }
  }
};

/**
 * Fetch authenticated user from server.
 */
export const getUser = async () => {
  try {
    const response = await api.get("/user");
    return response.data;
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: "Network Error. Please check your connection." };
  }
};

/**
 * Change password (used on forced-change flow — no token required yet).
 */
export const changePassword = async (passwordData) => {
  try {
    const response = await api.put("/changepassword", passwordData, {
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
    });
    return response.data;
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    if (error.request) throw { message: "Network Error. Please check your connection." };
    throw { message: error.message || "An error occurred" };
  }
};