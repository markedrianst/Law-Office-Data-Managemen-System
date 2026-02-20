import api from "./api";

export const login = async (credentials) => {
  try {
    const response = await api.post("/login", credentials);

    // Store token and user data
    localStorage.setItem("token", response.data.token);
    localStorage.setItem("user", JSON.stringify(response.data.user));

    return response.data;
  } catch (error) {
    if (error.response && error.response.data) {
      // Return exact backend error
      throw error.response.data;
    } else {
      throw { message: "Network Error. Please check your connection." };
    }
  }
};
export const logout = async () => {
  try {
    await api.post("/logout");
  } catch (error) {
    console.error("Logout error:", error);
  } finally {
    // Always remove tokens even if API call fails
    localStorage.removeItem("token");
    localStorage.removeItem("user");
  }
};

export const getUser = async () => {
  const response = await api.get("/user");
  return response.data;
};