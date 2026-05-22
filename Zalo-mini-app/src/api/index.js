// import { API_BASE_URL, API_SECRET_KEY, ENDPOINTS } from './config.js';

// Thay vì import lẻ tẻ:
// import { API_BASE_URL, API_SECRET_KEY } from './config.js';

// Hãy import object config:
import { config } from './config.js';

// Sau đó ở các hàm bên dưới, thay vì dùng API_BASE_URL trực tiếp, bạn dùng config.API_BASE_URL
// Ví dụ:
// async function fetchAndCacheConfig() {
//     const res = await fetch(`${config.API_BASE_URL}${config.ENDPOINTS.CONFIG}`);
//     // ...
// }

/**
 * Fetch cấu hình UI từ Backend
 * @returns {Promise<Object>}
 */
export async function fetchUIConfig() {
    // 1. Kiểm tra Cache trong LocalStorage
    // Bỏ qua cache nếu đang ở localhost/127.0.0.1 để tránh dữ liệu cũ trong phát triển
    const isDev = window.location.hostname === 'localhost' || 
                  window.location.hostname === '127.0.0.1' ||
                  window.location.hostname.includes('local') ||
                  config.API_BASE_URL.includes('local') ||
                  config.API_BASE_URL.includes('loca.lt');
    if (!isDev) {
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
    } else {
        console.log('Development environment: Bypassing local storage cache to avoid stale data.');
    }

    // 2. Nếu chưa có Cache, fetch trực tiếp
    return await fetchAndCacheConfig();
}

/**
 * Lấy config từ server và lưu vào cache
 */
async function fetchAndCacheConfig() {
    try {
        const response = await fetch(`${config.API_BASE_URL}${config.ENDPOINTS.CONFIG}`);
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
        const response = await fetch(`${config.API_BASE_URL}${config.ENDPOINTS.SUBMIT_REPORT}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
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
