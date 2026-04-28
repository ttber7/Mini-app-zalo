export function renderSkeletonLoader(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = `
        <div class="animate-pulse flex flex-col space-y-4 p-4">
            <!-- Banner Skeleton -->
            <div class="w-full h-40 bg-gray-200 rounded-lg"></div>
            
            <!-- Grid Menu Skeleton -->
            <div class="grid grid-cols-4 gap-4 mt-4">
                <div class="w-full h-16 bg-gray-200 rounded-lg"></div>
                <div class="w-full h-16 bg-gray-200 rounded-lg"></div>
                <div class="w-full h-16 bg-gray-200 rounded-lg"></div>
                <div class="w-full h-16 bg-gray-200 rounded-lg"></div>
            </div>

            <!-- List Skeleton -->
            <div class="w-1/2 h-6 bg-gray-200 rounded mt-6 mb-2"></div>
            <div class="w-full h-20 bg-gray-200 rounded-lg"></div>
            <div class="w-full h-20 bg-gray-200 rounded-lg"></div>
        </div>
    `;
}

export function renderErrorFallback(containerId, message, onRetry) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = `
        <div class="flex flex-col items-center justify-center h-[80vh] p-4 text-center">
            <svg class="w-16 h-16 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Đã xảy ra lỗi</h2>
            <p class="text-gray-500 mb-6">${message || 'Không thể tải dữ liệu. Vui lòng kiểm tra kết nối mạng.'}</p>
            <button id="btn-retry" class="px-6 py-2 bg-zalo-blue text-white font-medium rounded-full shadow hover:bg-blue-600 transition">
                Thử lại
            </button>
        </div>
    `;

    document.getElementById('btn-retry').addEventListener('click', () => {
        if (onRetry) onRetry();
    });
}
