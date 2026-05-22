import { navigate, openWebUrl, callPhone } from '../utils/zalo.js';
import { config } from '../api/config.js';

/**
 * 1. HERO HEADER (Phần trên cùng màu xanh đậm gradient)
 */
export function renderHeroHeader(data, globalConfig = {}) {
    const header = document.createElement('div');
    header.className = 'relative w-full pt-12 pb-24 px-4 bg-gradient-to-br from-[#1e3a8a] to-[#2D58D7] overflow-hidden flex flex-col items-center';

    const appName = globalConfig.app_name || globalConfig.app_title || 'CÔNG AN XÃ CẦN ĐƯỚC';
    const logoUrl = globalConfig.logo_url || 'https://upload.wikimedia.org/wikipedia/commons/a/a1/Logo_Huy_hiệu_Công_an_nhân_dân_Việt_Nam.png';
    const temp = globalConfig.weather?.temp || '25.0°C';
    const weatherDesc = globalConfig.weather?.desc || 'mây rải rác';

    const phone = globalConfig.station_phone || '0272.3881.213';

    const pattern = document.createElement('div');
    pattern.className = 'absolute inset-0 opacity-20 pointer-events-none z-0';
    pattern.innerHTML = `
        <div class="absolute inset-0 bg-[url('https://upload.wikimedia.org/wikipedia/commons/thumb/a/a1/Logo_Huy_hi%E1%BB%87u_C%C3%B4ng_an_nh%C3%A2n_d%C3%A2n_Vi%E1%BB%87t_Nam.png/600px-Logo_Huy_hi%E1%BB%87u_C%C3%B4ng_an_nh%C3%A2n_d%C3%A2n_Vi%E1%BB%87t_Nam.png')] bg-center bg-no-repeat bg-contain scale-150 grayscale brightness-200 opacity-10"></div>
    `;
    header.appendChild(pattern);

    header.innerHTML += `
        <div class="relative z-10 flex flex-col items-center text-center text-white mb-8">
            <div class="w-20 h-20 bg-white/10 backdrop-blur-md p-2 rounded-3xl mb-4 border border-white/20 shadow-2xl">
                <img src="${logoUrl}" class="w-full h-full object-contain" />
            </div>
            <h1 class="text-[18px] font-black uppercase tracking-[2px] leading-tight drop-shadow-md">${appName}</h1>
            <p class="text-[14px] font-bold opacity-80 mt-1 uppercase tracking-widest">Huyện Cần Đước - Long An</p>
        </div>

        <div class="relative z-10 grid grid-cols-2 gap-4 w-full px-2 mb-[-60px]">
            <div id="hero-news-btn" class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-4 flex items-center space-x-3 shadow-xl cursor-pointer active:scale-95 transition">
                <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center text-white shadow-lg text-lg">📄</div>
                <div class="flex flex-col">
                    <span class="text-white font-bold text-[13px]">Tin tức nổi bật</span>
                    <span class="text-white/70 text-[10px]">Cập nhật mới nhất</span>
                </div>
            </div>
            <div id="hero-hotline-btn" class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-4 flex items-center space-x-3 shadow-xl cursor-pointer active:scale-95 transition">
                <div class="w-10 h-10 bg-red-700 rounded-full flex items-center justify-center text-white shadow-lg text-lg">📞</div>
                <div class="flex flex-col">
                    <span class="text-white font-bold text-[13px]">Đường dây nóng</span>
                    <span class="text-white/80 text-[11px] font-mono">${phone}</span>
                </div>
            </div>
        </div>
        
        <div class="relative z-20 mt-20 w-full px-2">
            <div class="bg-gradient-to-br from-[#1E40AF] to-[#3B82F6] rounded-[32px] p-6 shadow-2xl border border-white/20 text-white">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-white/20 rounded-full p-2 backdrop-blur-md shadow-inner">
                            <img src="${logoUrl}" class="w-full h-full object-contain" />
                        </div>
                        <div>
                            <h2 class="text-[17px] font-black leading-tight">${appName}</h2>
                            <p class="text-[11px] opacity-90 font-medium">Vì nhân dân phục vụ</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-[11px] font-bold opacity-80 uppercase">${new Date().toLocaleDateString('vi-VN', { weekday: 'long' })}</p>
                        <p class="text-[11px] font-bold">${new Date().toLocaleDateString('vi-VN')}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-2xl p-4 flex items-center space-x-4">
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-xl shadow-inner">👤</div>
                        <div>
                            <p class="text-[14px] font-bold">Xin chào, bạn!</p>
                            <p class="text-[11px] opacity-80">Chào mừng bạn đến với ứng dụng</p>
                        </div>
                    </div>
                    
                    <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-2xl p-4 flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-xl shadow-inner">📍</div>
                            <div>
                                <p class="text-[14px] font-bold">Huyện Cần Đước, Long An</p>
                                <p class="text-[11px] opacity-80">${weatherDesc}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="text-2xl font-black">${temp}</span>
                            <span class="text-3xl animate-bounce-slow">☁️</span>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-center space-x-2 bg-black/10 py-2 rounded-xl">
                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse shadow-[0_0_8px_rgba(74,222,128,0.8)]"></div>
                    <span class="text-[10px] font-bold uppercase tracking-widest opacity-90">Hệ thống hoạt động bình thường</span>
                </div>
            </div>
        </div>
    `;

    // Gắn event listeners trực tiếp vào DOM của header
    const newsBtn = header.querySelector('#hero-news-btn');
    if (newsBtn) {
        newsBtn.addEventListener('click', () => {
            const navNews = document.querySelector('.nav-item[data-route="news"]');
            if (navNews) navNews.click();
            else navigate('news');
        });
    }

    const hotlineBtn = header.querySelector('#hero-hotline-btn');
    if (hotlineBtn) {
        hotlineBtn.addEventListener('click', () => {
            callPhone(phone.replace(/[^0-9+]/g, ''));
        });
    }

    return header;
}

