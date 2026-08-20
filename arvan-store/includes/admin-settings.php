<?php
if (!defined('ABSPATH')) {
    exit;
}

function arvan_store_admin_menu() {
    add_menu_page(
        'فروشگاه آروان',
        'فروشگاه آروان',
        'manage_options',
        'arvan_store',
        'arvan_store_admin_dashboard',
        'dashicons-cloud',
        56
    );
    
    add_submenu_page(
        'arvan_store',
        'تنظیمات',
        'تنظیمات',
        'manage_options',
        'arvan_store_settings',
        'arvan_store_settings_page'
    );
}
add_action('admin_menu', 'arvan_store_admin_menu');

function arvan_store_register_settings() {
    register_setting('arvan_store_settings_group', 'arvan_store_api_key', 'sanitize_text_field');
    register_setting('arvan_store_settings_group', 'arvan_store_access_token', 'sanitize_text_field');
    register_setting('arvan_store_settings_group', 'arvan_store_reseller_margin', 'floatval');
    register_setting('arvan_store_settings_group', 'arvan_store_threshold_warning', 'floatval');
    register_setting('arvan_store_settings_group', 'arvan_store_company_name', 'sanitize_text_field');
    register_setting('arvan_store_settings_group', 'arvan_store_company_logo', 'esc_url_raw');
    register_setting('arvan_store_settings_group', 'arvan_store_company_phone', 'sanitize_text_field');
    register_setting('arvan_store_settings_group', 'arvan_store_company_email', 'sanitize_email');
    register_setting('arvan_store_settings_group', 'arvan_store_company_about', 'sanitize_textarea_field');
}
add_action('admin_init', 'arvan_store_register_settings');

