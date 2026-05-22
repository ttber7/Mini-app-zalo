import { config } from '../api/config.js';

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

export function renderNewsUI(container, onNavigateDetail) {
    container.innerHTML = '';

    let currentPage = 1;
    let maxPages = 1;
    const limit = 5;
    let fetchController = null;

    const header = document.createElement('div');
    header.className = 'w-full pt-10 pb-6 px-4 bg-gradient-to-b from-[#1E40AF] to-white/0 flex flex-col items-center';
    header.innerHTML = `
        <h2 class="text-2xl font-black text-white drop-shadow-md mb-2">Tin tức & An ninh trật tự</h2>
        <p class="text-xs text-white/80 uppercase tracking-widest font-bold">Cập nhật chính thống mới nhất</p>
    `;
    container.appendChild(header);

    const listContainer = document.createElement('div');
    listContainer.className = 'px-4 pb-24 space-y-4';
    listContainer.id = 'news-list-container';
    container.appendChild(listContainer);

    const itemsWrapper = document.createElement('div');
    itemsWrapper.className = 'space-y-4';
    itemsWrapper.id = 'news-items-wrapper';
    listContainer.appendChild(itemsWrapper);

    const loadMoreContainer = document.createElement('div');
    loadMoreContainer.className = 'text-center pt-2 pb-6';
    loadMoreContainer.id = 'news-load-more-container';
    listContainer.appendChild(loadMoreContainer);

    // Event Delegation for detail clicks
    itemsWrapper.addEventListener('click', (e) => {
        const item = e.target.closest('.news-item');
        if (item) {
            const newsId = item.getAttribute('data-id');
            if (newsId && typeof onNavigateDetail === 'function') {
                onNavigateDetail(newsId);
            }
        }
    });

    const fetchNews = async (page = 1) => {
        if (page === 1) {
            itemsWrapper.innerHTML = `
                <div class="text-center py-10">
                    <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Đang tải tin tức...</p>
                </div>
            `;
            loadMoreContainer.innerHTML = '';
        } else {
            loadMoreContainer.innerHTML = `
                <div class="flex justify-center items-center py-2">
                    <div class="w-6 h-6 border-3 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                </div>
            `;
        }

        if (fetchController) fetchController.abort();
        fetchController = new AbortController();

        try {
            const res = await fetch(`${config.API_BASE_URL}/miniapp/v1/news?limit=${limit}&page=${page}`, {
                signal: fetchController.signal
            });
            if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);
            
            const result = await res.json();
            const items = result.data || [];
            maxPages = result.pages || 1;

            if (page === 1) {
                itemsWrapper.innerHTML = '';
            }

            if (items.length > 0) {
                const newItemsHtml = items.map(item => {
                    const safeTitle = escapeHTML(item.title);
                    const safeExcerpt = escapeHTML(item.excerpt);
                    const imgHtml = item.image_url 
                        ? `<img src="${item.image_url}" class="w-24 h-24 object-cover rounded-xl shadow-md flex-shrink-0" />`
                        : `<div class="w-24 h-24 bg-blue-50 rounded-xl flex items-center justify-center text-3xl flex-shrink-0">📰</div>`;

                    return `
                        <div class="news-item bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex space-x-4 cursor-pointer active:scale-[0.98] transition-transform duration-200" data-id="${item.id}">
                            <div class="flex-1 flex flex-col justify-between min-w-0">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-800 line-clamp-2 leading-snug mb-1">${safeTitle}</h4>
                                    <p class="text-xs text-gray-500 line-clamp-2">${safeExcerpt}</p>
                                </div>
                                <span class="text-[10px] font-black text-blue-600 uppercase tracking-widest mt-2">${item.date}</span>
                            </div>
                            ${imgHtml}
                        </div>
                    `;
                }).join('');

                itemsWrapper.insertAdjacentHTML('beforeend', newItemsHtml);

                // Update load more controls
                if (currentPage < maxPages) {
                    loadMoreContainer.innerHTML = `
                        <button id="btn-load-more" class="bg-blue-600 hover:bg-blue-700 text-white rounded-2xl px-6 py-3.5 text-xs font-black uppercase tracking-wider transition active:scale-95 shadow-md w-full max-w-[200px] select-none">
                            Tải thêm tin tức
                        </button>
                    `;
                    loadMoreContainer.querySelector('#btn-load-more').addEventListener('click', () => {
                        currentPage++;
                        fetchNews(currentPage);
                    });
                } else {
                    loadMoreContainer.innerHTML = `
                        <p class="text-xs font-bold text-gray-400 py-3 uppercase tracking-wider">Đã hiển thị toàn bộ tin tức</p>
                    `;
                }
            } else {
                if (page === 1) {
                    itemsWrapper.innerHTML = `
                        <div class="bg-gray-50 rounded-3xl p-8 text-center border border-gray-100">
                            <div class="text-4xl mb-3">📭</div>
                            <p class="text-sm font-bold text-gray-500">Chưa có bài viết mới nào được cập nhật.</p>
                        </div>
                    `;
                    loadMoreContainer.innerHTML = '';
                }
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Fetch News Error:', error);
                if (page === 1) {
                    itemsWrapper.innerHTML = '<p class="text-center text-red-500 text-sm py-4">Lỗi tải dữ liệu. Vui lòng thử lại sau.</p>';
                    loadMoreContainer.innerHTML = '';
                } else {
                    loadMoreContainer.innerHTML = `
                        <div class="text-center">
                            <p class="text-xs text-red-500 mb-2 font-bold">Không thể tải thêm tin tức.</p>
                            <button id="btn-retry-load-more" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-xl text-xs font-bold active:scale-95 transition">Thử lại</button>
                        </div>
                    `;
                    loadMoreContainer.querySelector('#btn-retry-load-more').addEventListener('click', () => {
                        fetchNews(currentPage);
                    });
                }
            }
        }
    };

    fetchNews(1);
    return () => {
        if (fetchController) fetchController.abort();
    };
}