/**
 * 2. STATISTICS GRID (4 ô màu rực rỡ)
 */
export function renderStatsGrid(data) {
    const container = document.createElement('div');
    container.className = 'mt-12 px-4';

    const pop = data?.population || '38.674';
    const house = data?.households || '11.524';
    const area = data?.area || '18,64';
    const party = data?.party_members || '1794';
    const subtitle = data?.update_time || 'Số liệu cập nhật tháng 4/2026';

    container.innerHTML = `
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
            <h3 class="text-[18px] font-black text-gray-800 uppercase tracking-tight">Thống kê xã</h3>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-blue-500 rounded-3xl p-5 text-white shadow-lg shadow-blue-200 relative overflow-hidden group">
                <div class="relative z-10">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-[11px] font-bold opacity-80 uppercase">Người dân</span>
                        <span class="text-xl">👤</span>
                    </div>
                    <div class="text-[24px] font-black tracking-tighter mb-1">${pop}</div>
                    <div class="text-[11px] font-bold opacity-80">Dân số</div>
                </div>
            </div>
            <div class="bg-emerald-500 rounded-3xl p-5 text-white shadow-lg shadow-emerald-200 relative overflow-hidden group">
                <div class="relative z-10">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-[11px] font-bold opacity-80 uppercase">Hộ dân</span>
                        <span class="text-xl">🏠</span>
                    </div>
                    <div class="text-[24px] font-black tracking-tighter mb-1">${house}</div>
                    <div class="text-[11px] font-bold opacity-80">Hộ gia đình</div>
                </div>
            </div>
            <div class="bg-sky-500 rounded-3xl p-5 text-white shadow-lg shadow-sky-200 relative overflow-hidden group">
                <div class="relative z-10">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-[11px] font-bold opacity-80 uppercase">Diện tích</span>
                        <span class="text-xl">📍</span>
                    </div>
                    <div class="text-[24px] font-black tracking-tighter mb-1">${area} km2</div>
                    <div class="text-[11px] font-bold opacity-80">Diện tích</div>
                </div>
            </div>
            <div class="bg-rose-500 rounded-3xl p-5 text-white shadow-lg shadow-rose-200 relative overflow-hidden group">
                <div class="relative z-10">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-[11px] font-bold opacity-80 uppercase">Đảng viên</span>
                        <span class="text-xl">❤️</span>
                    </div>
                    <div class="text-[24px] font-black tracking-tighter mb-1">${party}</div>
                    <div class="text-[11px] font-bold opacity-80">Đảng viên</div>
                </div>
            </div>
        </div>
        <div class="mt-6 bg-blue-50/50 py-3 rounded-2xl text-center">
            <span class="text-[11px] font-bold text-blue-400 italic">${subtitle}</span>
        </div>
    `;
    return container;
}

