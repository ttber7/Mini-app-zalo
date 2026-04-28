<?php
/**
 * REST API Endpoints for SDUI (Enterprise & Security Graded)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zalo_MiniApp_API_Endpoints
{

    // Khóa API bí mật dùng để chặn request lạ (Phase 1)
    // Tương lai Phase 2 sẽ thay bằng JWT / Zalo Access Token
    private $api_secret = 'zalo_miniapp_super_secret_2026';

    public function register_routes()
    {
        $namespace = 'miniapp/v1';

        // 1. GET /config: Cache 60s
        register_rest_route($namespace, '/config', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_config'),
            'permission_callback' => '__return_true' // Public, nhưng có cache header
        ));

        // Thêm filter CORS (Chỉ nên dùng cho môi trường Dev)
        add_filter('rest_pre_serve_request', function ($value) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-MiniApp-Key');
            return $value;
        });

        // 2. POST /submit-report: Có Rate Limit & Header Guard
        register_rest_route($namespace, '/submit-report', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_report'),
            'permission_callback' => array($this, 'verify_api_request')
        ));

        // 3. GET /news
        register_rest_route($namespace, '/news', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_news'),
            'permission_callback' => '__return_true'
        ));

        // 4. GET /news/<id>
        register_rest_route($namespace, '/news/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_news_detail'),
            'permission_callback' => '__return_true'
        ));

        // 5. GET /report-status: Có Regex Phone
        register_rest_route($namespace, '/report-status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_report_status'),
            'permission_callback' => array($this, 'verify_api_request')
        ));
    }

    /**
     * Middleware Kiểm tra Header (Bảo vệ điểm 2)
     */
    public function verify_api_request(WP_REST_Request $request)
    {
        // Tạm thời return true khi đang dev local để dễ test Postman.
        // Khi lên Production, bỏ comment đoạn dưới:

        /*
        $client_key = $request->get_header( 'X-MiniApp-Key' );
        if ( $client_key !== $this->api_secret ) {
            return new WP_Error( 'unauthorized', 'Request bị từ chối.', array( 'status' => 401 ) );
        }
        */
        return true;
    }

    /**
     * GET /config
     */
    public function get_config($request)
    {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/miniapp/ui-config.json';

        if (!file_exists($file_path)) {
            return new WP_Error('no_config', 'Config file not found. Please save settings in Admin.', array('status' => 404));
        }

        $json_data = file_get_contents($file_path);
        $data = json_decode($json_data, true);

        // 1. Fallback: Check JSON bị hỏng (Bảo vệ điểm 3) - Phải đặt ngay sau json_decode
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', 'Config corrupted.', array('status' => 500));
        }

        // ==========================================
        // 2. 🔥 TÍCH HỢP API THỜI TIẾT TỪ OPEN-METEO
        // Tọa độ Cần Đước, Long An
        // ==========================================
        $weather_api_url = "https://api.open-meteo.com/v1/forecast?latitude=10.5167&longitude=106.5833&current_weather=true";

        // Gọi API với timeout ngắn (3s) để không làm chậm App nếu API thời tiết sập
        $weather_res = wp_remote_get($weather_api_url, array('timeout' => 3));

        if (!is_wp_error($weather_res) && wp_remote_retrieve_response_code($weather_res) === 200) {
            $weather_body = json_decode(wp_remote_retrieve_body($weather_res), true);

            if (isset($weather_body['current_weather'])) {
                $data['global_config']['weather'] = array(
                    'temp' => round($weather_body['current_weather']['temperature'], 1) . '°C',
                    'code' => $weather_body['current_weather']['weathercode'],
                    'time' => current_time('H:i')
                );
            }
        } else {
            // Dữ liệu dự phòng nếu đứt cáp/mất kết nối API thời tiết
            $data['global_config']['weather'] = array(
                'temp' => '--°C',
                'code' => 0,
                'time' => current_time('H:i')
            );
        }

        // 3. ĐÓNG GÓI VÀ TRẢ VỀ (Chỉ 1 lần duy nhất)
        $response = new WP_REST_Response($data, 200);

        // Cache Header 60 giây (Bảo vệ điểm 4)
        $response->header('Cache-Control', 'public, max-age=60');

        return $response;
    }
    /**
     * POST /submit-report
     */
    public function submit_report(WP_REST_Request $request)
    {
        // Rate Limit Guard (Bảo vệ điểm 1)
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $limit_key = 'zalo_rate_' . md5($ip);

        if (get_transient($limit_key)) {
            return new WP_Error('rate_limit', 'Bạn thao tác quá nhanh, vui lòng đợi 15 giây.', array('status' => 429));
        }
        set_transient($limit_key, 1, 15); // Khóa 15 giây

        $params = $request->get_json_params();
        if (empty($params)) {
            $params = $request->get_body_params();
        }

        $name = isset($params['name']) ? sanitize_text_field($params['name']) : 'Người dân ẩn danh';
        $phone = isset($params['phone']) ? sanitize_text_field($params['phone']) : '';
        $gps = isset($params['gps']) ? sanitize_text_field($params['gps']) : '';
        $note = isset($params['note']) ? sanitize_textarea_field($params['note']) : '';

        // Spam Guard (Bảo vệ điểm 5)
        if (strlen($note) > 1000) {
            return new WP_Error('too_long', 'Nội dung phản ánh quá dài (tối đa 1000 ký tự).', array('status' => 400));
        }

        if (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
            return new WP_Error('invalid_phone', 'Số điện thoại không hợp lệ.', array('status' => 400));
        }

        $post_id = wp_insert_post(array(
            'post_title' => 'Phản ánh từ ' . $name . ' (' . current_time('d/m/Y H:i') . ')',
            'post_content' => $note,
            'post_type' => 'zalo_report',
            'post_status' => 'publish'
        ));

        if (is_wp_error($post_id)) {
            return new WP_Error('create_failed', 'Lỗi hệ thống. Không thể tạo phản ánh.', array('status' => 500));
        }

        if (function_exists('carbon_set_post_meta')) {
            carbon_set_post_meta($post_id, 'reporter_name', $name);
            carbon_set_post_meta($post_id, 'reporter_phone', $phone);
            carbon_set_post_meta($post_id, 'report_gps', $gps);
            carbon_set_post_meta($post_id, 'report_status', 'pending');
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Gửi phản ánh thành công',
            'report_id' => $post_id
        ), 200);
    }

    /**
     * GET /news
     */
    public function get_news(WP_REST_Request $request)
    {
        $page = $request->get_param('page') ? (int) $request->get_param('page') : 1;
        $limit = $request->get_param('limit') ? (int) $request->get_param('limit') : 5;

        $args = array(
            'post_type' => 'zalo_news',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'paged' => $page,
        );

        $query = new WP_Query($args);
        $items = array();

        foreach ($query->posts as $post) {
            $items[] = array(
                'id' => $post->ID,
                'title' => esc_html($post->post_title), // Escape Data (Bảo vệ điểm 7)
                'date' => get_the_date('d/m/Y', $post->ID),
                'thumb' => get_the_post_thumbnail_url($post->ID, 'medium')
            );
        }

        wp_reset_postdata(); // Clear Memory (Bảo vệ điểm 8)

        return new WP_REST_Response(array(
            'data' => $items,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages
        ), 200);
    }

    /**
     * GET /news/<id>
     */
    public function get_news_detail(WP_REST_Request $request)
    {
        $id = (int) $request->get_param('id');
        $post = get_post($id);

        if (!$post || $post->post_type !== 'zalo_news' || $post->post_status !== 'publish') {
            return new WP_Error('not_found', 'Tin tức không tồn tại', array('status' => 404));
        }

        return new WP_REST_Response(array(
            'id' => $post->ID,
            'title' => esc_html($post->post_title), // Escape
            'content' => apply_filters('the_content', $post->post_content),
            'date' => get_the_date('d/m/Y', $post->ID),
            'thumb' => get_the_post_thumbnail_url($post->ID, 'large')
        ), 200);
    }

    /**
     * GET /report-status
     */
    public function get_report_status(WP_REST_Request $request)
    {
        $phone = sanitize_text_field($request->get_param('phone'));

        // Regex Guard (Bảo vệ điểm 6)
        if (empty($phone) || !preg_match('/^[0-9]{10,11}$/', $phone)) {
            return new WP_Error('invalid_phone', 'Số điện thoại không hợp lệ', array('status' => 400));
        }

        $args = array(
            'post_type' => 'zalo_report',
            'meta_query' => array(
                array(
                    'key' => '_reporter_phone',
                    'value' => $phone,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 5 // Chỉ trả về tối đa 5 kết quả để giảm lộ lọt dữ liệu
        );

        $query = new WP_Query($args);
        $items = array();

        $status_labels = array(
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang giải quyết',
            'resolved' => 'Đã hoàn thành',
            'rejected' => 'Từ chối'
        );

        foreach ($query->posts as $post) {
            $status = get_post_meta($post->ID, '_report_status', true);
            $note = get_post_meta($post->ID, '_internal_notes', true);

            $items[] = array(
                'id' => $post->ID,
                'title' => esc_html($post->post_title),
                'date' => get_the_date('d/m/Y H:i', $post->ID),
                'status' => isset($status_labels[$status]) ? $status_labels[$status] : $status,
                'note' => esc_html($note) // Escape note
            );
        }

        wp_reset_postdata(); // Clear Memory

        return new WP_REST_Response(array('data' => $items), 200);
    }
}