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
            'manage_options',
            'zalo-miniapp',
            function () {
                // Callback fix lỗi trắng trang
                echo '<div class="wrap">';
                echo '<h1>Hệ thống Quản trị Zalo Mini App</h1>';
                echo '<p>Hệ thống Backend Headless đã hoạt động ổn định. Vui lòng chọn các tính năng quản lý ở menu bên trái.</p>';
                echo '</div>';
            },
            'dashicons-smartphone',
            20
        );
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

            'capability_type' => 'zalo_news',
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

            'capability_type' => 'zalo_officer',
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

            'capability_type' => 'zalo_schedule',
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

            'capability_type' => 'zalo_faq',
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