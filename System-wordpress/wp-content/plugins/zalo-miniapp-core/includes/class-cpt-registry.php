<?php
/**
 * Register Custom Post Types and Taxonomies (Enterprise Headless Grade)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zalo_MiniApp_CPT_Registry
{

    public function register()
    {
        // Tạo Menu Cha trước
        add_action('admin_menu', array($this, 'register_parent_menu'), 9);

        // Xử lý export/import khi submit
        add_action('admin_init', array($this, 'handle_export_request'));
        add_action('admin_init', array($this, 'handle_import_request'));

        // Đăng ký các CPT
        $this->register_zalo_report();
        $this->register_zalo_news();
        $this->register_zalo_officer();
        $this->register_zalo_schedule();
        $this->register_zalo_faq();
    }

    public function register_parent_menu()
    {
        add_menu_page(
            'Quản lý Zalo App',
            'Zalo App',
            'edit_posts',
            'zalo-miniapp',
            function () {
                if (class_exists('Zalo_MiniApp_SDUI_Builder')) {
                    Zalo_MiniApp_SDUI_Builder::render_main_dashboard_page();
                } else {
                    echo '<div class="wrap">';
                    echo '<h1>Hệ thống Quản trị Zalo Mini App</h1>';
                    echo '<p>Hệ thống Backend Headless đã hoạt động ổn định. Vui lòng chọn các tính năng quản lý ở menu bên trái.</p>';
                    echo '</div>';
                }
            },
            'dashicons-smartphone',
            20
        );

        add_submenu_page(
            'zalo-miniapp',
            'Nhập/Xuất cấu hình',
            'Nhập/Xuất cấu hình',
            'manage_options',
            'zalo-miniapp-import-export',
            array($this, 'render_import_export_page')
        );
    }

    public function handle_export_request()
    {
        if (isset($_GET['action']) && $_GET['action'] === 'zalo_miniapp_export' && current_user_can('manage_options')) {
            check_admin_referer('zalo_miniapp_export_action', 'zalo_miniapp_export_nonce');

            $config_data = array(
                'miniapp_name'             => function_exists('carbon_get_theme_option') ? carbon_get_theme_option('miniapp_name') : get_option('_miniapp_name'),
                'miniapp_logo'             => function_exists('carbon_get_theme_option') ? carbon_get_theme_option('miniapp_logo') : get_option('_miniapp_logo'),
                'miniapp_primary_color'    => function_exists('carbon_get_theme_option') ? carbon_get_theme_option('miniapp_primary_color') : get_option('_miniapp_primary_color'),
                'miniapp_version'          => function_exists('carbon_get_theme_option') ? carbon_get_theme_option('miniapp_version') : get_option('_miniapp_version'),
                'miniapp_entry_page'       => function_exists('carbon_get_theme_option') ? carbon_get_theme_option('miniapp_entry_page') : get_option('_miniapp_entry_page'),
                'station_address'          => function_exists('carbon_get_theme_option') ? carbon_get_theme_option('station_address') : get_option('_station_address'),
                'station_phone'            => function_exists('carbon_get_theme_option') ? carbon_get_theme_option('station_phone') : get_option('_station_phone'),
                'station_map_url'          => function_exists('carbon_get_theme_option') ? carbon_get_theme_option('station_map_url') : get_option('_station_map_url'),
                'faq_jaccard_threshold'    => function_exists('carbon_get_theme_option') ? carbon_get_theme_option('faq_jaccard_threshold') : get_option('_faq_jaccard_threshold'),
                'oa_report_notify_enabled' => function_exists('carbon_get_theme_option') ? carbon_get_theme_option('oa_report_notify_enabled') : get_option('_oa_report_notify_enabled'),
                'oa_report_template_id'    => function_exists('carbon_get_theme_option') ? carbon_get_theme_option('oa_report_template_id') : get_option('_oa_report_template_id'),
                'miniapp_pages'            => function_exists('carbon_get_theme_option') ? carbon_get_theme_option('miniapp_pages') : get_option('_miniapp_pages'),
                'zalo_oa_config'           => function_exists('carbon_get_theme_option') ? carbon_get_theme_option('zalo_oa_config') : get_option('_zalo_oa_config'),
            );

            $json_data = wp_json_encode($config_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $filename = 'zalo-miniapp-config-' . date('Y-m-d') . '.json';

            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            echo $json_data;
            exit;
        }
    }

    public function handle_import_request()
    {
        if (isset($_POST['zalo_miniapp_import_submit']) && current_user_can('manage_options')) {
            check_admin_referer('zalo_miniapp_import_action', 'zalo_miniapp_import_nonce');

            $json_content = '';
            if (!empty($_FILES['import_file']['tmp_name'])) {
                $json_content = file_get_contents($_FILES['import_file']['tmp_name']);
            } elseif (!empty($_POST['import_text'])) {
                $json_content = stripslashes($_POST['import_text']);
            }

            if (empty($json_content)) {
                set_transient('zalo_miniapp_import_error', 'Vui lòng chọn file JSON hoặc dán nội dung cấu hình.', 30);
                wp_safe_redirect(admin_url('admin.php?page=zalo-miniapp-import-export'));
                exit;
            }

            $data = json_decode($json_content, true);
            if ($data === null || !is_array($data)) {
                set_transient('zalo_miniapp_import_error', 'Định dạng JSON không hợp lệ.', 30);
                wp_safe_redirect(admin_url('admin.php?page=zalo-miniapp-import-export'));
                exit;
            }

            // Dọn dẹp cơ sở dữ liệu cũ trước khi nạp cấu hình mới tránh xung đột/rác dữ liệu
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_miniapp_pages%' OR option_name LIKE 'miniapp_pages%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_zalo_oa_config%' OR option_name LIKE 'zalo_oa_config%'");
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name IN (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                '_miniapp_name', '_miniapp_logo', '_miniapp_primary_color', '_miniapp_version', '_miniapp_entry_page',
                '_station_address', '_station_phone', '_station_map_url', '_faq_jaccard_threshold',
                '_oa_report_notify_enabled', '_oa_report_template_id'
            ));

            $keys = [
                'miniapp_name',
                'miniapp_logo',
                'miniapp_primary_color',
                'miniapp_version',
                'miniapp_entry_page',
                'station_address',
                'station_phone',
                'station_map_url',
                'faq_jaccard_threshold',
                'oa_report_notify_enabled',
                'oa_report_template_id',
                'miniapp_pages',
                'zalo_oa_config'
            ];

            if (function_exists('carbon_set_theme_option')) {
                foreach ($keys as $key) {
                    if (isset($data[$key])) {
                        carbon_set_theme_option($key, $data[$key]);
                    }
                }
            } else {
                foreach ($keys as $key) {
                    if (isset($data[$key])) {
                        update_option('_' . $key, $data[$key]);
                    }
                }
            }

            // Đồng bộ bộ nhớ đệm
            wp_cache_flush();

            // Kích hoạt việc sinh lại file JSON tĩnh ngay lập tức
            if (class_exists('Zalo_MiniApp_Security_Cache')) {
                $security = new Zalo_MiniApp_Security_Cache();
                $security->generate_ui_config_json();
            } else {
                update_option('zalo_miniapp_need_build', 1);
            }

            set_transient('zalo_miniapp_import_success', 'Đã nhập cấu hình và tự động lên lịch biên dịch JSON thành công!', 30);
            wp_safe_redirect(admin_url('admin.php?page=zalo-miniapp-import-export'));
            exit;
        }
    }

    public function render_import_export_page()
    {
        $error = get_transient('zalo_miniapp_import_error');
        $success = get_transient('zalo_miniapp_import_success');
        delete_transient('zalo_miniapp_import_error');
        delete_transient('zalo_miniapp_import_success');

        echo '<div class="wrap">';
        echo '<h1>Nhập/Xuất cấu hình Giao diện Zalo Mini App</h1>';
        echo '<p>Trang này cho phép bạn lưu trữ cấu hình giao diện hiện tại của Zalo Mini App dưới dạng file JSON hoặc nạp cấu hình mới.</p>';

        if ($error) {
            echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
        }
        if ($success) {
            echo '<div class="notice notice-success"><p>' . esc_html($success) . '</p></div>';
        }

        echo '<div style="display: flex; gap: 40px; margin-top: 20px;">';

        // Khối Xuất (Export)
        echo '<div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
        echo '<h2>Xuất cấu hình (Export)</h2>';
        echo '<p>Tải về file JSON chứa toàn bộ cấu hình giao diện (SDUI), menu, hotline, và Zalo OA token hiện tại.</p>';
        
        $export_url = wp_nonce_url(
            admin_url('admin.php?action=zalo_miniapp_export'),
            'zalo_miniapp_export_action',
            'zalo_miniapp_export_nonce'
        );
        echo '<p style="margin-top: 30px;"><a href="' . esc_url($export_url) . '" class="button button-primary button-large" style="display: inline-flex; align-items: center; justify-content: center; height: 46px; padding: 0 24px; font-size: 14px; font-weight: 600;"><span class="dashicons dashicons-download" style="margin-right: 8px; margin-top: 4px;"></span> TẢI FILE CẤU HÌNH (.JSON)</a></p>';
        echo '</div>';

        // Khối Nhập (Import)
        echo '<div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
        echo '<h2>Nhập cấu hình (Import)</h2>';
        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field('zalo_miniapp_import_action', 'zalo_miniapp_import_nonce');

        echo '<p>Chọn file JSON cấu hình đã lưu trước đó:</p>';
        echo '<p><input type="file" name="import_file" accept=".json" class="button" style="width: 100%; border: 1px dashed #ccc; padding: 10px; background: #fbfbfb;"></p>';

        echo '<p style="margin: 20px 0 10px;">Hoặc dán trực tiếp nội dung JSON vào đây:</p>';
        echo '<p><textarea name="import_text" rows="8" placeholder=\'{"miniapp_name": "Công an Xã...", ...}\' style="width: 100%; font-family: monospace; font-size: 12px; border: 1px solid #ccd0d4; border-radius: 4px; padding: 10px;"></textarea></p>';

        echo '<p style="margin-top: 20px;"><button type="submit" name="zalo_miniapp_import_submit" class="button button-secondary button-large" style="display: inline-flex; align-items: center; justify-content: center; height: 46px; padding: 0 24px; font-size: 14px; font-weight: 600;"><span class="dashicons dashicons-upload" style="margin-right: 8px; margin-top: 4px;"></span> NHẬP CẤU HÌNH</button></p>';
        echo '</form>';
        echo '</div>';

        echo '</div>'; // End flex container
        echo '</div>'; // End wrap
    }

    private function register_zalo_report()
    {
        $args = array(
            'labels' => array(
                'name' => 'Phản ánh',
                'singular_name' => 'Phản ánh',
                'menu_name' => 'Phản ánh HT',
                'all_items' => 'Tất cả Phản ánh'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'zalo-miniapp', // Nhét vào Parent Menu
            'supports' => array('title', 'author', 'custom-fields'),

            // SECURITY: Phân quyền tuyệt đối
            'capability_type' => 'zalo_report',
            'map_meta_cap' => true,
            'capabilities' => array(
                'edit_post' => 'edit_zalo_report',
                'read_post' => 'read_zalo_report',
                'delete_post' => 'delete_zalo_report',
                'edit_posts' => 'edit_zalo_reports',
                'edit_others_posts' => 'edit_others_zalo_reports',
                'publish_posts' => 'publish_zalo_reports',
                'read_private_posts' => 'read_private_zalo_reports',
                'create_posts' => 'do_not_allow', // Khóa tạo mới từ Admin
            ),

            // SECURITY: Tối ưu Headless & GovTech
            'show_in_rest' => false,
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'delete_with_user' => false, // Giữ data khi user bị xóa
        );
        register_post_type('zalo_report', $args);
    }

    private function register_zalo_news()
    {
        $args = array(
            'labels' => array(
                'name' => 'Tin tức ANTT',
                'singular_name' => 'Tin tức',
                'menu_name' => 'Tin tức'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'zalo-miniapp',
            'supports' => array('title', 'thumbnail', 'editor', 'custom-fields'), // Cần editor cho Tin tức

            'capability_type' => 'post',
            'map_meta_cap' => true,

            'show_in_rest' => false,
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'delete_with_user' => false,
        );
        register_post_type('zalo_news', $args);
    }

    private function register_zalo_officer()
    {
        $args = array(
            'labels' => array(
                'name' => 'Cán bộ CSKV',
                'singular_name' => 'Cán bộ'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'zalo-miniapp',
            'supports' => array('title', 'thumbnail', 'custom-fields'),

            'capability_type' => 'post',
            'map_meta_cap' => true,

            'show_in_rest' => false,
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'delete_with_user' => false,
        );
        register_post_type('zalo_officer', $args);
    }

    private function register_zalo_schedule()
    {
        $args = array(
            'labels' => array(
                'name' => 'Lịch làm việc',
                'singular_name' => 'Lịch làm việc'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'zalo-miniapp',
            'supports' => array('title', 'custom-fields'),

            'capability_type' => 'post',
            'map_meta_cap' => true,

            'show_in_rest' => false,
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'delete_with_user' => false,
        );
        register_post_type('zalo_schedule', $args);
    }

    private function register_zalo_faq()
    {
        $args = array(
            'labels' => array(
                'name' => 'Hỏi đáp (FAQ)',
                'singular_name' => 'Câu hỏi'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'zalo-miniapp',
            'supports' => array('title', 'editor', 'custom-fields'),
            'hierarchical' => true, // Cho phép làm thư mục cha/con

            'capability_type' => 'post',
            'map_meta_cap' => true,

            'show_in_rest' => false,
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'delete_with_user' => false,
        );
        register_post_type('zalo_faq', $args);
    }
}