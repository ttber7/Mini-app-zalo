<?php
/**
 * Carbon Fields Builder for SDUI
 */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

if (!defined('ABSPATH')) {
    exit;
}

class Zalo_MiniApp_SDUI_Builder
{

    public function init()
    {
        add_action('carbon_fields_register_fields', array($this, 'register_options_page'));
        add_action('carbon_fields_register_fields', array($this, 'register_cpt_fields'));

        // Disable Gutenberg for our CPTs to keep UI clean
        add_filter('use_block_editor_for_post_type', array($this, 'disable_gutenberg'), 10, 2);

        // Hook trigger khi Admin bấm Lưu Cấu hình Zalo App
        add_action('carbon_fields_theme_options_container_saved', array($this, 'trigger_json_build'));
    }

    public function disable_gutenberg($current_status, $post_type)
    {
        $disabled_cpts = array('zalo_report', 'zalo_news', 'zalo_officer', 'zalo_schedule', 'zalo_faq');
        if (in_array($post_type, $disabled_cpts)) {
            return false;
        }
        return $current_status;
    }

    public function trigger_json_build()
    {
        do_action('zalo_miniapp_build_json');
    }

    public function register_options_page()
    {
        if (!class_exists('\Carbon_Fields\Container')) {
            return;
        }

        Container::make('theme_options', 'Cấu hình Zalo App')
            ->set_page_parent('zalo-miniapp')
            ->set_page_file('zalo-app-config')
            ->set_icon('dashicons-smartphone')
            ->add_fields(array(
                Field::make('text', 'miniapp_name', 'Tên Ứng Dụng')->set_required(true),
                Field::make('image', 'miniapp_logo', 'Logo Ứng Dụng'),
                Field::make('color', 'miniapp_primary_color', 'Màu Chủ Đạo')->set_default_value('#0068ff'),

                Field::make('text', 'miniapp_entry_page', 'Mã Trang Chủ (Entry Page)')
                    ->set_default_value('home')->set_required(true),

                // Page Builder
                Field::make('complex', 'miniapp_pages', 'Quản lý Trang (Pages)')
                    ->set_layout('tabbed-horizontal')
                    ->add_fields(array(
                        Field::make('text', 'page_id', 'Mã Trang (ID)')->set_required(true),
                        Field::make('text', 'page_title', 'Tiêu đề Trang'),

                        Field::make('multiselect', 'visible_for', 'Quyền xem')
                            ->set_options(array('citizen' => 'Người dân', 'officer' => 'Cán bộ', 'admin' => 'Admin'))
                            ->set_default_value(array('citizen')),

                        Field::make('complex', 'page_components', 'Components (Kéo thả)')
                            ->set_layout('tabbed-vertical')

                            // Component: Banner
                            ->add_fields('banner', 'Banner', array(
                                Field::make('text', 'id', 'Component ID (VD: banner_01)')->set_required(true), // ĐÃ FIX
                                Field::make('image', 'image_url', 'Hình ảnh Banner'),
                                Field::make('select', 'action_type', 'Hành động khi click')
                                    ->set_options(array('' => 'Không có', 'navigate' => 'Mở trang', 'open_url' => 'Mở Link Web')),
                                Field::make('text', 'action_value', 'Giá trị hành động')
                                    ->set_help_text('Nếu mở trang: Nhập Page ID. Nếu mở web: Nhập https://...') // ĐÃ FIX
                            ))

                            // Component: Grid Menu
                            ->add_fields('grid_menu', 'Menu Dạng Lưới', array(
                                Field::make('text', 'id', 'Component ID (VD: menu_01)')->set_required(true), // ĐÃ FIX
                                Field::make('complex', 'items', 'Danh sách Menu')
                                    ->add_fields(array(
                                        Field::make('text', 'icon', 'Icon (Zalo Icon Class)'),
                                        Field::make('text', 'label', 'Nhãn'),
                                        Field::make('select', 'action_type', 'Loại hành động')
                                            ->set_options(array('navigate' => 'Mở trang', 'open_url' => 'Mở Link Web', 'call' => 'Gọi điện')),
                                        Field::make('text', 'action_value', 'Giá trị hành động')
                                            ->set_help_text('Mở trang -> Page ID | Mở Web -> Link | Gọi điện -> Số ĐT') // ĐÃ FIX
                                    ))
                            ))

                            // 1. KÊNH CHÍNH THỨC (Official Channel)
                            ->add_fields('official_channel', 'Kênh Zalo OA Chính Thức', array(
                                Field::make('text', 'id', 'Component ID (VD: oa_channel)')->set_required(true),
                                Field::make('text', 'oa_id', 'Zalo OA ID (Để mở Mini App từ OA)'),
                                Field::make('image', 'cover_image', 'Ảnh bìa (Cover)')->set_value_type('id'),
                            ))

                            // 2. LƯỚI THỐNG KÊ (Statistics Grid)
                            ->add_fields('statistics_grid', 'Thống Kê Xã', array(
                                Field::make('text', 'id', 'Component ID (VD: stats_01)')->set_required(true),
                                Field::make('text', 'population', 'Dân số')->set_default_value('0'),
                                Field::make('text', 'households', 'Hộ gia đình')->set_default_value('0'),
                                Field::make('text', 'area', 'Diện tích (km2)')->set_default_value('0'),
                                Field::make('text', 'party_members', 'Số Đảng viên')->set_default_value('0'),
                                Field::make('text', 'update_time', 'Tháng cập nhật (VD: Tháng 4/2026)'),
                            ))

                            // 3. DANH SÁCH KHẨN CẤP (Emergency List)
                            ->add_fields('emergency_list', 'Hotline Khẩn Cấp', array(
                                Field::make('text', 'id', 'Component ID (VD: emergency_01)')->set_required(true),
                                Field::make('complex', 'hotlines', 'Danh sách Số điện thoại')
                                    ->add_fields(array(
                                        Field::make('text', 'label', 'Tên Cơ quan (VD: Công an xã)'),
                                        Field::make('text', 'sub_label', 'Mô tả (VD: Báo cáo tội phạm)'),
                                        Field::make('text', 'phone', 'Số điện thoại')->set_required(true),
                                        Field::make('color', 'bg_color', 'Màu nền nút')->set_default_value('#1e3a8a'),
                                    ))
                            ))
                            // Component: Article List
                            ->add_fields('article_list', 'Danh sách Bài viết', array(
                                Field::make('text', 'id', 'Component ID (VD: list_news)')->set_required(true), // ĐÃ FIX
                                Field::make('text', 'title', 'Tiêu đề Danh sách'),
                                Field::make('text', 'data_source_key', 'Key Dữ liệu')->set_default_value('news_api')
                            ))

                            // Component: Form
                            ->add_fields('form', 'Biểu mẫu (Form)', array(
                                Field::make('text', 'id', 'Component ID (VD: form_report)')->set_required(true), // ĐÃ FIX
                                Field::make('text', 'api_submit', 'API Gửi dữ liệu (Key)')->set_default_value('submit_report_api'),
                                Field::make('complex', 'fields', 'Các trường nhập liệu')
                                    ->add_fields(array(
                                        Field::make('select', 'type', 'Loại Input')
                                            ->set_options(array('text' => 'Text', 'phone' => 'Số điện thoại', 'image' => 'Tải ảnh lên', 'location' => 'Lấy tọa độ', )),
                                        Field::make('text', 'id', 'Mã trường dữ liệu (Field ID)')->set_required(true), // ĐÃ FIX
                                        Field::make('text', 'label', 'Nhãn hiển thị (Label)'),
                                        Field::make('checkbox', 'required', 'Bắt buộc nhập?')
                                    ))
                            ))
                    ))
                    ->set_header_template('Trang: <%- page_title %> (<%- page_id %>)')
            ));
    }

