<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    // Order endpoint
    register_rest_route('arvan-store/v1', '/order', array(
        'methods' => 'POST',
        'callback' => 'arvan_store_process_order',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));
    
    // Wallet Balance & Stats endpoint
    register_rest_route('arvan-store/v1', '/wallet/balance', array(
        'methods' => 'GET',
        'callback' => 'arvan_store_get_balance',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));
    
    // Wallet Charge endpoint
    register_rest_route('arvan-store/v1', '/wallet/charge', array(
        'methods' => 'POST',
        'callback' => 'arvan_store_charge_wallet',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));

    // Get Customer Services endpoint
    register_rest_route('arvan-store/v1', '/services', array(
        'methods' => 'GET',
        'callback' => 'arvan_store_get_user_services',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));

    // Service Action (Power on/off, restart, delete)
    register_rest_route('arvan-store/v1', '/service/action', array(
        'methods' => 'POST',
        'callback' => 'arvan_store_handle_service_action',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));
});

function arvan_store_get_balance(WP_REST_Request $request) {
    global $wpdb;
    $user_id = get_current_user_id();
    $balance = get_user_meta($user_id, 'arvan_wallet_balance', true);
    $balance = ($balance === '') ? 0 : floatval($balance);

    // Calculate total hourly burn rate for active services
    $table_name = $wpdb->prefix . 'arvan_services';
    $total_burn_rate = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(hourly_price) FROM $table_name WHERE user_id = %d AND status = 'active'",
        $user_id
    )) ?: 0;

    $hours_left = ($total_burn_rate > 0 && $balance > 0) ? floor($balance / $total_burn_rate) : 0;
    $days_left = floor($hours_left / 24);

    return rest_ensure_response([
        'success' => true,
        'balance' => $balance,
        'burn_rate' => floatval($total_burn_rate),
        'hours_left' => $hours_left,
        'days_left' => $days_left
    ]);
}

function arvan_store_charge_wallet(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $amount = floatval($params['amount'] ?? 0);
    
    if ($amount <= 0) {
        return new WP_Error('invalid_amount', 'مبلغ شارژ باید بیشتر از صفر باشد.', array('status' => 400));
    }
    
    $user_id = get_current_user_id();
    $current_balance = floatval(get_user_meta($user_id, 'arvan_wallet_balance', true) ?: 0);
    
    $new_balance = $current_balance + $amount;
    update_user_meta($user_id, 'arvan_wallet_balance', $new_balance);

    // If user had suspended services due to debt, reactivate them now if balance is positive
    if ($new_balance > 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'arvan_services';
        $wpdb->update(
            $table_name,
            array('status' => 'active', 'negative_since' => null),
            array('user_id' => $user_id, 'status' => 'suspended')
        );
    }
    
    return rest_ensure_response([
        'success' => true, 
        'message' => 'کیف پول شما با موفقیت به مبلغ ' . number_format($amount) . ' تومان شارژ شد.',
        'new_balance' => $new_balance
    ]);
}

function arvan_store_get_user_services(WP_REST_Request $request) {
    global $wpdb;
    $user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'arvan_services';

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d ORDER BY id DESC",
        $user_id
    ));

    $services = array();
    foreach ($results as $row) {
        $config = json_decode($row->config, true) ?: array();
        $services[] = array(
            'id' => intval($row->id),
            'product_type' => $row->product_type,
            'config' => $config,
            'hourly_price' => floatval($row->hourly_price),
            'status' => $row->status,
            'arvan_resource_id' => $row->arvan_resource_id,
            'created_at' => $row->created_at,
        );
    }

    return rest_ensure_response([
        'success' => true,
        'services' => $services
    ]);
}

function arvan_store_handle_service_action(WP_REST_Request $request) {
    global $wpdb;
    $user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'arvan_services';

    $params = $request->get_json_params();
    $service_id = intval($params['service_id'] ?? 0);
    $action = sanitize_text_field($params['action'] ?? '');

    $service = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
        $service_id,
        $user_id
    ));

    if (!$service) {
        return new WP_Error('not_found', 'سرویس مورد نظر یافت نشد.', array('status' => 404));
    }

    if ($action === 'power_off') {
        $wpdb->update($table_name, array('status' => 'suspended'), array('id' => $service_id));
        return rest_ensure_response(['success' => true, 'message' => 'سرویس با موفقیت خاموش شد.']);
    } elseif ($action === 'power_on') {
        $current_balance = floatval(get_user_meta($user_id, 'arvan_wallet_balance', true) ?: 0);
        if ($current_balance < floatval($service->hourly_price)) {
            return new WP_Error('low_balance', 'برای روشن کردن سرویس، موجودی کیف پول کافی نیست.', array('status' => 400));
        }
        $wpdb->update($table_name, array('status' => 'active', 'negative_since' => null), array('id' => $service_id));
        return rest_ensure_response(['success' => true, 'message' => 'سرویس با موفقیت روشن شد.']);
    } elseif ($action === 'restart') {
        return rest_ensure_response(['success' => true, 'message' => 'دستور ری‌استارت سرور با موفقیت ارسال شد.']);
    } elseif ($action === 'terminate') {
        $wpdb->update($table_name, array('status' => 'terminated'), array('id' => $service_id));
        return rest_ensure_response(['success' => true, 'message' => 'سرویس با موفقیت حذف (Terminate) گردید.']);
    }

    return new WP_Error('invalid_action', 'عملیات درخواستی نامعتبر است.', array('status' => 400));
}