/**
 * 3. GRID MENU (Các tiện ích chính)
 */
export function renderGridMenu(data) {
    const items = data?.items || [];
    const container = document.createElement('div');
    container.className = 'mt-12 px-4';

    container.innerHTML = `
        <div class="flex items-center space-x-3 mb-8">
            <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
            <h3 class="text-[18px] font-black text-gray-800 uppercase tracking-tight">Tiện ích chính</h3>
        </div>
        <div class="grid grid-cols-4 gap-x-2 gap-y-10">
            ${items.map((item, index) => `
                <div data-index="${index}" class="grid-menu-item flex flex-col items-center group cursor-pointer active:scale-90 transition-all">
                    <div class="w-[68px] h-[68px] bg-blue-50 rounded-[24px] flex items-center justify-center text-[32px] shadow-sm mb-3 border border-blue-100/50 group-hover:bg-blue-100 transition-colors">
                        ${item.icon || '📌'}
                    </div>
                    <span class="text-[11px] font-bold text-gray-700 leading-[1.3] text-center px-1 h-8 flex items-center">${item.label}</span>
                </div>
            `).join('')}
        </div>
    `;

    // [FIX 3]: Bắt sự kiện điều hướng cho Grid Menu
    container.querySelectorAll('.grid-menu-item').forEach(el => {
        el.addEventListener('click', () => {
            const idx = el.dataset.index;
            const item = items[idx];
            if (!item) return;

            if (item.action_type === 'navigate') {
                // Kích hoạt giả lập click vào Bottom Nav tương ứng nếu là trang nội bộ
                const navFaq = document.querySelector(`.nav-item[data-route="${item.action_value}"]`);
                if (navFaq) navFaq.click();
                else if (typeof navigate === 'function') navigate(item.action_value);
            } else if (item.action_type === 'call') {
                if (typeof callPhone === 'function') callPhone(item.action_value);
                else window.location.href = `tel:${item.action_value}`;
            } else if (item.action_type === 'open_url') {
                if (typeof openWebUrl === 'function') openWebUrl(item.action_value);
                else window.open(item.action_value, '_blank');
            }
        });
    });

    return container;
}

/**
 * 4. OFFICIAL CHANNEL (Kênh chính thức)
 */
