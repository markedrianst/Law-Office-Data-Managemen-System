import api from "./api"; // your existing axios instance

export const login = async (credentials) => {
  try {
    const response = await api.post("/login", credentials);
    return response.data;
  } catch (error) {
    if (error.response) {
      throw error.response.data;
    } else {
      throw { message: "Network Error" };
    }
  }
};

export const logout = async () => {
  try {
    await api.post("/logout");
  } catch (error) {
    console.error("Logout error:", error);
  }
};

export const getUser = async () => {
  const response = await api.get("/user");
  return response.data;
};
