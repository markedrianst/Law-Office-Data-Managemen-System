// src/services/authService.js
import api from "./api";

/**
 * Login — stores token & user in sessionStorage (auto-clears on tab/browser close).
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
  }
};

/**
 * Fetch authenticated user from server.
 */
export const getUser = async () => {
  const response = await api.get("/user");
  return response.data;
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