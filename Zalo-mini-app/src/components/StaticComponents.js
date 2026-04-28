import { navigate, openWebUrl, callPhone } from '../utils/zalo.js';

/**
 * 1. HERO HEADER (Phần trên cùng màu tím/xanh gradient)
 */
/**
 * 1. HERO HEADER (Phần trên cùng màu xanh gradient)
 */
export function renderHeroHeader(data, globalConfig = {}) {
    const header = document.createElement('div');
    // Bỏ absolute cho thẻ card, dùng natural flow để dễ quản lý code về sau
    header.className = 'relative w-full pt-12 pb-8 px-4 bg-gradient-to-b from-[#0091FF] via-[#00BFFF] to-[#33CCFF] rounded-b-[40px] shadow-lg overflow-hidden flex flex-col space-y-6';

    // --- LẤY DỮ LIỆU ĐỘNG TỪ BACKEND ---
    const appName = globalConfig.app_name || 'CÔNG AN XÃ CẦN ĐƯỚC';
    const logoUrl = globalConfig.logo_url || 'https://upload.wikimedia.org/wikipedia/commons/a/a1/Logo_Huy_hi%E1%BB%87u_C%C3%B4ng_an_nh%C3%A2n_d%C3%A2n_Vi%E1%BB%87t_Nam.png';
    const temp = globalConfig.weather?.temp || '--°C';
    const weatherDesc = globalConfig.weather?.desc || 'Cập nhật trực tiếp';

    // Background Pattern
    const pattern = document.createElement('div');
    pattern.className = 'absolute inset-0 opacity-10 pointer-events-none overflow-hidden flex justify-center items-center z-0';
    pattern.innerHTML = `
        <div class="w-96 h-96 border-[40px] border-white/20 rounded-full border-dashed absolute top-0 -translate-y-1/4"></div>
        <div class="w-64 h-64 border-[20px] border-white/20 rounded-full border-dashed absolute top-10 -translate-y-1/4"></div>
    `;
    header.appendChild(pattern);

    // Ngày tháng hiện tại
    const today = new Date();
    const days = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
    const dateString = `${days[today.getDay()]} - ${today.getDate()}/${today.getMonth() + 1}/${today.getFullYear()}`;

    header.innerHTML += `
        <!-- Phần trên cùng -->
        <div class="relative z-10 flex flex-col items-center text-center text-white">
            <div class="w-16 h-16 bg-white p-1 rounded-2xl shadow-xl mb-3">
                <img src="${logoUrl}" class="w-full h-full object-contain" />
            </div>
            <h1 class="text-[20px] font-black uppercase tracking-wide leading-tight">${appName}</h1>
            <p class="text-[12px] font-bold opacity-90 mt-1 uppercase">HUYỆN CẦN ĐƯỚC - LONG AN</p>
            
            <div class="grid grid-cols-2 gap-3 w-full mt-6 px-2">
                <button class="flex items-center justify-center space-x-2 bg-white/20 backdrop-blur-md py-3 px-2 rounded-xl border border-white/30 hover:bg-white/40 transition">
                    <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center text-white text-sm shadow-sm">📄</div>
                    <div class="text-left">
                        <div class="text-[11px] font-bold">Tin tức nổi bật</div>
                        <div class="text-[9px] opacity-80">Cập nhật mới</div>
                    </div>
                </button>
                <button onclick="window.location.href='tel:02253925128'" class="flex items-center justify-center space-x-2 bg-white/20 backdrop-blur-md py-3 px-2 rounded-xl border border-white/30 hover:bg-white/40 transition">
                    <div class="w-8 h-8 bg-red-600 rounded-full flex items-center justify-center text-white text-sm shadow-sm">📞</div>
                    <div class="text-left">
                        <div class="text-[11px] font-bold">Hotline</div>
                        <div class="text-[9px] opacity-90 font-mono">02253.925.128</div>
                    </div>
                </button>
            </div>
        </div>

        <!-- Thẻ Blue Card (Dùng flow tự nhiên, không dùng absolute đè lên nữa) -->
        <div class="relative z-10 bg-gradient-to-b from-[#00BFFF] to-[#0091FF] rounded-3xl shadow-xl p-5 flex flex-col space-y-4 border border-white/30 text-white">
            <!-- Header của thẻ -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-white/20 rounded-full p-1 backdrop-blur-sm shadow-inner">
                         <img src="${logoUrl}" class="w-full h-full object-contain drop-shadow-md" />
                    </div>
                    <div>
                        <h4 class="text-sm font-bold">${appName}</h4>
                        <p class="text-[10px] opacity-80 italic">Vì nhân dân phục vụ</p>
                    </div>
                </div>
                <div class="text-[10px] bg-black/10 px-2 py-1 rounded-lg font-medium">
                    ${dateString}
                </div>
            </div>

            <!-- Các khối chức năng -->
            <div class="space-y-3">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 flex items-center space-x-4 border border-white/10 shadow-sm transition hover:bg-white/20">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-lg shadow-inner">👤</div>
                    <div>
                        <div class="text-sm font-bold">Xin chào, bạn!</div>
                        <div class="text-[11px] opacity-80">Chào mừng bạn đến với CA xã Cần Đước</div>
                    </div>
                </div> 

                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 flex items-center justify-between border border-white/10 shadow-sm transition hover:bg-white/20">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-lg shadow-inner">📍</div>
                        <div>
                            <div class="text-sm font-bold">Khu vực địa bàn</div>
                            <div class="text-[11px] opacity-80">${weatherDesc}</div>
                        </div>
                    </div>
                    <div class="text-right flex items-center space-x-3">
                        <div class="text-xl font-bold">${temp}</div>
                        <div class="text-2xl" id="weather-icon">☁️</div>
                    </div>
                </div>
            </div>

            <!-- Footer của thẻ -->
            <div class="flex items-center justify-center space-x-2 bg-black/5 py-2 rounded-xl">
                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse shadow-[0_0_8px_rgba(74,222,128,0.8)]"></div>
                <div class="text-[10px] font-bold uppercase tracking-widest opacity-90">Hệ thống ổn định</div>
            </div>
        </div>
    `;

    return header;
}

