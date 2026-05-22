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
                Field::make('text', 'miniapp_version', 'Phiên bản Mini App (Version)')
                    ->set_default_value('1.0.0')
                    ->set_help_text('Phiên bản của cấu hình Mini App, ví dụ: 1.0.0. Thay đổi số này để refresh cache cấu hình phía Client.')
                    ->set_required(true),

                Field::make('text', 'miniapp_entry_page', 'Mã Trang Chủ (Entry Page)')
                    ->set_default_value('home')->set_required(true),

                // Địa chỉ & Điện thoại trực ban của Trụ sở
                Field::make('text', 'station_address', 'Địa chỉ Trụ sở'),
                Field::make('text', 'station_phone', 'Số điện thoại trực ban'),
                Field::make('text', 'station_map_url', 'Đường dẫn chỉ đường (Google Maps)'),

                // Cấu hình Hỏi đáp & Thông báo Zalo OA
                Field::make('text', 'faq_jaccard_threshold', 'Ngưỡng khớp FAQ tự động (Jaccard)')
                    ->set_default_value('0.45')
                    ->set_help_text('Nhập số từ 0.00 đến 1.00. Hệ thống tự động trả lời nếu câu hỏi người dân khớp với FAQ có sẵn từ ngưỡng này trở lên. Mặc định: 0.45')
                    ->set_required(true),

                Field::make('checkbox', 'oa_report_notify_enabled', 'Bật gửi thông báo đẩy phản ánh qua Zalo OA')
                    ->set_default_value(true)
                    ->set_help_text('Tự động gửi tin nhắn cho người dân khi trạng thái phản ánh hiện trường được cập nhật.'),

                Field::make('text', 'oa_report_template_id', 'Mã Template ID (Zalo OA)')
                    ->set_help_text('Nhập mã Template ID đã đăng ký trên Zalo OA để gửi tin nhắn Giao dịch. Để trống nếu muốn gửi tin nhắn CSKH dạng văn bản thường.'),

                // Zalo OA Config
                Field::make('complex', 'zalo_oa_config', 'Cấu hình Zalo OA (Nâng cao)')
                    ->set_layout('tabbed-horizontal')
                    ->set_max(1)
                    ->add_fields('zalo_oa_config', 'Cấu hình', array(
                        Field::make('text', 'app_id', 'Zalo App ID'),
                        Field::make('text', 'secret_key', 'Zalo App Secret Key'),
                        Field::make('text', 'oa_id', 'Zalo OA ID'),
                        Field::make('textarea', 'access_token', 'Access Token (Tự động cập nhật)')->set_rows(4),
                        Field::make('textarea', 'refresh_token', 'Refresh Token (Tự động cập nhật)')->set_rows(4),
                    )),

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

                            // Component: FAQ List (Danh sách Hỏi đáp)
                            ->add_fields('faq_list', 'Danh sách Hỏi đáp (FAQ)', array(
                                Field::make('text', 'id', 'Component ID (VD: faq_main)')->set_required(true),
                                Field::make('text', 'title', 'Tiêu đề Khối')->set_default_value('Câu hỏi thường gặp'),
                                Field::make('text', 'search_placeholder', 'Chữ gợi ý ô Tìm kiếm')->set_default_value('Nhập từ khóa cần tìm...'),
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

        // Field cho Hỏi đáp (FAQ)
        Container::make('post_meta', 'Quản lý Câu hỏi & Phê duyệt')
            ->where('post_type', '=', 'zalo_faq')
            ->add_fields(array(
                Field::make('select', 'faq_status', 'Trạng thái')
                    ->set_options(array('pending' => 'Chờ duyệt / Chờ trả lời', 'approved' => 'Đã duyệt / Đã trả lời'))
                    ->set_default_value('pending'),
                Field::make('textarea', 'faq_answer', 'Nội dung Câu trả lời (Dành cho người dân)')->set_rows(5),
                Field::make('text', 'faq_reporter_id', 'Zalo User ID (Người gửi)')->set_help_text('Hệ thống tự động lưu ID người gửi để báo tin khi câu hỏi được phê duyệt.'),
            ));

        // Field cho Lịch trực / làm việc (CPT zalo_schedule)
        Container::make('post_meta', 'Chi Tiết Ca Trực Ban')
            ->where('post_type', '=', 'zalo_schedule')
            ->add_fields(array(
                Field::make('date', 'schedule_date', 'Ngày trực / làm việc')
                    ->set_width(50)
                    ->set_required(true),
                Field::make('text', 'schedule_time', 'Ca trực / Thời gian')
                    ->set_width(50)
                    ->set_default_value('Sáng: 07:30 - 11:30, Chiều: 13:30 - 17:00')
                    ->set_required(true),
                Field::make('text', 'schedule_officer', 'Cán bộ trực / phụ trách')
                    ->set_width(50)
                    ->set_required(true),
                Field::make('text', 'schedule_phone', 'Số điện thoại liên hệ')
                    ->set_width(50),
                Field::make('text', 'schedule_location', 'Địa điểm trực / làm việc')
                    ->set_default_value('Trụ sở Công an xã')
                    ->set_required(true),
                Field::make('textarea', 'schedule_notes', 'Ghi chú / Phân công nhiệm vụ')
                    ->set_rows(3)
            ));
    }

    /**
     * Render the premium admin dashboard with live counters and report statistics.
     */
    public static function render_main_dashboard_page()
    {
        // 1. Get Counters
        $news_count = wp_count_posts('zalo_news')->publish ?? 0;
        $schedule_count = wp_count_posts('zalo_schedule')->publish ?? 0;

        // 2. Get Report Counts by Status
        $total_reports = (new \WP_Query(array(
            'post_type' => 'zalo_report',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids'
        )))->found_posts;

        $processing_reports = (new \WP_Query(array(
            'post_type' => 'zalo_report',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_report_status',
                    'value' => 'processing',
                    'compare' => '='
                )
            )
        )))->found_posts;

        $resolved_reports = (new \WP_Query(array(
            'post_type' => 'zalo_report',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_report_status',
                    'value' => 'resolved',
                    'compare' => '='
                )
            )
        )))->found_posts;

        $rejected_reports = (new \WP_Query(array(
            'post_type' => 'zalo_report',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_report_status',
                    'value' => 'rejected',
                    'compare' => '='
                )
            )
        )))->found_posts;

        $pending_reports = max(0, $total_reports - $processing_reports - $resolved_reports - $rejected_reports);

        $pct_pending = $total_reports > 0 ? round(($pending_reports / $total_reports) * 100) : 0;
        $pct_processing = $total_reports > 0 ? round(($processing_reports / $total_reports) * 100) : 0;
        $pct_resolved = $total_reports > 0 ? round(($resolved_reports / $total_reports) * 100) : 0;
        $pct_rejected = $total_reports > 0 ? round(($rejected_reports / $total_reports) * 100) : 0;

        // 3. Get FAQ Pending Approval Count
        $pending_faqs = count(get_posts(array(
            'post_type' => 'zalo_faq',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_faq_status',
                    'value' => 'pending',
                    'compare' => '='
                )
            )
        )));

        // 4. Get 7-day Trend Data
        $daily_counts = array();
        for ($i = 6; $i >= 0; $i--) {
            $timestamp = strtotime("-$i days");
            $date_label = date('d/m', $timestamp);
            
            $q = new \WP_Query(array(
                'post_type' => 'zalo_report',
                'post_status' => 'any',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'date_query' => array(
                    array(
                        'year'  => date('Y', $timestamp),
                        'month' => date('m', $timestamp),
                        'day'   => date('d', $timestamp),
                    ),
                ),
            ));
            $daily_counts[$date_label] = $q->found_posts;
        }
        $max_count = max(max($daily_counts), 5); // scale threshold minimum 5

        $logo_url = defined('ZALO_MINIAPP_CORE_URL') ? ZALO_MINIAPP_CORE_URL . 'templates/assets/logo_cong_an.png' : '';
        ?>
        <div class="wrap zalo-dashboard-wrap">
            <style type="text/css">
                .zalo-dashboard-wrap {
                    margin: 20px 20px 0 0;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                }
                .zalo-dashboard-header {
                    background: linear-gradient(135deg, #1E40AF 0%, #2D58D7 100%);
                    color: #fff;
                    padding: 30px;
                    border-radius: 16px;
                    margin-bottom: 30px;
                    display: flex;
                    align-items: center;
                    box-shadow: 0 10px 25px -5px rgba(45, 88, 215, 0.3);
                }
                .zalo-dashboard-logo {
                    width: 80px;
                    height: 80px;
                    background-image: url('<?php echo esc_url($logo_url); ?>');
                    background-size: contain;
                    background-repeat: no-repeat;
                    margin-right: 20px;
                }
                .zalo-dashboard-title h1 {
                    color: #fff !important;
                    font-size: 24px !important;
                    font-weight: 700 !important;
                    margin: 0 0 5px 0 !important;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .zalo-dashboard-title p {
                    margin: 0;
                    font-size: 15px;
                    opacity: 0.9;
                }
                .zalo-dashboard-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                    gap: 20px;
                    margin-bottom: 30px;
                }
                .zalo-dashboard-card {
                    background: #fff;
                    border-radius: 16px;
                    padding: 24px;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
                    border: 1px solid #E2E8F0;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    position: relative;
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    min-height: 180px;
                }
                .zalo-dashboard-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                    border-color: #CBD5E1;
                }
                .zalo-dashboard-card-top {
                    display: flex;
                    align-items: flex-start;
                    justify-content: space-between;
                    margin-bottom: 15px;
                }
                .zalo-dashboard-icon-wrap {
                    width: 48px;
                    height: 48px;
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .zalo-dashboard-icon-wrap span.dashicons {
                    font-size: 24px;
                    width: 24px;
                    height: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .zalo-card-news .zalo-dashboard-icon-wrap {
                    background: rgba(59, 130, 246, 0.1);
                    color: #3B82F6;
                }
                .zalo-card-schedule .zalo-dashboard-icon-wrap {
                    background: rgba(16, 185, 129, 0.1);
                    color: #10B981;
                }
                .zalo-card-report .zalo-dashboard-icon-wrap {
                    background: rgba(245, 158, 11, 0.1);
                    color: #F59E0B;
                }
                .zalo-card-faq .zalo-dashboard-icon-wrap {
                    background: rgba(139, 92, 246, 0.1);
                    color: #8B5CF6;
                }
                .zalo-dashboard-badge {
                    background: #F1F5F9;
                    color: #475569;
                    font-size: 13px;
                    font-weight: 600;
                    padding: 4px 10px;
                    border-radius: 20px;
                }
                .zalo-dashboard-badge.alert-badge {
                    background: #EF4444;
                    color: #FFF;
                    animation: zalo-pulse 2s infinite;
                }
                .zalo-dashboard-card h3 {
                    font-size: 18px !important;
                    font-weight: 600 !important;
                    margin: 0 0 10px 0 !important;
                    color: #1E293B;
                }
                .zalo-dashboard-card p {
                    color: #64748B;
                    font-size: 14px;
                    line-height: 1.5;
                    margin: 0 0 20px 0;
                    flex-grow: 1;
                }
                .zalo-dashboard-action-btn {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    padding: 10px 16px;
                    background: #F8FAFC;
                    color: #334155;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 14px;
                    transition: all 0.2s ease;
                    border: 1px solid #E2E8F0;
                }
                .zalo-dashboard-action-btn:hover {
                    background: #2D58D7;
                    color: #fff;
                    border-color: #2D58D7;
                }
                @keyframes zalo-pulse {
                    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
                    70% { transform: scale(1.05); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
                    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
                }

                /* Statistics Section Styling */
                .zalo-dashboard-stats {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
                    gap: 20px;
                    margin-bottom: 30px;
                }
                @media (max-width: 900px) {
                    .zalo-dashboard-stats {
                        grid-template-columns: 1fr;
                    }
                }
                .zalo-stats-card {
                    background: #fff;
                    border-radius: 16px;
                    padding: 26px;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
                    border: 1px solid #E2E8F0;
                    display: flex;
                    flex-direction: column;
                }
                .zalo-stats-card h3 {
                    font-size: 18px !important;
                    font-weight: 700 !important;
                    margin: 0 0 8px 0 !important;
                    color: #1E293B;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .zalo-stats-card p.zalo-stats-desc {
                    color: #64748B;
                    font-size: 14px;
                    margin: 0 0 20px 0;
                }
                .zalo-stats-summary {
                    display: flex;
                    align-items: baseline;
                    margin-bottom: 20px;
                    font-size: 15px;
                    color: #475569;
                }
                .zalo-stats-summary strong {
                    font-size: 28px;
                    font-weight: 800;
                    color: #1E3A8A;
                    margin-left: 8px;
                    line-height: 1;
                }
                .zalo-stacked-bar {
                    display: flex;
                    height: 24px;
                    border-radius: 12px;
                    overflow: hidden;
                    background: #F1F5F9;
                    margin-bottom: 25px;
                    box-shadow: inset 0 2px 4px rgba(0,0,0,0.06);
                }
                .zalo-stacked-segment {
                    height: 100%;
                    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
                    position: relative;
                }
                .segment-pending { background: #F59E0B; }
                .segment-processing { background: #3B82F6; }
                .segment-resolved { background: #10B981; }
                .segment-rejected { background: #EF4444; }
                .segment-empty { background: #CBD5E1; }

                .zalo-stats-legend {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                    margin-top: auto;
                }
                .legend-item {
                    display: flex;
                    align-items: center;
                    padding: 8px 12px;
                    background: #F8FAFC;
                    border-radius: 8px;
                    border: 1px solid #F1F5F9;
                }
                .legend-color {
                    width: 12px;
                    height: 12px;
                    border-radius: 4px;
                    margin-right: 12px;
                    flex-shrink: 0;
                }
                .color-pending { background: #F59E0B; }
                .color-processing { background: #3B82F6; }
                .color-resolved { background: #10B981; }
                .color-rejected { background: #EF4444; }

                .legend-label {
                    color: #475569;
                    font-weight: 500;
                    font-size: 13px;
                    flex-grow: 1;
                }
                .legend-value {
                    color: #1E293B;
                    font-weight: 700;
                    font-size: 13px;
                    margin-left: 10px;
                }
                .zalo-chart-container {
                    width: 100%;
                    height: 220px;
                    margin-top: auto;
                }
                .zalo-chart-bar {
                    transition: all 0.3s ease;
                }
                .zalo-chart-bar:hover {
                    fill: #2563EB;
                    filter: drop-shadow(0px 4px 6px rgba(37, 99, 235, 0.4));
                    opacity: 0.9;
                }
            </style>

            <div class="zalo-dashboard-header">
                <div class="zalo-dashboard-logo"></div>
                <div class="zalo-dashboard-title">
                    <h1>Hệ thống Quản trị Nghiệp vụ Công an Xã</h1>
                    <p>Cổng quản lý nghiệp vụ, xử lý phản ánh kiến nghị và cấu hình giao diện Zalo Mini App</p>
                </div>
            </div>

            <div class="zalo-dashboard-grid">
                <!-- Tin tức -->
                <div class="zalo-dashboard-card zalo-card-news">
                    <div class="zalo-dashboard-card-top">
                        <div class="zalo-dashboard-icon-wrap">
                            <span class="dashicons dashicons-megaphone"></span>
                        </div>
                        <span class="zalo-dashboard-badge"><?php echo esc_html($news_count); ?> đã đăng</span>
                    </div>
                    <h3>Tin tức & Tuyên truyền</h3>
                    <p>Đăng tải các bản tin an ninh trật tự, tuyên truyền phòng chống tội phạm và thông báo khẩn cấp tới người dân.</p>
                    <a href="<?php echo admin_url('edit.php?post_type=zalo_news'); ?>" class="zalo-dashboard-action-btn">Quản lý bài viết</a>
                </div>

                <!-- Lịch trực -->
                <div class="zalo-dashboard-card zalo-card-schedule">
                    <div class="zalo-dashboard-card-top">
                        <div class="zalo-dashboard-icon-wrap">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                        <span class="zalo-dashboard-badge"><?php echo esc_html($schedule_count); ?> ca trực</span>
                    </div>
                    <h3>Lịch trực & Tiếp dân</h3>
                    <p>Phân công ca trực ban tại trụ sở, lịch tiếp công dân của Cán bộ và danh sách Cảnh sát khu vực phụ trách.</p>
                    <a href="<?php echo admin_url('edit.php?post_type=zalo_schedule'); ?>" class="zalo-dashboard-action-btn">Quản lý ca trực</a>
                </div>

                <!-- Phản ánh hiện trường -->
                <div class="zalo-dashboard-card zalo-card-report">
                    <div class="zalo-dashboard-card-top">
                        <div class="zalo-dashboard-icon-wrap">
                            <span class="dashicons dashicons-warning"></span>
                        </div>
                        <span class="zalo-dashboard-badge <?php echo $pending_reports > 0 ? 'alert-badge' : ''; ?>">
                            <?php echo esc_html($pending_reports); ?> chờ xử lý
                        </span>
                    </div>
                    <h3>Phản ánh An ninh Trật tự</h3>
                    <p>Tiếp nhận, thẩm định và xử lý các tin báo phản ánh hiện trường, tố giác tội phạm gửi trực tiếp từ Zalo Mini App.</p>
                    <a href="<?php echo admin_url('edit.php?post_type=zalo_report'); ?>" class="zalo-dashboard-action-btn">Xem phản ánh</a>
                </div>

                <!-- Hỏi đáp FAQ -->
                <div class="zalo-dashboard-card zalo-card-faq">
                    <div class="zalo-dashboard-card-top">
                        <div class="zalo-dashboard-icon-wrap">
                            <span class="dashicons dashicons-welcome-learn-more"></span>
                        </div>
                        <span class="zalo-dashboard-badge <?php echo $pending_faqs > 0 ? 'alert-badge' : ''; ?>">
                            <?php echo esc_html($pending_faqs); ?> chờ duyệt
                        </span>
                    </div>
                    <h3>Hỏi đáp & Ý kiến người dân</h3>
                    <p>Biên soạn ngân hàng câu hỏi thường gặp, giải đáp các thắc mắc về thủ tục hành chính, hộ khẩu, CCCD.</p>
                    <a href="<?php echo admin_url('edit.php?post_type=zalo_faq'); ?>" class="zalo-dashboard-action-btn">Trả lời & Phê duyệt</a>
                </div>
            </div>

            <!-- Statistics Grid Dashboard -->
            <div class="zalo-dashboard-stats">
                <!-- Report Status Distribution -->
                <div class="zalo-stats-card">
                    <h3>Phân phối Trạng thái Phản ánh</h3>
                    <p class="zalo-stats-desc">Tỷ lệ phân chia tiến độ giải quyết các kiến nghị hiện trường của người dân</p>
                    
                    <div class="zalo-stats-summary">
                        Tổng số phản ánh đã nhận: <strong><?php echo esc_html($total_reports); ?></strong>
                    </div>
                    
                    <div class="zalo-stacked-bar">
                        <?php if ($total_reports > 0) : ?>
                            <?php if ($pending_reports > 0) : ?>
                                <div class="zalo-stacked-segment segment-pending" style="width: <?php echo esc_attr($pct_pending); ?>%;" title="Chờ xử lý: <?php echo esc_attr($pending_reports); ?> (<?php echo esc_attr($pct_pending); ?>%)"></div>
                            <?php endif; ?>
                            <?php if ($processing_reports > 0) : ?>
                                <div class="zalo-stacked-segment segment-processing" style="width: <?php echo esc_attr($pct_processing); ?>%;" title="Đang giải quyết: <?php echo esc_attr($processing_reports); ?> (<?php echo esc_attr($pct_processing); ?>%)"></div>
                            <?php endif; ?>
                            <?php if ($resolved_reports > 0) : ?>
                                <div class="zalo-stacked-segment segment-resolved" style="width: <?php echo esc_attr($pct_resolved); ?>%;" title="Đã hoàn thành: <?php echo esc_attr($resolved_reports); ?> (<?php echo esc_attr($pct_resolved); ?>%)"></div>
                            <?php endif; ?>
                            <?php if ($rejected_reports > 0) : ?>
                                <div class="zalo-stacked-segment segment-rejected" style="width: <?php echo esc_attr($pct_rejected); ?>%;" title="Từ chối: <?php echo esc_attr($rejected_reports); ?> (<?php echo esc_attr($pct_rejected); ?>%)"></div>
                            <?php endif; ?>
                        <?php else : ?>
                            <div class="zalo-stacked-segment segment-empty" style="width: 100%;" title="Chưa có phản ánh nào"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="zalo-stats-legend">
                        <div class="legend-item">
                            <span class="legend-color color-pending"></span>
                            <span class="legend-label">Chờ xử lý</span>
                            <span class="legend-value"><?php echo esc_html($pending_reports); ?> (<?php echo esc_html($pct_pending); ?>%)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color color-processing"></span>
                            <span class="legend-label">Đang giải quyết</span>
                            <span class="legend-value"><?php echo esc_html($processing_reports); ?> (<?php echo esc_html($pct_processing); ?>%)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color color-resolved"></span>
                            <span class="legend-label">Đã hoàn thành</span>
                            <span class="legend-value"><?php echo esc_html($resolved_reports); ?> (<?php echo esc_html($pct_resolved); ?>%)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color color-rejected"></span>
                            <span class="legend-label">Từ chối</span>
                            <span class="legend-value"><?php echo esc_html($rejected_reports); ?> (<?php echo esc_html($pct_rejected); ?>%)</span>
                        </div>
                    </div>
                </div>

                <!-- Report Weekly Trend (SVG Chart) -->
                <div class="zalo-stats-card">
                    <h3>Thống kê Lượng phản ánh 7 ngày qua</h3>
                    <p class="zalo-stats-desc">Biểu đồ số lượng phản ánh gửi đến hệ thống theo từng ngày</p>
                    
                    <div class="zalo-chart-container">
                        <svg viewBox="0 0 600 240" width="100%" height="100%">
                            <defs>
                                <linearGradient id="barGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#3B82F6" />
                                    <stop offset="100%" stop-color="#1E3A8A" />
                                </linearGradient>
                            </defs>
                            
                            <!-- Grid Lines -->
                            <line x1="50" y1="40" x2="560" y2="40" stroke="#F1F5F9" stroke-width="1.5" stroke-dasharray="4 4" />
                            <line x1="50" y1="120" x2="560" y2="120" stroke="#F1F5F9" stroke-width="1.5" stroke-dasharray="4 4" />
                            <line x1="50" y1="200" x2="560" y2="200" stroke="#E2E8F0" stroke-width="2" />
                            
                            <!-- Y-Axis Labels -->
                            <text x="40" y="44" font-size="11" font-weight="600" fill="#64748B" text-anchor="end"><?php echo esc_html($max_count); ?></text>
                            <text x="40" y="124" font-size="11" font-weight="600" fill="#64748B" text-anchor="end"><?php echo esc_html(round($max_count / 2)); ?></text>
                            <text x="40" y="204" font-size="11" font-weight="600" fill="#64748B" text-anchor="end">0</text>
                            
                            <!-- Bars & Labels -->
                            <?php
                            $idx = 0;
                            foreach ($daily_counts as $date_label => $count) {
                                $x = 60 + $idx * 76;
                                $bar_height = ($max_count > 0) ? ($count / $max_count) * 160 : 0;
                                $y = 200 - $bar_height;
                                $bar_width = 40;
                                ?>
                                <!-- Bar -->
                                <rect class="zalo-chart-bar" x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($bar_width); ?>" height="<?php echo esc_attr($bar_height); ?>" rx="6" ry="6" fill="url(#barGradient)" />
                                
                                <!-- Value Label -->
                                <?php if ($count > 0) : ?>
                                    <text x="<?php echo esc_attr($x + 20); ?>" y="<?php echo esc_attr($y - 8); ?>" font-size="11" font-weight="700" fill="#1E3A8A" text-anchor="middle"><?php echo esc_html($count); ?></text>
                                <?php endif; ?>
                                
                                <!-- Date Label -->
                                <text x="<?php echo esc_attr($x + 20); ?>" y="220" font-size="12" font-weight="500" fill="#64748B" text-anchor="middle"><?php echo esc_html($date_label); ?></text>
                                <?php
                                $idx++;
                            }
                            ?>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}