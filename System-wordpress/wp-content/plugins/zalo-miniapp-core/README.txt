========================================================================
BỘ ĐÓNG GÓI SẢN PHẨM: ZALO MINI APP CORE (WHITE-LABEL EDITION)
========================================================================

Hệ thống Backend Headless SDUI & Quản lý Nghiệp vụ số hóa cấp Xã/Phường.
Thiết kế riêng biệt cho lực lượng Công an Xã/Phường.

------------------------------------------------------------------------
1. THƯ MỤC CẤU TRÚC PLUGIN
------------------------------------------------------------------------
zalo-miniapp-core/
│
├── includes/
│   ├── class-cpt-registry.php   - Đăng ký các CPT nghiệp vụ (Tin tức, Lịch trực, Phản ánh, Hỏi đáp)
│   ├── class-sdui-builder.php   - Cấu hình layout động kéo thả (SDUI) & Dashboard nghiệp vụ
│   ├── class-api-endpoints.php  - Cung cấp REST API bảo mật cho Mini App
│   ├── class-security-cache.php - Bảo mật JWT, Quản lý cache và mã hóa dữ liệu
│   └── class-oa-service.php     - Quản lý đồng bộ tin nhắn qua Webhook & Token Zalo OA
│
├── templates/
│   ├── assets/
│   │   └── logo_cong_an.png     - Huy hiệu Công an nhân dân Việt Nam (White-labeled)
│   └── default-ui.json          - File cấu hình giao diện mẫu (Mồi dữ liệu ban đầu)
│
├── vendor/                      - Thư viện Carbon Fields phụ thuộc (Bắt buộc)
├── zalo-miniapp-core.php        - File khởi chạy chính (Tạo vai trò, ẩn menu thừa, tùy biến Login)
└── README.txt                   - Hướng dẫn này

------------------------------------------------------------------------
2. HƯỚNG DẪN CÀI ĐẶT NHANH CHO CÁN BỘ KỸ THUẬT
------------------------------------------------------------------------
Bước 1: Tải thư mục `zalo-miniapp-core` lên thư mục `/wp-content/plugins/` trên Host/VPS.
Bước 2: Truy cập vào trang Quản trị WordPress (wp-admin).
Bước 3: Nhấp vào menu "Plugins" -> Tìm "Zalo Mini App Core" -> Chọn "Activate" (Kích hoạt).
        * Lưu ý: Khi kích hoạt, hệ thống sẽ tự động đăng ký vai trò mới là "Cán bộ Trực ban"
          và mồi sẵn dữ liệu giao diện cấu hình mẫu (Cần Đước) để sẵn sàng sử dụng.

------------------------------------------------------------------------
3. THIẾT LẬP VAI TRÒ "CÁN BỘ TRỰC BAN" (zalo_officer)
------------------------------------------------------------------------
Để cán bộ trực ban có thể tác nghiệp mà không làm rối mắt bởi các cài đặt của quản trị viên:
1. Vào menu "Thành viên" (Users) -> Chọn "Thêm mới" (Add New).
2. Nhập thông tin tài khoản cho cán bộ trực ban.
3. Tại phần "Vai trò" (Role), chọn: "Cán bộ Trực ban" (Cán bộ Trực ban).
4. Khi Cán bộ Trực ban đăng nhập, họ sẽ:
   - Thấy trang Đăng nhập màu xanh đại dương chuyên nghiệp kèm Huy hiệu Công an nhân dân.
   - Thấy một Dashboard Dashboard "Chính chủ" 4 cột có số liệu thống kê thời gian thực (Live Counters)
     về Tin tức, Lịch trực, số Phản ánh đang chờ xử lý, số câu hỏi FAQ đang chờ duyệt.
   - Bị ẩn sạch các menu cài đặt hệ thống WordPress (Bài viết mặc định, Trang, Comments, Cài đặt, Plugins, Tools...).

------------------------------------------------------------------------
4. CẤU HÌNH KÊNH TRUYỀN THÔNG & ZALO OA
------------------------------------------------------------------------
1. Vào menu "Zalo App" -> Chọn "Cấu hình Zalo App".
2. Cập nhật các thông tin cơ bản:
   - Tên Ứng Dụng (Hiển thị trên tiêu đề Mini App).
   - Màu chủ đạo (Nên giữ màu xanh dương đậm đặc trưng `#2D58D7`).
   - Số điện thoại trực ban (Số này sẽ được dùng toàn cục trên Mini App).
3. Tại mục "Cấu hình Zalo OA (Nâng cao)":
   - Nhập App ID và Secret Key của Zalo App liên kết (Lấy từ Zalo Developer Portal).
   - Nhập OA ID của trang Zalo OA Công an xã.
   - Bấm "Lưu thay đổi". 
   - Hệ thống sẽ tự động đăng ký callback và quản lý cập nhật Access Token/Refresh Token
     mỗi khi có yêu cầu tương tác.

------------------------------------------------------------------------
5. SAO LƯU & DI CHUYỂN CẤU HÌNH (IMPORT/EXPORT)
------------------------------------------------------------------------
Khi bàn giao hệ thống cho Xã/Phường khác:
1. Tại Xã nguồn: Vào "Zalo App" -> Chọn "Nhập/Xuất cấu hình". Bấm nút "TẢI FILE CẤU HÌNH (.JSON)" để lưu cấu hình.
2. Tại Xã đích: Cài đặt mới plugin này. Vào "Nhập/Xuất cấu hình", tải tệp tin JSON vừa xuất lên.
3. Hệ thống sẽ tự động cấu hình lại toàn bộ giao diện kéo thả mẫu mà không cần xây dựng lại từ đầu.

------------------------------------------------------------------------
Hỗ trợ kỹ thuật: Zalo Mini App Development Team.
