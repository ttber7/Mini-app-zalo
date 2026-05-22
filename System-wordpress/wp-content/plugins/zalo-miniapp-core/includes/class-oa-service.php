<?php
/**
 * Quản lý các kết nối API tới Zalo OA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zalo_MiniApp_OA_Service
{
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init()
    {
        // Khởi tạo các hook nếu cần
        add_action('admin_init', array($this, 'seed_initial_tokens'));

        // Theo dõi thay đổi trạng thái FAQ
        add_action('updated_post_meta', array($this, 'check_faq_status_change'), 10, 4);
        add_action('added_post_meta', array($this, 'check_faq_status_change'), 10, 4);

        // Theo dõi thay đổi trạng thái Phản ánh hiện trường
        add_action('updated_post_meta', array($this, 'check_report_status_change'), 10, 4);
        add_action('added_post_meta', array($this, 'check_report_status_change'), 10, 4);
    }

    public function check_faq_status_change($meta_id, $post_id, $meta_key, $meta_value)
    {
        // Chặn sớm nếu không phải key cần tìm
        if ($meta_key !== '_faq_status' || $meta_value !== 'approved') {
            return;
        }

        // Chỉ gửi khi bài viết là zalo_faq
        if (get_post_type($post_id) !== 'zalo_faq') {
            return;
        }

        // Đảm bảo không gửi lại nhiều lần (dùng một cờ flag)
        if (get_post_meta($post_id, '_faq_notified', true)) {
            return;
        }

        $reporter_id = get_post_meta($post_id, '_faq_reporter_id', true);
        if (empty($reporter_id)) {
            return;
        }

        $question = get_the_title($post_id);
        $msg = "Câu hỏi của bạn: \"{$question}\" đã được cán bộ trả lời. Vui lòng kiểm tra lại trong mục Hỏi đáp của ứng dụng Zalo Công an.";

        $sent = $this->send_oa_message($reporter_id, $msg);
        if ($sent) {
            update_post_meta($post_id, '_faq_notified', '1');
        } else {
            // Logging hệ thống
            error_log("[ZALO OA] Lỗi: Không thể gửi thông báo duyệt FAQ cho User {$reporter_id}");
        }
    }

    public function check_report_status_change($meta_id, $post_id, $meta_key, $meta_value)
    {
        // Chặn sớm nếu không phải key trạng thái phản ánh
        if ($meta_key !== '_report_status') {
            return;
        }

        // Chỉ gửi khi bài viết là zalo_report
        if (get_post_type($post_id) !== 'zalo_report') {
            return;
        }

        // Đọc cấu hình xem có bật tính năng thông báo phản ánh không
        $notify_enabled = true;
        if (function_exists('carbon_get_theme_option')) {
            $notify_enabled = carbon_get_theme_option('oa_report_notify_enabled');
        }

        if (!$notify_enabled) {
            return;
        }

        // Kiểm tra xem trạng thái mới có khác trạng thái được thông báo trước đó không
        $last_notified = get_post_meta($post_id, '_report_last_notified_status', true);
        if ($last_notified === $meta_value) {
            return;
        }

        // Lấy thông tin người báo tin
        $reporter_id = get_post_meta($post_id, '_reporter_zalo_id', true);
        if (empty($reporter_id)) {
            error_log("[ZALO OA] Không thể gửi thông báo cập nhật phản ánh #{$post_id} vì thiếu User Zalo ID.");
            return;
        }

        $reporter_name = get_post_meta($post_id, '_reporter_name', true) ?: 'người dân';
        $internal_notes = get_post_meta($post_id, '_internal_notes', true);

        $status_labels = array(
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang giải quyết',
            'resolved' => 'Đã hoàn thành',
            'rejected' => 'Từ chối'
        );
        $status_label = isset($status_labels[$meta_value]) ? $status_labels[$meta_value] : $meta_value;

        // Đọc cấu hình Template ID
        $template_id = '';
        if (function_exists('carbon_get_theme_option')) {
            $template_id = carbon_get_theme_option('oa_report_template_id');
        }

        $sent = false;

        // Nếu có Template ID cấu hình, ưu tiên gửi Tin nhắn Giao dịch (Transaction)
        if (!empty($template_id)) {
            $sent = $this->send_oa_transaction_template($reporter_id, $template_id, array(
                'name' => $reporter_name,
                'report_id' => '#' . $post_id,
                'status' => $status_label,
                'notes' => !empty($internal_notes) ? $internal_notes : 'Đang xử lý',
                'date' => get_the_date('d/m/Y H:i', $post_id)
            ));
        }

        // Nếu không có Template ID hoặc gửi Template lỗi, fallback về Tin nhắn Chăm sóc khách hàng dạng text
        if (!$sent) {
            $msg = "Kính gửi {$reporter_name}, phản ánh của bạn (Mã số: #{$post_id}) đã được cập nhật trạng thái mới: **{$status_label}**.\n";
            if (!empty($internal_notes)) {
                $msg .= "Nội dung phản hồi từ Cán bộ: {$internal_notes}";
            } else {
                $msg .= "Cán bộ trực ban đang tiến hành xử lý phản ánh của bạn. Cảm ơn bạn đã đóng góp ý kiến.";
            }

            $sent = $this->send_oa_message($reporter_id, $msg);
        }

        if ($sent) {
            update_post_meta($post_id, '_report_last_notified_status', $meta_value);
            error_log("[ZALO OA] Đã gửi thông báo cập nhật phản ánh #{$post_id} thành công với trạng thái: {$meta_value}");
        } else {
            error_log("[ZALO OA] Gửi thông báo cập nhật phản ánh #{$post_id} thất bại.");
        }
    }

    /**
     * Gửi tin nhắn Giao dịch (Transaction Template) qua Zalo OA
     */
    public function send_oa_transaction_template($zalo_user_id, $template_id, $template_data)
    {
        $access_token = $this->get_valid_access_token();
        if (!$access_token) {
            error_log("[ZALO OA] Không tìm thấy Access Token để gửi tin giao dịch cho User {$zalo_user_id}.");
            return false;
        }

        $url = 'https://openapi.zalo.me/v3.0/oa/message/transaction';
        
        // Clean values to avoid JSON breakage
        $clean_data = array();
        foreach ($template_data as $k => $v) {
            $clean_data[$k] = sanitize_text_field($v);
        }

        $body = wp_json_encode(array(
            'recipient' => array('user_id' => $zalo_user_id),
            'template' => array(
                'template_id' => $template_id,
                'template_data' => $clean_data
            )
        ));

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'access_token' => $access_token
            ),
            'body' => $body,
            'timeout' => 15
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            error_log("[ZALO OA] HTTP Error khi gửi tin giao dịch: " . $response->get_error_message());
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($data)) {
            error_log('[ZALO OA] Lỗi: Zalo API trả về phản hồi không phải JSON hợp lệ (Gửi tin giao dịch).');
            return false;
        }

        // Tự động làm mới token nếu token hết hạn (Lỗi -216 hoặc -213)
        if (isset($data['error']) && in_array($data['error'], array(-216, -213))) {
            error_log("[ZALO OA] Access Token hết hạn (Lỗi {$data['error']}) khi gửi tin giao dịch. Tiến hành auto-refresh...");
            $new_token = $this->refresh_oa_token();
            if ($new_token) {
                $args['headers']['access_token'] = $new_token;
                $retry_response = wp_remote_post($url, $args);
                if (is_wp_error($retry_response)) return false;
                $retry_data = json_decode(wp_remote_retrieve_body($retry_response), true);
                if (is_array($retry_data) && isset($retry_data['error']) && $retry_data['error'] === 0) {
                    return true;
                }
            }
            return false;
        }

        if (isset($data['error']) && $data['error'] !== 0) {
            error_log("[ZALO OA] Lỗi API Zalo khi gửi tin giao dịch cho {$zalo_user_id}. Mã lỗi: {$data['error']} - Chi tiết: " . ($data['message'] ?? ''));
            return false;
        }

        return true;
    }

    /**
     * Tự động tạo cấu trúc ban đầu. Có dùng flag để tối ưu Performance.
     */
    public function seed_initial_tokens()
    {
        // Dùng option flag để không query DB liên tục mỗi lần load admin
        if (get_option('zalo_oa_seeded')) {
            return;
        }

        if (!function_exists('carbon_get_theme_option')) {
            return;
        }

        $oa_config = carbon_get_theme_option('zalo_oa_config');
        if (empty($oa_config)) {
            carbon_set_theme_option('zalo_oa_config', array(
                array(
                    '_type' => 'zalo_oa_config',
                    'app_id' => '4401826645366479708',
                    'secret_key' => 'fR1aHkswH3L4aU1Wldq1',
                    'oa_id' => '502931508216399126',
                    'access_token' => 'SoiLNLVKnXnoB1XGLjRC474W4r1HmSLiSKTCUY_NccD2RaX28Upi3dfGInK8tSWqVdz48IxZxm9EQcSx3FN8B5mC761cZynLG2nZQoAWzNf027XNDOFQT15uLZOmt8HtHdbIQ3d4_MfROIbi0z-_J5CdAmyJZlWsDWPcBrc6rcftAtnf68-iIrWd1quNheHuUIi7L0E-rXPWG5PKEhRZQXqSI4rvdELy7nPAENBkrHW3Va43UVIVHJXB8rvfulGQ77eb76VUX2arQ5G4M-pdCof2P3LnsBC63bejHcxJ-7WfK5zvSzhwKWP4I7nxvVGu06bh15tdvmCYU3SFOSIP0GLO22Ldseay4aq26rpbd5u2MozP9hcYLsua05ORWQvEIHqfN1Q_hbfx5JXC6ehdMKKAI60WiFabUbONp-PqKCh160',
                    'refresh_token' => 'nYRn8eB-2q_78VmlW_m1GyGjzo-4wqq2dKNq7RdTNWwfPyjXxVbILFvQpsNG_qP-zqtCSx7XPXAtRCOJhTPu9f1JrIVAyrScsMwXCVlpEW6uGy8Oz_jN4h5c_7wdmciGbHB6VDIIHMpK0ibYzebN0jOjsZBOlqmVmJxYTV6FHqFg1DzGoDXKPTG5taVsdKTdvn_C1y36O1c20yLtaD9dOuXttdM-g25XX3RSRukqG4w_1i1pfhvjUAG7nK-0rdman6Z46ENoJt3KOivxyj9LLhn-edR-eWb_-mkoL_cl6ZZH3ADNmhmbVVG1lnF7i2OAWZMJDuIBCL-n1BHeZvH2TRqgtHNoiqCxjJMX9w2KD0-z7vWshiSx9OndjXB9opqC-nB65TIDGpIJA'
                )
            ));

            // Double-check DB write thành công mới bật cờ
            if (!empty(carbon_get_theme_option('zalo_oa_config'))) {
                update_option('zalo_oa_seeded', 1);
            }
        }
    }

    /**
     * Đọc Access Token hiện tại từ Database.
     */
    public function get_valid_access_token()
    {
        // Chống Fatal Error nếu Carbon Fields bị tắt
        if (!function_exists('carbon_get_theme_option')) {
            return false;
        }

        $oa_config = carbon_get_theme_option('zalo_oa_config');
        if (empty($oa_config) || empty($oa_config[0]) || empty($oa_config[0]['access_token'])) {
            // Token kiểm thử mặc định do người dùng cung cấp
            return 'SoiLNLVKnXnoB1XGLjRC474W4r1HmSLiSKTCUY_NccD2RaX28Upi3dfGInK8tSWqVdz48IxZxm9EQcSx3FN8B5mC761cZynLG2nZQoAWzNf027XNDOFQT15uLZOmt8HtHdbIQ3d4_MfROIbi0z-_J5CdAmyJZlWsDWPcBrc6rcftAtnf68-iIrWd1quNheHuUIi7L0E-rXPWG5PKEhRZQXqSI4rvdELy7nPAENBkrHW3Va43UVIVHJXB8rvfulGQ77eb76VUX2arQ5G4M-pdCof2P3LnsBC63bejHcxJ-7WfK5zvSzhwKWP4I7nxvVGu06bh15tdvmCYU3SFOSIP0GLO22Ldseay4aq26rpbd5u2MozP9hcYLsua05ORWQvEIHqfN1Q_hbfx5JXC6ehdMKKAI60WiFabUbONp-PqKCh160';
        }
        return $oa_config[0]['access_token'];
    }

    /**
     * Refresh Token với cơ chế chống Race Condition & Infinite Retry
     */
    public function refresh_oa_token()
    {
        // Infinite Retry Guard (Rate Limit Refresh 60s)
        if (get_transient('zalo_refresh_failed')) {
            error_log("[ZALO OA] Bỏ qua Refresh Token do mới thất bại gần đây (Đang bị Rate Limit 60s).");
            return false;
        }

        // RACE CONDITION GUARD: Khóa tiến trình 10 giây
        if (get_transient('zalo_token_refresh_lock')) {
            // Có tiến trình khác đang refresh token, spin-lock đợi tối đa 3 giây
            $wait_cycles = 0;
            while (get_transient('zalo_token_refresh_lock') && $wait_cycles < 30) {
                usleep(100000); // Đợi 100ms
                $wait_cycles++;
            }
            // Sau khi đợi xong, trả về access token mới (nếu thành công)
            $oa_config = carbon_get_theme_option('zalo_oa_config');
            return !empty($oa_config[0]['access_token']) ? $oa_config[0]['access_token'] : false;
        }

        // Đặt khóa (lock) trong 10 giây
        set_transient('zalo_token_refresh_lock', '1', 10);

        try {
            $oa_config = carbon_get_theme_option('zalo_oa_config');
            if (empty($oa_config) || empty($oa_config[0])) {
                return false;
            }

            $config = $oa_config[0];
            $refresh_token = !empty($config['refresh_token']) ? $config['refresh_token'] : 'nYRn8eB-2q_78VmlW_m1GyGjzo-4wqq2dKNq7RdTNWwfPyjXxVbILFvQpsNG_qP-zqtCSx7XPXAtRCOJhTPu9f1JrIVAyrScsMwXCVlpEW6uGy8Oz_jN4h5c_7wdmciGbHB6VDIIHMpK0ibYzebN0jOjsZBOlqmVmJxYTV6FHqFg1DzGoDXKPTG5taVsdKTdvn_C1y36O1c20yLtaD9dOuXttdM-g25XX3RSRukqG4w_1i1pfhvjUAG7nK-0rdman6Z46ENoJt3KOivxyj9LLhn-edR-eWb_-mkoL_cl6ZZH3ADNmhmbVVG1lnF7i2OAWZMJDuIBCL-n1BHeZvH2TRqgtHNoiqCxjJMX9w2KD0-z7vWshiSx9OndjXB9opqC-nB65TIDGpIJA';
            $app_id = $config['app_id'];
            $secret_key = $config['secret_key'];

            if (empty($refresh_token) || empty($app_id) || empty($secret_key)) {
                error_log("[ZALO OA] Lỗi: Trống Cấu hình (App ID, Secret Key, Refresh Token).");
                return false;
            }

            $url = 'https://oauth.zaloapp.com/v4/oa/access_token';
            $response = wp_remote_post($url, array(
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'secret_key' => $secret_key
                ),
                'body' => array(
                    'app_id' => $app_id,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refresh_token
                ),
                'timeout' => 15
            ));

            if (is_wp_error($response)) {
                error_log('[ZALO OA] Lỗi HTTP khi gọi API Refresh Token: ' . $response->get_error_message());
                set_transient('zalo_refresh_failed', 1, 60);
                return false;
            }

            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                error_log("[ZALO OA] HTTP Status Error khi Refresh Token: {$status_code}");
                set_transient('zalo_refresh_failed', 1, 60);
                return false;
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);

            if (!is_array($data)) {
                error_log('[ZALO OA] Lỗi: Zalo API trả về phản hồi không phải JSON hợp lệ (Refresh Token).');
                set_transient('zalo_refresh_failed', 1, 60);
                return false;
            }

            if (isset($data['access_token']) && isset($data['refresh_token'])) {
                $config['access_token'] = $data['access_token'];
                $config['refresh_token'] = $data['refresh_token'];

                $config['_type'] = 'zalo_oa_config';
                carbon_set_theme_option('zalo_oa_config', array($config));

                error_log('[ZALO OA] Làm mới Access Token thành công.');
                return $data['access_token'];
            }

            $err_code = $data['error'] ?? 'unknown';
            error_log("[ZALO OA] API Refresh Token thất bại. Mã lỗi: {$err_code}");
            set_transient('zalo_refresh_failed', 1, 60);
            return false;

        } finally {
            // BẤT CHẤP return hay exception ở trên, block này luôn chạy để giải phóng khóa!
            delete_transient('zalo_token_refresh_lock');
        }
    }

    /**
     * Gửi tin nhắn thông báo (ZNS / CSKH) qua Zalo OA
     */
    public function send_oa_message($zalo_user_id, $message_text)
    {
        $access_token = $this->get_valid_access_token();
        if (!$access_token) {
            error_log("[ZALO OA] Không tìm thấy Access Token để gửi tin cho User {$zalo_user_id}.");
            return false;
        }

        // Sanitize input trước khi gửi lên API để chống Injection/JSON Error
        $clean_message = sanitize_textarea_field($message_text);

        $url = 'https://openapi.zalo.me/v3.0/oa/message/cs';
        // Dùng wp_json_encode chống vỡ UTF-8, Emoji
        $body = wp_json_encode(array(
            'recipient' => array('user_id' => $zalo_user_id),
            'message' => array('text' => $clean_message)
        ));

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'access_token' => $access_token
            ),
            'body' => $body,
            'timeout' => 15
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            error_log("[ZALO OA] HTTP Error khi gửi tin ZNS: " . $response->get_error_message());
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        // JSON Validate Guard
        if (!is_array($data)) {
            error_log('[ZALO OA] Lỗi: Zalo API trả về phản hồi không phải JSON hợp lệ (Gửi tin).');
            return false;
        }

        // Kiểm tra mã lỗi Access Token hết hạn (code -216 hoặc -213)
        if (isset($data['error']) && in_array($data['error'], array(-216, -213))) {
            error_log("[ZALO OA] Access Token hết hạn (Lỗi {$data['error']}). Tiến hành auto-refresh...");

            // Thử Refresh Token và Retry
            $new_token = $this->refresh_oa_token();
            if ($new_token) {
                $args['headers']['access_token'] = $new_token;
                $retry_response = wp_remote_post($url, $args);

                // Check WP_Error ở lượt Retry
                if (is_wp_error($retry_response)) {
                    error_log("[ZALO OA] Retry request failed: " . $retry_response->get_error_message());
                    return false;
                }

                $retry_status = wp_remote_retrieve_response_code($retry_response);
                if ($retry_status !== 200) {
                    error_log("[ZALO OA] Retry HTTP Status Error: {$retry_status}");
                    return false;
                }

                $retry_data = json_decode(wp_remote_retrieve_body($retry_response), true);

                // JSON Validate Guard (Lượt Retry)
                if (!is_array($retry_data)) {
                    error_log('[ZALO OA] Lỗi: Zalo API trả về phản hồi không phải JSON hợp lệ (Gửi tin - Lượt Retry).');
                    return false;
                }

                if (isset($retry_data['error']) && $retry_data['error'] !== 0) {
                    error_log("[ZALO OA] Lỗi gửi tin sau khi đã retry. Mã lỗi: " . ($retry_data['error'] ?? 'unknown'));
                    return false;
                }
                return true;
            }
            return false; // Refresh failed
        }

        // Bắt các lỗi API khác từ Zalo
        if (isset($data['error']) && $data['error'] !== 0) {
            error_log("[ZALO OA] Lỗi API Zalo khi gửi tin cho {$zalo_user_id}. Mã lỗi: {$data['error']}");
            return false;
        }

        return true;
    }

    /**
     * Gửi tin nhắn Broadcast hàng loạt
     */
    public function send_broadcast($user_ids, $message_text)
    {
        // Validate đầu vào tránh warning
        if (!is_array($user_ids) || empty($user_ids)) {
            error_log("[ZALO OA] Lỗi: Danh sách User ID để gửi Broadcast không hợp lệ.");
            return false;
        }
        // Chia nhỏ mảng để tránh sập PHP-FPM
        $chunks = array_chunk($user_ids, 50); // Chia lô 50 user mỗi đợt

        foreach ($chunks as $chunk) {
            foreach ($chunk as $id) {
                $this->send_oa_message($id, $message_text);
            }
            // Tạm nghỉ 1 giây sau mỗi lô 50 người để nhường tài nguyên CPU & Tránh Rate Limit
            usleep(1000000);
        }
    }
}