function arvan_store_settings_page() {
    if (!current_user_can('manage_options')) return;
    
    $token = get_option('arvan_store_access_token');
    $is_valid = arvan_store_is_token_valid($token);
    $margin = get_option('arvan_store_reseller_margin', 15);
    $threshold = get_option('arvan_store_threshold_warning', 5000);
    ?>
    <div class="wrap" dir="rtl" style="font-family: 'Vazirmatn', Tahoma, sans-serif; max-width: 1000px; margin: 20px auto;">
        <h1 style="margin-bottom: 20px; font-weight: 900; color: #0f172a;">⚙️ تنظیمات نمایندگی و اتصال آروان‌کلاد</h1>
        
        <?php if (!empty($token)): ?>
            <?php if ($is_valid): ?>
                <div class="notice notice-success inline" style="padding: 12px; border-radius: 8px; border-right: 4px solid #10b981;"><p>✅ <strong>توکن دسترسی معتبر است:</strong> فروشگاه فعال و آماده خدمت‌رسانی می‌باشد.</p></div>
            <?php else: ?>
                <div class="notice notice-error inline" style="padding: 12px; border-radius: 8px; border-right: 4px solid #ef4444;"><p>❌ <strong>توکن دسترسی نامعتبر است:</strong> فروشگاه غیرفعال می‌باشد.</p></div>
            <?php endif; ?>
        <?php endif; ?>

        <div style="background: #fff; padding: 28px; border: 1px solid #e2e8f0; border-radius: 16px; margin-top: 20px; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.05);">
            <form method="post" action="options.php">
                <?php settings_fields('arvan_store_settings_group'); ?>
                
                <h3 style="font-weight: 900; color: #FF0066; border-bottom: 2px solid #fce7f3; padding-bottom: 8px; margin-top: 0;">۱. احراز هویت و کلیدهای API اکانت مادر (Machine User)</h3>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="arvan_store_api_key"><strong>کلید API آروان‌کلاد (User API Key)</strong></label></th>
                            <td>
                                <input type="password" name="arvan_store_api_key" id="arvan_store_api_key" value="<?php echo esc_attr(get_option('arvan_store_api_key')); ?>" class="regular-text" dir="ltr" style="border-radius: 8px; padding: 8px;" />
                                <p class="description">برای ایجاد و ارتباط مستقیم با API سرور ابری، CDN و فضای ابری آروان کلاد.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="arvan_store_access_token"><strong>توکن دسترسی نماینده (Access Token)</strong></label></th>
                            <td>
                                <input type="password" name="arvan_store_access_token" id="arvan_store_access_token" value="<?php echo esc_attr(get_option('arvan_store_access_token')); ?>" class="regular-text" dir="ltr" style="border-radius: 8px; padding: 8px;" />
                                <p class="description">توکن امنیتی برای تایید اعتبار ماژول فروشگاهی (برای حالت آزمایشی مقدار <code>demo-token-123</code> مجاز است).</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h3 style="font-weight: 900; color: #FF0066; border-bottom: 2px solid #fce7f3; padding-bottom: 8px; margin-top: 24px;">۲. تنظیمات مالی و فرمول سهم ریسلر (Reseller Margin)</h3>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="arvan_store_reseller_margin"><strong>درصد سهم ریسلر (Reseller Margin %)</strong></label></th>
                            <td>
                                <input type="number" min="0" max="20" step="0.5" name="arvan_store_reseller_margin" id="arvan_store_reseller_margin" value="<?php echo esc_attr($margin); ?>" style="width: 100px; border-radius: 8px; padding: 8px;" />
                                <span>درصد (حداکثر ۲۰٪ طبق آیین‌نامه آروان)</span>
                                <p class="description">این درصد سود به قیمت پایه آروان اضافه و در کسر هزینه ساعتی به عنوان سهم ریسلر محاسبه می‌شود.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="arvan_store_threshold_warning"><strong>آستانه هشدار کاهش موجودی (تومان)</strong></label></th>
                            <td>
                                <input type="number" step="1000" name="arvan_store_threshold_warning" id="arvan_store_threshold_warning" value="<?php echo esc_attr($threshold); ?>" style="width: 160px; border-radius: 8px; padding: 8px;" />
                                <p class="description">هنگامی که موجودی کاربر به این رقم برسد، ایمیل هشدار شارژ خودکار برای جلوگیری از قطع سرویس ارسال می‌گردد.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h3 style="font-weight: 900; color: #FF0066; border-bottom: 2px solid #fce7f3; padding-bottom: 8px; margin-top: 24px;">۳. اطلاعات پایه مجموعه ریسلر (Branding & Identity)</h3>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="arvan_store_company_name">نام مجموعه ریسلر:</label></th>
                            <td><input type="text" name="arvan_store_company_name" id="arvan_store_company_name" value="<?php echo esc_attr(get_option('arvan_store_company_name', 'نمایندگی رسمی خدمات ابری آروان')); ?>" class="regular-text" style="border-radius: 8px; padding: 8px;" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="arvan_store_company_phone">تلفن پشتیبانی و تماس:</label></th>
                            <td><input type="text" name="arvan_store_company_phone" id="arvan_store_company_phone" value="<?php echo esc_attr(get_option('arvan_store_company_phone', '۰۲۱-۸۸۸۸۸۸۸۸')); ?>" class="regular-text" dir="ltr" style="border-radius: 8px; padding: 8px;" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="arvan_store_company_email">ایمیل رسمی پشتیبانی:</label></th>
                            <td><input type="email" name="arvan_store_company_email" id="arvan_store_company_email" value="<?php echo esc_attr(get_option('arvan_store_company_email', get_option('admin_email'))); ?>" class="regular-text" dir="ltr" style="border-radius: 8px; padding: 8px;" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="arvan_store_company_about">درباره مجموعه و خدمات:</label></th>
                            <td><textarea name="arvan_store_company_about" id="arvan_store_company_about" rows="3" class="large-text" style="border-radius: 8px; padding: 8px;"><?php echo esc_textarea(get_option('arvan_store_company_about', 'ارائه‌دهنده راهکارهای نوین ابری، سرورهای پایدار ابری و شتاب‌دهی محتوا بر بستر زیرساخت آروان‌کلاد')); ?></textarea></td>
                        </tr>
                    </tbody>
                </table>

                <div style="margin-top: 20px;">
                    <?php submit_button('💾 ذخیره تمامی تنظیمات ریسلر'); ?>
                </div>
            </form>
        </div>
        
        <div style="margin-top: 24px; background: #f0fdf4; border: 1px solid #bbf7d0; border-right: 4px solid #10b981; padding: 18px; border-radius: 14px;">
            <strong style="color: #166534; font-size: 14px;">🔗 برگه‌های پیش‌ساخته فروشگاه:</strong><br>
            <p style="color: #14532d; font-size: 13px; margin: 6px 0 10px 0;">برگه‌ها به صورت خودکار ایجاد شده‌اند. می‌توانید مستقیماً از لینک‌های زیر بازدید نمایید:</p>
            <ul style="list-style-type: disc; margin-right: 20px; line-height: 1.8;">
                <li><a href="<?php echo esc_url(home_url('/arvan-store/')); ?>" target="_blank" style="font-weight: bold; text-decoration: underline;">فروشگاه جامع و یکپارچه آروان (تب‌بندی شده)</a> <code>[arvan_store]</code></li>
                <li><a href="<?php echo esc_url(home_url('/arvan-wallet/')); ?>" target="_blank">کیف پول و شارژ اعتبار</a> <code>[arvan_store type="wallet"]</code></li>
                <li><a href="<?php echo esc_url(home_url('/arvan-cloud-server/')); ?>" target="_blank">خرید سرور ابری (ECC)</a> <code>[arvan_store type="cloud_server"]</code></li>
                <li><a href="<?php echo esc_url(home_url('/arvan-cdn/')); ?>" target="_blank">شبکه توزیع محتوا (CDN)</a> <code>[arvan_store type="cdn"]</code></li>
                <li><a href="<?php echo esc_url(home_url('/arvan-storage/')); ?>" target="_blank">فضای ابری (Object Storage)</a> <code>[arvan_store type="object_storage"]</code></li>
            </ul>
        </div>
    </div>
    <?php
}