/**
 * 2. STATISTICS GRID (4 ô màu)
 */
export function renderStatsGrid(data) {
    const container = document.createElement('div');
    container.className = 'mt-10 px-4';

    // Lấy dữ liệu động, fallback về '0' nếu admin chưa nhập
    const pop = data?.population || '0';
    const house = data?.households || '0';
    const area = data?.area || '0';
    const party = data?.party_members || '0';
    const updateTime = data?.update_time || 'Đang cập nhật';

    container.innerHTML = `
        <div class="flex items-center space-x-2 mb-4">
            <span class="text-xl">📊</span>
            <h3 class="font-bold text-gray-900">Thống kê xã</h3>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-gradient-to-br from-[#00BFFF] to-[#0091FF] p-4 rounded-2xl text-white shadow-lg shadow-blue-200">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs opacity-80 font-medium">Người dân</span>
                    <span class="text-lg">👤</span>
                </div>
                <div class="text-xl font-black">${pop}</div>
                <div class="text-[10px] opacity-80 uppercase font-bold mt-1">Dân số</div>
            </div>
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 p-4 rounded-2xl text-white shadow-lg shadow-emerald-200">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs opacity-80 font-medium">Hộ dân</span>
                    <span class="text-lg">🏠</span>
                </div>
                <div class="text-xl font-black">${house}</div>
                <div class="text-[10px] opacity-80 uppercase font-bold mt-1">Hộ gia đình</div>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-4 rounded-2xl text-white shadow-lg shadow-purple-200">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs opacity-80 font-medium">Diện tích</span>
                    <span class="text-lg">📍</span>
                </div>
                <div class="text-xl font-black">${area} km2</div>
                <div class="text-[10px] opacity-80 uppercase font-bold mt-1">Diện tích</div>
            </div>
            <div class="bg-gradient-to-br from-rose-500 to-rose-600 p-4 rounded-2xl text-white shadow-lg shadow-rose-200">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs opacity-80 font-medium">Đảng viên</span>
                    <span class="text-lg">❤️</span>
                </div>
                <div class="text-xl font-black">${party}</div>
                <div class="text-[10px] opacity-80 uppercase font-bold mt-1">Đảng viên</div>
            </div>
        </div>
        <p class="text-center text-[10px] text-gray-400 mt-4 italic font-medium">Số liệu cập nhật tháng 4/2026</p>
    `;
    return container;
}

/**
 * 3. OFFICIAL CHANNEL (Kênh chính thức)
 */
