<?php
/**
 * Plugin Name: Zalo Mini App Core
 * Description: Hệ thống Backend Headless SDUI và Quản lý API cho Zalo Mini App.
 * Version: 1.0.0
 * Author: Zalo Mini App Team
 * Text Domain: zalo-miniapp-core
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('ZALO_MINIAPP_CORE_VERSION', '1.0.0');
define('ZALO_MINIAPP_CORE_PATH', plugin_dir_path(__FILE__));
define('ZALO_MINIAPP_CORE_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader if it exists (for Carbon Fields)
if (file_exists(ZALO_MINIAPP_CORE_PATH . 'vendor/autoload.php')) {
    require_once ZALO_MINIAPP_CORE_PATH . 'vendor/autoload.php';
}

/**
 * Main Plugin Class
 */
class Zalo_MiniApp_Core
{
    private $components = [];

    public function __construct()
    {
        $this->load_dependencies();
        // Khởi chạy hệ thống đúng Lifecycle của WordPress
        // Boot Carbon Fields (Chuẩn cho Plugin)
        add_action('plugins_loaded', array($this, 'boot_carbon_fields'));

        // Initialize components
        add_action('init', array($this, 'init_components'), 10);

        add_action('init', array($this, 'init_api_endpoints'));

        // Đảm bảo Admin có đầy đủ quyền CPT
        add_action('admin_init', array($this, 'ensure_admin_capabilities'));

        // Nạp dữ liệu cấu hình mẫu an toàn
        add_action('admin_init', array($this, 'seed_default_configuration'));

        // Mồi dữ liệu mẫu cho các Custom Post Types
        add_action('admin_init', array($this, 'seed_mock_data'));

        // Ghi đè giao diện Trang chủ WordPress ngoài Frontend
        add_filter('template_include', array($this, 'override_frontend_homepage'));

        // Ẩn menu rác WordPress cho tài khoản Cán bộ Trực ban (non manage_options)
        add_action('admin_menu', array($this, 'hide_unused_admin_menus'), 999);

        // Tùy biến trang wp-login.php
        add_action('login_enqueue_scripts', array($this, 'custom_login_styles'));
        add_filter('login_headerurl', array($this, 'custom_login_logo_url'));
        add_filter('login_headertext', array($this, 'custom_login_logo_title'));
    }

    private function load_dependencies()
    {
        $files = [
            'includes/class-cpt-registry.php',
            'includes/class-sdui-builder.php',
            'includes/class-api-endpoints.php',
            'includes/class-security-cache.php',
            'includes/class-oa-service.php',
        ];

        foreach ($files as $file) {
            $path = ZALO_MINIAPP_CORE_PATH . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }
    public function boot_carbon_fields()
    {
        // 1. BẮT BUỘC KHỞI TẠO BUILDER TRƯỚC ĐỂ NÓ XẾP HÀNG
        if (class_exists('Zalo_MiniApp_SDUI_Builder')) {
            $this->components['builder'] = new Zalo_MiniApp_SDUI_Builder();
            $this->components['builder']->init();
        }

        if (class_exists('Zalo_MiniApp_Security_Cache')) {
            $this->components['security'] = new Zalo_MiniApp_Security_Cache();
            $this->components['security']->init();
        }

        // 2. SAU ĐÓ MỚI GỌI BOOT ĐỂ CARBON FIELDS CHẠY (Bắt đầu vẽ giao diện)
        if (class_exists('\Carbon_Fields\Carbon_Fields')) {
            \Carbon_Fields\Carbon_Fields::boot();
        }
    }

    public function init_components()
    {
        // Đăng ký Custom Post Type (CPT bắt buộc chạy ở hook init)
        if (class_exists('Zalo_MiniApp_CPT_Registry')) {
            $this->components['cpt'] = new Zalo_MiniApp_CPT_Registry();
            $this->components['cpt']->register();
        }

        // Đăng ký dịch vụ Zalo OA
        if (class_exists('Zalo_MiniApp_OA_Service')) {
            $this->components['oa_service'] = Zalo_MiniApp_OA_Service::get_instance();
            $this->components['oa_service']->init();
        }
    }

    // [FIX POINT 1 & 2]: Đưa luồng API khởi động vào đúng lifecycle của WP REST API
    public function init_api_endpoints()
    {
        if (class_exists('Zalo_MiniApp_API_Endpoints')) {
            $api = new Zalo_MiniApp_API_Endpoints();
            $api->init();
        }
    }

    /**
     * Đảm bảo tài khoản administrator có đầy đủ capabilities cho các CPT
     */
    public function ensure_admin_capabilities()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $role = get_role('administrator');
        if ($role) {
            $cpts = ['report', 'news', 'officer', 'schedule', 'faq'];
            foreach ($cpts as $type) {
                $caps = [
                    "edit_zalo_{$type}",
                    "read_zalo_{$type}",
                    "delete_zalo_{$type}",
                    "edit_zalo_{$type}s",
                    "edit_others_zalo_{$type}s",
                    "publish_zalo_{$type}s",
                    "read_private_zalo_{$type}s",
                    "delete_zalo_{$type}s",
                    "delete_private_zalo_{$type}s",
                    "delete_published_zalo_{$type}s",
                    "delete_others_zalo_{$type}s",
                    "edit_private_zalo_{$type}s",
                    "edit_published_zalo_{$type}s"
                ];
                foreach ($caps as $cap) {
                    if (!$role->has_cap($cap)) {
                        $role->add_cap($cap);
                    }
                }
            }
        }
    }

