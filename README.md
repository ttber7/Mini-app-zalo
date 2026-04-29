# Zalo Mini App - Công an xã Cần Đước (SDUI Framework)

Dự án này sử dụng mô hình **Server-Driven UI (SDUI)**. WordPress đóng vai trò là Backend quản trị dữ liệu, Zalo Mini App đóng vai trò là Frontend hiển thị linh hoạt.

## 📂 Cấu trúc dự án
- `/System-wordpress`: Chứa mã nguồn Plugin WordPress (`zalo-miniapp-core`).
- `/Zalo-mini-app`: Chứa mã nguồn Frontend (Vite, Vanilla JS, Tailwind).

---

## 🛠 Hướng dẫn thiết lập trên máy mới

### 1. Thiết lập Backend (WordPress)
1. Cài đặt một trang WordPress mới (khuyên dùng **Local WP**).
2. Copy thư mục `System-wordpress/wp-content/plugins/zalo-miniapp-core` vào thư mục plugins của WordPress mới.
3. Kích hoạt Plugin **Zalo Mini App Core**.
4. Vào WP Admin -> Zalo App -> Cấu hình các thông số (Dân số, Diện tích, OA ID...).
5. Bấm **Lưu cấu hình** để sinh file `ui-config.json`.

### 2. Thiết lập Frontend (Zalo Mini App)
1. Mở terminal tại thư mục `Zalo-mini-app`.
2. Chạy lệnh: `npm install` (để cài đặt các thư viện cần thiết).
3. **Móc nối API:** Mở file `src/api/index.js` và cập nhật biến `BASE_URL` trỏ về địa chỉ WordPress local của bạn (Ví dụ: `http://localhost/can-duoc`).
4. Chạy lệnh: `npm run dev` để xem kết quả.

---

## 🚀 Lộ trình phát triển
- **Giai đoạn 1, 2, 3:** Đã hoàn thành (Core SDUI, Thống kê, Tin tức, Phản ánh hiện trường).
- **Giai đoạn 4 (Đang thực hiện):** Tích hợp Zalo OA, FAQ và Cổng thông tin Cán bộ.

---

## 📝 Lưu ý khi Clone
- Đảm bảo máy đã cài **Node.js** (phiên bản 18 trở lên).
- Khi đẩy code lên GitHub, thư mục `node_modules` đã được bỏ qua (.gitignore), nên bắt buộc phải chạy `npm install` ở máy mới.