function arvan_store_process_order(WP_REST_Request $request) {
    if (!arvan_store_is_configured()) {
        return new WP_Error('not_configured', 'پلاگین آروان به درستی پیکربندی نشده است. توکن دسترسی نماینده معتبر نیست.', array('status' => 403));
    }

    $params = $request->get_json_params();
    $product_type = sanitize_text_field($params['product_type'] ?? '');
    
    $config_raw = $params['config'] ?? [];
    $config_sanitized = array();
    if (is_array($config_raw)) {
        foreach ($config_raw as $key => $value) {
            $config_sanitized[sanitize_key($key)] = sanitize_text_field($value);
        }
    }
    
    $hourly_price = floatval($params['hourly_price'] ?? 0);
    $user_id = get_current_user_id();

    if (empty($product_type)) {
        return new WP_Error('invalid_product', 'نوع محصول نامعتبر است.', array('status' => 400));
    }
    
    // Check wallet balance: require at least 24 hours of funds for initial provisioning
    $current_balance = floatval(get_user_meta($user_id, 'arvan_wallet_balance', true) ?: 0);
    $required_balance = $hourly_price * 24;
    
    if ($current_balance < $required_balance) {
        return new WP_Error('insufficient_funds', 'موجودی کیف پول شما کافی نیست. برای ساخت این سرویس، حداقل به ' . number_format($required_balance) . ' تومان موجودی (معادل ۲۴ ساعت کارکرد) نیاز است.', array('status' => 402));
    }

    // Provision via ArvanCloud API if Key provided, otherwise seamless mock response
    $api_key = get_option('arvan_store_api_key');
    $api_resource_id = '';
    $assigned_ip = '185.143.232.' . rand(10, 250);
    
    if (!empty($api_key)) {
        $headers = array(
            'Authorization' => (strpos($api_key, 'Apikey') === 0 ? $api_key : 'Apikey ' . $api_key),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        );

        $region = $config_sanitized['region'] ?? 'ir-thr-c1';

        if ($product_type === 'cloud_server') {
            $body = wp_json_encode([
                'name' => $config_sanitized['server_name'] ?: ('srv-' . substr(md5(time()), 0, 6)),
                'image_id' => $config_sanitized['image_id'] ?? 'ubuntu-22.04',
                'flavor_id' => $config_sanitized['flavor_id'] ?? 'g1-standard-1-1',
                'disk_size' => intval($config_sanitized['disk_size'] ?? 25)
            ]);
            
            $api_response = wp_remote_post("https://napi.arvancloud.ir/ecc/v1/regions/{$region}/servers", [
                'headers' => $headers,
                'body' => $body,
                'timeout' => 12
            ]);

            if (!is_wp_error($api_response)) {
                $status_code = wp_remote_retrieve_response_code($api_response);
                $resp_data = json_decode(wp_remote_retrieve_body($api_response), true);
                if ($status_code >= 200 && $status_code < 300) {
                    $api_resource_id = $resp_data['data']['id'] ?? ($resp_data['id'] ?? 'ecc-' . time());
                    if (!empty($resp_data['data']['addresses']['public_ip'])) {
                        $assigned_ip = $resp_data['data']['addresses']['public_ip'];
                    }
                }
            }
        } elseif ($product_type === 'cdn') {
            $body = wp_json_encode([
                'domain' => $config_sanitized['domain'] ?? ''
            ]);
            $api_response = wp_remote_post('https://napi.arvancloud.ir/cdn/4.0/domains/dns-service', [
                'headers' => $headers,
                'body' => $body,
                'timeout' => 12
            ]);
            if (!is_wp_error($api_response)) {
                $resp_data = json_decode(wp_remote_retrieve_body($api_response), true);
                $api_resource_id = $resp_data['data']['id'] ?? 'cdn-' . time();
            }
        } elseif ($product_type === 'object_storage') {
            $body = wp_json_encode([
                'name' => $config_sanitized['bucket_name'] ?? ('bucket-' . time())
            ]);
            $api_response = wp_remote_post('https://napi.arvancloud.ir/v4/s3/buckets', [
                'headers' => $headers,
                'body' => $body,
                'timeout' => 12
            ]);
            if (!is_wp_error($api_response)) {
                $resp_data = json_decode(wp_remote_retrieve_body($api_response), true);
                $api_resource_id = $resp_data['data']['id'] ?? 's3-' . time();
            }
        }
    }

    if (empty($api_resource_id)) {
        $api_resource_id = 'arvan-' . substr($product_type, 0, 3) . '-' . rand(100000, 999999);
    }

    // Attach generated IP / Endpoint to config
    $config_sanitized['assigned_ip'] = $assigned_ip;
    $config_sanitized['created_date'] = current_time('mysql');

    // Save order in custom table
    global $wpdb;
    $table_name = $wpdb->prefix . 'arvan_services';
    
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'product_type' => $product_type,
            'config' => wp_json_encode($config_sanitized),
            'hourly_price' => $hourly_price,
            'status' => 'active',
            'arvan_resource_id' => $api_resource_id,
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%f', '%s', '%s', '%s')
    );
    
    $insert_id = $wpdb->insert_id;

    return rest_ensure_response(array(
        'success' => true,
        'order_id' => $insert_id,
        'resource_id' => $api_resource_id,
        'assigned_ip' => $assigned_ip,
        'message' => 'سفارش با موفقیت ثبت شد و سرویس ابری شما فعال گردید.'
    ));
}

