// src/services/api.js
import axios from "axios";

let _router = null;
const getRouter = async () => {
  if (!_router) {
    const mod = await import("@/router");
    _router = mod.default;
  }
  return _router;
};

// ── Shared event bus for showing the deactivated modal ────────────
// Components listen to this to show the modal before redirecting
const _listeners = new Set();
export const onAccountDeactivated = (fn) => {
  _listeners.add(fn);
  return () => _listeners.delete(fn); // returns unsubscribe fn
};
const emitDeactivated = (msg) => _listeners.forEach((fn) => fn(msg));

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://localhost:8000/api",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

api.interceptors.request.use((config) => {
  const token = sessionStorage.getItem("token");
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error.response?.status;
    const msg = error.response?.data?.message || "";
    const url = error.config?.url || "";

    // Only auto-logout on 401 for non-login routes
    if (status === 401 && !url.includes("/login") && !url.includes("/changepassword")) {
      sessionStorage.removeItem("token");
      sessionStorage.removeItem("user");
      const router = await getRouter();
      if (router.currentRoute.value.path !== "/") router.push("/");
    }

    // Only trigger deactivated modal for the check-status route
    if (status === 403 && url.includes("/check-status")) {
      sessionStorage.removeItem("token");
      sessionStorage.removeItem("user");
      emitDeactivated(msg);
    }

    return Promise.reject(error);
  }
);

export default api;