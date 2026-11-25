<?php
/**
 * /account/ — visitor account page (sign in / sign up / reset, profile, friends, security).
 * The UI is rendered by public/js/account.js into [data-acct-page]; this shell keeps a semantic, no-JS fallback.
 *
 * @package KMPR
 */

get_header();
?>
<section class="ka-page" aria-labelledby="ka-page-title">
    <div class="ka-page__in">
        <h1 id="ka-page-title" class="ka-page__h"><?= is_user_logged_in() ? 'حساب کاربری من' : 'ورود یا ساخت حساب'; ?></h1>
        <p class="ka-page__lead">با یک حساب، امتیازها، پیشرفت بازی‌ها، درس‌های خوانده‌شده و اشتراکت روی همه دستگاه‌ها یکی می‌شود.</p>
        <div data-acct-page>
            <noscript><p class="ka-note">برای ورود، جاوااسکریپت مرورگر را روشن کن.</p></noscript>
        </div>
    </div>
</section>
<?php
get_footer();