export function renderOfficialChannel(data, globalConfig = {}) {
    const container = document.createElement('div');
    container.className = 'mt-12 px-4';
    const oaId = globalConfig?.oa_id || data?.oa_id || '502931508216399126';
    const logoUrl = globalConfig?.logo_url || data?.logo_url || 'https://upload.wikimedia.org/wikipedia/commons/a/a1/Logo_Huy_hiệu_Công_an_nhân_dân_Việt_Nam.png';
    const appName = globalConfig?.app_name || globalConfig?.app_title || data?.title || 'Công an xã Cần Đước';

    container.innerHTML = `
        <div class="bg-white rounded-[32px] shadow-2xl border border-white/50 overflow-hidden premium-shadow">
            <div class="bg-[#2D58D7] p-4 text-white flex items-center space-x-2">
                <span class="text-lg">⭐</span>
                <span class="font-black text-[15px] uppercase tracking-wide">Kênh chính thức</span>
            </div>
            <div class="p-6">
                <div id="oa-card-header" class="flex items-center justify-between mb-6 bg-gray-50 p-4 rounded-2xl border border-gray-100 cursor-pointer active:scale-[0.98] transition-transform">
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 rounded-full overflow-hidden border-2 border-white shadow-xl bg-white p-1">
                            <img src="${logoUrl}" class="w-full h-full object-contain" />
                        </div>
                        <div>
                            <h4 class="text-[15px] font-black text-gray-900 leading-tight">${appName}</h4>
                            <div class="flex items-center mt-1 text-[10px] text-blue-600 font-black uppercase">
                                <span class="w-3 h-3 bg-blue-600 text-white rounded-full flex items-center justify-center text-[7px] mr-1">✓</span>
                                Kênh chính thức
                            </div>
                        </div>
                    </div>
                    <button id="oa-quan-tam-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-full text-[12px] font-black shadow-lg shadow-blue-200 transition active:scale-95 animate-pulse">
                        Quan tâm
                    </button>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-blue-50 border border-blue-100 p-4 rounded-2xl flex items-center space-x-3">
                        <span class="text-xl">🔔</span>
                        <span class="text-[11px] font-black text-blue-800 uppercase tracking-tighter">Thông báo nhanh</span>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex items-center space-x-3">
                        <span class="text-xl">📅</span>
                        <span class="text-[11px] font-black text-emerald-800 uppercase tracking-tighter">Cập nhật 24/7</span>
                    </div>
                </div>

                <div class="rounded-3xl overflow-hidden shadow-inner relative group cursor-pointer active:scale-95 transition-transform" onclick="if(typeof openWebUrl==='function') openWebUrl('https://phapluat.sotuphap.longan.gov.vn/')">
                    <img src="${data?.cover_image || 'https://images.unsplash.com/photo-1450133064473-71024230f91b?auto=format&fit=crop&w=800&q=80'}" class="w-full h-40 object-cover group-hover:scale-110 transition-transform duration-700" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    <div class="absolute bottom-4 left-6 right-6">
                        <p class="text-white text-[12px] font-black uppercase drop-shadow-md">Sổ tay tra cứu pháp luật</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Gắn sự kiện click vào nút Quan tâm để mở trực tiếp Zalo OA
    const btn = container.querySelector('#oa-quan-tam-btn');
    if (btn) {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (typeof openWebUrl === 'function') {
                openWebUrl(`https://zalo.me/${oaId}`);
            } else {
                window.open(`https://zalo.me/${oaId}`, '_blank');
            }
        });
    }

    // Gắn sự kiện click vào phần header card thông tin OA để mở trực tiếp Zalo OA
    const headerCard = container.querySelector('#oa-card-header');
    if (headerCard) {
        headerCard.addEventListener('click', () => {
            if (typeof openWebUrl === 'function') {
                openWebUrl(`https://zalo.me/${oaId}`);
            } else {
                window.open(`https://zalo.me/${oaId}`, '_blank');
            }
        });
    }

    return container;
}

/**
 * 5. EMERGENCY CONTACTS
 */
