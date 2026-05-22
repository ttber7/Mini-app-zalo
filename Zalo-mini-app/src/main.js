import './style.css';
import { fetchUIConfig } from './api/index.js';
import { renderSkeletonLoader, renderErrorFallback } from './components/Skeleton.js';
import { renderSDUI } from './components/Renderer.js';
import { renderFaqUI } from './components/Faq.js';
import { renderNewsUI, renderNewsDetailUI } from './components/News.js';
import { renderReportUI } from './components/Report.js';
import { renderContactUI } from './components/Contact.js';
import { renderScheduleUI } from './components/Schedule.js';
import { openWebUrl } from './utils/zalo.js';

// [FIX 7]: Guard chống Double-run khi Vite HMR (Hot Reload)
if (window.__MINIAPP_INITIALIZED__) {
    console.warn('App already initialized. Skipping.');
} else {
    window.__MINIAPP_INITIALIZED__ = true;

    // [FIX 6]: Centralized App State (Nano Store Pattern)
    const appState = {
        layout: null,
        global: null,
        uiConfig: null,
        currentCleanupFn: null,
        isNavigating: false,
        controllers: {
            initFetch: null
        }
    };

    // [FIX 10]: Centralized Logger giả lập (Dành cho Sentry/Telemetry sau này)
    const logError = (error, context = 'General') => {
        console.error(`[ERROR][${context}]`, error);
        // Sau này có thể push API: fetch('/api/log', { body: error })
    };

    // Khởi tạo Zalo Mini App
    async function initApp() {
        const appContainer = document.getElementById('app');
        renderSkeletonLoader('app');

        // [FIX 8]: AbortController cho luồng Init
        if (appState.controllers.initFetch) {
            appState.controllers.initFetch.abort();
        }
        appState.controllers.initFetch = new AbortController();

        try {
            const uiConfig = await fetchUIConfig({ signal: appState.controllers.initFetch.signal });

            let pages = uiConfig.pages;
            let homePage = null;

            if (Array.isArray(pages)) {
                homePage = pages.find(p => p.id === 'home') || pages[0];
            } else if (typeof pages === 'object') {
                homePage = pages['home'] || Object.values(pages)[0];
            }

            if (homePage) {
                appState.uiConfig = uiConfig;
                appState.layout = homePage.page_components || homePage.components || homePage.layout || [];
                appState.global = uiConfig.global_config || {};

                // Set initial route state
                history.replaceState({ route: 'home' }, '', '?route=home');
                navigate('home', false); // Xử lý render qua hàm navigate
            } else {
                throw new Error('Không tìm thấy cấu hình trang chủ');
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                logError(error, 'InitApp');
                renderErrorFallback('app', error.message, initApp);
            }
        }
    }

    // [FIX 4 & 9]: Smart Router xử lý Route logic và State
    const navigate = (route, pushHistory = true) => {
        if (appState.isNavigating) return;
        appState.isNavigating = true;

        const appContainer = document.getElementById('app');

        // [FIX 5]: Animation mượt mà khi chuyển trang
        appContainer.style.opacity = '0';
        appContainer.style.transition = 'opacity 0.2s ease-in-out';

        setTimeout(() => {
            // [FIX 3]: Dọn dẹp DOM và Event Listeners của trang cũ
            if (typeof appState.currentCleanupFn === 'function') {
                appState.currentCleanupFn();
                appState.currentCleanupFn = null;
            }

            // Xóa HTML an toàn
            appContainer.innerHTML = '';

            // Render Route tương ứng
            try {
                if (route === 'home') {
                    // Giả định renderSDUI cũng trả về cleanup function
                    appState.currentCleanupFn = renderSDUI(appState.layout, appContainer, appState.global, appState.uiConfig);
                } else if (route === 'faq') {
                    appState.currentCleanupFn = renderFaqUI(appContainer);
                } else if (route === 'schedule') {
                    appState.currentCleanupFn = renderScheduleUI(appContainer, 'general');
                } else if (route === 'schedule_cskv') {
                    appState.currentCleanupFn = renderScheduleUI(appContainer, 'cskv');
                } else if (route === 'news') {
                    appState.currentCleanupFn = renderNewsUI(appContainer, (newsId) => {
                        navigate(`news-detail:${newsId}`, true);
                    });
                } else if (route.startsWith('news-detail:')) {
                    const newsId = route.split(':')[1];
                    appState.currentCleanupFn = renderNewsDetailUI(appContainer, newsId, () => {
                        navigate('news', true);
                    });
                } else if (route === 'report') {
                    appState.currentCleanupFn = renderReportUI(appContainer);
                } else if (route === 'contact') {
                    appState.currentCleanupFn = renderContactUI(appContainer, appState.global, appState.uiConfig);
                } else {
                    appContainer.innerHTML = `
                        <div class="flex flex-col items-center justify-center h-screen bg-gray-50">
                            <span class="text-5xl mb-4 animate-bounce">🚧</span>
                            <p class="text-gray-600 font-bold text-lg">Tính năng đang phát triển</p>
                        </div>
                    `;
                }
            } catch (err) {
                logError(err, 'Router Render');
                renderErrorFallback('app', 'Lỗi hiển thị giao diện', () => navigate('home'));
            }

            // Update History API
            if (pushHistory) {
                history.pushState({ route: route }, '', `?route=${route}`);
            }

            // Update UI Navigation Bar
            updateBottomNavUI(route);

            // Fade in trở lại
            requestAnimationFrame(() => {
                appContainer.style.opacity = '1';
                appState.isNavigating = false;
            });

        }, 200); // Đợi CSS Fade-out hoàn tất
    };

    // Tách riêng logic xử lý giao diện thanh Bottom Nav
    const updateBottomNavUI = (route) => {
        document.querySelectorAll('.nav-item').forEach(el => {
            // [FIX 1]: Check Guard chống crash nếu HTML đổi cấu trúc
            const icon = el.querySelector('span:first-child');
            const text = el.querySelector('span:nth-child(2)');
            const dot = el.querySelector('.active-dot');

            if (icon) {
                icon.classList.remove('text-blue-600', 'drop-shadow-sm');
                icon.classList.add('text-gray-300');
            }
            if (text) {
                text.classList.remove('text-blue-600');
                text.classList.add('text-gray-400');
            }
            if (dot) dot.remove();

            // Kích hoạt item hiện tại
            const activeRoute = route.startsWith('news-detail:') ? 'news' : route;
            if (el.dataset.route === activeRoute) {
                if (icon) {
                    icon.classList.remove('text-gray-300');
                    icon.classList.add('text-blue-600', 'drop-shadow-sm');
                }
                if (text) {
                    text.classList.remove('text-gray-400');
                    text.classList.add('text-blue-600');
                }
 
                 // [FIX 2]: Dùng appendChild thay vì innerHTML += để bảo toàn Event Listeners
                 const newDot = document.createElement('div');
                 newDot.className = 'active-dot w-1 h-1 bg-blue-600 rounded-full mt-1 animate-pulse';
                 el.appendChild(newDot);
             }
        });
    };

    // Lắng nghe sự kiện click Bottom Nav
    document.addEventListener('click', (e) => {
        const navItem = e.target.closest('.nav-item');
        if (!navItem) return;

        // [FIX 9]: Dùng data-route thay vì ID cứng
        const route = navItem.dataset.route;
        if (route) {
            navigate(route, true);
        }
    });

    // [FIX 4]: Lắng nghe sự kiện nút Back của hệ điều hành / trình duyệt
    window.addEventListener('popstate', (event) => {
        const route = event.state ? event.state.route : 'home';
        navigate(route, false); // false = không pushHistory nữa
    });

    // Lắng nghe sự kiện điều hướng tùy chỉnh toàn cục
    window.addEventListener('miniapp:navigate', (e) => {
        const route = e.detail?.route;
        if (route) {
            navigate(route, true);
        }
    });

    // Chạy App
    initApp();
}