import { callPhone, openWebUrl } from '../utils/zalo.js';
import { config } from '../api/config.js';

export function renderContactUI(container, globalConfig = {}, uiConfig = {}) {
    container.innerHTML = '';

    const header = document.createElement('div');
    header.className = 'w-full pt-10 pb-6 px-4 bg-gradient-to-b from-[#1E40AF] to-white/0 flex flex-col items-center';
    header.innerHTML = `
        <h2 class="text-2xl font-black text-white drop-shadow-md mb-2">Liên hệ & Hotline</h2>
        <p class="text-xs text-white/80 uppercase tracking-widest font-bold">Kênh liên hệ khẩn cấp trực tiếp</p>
    `;
    container.appendChild(header);

    const contentWrapper = document.createElement('div');
    contentWrapper.className = 'px-4 pb-28 space-y-6';
    
    // Phần 1: Địa chỉ Cơ quan (Lấy từ globalConfig)
    const stationName = globalConfig.app_name || 'CÔNG AN XÃ CẦN ĐƯỚC';
    const stationAddress = globalConfig.station_address || '12 Đường Quốc Lộ 50, Thị trấn Cần Đước, Huyện Cần Đước, Long An';
    const stationPhone = globalConfig.station_phone || '0272.3881.213';
    const stationMapUrl = globalConfig.station_map_url || 'https://maps.google.com/?q=Cong+an+huyen+Can+Duoc+Long+An';

    const addressCard = document.createElement('div');
    addressCard.className = 'bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-4';
    addressCard.innerHTML = `
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">🏢</div>
            <div>
                <h4 class="text-sm font-black text-gray-900 uppercase">${stationName}</h4>
                <p class="text-xs text-gray-500 font-medium mt-0.5">Thành viên hệ thống Công an nhân dân</p>
            </div>
        </div>
        <div class="space-y-3 pt-2 text-xs text-gray-600 font-medium">
            <div class="flex items-start space-x-2">
                <span>📍</span>
                <span>Địa chỉ: ${stationAddress}</span>
            </div>
            <div class="flex items-start space-x-2">
                <span>📞</span>
                <span>Số điện thoại trực ban: <a href="tel:${stationPhone.replace(/\./g, '')}" class="text-blue-600 font-bold hover:underline">${stationPhone}</a></span>
            </div>
        </div>
        <button id="btn-open-map" class="w-full bg-blue-50 text-blue-600 rounded-xl py-3 text-xs font-black uppercase hover:bg-blue-100 transition active:scale-98">Chỉ đường qua Bản đồ</button>
    `;
    contentWrapper.appendChild(addressCard);

    // Phần 2: Hotline Khẩn cấp (Tìm component contact_emergency từ uiConfig)
    let hotlines = [];
    
    // Thử lấy từ uiConfig.pages.contact.layout (dạng API object) hoặc uiConfig.pages.contact.page_components (dạng thô)
    const contactPage = uiConfig.pages?.['contact'] || {};
    const layout = contactPage.layout || contactPage.page_components || [];
    const emergencyComp = layout.find(c => c.id === 'contact_emergency' || c.type === 'emergency_list');
    
    if (emergencyComp && emergencyComp.content?.hotlines) {
        hotlines = emergencyComp.content.hotlines;
    } else if (emergencyComp && emergencyComp.hotlines) {
        hotlines = emergencyComp.hotlines;
    }

    // Fallback nếu không có config
    if (!hotlines || hotlines.length === 0) {
        hotlines = [
            { label: 'Trực ban Công an', sub_label: 'Trực ban Công an', phone: stationPhone, bg_color: '#ef4444' },
            { label: 'Cảnh sát 113', sub_label: 'Cảnh sát 113', phone: '113', bg_color: '#2563eb' },
            { label: 'Cứu hỏa 114', sub_label: 'Cứu hóa 114', phone: '114', bg_color: '#f97316' },
            { label: 'Cấp cứu 115', sub_label: 'Cấp cứu 115', phone: '115', bg_color: '#10b981' }
        ];
    }

    const hotlinesCard = document.createElement('div');
    hotlinesCard.className = 'space-y-3';
    
    // Map icons cho hotline
    const getIconForHotline = (label) => {
        const lbl = label.toLowerCase();
        if (lbl.includes('cứu hỏa') || lbl.includes('pccc') || lbl.includes('114')) return '🔥';
        if (lbl.includes('cảnh sát') || lbl.includes('113')) return '🚔';
        if (lbl.includes('cấp cứu') || lbl.includes('y tế') || lbl.includes('115')) return '🚑';
        if (lbl.includes('điện') || lbl.includes('sự cố')) return '⚡';
        return '🚨';
    };

    hotlinesCard.innerHTML = `
        <div class="flex items-center space-x-3 mb-4">
            <div class="w-1 h-6 bg-red-600 rounded-full"></div>
            <h3 class="text-sm font-black text-gray-800 uppercase tracking-tight">Số điện thoại khẩn cấp</h3>
        </div>
        <div class="grid grid-cols-2 gap-4">
            ${hotlines.map((h, idx) => `
                <div style="background-color: ${h.bg_color || '#ef4444'}" class="rounded-2xl p-4 text-white shadow-lg cursor-pointer active:scale-95 transition-transform" id="hotline-btn-${idx}">
                    <div class="text-2xl mb-2">${getIconForHotline(h.label)}</div>
                    <div class="text-xs font-bold opacity-80">${h.label}</div>
                    <div class="text-base font-black tracking-tight mt-1 font-mono">${h.phone}</div>
                </div>
            `).join('')}
        </div>
    `;
    contentWrapper.appendChild(hotlinesCard);

    // Phần 3: Danh sách Cán bộ CSKV (Fetch từ API /miniapp/v1/officers)
    const officersSection = document.createElement('div');
    officersSection.className = 'space-y-4';
    officersSection.innerHTML = `
        <div class="flex items-center space-x-3 mb-4">
            <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
            <h3 class="text-sm font-black text-gray-800 uppercase tracking-tight">Cán bộ phụ trách khu vực</h3>
        </div>
        <div id="officers-list-container" class="space-y-3">
            <div class="text-center py-6"><div class="w-6 h-6 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div></div>
        </div>
    `;
    contentWrapper.appendChild(officersSection);

    container.appendChild(contentWrapper);

    // Gắn Event Listeners cho Map
    container.querySelector('#btn-open-map').addEventListener('click', () => {
        openWebUrl(stationMapUrl);
    });

    // Gắn Event Listeners cho Hotlines
    hotlines.forEach((h, idx) => {
        const btn = container.querySelector(`#hotline-btn-${idx}`);
        if (btn) {
            btn.addEventListener('click', () => callPhone(h.phone));
        }
    });

    // Fetch cán bộ thực tế
    let isMounted = true;
    (async () => {
        const officersList = container.querySelector('#officers-list-container');
        if (!officersList) return;
        try {
            const res = await fetch(`${config.API_BASE_URL}/miniapp/v1/officers`);
            if (!res.ok) throw new Error('API Error');
            const result = await res.json();
            const officers = result.data || [];

            if (!isMounted) return;

            if (officers.length > 0) {
                officersList.innerHTML = officers.map((o, idx) => {
                    const initials = o.name ? o.name.split(' ').pop().substring(0, 2).toUpperCase() : 'CB';
                    const avatarHtml = o.image_url 
                        ? `<img src="${o.image_url}" class="w-10 h-10 object-cover rounded-full border-2 border-white shadow" />`
                        : `<div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-xs font-bold text-blue-600">${initials}</div>`;

                    return `
                        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                ${avatarHtml}
                                <div>
                                    <h4 class="text-xs font-black text-gray-800 uppercase">${o.name}</h4>
                                    <p class="text-[10px] text-gray-400 font-medium">${o.area || 'Cán bộ phụ trách khu vực'}</p>
                                </div>
                            </div>
                            <button class="bg-blue-50 text-blue-600 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-wider active:scale-95 transition-transform" id="btn-call-officer-${idx}">Gọi</button>
                        </div>
                    `;
                }).join('');

                // Gắn Event Listeners gọi điện cho cán bộ
                officers.forEach((o, idx) => {
                    const btn = container.querySelector(`#btn-call-officer-${idx}`);
                    if (btn) {
                        btn.addEventListener('click', () => callPhone(o.phone));
                    }
                });
            } else {
                officersList.innerHTML = `
                    <div class="bg-gray-50 rounded-2xl p-6 text-center border border-gray-150">
                        <p class="text-xs font-bold text-gray-500">Chưa cập nhật danh sách cán bộ phụ trách.</p>
                    </div>
                `;
            }
        } catch (err) {
            console.error('Fetch officers error:', err);
            if (isMounted) {
                officersList.innerHTML = '<p class="text-center text-red-500 text-xs py-4">Không thể tải danh sách cán bộ.</p>';
            }
        }
    })();

    return () => {
        isMounted = false;
    };
}
