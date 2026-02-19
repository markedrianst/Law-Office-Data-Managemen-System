import axios from "axios";

const api = axios.create({
  baseURL: "http://localhost:8000/api",
  withCredentials: true,
});

// Request interceptor
api.interceptors.request.use(config => {
  const token = localStorage.getItem("token");
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

// Response interceptor - Check for role changes
api.interceptors.response.use(
  (response) => {
    // Check if response contains updated user data
    if (response.data?.user) {
      const currentUser = JSON.parse(localStorage.getItem("user") || "{}");
      const newUser = response.data.user;
      
      // If role changed, update localStorage and emit event
      if (currentUser.role?.name !== newUser.role?.name) {
        localStorage.setItem("user", JSON.stringify(newUser));
        // Emit custom event for role change
        window.dispatchEvent(new CustomEvent('user-role-changed', { 
          detail: { oldRole: currentUser.role?.name, newRole: newUser.role?.name }
        }));
      }
    }
    return response;
  },
  (error) => {
    return Promise.reject(error);
  }
);

export default api;