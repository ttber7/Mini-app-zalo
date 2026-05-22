import { config } from '../api/config.js';
import { callPhone } from '../utils/zalo.js';

// Helper to escape HTML safely to prevent XSS
const escapeHTML = (str = '') => {
    return String(str).replace(/[&<>'"]/g,
        tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag)
    );
};

// Helper to format date string YYYY-MM-DD to DD/MM/YYYY
function formatDate(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
}

// Helper to get Vietnamese day of week from YYYY-MM-DD
function getDayOfWeek(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '';
    const days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
    return days[date.getDay()];
}

export function renderScheduleUI(container, type = 'general') {
    container.innerHTML = '';

    // Track active tab state
    let activeTab = type === 'cskv' ? 'cskv' : 'general';
    let fetchController = null;

    // Create Page Header
    const header = document.createElement('div');
    header.className = 'w-full pt-10 pb-6 px-4 bg-gradient-to-b from-[#1E40AF] to-white/0 flex flex-col items-center';
    header.innerHTML = `
        <h2 class="text-2xl font-black text-white drop-shadow-md mb-2">Lịch Trực & Cán Bộ</h2>
        <p class="text-xs text-white/80 uppercase tracking-widest font-bold">Tra cứu thông tin chính xác, nhanh chóng</p>
    `;
    container.appendChild(header);

    // Create Tabs Container
    const tabsContainer = document.createElement('div');
    tabsContainer.className = 'px-4 -mt-2 relative z-10';
    tabsContainer.innerHTML = `
        <div class="bg-white/70 backdrop-blur-xl border border-white/50 shadow-xl rounded-2xl p-1.5 flex justify-between items-center gap-2">
            <button id="tab-general" class="flex-1 text-center py-3 rounded-xl text-sm font-bold transition-all duration-300 select-none active:scale-95">
                📅 Lịch tiếp dân
            </button>
            <button id="tab-cskv" class="flex-1 text-center py-3 rounded-xl text-sm font-bold transition-all duration-300 select-none active:scale-95">
                👮‍♂️ Cán bộ CSKV
            </button>
        </div>
    `;
    container.appendChild(tabsContainer);

    // Create Content Panel Container
    const contentPanel = document.createElement('div');
    contentPanel.className = 'px-4 mt-6 pb-24';
    contentPanel.id = 'schedule-content-panel';
    container.appendChild(contentPanel);

    const btnGeneral = tabsContainer.querySelector('#tab-general');
    const btnCskv = tabsContainer.querySelector('#tab-cskv');

    // Update active tab visual styles
    function updateTabStyles() {
        if (activeTab === 'general') {
            btnGeneral.className = 'flex-1 text-center py-3 rounded-xl text-sm font-black bg-blue-600 text-white shadow-md transition-all duration-300';
            btnCskv.className = 'flex-1 text-center py-3 rounded-xl text-sm font-bold text-gray-500 hover:text-gray-700 bg-transparent transition-all duration-300';
        } else {
            btnCskv.className = 'flex-1 text-center py-3 rounded-xl text-sm font-black bg-blue-600 text-white shadow-md transition-all duration-300';
            btnGeneral.className = 'flex-1 text-center py-3 rounded-xl text-sm font-bold text-gray-500 hover:text-gray-700 bg-transparent transition-all duration-300';
        }
    }

    // Load Schedules (Lịch tiếp dân)
    async function loadSchedules() {
        contentPanel.innerHTML = `
            <div class="text-center py-12">
                <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Đang tải lịch trực ban...</p>
            </div>
        `;

        if (fetchController) fetchController.abort();
        fetchController = new AbortController();

        try {
            const res = await fetch(`${config.API_BASE_URL}/miniapp/v1/schedules`, {
                method: 'GET',
                signal: fetchController.signal
            });

            if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);
            const result = await res.json();
            const schedules = result.data || [];

            if (schedules.length === 0) {
                contentPanel.innerHTML = `
                    <div class="bg-white/50 border border-gray-100 rounded-3xl p-10 text-center shadow-sm">
                        <div class="text-5xl mb-4">📅</div>
                        <h4 class="text-base font-black text-gray-700 mb-1">Chưa có lịch trực ban</h4>
                        <p class="text-xs text-gray-400">Danh sách ca trực sẽ được cập nhật trong thời gian sớm nhất.</p>
                    </div>
                `;
                return;
            }

            renderSchedulesList(schedules);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Load Schedules Error:', error);
                contentPanel.innerHTML = `
                    <div class="bg-red-50/50 border border-red-100 rounded-3xl p-8 text-center">
                        <div class="text-4xl mb-3">⚠️</div>
                        <p class="text-sm font-bold text-red-600 mb-2">Lỗi tải dữ liệu lịch trực</p>
                        <p class="text-xs text-red-400">Vui lòng kiểm tra lại kết nối mạng.</p>
                    </div>
                `;
            }
        }
    }

    function renderSchedulesList(schedules) {
        contentPanel.innerHTML = `
            <div class="space-y-4 relative before:absolute before:left-[19px] before:top-2 before:bottom-2 before:w-0.5 before:bg-blue-100">
                ${schedules.map(item => {
                    const dow = getDayOfWeek(item.date);
                    const formattedDate = formatDate(item.date);
                    const dateDisplay = dow ? `${dow}, ${formattedDate}` : formattedDate;
                    const safeTitle = escapeHTML(item.title);
                    const safeTime = escapeHTML(item.time || 'Cả ngày');
                    const safeOfficer = escapeHTML(item.officer);
                    const safeLocation = escapeHTML(item.location || 'Trụ sở Công an');
                    const safeNotes = escapeHTML(item.notes);

                    return `
                        <div class="relative pl-9 group">
                            <!-- Timeline node circle -->
                            <div class="absolute left-[11px] top-1.5 w-[18px] h-[18px] rounded-full border-4 border-blue-500 bg-white z-10 transition-transform duration-300 group-hover:scale-125"></div>
                            
                            <!-- Schedule Card -->
                            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 transition-all duration-300 active:scale-[0.99] active:shadow-none hover:border-blue-200">
                                <div class="flex justify-between items-start gap-2 mb-2">
                                    <span class="text-xs font-black text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full uppercase tracking-wider">${dateDisplay}</span>
                                    ${item.phone ? `
                                        <button class="btn-call-schedule text-xs font-extrabold text-white bg-green-600 active:bg-green-700 px-3 py-1 rounded-full flex items-center gap-1 shadow-sm select-none" data-phone="${escapeHTML(item.phone)}">
                                            📞 Gọi
                                        </button>
                                    ` : ''}
                                </div>
                                <h4 class="text-sm font-black text-gray-800 mb-2 leading-tight">${safeTitle}</h4>
                                
                                <div class="space-y-1.5 text-xs text-gray-500">
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 text-center text-sm">⏰</span>
                                        <span>Ca trực: <strong class="text-gray-700">${safeTime}</strong></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 text-center text-sm">👤</span>
                                        <span>Phụ trách: <strong class="text-gray-700">${safeOfficer}</strong></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 text-center text-sm">📍</span>
                                        <span>Địa điểm: <strong class="text-gray-700">${safeLocation}</strong></span>
                                    </div>
                                    ${safeNotes ? `
                                        <div class="mt-2.5 pt-2.5 border-t border-dashed border-gray-100 flex items-start gap-2">
                                            <span class="w-5 text-center text-sm">📝</span>
                                            <span class="italic leading-relaxed text-gray-400">Nhiệm vụ: ${safeNotes}</span>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;

        // Attach calls to buttons
        contentPanel.querySelectorAll('.btn-call-schedule').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const phone = btn.getAttribute('data-phone');
                if (phone) callPhone(phone);
            });
        });
    }

    // Load Officers (Cán bộ CSKV)
    async function loadOfficers() {
        contentPanel.innerHTML = `
            <div class="text-center py-12">
                <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Đang tìm danh sách cán bộ...</p>
            </div>
        `;

        if (fetchController) fetchController.abort();
        fetchController = new AbortController();

        try {
            const res = await fetch(`${config.API_BASE_URL}/miniapp/v1/officers`, {
                method: 'GET',
                signal: fetchController.signal
            });

            if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);
            const result = await res.json();
            const officers = result.data || [];

            if (officers.length === 0) {
                contentPanel.innerHTML = `
                    <div class="bg-white/50 border border-gray-100 rounded-3xl p-10 text-center shadow-sm">
                        <div class="text-5xl mb-4">👮‍♂️</div>
                        <h4 class="text-base font-black text-gray-700 mb-1">Chưa có thông tin cán bộ</h4>
                        <p class="text-xs text-gray-400">Danh sách Cán bộ Cảnh sát khu vực đang được cập nhật.</p>
                    </div>
                `;
                return;
            }

            renderOfficersList(officers);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Load Officers Error:', error);
                contentPanel.innerHTML = `
                    <div class="bg-red-50/50 border border-red-100 rounded-3xl p-8 text-center">
                        <div class="text-4xl mb-3">⚠️</div>
                        <p class="text-sm font-bold text-red-600 mb-2">Lỗi tải dữ liệu Cán bộ</p>
                        <p class="text-xs text-red-400">Vui lòng kiểm tra lại kết nối mạng.</p>
                    </div>
                `;
            }
        }
    }

    function renderOfficersList(officers) {
        contentPanel.innerHTML = `
            <div class="space-y-4">
                ${officers.map(item => {
                    const safeName = escapeHTML(item.name);
                    const safeArea = escapeHTML(item.area || 'Chưa phân công địa bàn');
                    const safePhone = escapeHTML(item.phone);
                    const photoUrl = item.image_url || 'https://dummyimage.com/100x100/e0e0e0/555555.png&text=CSKV';

                    return `
                        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-4 transition-all duration-300 hover:border-blue-200 active:scale-[0.99]">
                            <!-- Officer Avatar -->
                            <img src="${photoUrl}" alt="${safeName}" class="w-14 h-14 rounded-full object-cover border border-gray-100 shadow-sm bg-gray-50 flex-shrink-0">
                            
                            <!-- Officer Metadata -->
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-black text-gray-800 mb-0.5 truncate">${safeName}</h4>
                                <p class="text-xs font-semibold text-gray-400 mb-1 truncate">👮‍♂️ Cảnh sát khu vực</p>
                                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                    <span class="text-blue-500 text-xs">🗺️</span>
                                    <span class="truncate">Khu vực: <strong class="text-gray-700">${safeArea}</strong></span>
                                </div>
                            </div>
                            
                            <!-- Call Button wrapper -->
                            ${safePhone ? `
                                <button class="btn-call-officer bg-green-600 hover:bg-green-700 active:scale-95 text-white font-black rounded-full w-10 h-10 flex items-center justify-center flex-shrink-0 shadow-sm select-none" data-phone="${safePhone}">
                                    📞
                                </button>
                            ` : ''}
                        </div>
                    `;
                }).join('')}
            </div>
        `;

        // Attach click actions
        contentPanel.querySelectorAll('.btn-call-officer').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const phone = btn.getAttribute('data-phone');
                if (phone) callPhone(phone);
            });
        });
    }

    // Toggle Content panel based on Tab Selection
    function switchTab(tab) {
        if (activeTab === tab) return;
        activeTab = tab;
        updateTabStyles();
        if (activeTab === 'general') {
            loadSchedules();
        } else {
            loadOfficers();
        }
    }

    // Bind tab clicks
    btnGeneral.addEventListener('click', () => switchTab('general'));
    btnCskv.addEventListener('click', () => switchTab('cskv'));

    // Init display
    updateTabStyles();
    if (activeTab === 'general') {
        loadSchedules();
    } else {
        loadOfficers();
    }

    // Return cleanup function to protect SPA routing context (memory leaks & aborting requests)
    return () => {
        if (fetchController) {
            fetchController.abort();
        }
    };
}
