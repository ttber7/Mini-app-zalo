<?php
/**
 * REST API Endpoints for SDUI (Enterprise & Security Graded)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zalo_MiniApp_API_Endpoints
{
    private $api_secret;

    public function __construct()
    {
        // Đọc Secret từ wp-config.php (Không hardcode)
        $this->api_secret = defined('ZALO_MINIAPP_SECRET') ? ZALO_MINIAPP_SECRET : '';
    }

    public function init()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('rest_api_init', array($this, 'setup_cors_and_preflight'), 15);
    }

    public function setup_cors_and_preflight()
    {
        add_filter('rest_pre_serve_request', function ($value) {
            // CORS Limit (Chỉ cho phép domain của Zalo)
            $allowed_origins = array(
                'https://h5.zdn.vn',
                'https://mini.zalo.me',
                'http://localhost:5173' // Dành cho dev Vite/React
            );

            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

            if (in_array($origin, $allowed_origins, true)) {
                header("Access-Control-Allow-Origin: {$origin}");
            }

            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-MiniApp-Key');
            header('Vary: Origin'); // Tránh CDN cache nhầm CORS header

            // Xử lý OPTIONS Preflight request cho Zalo App
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                status_header(200);
                exit();
            }

            return $value;
        });
    }

    public function register_routes()
    {
        $namespace = 'miniapp/v1';

        // 1. GET /config: Lấy giao diện tĩnh SDUI
        register_rest_route($namespace, '/config', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_config'),
            'permission_callback' => '__return_true' // Bỏ auth để Frontend fetch trần trụi
        ));

        // 2. POST /submit-report: Gửi phản ánh
        register_rest_route($namespace, '/submit-report', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_report'),
            'permission_callback' => '__return_true'
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
            'permission_callback' => '__return_true'
        ));
        // 6. GET /faqs: Tải danh sách tra cứu
        register_rest_route($namespace, '/faqs', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_faqs'),
            'permission_callback' => '__return_true'
        ));

        // 7. POST /submit-question: Gửi câu hỏi mới
        register_rest_route($namespace, '/submit-question', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_question'),
            'permission_callback' => '__return_true'
        ));

        // 8. POST /webhook: Webhook từ Zalo OA (Bypass Auth vì Zalo dùng HMAC)
        register_rest_route($namespace, '/webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'zalo_webhook'),
            'permission_callback' => '__return_true' // Zalo server gọi, auth bằng HMAC
        ));

        // 9. GET /officers: Lấy danh sách cán bộ
        register_rest_route($namespace, '/officers', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_officers'),
            'permission_callback' => '__return_true'
        ));

        // 10. GET /schedules: Lấy lịch trực ban / làm việc
        register_rest_route($namespace, '/schedules', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_schedules'),
            'permission_callback' => '__return_true'
        ));
    }

    // Chống Spoofing IP - Chỉ tin tưởng Cloudflare nếu dùng CF
    private function get_client_ip()
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
        }
        return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    // Trả về WP_Error chuẩn thay vì gán TRUE/FALSE mập mờ
    public function verify_api_request(WP_REST_Request $request)
    {
        // Triển khai Auth Check (Phải định nghĩa MINIAPP_API_KEY ở wp-config.php)
        $client_key = $request->get_header('X-MiniApp-Key');
        $server_key = defined('MINIAPP_API_KEY') ? MINIAPP_API_KEY : '';

        // Fail Closed - Chặn toàn bộ nếu chưa cấu hình KEY
        if (empty($server_key)) {
            error_log('[MINIAPP API] LỖI BẢO MẬT: MINIAPP_API_KEY chưa được cấu hình trong wp-config.php.');
            return new WP_Error('server_misconfigured', 'Hệ thống chưa được cấu hình.', array('status' => 500));
        }

        // Trả lỗi Forbidden chuẩn hóa API thay vì chặn ngang
        if (empty($client_key) || !hash_equals($server_key, $client_key)) {
            return new WP_Error('forbidden', 'Truy cập không hợp lệ.', array('status' => 401));
        }

        return true;
    }

    public function get_config($request)
    {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/miniapp/ui-config.json';

        // Check file_exists trước khi get_contents
        if (!file_exists($file_path)) {
            return new WP_Error('config_missing', 'Config file not found.', array('status' => 404));
        }

        // Thêm quyền check is_readable chống crash an toàn hệ thống file
        if (!is_readable($file_path)) {
            return new WP_Error('config_unreadable', 'Cannot read config file permissions.', array('status' => 500));
        }

        // Guard chống Crash file
        $json_data = file_get_contents($file_path);
        $data = json_decode($json_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', 'Config corrupted.', array('status' => 500));
        }

        // Cache Weather API chống treo Server Config (Lưu 10 phút)
        $weather_cache_key = 'miniapp_weather_data';
        $weather_cached = get_transient($weather_cache_key);

        if ($weather_cached !== false) {
            $data['global_config']['weather'] = $weather_cached;
        } else {
            $weather_api_url = "https://api.open-meteo.com/v1/forecast?latitude=10.5167&longitude=106.5833&current_weather=true";
            $weather_res = wp_remote_get($weather_api_url, array('timeout' => 3));

            if (!is_wp_error($weather_res) && wp_remote_retrieve_response_code($weather_res) === 200) {
                $weather_body = json_decode(wp_remote_retrieve_body($weather_res), true);
                if (is_array($weather_body) && isset($weather_body['current_weather'])) {
                    $weather_code_map = array(
                        0  => 'trời quang đãng',
                        1  => 'nắng nhẹ, ít mây',
                        2  => 'mây rải rác',
                        3  => 'nhiều mây, âm u',
                        45 => 'có sương mù',
                        48 => 'có sương muối',
                        51 => 'mưa phùn nhẹ',
                        53 => 'mưa phùn vừa',
                        55 => 'mưa phùn dày đặc',
                        56 => 'mưa phùn lạnh nhẹ',
                        57 => 'mưa phùn lạnh dày',
                        61 => 'mưa nhỏ',
                        63 => 'mưa vừa',
                        65 => 'mưa to',
                        66 => 'mưa lạnh nhẹ',
                        67 => 'mưa lạnh nặng hạt',
                        71 => 'tuyết rơi nhẹ',
                        73 => 'tuyết rơi vừa',
                        75 => 'tuyết rơi nhiều',
                        77 => 'có mưa đá nhỏ',
                        80 => 'mưa rào nhẹ',
                        81 => 'mưa rào vừa',
                        82 => 'mưa rào lớn',
                        85 => 'mưa tuyết rào nhẹ',
                        86 => 'mưa tuyết rào nặng',
                        95 => 'có dông bão',
                        96 => 'dông bão kèm mưa đá',
                        99 => 'dông bão lớn kèm mưa đá'
                    );
                    $wcode = (int)$weather_body['current_weather']['weathercode'];
                    $desc = isset($weather_code_map[$wcode]) ? $weather_code_map[$wcode] : 'mây rải rác';

                    $weather_parsed = array(
                        'temp' => round($weather_body['current_weather']['temperature'], 1) . '°C',
                        'code' => $wcode,
                        'desc' => $desc,
                        'time' => current_time('H:i')
                    );
                    $data['global_config']['weather'] = $weather_parsed;
                    set_transient($weather_cache_key, $weather_parsed, 10 * MINUTE_IN_SECONDS);
                }
            } else {
                // Cache luôn fallback khi API thời tiết chết, tránh nghẽn luồng request liên tục
                $fallback_weather = array('temp' => '--°C', 'code' => 0, 'desc' => 'mây rải rác', 'time' => current_time('H:i'));
                $data['global_config']['weather'] = $fallback_weather;
                set_transient($weather_cache_key, $fallback_weather, 5 * MINUTE_IN_SECONDS);
            }
        }

        return new WP_REST_Response($data, 200);
    }


    public function submit_report(WP_REST_Request $request)
    {
        // Tăng giới hạn PHP runtime nếu cấu hình server cho phép
        @ini_set('upload_max_filesize', '20M');
        @ini_set('post_max_size', '25M');
        @ini_set('memory_limit', '256M');

        // Rate Limit Guard
        $ip = $this->get_client_ip();
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
        
        // Xử lý Base64 Image từ Frontend
        $image_data = $params['image'] ?? ($params['image_url'] ?? '');
        $image = '';

        if (!empty($image_data)) {
            if (preg_match('/^data:image\/(\w+);base64,/', $image_data, $type)) {
                // Giải mã Base64
                $data = substr($image_data, strpos($image_data, ',') + 1);
                $type = strtolower($type[1]); // jpg, jpeg, png, gif, webp, etc.

                if (in_array($type, array('jpg', 'jpeg', 'gif', 'png', 'webp'))) {
                    $decoded_data = base64_decode($data);

                    if ($decoded_data !== false) {
                        $upload_dir = wp_upload_dir();
                        if (!file_exists($upload_dir['path'])) {
                            wp_mkdir_p($upload_dir['path']);
                        }

                        $filename = 'report_' . uniqid() . '.' . $type;
                        $filepath = $upload_dir['path'] . '/' . $filename;

                        if (file_put_contents($filepath, $decoded_data) !== false) {
                            $image = $upload_dir['url'] . '/' . $filename;
                            error_log("[MINIAPP] Đã giải mã và lưu ảnh Base64: {$image}");
                        } else {
                            error_log("[MINIAPP] Lỗi: Không thể ghi file ảnh Base64 vào {$filepath}");
                        }
                    } else {
                        error_log("[MINIAPP] Lỗi: Không thể giải mã Base64.");
                    }
                } else {
                    error_log("[MINIAPP] Lỗi: Định dạng ảnh không hỗ trợ ({$type}).");
                }
            } else {
                $image = esc_url_raw($image_data);
            }
        }

        $user_id = sanitize_text_field($params['user_id'] ?? '');

        // Khóa chặt dữ liệu đầu vào (Độ dài phần cứng)
        if (empty($content)) {
            return new WP_Error('missing_content', 'Vui lòng nhập nội dung.', array('status' => 400));
        }
        // Chuyển hoàn toàn sang mb_strlen phòng thủ tiếng Việt có dấu
        if (mb_strlen($name, 'UTF-8') > 100) {
            return new WP_Error('too_long_name', 'Họ tên quá dài.', array('status' => 400));
        }
        if (mb_strlen($content, 'UTF-8') > 1000) {
            return new WP_Error('too_long', 'Nội dung phản ánh quá dài.', array('status' => 400));
        }
        if (mb_strlen($gps, 'UTF-8') > 255) {
            return new WP_Error('too_long_gps', 'Tọa độ GPS không hợp lệ.', array('status' => 400));
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
            return new WP_Error('invalid_phone', 'Số điện thoại không hợp lệ.', array('status' => 400));
        }
        if (!empty($image) && !filter_var($image, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', 'Đường dẫn ảnh không hợp lệ.', array('status' => 400));
        }

        // Idempotency Guard - Khóa trùng lặp (Bấm đúp nút gửi)
        $duplicate_key = 'dup_rep_' . md5($phone . $content);
        if (get_transient($duplicate_key)) {
            return new WP_Error('duplicate_request', 'Nội dung phản ánh trùng lặp đang được xử lý.', array('status' => 400));
        }
        set_transient($duplicate_key, 1, 60); // Khóa 60 giây

        $post_id = wp_insert_post(array(
            'post_title' => 'Phản ánh từ ' . $name . ' (' . current_time('d/m/Y H:i') . ')',
            'post_content' => $content,
            'post_type' => 'zalo_report',
            'post_status' => 'pending' // Bảo mật tuyệt đối - Lưu trạng thái chờ duyệt
        ));

        if (is_wp_error($post_id)) {
            return new WP_Error('create_failed', 'Lỗi hệ thống. Không thể tạo phản ánh.', array('status' => 500));
        }

        // Ghi log hệ thống vận hành
        error_log("[MINIAPP] Tạo phản ánh thành công. ID: #{$post_id}");

        // Lưu Data vào Carbon Fields
        if (function_exists('carbon_set_post_meta')) {
            carbon_set_post_meta($post_id, 'reporter_name', $name);
            carbon_set_post_meta($post_id, 'reporter_phone', $phone);
            carbon_set_post_meta($post_id, 'report_gps', $gps);
            carbon_set_post_meta($post_id, 'report_image', $image);
            carbon_set_post_meta($post_id, 'report_status', 'pending');
            if (!empty($user_id)) {
                carbon_set_post_meta($post_id, 'reporter_zalo_id', $user_id);
            }
        }

        // Gửi tin nhắn Zalo OA xác nhận
        if (!empty($user_id) && class_exists('Zalo_MiniApp_OA_Service')) {
            $msg = "Xin chào {$name}, phản ánh của bạn (Mã số: #{$post_id}) đã được gửi thành công và đang chờ xử lý. Cảm ơn bạn đã đóng góp.";
            Zalo_MiniApp_OA_Service::get_instance()->send_oa_message($user_id, $msg);
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
        // Giới hạn phân trang tối đa 20 bài chống tràn RAM / DDoS database
        $limit = $request->get_param('limit') ? min(max((int) $request->get_param('limit'), 1), 20) : 5;

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
                // Strip tags cạo sạch HTML rác trước khi băm chữ
                'excerpt' => wp_trim_words(wp_strip_all_tags($post->post_content), 15, '...'),
                'date' => get_the_date('d/m/Y', $post->ID),
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
            // Lọc XSS Injection qua nội dung bài viết bằng wp_kses_post
            'content' => wp_kses_post(apply_filters('the_content', $post->post_content)),
            'date' => get_the_date('d/m/Y', $post->ID),
            'image_url' => get_the_post_thumbnail_url($post->ID, 'large') ?: ''
        ), 200);
    }

    // Khóa lỗ hổng rò rỉ dữ liệu, bắt buộc tra cứu bằng Zalo ID định danh
    public function get_report_status(WP_REST_Request $request)
    {
        $zalo_user_id = sanitize_text_field($request->get_param('user_id'));

        if (empty($zalo_user_id)) {
            return new WP_Error('missing_identity', 'Thiếu thông tin định danh Zalo.', array('status' => 400));
        }

        $args = array(
            'post_type' => 'zalo_report',
            'meta_query' => array(
                array(
                    'key' => '_reporter_zalo_id',
                    'value' => $zalo_user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 10
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

    public function get_faqs(WP_REST_Request $request)
    {
        $keyword = sanitize_text_field($request->get_param('q'));
        $page = $request->get_param('page') ? (int) $request->get_param('page') : 1;
        // Giới hạn phân trang tối đa 50 cho FAQ tra cứu
        $limit = $request->get_param('limit') ? min(max((int) $request->get_param('limit'), 1), 50) : 10;

        $args = array(
            'post_type' => 'zalo_faq',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'paged' => $page,
        );

        // Sử dụng cơ chế Collation Native của WordPress (Không dùng filter cắt dấu thủ công làm vỡ index SQL)
        if (!empty($keyword)) {
            $args['s'] = $keyword;
        }

        $query = new WP_Query($args);
        $items = array();

        foreach ($query->posts as $post) {
            $answer = '';
            if (function_exists('carbon_get_post_meta')) {
                $answer = carbon_get_post_meta($post->ID, 'faq_answer');
            }
            $items[] = array(
                'id' => $post->ID,
                'question' => esc_html($post->post_title),
                // Lọc XSS Injection cho câu trả lời bằng wp_kses_post
                'answer' => wp_kses_post(apply_filters('the_content', $answer))
            );
        }
        wp_reset_postdata();

        return new WP_REST_Response(array(
            'data' => $items,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages
        ), 200);
    }

    public function submit_question(WP_REST_Request $request)
    {
        // Rate Limit Guard chống spam form
        $ip = $this->get_client_ip();
        $limit_key = 'zalo_faq_rate_' . md5($ip);

        if (get_transient($limit_key)) {
            return new WP_Error('rate_limit', 'Bạn thao tác quá nhanh, vui lòng đợi 15 giây.', array('status' => 429));
        }
        set_transient($limit_key, 1, 15);

        $params = $request->get_json_params();
        if (empty($params))
            $params = $request->get_body_params();

        $question = sanitize_textarea_field($params['question'] ?? '');
        $user_id = sanitize_text_field($params['user_id'] ?? '');

        if (empty($question)) {
            return new WP_Error('missing_question', 'Vui lòng nhập câu hỏi.', array('status' => 400));
        }
        if (strlen($question) > 500) {
            return new WP_Error('too_long', 'Câu hỏi quá dài (tối đa 500 ký tự).', array('status' => 400));
        }

        // Chống spam lặp câu hỏi tĩnh
        $duplicate_key = 'dup_faq_' . md5($user_id . $question);
        if (get_transient($duplicate_key)) {
            return new WP_Error('duplicate_question', 'Câu hỏi trùng lặp đang chờ phê duyệt.', array('status' => 400));
        }
        set_transient($duplicate_key, 1, 60);

        // --- Bắt đầu tính toán Jaccard Similarity để trả lời tự động ---
        $user_tokens = $this->get_normalized_tokens($question);

        $faqs_query = new WP_Query(array(
            'post_type' => 'zalo_faq',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ));

        $best_score = 0;
        $best_match = null;

        if ($faqs_query->have_posts()) {
            foreach ($faqs_query->posts as $post) {
                $faq_tokens = $this->get_normalized_tokens($post->post_title);
                $score = $this->get_jaccard_similarity($user_tokens, $faq_tokens);

                if ($score > $best_score) {
                    $best_score = $score;
                    $best_match = $post;
                }
            }
        }
        wp_reset_postdata();

        $threshold = 0.45;
        if (function_exists('carbon_get_theme_option')) {
            $opt_threshold = carbon_get_theme_option('faq_jaccard_threshold');
            if ($opt_threshold !== '' && $opt_threshold !== null) {
                $threshold = floatval($opt_threshold);
            }
        }
        if ($best_match && $best_score >= $threshold) {
            $answer = '';
            if (function_exists('carbon_get_post_meta')) {
                $answer = carbon_get_post_meta($best_match->ID, 'faq_answer');
            }
            $answer = wp_kses_post(apply_filters('the_content', $answer));

            error_log("[MINIAPP] FAQ Auto-Reply matched post ID: #{$best_match->ID} with score {$best_score}");

            return new WP_REST_Response(array(
                'success' => true,
                'auto_answered' => true,
                'matched_question' => esc_html($best_match->post_title),
                'answer' => $answer,
                'score' => $best_score
            ), 200);
        }

        // Nếu không khớp, lưu câu hỏi thành draft zalo_faq như cũ
        $post_id = wp_insert_post(array(
            'post_title' => $question,
            'post_type' => 'zalo_faq',
            'post_status' => 'draft'
        ));

        if (is_wp_error($post_id)) {
            return new WP_Error('create_failed', 'Lỗi hệ thống.', array('status' => 500));
        }

        if (function_exists('carbon_set_post_meta')) {
            carbon_set_post_meta($post_id, 'faq_status', 'pending');
            if (!empty($user_id)) {
                carbon_set_post_meta($post_id, 'faq_reporter_id', $user_id);
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'auto_answered' => false,
            'message' => 'Câu hỏi đã được gửi và đang chờ duyệt.',
        ), 200);
    }

    private function get_normalized_tokens($text)
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = $this->strip_accents($text);
        // Loại bỏ ký tự đặc biệt, chỉ giữ lại chữ cái và số
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        $filtered_words = array();
        foreach ($words as $word) {
            // Loại bỏ các từ quá ngắn vô nghĩa (độ dài 1 ký tự và không phải số)
            if (mb_strlen($word, 'UTF-8') > 1 || is_numeric($word)) {
                $filtered_words[] = $word;
            }
        }
        return array_unique($filtered_words);
    }

    private function strip_accents($str)
    {
        $accents = array(
            'à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
            'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
            'ì','í','ị','ỉ','ĩ',
            'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
            'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
            'ỳ','ý','ỵ','ỷ','ỹ',
            'đ',
            'À','Á','Ạ','Ả','Ã','Â','Ầ','Ấ','Ậ','Ẩ','Ẫ','Ă','Ằ','Ắ','Ặ','Ẳ','Ẵ',
            'È','É','Ẹ','Ẻ','Ẽ','Ê','Ề','Ế','Ệ','Ể','Ễ',
            'Ì','Í','Ị','Ỉ','Ĩ',
            'Ò','Ó','Ọ','Ỏ','Õ','Ô','Ồ','Ố','Ộ','Ổ','Ỗ','Ơ','Ờ','Ớ','Ợ','Ở','Ỡ',
            'Ù','Ú','Ụ','Ủ','Ũ','Ư','Ừ','Ứ','Ự','Ử','Ữ',
            'Ý','Ỳ','Ỵ','Ỷ','Ỹ',
            'Đ'
        );
        $replacements = array(
            'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
            'e','e','e','e','e','e','e','e','e','e','e',
            'i','i','i','i','i',
            'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
            'u','u','u','u','u','u','u','u','u','u','u',
            'y','y','y','y','y',
            'd',
            'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
            'e','e','e','e','e','e','e','e','e','e','e',
            'i','i','i','i','i',
            'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
            'u','u','u','u','u','u','u','u','u','u','u',
            'y','y','y','y','y',
            'd'
        );
        return str_replace($accents, $replacements, $str);
    }

    private function get_jaccard_similarity($tokens1, $tokens2)
    {
        if (empty($tokens1) && empty($tokens2)) {
            return 0.0;
        }
        $intersection = array_intersect($tokens1, $tokens2);
        $union = array_unique(array_merge($tokens1, $tokens2));
        return count($intersection) / count($union);
    }

    public function get_officers(WP_REST_Request $request)
    {
        $args = array(
            'post_type' => 'zalo_officer',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );

        $query = new WP_Query($args);
        $items = array();

        foreach ($query->posts as $post) {
            $phone = '';
            $area = '';
            if (function_exists('carbon_get_post_meta')) {
                $phone = carbon_get_post_meta($post->ID, 'officer_phone');
                $area = carbon_get_post_meta($post->ID, 'officer_area');
            }
            $items[] = array(
                'id' => $post->ID,
                'name' => esc_html($post->post_title),
                'phone' => esc_html($phone),
                'area' => esc_html($area),
                'image_url' => get_the_post_thumbnail_url($post->ID, 'thumbnail') ?: ''
            );
        }
        wp_reset_postdata();

        return new WP_REST_Response(array(
            'data' => $items
        ), 200);
    }

    public function get_schedules(WP_REST_Request $request)
    {
        $args = array(
            'post_type' => 'zalo_schedule',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => '_schedule_date',
            'orderby' => 'meta_value',
            'order' => 'ASC'
        );

        $query = new WP_Query($args);
        $items = array();

        foreach ($query->posts as $post) {
            $date = '';
            $time = '';
            $officer = '';
            $phone = '';
            $location = '';
            $notes = '';

            if (function_exists('carbon_get_post_meta')) {
                $date = carbon_get_post_meta($post->ID, 'schedule_date');
                $time = carbon_get_post_meta($post->ID, 'schedule_time');
                $officer = carbon_get_post_meta($post->ID, 'schedule_officer');
                $phone = carbon_get_post_meta($post->ID, 'schedule_phone');
                $location = carbon_get_post_meta($post->ID, 'schedule_location');
                $notes = carbon_get_post_meta($post->ID, 'schedule_notes');
            }

            $items[] = array(
                'id' => $post->ID,
                'title' => esc_html($post->post_title),
                'date' => esc_html($date),
                'time' => esc_html($time),
                'officer' => esc_html($officer),
                'phone' => esc_html($phone),
                'location' => esc_html($location),
                'notes' => esc_html($notes)
            );
        }
        wp_reset_postdata();

        return new WP_REST_Response(array(
            'data' => $items
        ), 200);
    }

    public function zalo_webhook(WP_REST_Request $request)
    {
        $raw_body = $request->get_body();
        $headers = $request->get_headers();

        $oa_config = function_exists('carbon_get_theme_option') ? carbon_get_theme_option('zalo_oa_config') : [];

        $app_id = $oa_config[0]['app_id'] ?? '';
        $secret_key = $oa_config[0]['secret_key'] ?? '';

        // Fail Closed - Khóa chặn webhook nếu cán bộ chưa setup cấu hình
        if (empty($app_id) || empty($secret_key)) {
            error_log('[ZALO WEBHOOK] Chặn khẩn cấp: Hệ thống chưa được cán bộ cấu hình App ID/Secret Key.');
            return new WP_Error('webhook_misconfigured', 'Webhook service unavailable.', array('status' => 500));
        }

        // Lấy header an toàn (không phân biệt hoa thường) hỗ trợ cả Zalo OA và Mini App
        $timestamp = $request->get_header('x-timestamp') ?: $request->get_header('x_timestamp') ?: $request->get_header('x-zevent-timestamp') ?: '';
        $mac = $request->get_header('x-mac-zalo') ?: $request->get_header('x_mac_zalo') ?: $request->get_header('x-zevent-signature') ?: '';

        // Anti Replay-Attack (Lọc trùng lặp gói tin gửi sau 5 phút)
        $timestamp_seconds = (int) (strlen($timestamp) > 10 ? $timestamp / 1000 : $timestamp);
        if (abs(time() - $timestamp_seconds) > 300) {
            error_log('[ZALO WEBHOOK] CẢNH BÁO: Phát hiện request hết hạn hoặc có dấu hiệu Replay Attack.');
            return new WP_Error('expired_request', 'Expired request.', array('status' => 401));
        }

        // Dùng hash_equals chống Timing Attack
        $expected_mac = hash('sha256', $app_id . $raw_body . $timestamp . $secret_key);
        if (!hash_equals($expected_mac, $mac)) {
            error_log('[ZALO WEBHOOK] Sai chữ ký mã MAC bảo mật.');
            return new WP_Error('unauthorized', 'Invalid Signature', array('status' => 401));
        }

        // Log Webhook event chuẩn phục vụ DevOps monitoring
        error_log('[ZALO WEBHOOK] Tiếp nhận sự kiện tương tác hợp lệ từ Zalo Server.');

        $data = json_decode($raw_body, true);
        if (!is_array($data))
            return new WP_REST_Response(array('error' => 0, 'message' => 'ok'), 200);

        $event_name = $data['event_name'] ?? '';

        if ($event_name === 'user_send_text') {
            $user_id = sanitize_text_field($data['sender']['id'] ?? '');
            $message = sanitize_text_field($data['message']['text'] ?? '');

            if (stripos($message, 'hỗ trợ') !== false && class_exists('Zalo_MiniApp_OA_Service')) {
                Zalo_MiniApp_OA_Service::get_instance()->send_oa_message($user_id, 'Chào bạn, câu hỏi của bạn đã được ghi nhận. Chúng tôi sẽ phản hồi trong thời gian sớm nhất.');
            }
        }

        return new WP_REST_Response(array('error' => 0, 'message' => 'ok'), 200);
    }
}