export function renderOfficialChannel(data) {
    const container = document.createElement('div');
    container.className = 'mt-6 px-4';

    const coverImg = data?.cover_image || 'https://via.placeholder.com/600x200?text=Banner+Zalo+OA';
    const oaId = data?.oa_id || '';

    container.innerHTML = `
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 flex flex-col">
            <!-- Header Bar -->
            <div class="bg-[#2D58D7] p-3 text-white flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="text-base">⭐</span>
                    <h3 class="font-bold text-[15px] tracking-wide">Kênh chính thức</h3>
                </div>
            </div>
            <div class="bg-[#4169E1] px-4 py-2 text-white/90 text-[10.5px] font-medium">
                Nhận thông báo và cập nhật mới nhất từ cơ quan
            </div>
            
            <!-- Body -->
            <div class="p-4 flex flex-col space-y-5">
                <!-- OA Info Row -->
                <div class="flex items-center justify-between bg-gray-50/50 p-2 rounded-2xl">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-white shadow-sm">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/a/a1/Logo_Huy_hi%E1%BB%87u_C%C3%B4ng_an_nh%C3%A2n_d%C3%A2n_Vi%E1%BB%87t_Nam.png" class="w-full h-full object-contain bg-white" />
                        </div>
                        <div>
                            <h4 class="text-[13px] font-bold text-gray-900 leading-tight">Công an xã Cần Đước</h4>
                            <div class="text-[10px] text-blue-600 font-bold flex items-center mt-0.5">
                                <span class="bg-blue-600 text-white text-[7px] w-3 h-3 rounded-full flex items-center justify-center mr-1">✓</span> Kênh chính thức
                            </div>
                        </div>
                    </div>
                    <button onclick="window.location.href='https://zalo.me/${oaId}'" class="bg-[#2D58D7] hover:bg-blue-700 text-white px-6 py-2 rounded-full text-xs font-black shadow-md transition active:scale-95">
                        Quan tâm
                    </button>
                </div>
                
                <!-- Sub Action Buttons -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-blue-50/70 p-3 rounded-xl flex items-center space-x-2 border border-blue-100/50">
                        <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-sm shadow-sm">🔔</div>
                        <span class="text-[11px] font-bold text-blue-800">Thông báo nhanh</span>
                    </div>
                    <div class="bg-emerald-50/70 p-3 rounded-xl flex items-center space-x-2 border border-emerald-100/50">
                        <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-sm shadow-sm">📅</div>
                        <span class="text-[11px] font-bold text-emerald-800">Cập nhật 24/7</span>
                    </div>
                </div>
                
                <!-- Bottom Banner Image (Red one from screenshot) -->
                <div class="rounded-xl overflow-hidden shadow-md border border-gray-100 relative">
                    <img src="${coverImg}" class="w-full h-32 object-cover" />
                    <div class="absolute inset-0 bg-gradient-to-r from-red-600/20 to-transparent pointer-events-none"></div>
                </div>
            </div>
        </div>
    `;
    return container;
}

/**
 * 4. LATEST NEWS (Thông tin mới nhất)
 * Lưu ý: Phần này hiện tại vẫn render Giao diện tĩnh vì Backend WordPress (Giai đoạn 3) 
 * chưa viết API đẩy bài viết sang. Sẽ móc nối ở giai đoạn sau.
 */
export function renderLatestNews(data) {
    const container = document.createElement('div');
    container.className = 'mt-10 px-4 mb-4';
    const sectionTitle = data?.title || 'Thông tin mới nhất';

    container.innerHTML = `
        <div class="flex items-center justify-between mb-4 px-1">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">📄</div>
                <h3 class="font-bold text-[16px] text-gray-900">${sectionTitle}</h3>
            </div>
            <a href="#" class="text-[12px] font-bold text-blue-600 hover:underline">Xem tất cả</a>
        </div>
        
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="p-4">
                <!-- News Meta -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-7 h-7 bg-blue-50 rounded-full flex items-center justify-center text-blue-600 text-xs shadow-inner">📰</div>
                        <span class="text-[11px] font-black text-blue-700 uppercase tracking-tight">Tin an ninh</span>
                    </div>
                    <button class="text-[11px] font-bold text-gray-400 hover:text-blue-500">Xem thêm</button>
                </div>
                
                <!-- News Title -->
                <h4 class="text-[15px] font-bold text-gray-900 leading-snug mb-4">
                    Thông báo: Lịch phát sóng chương trình nghệ thuật "Giai điệu bình yên" số 59 với chủ đề "80 năm Công an nhân dân"...
                </h4>
                
                <!-- News Image -->
                <div class="rounded-2xl overflow-hidden shadow-lg aspect-[16/9] mb-4 relative group">
                    <img src="https://congan.haiphong.gov.vn/uploads/haiphong/portal/congan/news/2024_04/17/tt_an_ninh.jpg" class="w-full h-full object-cover transition duration-500 group-hover:scale-110" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                </div>
                
                <!-- News Footer -->
                <div class="flex items-center justify-between pt-2 border-t border-gray-50 mt-2">
                    <div class="flex items-center text-[10px] text-gray-400 font-bold space-x-3">
                        <div class="flex items-center"><span class="mr-1">📅</span> 17/04/2026</div>
                        <div class="flex items-center"><span class="mr-1">🛡️</span> CA Xã Cần Đước</div>
                    </div>
                    <div class="flex space-x-2">
                        <span class="w-6 h-6 bg-gray-50 rounded-full flex items-center justify-center text-[10px]">👍</span>
                        <span class="w-6 h-6 bg-gray-50 rounded-full flex items-center justify-center text-[10px]">🔗</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scrolling Text / Marquee Style Notice -->
        <div class="mt-6 bg-gradient-to-r from-gray-50 to-white border border-gray-100 rounded-2xl p-4 flex items-center space-x-4 shadow-sm">
            <div class="w-12 h-12 bg-white rounded-2xl shadow-md flex items-center justify-center text-2xl animate-bounce-slow">⚖️</div>
            <div class="flex-1 overflow-hidden">
                <div class="text-[12px] font-black text-gray-900 leading-tight uppercase tracking-tight">Cập nhật tin mới nhất</div>
                <div class="text-[10px] text-gray-500 mt-0.5 truncate">về pháp luật và an ninh trật tự địa bàn xã Cần Đước...</div>
            </div>
        </div>
    `;
    return container;
}