    /**
     * Tự động mồi dữ liệu cấu hình mẫu default-ui.json vào Carbon Fields
     */
    public function seed_default_configuration()
    {
        // 1. Chỉ Admin tối cao mới được phép thực thi seeder
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!function_exists('carbon_set_theme_option') || !function_exists('carbon_get_theme_option')) {
            return;
        }

        // 2. Giáp bảo vệ kép: Kiểm tra cờ seeding phiên bản 5
        $is_seeded = get_option('zalo_miniapp_cf_seeded_v5');

        if (!$is_seeded) {
            $template_file = ZALO_MINIAPP_CORE_PATH . 'templates/default-ui.json';
            if (file_exists($template_file)) {
                $json_content = file_get_contents($template_file);
                $data = json_decode($json_content, true);
                if (is_array($data)) {
                    global $wpdb;

                    // 3. Sử dụng tiền tố DB động để dọn dẹp các option cũ tránh xung đột
                    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_miniapp_pages%' OR option_name LIKE 'miniapp_pages%'");
                    $wpdb->query($wpdb->prepare(
                        "DELETE FROM {$wpdb->options} WHERE option_name IN (%s, %s, %s, %s, %s, %s, %s)",
                        '_miniapp_name', '_miniapp_logo', '_miniapp_primary_color', '_miniapp_entry_page',
                        '_station_address', '_station_phone', '_station_map_url'
                    ));

                    // Nạp dữ liệu mẫu chuẩn Carbon Fields
                    carbon_set_theme_option('miniapp_name', $data['miniapp_name'] ?? 'Công an Xã Cần Đước');
                    carbon_set_theme_option('miniapp_logo', $data['miniapp_logo'] ?? '');
                    carbon_set_theme_option('miniapp_primary_color', $data['miniapp_primary_color'] ?? '#2D58D7');
                    carbon_set_theme_option('miniapp_entry_page', $data['miniapp_entry_page'] ?? 'home');
                    carbon_set_theme_option('station_address', $data['station_address'] ?? '12 Đường Quốc Lộ 50, Thị trấn Cần Đước, Huyện Cần Đước, Long An');
                    carbon_set_theme_option('station_phone', $data['station_phone'] ?? '0272.3881.213');
                    carbon_set_theme_option('station_map_url', $data['station_map_url'] ?? 'https://maps.google.com/?q=Cong+an+huyen+Can+Duoc+Long+An');
                    carbon_set_theme_option('miniapp_pages', $data['miniapp_pages'] ?? []);
                    carbon_set_theme_option('zalo_oa_config', $data['zalo_oa_config'] ?? []);

                    // Đánh dấu đã mồi thành công
                    update_option('zalo_miniapp_cf_seeded_v5', 1);

                    // 4. Giải phóng cache tránh dữ liệu cũ trong bộ nhớ đệm
                    wp_cache_flush();

                    // Đặt cờ yêu cầu biên dịch cấu hình tĩnh ui-config.json ở bước tiếp theo
                    update_option('zalo_miniapp_need_build', 1);
                }
            }
        }
    }

    /**
     * Mồi dữ liệu mẫu (1-2 cái mỗi loại) cho 5 Custom Post Types
     */
    public function seed_mock_data()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $seeded = get_option('zalo_miniapp_mock_data_seeded_v5');
        if ($seeded) {
            return;
        }

        // --- 1. Tin tức (zalo_news) ---
        $news_items = [
            [
                'title'   => 'Tuyên truyền phòng chống tội phạm công nghệ cao và lừa đảo qua mạng',
                'content' => 'Hiện nay, các đối tượng lừa đảo sử dụng nhiều thủ đoạn tinh vi như giả danh cơ quan công an, viện kiểm sát báo vi phạm giao thông hoặc tài khoản ngân hàng liên quan đến tội phạm. Công an xã khuyến cáo người dân không cung cấp OTP, không chuyển tiền cho người lạ và báo ngay cho công an xã qua hotline 0272.3881.213 khi có dấu hiệu nghi vấn.',
            ],
            [
                'title'   => 'Hướng dẫn cài đặt và đăng ký tài khoản định danh điện tử VNeID mức độ 2',
                'content' => 'Để tạo điều kiện thuận lợi cho công dân trong thực hiện các thủ tục hành chính, Công an xã Cần Đước tổ chức hướng dẫn hỗ trợ người dân đăng ký định danh điện tử VNeID mức độ 2 tại Trụ sở Công an xã vào tất cả các ngày trong tuần. Người dân khi đi cần mang theo căn cước công dân gắn chíp và thẻ bảo hiểm y tế, giấy phép lái xe để tích hợp.',
            ]
        ];

        foreach ($news_items as $item) {
            wp_insert_post([
                'post_title'   => $item['title'],
                'post_content' => $item['content'],
                'post_type'    => 'zalo_news',
                'post_status'  => 'publish'
            ]);
        }

        // --- 2. Cán bộ CSKV (zalo_officer) ---
        $officer_items = [
            [
                'title' => 'Đại úy Nguyễn Minh Tiến',
                'meta'  => [
                    'officer_phone' => '0912.345.678',
                    'officer_area'  => 'Ấp 1, Ấp 2 - Xã Cần Đước'
                ]
            ],
            [
                'title' => 'Thượng úy Lê Hoàng Nam',
                'meta'  => [
                    'officer_phone' => '0988.765.432',
                    'officer_area'  => 'Ấp 3, Ấp 4 - Xã Cần Đước'
                ]
            ]
        ];

        foreach ($officer_items as $item) {
            $post_id = wp_insert_post([
                'post_title'  => $item['title'],
                'post_type'   => 'zalo_officer',
                'post_status' => 'publish'
            ]);
            if (!is_wp_error($post_id)) {
                foreach ($item['meta'] as $key => $val) {
                    update_post_meta($post_id, '_' . $key, $val);
                    update_post_meta($post_id, $key, $val);
                }
            }
        }

        // --- 3. Lịch làm việc (zalo_schedule) ---
        $schedule_items = [
            [
                'title' => 'Lịch trực ban tiếp dân ấp 1 và ấp 2',
                'meta'  => [
                    'schedule_date'     => '2026-05-25',
                    'schedule_time'     => 'Sáng: 07:30 - 11:30, Chiều: 13:30 - 17:00',
                    'schedule_officer'  => 'Đại úy Nguyễn Minh Tiến',
                    'schedule_phone'    => '0912.345.678',
                    'schedule_location' => 'Phòng tiếp dân - Trụ sở Công an xã',
                    'schedule_notes'    => 'Tiếp nhận hồ sơ đăng ký tạm trú, thường trú và giải quyết phản ánh kiến nghị.'
                ]
            ],
            [
                'title' => 'Lịch tuần tra kiểm soát ban đêm địa bàn',
                'meta'  => [
                    'schedule_date'     => '2026-05-26',
                    'schedule_time'     => 'Đêm: 21:00 - 01:00',
                    'schedule_officer'  => 'Thượng úy Lê Hoàng Nam',
                    'schedule_phone'    => '0988.765.432',
                    'schedule_location' => 'Tuyến đường Quốc lộ 50 và các đường liên ấp',
                    'schedule_notes'    => 'Tuần tra đảm bảo an ninh trật tự, phòng ngừa trộm cắp và tệ nạn xã hội.'
                ]
            ]
        ];

        foreach ($schedule_items as $item) {
            $post_id = wp_insert_post([
                'post_title'  => $item['title'],
                'post_type'   => 'zalo_schedule',
                'post_status' => 'publish'
            ]);
            if (!is_wp_error($post_id)) {
                foreach ($item['meta'] as $key => $val) {
                    update_post_meta($post_id, '_' . $key, $val);
                    update_post_meta($post_id, $key, $val);
                }
            }
        }

        // --- 4. Hỏi đáp FAQ (zalo_faq) ---
        $faq_items = [
            [
                'title' => 'Làm thế nào để đăng ký tạm trú tại xã Cần Đước?',
                'meta'  => [
                    'faq_status' => 'approved',
                    'faq_answer' => 'Người dân có thể đến trực tiếp Trụ sở Công an xã Cần Đước gặp Cảnh sát khu vực phụ trách địa bàn để nộp hồ sơ (gồm tờ khai thay đổi thông tin cư trú, giấy tờ chứng minh chỗ ở hợp pháp) hoặc thực hiện đăng ký trực tuyến qua Cổng dịch vụ công Bộ Công an.'
                ]
            ],
            [
                'title' => 'Thời gian tiếp công dân giải quyết thủ tục hành chính là khi nào?',
                'meta'  => [
                    'faq_status' => 'approved',
                    'faq_answer' => 'Bộ phận tiếp dân Công an xã Cần Đước làm việc từ thứ Hai đến thứ Sáu hàng tuần (Sáng từ 07:30 - 11:30, Chiều từ 13:30 - 17:00). Thứ Bảy chỉ trực giải quyết các trường hợp khẩn cấp.'
                ]
            ]
        ];

        foreach ($faq_items as $item) {
            $post_id = wp_insert_post([
                'post_title'  => $item['title'],
                'post_type'   => 'zalo_faq',
                'post_status' => 'publish'
            ]);
            if (!is_wp_error($post_id)) {
                foreach ($item['meta'] as $key => $val) {
                    update_post_meta($post_id, '_' . $key, $val);
                    update_post_meta($post_id, $key, $val);
                }
            }
        }

        // --- 5. Phản ánh hiện trường (zalo_report) ---
        $report_items = [
            [
                'title'   => 'Tin báo phản ánh tụ tập đua xe trái phép',
                'content' => 'Đêm qua xuất hiện một nhóm khoảng 10 thanh niên tụ tập nẹt pô, chạy xe tốc độ cao tại khu vực ngã tư chợ xã gây mất trật tự.',
                'meta'    => [
                    'report_status'     => 'pending',
                    'reporter_name'     => 'Nguyễn Văn Hùng',
                    'reporter_phone'    => '0909123456',
                    'report_gps'        => '10.5187,106.5821',
                    'reporter_zalo_id'  => 'zalo_user_1',
                    'internal_notes'    => 'Chuyển Đại úy Nguyễn Minh Tiến xác minh hiện trường và trích xuất camera.'
                ]
            ],
            [
                'title'   => 'Phản ánh bãi rác tự phát gây ô nhiễm môi trường',
                'content' => 'Tại khu vực ven đường liên ấp 3 có người dân đổ rác thải sinh hoạt bừa bãi gây mùi hôi thối khó chịu.',
                'meta'    => [
                    'report_status'     => 'resolved',
                    'reporter_name'     => 'Trần Thị Lan',
                    'reporter_phone'    => '0918765432',
                    'report_gps'        => '10.5122,106.5795',
                    'reporter_zalo_id'  => 'zalo_user_2',
                    'internal_notes'    => 'Đã phối hợp với Ủy ban nhân dân xã dọn dẹp bãi rác và cắm biển cấm đổ rác.'
                ]
            ]
        ];

        foreach ($report_items as $item) {
            $post_id = wp_insert_post([
                'post_title'   => $item['title'],
                'post_content' => $item['content'],
                'post_type'    => 'zalo_report',
                'post_status'  => 'publish'
            ]);
            if (!is_wp_error($post_id)) {
                foreach ($item['meta'] as $key => $val) {
                    update_post_meta($post_id, '_' . $key, $val);
                    update_post_meta($post_id, $key, $val);
                }
            }
        }

        update_option('zalo_miniapp_mock_data_seeded_v5', 1);
        wp_cache_flush();
    }

    /**
     * Ghi đè trang chủ WordPress ngoài Frontend bằng landing page chuyên nghiệp
     */
    public function override_frontend_homepage($template)
    {
        if (is_front_page() || is_home()) {
            $landing_page = ZALO_MINIAPP_CORE_PATH . 'templates/landing-page.php';
            if (file_exists($landing_page)) {
                return $landing_page;
            }
        }
        return $template;
    }

    /**
     * Ẩn các menu không cần thiết đối với tài khoản không phải Administrator (manage_options)
     */
    public function hide_unused_admin_menus()
    {
        if (current_user_can('manage_options')) {
            return;
        }

        remove_menu_page('edit.php'); // Bài viết
        remove_menu_page('edit.php?post_type=page'); // Trang
        remove_menu_page('edit-comments.php'); // Phản hồi / Bình luận
        remove_menu_page('themes.php'); // Giao diện
        remove_menu_page('plugins.php'); // Plugin
        remove_menu_page('users.php'); // Thành viên
        remove_menu_page('tools.php'); // Công cụ
        remove_menu_page('options-general.php'); // Cài đặt
    }

    /**
     * Thêm CSS tùy biến trang đăng nhập
     */
    public function custom_login_styles()
    {
        $logo_url = plugins_url('templates/assets/logo_cong_an.png', __FILE__);
        ?>
        <style type="text/css">
            body.login {
                background: linear-gradient(135deg, #1E40AF 0%, #2D58D7 100%) !important;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }
            #login {
                padding: 40px 20px !important;
                width: 380px !important;
            }
            #login h1 a, .login h1 a {
                background-image: url('<?php echo esc_url($logo_url); ?>') !important;
                background-size: contain !important;
                background-position: center !important;
                width: 120px !important;
                height: 120px !important;
                margin-bottom: 20px !important;
            }
            .login form {
                background: rgba(255, 255, 255, 0.95) !important;
                backdrop-filter: blur(10px) !important;
                border-radius: 16px !important;
                border: none !important;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
                padding: 30px !important;
            }
            .login form .input, .login input[type=text] {
                border-radius: 8px !important;
                border: 1px solid #E2E8F0 !important;
                padding: 10px 12px !important;
                font-size: 16px !important;
            }
            .login form .input:focus, .login input[type=text]:focus {
                border-color: #2D58D7 !important;
                box-shadow: 0 0 0 3px rgba(45, 88, 215, 0.2) !important;
            }
            .wp-core-ui .button-primary {
                background: #2D58D7 !important;
                border-color: #2D58D7 !important;
                border-radius: 8px !important;
                font-weight: 600 !important;
                padding: 6px 20px !important;
                height: auto !important;
                font-size: 15px !important;
                transition: all 0.2s ease-in-out !important;
            }
            .wp-core-ui .button-primary:hover {
                background: #1E40AF !important;
                border-color: #1E40AF !important;
                transform: translateY(-1px) !important;
            }
            .login #nav a, .login #backtoblog a {
                color: #FFFFFF !important;
                font-weight: 500 !important;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2) !important;
            }
            .login #nav a:hover, .login #backtoblog a:hover {
                color: #E2E8F0 !important;
            }
        </style>
        <?php
    }

    /**
     * Thay đổi đường dẫn khi click vào logo trang login về trang chủ
     */
    public function custom_login_logo_url()
    {
        return home_url();
    }

    /**
     * Thay đổi title của logo trang login
     */
    public function custom_login_logo_title()
    {
        return get_bloginfo('name');
    }
}

