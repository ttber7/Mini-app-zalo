<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cổng Thông Tin Nghiệp Vụ - Công an Xã Cần Đước</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #F8FAFC;
            --card-bg: #FFFFFF;
            --primary: #1E3A8A;
            --primary-hover: #1E40AF;
            --accent: #DC2626;
            --gold: #D97706;
            --text-light: #0F172A;
            --text-muted: #475569;
            --border-color: rgba(15, 23, 42, 0.08);
            --glow: 0 10px 30px rgba(30, 58, 138, 0.06);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(30, 58, 138, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(220, 38, 38, 0.02) 0%, transparent 40%),
                linear-gradient(rgba(15, 23, 42, 0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15, 23, 42, 0.015) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 40px 40px, 40px 40px;
            color: var(--text-light);
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-x: hidden;
        }

        .header-container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 40px 20px;
            text-align: center;
        }

        .logo-emblem {
            width: 110px;
            height: 110px;
            margin: 0 auto 24px;
            background-image: url('<?php echo esc_url(plugins_url("assets/logo_cong_an.png", __FILE__)); ?>');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            filter: drop-shadow(0 4px 12px rgba(30, 58, 138, 0.15));
            animation: pulse-glow 3s infinite alternate;
        }

        @keyframes pulse-glow {
            0% {
                transform: scale(1);
                filter: drop-shadow(0 4px 12px rgba(30, 58, 138, 0.15));
            }
            100% {
                transform: scale(1.03);
                filter: drop-shadow(0 6px 18px rgba(30, 58, 138, 0.25));
            }
        }

        h1 {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: var(--primary);
            margin-bottom: 8px;
            background: linear-gradient(135deg, #1E3A8A 60%, #1D4ED8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            font-size: 1.1rem;
            color: var(--accent);
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .location-badge {
            display: inline-block;
            background: rgba(30, 58, 138, 0.06);
            border: 1px solid rgba(30, 58, 138, 0.15);
            color: #1E3A8A;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 5px;
        }

        main {
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
            padding: 0 20px 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: stretch;
        }

        @media (max-width: 768px) {
            main {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        .portal-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .portal-card:hover {
            transform: translateY(-5px);
            border-color: rgba(30, 58, 138, 0.2);
            box-shadow: 0 20px 40px rgba(30, 58, 138, 0.08);
        }

        .portal-card.citizen-card:hover {
            border-color: rgba(217, 119, 6, 0.2);
            box-shadow: 0 20px 40px rgba(217, 119, 6, 0.08);
        }

        .card-header {
            margin-bottom: 25px;
        }

        .card-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .citizen-badge {
            background: rgba(217, 119, 6, 0.08);
            color: var(--gold);
            border: 1px solid rgba(217, 119, 6, 0.2);
        }

        .officer-badge {
            background: rgba(30, 58, 138, 0.06);
            color: #1D4ED8;
            border: 1px solid rgba(30, 58, 138, 0.15);
        }

        h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-light);
        }

        .card-desc {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Citizen section features */
        .qr-section {
            background: #F8FAFC;
            border: 1px dashed rgba(15, 23, 42, 0.12);
            border-radius: 16px;
            padding: 20px;
            margin: 25px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .qr-placeholder {
            width: 90px;
            height: 90px;
            background: #fff;
            border-radius: 12px;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, 0.06);
        }

        .qr-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .qr-scan-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: #10B981;
            box-shadow: 0 0 8px #10B981;
            top: 0;
            left: 0;
            animation: scan 2s infinite ease-in-out;
        }

        @keyframes scan {
            0% { top: 0%; }
            50% { top: 100%; }
            100% { top: 0%; }
        }

        .qr-info h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 4px;
        }

        .qr-info p {
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .steps-list {
            list-style: none;
            font-size: 0.85rem;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .steps-list li {
            position: relative;
            padding-left: 20px;
        }

        .steps-list li::before {
            content: "•";
            color: var(--accent);
            font-size: 1.2rem;
            position: absolute;
            left: 5px;
            top: -2px;
        }

        /* Officer section elements */
        .officer-illustration {
            background: linear-gradient(135deg, rgba(30, 58, 138, 0.04) 0%, rgba(220, 38, 38, 0.02) 100%);
            border: 1px solid rgba(30, 58, 138, 0.08);
            border-radius: 16px;
            padding: 20px;
            margin: 25px 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .info-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.04);
            padding-bottom: 8px;
        }

        .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-label {
            color: var(--text-muted);
        }

        .info-value {
            color: var(--text-light);
            font-weight: 600;
        }

        .badge-live {
            background: rgba(16, 185, 129, 0.12);
            color: #059669;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            animation: pulse-live 1.5s infinite;
        }

        @keyframes pulse-live {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }

        .btn {
            display: block;
            width: 100%;
            text-align: center;
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            margin-top: 30px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.15);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 58, 138, 0.25);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid rgba(217, 119, 6, 0.4);
            color: var(--gold);
        }

        .btn-outline:hover {
            background: rgba(217, 119, 6, 0.05);
            border-color: var(--gold);
            transform: translateY(-2px);
        }

        footer {
            border-top: 1px solid var(--border-color);
            background: #F1F5F9;
            padding: 30px 20px;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .footer-logo {
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .footer-info {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 5px;
        }

        .footer-info-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        @media (max-width: 600px) {
            .footer-info {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>

    <header class="header-container">
        <div class="logo-emblem"></div>
        <div class="subtitle">Công an Nhân dân Việt Nam</div>
        <h1>Cổng Thông Tin Nghiệp Vụ & Phản Ánh An Ninh Trật Tự</h1>
        <div class="location-badge">Công an Xã Cần Đước - Huyện Cần Đước - Tỉnh Long An</div>
    </header>

    <main>
        <!-- Card 1: Dành cho Người dân -->
        <div class="portal-card citizen-card">
            <div class="card-header">
                <span class="card-badge citizen-badge">Dành cho Người dân</span>
                <h2>Ứng dụng Zalo Mini App</h2>
                <p class="card-desc">Gửi phản ánh an ninh trật tự, kiến nghị hiện trường nhanh chóng và theo dõi tiến độ xử lý trực tiếp trên thiết bị di động.</p>
            </div>

            <div>
                <div class="qr-section">
                    <div class="qr-placeholder">
                        <div class="qr-scan-line"></div>
                        <!-- Sử dụng QR Code mẫu chuyên nghiệp -->
                        <svg viewBox="0 0 100 100" style="width: 100%; height: 100%; fill: #0F172A;">
                            <rect x="0" y="0" width="25" height="25" />
                            <rect x="5" y="5" width="15" height="15" fill="#fff" />
                            <rect x="9" y="9" width="7" height="7" />
                            
                            <rect x="75" y="0" width="25" height="25" />
                            <rect x="80" y="5" width="15" height="15" fill="#fff" />
                            <rect x="84" y="9" width="7" height="7" />
                            
                            <rect x="0" y="75" width="25" height="25" />
                            <rect x="5" y="80" width="15" height="15" fill="#fff" />
                            <rect x="9" y="84" width="7" height="7" />

                            <rect x="35" y="10" width="10" height="10" />
                            <rect x="55" y="15" width="12" height="8" />
                            <rect x="40" y="35" width="15" height="15" />
                            <rect x="10" y="45" width="10" height="12" />
                            <rect x="70" y="40" width="20" height="10" />
                            <rect x="35" y="65" width="15" height="15" />
                            <rect x="65" y="65" width="25" height="10" />
                            <rect x="80" y="80" width="15" height="15" />
                        </svg>
                    </div>
                    <div class="qr-info">
                        <h4>Quét mã QR để truy cập</h4>
                        <p>Sử dụng camera điện thoại hoặc tính năng Quét mã trên Zalo để mở Zalo Mini App.</p>
                    </div>
                </div>

                <ul class="steps-list">
                    <li>Gửi ý kiến phản ánh, tố giác tội phạm bảo mật tuyệt đối.</li>
                    <li>Tra cứu lịch tiếp dân, lịch trực ban của cán bộ xã.</li>
                    <li>Xem số điện thoại khẩn cấp và danh sách Cảnh sát khu vực.</li>
                    <li>Giải đáp nhanh thủ tục hành chính, tạm trú, CCCD tự động.</li>
                </ul>
            </div>

            <a href="#" class="btn btn-outline" onclick="alert('Vui lòng quét mã QR trên ứng dụng Zalo để truy cập Mini App!'); return false;">Hướng dẫn truy cập</a>
        </div>

        <!-- Card 2: Dành cho Cán bộ -->
        <div class="portal-card">
            <div class="card-header">
                <span class="card-badge officer-badge">Dành cho Cán bộ</span>
                <h2>Quản lý & Tiếp nhận Nghiệp vụ</h2>
                <p class="card-desc">Hệ thống xử lý thông tin nghiệp vụ, trực ban trực tuyến và kiểm duyệt các phản ánh hiện trường từ nhân dân gửi về.</p>
            </div>

            <div>
                <div class="officer-illustration">
                    <div class="info-row">
                        <span class="info-label">Trạng thái hệ thống:</span>
                        <span class="badge-live">Hoạt động</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kết nối Zalo OA:</span>
                        <span class="info-value" style="color: #34D399;">Đã liên kết</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Cấp độ an ninh:</span>
                        <span class="info-value">SSL / AES-256</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phiên bản Backend:</span>
                        <span class="info-value">Core v1.0.0</span>
                    </div>
                </div>

                <p class="card-desc" style="font-size: 0.85rem; text-align: center;">Chỉ dành cho cán bộ trực ban được phân công nhiệm vụ truy cập và xử lý hồ sơ.</p>
            </div>

            <a href="<?php echo esc_url(wp_login_url()); ?>" class="btn btn-primary">Đăng nhập Cán bộ</a>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-logo">Cổng thông tin Điện tử Công an Xã Cần Đước</div>
            <p>Trực thuộc Công an Huyện Cần Đước, Tỉnh Long An</p>
            <div class="footer-info">
                <div class="footer-info-item">
                    <span>📞</span> Hotline trực ban: <strong>0272.3881.213</strong>
                </div>
                <div class="footer-info-item">
                    <span>📍</span> Địa chỉ: 12 Đường Quốc Lộ 50, Thị trấn Cần Đước, Huyện Cần Đước, Long An
                </div>
            </div>
            <p style="margin-top: 15px; font-size: 0.75rem; opacity: 0.5;">&copy; 2026 Công an Xã Cần Đước. Bảo lưu mọi quyền.</p>
        </div>
    </footer>

</body>
</html>
