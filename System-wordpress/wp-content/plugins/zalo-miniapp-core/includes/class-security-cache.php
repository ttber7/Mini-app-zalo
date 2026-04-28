<?php
/**
 * Security and Caching logic (Trái tim số 2 của hệ thống - Enterprise Grade)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zalo_MiniApp_Security_Cache
{
    public function init()
    {
        add_action('zalo_miniapp_build_json', array($this, 'generate_ui_config_json'));
        add_action('admin_notices', array($this, 'display_validation_errors'));
    }

    public function generate_ui_config_json()
    {
        // 1. THÊM VÀO ĐÂY: Chặn mọi truy cập nếu không phải Admin
        if (is_admin() && !current_user_can('manage_options')) {
            return;
        }

        $pages = carbon_get_theme_option('miniapp_pages');
        if (empty($pages))
            return;

        $page_ids = [];

        // --- BƯỚC 1: VALIDATION LỚP 1 (Trùng lặp & Logging) ---
        foreach ($pages as $page) {
            // Chặn ngay nếu để trống Page ID
            if (empty($page['page_id'])) {
                $msg = 'LỖI: Mã trang (Page ID) không được để trống.';
                set_transient('zalo_sdui_error', $msg, 60);
                error_log('[ZALO SDUI] VALIDATION ERROR: ' . $msg);
                return;
            }

            if (in_array($page['page_id'], $page_ids)) {
                $msg = 'LỖI: Mã trang (Page ID) "' . $page['page_id'] . '" bị trùng.';
                set_transient('zalo_sdui_error', $msg, 60);
                error_log('[ZALO SDUI] VALIDATION ERROR: ' . $msg);
                return;
            }
            $page_ids[] = $page['page_id'];

            $component_ids = [];
            if (!empty($page['page_components'])) {
                foreach ($page['page_components'] as $comp) {
                    if (empty($comp['id']))
                        continue;
                    if (in_array($comp['id'], $component_ids)) {
                        $msg = 'LỖI: Component ID "' . $comp['id'] . '" bị trùng trong trang "' . $page['page_id'] . '".';
                        set_transient('zalo_sdui_error', $msg, 60);
                        error_log('[ZALO SDUI] VALIDATION ERROR: ' . $msg);
                        return;
                    }
                    $component_ids[] = $comp['id'];
                }
            }
        }

        // --- BƯỚC 2: VALIDATION LỚP 2 (Entry Page) ---
        $entry_page = carbon_get_theme_option('miniapp_entry_page') ?: 'home';
        if (!in_array($entry_page, $page_ids)) {
            $msg = 'LỖI: Mã Trang Chủ (Entry Page) "' . $entry_page . '" không tồn tại!';
            set_transient('zalo_sdui_error', $msg, 60);
            error_log('[ZALO SDUI] VALIDATION ERROR: ' . $msg);
            return;
        }

        delete_transient('zalo_sdui_error');

        // --- BƯỚC 3: TRANSFORMER (Chuẩn hóa) ---
        $base_api_url = '/wp-json/miniapp/v1';

        $schema = array(
            'version' => '1.0.0',
            'min_app_version' => '1.0.0',
            'cache_version' => '', // Tạm để rỗng, build xong sẽ gán sau
            'schema_hash' => '',
            'tracking_id' => 'GA-123456789',
            'tenant_id' => 'cong_an_phuong', // Fixed Tenant ID an toàn
            'entry_page' => sanitize_text_field($entry_page),
            'last_updated' => current_time('c'),
            'global_config' => array(
                'app_name' => sanitize_text_field(carbon_get_theme_option('miniapp_name')),
                'primary_color' => sanitize_hex_color(carbon_get_theme_option('miniapp_primary_color')),
                'logo_url' => carbon_get_theme_option('miniapp_logo') ? esc_url_raw(wp_get_attachment_url(carbon_get_theme_option('miniapp_logo'))) : ''
            ),
            'data_sources' => array(
                'news_api' => array('api' => $base_api_url . '/news', 'method' => 'GET', 'cache' => 600),
                'submit_report_api' => array('api' => $base_api_url . '/submit-report', 'method' => 'POST')
            ),
            'pages' => array()
        );

        // ĐÃ SỬA: Mở khóa cho 3 component mới
        $allowed_types = ['banner', 'grid_menu', 'article_list', 'form', 'official_channel', 'statistics_grid', 'emergency_list'];

        foreach ($pages as $page) {
            $page_id = sanitize_text_field($page['page_id']);
            $page_data = array(
                'id' => $page_id,
                'title' => sanitize_text_field($page['page_title']),
                'visible_for' => !empty($page['visible_for']) ? array_map('sanitize_text_field', $page['visible_for']) : ['citizen'],
                'layout' => array()
            );

            if (!empty($page['page_components']) && is_array($page['page_components'])) {
                foreach ($page['page_components'] as $comp) {
                    $type = isset($comp['_type']) ? $comp['_type'] : '';

                    if (!in_array($type, $allowed_types)) {
                        continue;
                    }

                    // Deep Validation: Bỏ qua component rỗng để tránh lỗi App
                    if ($type === 'form' && empty($comp['fields'])) {
                        error_log('[ZALO SDUI] SKIPPED COMPONENT: form missing fields at page ' . $page_id);
                        continue;
                    }
                    if ($type === 'grid_menu' && empty($comp['items'])) {
                        error_log('[ZALO SDUI] SKIPPED COMPONENT: grid_menu missing items at page ' . $page_id);
                        continue;
                    }

                    $formatted_comp = array(
                        'id' => sanitize_text_field($comp['id']),
                        'type' => $type,
                        'visible_for' => !empty($comp['visible_for'])
                            ? array_map('sanitize_text_field', $comp['visible_for'])
                            : ['citizen']
                    );

                    // --- BẮT ĐẦU ÉP KIỂU DỮ LIỆU TỪNG COMPONENT ---
                    if ($type === 'banner') {
                        $formatted_comp['content'] = array(
                            'image_url' => $comp['image_url'] ? esc_url_raw(wp_get_attachment_url($comp['image_url'])) : ''
                        );
                        if (!empty($comp['action_type'])) {
                            $action_val = $this->sanitize_action_value($comp['action_type'], $comp['action_value']);
                            $param_key = ($comp['action_type'] === 'navigate') ? 'page' : ($comp['action_type'] === 'open_url' ? 'url' : 'value');
                            $formatted_comp['action'] = array(
                                'type' => sanitize_text_field($comp['action_type']),
                                'params' => array($param_key => $action_val)
                            );
                        }
                    } elseif ($type === 'grid_menu') {
                        $items = array();
                        if (!empty($comp['items'])) {
                            foreach ($comp['items'] as $item) {
                                $action_val = $this->sanitize_action_value($item['action_type'], $item['action_value']);
                                $param_key = ($item['action_type'] === 'navigate') ? 'page' : ($item['action_type'] === 'open_url' ? 'url' : 'phone');
                                $items[] = array(
                                    'icon' => sanitize_text_field($item['icon']),
                                    'label' => sanitize_text_field($item['label']),
                                    'action' => array(
                                        'type' => sanitize_text_field($item['action_type']),
                                        'params' => array($param_key => $action_val)
                                    )
                                );
                            }
                        }
                        $formatted_comp['content'] = array('columns' => 3, 'items' => $items);
                    } elseif ($type === 'article_list') {
                        $formatted_comp['content'] = array('title' => sanitize_text_field($comp['title']));
                        $formatted_comp['data_source'] = array(
                            'key' => sanitize_text_field($comp['data_source_key'] ?: 'news_api'),
                            'params' => array('limit' => 5)
                        );
                    } elseif ($type === 'form') {
                        $fields = array();
                        if (!empty($comp['fields'])) {
                            foreach ($comp['fields'] as $field) {
                                if (empty($field['id']) || empty($field['type'])) {
                                    continue;
                                }
                                $fields[] = array(
                                    'id' => sanitize_text_field($field['id']),
                                    'type' => sanitize_text_field($field['type']),
                                    'label' => sanitize_text_field($field['label']),
                                    'required' => !empty($field['required']),
                                    'require_native_permission' => in_array($field['type'], ['image', 'location'])
                                );
                            }
                        }
                        $formatted_comp['content'] = array(
                            'fields' => $fields,
                            'submit_api' => sanitize_text_field($comp['api_submit'] ?: 'submit_report_api')
                        );
                    }
                    // [ĐÃ THÊM]: Parse data cho Kênh Chính Thức
                    elseif ($type === 'official_channel') {
                        $formatted_comp['content'] = array(
                            'oa_id' => sanitize_text_field($comp['oa_id']),
                            'cover_image' => $comp['cover_image'] ? esc_url_raw(wp_get_attachment_url($comp['cover_image'])) : ''
                        );
                    }
                    // [ĐÃ THÊM]: Parse data cho Lưới Thống Kê
                    elseif ($type === 'statistics_grid') {
                        $formatted_comp['content'] = array(
                            'population' => sanitize_text_field($comp['population']),
                            'households' => sanitize_text_field($comp['households']),
                            'area' => sanitize_text_field($comp['area']),
                            'party_members' => sanitize_text_field($comp['party_members']),
                            'update_time' => sanitize_text_field($comp['update_time'])
                        );
                    }
                    // [ĐÃ THÊM]: Parse data cho Hotline Khẩn Cấp
                    elseif ($type === 'emergency_list') {
                        $hotlines = array();
                        if (!empty($comp['hotlines'])) {
                            foreach ($comp['hotlines'] as $hotline) {
                                $hotlines[] = array(
                                    'label' => sanitize_text_field($hotline['label']),
                                    'sub_label' => sanitize_text_field($hotline['sub_label']),
                                    'phone' => sanitize_text_field($hotline['phone']),
                                    'bg_color' => sanitize_text_field($hotline['bg_color'])
                                );
                            }
                        }
                        $formatted_comp['content'] = array('hotlines' => $hotlines);
                    }

                    $page_data['layout'][] = $formatted_comp;
                }
            }
            $schema['pages'][$page_id] = $page_data;
        }

        // Tính toán Hash khi schema đã hoàn thiện 100%
        $schema['schema_hash'] = md5(wp_json_encode($schema));
        // Lấy 8 ký tự đầu của Hash làm Cache Version cực kỳ tối ưu
        $schema['cache_version'] = substr($schema['schema_hash'], 0, 8);

        // --- BƯỚC 4: ATOMIC WRITE (Có LOCK_EX và Fallback XAMPP) ---
        $upload_dir = wp_upload_dir();
        $miniapp_dir = $upload_dir['basedir'] . '/miniapp';
        if (!file_exists($miniapp_dir)) {
            wp_mkdir_p($miniapp_dir);
        }

        $file_path = $miniapp_dir . '/ui-config.json';
        $temp_file = $file_path . '.tmp';

        // Thêm LOCK_EX để chống ghi đè đồng thời
        $result = file_put_contents($temp_file, wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

        if ($result === false) {
            $msg = 'LỖI HỆ THỐNG: Không thể ghi file tạm. Kiểm tra quyền thư mục Uploads.';
            set_transient('zalo_sdui_error', $msg, 60);
            error_log('[ZALO SDUI] FILE WRITE ERROR: ' . $msg);
        } else {
            // Fallback an toàn cho Windows/XAMPP
            if (!@rename($temp_file, $file_path)) {
                if (!copy($temp_file, $file_path)) {
                    error_log('[ZALO SDUI] CRITICAL: Cannot move JSON file');
                    set_transient('zalo_sdui_error', 'LỖI HỆ THỐNG: Không thể copy file JSON tĩnh. Báo ngay cho IT.', 60);
                    return;
                }
                unlink($temp_file);
            }
            error_log('[ZALO SDUI] JSON successfully rebuilt at ' . current_time('mysql'));
        }
    }

    private function sanitize_action_value($type, $value)
    {
        if ($type === 'open_url') {
            return esc_url_raw($value);
        } elseif ($type === 'call') {
            return preg_replace('/[^0-9+]/', '', $value);
        }
        return sanitize_text_field($value);
    }

    public function display_validation_errors()
    {
        $error = get_transient('zalo_sdui_error');
        if ($error) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Hệ thống Zalo App:</strong> ' . esc_html($error) . '</p></div>';
        }
    }
}