export function renderEmergencyList(data) {
    const container = document.createElement('div');
    container.className = 'mt-12 px-4 pb-12';
    const hotlines = data?.hotlines || [];

    // [FIX 2]: Map đúng key từ CPT Carbon Fields & Inline CSS cho màu
    container.innerHTML = `
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-1 h-6 bg-red-600 rounded-full"></div>
            <h3 class="text-[18px] font-black text-gray-800 uppercase tracking-tight">Liên hệ khẩn cấp</h3>
        </div>
        <div class="space-y-4">
            ${hotlines.map(h => `
                <div style="background-color: ${h.bg_color || '#ef4444'}" class="rounded-[24px] p-5 text-white shadow-xl flex items-center justify-between relative overflow-hidden group active:scale-95 transition-all">
                    <div class="flex items-center space-x-4 relative z-10">
                        <div class="text-3xl">${h.icon || '🚨'}</div>
                        <div>
                            <h4 class="text-[15px] font-black uppercase leading-tight">${h.label}</h4>
                            <p class="text-[11px] opacity-80 font-bold">${h.sub_label || ''}</p>
                        </div>
                    </div>
                    <div class="text-right relative z-10">
                        <p class="text-[18px] font-black tracking-tight mb-1 font-mono">${h.phone}</p>
                        <button onclick="${typeof callPhone === 'function' ? `callPhone('${h.phone}')` : `window.location.href='tel:${h.phone}'`}" class="bg-white/20 backdrop-blur-md px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border border-white/30">Gọi ngay</button>
                    </div>
                    <div class="absolute right-[-10%] top-[-20%] w-32 h-32 bg-white/10 rounded-full group-hover:scale-125 transition-transform duration-700"></div>
                </div>
            `).join('')}
        </div>
        <div class="mt-6 bg-red-50 border border-red-100 p-4 rounded-2xl flex items-center justify-center space-x-3">
            <span class="text-red-500 text-lg">⚠️</span>
            <span class="text-[11px] font-black text-red-700 uppercase tracking-tighter">Chỉ sử dụng cho các trường hợp khẩn cấp thực sự</span>
        </div>
    `;
    return container;
}

/**
 * 6. LATEST NEWS
 */
export function renderLatestNews(node, globalConfig = {}, dataSources = {}) {
    const container = document.createElement('div');
    container.className = 'mt-12 px-4';

    const title = node?.title || node?.content?.title || 'Thông tin mới nhất';
    const dataSourceKey = node?.data_source?.key || node?.content?.data_source?.key || 'news_api';
    const params = node?.data_source?.params || node?.content?.data_source?.params || {};

    container.innerHTML = `
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-3">
                <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
                <h3 class="text-[18px] font-black text-gray-800 uppercase tracking-tight">${title}</h3>
            </div>
            <span class="text-[11px] font-black text-blue-600 uppercase cursor-pointer hover:underline" data-route="news">Xem tất cả</span>
        </div>
        <div id="latest-news-list" class="space-y-4">
            <div class="text-center py-6"><div class="w-6 h-6 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div></div>
        </div>
    `;

    // Hook 'Xem tất cả' vào Router
    const viewAllBtn = container.querySelector('[data-route="news"]');
    if (viewAllBtn) {
        viewAllBtn.addEventListener('click', () => {
            const navNews = document.querySelector('.nav-item[data-route="news"]');
            if (navNews) navNews.click();
        });
    }

    // Resolve API URL
    const dataSource = dataSources && dataSources[dataSourceKey];
    let apiEndpoint = '';
    if (dataSource && dataSource.api) {
        const apiPath = dataSource.api;
        if (apiPath.startsWith('http://') || apiPath.startsWith('https://')) {
            apiEndpoint = apiPath;
        } else if (apiPath.startsWith('/wp-json')) {
            const origin = config.API_BASE_URL.split('/wp-json')[0];
            apiEndpoint = `${origin}${apiPath}`;
        } else {
            const separator = apiPath.startsWith('/') ? '' : '/';
            apiEndpoint = `${config.API_BASE_URL}${separator}${apiPath}`;
        }
    } else {
        apiEndpoint = `${config.API_BASE_URL}/miniapp/v1/news`;
    }

    const finalParams = { limit: 3, ...params };
    let fetchUrl = apiEndpoint;
    const queryParams = [];
    for (const [k, v] of Object.entries(finalParams)) {
        queryParams.push(`${encodeURIComponent(k)}=${encodeURIComponent(v)}`);
    }
    if (queryParams.length > 0) {
        const separator = fetchUrl.includes('?') ? '&' : '?';
        fetchUrl += separator + queryParams.join('&');
    }

    // Fetch dynamic content
    (async () => {
        const listDiv = container.querySelector('#latest-news-list');
        if (!listDiv) return;
        try {
            const res = await fetch(fetchUrl);
            if (!res.ok) throw new Error('API Error');
            const result = await res.json();
            const items = result.data || [];

            if (!listDiv.isConnected) return; // Prevent memory leak / write to detached DOM

            if (items.length > 0) {
                listDiv.innerHTML = items.map(item => {
                    const imgHtml = item.image_url 
                        ? `<img src="${item.image_url}" class="w-16 h-16 object-cover rounded-xl shadow-md" />`
                        : `<div class="w-16 h-16 bg-blue-50 rounded-xl flex items-center justify-center text-2xl">📰</div>`;

                    return `
                        <div class="latest-news-item bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex space-x-4 cursor-pointer active:scale-98 transition-transform duration-200" data-id="${item.id}">
                            <div class="flex-1 flex flex-col justify-between">
                                <h4 class="text-xs font-bold text-gray-800 line-clamp-2 leading-snug mb-1">${item.title}</h4>
                                <span class="text-[9px] font-black text-blue-600 uppercase tracking-widest mt-1">${item.date}</span>
                            </div>
                            ${imgHtml}
                        </div>
                    `;
                }).join('');

                listDiv.querySelectorAll('.latest-news-item').forEach(el => {
                    el.addEventListener('click', () => {
                        const newsId = el.dataset.id;
                        window.dispatchEvent(new CustomEvent('miniapp:navigate', { detail: { route: `news-detail:${newsId}` } }));
                    });
                });
            } else {
                listDiv.innerHTML = `
                    <div class="bg-white rounded-[32px] p-6 border border-gray-100 shadow-xl flex flex-col items-center text-center">
                        <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center text-3xl mb-4">📰</div>
                        <p class="text-[13px] font-bold text-gray-600 italic">Chưa có bài viết mới nào được cập nhật.</p>
                    </div>
                `;
            }
        } catch (err) {
            console.error('Fetch latest news error:', err);
            if (listDiv.isConnected) {
                listDiv.innerHTML = '<p class="text-center text-red-500 text-[11px] py-4">Không thể tải tin tức.</p>';
            }
        }
    })();

    return container;
}

