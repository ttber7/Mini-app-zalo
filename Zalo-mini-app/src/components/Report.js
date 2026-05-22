import { config } from '../api/config.js';
import { renderDynamicForm } from './DynamicForm.js';
import { getZaloUserInfo } from '../utils/zalo.js';

// Helper escape HTML
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

export function renderReportUI(container) {
    container.innerHTML = '';

    const header = document.createElement('div');
    header.className = 'w-full pt-10 pb-6 px-4 bg-gradient-to-b from-[#1E40AF] to-white/0 flex flex-col items-center';
    header.innerHTML = `
        <h2 class="text-2xl font-black text-white drop-shadow-md mb-2">Phản ánh hiện trường</h2>
        <p class="text-xs text-white/80 uppercase tracking-widest font-bold">Gửi thông tin trực tiếp tới Công an</p>
    `;
    container.appendChild(header);

    // Phần 1: Form phản ánh hiện trường
    const formSection = document.createElement('div');
    formSection.className = 'px-4 mb-8';
    
    // Tìm cấu hình form trong cache config hoặc dùng mặc định
    let formConfig = null;
    const cachedConfig = localStorage.getItem('sdui_config_cache');
    if (cachedConfig) {
        try {
            const parsed = JSON.parse(cachedConfig);
            // Duyệt qua các component tìm component form
            const pages = parsed.pages || {};
            const homePage = Array.isArray(pages) ? pages.find(p => p.id === 'home') : pages['home'];
            if (homePage) {
                const components = homePage.page_components || homePage.components || homePage.layout || [];
                const formComp = components.find(c => c._type === 'form' || c.type === 'form');
                if (formComp) {
                    formConfig = formComp;
                }
            }
        } catch (e) {
            console.error('Lỗi parse form config cache', e);
        }
    }

    if (!formConfig) {
        formConfig = {
            id: 'form_report',
            api_submit: 'submit_report_api',
            fields: [
                { type: 'text', id: 'name', label: 'Họ tên người gửi', required: true },
                { type: 'phone', id: 'phone', label: 'Số điện thoại liên hệ', required: true },
                { type: 'text', id: 'content', label: 'Nội dung phản ánh', required: true },
                { type: 'location', id: 'location', label: 'Địa chỉ / Tọa độ GPS', required: false },
                { type: 'image', id: 'image', label: 'Hình ảnh hiện trường', required: false }
            ]
        };
    }

    const formEl = renderDynamicForm(formConfig);
    formSection.appendChild(formEl);
    container.appendChild(formSection);

    // Phần 2: Lịch sử phản ánh của người dân
    const historySection = document.createElement('div');
    historySection.className = 'px-4 pb-28';
    historySection.innerHTML = `
        <div class="flex items-center space-x-3 mb-4">
            <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
            <h3 class="text-sm font-black text-gray-800 uppercase tracking-tight">Lịch sử phản ánh của bạn</h3>
        </div>
        <div id="report-history-list" class="space-y-3">
            <div class="text-center py-6"><div class="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div></div>
        </div>
    `;
    container.appendChild(historySection);

    const loadHistory = async () => {
        const historyList = historySection.querySelector('#report-history-list');
        try {
            const userInfo = await getZaloUserInfo();
            const userId = userInfo.id || 'guest_user';

            const res = await fetch(`${config.API_BASE_URL}/miniapp/v1/report-status?user_id=${userId}`);
            if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);

            const result = await res.json();
            const items = result.data || [];

            if (items.length > 0) {
                historyList.innerHTML = items.map(item => {
                    const safeTitle = escapeHTML(item.title);
                    const safeNote = item.note ? escapeHTML(item.note) : 'Đang xử lý thông tin phản ánh';
                    
                    let statusColor = 'bg-yellow-100 text-yellow-800';
                    if (item.status === 'Đã hoàn thành' || item.status === 'resolved') {
                        statusColor = 'bg-green-100 text-green-800';
                    } else if (item.status === 'Từ chối' || item.status === 'rejected') {
                        statusColor = 'bg-red-100 text-red-800';
                    } else if (item.status === 'Đang giải quyết' || item.status === 'processing') {
                        statusColor = 'bg-blue-100 text-blue-800';
                    }

                    return `
                        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm space-y-2">
                            <div class="flex justify-between items-start">
                                <span class="text-xs font-black text-gray-800 line-clamp-1 flex-1 pr-2">${safeTitle}</span>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase ${statusColor}">${item.status}</span>
                            </div>
                            <p class="text-xs text-gray-500 font-medium">${safeNote}</p>
                            <div class="text-[10px] text-gray-400 font-bold">${item.date}</div>
                        </div>
                    `;
                }).join('');
            } else {
                historyList.innerHTML = `
                    <div class="bg-gray-50 rounded-2xl p-6 text-center border border-gray-100">
                        <p class="text-xs font-bold text-gray-400">Bạn chưa gửi phản ánh nào.</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Load Report History Failed:', error);
            historyList.innerHTML = '<p class="text-center text-red-500 text-xs py-4">Lỗi mạng. Vui lòng thử lại sau.</p>';
        }
    };

    loadHistory();
    return () => {};
}
