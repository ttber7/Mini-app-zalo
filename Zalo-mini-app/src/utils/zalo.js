import api from 'zmp-sdk';

/**
 * Lấy vị trí an toàn (Graceful Degradation)
 * @returns {Promise<Object>} Tọa độ hoặc null nếu từ chối
 */
export async function getSafeLocation() {
    try {
        const { latitude, longitude } = await api.getLocation({});
        return { latitude, longitude };
    } catch (error) {
        console.warn('User denied location or location failed:', error);
        return null; // Trả về null để UI fallback sang nhập tay
    }
}

/**
 * Điều hướng trang
 * @param {string} url 
 */
export function navigate(url) {
    // Trong môi trường thuần Vanilla JS, ta tạm dùng location.hash hoặc history
    // Nếu có zmp-ui router thì sẽ gọi zmp-ui router.
    window.location.hash = url;
}

/**
 * Mở Web (Open URL)
 * @param {string} url 
 */
export async function openWebUrl(url) {
    try {
        await api.openWebview({ url });
    } catch (e) {
        window.open(url, '_blank');
    }
}

/**
 * Gọi điện thoại
 * @param {string} phoneNumber 
 */
export async function callPhone(phoneNumber) {
    try {
        await api.openPhone({ phoneNumber });
    } catch (e) {
        window.location.href = `tel:${phoneNumber}`;
    }
}
