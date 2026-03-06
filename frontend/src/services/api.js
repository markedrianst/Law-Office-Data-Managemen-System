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
const _listeners = new Set();
export const onAccountDeactivated = (fn) => {
  _listeners.add(fn);
  return () => _listeners.delete(fn);
};
const emitDeactivated = (msg) => _listeners.forEach((fn) => fn(msg));

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://localhost:8000/api",
  timeout: 30000,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
  },
});

// Response cache for GET requests (5 second TTL)
const requestCache = new Map();
const CACHE_TTL = 5000;

api.interceptors.request.use((config) => {
  const token = sessionStorage.getItem("token");
  if (token) config.headers.Authorization = `Bearer ${token}`;

  // If the caller passed `fresh: true` in params, convert it to a _t timestamp
  // so the request bypasses both this cache AND any server-side HTTP cache.
  if (config.method === "get" && config.params?.fresh) {
    delete config.params.fresh;
    config.params._t = Date.now();
  }

  // Skip cache lookup when a _t (timestamp/cache-buster) param is present.
  // This is used by forceRefresh calls (e.g. getAssignableUsers(true)) so
  // they always hit the network instead of returning a 5-second-old response.
  if (config.method === "get" && !config.params?._t) {
    const cacheKey = config.url + JSON.stringify(config.params ?? {});
    const cached = requestCache.get(cacheKey);
    if (cached && Date.now() - cached.timestamp < CACHE_TTL) {
      // Return a resolved promise with the cached response to short-circuit
      config._cachedResponse = cached.data;
    }
  }

  return config;
});

api.interceptors.response.use(
  (response) => {
    // Cache GET responses that are NOT cache-busted
    if (
      response.config.method === "get" &&
      response.config.url &&
      !response.config.params?._t
    ) {
      const cacheKey =
        response.config.url + JSON.stringify(response.config.params ?? {});
      requestCache.set(cacheKey, {
        data: response.data,
        timestamp: Date.now(),
      });

      // Prune stale entries
      setTimeout(() => {
        for (const [key, value] of requestCache.entries()) {
          if (Date.now() - value.timestamp > CACHE_TTL) {
            requestCache.delete(key);
          }
        }
      }, CACHE_TTL);
    }

    return response;
  },
  async (error) => {
    const status = error.response?.status;
    const msg = error.response?.data?.message || "";
    const url = error.config?.url || "";

    if (
      status === 401 &&
      !url.includes("/login") &&
      !url.includes("/changepassword")
    ) {
      sessionStorage.removeItem("token");
      sessionStorage.removeItem("user");
      const router = await getRouter();
      if (router.currentRoute.value.path !== "/") router.push("/");
    }

    if (status === 403 && url.includes("/check-status")) {
      sessionStorage.removeItem("token");
      sessionStorage.removeItem("user");
      emitDeactivated(msg);
    }

    return Promise.reject(error);
  }
);

const pendingRequests = new Map();

export const deduplicateRequest = async (config) => {
  const requestKey = `${config.method}-${config.url}-${JSON.stringify(
    config.params
  )}`;

  // Don't deduplicate cache-busted requests — they must always fire
  if (config.method === "get" && !config.params?._t && pendingRequests.has(requestKey)) {
    return pendingRequests.get(requestKey);
  }

  if (config.method === "get") {
    const promise = api(config);
    pendingRequests.set(requestKey, promise);
    promise.finally(() => {
      pendingRequests.delete(requestKey);
    });
    return promise;
  }

  return api(config);
};

export default api;