import api from "./api";

export const login = async (credentials) => {
  try {
    const response = await api.post("/login", credentials);
    const data = response.data;

    // Only store token if provided (i.e., must_change_password = false)
    if (data.token) {
      localStorage.setItem("token", data.token);
      localStorage.setItem("user", JSON.stringify(data.user));
    }

    return data;
  } catch (error) {
    if (error.response && error.response.data) {
      throw error.response.data; // backend errors
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

export const changePassword = async (passwordData) => {
  try {
    // NO TOKEN needed for this request since user isn't authenticated yet
    const response = await api.put("/changepassword", passwordData, {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    });
    
    return response.data;
  } catch (error) {
    console.error("Change password error:", error);
    if (error.response && error.response.data) {
      throw error.response.data;
    } else if (error.request) {
      throw { message: "Network Error. Please check your connection." };
    } else {
      throw { message: error.message || "An error occurred" };
    }
  }
};