/**
 * 5. EMERGENCY CONTACTS (Danh sách nút liên hệ khẩn cấp)
 */
export function renderEmergencyList(data) {
    const container = document.createElement('div');
    container.className = 'px-4 mt-10';

    const contacts = data?.hotlines || [];

    if (contacts.length === 0) return container; // Không render nếu WP rỗng

    container.innerHTML = ` 
        <div class="flex items-center space-x-2 mb-4">
            <span class="text-xl text-red-500">📞</span>
            <h3 class="font-bold text-gray-900">Liên hệ khẩn cấp</h3>
        </div>
        <div class="space-y-3">
            ${contacts.map(c => `
                <div class="flex items-center justify-between p-4 ${c.color} text-white rounded-2xl shadow-xl transition active:scale-95">
                    <div class="flex items-center space-x-3">
                        <div class="text-2xl">${c.icon}</div>
                        <div>
                            <div class="text-sm font-bold">${c.label}</div>
                            <div class="text-[10px] opacity-80">${c.sub}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-black tracking-tight">${c.phone}</div>
                        <button onclick="window.location.href='tel:${c.phone}'" class="text-[9px] font-bold bg-white/20 px-3 py-1.5 rounded-full uppercase tracking-wider">Gọi ngay</button>
                    </div>
                </div>
            `).join('')}
        </div>
        
        <div class="mt-4 bg-rose-50 border border-rose-100 rounded-xl p-3 flex items-center justify-center space-x-2">
            <span class="text-rose-500 text-sm">⚠️</span>
            <span class="text-[10px] font-bold text-rose-600">Chỉ sử dụng cho các trường hợp khẩn cấp thực sự</span>
        </div>
    `;
    return container;
}

/**
 * 6. GRID MENU (Các icon chức năng)
 */
export function renderGridMenu(data) {
    const items = data?.items || [];
    const container = document.createElement('div');
    container.className = 'px-4 mt-10';

    if (items.length === 0) return container;

    container.innerHTML = `
        <div class="flex items-center space-x-2 mb-4">
            <span class="text-xl text-blue-500">⚙️</span>
            <h3 class="font-bold text-gray-900">Tiện ích chính</h3>
        </div>
        <div class="grid grid-cols-4 gap-y-8 gap-x-2">
            ${items.map(item => `
                <div class="flex flex-col items-center text-center cursor-pointer group active:scale-90 transition">
                    <div class="w-16 h-16 bg-blue-50/80 group-hover:bg-blue-100 transition rounded-3xl flex items-center justify-center text-3xl shadow-sm mb-2 border border-blue-100/50">
                        ${item.icon || '📌'}
                    </div>
                    <span class="text-[10px] font-bold text-gray-600 leading-tight px-1">${item.label}</span>
                </div>
            `).join('')}
        </div>
    `;

    return container;
}

/**
 * 7. BOTTOM NAVIGATION
 * Ghi chú: Bottom Nav nên được giữ code tĩnh vì đây là điều hướng cốt lõi của App.
 */
export function renderBottomNav() {
    const nav = document.createElement('div');
    nav.className = 'fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[480px] bg-white/90 backdrop-blur-xl border-t border-gray-100 px-8 py-4 flex justify-between items-center z-50 rounded-t-3xl shadow-2xl';

    const items = [
        { label: 'Trang chủ', icon: '🏠', active: true },
        { label: 'Phản ánh', icon: '📝' },
        { label: 'Tin tức', icon: '📰' },
        { label: 'Liên hệ', icon: '📞' }
    ];

    nav.innerHTML = items.map(item => `
        <div class="flex flex-col items-center cursor-pointer group active:scale-90 transition">
            <span class="text-2xl ${item.active ? 'text-[#00BFFF] drop-shadow-sm' : 'text-gray-300'}">${item.icon}</span>
            <span class="text-[10px] font-black mt-1.5 ${item.active ? 'text-[#00BFFF]' : 'text-gray-400'}">${item.label}</span>
        </div>
    `).join('');

    return nav;
}
