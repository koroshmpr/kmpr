<?php
/**
 * Game Center footer (replaces the site footer on game pages, see footer.php).
 */
$games_url = get_post_type_archive_link('game') ?: home_url('/games/');
?>
    <div class="gfoot relative border-t border-white/10 bg-slate-950 <?= is_singular('game') ? 'pb-8' : 'pb-28 lg:pb-8'; ?> pt-8">
        <div class="container mx-auto px-4 sm:px-6 flex flex-col lg:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3 text-center lg:text-start">
                <span class="gh__logo" aria-hidden="true"><svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M7 7h10a5 5 0 0 1 4.9 6l-.8 4a2.6 2.6 0 0 1-4.4 1.3L14.8 16H9.2l-1.9 2.3A2.6 2.6 0 0 1 2.9 17l-.8-4A5 5 0 0 1 7 7Zm0 3v1.25H5.75v1.5H7V14h1.5v-1.25h1.25v-1.5H8.5V10H7Zm9.25.25a1 1 0 1 0 0 2 1 1 0 0 0 0-2Zm2 2a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z"/></svg></span>
                <p class="text-sm text-slate-300">گیم‌سنتر کوروش · همه بازی‌ها را خودم از صفر کد زده‌ام.</p>
            </div>
            <nav aria-label="پیوندهای گیم‌سنتر">
                <ul class="flex flex-wrap items-center justify-center gap-1">
                    <li><a href="<?= esc_url(home_url('/')); ?>">بازگشت به سایت</a></li>
                    <li><a href="<?= esc_url($games_url); ?>">خانه گیم‌سنتر</a></li>
                    <li><a href="<?= esc_url($games_url); ?>#lobby">لابی آنلاین و برترین‌ها</a></li>
                    <li><a href="<?= esc_url($games_url); ?>#profile">پروفایل من</a></li>
                    <li><a href="<?= esc_url(kmpr_request_url()); ?>">سفارش پروژه</a></li>
                </ul>
            </nav>
        </div>
        <p class="mt-6 text-center text-xs text-slate-400">© <?= esc_html(shamsi_date('Y', time())); ?> کوروش محمدپور · برای بازی هیچ اطلاعات شخصی ذخیره نمی‌شود.</p>
    </div>
