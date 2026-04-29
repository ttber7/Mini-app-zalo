<?php
/**
 * REST API Endpoints for SDUI (Enterprise & Security Graded)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zalo_MiniApp_API_Endpoints
{
    private $api_secret = 'zalo_miniapp_super_secret_2026';

    public function register_routes()
    {
        $namespace = 'miniapp/v1';

        // 1. GET /config: Lấy giao diện tĩnh SDUI
        register_rest_route($namespace, '/config', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_config'),
            'permission_callback' => '__return_true'
        ));

        add_filter('rest_pre_serve_request', function ($value) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-MiniApp-Key');
            return $value;
        });

        // 2. POST /submit-report: Gửi phản ánh
        register_rest_route($namespace, '/submit-report', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_report'),
            'permission_callback' => array($this, 'verify_api_request')
        ));

        // 3. GET /news: Lấy danh sách tin
        register_rest_route($namespace, '/news', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_news'),
            'permission_callback' => '__return_true'
        ));

        // 4. GET /news/<id>: Chi tiết tin
        register_rest_route($namespace, '/news/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_news_detail'),
            'permission_callback' => '__return_true'
        ));

        // 5. GET /report-status: Tra cứu phản ánh
        register_rest_route($namespace, '/report-status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_report_status'),
            'permission_callback' => array($this, 'verify_api_request')
        ));
    }

    public function verify_api_request(WP_REST_Request $request)
    {
        // Tạm mở cửa cho môi trường Dev. Lên Production nhớ bật lại check Header
        return true;
    }

    public function get_config($request)
    {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/miniapp/ui-config.json';

        if (!file_exists($file_path)) {
            return new WP_Error('no_config', 'Config file not found.', array('status' => 404));
        }

        $json_data = file_get_contents($file_path);
        $data = json_decode($json_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', 'Config corrupted.', array('status' => 500));
        }

        // TÍCH HỢP API THỜI TIẾT TỪ OPEN-METEO
        $weather_api_url = "https://api.open-meteo.com/v1/forecast?latitude=10.5167&longitude=106.5833&current_weather=true";
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
            $data['global_config']['weather'] = array('temp' => '--°C', 'code' => 0, 'time' => current_time('H:i'));
        }

        $response = new WP_REST_Response($data, 200);
        $response->header('Cache-Control', 'public, max-age=60');
        return $response;
    }

    public function submit_report(WP_REST_Request $request)
    {
        // Rate Limit Guard
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $limit_key = 'zalo_rate_' . md5($ip);

        if (get_transient($limit_key)) {
            return new WP_Error('rate_limit', 'Bạn thao tác quá nhanh, vui lòng đợi 15 giây.', array('status' => 429));
        }
        set_transient($limit_key, 1, 15);

        $params = $request->get_json_params();
        if (empty($params))
            $params = $request->get_body_params();

        // CHUẨN HÓA TÊN BIẾN (Chấp nhận cả 2 kiểu gọi từ Frontend)
        $name = sanitize_text_field($params['name'] ?? 'Người dân');
        $phone = sanitize_text_field($params['phone'] ?? '');
        $content = sanitize_textarea_field($params['content'] ?? ($params['note'] ?? ''));
        $gps = sanitize_text_field($params['location'] ?? ($params['gps'] ?? ''));
        $image = esc_url_raw($params['image_url'] ?? '');

        // Validation
        if (empty($content)) {
            return new WP_Error('missing_content', 'Vui lòng nhập nội dung.', array('status' => 400));
        }
        if (strlen($content) > 1000) {
            return new WP_Error('too_long', 'Nội dung phản ánh quá dài (tối đa 1000 ký tự).', array('status' => 400));
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
            return new WP_Error('invalid_phone', 'Số điện thoại không hợp lệ.', array('status' => 400));
        }

        $post_id = wp_insert_post(array(
            'post_title' => 'Phản ánh từ ' . $name . ' (' . current_time('d/m/Y H:i') . ')',
            'post_content' => $content,
            'post_type' => 'zalo_report',
            'post_status' => 'publish'
        ));

        if (is_wp_error($post_id)) {
            return new WP_Error('create_failed', 'Lỗi hệ thống. Không thể tạo phản ánh.', array('status' => 500));
        }

        // Lưu Data vào Carbon Fields
        if (function_exists('carbon_set_post_meta')) {
            carbon_set_post_meta($post_id, 'reporter_name', $name);
            carbon_set_post_meta($post_id, 'reporter_phone', $phone);
            carbon_set_post_meta($post_id, 'report_gps', $gps);
            carbon_set_post_meta($post_id, 'report_image', $image);
            carbon_set_post_meta($post_id, 'report_status', 'pending');
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Gửi phản ánh thành công',
            'report_id' => $post_id
        ), 200);
    }

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
                'title' => esc_html($post->post_title),
                'excerpt' => wp_trim_words($post->post_content, 15, '...'),
                'date' => get_the_date('d/m/Y', $post->ID),
                // CHUẨN HÓA TÊN BIẾN: Trả về image_url để đồng bộ với Zalo
                'image_url' => get_the_post_thumbnail_url($post->ID, 'medium') ?: ''
            );
        }

        wp_reset_postdata();

        return new WP_REST_Response(array(
            'data' => $items,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages
        ), 200);
    }

    public function get_news_detail(WP_REST_Request $request)
    {
        $id = (int) $request->get_param('id');
        $post = get_post($id);

        if (!$post || $post->post_type !== 'zalo_news' || $post->post_status !== 'publish') {
            return new WP_Error('not_found', 'Tin tức không tồn tại', array('status' => 404));
        }

        return new WP_REST_Response(array(
            'id' => $post->ID,
            'title' => esc_html($post->post_title),
            'content' => apply_filters('the_content', $post->post_content),
            'date' => get_the_date('d/m/Y', $post->ID),
            'image_url' => get_the_post_thumbnail_url($post->ID, 'large') ?: ''
        ), 200);
    }

    public function get_report_status(WP_REST_Request $request)
    {
        $phone = sanitize_text_field($request->get_param('phone'));

        if (empty($phone) || !preg_match('/^[0-9]{10,11}$/', $phone)) {
            return new WP_Error('invalid_phone', 'Số điện thoại không hợp lệ', array('status' => 400));
        }

        $args = array(
            'post_type' => 'zalo_report',
            'meta_query' => array(
                array(
                    'key' => '_reporter_phone', // Carbon Fields DB prefix
                    'value' => $phone,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 5
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
                'note' => esc_html($note)
            );
        }

        wp_reset_postdata();

        return new WP_REST_Response(array('data' => $items), 200);
    }
}