export function renderNewsDetailUI(container, newsId, onBack) {
    container.innerHTML = '<div class="text-center py-20"><div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div></div>';

    const fetchDetail = async () => {
        try {
            const res = await fetch(`${config.API_BASE_URL}/miniapp/v1/news/${newsId}`);
            if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);
            
            const item = await res.json();
            const safeTitle = escapeHTML(item.title);
            const safeContent = window.DOMPurify ? window.DOMPurify.sanitize(item.content) : item.content;
            
            container.innerHTML = `
                <div class="w-full pt-10 pb-4 px-4 bg-gradient-to-b from-[#1E40AF]/20 to-white/0 flex items-center space-x-3">
                    <button id="btn-news-back" class="w-10 h-10 bg-white border border-gray-150 rounded-full flex items-center justify-center text-lg active:scale-90 transition-transform">←</button>
                    <span class="text-xs uppercase font-black text-gray-500 tracking-widest">Chi tiết tin tức</span>
                </div>
                <div class="px-4 pb-28">
                    <h1 class="text-lg font-black text-gray-900 leading-snug mb-2">${safeTitle}</h1>
                    <div class="flex items-center space-x-2 text-xs text-gray-400 font-bold mb-6">
                        <span>📅 ${item.date}</span>
                        <span>•</span>
                        <span class="text-blue-600">CÔNG AN PHƯỜNG</span>
                    </div>
                    ${item.image_url ? `<img src="${item.image_url}" class="w-full h-48 object-cover rounded-3xl shadow-lg mb-6" />` : ''}
                    <div class="prose max-w-none text-sm text-gray-600 leading-relaxed font-medium">
                        ${safeContent}
                    </div>
                </div>
            `;

            container.querySelector('#btn-news-back').addEventListener('click', () => {
                if (typeof onBack === 'function') onBack();
            });

        } catch (error) {
            console.error('Fetch News Detail Error:', error);
            container.innerHTML = `
                <div class="p-6 text-center">
                    <p class="text-red-500 text-sm mb-4">Lỗi mạng hoặc tin tức không tồn tại.</p>
                    <button id="btn-news-back" class="bg-blue-600 text-white px-6 py-2 rounded-full font-black text-xs">Quay lại</button>
                </div>
            `;
            container.querySelector('#btn-news-back').addEventListener('click', () => {
                if (typeof onBack === 'function') onBack();
            });
        }
    };

    fetchDetail();
    return () => {};
}
