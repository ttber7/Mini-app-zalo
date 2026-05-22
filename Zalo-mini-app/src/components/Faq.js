import { config } from '../api/config.js';
import { getZaloUserInfo } from '../utils/zalo.js';

// [FIX 3]: Chống Crash nếu tham số str là null/undefined
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

// [FIX 6]: Xây dựng Helper Toast UI chuyên nghiệp thay cho alert()
const showToast = (message, type = 'success') => {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-600' : 'bg-red-500';
    toast.className = `fixed top-10 left-1/2 transform -translate-x-1/2 ${bgColor} text-white px-6 py-3 rounded-full shadow-2xl z-[300] text-sm font-bold transition-all duration-300 opacity-0 translate-y-[-20px]`;
    toast.textContent = message;

    document.body.appendChild(toast);

    // Trigger animation
    requestAnimationFrame(() => {
        toast.classList.remove('opacity-0', 'translate-y-[-20px]');
    });

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-[-20px]');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

export function renderFaqUI(container) {
    container.innerHTML = '';

    const header = document.createElement('div');
    header.className = 'w-full pt-10 pb-6 px-4 bg-gradient-to-b from-[#1E40AF] to-white/0 flex flex-col items-center';
    header.innerHTML = `
        <h2 class="text-2xl font-black text-white drop-shadow-md mb-2">Hỏi đáp & Hỗ trợ</h2>
        <p class="text-xs text-white/80 uppercase tracking-widest font-bold">Tìm kiếm câu trả lời nhanh chóng</p>
    `;
    container.appendChild(header);

    const searchWrapper = document.createElement('div');
    searchWrapper.className = 'px-4 -mt-2 relative z-10';
    searchWrapper.innerHTML = `
        <div class="relative bg-white/60 backdrop-blur-xl border border-white/40 shadow-2xl rounded-full p-2 flex items-center">
            <span class="pl-4 text-xl text-blue-600">🔍</span>
            <input type="text" id="faq-search-input" placeholder="Nhập từ khóa cần tìm..." 
                   class="w-full bg-transparent border-none outline-none px-3 py-2 text-gray-700 font-medium placeholder-gray-400">
            <button id="faq-search-btn" class="bg-blue-600 hover:bg-blue-700 text-white rounded-full px-5 py-2 text-sm font-black transition active:scale-95 shadow-md flex items-center justify-center min-w-[90px]">Tìm</button>
        </div>
    `;
    container.appendChild(searchWrapper);

    const listContainer = document.createElement('div');
    listContainer.className = 'px-4 mt-6 pb-24';
    listContainer.id = 'faq-list-container';
    container.appendChild(listContainer);

    // Dọn dẹp rác Modal cũ (Memory Leak Guard)
    const oldModal = document.getElementById('faq-submit-modal');
    if (oldModal) oldModal.remove();

    const submitModal = document.createElement('div');
    submitModal.id = 'faq-submit-modal';
    submitModal.className = 'fixed inset-0 z-[200] hidden flex flex-col items-center justify-end pointer-events-none';
    submitModal.innerHTML = `
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm pointer-events-auto transition-opacity opacity-0" id="faq-modal-overlay"></div>
        <div class="w-full max-w-[480px] bg-white rounded-t-[32px] p-6 pb-10 shadow-2xl transform translate-y-full transition-transform duration-300 pointer-events-auto relative z-10" id="faq-modal-content">
            <button id="faq-close-btn" class="absolute top-4 right-5 text-gray-400 hover:text-gray-600 text-3xl">&times;</button>
            <div class="w-12 h-1.5 bg-gray-200 rounded-full mx-auto mb-6"></div>
            <h3 class="text-lg font-black text-gray-800 mb-2">Gửi câu hỏi mới</h3>
            <p class="text-xs text-gray-500 mb-4">Câu hỏi sẽ được gửi đến cán bộ để xem xét.</p>
            <textarea id="faq-new-question" class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-4 text-sm outline-none focus:border-blue-500 transition-colors resize-none h-32" placeholder="Nhập chi tiết câu hỏi..."></textarea>
            <button id="faq-submit-btn" class="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl py-3.5 font-black shadow-xl transition active:scale-95 flex justify-center items-center">Gửi câu hỏi</button>
        </div>
    `;
    document.body.appendChild(submitModal);

    const input = searchWrapper.querySelector('#faq-search-input');
    const searchBtn = searchWrapper.querySelector('#faq-search-btn');
    const overlay = submitModal.querySelector('#faq-modal-overlay');
    const content = submitModal.querySelector('#faq-modal-content');
    const newQuestionInput = submitModal.querySelector('#faq-new-question');
    const submitBtn = submitModal.querySelector('#faq-submit-btn');
    const closeBtn = submitModal.querySelector('#faq-close-btn');

    let debounceTimer;
    let fetchController = null;

    const fetchFaqs = async (keyword = '') => {
        listContainer.innerHTML = '<div class="text-center py-10"><div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div></div>';

        // [FIX 7]: Khóa nút Search chống click spam
        searchBtn.disabled = true;
        searchBtn.style.opacity = '0.7';

        if (fetchController) {
            fetchController.abort();
        }
        fetchController = new AbortController();

        try {
            const params = new URLSearchParams();
            if (keyword) params.append('q', keyword);
            // Bỏ limit ở đây tạm thời, backend đang tự set là 10/50
            const url = `${config.API_BASE_URL}/miniapp/v1/faqs?${params.toString()}`;

            const res = await fetch(url, {
                method: 'GET',
                signal: fetchController.signal
                // Backend đã cấu hình __return_true, không cần X-MiniApp-Key nữa
            });

            if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);

            const data = await res.json();

            if (data && data.data && data.data.length > 0) {
                renderList(data.data);
            } else {
                listContainer.innerHTML = `
                    <div class="bg-gray-50 rounded-3xl p-8 text-center border border-gray-100 mt-4">
                        <div class="text-4xl mb-3">🤔</div>
                        <p class="text-sm font-bold text-gray-600 mb-4">Không tìm thấy câu trả lời phù hợp.</p>
                        <button id="open-submit-modal-btn" class="bg-white border-2 border-blue-600 text-blue-600 px-6 py-2 rounded-full font-black text-xs uppercase hover:bg-blue-50 transition active:scale-95">Đặt câu hỏi mới</button>
                    </div>
                `;
                listContainer.querySelector('#open-submit-modal-btn').addEventListener('click', openModal);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Fetch FAQ Error:', error);
                listContainer.innerHTML = '<p class="text-center text-red-500 text-sm py-4">Lỗi mạng. Vui lòng thử lại sau.</p>';
            }
        } finally {
            // Mở lại nút Search
            searchBtn.disabled = false;
            searchBtn.style.opacity = '1';
        }
    };

    const renderList = (items) => {
        listContainer.innerHTML = items.map((item) => {
            const safeQuestion = escapeHTML(item.question);
            // [FIX 4]: Fallback an toàn về escapeHTML nếu DOMPurify không load được
            const safeAnswer = window.DOMPurify
                ? window.DOMPurify.sanitize(item.answer)
                : escapeHTML(item.answer);

            return `
            <div class="faq-item bg-white rounded-2xl shadow-sm border border-gray-100 mb-3 overflow-hidden transition-all duration-300">
                <div class="faq-header p-4 flex justify-between items-center cursor-pointer active:bg-gray-50">
                    <h4 class="text-sm font-bold text-gray-800 flex-1 pr-4 leading-tight">${safeQuestion}</h4>
                    <span class="faq-icon text-gray-400 transform transition-transform duration-300">▼</span>
                </div>
                <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 bg-gray-50/50">
                    <div class="p-4 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-50">
                        ${safeAnswer}
                    </div>
                </div>
            </div>
        `}).join('');

        listContainer.innerHTML += `
            <div class="text-center mt-6">
                <button id="open-submit-modal-bottom" class="text-blue-600 font-bold text-xs uppercase hover:underline active:opacity-70">Chưa có câu trả lời bạn cần? Đặt câu hỏi ngay</button>
            </div>
        `;

        const faqs = listContainer.querySelectorAll('.faq-item');
        faqs.forEach(item => {
            const header = item.querySelector('.faq-header');
            const content = item.querySelector('.faq-content');
            const icon = item.querySelector('.faq-icon');

            header.addEventListener('click', () => {
                const isOpen = content.style.maxHeight && content.style.maxHeight !== '0px';

                faqs.forEach(f => {
                    f.querySelector('.faq-content').style.maxHeight = null;
                    f.querySelector('.faq-icon').style.transform = 'rotate(0deg)';
                });

                if (!isOpen) {
                    content.style.maxHeight = content.scrollHeight + 'px';
                    icon.style.transform = 'rotate(180deg)';
                }
            });
        });

        listContainer.querySelector('#open-submit-modal-bottom').addEventListener('click', openModal);
    };

    const openModal = () => {
        submitModal.classList.remove('hidden');
        void submitModal.offsetWidth;
        overlay.classList.remove('opacity-0');
        content.classList.remove('translate-y-full');
        if (input.value.trim() !== '') newQuestionInput.value = input.value.trim();
    };

    const closeModal = () => {
        overlay.classList.add('opacity-0');
        content.classList.add('translate-y-full');
        setTimeout(() => { submitModal.classList.add('hidden'); }, 300);
    };

    overlay.addEventListener('click', closeModal);
    closeBtn.addEventListener('click', closeModal);

    submitBtn.addEventListener('click', async () => {
        const question = newQuestionInput.value.trim();
        if (!question) {
            showToast('Vui lòng nhập chi tiết câu hỏi', 'error');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>';

        try {
            const userInfo = await getZaloUserInfo();
            const userId = userInfo.id || 'guest_user';

            const res = await fetch(`${config.API_BASE_URL}/miniapp/v1/submit-question`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question, user_id: userId })
            });

            const data = await res.json();

            if (res.ok && data.success) {
                if (data.auto_answered) {
                    showToast('Hệ thống tự động tìm thấy câu trả lời!', 'success');
                    closeModal();
                    newQuestionInput.value = '';

                    // Trích xuất câu hỏi & trả lời an toàn
                    const safeQuestion = escapeHTML(data.matched_question);
                    const safeAnswer = window.DOMPurify
                        ? window.DOMPurify.sanitize(data.answer)
                        : escapeHTML(data.answer);

                    // Xóa phần hiển thị không tìm thấy câu hỏi nếu có
                    const emptyState = listContainer.querySelector('.bg-gray-50');
                    if (emptyState) {
                        listContainer.innerHTML = '';
                    }

                    // Tạo và chèn phần tử FAQ mới lên đầu
                    const faqItem = document.createElement('div');
                    faqItem.className = 'faq-item bg-blue-50/50 border-2 border-blue-200 rounded-2xl shadow-sm mb-3 overflow-hidden transition-all duration-300 animate-pulse';
                    faqItem.innerHTML = `
                        <div class="faq-header p-4 flex justify-between items-center cursor-pointer active:bg-blue-100/50">
                            <h4 class="text-sm font-bold text-blue-900 flex-1 pr-4 leading-tight">
                                <span class="bg-blue-600 text-white text-[9px] px-1.5 py-0.5 rounded mr-1.5 uppercase font-black">Tự động trả lời</span>
                                ${safeQuestion}
                            </h4>
                            <span class="faq-icon text-blue-500 transform transition-transform duration-300">▼</span>
                        </div>
                        <div class="faq-content max-h-0 overflow-hidden transition-all duration-300 bg-white">
                            <div class="p-4 pt-0 text-sm text-gray-700 leading-relaxed border-t border-blue-100">
                                ${safeAnswer}
                            </div>
                        </div>
                    `;

                    // Gắn sự kiện click mở rộng
                    const header = faqItem.querySelector('.faq-header');
                    const content = faqItem.querySelector('.faq-content');
                    const icon = faqItem.querySelector('.faq-icon');

                    header.addEventListener('click', () => {
                        const isOpen = content.style.maxHeight && content.style.maxHeight !== '0px';

                        // Đóng tất cả các faq-item khác
                        listContainer.querySelectorAll('.faq-item').forEach(f => {
                            f.querySelector('.faq-content').style.maxHeight = null;
                            f.querySelector('.faq-icon').style.transform = 'rotate(0deg)';
                        });

                        if (!isOpen) {
                            content.style.maxHeight = content.scrollHeight + 'px';
                            icon.style.transform = 'rotate(180deg)';
                        }
                    });

                    // Chèn lên trên đầu danh sách
                    listContainer.insertBefore(faqItem, listContainer.firstChild);

                    // Mở rộng phần tử tự động trả lời này ngay lập tức sau hiệu ứng mượt
                    setTimeout(() => {
                        faqItem.classList.remove('animate-pulse');
                        content.style.maxHeight = content.scrollHeight + 'px';
                        icon.style.transform = 'rotate(180deg)';
                    }, 500);

                } else {
                    showToast('Gửi câu hỏi thành công! Cán bộ sẽ sớm giải đáp.', 'success');
                    closeModal();
                    newQuestionInput.value = '';
                }
            } else {
                showToast(data.message || 'Có lỗi xảy ra, vui lòng thử lại', 'error');
            }
        } catch (error) {
            showToast('Lỗi mạng, vui lòng kiểm tra lại kết nối.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Gửi câu hỏi';
        }
    });

    searchBtn.addEventListener('click', () => fetchFaqs(input.value.trim()));

    input.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchFaqs(e.target.value.trim()), 500);
    });

    // Initial fetch
    fetchFaqs();

    // [FIX 5]: Cleanup Function dành cho SPA Router (Trả về hàm để router dọn dẹp)
    return () => {
        clearTimeout(debounceTimer);
        if (fetchController) fetchController.abort();
        const existingModal = document.getElementById('faq-submit-modal');
        if (existingModal) existingModal.remove();
    };
}