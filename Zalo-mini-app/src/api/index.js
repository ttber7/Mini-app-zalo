import { API_BASE_URL, API_SECRET_KEY, ENDPOINTS } from './config.js';

/**
 * Fetch cấu hình UI từ Backend
 * @returns {Promise<Object>}
 */
export async function fetchUIConfig() {
    // 1. Kiểm tra Cache trong LocalStorage
    const cachedData = localStorage.getItem('sdui_config_cache');
    if (cachedData) {
        try {
            const parsed = JSON.parse(cachedData);
            console.log('Loaded UI config from cache');
            // Fetch ngầm để cập nhật cache
            fetchAndCacheConfig();
            return parsed;
        } catch (e) {
            console.error('Lỗi parse cache', e);
        }
    }

    // 2. Nếu chưa có Cache, fetch trực tiếp
    return await fetchAndCacheConfig();
}

/**
 * Lấy config từ server và lưu vào cache
 */
async function fetchAndCacheConfig() {
    try {
        const response = await fetch(`${API_BASE_URL}${ENDPOINTS.CONFIG}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        
        // Lưu Cache
        localStorage.setItem('sdui_config_cache', JSON.stringify(data));
        
        return data;
    } catch (error) {
        console.error('Fetch UI Config Failed:', error);
        throw error;
    }
}

/**
 * Gửi phản ánh (Submit Report)
 * @param {Object} payload 
 */
export async function submitReport(payload) {
    try {
        const response = await fetch(`${API_BASE_URL}${ENDPOINTS.SUBMIT_REPORT}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-MiniApp-Key': API_SECRET_KEY // Header Bảo mật
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Lỗi hệ thống');
        }

        return data;
    } catch (error) {
        console.error('Submit Report Failed:', error);
        throw error;
    }
}
