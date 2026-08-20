<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('arvan_hourly_billing_event', 'arvan_store_process_hourly_billing');

function arvan_store_process_hourly_billing() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'arvan_services';
    
    // Get all active or suspended services
    $services = $wpdb->get_results("SELECT * FROM $table_name WHERE status IN ('active', 'suspended')");
    
    $user_balances = array();
    
    foreach ($services as $service) {
        $user_id = $service->user_id;
        
        if (!isset($user_balances[$user_id])) {
            $balance = get_user_meta($user_id, 'arvan_wallet_balance', true);
            $user_balances[$user_id] = $balance === '' ? 0 : floatval($balance);
        }
        
        // Deduct hourly price if active
        if ($service->status === 'active') {
            $user_balances[$user_id] -= floatval($service->hourly_price);
        }
        
        // Check if balance is negative
        if ($user_balances[$user_id] < 0) {
            // Apply Service Termination rules
            if ($service->status === 'active') {
                // Just became negative, or hasn't been marked yet
                $wpdb->update(
                    $table_name,
                    array('status' => 'suspended', 'negative_since' => current_time('mysql')),
                    array('id' => $service->id)
                );
                // Call API to suspend/power-off service
                arvan_store_suspend_service($service);
                
            } elseif ($service->status === 'suspended' && !empty($service->negative_since)) {
                // Check how long it has been suspended
                $negative_since = strtotime($service->negative_since);
                $now = current_time('timestamp');
                $hours_passed = ($now - $negative_since) / 3600;
                
                if ($hours_passed >= 24) {
                    // After 24 hours of negative balance, terminate completely
                    $wpdb->update(
                        $table_name,
                        array('status' => 'terminated'),
                        array('id' => $service->id)
                    );
                    // Call API to delete service
                    arvan_store_terminate_service($service);
                }
            }
        } else {
            // Balance is positive. If service was suspended, reactivate it
            if ($service->status === 'suspended') {
                $wpdb->update(
                    $table_name,
                    array('status' => 'active', 'negative_since' => null),
                    array('id' => $service->id)
                );
                // Call API to power-on
                arvan_store_reactivate_service($service);
            }
        }
    }
    
    // Save updated balances back to user meta
    foreach ($user_balances as $user_id => $new_balance) {
        update_user_meta($user_id, 'arvan_wallet_balance', $new_balance);
    }
}

// Mocked API Calls for Suspension/Termination
// In a real app, these would hit ArvanCloud APIs to PowerOff/Delete resources
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
