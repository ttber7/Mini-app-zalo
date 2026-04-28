import './style.css';
import { fetchUIConfig } from './api/index.js';
import { renderSkeletonLoader, renderErrorFallback } from './components/Skeleton.js';
import { renderSDUI } from './components/Renderer.js';

// Khởi tạo Zalo Mini App
async function initApp() {
    const appContainer = document.getElementById('app');

    // 1. Hiển thị Skeleton Loader ngay lập tức
    renderSkeletonLoader('app');

    try {
        // 2. Tải cấu hình giao diện từ Server (Hoặc Cache)
        const uiConfig = await fetchUIConfig();

        // 3. Render giao diện dựa trên cấu hình
        let pages = uiConfig.pages;
        let homePage = null;

        if (Array.isArray(pages)) {
            homePage = pages.find(p => p.id === 'home') || pages[0];
        } else if (typeof pages === 'object') {
            homePage = pages['home'] || Object.values(pages)[0];
        }

        if (homePage) {
            const layoutConfig = homePage.page_components || homePage.components || homePage.layout || [];
            const globalConfig = uiConfig.global_config || {};
            // THÊM 2 DÒNG NÀY VÀO ĐỂ SOI DỮ LIỆU:
            console.log("👉 TẤT CẢ DỮ LIỆU TỪ WP GỬI VỀ:", uiConfig);
            console.log("👉 DỮ LIỆU TRANG CHỦ (Sắp đem đi vẽ):", layoutConfig);
            renderSDUI(layoutConfig, appContainer, globalConfig);
        } else {
            throw new Error('Không tìm thấy cấu hình trang chủ');
        }
    } catch (error) {
        renderErrorFallback('app', error.message, initApp);
    }
}

// Chạy App
initApp();