// Initialize the plugin
$zalo_miniapp_core = new Zalo_MiniApp_Core();

// Activation Hook (Đã fix lỗi CPT & Phân quyền Admin)
register_activation_hook(__FILE__, function () {
    // 1. Load và Đăng ký CPT trước khi flush
    require_once ZALO_MINIAPP_CORE_PATH . 'includes/class-cpt-registry.php';
    if (class_exists('Zalo_MiniApp_CPT_Registry')) {
        $cpt = new Zalo_MiniApp_CPT_Registry();
        $cpt->register();
    }

    // 2. KÍCH HOẠT QUYỀN CHO ADMIN (Giải quyết lỗi Capability Lockout)
    $role = get_role('administrator');
    if ($role) {
        $cpts = ['report', 'news', 'officer', 'schedule', 'faq'];
        foreach ($cpts as $type) {
            $caps = [
                "edit_zalo_{$type}",
                "read_zalo_{$type}",
                "delete_zalo_{$type}",
                "edit_zalo_{$type}s",
                "edit_others_zalo_{$type}s",
                "publish_zalo_{$type}s",
                "read_private_zalo_{$type}s",
                "delete_zalo_{$type}s",
                "delete_private_zalo_{$type}s",
                "delete_published_zalo_{$type}s",
                "delete_others_zalo_{$type}s",
                "edit_private_zalo_{$type}s",
                "edit_published_zalo_{$type}s"
            ];
            foreach ($caps as $cap) {
                $role->add_cap($cap);
            }
        }
    }

    // 3. ĐĂNG KÝ ROLE CÁN BỘ TRỰC BAN (zalo_officer)
    $editor = get_role('editor');
    if ($editor) {
        $caps = $editor->capabilities;
        $cpts = ['report', 'news', 'officer', 'schedule', 'faq'];
        foreach ($cpts as $type) {
            $cpt_caps = [
                "edit_zalo_{$type}",
                "read_zalo_{$type}",
                "delete_zalo_{$type}",
                "edit_zalo_{$type}s",
                "edit_others_zalo_{$type}s",
                "publish_zalo_{$type}s",
                "read_private_zalo_{$type}s",
                "delete_zalo_{$type}s",
                "delete_private_zalo_{$type}s",
                "delete_published_zalo_{$type}s",
                "delete_others_zalo_{$type}s",
                "edit_private_zalo_{$type}s",
                "edit_published_zalo_{$type}s"
            ];
            foreach ($cpt_caps as $cap) {
                $caps[$cap] = true;
            }
        }
        add_role('zalo_officer', 'Cán bộ Trực ban', $caps);
        
        // Cập nhật lại quyền nếu vai trò đã tồn tại
        $zalo_officer = get_role('zalo_officer');
        if ($zalo_officer) {
            foreach ($caps as $cap => $grant) {
                if ($grant) {
                    $zalo_officer->add_cap($cap);
                }
            }
        }
    }

    // 4. Flush rewrite rules
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

// Admin notice nếu quên cài Carbon Fields
add_action('admin_notices', function () {
    if (!class_exists('\Carbon_Fields\Carbon_Fields')) {
        echo '<div class="error"><p><strong>Cảnh báo (Zalo Mini App):</strong> Thư viện Carbon Fields chưa được cài đặt! Vui lòng chạy <code>composer install</code> trong thư mục plugin.</p></div>';
    }
});