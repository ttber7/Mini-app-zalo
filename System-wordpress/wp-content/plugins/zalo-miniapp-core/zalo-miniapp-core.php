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

        // Boot Carbon Fields (Chuẩn cho Plugin)
        add_action('plugins_loaded', array($this, 'boot_carbon_fields'));

        // Initialize components
        add_action('init', array($this, 'init_components'), 10);
        add_action('rest_api_init', array($this, 'init_api'));
    }

    private function load_dependencies()
    {
        $files = [
            'includes/class-cpt-registry.php',
            'includes/class-sdui-builder.php',
            'includes/class-api-endpoints.php',
            'includes/class-security-cache.php',
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
        // if (class_exists('Zalo_MiniApp_CPT_Registry')) {
        //     $this->components['cpt'] = new Zalo_MiniApp_CPT_Registry();
        //     $this->components['cpt']->register();
        // }

        // if (class_exists('Zalo_MiniApp_SDUI_Builder')) {
        //     $this->components['builder'] = new Zalo_MiniApp_SDUI_Builder();
        //     $this->components['builder']->init();
        // }

        // if (class_exists('Zalo_MiniApp_Security_Cache')) {
        //     $this->components['security'] = new Zalo_MiniApp_Security_Cache();
        //     $this->components['security']->init();
        // }
        // Chỉ để lại CPT ở đây vì CPT bắt buộc phải chạy ở trạm 'init'
        if (class_exists('Zalo_MiniApp_CPT_Registry')) {
            $this->components['cpt'] = new Zalo_MiniApp_CPT_Registry();
            $this->components['cpt']->register();
        }
    }

    public function init_api()
    {
        if (class_exists('Zalo_MiniApp_API_Endpoints')) {
            $api = new Zalo_MiniApp_API_Endpoints();
            $api->register_routes();
        }
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
                "read_private_zalo_{$type}s"
            ];
            foreach ($caps as $cap) {
                $role->add_cap($cap);
            }
        }
    }

    // 3. Flush rewrite rules
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