/**
 * 7. BOTTOM NAVIGATION
 */
export function renderBottomNav(globalConfig) {
    const nav = document.createElement('div');
    nav.className = 'fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[480px] bg-white/95 backdrop-blur-2xl border-t border-gray-100 px-8 py-5 flex justify-between items-center z-[100] rounded-t-[32px] shadow-[0_-10px_40px_rgba(0,0,0,0.08)]';

    // [FIX 1]: Đổi id thành data-route để tương thích Router main.js
    const items = [
        { route: 'home', label: 'Trang chủ', icon: '🏠', active: true },
        { route: 'faq', label: 'Hỏi đáp', icon: '💬' },
        { route: 'report', label: 'Phản ánh', icon: '📝' },
        { route: 'news', label: 'Tin tức', icon: '📰' },
        { route: 'contact', label: 'Liên hệ', icon: '📞' }
    ];

    nav.innerHTML = items.map(item => `
        <div data-route="${item.route}" class="nav-item flex flex-col items-center group cursor-pointer active:scale-90 transition-all">
            <span class="text-[26px] mb-1.5 transition-transform group-hover:-translate-y-1 ${item.active ? 'text-blue-600 drop-shadow-sm' : 'text-gray-300'}">${item.icon}</span>
            <span class="text-[10px] font-black uppercase tracking-widest ${item.active ? 'text-blue-600' : 'text-gray-400'}">${item.label}</span>
            ${item.active ? '<div class="active-dot w-1 h-1 bg-blue-600 rounded-full mt-1 animate-pulse"></div>' : ''}
        </div>
    `).join('');

    return nav;
}