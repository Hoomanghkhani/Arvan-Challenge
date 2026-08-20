<?php
if (!defined('ABSPATH')) {
    exit;
}

function arvan_store_enqueue_scripts() {
    global $post;
    
    $is_store_page = is_page(array('arvan-store', 'arvan-wallet', 'arvan-cloud-server', 'arvan-cdn', 'arvan-storage'));
    $has_shortcode = (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'arvan_store'));

    if ($is_store_page || $has_shortcode) {
        wp_enqueue_style('vazirmatn-font', 'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;900&display=swap', array(), null);
        wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', array(), '3.4.0', false);
        wp_enqueue_script('vue', 'https://unpkg.com/vue@3/dist/vue.global.js', array(), '3.0.0', false);
        
        wp_enqueue_script(
            'arvan-store-app',
            ARVAN_STORE_PLUGIN_URL . 'frontend/app.js',
            array('vue'),
            time(), // Bust cache for live updates
            true
        );
        
        wp_localize_script('arvan-store-app', 'arvanStoreData', array(
            'rest_url' => esc_url_raw(rest_url('arvan-store/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'is_logged_in' => is_user_logged_in() ? 1 : 0
        ));
    }
}
add_action('wp_enqueue_scripts', 'arvan_store_enqueue_scripts');

function arvan_store_shortcode($atts) {
    $atts = shortcode_atts(array(
        'type' => 'cloud_server', // default to Cloud Server product
    ), $atts);
    
    ob_start();
    ?>
    <div id="arvan-store-app" data-view="<?php echo esc_attr($atts['type']); ?>">
        <!-- Vue App Mount Point -->
        <div style="text-align: center; padding: 40px; font-family: Tahoma, sans-serif; color: #64748b;">
            <p>⏳ در حال بارگذاری فروشگاه ابری آروان‌کلاد...</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('arvan_store', 'arvan_store_shortcode');

function arvan_store_template_redirect() {
    if (isset($_GET['standalone']) && $_GET['standalone'] == '1') {
        ?>
        <!DOCTYPE html>
        <html lang="fa" dir="rtl" class="h-full bg-slate-100">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
            <title>فروشگاه محصولات ابری آروان‌کلاد | سرخاب</title>
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;900&display=swap">
            <script src="https://cdn.tailwindcss.com"></script>
            <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
            <style>
                * { -webkit-tap-highlight-color: transparent; }
                body { 
                    font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, Tahoma, sans-serif; 
                    margin: 0; 
                    padding: 0; 
                    background-color: #f1f5f9; 
                    min-height: 100vh;
                    -webkit-font-smoothing: antialiased;
                }
                .no-scrollbar::-webkit-scrollbar { display: none; }
                .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
                @keyframes scaleIn {
                    from { transform: scale(0.95); opacity: 0; }
                    to { transform: scale(1); opacity: 1; }
                }
                .animate-scale { animation: scaleIn 0.2s ease-out forwards; }
            </style>
        </head>
        <body class="bg-slate-100 min-h-screen p-0 sm:p-4 md:p-8">
            <div id="arvan-store-app" data-view="<?php echo esc_attr($_GET['view'] ?? 'cloud_server'); ?>">
                <div style="text-align: center; padding: 60px 20px; color: #64748b; font-size: 14px;">
                    ⏳ در حال بارگذاری دیزاین‌سیستم سرخاب آروان‌کلاد...
                </div>
            </div>
            <script>
                var arvanStoreData = {
                    rest_url: "<?php echo esc_url_raw(rest_url('arvan-store/v1')); ?>",
                    nonce: "<?php echo wp_create_nonce('wp_rest'); ?>",
                    is_logged_in: <?php echo is_user_logged_in() ? '1' : '0'; ?>
                };
            </script>
            <script src="<?php echo ARVAN_STORE_PLUGIN_URL . 'frontend/app.js?ver=' . time(); ?>"></script>
        </body>
        </html>
        <?php
        exit;
    }
}
add_action('template_redirect', 'arvan_store_template_redirect');



