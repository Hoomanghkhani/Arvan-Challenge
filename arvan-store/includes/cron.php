<?php
if (!defined('ABSPATH')) {
    exit;
}

// 1. Hourly Billing & Consumption Cron
add_action('arvan_hourly_billing_event', 'arvan_store_process_hourly_billing');

// 2. Daily Settlement Cron (Aggregating Reseller Margin vs Arvan Cost)
add_action('arvan_daily_settlement_event', 'arvan_store_process_daily_settlement');

function arvan_store_process_hourly_billing() {
    global $wpdb;
    $services_table = $wpdb->prefix . 'arvan_services';
    $trans_table = $wpdb->prefix . 'arvan_transactions';
    $logs_table = $wpdb->prefix . 'arvan_usage_logs';
    
    // Get reseller margin percentage (Default 15%, max 20%)
    $reseller_margin_percent = min(20, max(0, floatval(get_option('arvan_store_reseller_margin', 15))));
    $threshold_warning = floatval(get_option('arvan_store_threshold_warning', 5000));

    // Get all active or suspended services
    $services = $wpdb->get_results("SELECT * FROM $services_table WHERE status IN ('active', 'suspended')");
    
    $user_balances = array();
    
    foreach ($services as $service) {
        $user_id = $service->user_id;
        
        if (!isset($user_balances[$user_id])) {
            $balance = get_user_meta($user_id, 'arvan_wallet_balance', true);
            $user_balances[$user_id] = $balance === '' ? 0 : floatval($balance);
        }
        
        if ($service->status === 'active') {
            $base_price = floatval($service->hourly_price);
            $margin_amount = ($base_price * $reseller_margin_percent) / 100;
            $final_deduction = $base_price + $margin_amount;
            
            // Deduct from virtual wallet
            $user_balances[$user_id] -= $final_deduction;
            
            // Record in Custom Usage Log Table
            $wpdb->insert($logs_table, array(
                'service_id'       => $service->id,
                'user_id'          => $user_id,
                'base_price'       => $base_price,
                'reseller_margin'  => $margin_amount,
                'final_price'      => $final_deduction,
                'recorded_at'      => current_time('mysql')
            ));

            // Record in Custom Ledger Transactions Table
            $wpdb->insert($trans_table, array(
                'user_id'     => $user_id,
                'amount'      => -$final_deduction,
                'type'        => 'usage_deduction',
                'status'      => 'success',
                'description' => "کسر هزینه مصرف ساعتی سرویس #{$service->id} ({$service->product_type})",
                'created_at'  => current_time('mysql')
            ));
        }
        
        // Check if balance is negative or below threshold
        if ($user_balances[$user_id] < 0) {
            // Apply Service Termination rules
            if ($service->status === 'active') {
                $wpdb->update(
                    $services_table,
                    array('status' => 'suspended', 'negative_since' => current_time('mysql')),
                    array('id' => $service->id)
                );
                arvan_store_suspend_service($service);
                
            } elseif ($service->status === 'suspended' && !empty($service->negative_since)) {
                $negative_since = strtotime($service->negative_since);
                $now = current_time('timestamp');
                $hours_passed = ($now - $negative_since) / 3600;
                
                if ($hours_passed >= 24) {
                    $wpdb->update(
                        $services_table,
                        array('status' => 'terminated'),
                        array('id' => $service->id)
                    );
                    arvan_store_terminate_service($service);
                }
            }
        } else {
            // Check threshold notification
            if ($user_balances[$user_id] <= $threshold_warning) {
                $user_data = get_userdata($user_id);
                if ($user_data && !empty($user_data->user_email)) {
                    wp_mail(
                        $user_data->user_email,
                        '⚠️ هشدار کاهش موجودی کیف پول آروان‌کلاد',
                        "کاربر گرامی، موجودی کیف پول شما به " . number_format($user_balances[$user_id]) . " تومان رسیده است. لطفاً جهت جلوگیری از خاموش شدن سرویس‌ها نسبت به افزایش موجودی اقدام نمایید."
                    );
                }
            }

            // Balance is positive. If service was suspended, reactivate it
            if ($service->status === 'suspended') {
                $wpdb->update(
                    $services_table,
                    array('status' => 'active', 'negative_since' => null),
                    array('id' => $service->id)
                );
                arvan_store_reactivate_service($service);
            }
        }
    }
    
    // Save updated balances back to user meta
    foreach ($user_balances as $user_id => $new_balance) {
        update_user_meta($user_id, 'arvan_wallet_balance', $new_balance);
    }
}

// 3. Daily Settlement Automation
function arvan_store_process_daily_settlement() {
    global $wpdb;
    $logs_table = $wpdb->prefix . 'arvan_usage_logs';
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    $summary = $wpdb->get_row($wpdb->prepare(
        "SELECT SUM(base_price) as total_arvan_cost, SUM(reseller_margin) as total_reseller_profit, SUM(final_price) as total_billed FROM $logs_table WHERE DATE(recorded_at) = %s",
        $yesterday
    ));
    
    if ($summary && $summary->total_billed > 0) {
        update_option('arvan_last_settlement_date', $yesterday);
        update_option('arvan_last_settlement_data', array(
            'date' => $yesterday,
            'total_billed' => floatval($summary->total_billed),
            'arvan_cost' => floatval($summary->total_arvan_cost),
            'reseller_profit' => floatval($summary->total_reseller_profit),
            'status' => 'settled_successfully'
        ));
    }
}

// API Calls for Suspension/Termination/Reactivation
function arvan_store_suspend_service($service) {
    $api_key = get_option('arvan_store_api_key');
    if (empty($api_key) || empty($service->arvan_resource_id)) return;
    
    $headers = array('Authorization' => 'Apikey ' . $api_key, 'Accept' => 'application/json');
    if ($service->product_type === 'cloud_server') {
        wp_remote_post("https://napi.arvancloud.ir/ecc/v1/regions/ir-thr-c1/servers/{$service->arvan_resource_id}/power-off", ['headers' => $headers]);
    }
}

function arvan_store_terminate_service($service) {
    $api_key = get_option('arvan_store_api_key');
    if (empty($api_key) || empty($service->arvan_resource_id)) return;
    
    $headers = array('Authorization' => 'Apikey ' . $api_key, 'Accept' => 'application/json');
    if ($service->product_type === 'cloud_server') {
        wp_remote_request("https://napi.arvancloud.ir/ecc/v1/regions/ir-thr-c1/servers/{$service->arvan_resource_id}", ['method' => 'DELETE', 'headers' => $headers]);
    }
}

function arvan_store_reactivate_service($service) {
    $api_key = get_option('arvan_store_api_key');
    if (empty($api_key) || empty($service->arvan_resource_id)) return;
    
    $headers = array('Authorization' => 'Apikey ' . $api_key, 'Accept' => 'application/json');
    if ($service->product_type === 'cloud_server') {
        wp_remote_post("https://napi.arvancloud.ir/ecc/v1/regions/ir-thr-c1/servers/{$service->arvan_resource_id}/power-on", ['headers' => $headers]);
    }
}

