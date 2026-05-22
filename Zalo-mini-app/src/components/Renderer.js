import {
    renderHeroHeader,
    renderStatsGrid,
    renderEmergencyList,
    renderGridMenu,
    renderBottomNav,
    renderOfficialChannel,
    renderLatestNews
} from './StaticComponents.js';
import { renderDynamicForm } from './DynamicForm.js';

/**
 * SDUI Renderer - Đọc JSON và sinh ra Component chuẩn Premium
 */
export function renderSDUI(layoutConfig, container, globalConfig = {}, uiConfig = {}) {
    container.innerHTML = ''; // Clear skeleton

    // Mảng chứa các hàm dọn dẹp cục bộ (nếu component con cần dọn dẹp riêng)
    const cleanupFns = [];

    // Truyền globalConfig để nó lấy Thời tiết từ Backend
    const headerElement = renderHeroHeader({}, globalConfig);
    if (headerElement) {
        headerElement.id = 'fixed-hero-header';
        container.appendChild(headerElement);
    }

    if (Array.isArray(layoutConfig)) {
        layoutConfig.forEach(node => {
            let element = null;
            const type = node._type || node.type;
            const content = node.content || node;

            switch (type) {
                case 'banner':
                    if (typeof Banner === 'function') {
                        element = Banner({ data: content, action: node.action });
                    }
                    break;
                case 'statistics_grid':
                    element = renderStatsGrid(content);
                    break;
                case 'official_channel':
                    element = renderOfficialChannel(content, globalConfig);
                    break;
                case 'latest_news':
                case 'article_list':
                    element = renderLatestNews(node, globalConfig, uiConfig?.data_sources);
                    break;
                case 'emergency_list':
                    element = renderEmergencyList(content);
                    break;
                case 'grid_menu':
                    element = renderGridMenu(content);
                    break;
                case 'form':
                    element = renderDynamicForm(content);
                    break;

                // [FIX 1]: Thêm case xử lý FAQ List từ Backend kéo thả
                case 'faq_list':
                    element = document.createElement('div');
                    element.className = 'px-4 my-6';
                    element.innerHTML = `
                        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 flex items-center justify-between shadow-sm cursor-pointer active:scale-95 transition-transform" id="sdui-faq-cta">
                            <div>
                                <h3 class="font-black text-blue-800 text-base mb-1">${content.title || 'Câu hỏi thường gặp'}</h3>
                                <p class="text-xs text-blue-600">${content.search_placeholder || 'Tra cứu và đặt câu hỏi cho cơ quan Công an'}</p>
                            </div>
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-blue-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path></svg>
                            </div>
                        </div>
                    `;

                    // Gắn sự kiện click để giả lập hành vi bấm vào Bottom Nav (kích hoạt Router)
                    const ctaBtn = element.querySelector('#sdui-faq-cta');
                    const ctaHandler = () => {
                        const navFaq = document.querySelector('.nav-item[data-route="faq"]');
                        if (navFaq) navFaq.click();
                    };
                    ctaBtn.addEventListener('click', ctaHandler);

                    // Lưu lại để gỡ Event Listener khi chuyển trang
                    cleanupFns.push(() => ctaBtn.removeEventListener('click', ctaHandler));
                    break;

                default:
                    console.warn('[SDUI] Component type chưa được hỗ trợ:', type);
            }

            if (element) {
                if (node.id) element.id = `sdui-comp-${node.id}`;
                container.appendChild(element);
            }
        });
    } else {
        console.warn('[SDUI] Layout config is empty or invalid');
    }

    // ==========================================
    // 3. CHÂN ĐẠP ĐẤT: LUÔN RENDER BOTTOM NAV CUỐI CÙNG
    // ==========================================
    const existingNav = document.getElementById('bottom-nav');
    if (existingNav) existingNav.remove();

    const bottomNav = renderBottomNav(globalConfig);
    bottomNav.id = 'bottom-nav';
    document.body.appendChild(bottomNav);

    // [FIX 2]: Trả về Lifecycle Cleanup Function cho Router trong main.js
    return () => {
        // Thực thi các lệnh dọn dẹp sự kiện cục bộ
        cleanupFns.forEach(fn => fn());

        // Xóa sạch DOM để tránh rò rỉ bộ nhớ
        container.innerHTML = '';

        // LƯU Ý: Không xóa Bottom Nav ở đây vì nó là Global Component
    };
}