function arvan_store_admin_dashboard() {
    if (!current_user_can('manage_options')) return;
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'arvan_services';
    $message = '';
    
    // Handle admin service actions (Stop, Start, Restart, Terminate)
    if (isset($_POST['arvan_admin_action']) && check_admin_referer('arvan_admin_service_action')) {
        $action = sanitize_text_field($_POST['arvan_admin_action']);
        $service_id = intval($_POST['service_id'] ?? 0);

        if ($service_id > 0) {
            $service = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $service_id));
            if ($service) {
                if ($action === 'stop') {
                    $wpdb->update($table_name, array('status' => 'suspended', 'negative_since' => current_time('mysql')), array('id' => $service_id));
                    arvan_store_suspend_service($service);
                    $message = "سرویس #{$service_id} با موفقیت توسط ادمین خاموش و به وضعیت معلق درآمد.";
                } elseif ($action === 'start') {
                    $wpdb->update($table_name, array('status' => 'active', 'negative_since' => null), array('id' => $service_id));
                    arvan_store_reactivate_service($service);
                    $message = "سرویس #{$service_id} با موفقیت توسط ادمین روشن و فعال شد.";
                } elseif ($action === 'restart') {
                    $message = "دستور ری‌استارت سرور ابری #{$service_id} با موفقیت ارسال شد.";
                } elseif ($action === 'terminate') {
                    $wpdb->update($table_name, array('status' => 'terminated'), array('id' => $service_id));
                    arvan_store_terminate_service($service);
                    $message = "سرویس #{$service_id} به صورت کامل حذف (Terminated) شد.";
                }
            }
        }
    }

    // Handle manual billing trigger for live demo
    if (isset($_POST['run_arvan_billing']) && check_admin_referer('arvan_manual_billing')) {
        arvan_store_process_hourly_billing();
        $message = "فرآیند محاسبه هزینه ساعتی و بررسی قوانین قطع سرویس (Termination Rules) با موفقیت اجرا شد.";
    }

    // Handle manual user wallet recharge by Admin
    if (isset($_POST['arvan_admin_wallet_charge']) && check_admin_referer('arvan_admin_wallet_action')) {
        $target_user_id = intval($_POST['target_user_id'] ?? 0);
        $charge_amount = floatval($_POST['charge_amount'] ?? 0);
        if ($target_user_id > 0 && $charge_amount != 0) {
            $curr = floatval(get_user_meta($target_user_id, 'arvan_wallet_balance', true) ?: 0);
            $new_bal = $curr + $charge_amount;
            update_user_meta($target_user_id, 'arvan_wallet_balance', $new_bal);
            if ($new_bal > 0) {
                $wpdb->update($table_name, array('status' => 'active', 'negative_since' => null), array('user_id' => $target_user_id, 'status' => 'suspended'));
            }
            $message = "کیف پول کاربر (ID: {$target_user_id}) با موفقیت به مبلغ " . number_format($charge_amount) . " تومان به‌روزرسانی شد. موجودی جدید: " . number_format($new_bal) . " تومان";
        }
    }

    $total_orders = $wpdb->get_var("SELECT COUNT(*) FROM $table_name") ?: 0;
    $active_orders = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'active'") ?: 0;
    $suspended_orders = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'suspended'") ?: 0;
    $total_hourly_revenue = $wpdb->get_var("SELECT SUM(hourly_price) FROM $table_name WHERE status = 'active'") ?: 0;

    $reseller_margin_percent = min(20, max(0, floatval(get_option('arvan_store_reseller_margin', 15))));
    $reseller_hourly_profit = ($total_hourly_revenue * $reseller_margin_percent) / 100;
    
    $services = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 50");
    $trans_table = $wpdb->prefix . 'arvan_transactions';
    $transactions = $wpdb->get_results("SELECT * FROM $trans_table ORDER BY id DESC LIMIT 20");
    $last_settlement = get_option('arvan_last_settlement_data');
    ?>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;900&display=swap">
    <div class="wrap" dir="rtl" style="font-family: 'Vazirmatn', Tahoma, sans-serif; max-width: 1200px; margin: 20px auto 40px auto;">
        
        <!-- Sorkhab Admin Top Header -->
        <div style="background: linear-gradient(135deg, #FF0066 0%, #E11D48 40%, #7928CA 100%); padding: 28px 32px; border-radius: 20px; color: #ffffff; box-shadow: 0 10px 25px -5px rgba(255, 0, 102, 0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 25px;">
            <div>
                <div style="display: inline-block; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(8px); padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 900; letter-spacing: 0.5px; margin-bottom: 8px;">
                    🎨 SORKHAB DESIGN SYSTEM | سهم ریسلر: <?php echo esc_html($reseller_margin_percent); ?>٪
                </div>
                <h1 style="font-weight: 900; color: #ffffff; margin: 0; font-size: 24px; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    پیشخوان مدیریت فروش و نمایندگی آروان‌کلاد
                </h1>
                <p style="color: rgba(255, 255, 255, 0.85); margin: 6px 0 0 0; font-size: 13px;">
                    مانیتورینگ لحظه‌ای، کنترل عملیاتی سرویس‌ها، سهم سود ریسلر و تسویه خودکار با آروان
                </p>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <form method="post" style="margin: 0;">
                    <?php wp_nonce_field('arvan_manual_billing'); ?>
                    <button type="submit" name="run_arvan_billing" value="1" class="button" style="background: rgba(255, 255, 255, 0.95); color: #FF0066; font-weight: 900; border: none; padding: 8px 18px; border-radius: 12px; font-size: 12px; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                        ⚡ اجرای چرخه کسر هزینه ساعتی
                    </button>
                </form>
                <a href="<?php echo esc_url(home_url('/arvan-store/?standalone=1')); ?>" target="_blank" class="button" style="background: #0f172a; color: #ffffff; font-weight: 900; border: none; padding: 8px 18px; border-radius: 12px; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    🚀 فروشگاه
                </a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-right: 5px solid #10b981; padding: 14px 20px; border-radius: 14px; margin-bottom: 20px; color: #065f46; font-size: 13px; font-weight: 700; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.08);">
                ✅ <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>

        <!-- Sorkhab KPI Metrics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 25px;">
            <div style="background: #ffffff; padding: 22px; border-radius: 18px; border: 1px solid #f1f5f9; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.04);">
                <div style="color: #64748b; font-size: 12px; font-weight: 700;">کل سرویس‌های ایجاد شده</div>
                <div style="font-size: 28px; font-weight: 900; color: #0f172a; margin-top: 6px;"><?php echo number_format($total_orders); ?></div>
                <div style="margin-top: 6px; font-size: 11px; color: #94a3b8;">در ۳ محصول سرور ابری، CDN و S3</div>
            </div>
            <div style="background: #ffffff; padding: 22px; border-radius: 18px; border: 1px solid #f1f5f9; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.04); border-right: 4px solid #10b981;">
                <div style="color: #64748b; font-size: 12px; font-weight: 700;">سرویس‌های روشن و فعال</div>
                <div style="font-size: 28px; font-weight: 900; color: #10b981; margin-top: 6px;"><?php echo number_format($active_orders); ?></div>
                <div style="margin-top: 6px; font-size: 11px; color: #10b981; font-weight: 700;">🟢 وضعیت پایدار و درآمدزا</div>
            </div>
            <div style="background: #ffffff; padding: 22px; border-radius: 18px; border: 1px solid #f1f5f9; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.04); border-right: 4px solid #f59e0b;">
                <div style="color: #64748b; font-size: 12px; font-weight: 700;">سرویس‌های معلق / بدهکار</div>
                <div style="font-size: 28px; font-weight: 900; color: #f59e0b; margin-top: 6px;"><?php echo number_format($suspended_orders); ?></div>
                <div style="margin-top: 6px; font-size: 11px; color: #b45309;">🟡 خاموش شده به علت اتمام شارژ</div>
            </div>
            <div style="background: #ffffff; padding: 22px; border-radius: 18px; border: 1px solid #f1f5f9; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.04); border-right: 4px solid #FF0066;">
                <div style="color: #64748b; font-size: 12px; font-weight: 700;">سهم سود ساعتی ریسلر (<?php echo esc_html($reseller_margin_percent); ?>٪)</div>
                <div style="font-size: 28px; font-weight: 900; color: #FF0066; margin-top: 6px;"><?php echo number_format($reseller_hourly_profit); ?> <span style="font-size: 12px; font-weight: normal; color: #64748b;">تومان/ساعت</span></div>
                <div style="margin-top: 6px; font-size: 11px; color: #e11d48; font-weight: 700;">کل فاکتور ساعتی: <?php echo number_format($total_hourly_revenue + $reseller_hourly_profit); ?> ت</div>
            </div>
        </div>

        <!-- Quick User Wallet Adjustment Tool -->
        <div style="background: #ffffff; padding: 22px 28px; border-radius: 18px; border: 1px solid #f1f5f9; margin-bottom: 25px; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.04);">
            <div style="font-weight: 900; font-size: 14px; color: #0f172a; display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                <span>💳</span>
                <span>مدیریت و شارژ دستی کیف پول کاربران (Admin Credit Manager):</span>
            </div>
            <form method="post" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
                <?php wp_nonce_field('arvan_admin_wallet_action'); ?>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <label style="font-size: 12px; font-weight: 700; color: #475569;">شناسه کاربر (ID):</label>
                    <input type="number" name="target_user_id" value="1" style="width: 80px; padding: 6px 10px; border-radius: 10px; border: 1px solid #cbd5e1; font-family: 'Vazirmatn';" required />
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <label style="font-size: 12px; font-weight: 700; color: #475569;">مبلغ به تومان:</label>
                    <input type="number" name="charge_amount" placeholder="مثلا: 200000" style="width: 180px; padding: 6px 10px; border-radius: 10px; border: 1px solid #cbd5e1; font-family: 'Vazirmatn';" required />
                </div>
                <button type="submit" name="arvan_admin_wallet_charge" value="1" class="button" style="background: #0f172a; color: #ffffff; font-weight: 900; border-radius: 10px; padding: 6px 18px; border: none; cursor: pointer;">
                    اعمال روی حساب کاربر
                </button>
            </form>
        </div>

        <!-- Sorkhab Resource Table -->
        <div style="background: #ffffff; border-radius: 20px; border: 1px solid #f1f5f9; overflow: hidden; box-shadow: 0 4px 20px -3px rgba(0,0,0,0.05); margin-bottom: 25px;">
            <div style="padding: 18px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 style="font-weight: 900; font-size: 15px; color: #0f172a; margin: 0;">لیست سفارشات و کنترل عملیاتی منابع</h3>
                    <p style="font-size: 12px; color: #94a3b8; margin: 2px 0 0 0;">عملیات روشن، خاموش، ری‌استارت و حذف دائمی منابع ابری</p>
                </div>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="wp-list-table widefat fixed striped table-view-list" style="border: none; margin: 0;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th style="font-weight: 900; width: 60px; color: #475569;">شناسه</th>
                            <th style="font-weight: 900; width: 130px; color: #475569;">مشتری</th>
                            <th style="font-weight: 900; width: 120px; color: #475569;">محصول</th>
                            <th style="font-weight: 900; color: #475569;">مشخصات، IP و کانفیگ</th>
                            <th style="font-weight: 900; width: 120px; color: #475569;">هزینه ساعتی</th>
                            <th style="font-weight: 900; width: 110px; color: #475569;">وضعیت منبع</th>
                            <th style="font-weight: 900; width: 220px; color: #475569;">عملیات کنترلی ادمین</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($services)): ?>
                            <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-size: 13px;">هنوز هیچ سرویسی در سیستم ثبت نشده است.</td></tr>
                        <?php else: ?>
                            <?php foreach ($services as $service): 
                                $user_info = get_userdata($service->user_id);
                                $user_name = $user_info ? $user_info->user_login : 'Guest';
                                $config = json_decode($service->config, true) ?: array();
                            ?>
                                <tr>
                                    <td><strong>#<?php echo esc_html($service->id); ?></strong></td>
                                    <td>
                                        <strong style="color: #0f172a; font-size: 13px;"><?php echo esc_html($user_name); ?></strong>
                                        <div style="font-size: 11px; color: #94a3b8;">User ID: <?php echo esc_html($service->user_id); ?></div>
                                    </td>
                                    <td>
                                        <?php if ($service->product_type === 'cloud_server'): ?>
                                            <span style="background: #eff6ff; color: #1d4ed8; padding: 4px 10px; border-radius: 8px; font-weight: 800; font-size: 11px; display: inline-block;">☁️ سرور ابری</span>
                                        <?php elseif ($service->product_type === 'cdn'): ?>
                                            <span style="background: #fef3c7; color: #b45309; padding: 4px 10px; border-radius: 8px; font-weight: 800; font-size: 11px; display: inline-block;">🌐 CDN</span>
                                        <?php elseif ($service->product_type === 'object_storage'): ?>
                                            <span style="background: #fdf2f8; color: #FF0066; padding: 4px 10px; border-radius: 8px; font-weight: 800; font-size: 11px; display: inline-block;">🗄️ فضای ابری</span>
                                        <?php else: ?>
                                            <?php echo esc_html($service->product_type); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 12px; color: #475569; line-height: 1.6;">
                                            <?php if (!empty($config['server_name'])): ?>
                                                نام سرور: <strong style="color: #0f172a;"><?php echo esc_html($config['server_name']); ?></strong> |
                                            <?php endif; ?>
                                            <?php if (!empty($config['assigned_ip'])): ?>
                                                IP عمومی: <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 6px; font-weight: 900; color: #0f172a; font-family: monospace;"><?php echo esc_html($config['assigned_ip']); ?></code> |
                                            <?php endif; ?>
                                            <?php if (!empty($config['domain'])): ?>
                                                دامنه: <strong style="color: #0f172a;"><?php echo esc_html($config['domain']); ?></strong> |
                                            <?php endif; ?>
                                            <?php if (!empty($config['bucket_name'])): ?>
                                                باکت: <strong style="color: #0f172a;"><?php echo esc_html($config['bucket_name']); ?></strong> |
                                            <?php endif; ?>
                                            <span style="font-size: 10px; color: #94a3b8;">(<?php echo esc_html($service->arvan_resource_id); ?>)</span>
                                        </div>
                                    </td>
                                    <td>
                                        <strong style="color: #FF0066; font-size: 13px;"><?php echo number_format($service->hourly_price); ?></strong> 
                                        <span style="font-size: 11px; color: #64748b;">تومان/ساعت</span>
                                    </td>
                                    <td>
                                        <?php if ($service->status === 'active'): ?>
                                            <span style="background: #ecfdf5; color: #065f46; padding: 4px 10px; border-radius: 9999px; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                                                <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></span> فعال و روشن
                                            </span>
                                        <?php elseif ($service->status === 'suspended'): ?>
                                            <span style="background: #fffbeb; color: #92400e; padding: 4px 10px; border-radius: 9999px; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                                                <span style="width: 6px; height: 6px; border-radius: 50%; background: #f59e0b;"></span> معلق (خاموش)
                                            </span>
                                        <?php elseif ($service->status === 'terminated'): ?>
                                            <span style="background: #fef2f2; color: #991b1b; padding: 4px 10px; border-radius: 9999px; font-weight: 800; font-size: 11px; display: inline-block;">
                                                حذف شده
                                            </span>
                                        <?php else: ?>
                                            <?php echo esc_html($service->status); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <!-- Action Form with Nonce -->
                                        <form method="post" style="display: flex; gap: 4px; align-items: center; margin: 0;">
                                            <?php wp_nonce_field('arvan_admin_service_action'); ?>
                                            <input type="hidden" name="service_id" value="<?php echo esc_attr($service->id); ?>">

                                            <?php if ($service->status === 'active'): ?>
                                                <button type="submit" name="arvan_admin_action" value="stop" class="button button-small" style="background: #fffbeb; color: #92400e; border-color: #fde68a; font-weight: 800; border-radius: 6px;" title="خاموش کردن سرویس">
                                                    ⚡ خاموش
                                                </button>
                                                <button type="submit" name="arvan_admin_action" value="restart" class="button button-small" style="background: #f8fafc; color: #334155; border-color: #e2e8f0; font-weight: 800; border-radius: 6px;" title="راه‌اندازی مجدد سرور">
                                                    🔄 ری‌استارت
                                                </button>
                                            <?php elseif ($service->status === 'suspended'): ?>
                                                <button type="submit" name="arvan_admin_action" value="start" class="button button-small button-primary" style="background: #10b981; border-color: #059669; font-weight: 800; border-radius: 6px;" title="روشن کردن سرویس">
                                                    ⚡ روشن کردن
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($service->status !== 'terminated'): ?>
                                                <button type="submit" name="arvan_admin_action" value="terminate" class="button button-small" style="background: #fef2f2; color: #b91c1c; border-color: #fecaca; font-weight: 800; border-radius: 6px;" onclick="return confirm('آیا از حذف کامل این منبع ابری (Terminate) اطمینان دارید؟');" title="حذف دائمی منبع">
                                                    🗑️ حذف
                                                </button>
                                            <?php else: ?>
                                                <span style="font-size: 11px; color: #cbd5e1;">فاقد عملیات</span>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sorkhab Financial Ledger & Transactions Table -->
        <div style="background: #ffffff; border-radius: 20px; border: 1px solid #f1f5f9; overflow: hidden; box-shadow: 0 4px 20px -3px rgba(0,0,0,0.05);">
            <div style="padding: 18px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="font-weight: 900; font-size: 15px; color: #0f172a; margin: 0;">دفتر کل مالی و تاریخچه پرداخت‌های کاربران (Transactions Ledger)</h3>
                    <p style="font-size: 12px; color: #94a3b8; margin: 2px 0 0 0;">ثبت دقیق شارژهای کیف پول و کسر هزینه‌های ساعتی مصرف</p>
                </div>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="wp-list-table widefat fixed striped table-view-list" style="border: none; margin: 0;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th style="font-weight: 900; width: 60px; color: #475569;">شناسه</th>
                            <th style="font-weight: 900; width: 140px; color: #475569;">کاربر</th>
                            <th style="font-weight: 900; width: 130px; color: #475569;">مبلغ تراکنش</th>
                            <th style="font-weight: 900; width: 110px; color: #475569;">نوع عملیات</th>
                            <th style="font-weight: 900; color: #475569;">شرح تراکنش</th>
                            <th style="font-weight: 900; width: 100px; color: #475569;">وضعیت</th>
                            <th style="font-weight: 900; width: 160px; color: #475569;">تاریخ و زمان</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr><td colspan="7" style="text-align: center; padding: 30px; color: #94a3b8; font-size: 13px;">هنوز تراکنشی در سیستم ثبت نشده است.</td></tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): 
                                $u_info = get_userdata($tx->user_id);
                                $u_name = $u_info ? $u_info->user_login : 'Guest';
                            ?>
                                <tr>
                                    <td><strong>#<?php echo esc_html($tx->id); ?></strong></td>
                                    <td><?php echo esc_html($u_name); ?> <span style="font-size: 11px; color: #94a3b8;">(ID: <?php echo esc_html($tx->user_id); ?>)</span></td>
                                    <td>
                                        <?php if ($tx->amount >= 0): ?>
                                            <strong style="color: #10b981; font-size: 13px;">+<?php echo number_format($tx->amount); ?></strong>
                                        <?php else: ?>
                                            <strong style="color: #e11d48; font-size: 13px;"><?php echo number_format($tx->amount); ?></strong>
                                        <?php endif; ?>
                                        <span style="font-size: 11px; color: #64748b;">تومان</span>
                                    </td>
                                    <td>
                                        <?php if ($tx->type === 'charge'): ?>
                                            <span style="background: #ecfdf5; color: #065f46; padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 11px;">💳 شارژ حساب</span>
                                        <?php elseif ($tx->type === 'usage_deduction'): ?>
                                            <span style="background: #fef2f2; color: #991b1b; padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 11px;">⚡ کسر مصرف</span>
                                        <?php else: ?>
                                            <?php echo esc_html($tx->type); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: #334155; font-size: 12px;"><?php echo esc_html($tx->description); ?></td>
                                    <td>
                                        <span style="background: #ecfdf5; color: #065f46; padding: 2px 8px; border-radius: 9999px; font-weight: 800; font-size: 10px;">
                                            موفق
                                        </span>
                                    </td>
                                    <td style="font-size: 11px; color: #64748b;"><?php echo esc_html($tx->created_at); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}




