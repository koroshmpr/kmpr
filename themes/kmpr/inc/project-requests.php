<?php
/**
 * Project Requests (Lead Manager)
 *
 * - Stores every project request as a private `project_request` post (nothing is lost if Telegram is down).
 * - Sends a rich notification to a Telegram chat via the official Bot API (+ email fallback).
 * - Secured REST endpoint: WP REST nonce, honeypot, time-trap, per-IP rate limit, strict sanitization.
 * - Admin: list columns, status workflow, detail meta box, dashboard widget, settings page with test button.
 *
 * Secrets: define KMPR_TG_BOT_TOKEN / KMPR_TG_CHAT_ID in wp-config.php (preferred),
 * or fill them in «تنظیمات > درخواست پروژه».
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/* -------------------------------------------------------------------------
 * 1. Definitions (single source of truth, shared with the front-end form)
 * ---------------------------------------------------------------------- */

function kmpr_request_options()
{
    return array(
        'types' => array(
            'website'   => 'وب‌سایت شخصی / شرکتی',
            'ecommerce' => 'فروشگاه اینترنتی',
            'web_app'   => 'وب‌اپلیکیشن / پنل اختصاصی',
            'redesign'  => 'بازطراحی و افزایش سرعت',
            'seo'       => 'سئو تکنیکال و بهینه‌سازی',
            'consult'   => 'مشاوره و بررسی فنی',
        ),
        'features' => array(
            'design'   => 'طراحی UI/UX اختصاصی',
            'speed'    => 'سرعت بالا (Core Web Vitals)',
            'seo'      => 'سئو تکنیکال و اسکیما',
            'cms'      => 'پنل مدیریت محتوای ساده',
            'payment'  => 'درگاه پرداخت',
            'api'      => 'اتصال به API / CRM / حسابداری',
            'auth'     => 'ثبت‌نام و پنل کاربری',
            'multilang'=> 'چندزبانه',
            'ai'       => 'قابلیت‌های هوش مصنوعی',
            'support'  => 'پشتیبانی و نگهداری',
        ),
        'budgets' => array(
            'b1'      => 'کمتر از ۳۰ میلیون تومان',
            'b2'      => '۳۰ تا ۷۰ میلیون تومان',
            'b3'      => '۷۰ تا ۱۵۰ میلیون تومان',
            'b4'      => 'بیش از ۱۵۰ میلیون تومان',
            'unknown' => 'هنوز مشخص نیست',
        ),
        'timelines' => array(
            'urgent'  => 'فوری (کمتر از ۲ هفته)',
            'month'   => 'حدود یک ماه',
            'quarter' => 'یک تا سه ماه',
            'flex'    => 'انعطاف‌پذیر',
        ),
        'channels' => array(
            'telegram' => 'تلگرام',
            'whatsapp' => 'واتساپ',
            'call'     => 'تماس تلفنی',
            'email'    => 'ایمیل',
        ),
        'statuses' => array(
            'new'       => array('label' => 'جدید', 'color' => '#f59e0b'),
            'contacted' => array('label' => 'تماس گرفته شد', 'color' => '#38bdf8'),
            'proposal'  => array('label' => 'پیشنهاد ارسال شد', 'color' => '#a78bfa'),
            'won'       => array('label' => 'قرارداد بسته شد', 'color' => '#10b981'),
            'lost'      => array('label' => 'منتفی', 'color' => '#64748b'),
        ),
    );
}

/**
 * Contact URLs from ACF options with safe fallbacks (one place to change handles).
 */
function kmpr_contact_url($channel)
{
    $acf = function_exists('get_field') ? get_field($channel, 'option') : '';
    if ($acf) {
        return $acf;
    }
    $fallbacks = array(
        'telegram' => 'https://t.me/korosh_mpr',
        'whatsapp' => 'https://wa.me/989358088118',
    );
    return $fallbacks[$channel] ?? '';
}

/* -------------------------------------------------------------------------
 * 2. Post type
 * ---------------------------------------------------------------------- */

