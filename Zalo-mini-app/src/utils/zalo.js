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

/**
 * Lấy thông tin user định danh của Zalo
 * @returns {Promise<Object>} Trả về userInfo hoặc object chứa id = 'guest_user' làm fallback
 */
export function getZaloUserInfo() {
    return new Promise((resolve) => {
        if (typeof api !== 'undefined' && api.getUserInfo) {
            api.getUserInfo({
                success: (data) => {
                    resolve(data.userInfo || { id: 'guest_user', name: 'Khách' });
                },
                fail: (err) => {
                    console.warn('Lỗi lấy thông tin Zalo User:', err);
                    resolve({ id: 'guest_user', name: 'Khách' });
                }
            });
        } else {
            resolve({ id: 'guest_user', name: 'Khách' });
        }
    });
}

