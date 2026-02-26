// frontend/src/services/caseCategoryService.js
import api from "@/services/api"

class CaseCategoryService {
    async getAll() {
        try {
            console.log('Fetching categories from API...');
            const response = await api.get("/admin/case-categories");
            console.log('API Response:', response);
            
            // Handle different response formats
            if (response.data && response.data.data) {
                return response.data.data;
            } else if (Array.isArray(response.data)) {
                return response.data;
            } else {
                console.warn('Unexpected response format:', response.data);
                return [];
            }
        } catch (error) {
            console.error('Error fetching categories:', error);
            throw error;
        }
    }

    async create(data) {
        try {
            const response = await api.post("/admin/case-categories", {
                name: data.name,
                sort_order: parseInt(data.sort_order) || 0,
                is_active: data.is_active
            });
            return response.data.data || response.data;
        } catch (error) {
            console.error('Error creating category:', error);
            throw error;
        }
    }

    async update(id, data) {
        try {
            const response = await api.put(`/admin/case-categories/${id}`, {
                name: data.name,
                sort_order: parseInt(data.sort_order) || 0,
                is_active: data.is_active
            });
            return response.data.data || response.data;
        } catch (error) {
            console.error('Error updating category:', error);
            throw error;
        }
    }

    async toggle(id) {
        try {
            const response = await api.patch(`/admin/case-categories/${id}/toggle`);
            return response.data.data || response.data;
        } catch (error) {
            console.error('Error toggling category:', error);
            throw error;
        }
    }
}

export default new CaseCategoryService();