function kmpr_register_request_cpt()
{
    register_post_type('project_request', array(
        'labels' => array(
            'name'          => 'درخواست‌های پروژه',
            'singular_name' => 'درخواست پروژه',
            'menu_name'     => 'درخواست‌ها',
            'all_items'     => 'همه درخواست‌ها',
            'edit_item'     => 'جزئیات درخواست',
            'search_items'  => 'جستجوی درخواست‌ها',
            'not_found'     => 'هنوز درخواستی ثبت نشده است.',
        ),
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'menu_icon'           => 'dashicons-email-alt2',
        'menu_position'       => 4,
        'supports'            => array('title'),
        'capability_type'     => 'post',
        'capabilities'        => array('create_posts' => 'do_not_allow'),
        'map_meta_cap'        => true,
    ));
}
add_action('init', 'kmpr_register_request_cpt');

/* -------------------------------------------------------------------------
 * 3. Settings (token / chat id)
 * ---------------------------------------------------------------------- */

function kmpr_tg_token()
{
    return defined('KMPR_TG_BOT_TOKEN') ? KMPR_TG_BOT_TOKEN : (string) get_option('kmpr_tg_bot_token', '');
}

function kmpr_tg_chat_id()
{
    return defined('KMPR_TG_CHAT_ID') ? KMPR_TG_CHAT_ID : (string) get_option('kmpr_tg_chat_id', '');
}

