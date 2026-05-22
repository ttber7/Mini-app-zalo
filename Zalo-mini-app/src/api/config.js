// src/api/config.js

export const config = {
    // Chỉ lấy đến /wp-json thôi, vì trong Faq.js đã cộng thêm /miniapp/v1/ rồi
    API_BASE_URL: 'http://zalo-miniapp.local/wp-json',

    // Các Endpoints tĩnh (Dành cho các file khác nếu cần dùng)
    ENDPOINTS: {
        CONFIG: '/miniapp/v1/config',
        SUBMIT_REPORT: '/miniapp/v1/submit-report',
        NEWS: '/miniapp/v1/news',
        REPORT_STATUS: '/miniapp/v1/report-status'
    }
};