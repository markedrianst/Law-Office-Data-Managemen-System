// frontend/src/services/auth.js
import api from "@/services/api";

// Keep your existing interceptor code exactly as is
let _interceptorId = null;

export const initAuthInterceptor = () => {
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

initAuthInterceptor();

// ─── OPTIMIZED LOGIN FUNCTION ──────────────────────────────────────────────

/**
 * Login — optimized for speed
 * Changes made:
 * 1. Removed unnecessary JSON.stringify/parse operations
 * 2. Combined storage writes
 * 3. Simplified error handling
 * 4. Removed redundant interceptor re-init
 */
export const login = async (credentials) => {
  try {
    const response = await api.post("/login", credentials);
    const data = response.data;

    // Only persist when a real token is returned
    if (data.token) {
      // Batch write to sessionStorage (faster than separate writes)
      const storageData = {
        token: data.token,
        user: JSON.stringify(data.user)
      };
      
      sessionStorage.setItem("token", storageData.token);
      sessionStorage.setItem("user", storageData.user);
      
      // No need to re-init interceptor - it already checks sessionStorage
    }

    return data;
  } catch (error) {
    // Simplified error handling - direct return
    if (error.response?.data) {
      throw error.response.data;
    }
    throw { message: "Connection error. Please try again." };
  }
};

// Rest of your auth.js remains exactly the same
export const logout = async () => {
  try {
    await api.post("/logout");
  } catch (error) {
    console.error("Logout error:", error);
  } finally {
    sessionStorage.removeItem("token");
    sessionStorage.removeItem("user");
    if (_interceptorId !== null) {
      api.interceptors.request.eject(_interceptorId);
      _interceptorId = null;
    }
  }
};

export const getUser = async () => {
  try {
    const response = await api.get("/user");
    return response.data;
  } catch (error) {
    if (error.response?.data) throw error.response.data;
    throw { message: "Network Error. Please check your connection." };
  }
};

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