function kmpr_requests_settings_init()
{
    register_setting('kmpr_requests', 'kmpr_tg_bot_token', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('kmpr_requests', 'kmpr_tg_chat_id', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('kmpr_requests', 'kmpr_request_email', array('sanitize_callback' => 'sanitize_email'));
}
add_action('admin_init', 'kmpr_requests_settings_init');

function kmpr_requests_settings_menu()
{
    add_submenu_page(
        'edit.php?post_type=project_request',
        'تنظیمات اعلان تلگرام',
        'تنظیمات تلگرام',
        'manage_options',
        'kmpr-requests-settings',
        'kmpr_requests_settings_page'
    );
}
add_action('admin_menu', 'kmpr_requests_settings_menu');

function kmpr_requests_settings_page()
{
    $token_locked = defined('KMPR_TG_BOT_TOKEN');
    $chat_locked  = defined('KMPR_TG_CHAT_ID');
    $test         = isset($_GET['kmpr_test']) ? sanitize_key($_GET['kmpr_test']) : '';
    ?>
    <div class="wrap">
        <h1>اعلان درخواست‌های پروژه در تلگرام</h1>
        <?php if ($test === 'ok') : ?>
            <div class="notice notice-success"><p>پیام آزمایشی با موفقیت به تلگرام ارسال شد.</p></div>
        <?php elseif ($test) : ?>
            <div class="notice notice-error"><p>ارسال ناموفق بود. توکن، Chat ID و اینکه ربات را Start کرده‌اید (یا در گروه/کانال ادمین است) را بررسی کنید.</p></div>
        <?php endif; ?>

        <ol style="line-height:2">
            <li>در تلگرام به <code>@BotFather</code> پیام دهید، <code>/newbot</code> را بزنید و توکن را کپی کنید.</li>
            <li>ربات را Start کنید (یا به گروه/کانال خود به‌عنوان ادمین اضافه کنید).</li>
            <li>Chat ID خود را از <code>@userinfobot</code> بگیرید (برای گروه/کانال با <code>-100</code> شروع می‌شود).</li>
            <li>برای امنیت بیشتر می‌توانید در <code>wp-config.php</code> مقادیر <code>KMPR_TG_BOT_TOKEN</code> و <code>KMPR_TG_CHAT_ID</code> را تعریف کنید.</li>
        </ol>

        <form method="post" action="options.php">
            <?php settings_fields('kmpr_requests'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="kmpr_tg_bot_token">توکن ربات</label></th>
                    <td>
                        <?php if ($token_locked) : ?>
                            <code>در wp-config.php تعریف شده است</code>
                        <?php else : ?>
                            <input type="password" autocomplete="off" class="regular-text ltr" id="kmpr_tg_bot_token" name="kmpr_tg_bot_token" value="<?= esc_attr(get_option('kmpr_tg_bot_token')); ?>">
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="kmpr_tg_chat_id">Chat ID</label></th>
                    <td>
                        <?php if ($chat_locked) : ?>
                            <code>در wp-config.php تعریف شده است</code>
                        <?php else : ?>
                            <input type="text" class="regular-text ltr" id="kmpr_tg_chat_id" name="kmpr_tg_chat_id" value="<?= esc_attr(get_option('kmpr_tg_chat_id')); ?>">
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="kmpr_request_email">ایمیل پشتیبان</label></th>
                    <td>
                        <input type="email" class="regular-text ltr" id="kmpr_request_email" name="kmpr_request_email" value="<?= esc_attr(get_option('kmpr_request_email', get_option('admin_email'))); ?>">
                        <p class="description">اگر تلگرام در دسترس نبود، درخواست به این ایمیل هم ارسال می‌شود.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('ذخیره تنظیمات'); ?>
        </form>

        <form method="post" action="<?= esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="kmpr_tg_test">
            <?php wp_nonce_field('kmpr_tg_test'); ?>
            <?php submit_button('ارسال پیام آزمایشی', 'secondary', 'submit', false); ?>
        </form>
    </div>
    <?php
}

function kmpr_handle_tg_test()
{
    if (!current_user_can('manage_options') || !check_admin_referer('kmpr_tg_test')) {
        wp_die('دسترسی غیرمجاز');
    }
    $ok = kmpr_tg_send("✅ <b>اتصال برقرار است</b>\nاعلان درخواست‌های پروژه " . esc_html(wp_parse_url(home_url(), PHP_URL_HOST)) . ' فعال شد.');
    wp_safe_redirect(add_query_arg(
        array('post_type' => 'project_request', 'page' => 'kmpr-requests-settings', 'kmpr_test' => $ok ? 'ok' : 'fail'),
        admin_url('edit.php')
    ));
    exit;
}
add_action('admin_post_kmpr_tg_test', 'kmpr_handle_tg_test');

/* -------------------------------------------------------------------------
 * 4. Telegram transport
 * ---------------------------------------------------------------------- */

function kmpr_tg_send($html, $buttons = array())
{
    $token   = kmpr_tg_token();
    $chat_id = kmpr_tg_chat_id();
    if (!$token || !$chat_id) {
        return false;
    }

    $body = array(
        'chat_id'                  => $chat_id,
        'text'                     => $html,
        'parse_mode'               => 'HTML',
        'disable_web_page_preview' => true,
    );
    if ($buttons) {
        $body['reply_markup'] = wp_json_encode(array('inline_keyboard' => $buttons));
    }

    $response = wp_remote_post('https://api.telegram.org/bot' . $token . '/sendMessage', array(
        'timeout' => 8,
        'body'    => $body,
    ));

    if (is_wp_error($response)) {
        error_log('[kmpr] Telegram error: ' . $response->get_error_message());
        return false;
    }
    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        error_log('[kmpr] Telegram HTTP ' . $code . ': ' . wp_remote_retrieve_body($response));
        return false;
    }
    return true;
}

/* -------------------------------------------------------------------------
 * 5. REST endpoint
 * ---------------------------------------------------------------------- */

add_action('rest_api_init', function () {
    register_rest_route('kmpr/v1', '/project-request', array(
        'methods'             => 'POST',
        'callback'            => 'kmpr_handle_project_request',
        'permission_callback' => '__return_true',
    ));
});

/**
 * Convert Persian/Arabic digits to Latin.
 */
function kmpr_latin_digits($value)
{
    return strtr((string) $value, array(
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ));
}

function kmpr_request_error($message, $status = 400, $field = '')
{
    return new WP_REST_Response(array('ok' => false, 'message' => $message, 'field' => $field), $status);
}

function kmpr_handle_project_request(WP_REST_Request $request)
{
    $p    = $request->get_json_params() ?: $request->get_body_params();
    $opts = kmpr_request_options();

    // --- Anti-spam -------------------------------------------------------
    if (!empty($p['website_url_confirm'])) { // honeypot: silently accept, do nothing
        return new WP_REST_Response(array('ok' => true, 'code' => 'KM-' . strtoupper(wp_generate_password(6, false))), 200);
    }
    $started = isset($p['started_at']) ? (int) $p['started_at'] : 0;
    if ($started && (time() * 1000 - $started) < 4000) {
        return kmpr_request_error('ارسال بیش از حد سریع بود. لطفاً دوباره تلاش کنید.', 429);
    }
    $ip_key = 'kmpr_rq_' . md5(($_SERVER['REMOTE_ADDR'] ?? '') . wp_salt('nonce'));
    $hits   = (int) get_transient($ip_key);
    if ($hits >= 4) {
        return kmpr_request_error('تعداد درخواست‌های شما زیاد است. لطفاً چند دقیقه دیگر تلاش کنید یا مستقیم پیام دهید.', 429);
    }

    // --- Sanitize & validate --------------------------------------------
    $type      = sanitize_key($p['type'] ?? '');
    $features  = array_values(array_intersect(array_map('sanitize_key', (array) ($p['features'] ?? array())), array_keys($opts['features'])));
    $budget    = sanitize_key($p['budget'] ?? '');
    $timeline  = sanitize_key($p['timeline'] ?? '');
    $channel   = sanitize_key($p['channel'] ?? '');
    $name      = sanitize_text_field(mb_substr($p['name'] ?? '', 0, 80));
    $phone     = preg_replace('/\D+/', '', kmpr_latin_digits($p['phone'] ?? ''));
    $email     = sanitize_email($p['email'] ?? '');
    $telegram  = ltrim(sanitize_text_field(mb_substr($p['telegram'] ?? '', 0, 40)), '@');
    $business  = sanitize_text_field(mb_substr($p['business'] ?? '', 0, 120));
    $reference = esc_url_raw(mb_substr($p['reference'] ?? '', 0, 300));
    $message   = sanitize_textarea_field(mb_substr($p['message'] ?? '', 0, 2000));
    $source    = esc_url_raw(mb_substr($p['source'] ?? '', 0, 300));
    $service   = sanitize_text_field(mb_substr($p['service'] ?? '', 0, 120));

    if (!isset($opts['types'][$type])) {
        return kmpr_request_error('نوع پروژه را انتخاب کنید.', 422, 'type');
    }
    if (mb_strlen($name) < 2) {
        return kmpr_request_error('نام خود را وارد کنید.', 422, 'name');
    }
    if (strpos($phone, '98') === 0 && strlen($phone) === 12) {
        $phone = '0' . substr($phone, 2);
    }
    if (!preg_match('/^09\d{9}$/', $phone)) {
        return kmpr_request_error('شماره موبایل معتبر نیست (مثال: ۰۹۱۲۱۲۳۴۵۶۷).', 422, 'phone');
    }
    if (!empty($p['email']) && !is_email($email)) {
        return kmpr_request_error('ایمیل معتبر نیست.', 422, 'email');
    }
    if (!isset($opts['budgets'][$budget])) {
        $budget = 'unknown';
    }
    if (!isset($opts['timelines'][$timeline])) {
        $timeline = 'flex';
    }
    if (!isset($opts['channels'][$channel])) {
        $channel = 'call';
    }

    set_transient($ip_key, $hits + 1, 10 * MINUTE_IN_SECONDS);

    // --- Store -----------------------------------------------------------
    $code    = 'KM-' . wp_date('ymd') . '-' . strtoupper(wp_generate_password(4, false));
    $post_id = wp_insert_post(array(
        'post_type'   => 'project_request',
        'post_status' => 'publish',
        'post_title'  => $name . ' — ' . $opts['types'][$type],
    ), true);

    if (is_wp_error($post_id)) {
        return kmpr_request_error('خطای داخلی در ثبت درخواست. لطفاً مستقیم در تلگرام پیام دهید.', 500);
    }

    $meta = compact('code', 'type', 'features', 'budget', 'timeline', 'channel', 'name', 'phone', 'email', 'telegram', 'business', 'reference', 'message', 'source', 'service');
    $meta['status'] = 'new';
    foreach ($meta as $key => $value) {
        update_post_meta($post_id, '_kmpr_' . $key, $value);
    }

    // --- Notify ----------------------------------------------------------
    $sent = kmpr_notify_request($post_id, $meta);
    update_post_meta($post_id, '_kmpr_tg_sent', $sent ? 1 : 0);

    return new WP_REST_Response(array(
        'ok'      => true,
        'code'    => $code,
        'message' => 'درخواست شما ثبت شد.',
    ), 201);
}

function kmpr_notify_request($post_id, $m)
{
    $o   = kmpr_request_options();
    $e   = function ($v) {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    };
    $feat = array_map(function ($f) use ($o) {
        return $o['features'][$f] ?? $f;
    }, (array) $m['features']);

    $lines   = array();
    $lines[] = '🚀 <b>درخواست پروژه جدید</b>  <code>' . $e($m['code']) . '</code>';
    $lines[] = '';
    $lines[] = '👤 <b>' . $e($m['name']) . '</b>' . ($m['business'] ? ' — ' . $e($m['business']) : '');
    $lines[] = '📱 <code>' . $e($m['phone']) . '</code>';
    if ($m['email']) {
        $lines[] = '✉️ ' . $e($m['email']);
    }
    if ($m['telegram']) {
        $lines[] = '💬 @' . $e($m['telegram']);
    }
    $lines[] = '📞 ترجیح ارتباط: <b>' . $e($o['channels'][$m['channel']]) . '</b>';
    $lines[] = '';
    $lines[] = '🧩 نوع پروژه: <b>' . $e($o['types'][$m['type']]) . '</b>';
    if ($m['service']) {
        $lines[] = '🛠 از صفحه خدمت: ' . $e($m['service']);
    }
    if ($feat) {
        $lines[] = '⚙️ امکانات: ' . $e(implode('، ', $feat));
    }
    $lines[] = '💰 بودجه: ' . $e($o['budgets'][$m['budget']]);
    $lines[] = '⏱ زمان‌بندی: ' . $e($o['timelines'][$m['timeline']]);
    if ($m['reference']) {
        $lines[] = '🔗 نمونه/سایت فعلی: ' . $e($m['reference']);
    }
    if ($m['message']) {
        $lines[] = '';
        $lines[] = '📝 <b>توضیحات:</b>';
        $lines[] = $e(mb_substr($m['message'], 0, 1500));
    }
    if ($m['source']) {
        $lines[] = '';
        $lines[] = '📍 ثبت از: ' . $e($m['source']);
    }

    $intl    = '98' . substr($m['phone'], 1);
    $buttons = array(array(
        array('text' => '💬 واتساپ', 'url' => 'https://wa.me/' . $intl),
        array('text' => '🗂 مشاهده در پنل', 'url' => admin_url('post.php?post=' . (int) $post_id . '&action=edit')),
    ));
    if ($m['telegram'] && preg_match('/^[A-Za-z0-9_]{5,32}$/', $m['telegram'])) {
        array_unshift($buttons[0], array('text' => '✈️ تلگرام', 'url' => 'https://t.me/' . $m['telegram']));
    }

    $sent = kmpr_tg_send(implode("\n", $lines), $buttons);

    // Email fallback / archive copy.
    $to = get_option('kmpr_request_email', get_option('admin_email'));
    if ($to) {
        wp_mail(
            $to,
            ($sent ? '' : '[تلگرام ناموفق] ') . 'درخواست پروژه جدید ' . $m['code'] . ' — ' . $m['name'],
            wp_strip_all_tags(implode("\n", $lines)) . "\n\n" . admin_url('post.php?post=' . (int) $post_id . '&action=edit')
        );
    }

    return $sent;
}

/* -------------------------------------------------------------------------
 * 6. Admin UI
 * ---------------------------------------------------------------------- */

add_filter('manage_project_request_posts_columns', function () {
    return array(
        'cb'          => '<input type="checkbox" />',
        'title'       => 'مشتری / پروژه',
        'kmpr_code'   => 'کد پیگیری',
        'kmpr_phone'  => 'موبایل',
        'kmpr_budget' => 'بودجه',
        'kmpr_status' => 'وضعیت',
        'date'        => 'تاریخ',
    );
});

add_action('manage_project_request_posts_custom_column', function ($column, $post_id) {
    $o = kmpr_request_options();
    switch ($column) {
        case 'kmpr_code':
            echo '<code>' . esc_html(get_post_meta($post_id, '_kmpr_code', true)) . '</code>';
            if (!get_post_meta($post_id, '_kmpr_tg_sent', true)) {
                echo '<br><small style="color:#b91c1c">تلگرام ارسال نشد</small>';
            }
            break;
        case 'kmpr_phone':
            $phone = get_post_meta($post_id, '_kmpr_phone', true);
            echo '<a class="ltr" href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a>';
            break;
        case 'kmpr_budget':
            $b = get_post_meta($post_id, '_kmpr_budget', true);
            echo esc_html($o['budgets'][$b] ?? '—');
            break;
        case 'kmpr_status':
            $s  = get_post_meta($post_id, '_kmpr_status', true) ?: 'new';
            $st = $o['statuses'][$s] ?? $o['statuses']['new'];
            printf('<span style="display:inline-block;padding:2px 10px;border-radius:999px;background:%1$s22;color:%1$s;font-weight:700">%2$s</span>', esc_attr($st['color']), esc_html($st['label']));
            break;
    }
}, 10, 2);

add_action('add_meta_boxes_project_request', function () {
    add_meta_box('kmpr_request_details', 'جزئیات درخواست', 'kmpr_request_details_box', 'project_request', 'normal', 'high');
    add_meta_box('kmpr_request_status', 'وضعیت پیگیری', 'kmpr_request_status_box', 'project_request', 'side', 'high');
});

function kmpr_request_details_box($post)
{
    $o   = kmpr_request_options();
    $get = function ($k) use ($post) {
        return get_post_meta($post->ID, '_kmpr_' . $k, true);
    };
    $features = array_map(function ($f) use ($o) {
        return $o['features'][$f] ?? $f;
    }, (array) $get('features'));
    $rows = array(
        'کد پیگیری'        => '<code>' . esc_html($get('code')) . '</code>',
        'نام'              => esc_html($get('name')),
        'کسب‌وکار'          => esc_html($get('business')),
        'موبایل'           => '<a href="tel:' . esc_attr($get('phone')) . '">' . esc_html($get('phone')) . '</a> · <a target="_blank" rel="noopener" href="https://wa.me/98' . esc_attr(substr($get('phone'), 1)) . '">واتساپ</a>',
        'ایمیل'            => $get('email') ? '<a href="mailto:' . esc_attr($get('email')) . '">' . esc_html($get('email')) . '</a>' : '—',
        'تلگرام'           => $get('telegram') ? '<a target="_blank" rel="noopener" href="https://t.me/' . esc_attr($get('telegram')) . '">@' . esc_html($get('telegram')) . '</a>' : '—',
        'ترجیح ارتباط'     => esc_html($o['channels'][$get('channel')] ?? '—'),
        'نوع پروژه'        => esc_html($o['types'][$get('type')] ?? '—'),
        'صفحه خدمت'        => esc_html($get('service') ?: '—'),
        'امکانات'          => esc_html($features ? implode('، ', $features) : '—'),
        'بودجه'            => esc_html($o['budgets'][$get('budget')] ?? '—'),
        'زمان‌بندی'         => esc_html($o['timelines'][$get('timeline')] ?? '—'),
        'سایت مرجع / فعلی' => $get('reference') ? '<a target="_blank" rel="noopener" href="' . esc_url($get('reference')) . '">' . esc_html($get('reference')) . '</a>' : '—',
        'توضیحات'          => nl2br(esc_html($get('message') ?: '—')),
        'ثبت از صفحه'      => $get('source') ? '<a target="_blank" href="' . esc_url($get('source')) . '">' . esc_html(urldecode($get('source'))) . '</a>' : '—',
    );
    echo '<table class="widefat striped"><tbody>';
    foreach ($rows as $label => $value) {
        echo '<tr><th style="width:160px">' . esc_html($label) . '</th><td>' . $value . '</td></tr>'; // values escaped above
    }
    echo '</tbody></table>';
}

function kmpr_request_status_box($post)
{
    $o       = kmpr_request_options();
    $current = get_post_meta($post->ID, '_kmpr_status', true) ?: 'new';
    wp_nonce_field('kmpr_request_status', 'kmpr_request_status_nonce');
    echo '<select name="kmpr_status" style="width:100%">';
    foreach ($o['statuses'] as $key => $st) {
        printf('<option value="%s"%s>%s</option>', esc_attr($key), selected($current, $key, false), esc_html($st['label']));
    }
    echo '</select>';
    echo '<p><label for="kmpr_note">یادداشت داخلی</label><textarea id="kmpr_note" name="kmpr_note" rows="5" style="width:100%">' . esc_textarea(get_post_meta($post->ID, '_kmpr_note', true)) . '</textarea></p>';
}

add_action('save_post_project_request', function ($post_id) {
    if (!isset($_POST['kmpr_request_status_nonce']) || !wp_verify_nonce($_POST['kmpr_request_status_nonce'], 'kmpr_request_status')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || !current_user_can('edit_post', $post_id)) {
        return;
    }
    $status = sanitize_key($_POST['kmpr_status'] ?? 'new');
    if (isset(kmpr_request_options()['statuses'][$status])) {
        update_post_meta($post_id, '_kmpr_status', $status);
    }
    update_post_meta($post_id, '_kmpr_note', sanitize_textarea_field($_POST['kmpr_note'] ?? ''));
});

// Badge with the number of new requests in the admin menu.
add_action('admin_menu', function () {
    global $menu;
    $count = (int) (new WP_Query(array(
        'post_type'      => 'project_request',
        'post_status'    => 'publish',
        'meta_key'       => '_kmpr_status',
        'meta_value'     => 'new',
        'fields'         => 'ids',
        'posts_per_page' => 99,
        'no_found_rows'  => true,
    )))->post_count;
    if (!$count || !is_array($menu)) {
        return;
    }
    foreach ($menu as $i => $item) {
        if (($item[2] ?? '') === 'edit.php?post_type=project_request') {
            $menu[$i][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . $count . '</span></span>';
            break;
        }
    }
}, 99);

// Dashboard widget.
add_action('wp_dashboard_setup', function () {
    wp_add_dashboard_widget('kmpr_requests_widget', 'آخرین درخواست‌های پروژه', function () {
        $o = kmpr_request_options();
        $q = new WP_Query(array('post_type' => 'project_request', 'posts_per_page' => 6, 'no_found_rows' => true));
        if (!$q->have_posts()) {
            echo '<p>هنوز درخواستی ثبت نشده است.</p>';
        }
        if (!kmpr_tg_token() || !kmpr_tg_chat_id()) {
            echo '<p style="color:#b45309">اعلان تلگرام هنوز تنظیم نشده است. <a href="' . esc_url(admin_url('edit.php?post_type=project_request&page=kmpr-requests-settings')) . '">تنظیم کنید</a></p>';
        }
        echo '<ul>';
        while ($q->have_posts()) {
            $q->the_post();
            $s = get_post_meta(get_the_ID(), '_kmpr_status', true) ?: 'new';
            printf(
                '<li><a href="%s">%s</a> <small style="color:%s">● %s</small> <small>%s</small></li>',
                esc_url(get_edit_post_link()),
                esc_html(get_the_title()),
                esc_attr($o['statuses'][$s]['color']),
                esc_html($o['statuses'][$s]['label']),
                esc_html(human_time_diff(get_the_time('U')) . ' پیش')
            );
        }
        wp_reset_postdata();
        echo '</ul>';
    });
});