    public function register_cpt_fields()
    {
        if (!class_exists('\Carbon_Fields\Container')) {
            return;
        }

        // Field cho Phản ánh hiện trường
        Container::make('post_meta', 'Chi tiết Phản ánh')
            ->where('post_type', '=', 'zalo_report')
            ->add_fields(array(
                Field::make('select', 'report_status', 'Trạng thái xử lý')
                    ->set_options(array('pending' => 'Chờ xử lý', 'processing' => 'Đang giải quyết', 'resolved' => 'Đã hoàn thành', 'rejected' => 'Từ chối'))
                    ->set_default_value('pending'),
                Field::make('text', 'reporter_name', 'Họ tên người gửi'),
                Field::make('text', 'reporter_phone', 'Số điện thoại'),
                Field::make('text', 'report_gps', 'Tọa độ GPS'),
                Field::make('image', 'report_image', 'Hình ảnh đính kèm')->set_value_type('url'),
                Field::make('textarea', 'internal_notes', 'Ghi chú nội bộ')
            ));

        // Field cho Cán bộ CSKV
        Container::make('post_meta', 'Thông tin Cán bộ')
            ->where('post_type', '=', 'zalo_officer')
            ->add_fields(array(
                Field::make('text', 'officer_phone', 'Số điện thoại'),
                Field::make('text', 'officer_area', 'Khu vực phụ trách')
            ));
    }
}