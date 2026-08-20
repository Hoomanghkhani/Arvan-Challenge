<?php
/**
 * Plugin Name: ArvanCloud Store
 * Description: Sell ArvanCloud products (Cloud Server, CDN, Object Storage) directly from your WordPress site.
 * Version: 2.0.0
 * Author: Antigravity
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ARVAN_STORE_VERSION', '2.0.0');
define('ARVAN_STORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ARVAN_STORE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Function to automatically create store pages
function arvan_store_create_default_pages() {
    $pages = array(
        array(
            'title'   => 'فروشگاه جامع محصولات ابری',
            'slug'    => 'arvan-store',
            'content' => '[arvan_store]'
        ),
        array(
            'title'   => 'کیف پول و شارژ موجودی',
            'slug'    => 'arvan-wallet',
            'content' => '[arvan_store type="wallet"]'
        ),
        array(
            'title'   => 'خرید سرور ابری',
            'slug'    => 'arvan-cloud-server',
            'content' => '[arvan_store type="cloud_server"]'
        ),
        array(
            'title'   => 'شبکه توزیع محتوا (CDN)',
            'slug'    => 'arvan-cdn',
            'content' => '[arvan_store type="cdn"]'
        ),
        array(
            'title'   => 'فضای ابری (Object Storage)',
            'slug'    => 'arvan-storage',
            'content' => '[arvan_store type="object_storage"]'
        ),
    );

    foreach ($pages as $p) {
        $existing = get_page_by_path($p['slug']);
        if (!$existing) {
            wp_insert_post(array(
                'post_title'   => $p['title'],
                'post_name'    => $p['slug'],
                'post_content' => $p['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'comment_status' => 'closed'
            ));
        }
    }
}

// Initialize plugin
function arvan_store_init() {
    require_once ARVAN_STORE_PLUGIN_DIR . 'includes/auth.php';
    require_once ARVAN_STORE_PLUGIN_DIR . 'includes/admin-settings.php';
    require_once ARVAN_STORE_PLUGIN_DIR . 'includes/rest-api.php';
    require_once ARVAN_STORE_PLUGIN_DIR . 'includes/shortcode.php';
    require_once ARVAN_STORE_PLUGIN_DIR . 'includes/cron.php';
}
add_action('plugins_loaded', 'arvan_store_init');

// Hook page creation to init hook when WP is fully loaded
function arvan_store_maybe_create_default_pages() {
    if (!get_option('arvan_store_default_pages_created')) {
        arvan_store_create_default_pages();
        update_option('arvan_store_default_pages_created', 1);
    }
}
add_action('init', 'arvan_store_maybe_create_default_pages');

// Activation hook
register_activation_hook(__FILE__, 'arvan_store_activate');
function arvan_store_activate() {
    global $wpdb;
    
    // Create services table
    $table_name = $wpdb->prefix . 'arvan_services';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        product_type varchar(50) NOT NULL,
        config text NOT NULL,
        hourly_price decimal(10,2) NOT NULL,
        status varchar(20) DEFAULT 'active',
        negative_since datetime DEFAULT NULL,
        arvan_resource_id varchar(100) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Schedule cron
    if (!wp_next_scheduled('arvan_hourly_billing_event')) {
        wp_schedule_event(time(), 'hourly', 'arvan_hourly_billing_event');
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'arvan_store_deactivate');
function arvan_store_deactivate() {
    wp_clear_scheduled_hook('arvan_hourly_billing_event');
}
