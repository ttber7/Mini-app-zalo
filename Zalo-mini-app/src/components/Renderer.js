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
export function renderSDUI(layoutConfig, container, globalConfig = {}) {
    container.innerHTML = ''; // Clear skeleton

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
                // ĐÃ BỎ case 'hero_header' ra khỏi vòng lặp này
                case 'banner':
                    // Đây là Banner ảnh thật sự (Kéo thả từ WP), không phải Header màu xanh
                    if (typeof Banner === 'function') {
                        element = Banner({ data: content, action: node.action });
                    }
                    break;
                case 'statistics_grid':
                    element = renderStatsGrid(content);
                    break;
                case 'official_channel':
                    element = renderOfficialChannel(content);
                    break;
                case 'latest_news':
                case 'article_list':
                    element = renderLatestNews(content);
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
}
