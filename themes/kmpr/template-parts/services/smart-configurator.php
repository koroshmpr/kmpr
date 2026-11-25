<?php
/**
 * Project Request Wizard
 * 5-step brief builder (phone: each step is one app-like screen inside the visible viewport) → stored in WP admin + instant Telegram notification (inc/project-requests.php).
 *
 * Optional $args: 'service' (string) service title for context, 'type' (string) preset project type.
 */
$kmpr_opts   = kmpr_request_options();
$kmpr_steps = array(1 => 'نوع پروژه', 2 => 'امکانات', 3 => 'بودجه و زمان', 4 => 'جزئیات', 5 => 'اطلاعات تماس');
$kmpr_config = array(
    'endpoint'    => esc_url_raw(rest_url('kmpr/v1/project-request')),
    'options'     => array_diff_key($kmpr_opts, array('statuses' => 1)),
    'service'     => $args['service'] ?? (is_singular('services') ? get_the_title() : ''),
    'defaultType' => $args['type'] ?? '',
    'telegram'    => kmpr_contact_url('telegram'),
    'steps'       => array_values($kmpr_steps),
);
$kmpr_type_icons = array(
    'website'   => '<path d="M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M3 9h18"/><path d="M7 6h.01M10 6h.01"/>',
    'ecommerce' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
    'web_app'   => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="m9 8 3 3-3 3"/><path d="M14 14h2"/>',
    'redesign'  => '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>',
    'seo'       => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/><path d="M8 13l2-2 2 2 3-3"/>',
    'consult'   => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8M8 13h5"/>',
);
$kmpr_type_desc = array(
    'website'   => 'معرفی برند یا کسب‌وکار با طراحی اختصاصی و سئوی اصولی',
    'ecommerce' => 'فروشگاه سریع با درگاه پرداخت و مدیریت ساده سفارش‌ها',
    'web_app'   => 'سامانه، داشبورد یا پنل اختصاصی با API و احراز هویت',
    'redesign'  => 'بازطراحی سایت فعلی و رساندن سرعت به استاندارد گوگل',
    'seo'       => 'رفع مشکلات فنی، اسکیما، ایندکس و Core Web Vitals',
    'consult'   => 'بررسی فنی پروژه، انتخاب معماری یا جلسه مشاوره',
);
?>
<section id="smart-configurator" aria-labelledby="request-title"
         class="rq-section py-10 sm:py-12 lg:py-8 lg:min-h-[calc(100svh-5rem)] lg:flex lg:flex-col lg:justify-center bg-slate-950 text-white relative overflow-x-clip border-y border-white/10 scroll-mt-20"
         x-data='projectRequest(<?= wp_json_encode($kmpr_config, JSON_HEX_APOS | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE); ?>)' x-init="$el.classList.add('rq-live')">
    <div class="absolute top-1/4 start-1/4 w-96 h-96 bg-secondary/10 rounded-full blur-[140px] pointer-events-none" aria-hidden="true"></div>
    <div class="absolute bottom-10 end-1/4 w-96 h-96 bg-blue-600/10 rounded-full blur-[140px] pointer-events-none" aria-hidden="true"></div>

    <div class="container mx-auto px-3 sm:px-6 relative z-10 max-w-6xl w-full">
        <header class="text-center max-w-3xl mx-auto mb-5 sm:mb-7 lg:mb-6 px-1">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-secondary/15 border border-secondary/30 mb-3">
                <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                <span class="text-xs font-black text-secondary">ثبت درخواست پروژه · کمتر از ۲ دقیقه</span>
            </div>
            <h2 id="request-title" class="text-2xl sm:text-3xl lg:text-4xl font-black text-white leading-tight">
                بریف پروژه‌تان را بسازید
            </h2>
            <p class="text-slate-300 text-sm sm:text-base leading-relaxed mt-3">
                چند انتخاب ساده انجام دهید؛ درخواست شما مستقیم به خود من می‌رسد و معمولاً ظرف چند ساعت کاری برای هماهنگی جلسه تماس می‌گیرم.
            </p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_400px] xl:grid-cols-[minmax(0,1fr)_440px] gap-6 items-stretch">

            <!-- ============ Wizard Card (phone: one app screen per step, sized to the visible viewport) ============ -->
            <div x-ref="card" class="rq-card flex flex-col bg-slate-900/80 border border-white/10 rounded-[1.75rem] sm:rounded-3xl shadow-2xl backdrop-blur-xl overflow-hidden" :class="kb && 'rq-kb'">

                <!-- Fallback when the wizard script cannot run: direct contact options (shown by CSS / inline timer). -->
                <div class="rq-fallback flex-1 flex-col items-center justify-center gap-4 p-6 text-center" role="note">
                    <p class="text-base font-black text-white">فرم هوشمند در مرورگر شما بارگذاری نشد</p>
                    <p class="text-sm text-slate-300 leading-relaxed max-w-sm">مشکلی نیست؛ همین حالا مستقیم پیام بدهید تا درباره پروژه صحبت کنیم.</p>
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <a href="<?= esc_url(kmpr_contact_url('telegram')); ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center px-6 py-3 min-h-12 rounded-full bg-sky-600 hover:bg-sky-500 text-white text-sm font-black whitespace-nowrap">پیام در تلگرام</a>
                        <a href="tel:<?= esc_attr(kmpr_contact('phone')); ?>" class="inline-flex items-center justify-center px-6 py-3 min-h-12 rounded-full bg-white/[0.06] border border-white/15 text-slate-100 text-sm font-bold whitespace-nowrap">تماس تلفنی</a>
                    </div>
                </div>

                <!-- Progress -->
                <div x-show="status !== 'done'" class="rq-head shrink-0 px-4 sm:px-7 pt-4 sm:pt-6">
                    <div class="flex items-center justify-between gap-3 mb-3 lg:hidden">
                        <p class="text-xs font-bold text-slate-300 whitespace-nowrap">
                            گام <span class="text-white font-black" x-text="step.toLocaleString('fa-IR')">۱</span> از <span x-text="totalSteps.toLocaleString('fa-IR')">۵</span>
                            <span class="text-slate-500 mx-1" aria-hidden="true">·</span>
                            <span class="text-slate-200" x-text="stepLabels[step - 1]"></span>
                        </p>
                        <span class="text-xs font-black text-secondary tabular-nums whitespace-nowrap" x-text="completion.toLocaleString('fa-IR') + '٪ آماده'"></span>
                    </div>
                    <ol class="grid grid-cols-5 gap-1.5 sm:gap-2 text-[11px] sm:text-xs font-bold" aria-label="مراحل ثبت درخواست">
                        <?php foreach ($kmpr_steps as $n => $label) : ?>
                            <li>
                                <button type="button" @click="go(<?= $n; ?>)"
                                        class="w-full text-start cursor-pointer group py-[1.1rem] -my-[1.1rem] lg:py-0 lg:my-0"
                                        :aria-current="step === <?= $n; ?> ? 'step' : null" aria-label="<?= esc_attr('مرحله ' . kmpr_fa_num($n) . ': ' . $label); ?>">
                                    <span class="block h-1.5 rounded-full transition-colors duration-300"
                                          :class="step >= <?= $n; ?> ? 'bg-secondary' : 'bg-white/10 group-hover:bg-white/20'"></span>
                                    <span class="mt-2 hidden lg:block transition-colors whitespace-nowrap" :class="step >= <?= $n; ?> ? 'text-slate-100' : 'text-slate-400'"><?= esc_html($label); ?></span>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>

                <form class="rq-form flex-1 min-h-0 flex flex-col pt-4 sm:pt-5" @submit.prevent="step < totalSteps ? go(step + 1) : submit()" @focusin="focusField($event)" novalidate x-show="status !== 'done'">

                    <!-- Honeypot (hidden from humans) -->
                    <div class="absolute -start-[9999px] w-px h-px overflow-hidden" aria-hidden="true">
                        <label>وب‌سایت <input type="text" tabindex="-1" autocomplete="off" x-model="form.website_url_confirm"></label>
                    </div>

                    <div x-ref="viewport" class="rq-viewport flex-1 min-h-0 grid px-4 sm:px-7 overflow-x-hidden overflow-y-auto overscroll-contain" :style="`--dir:${dir}`">
                    <!-- STEP 1 -->
                    <fieldset class="rq-step pb-3" x-show="step === 1" x-transition:enter="rq-step-enter" x-transition:enter-start="rq-from" x-transition:enter-end="rq-to" x-transition:leave="rq-step-leave" x-transition:leave-start="rq-to" x-transition:leave-end="rq-out">
                        <legend class="rq-title text-lg sm:text-xl font-black text-white mb-1" tabindex="-1">چه چیزی می‌خواهید بسازیم؟</legend>
                        <p class="rq-sub text-sm text-slate-400 mb-3">نزدیک‌ترین گزینه را انتخاب کنید؛ جزئیات را بعداً با هم دقیق می‌کنیم.</p>
                        <div class="grid grid-cols-2 gap-2 sm:gap-3">
                            <?php foreach ($kmpr_opts['types'] as $id => $title) : ?>
                                <button type="button" @click="pickType('<?= esc_js($id); ?>')"
                                        :aria-pressed="form.type === '<?= esc_js($id); ?>'"
                                        :class="form.type === '<?= esc_js($id); ?>' ? 'border-secondary bg-secondary/10 ring-2 ring-secondary/30' : 'border-white/10 bg-white/[0.02] hover:border-white/25 hover:bg-white/[0.04]'"
                                        class="rq-type group text-start p-3 sm:p-4 rounded-2xl border transition-[border-color,background-color,box-shadow,transform] duration-200 cursor-pointer flex flex-col sm:flex-row gap-2.5 sm:gap-3.5 sm:items-center min-w-0 min-h-[5.5rem] active:scale-[0.98]">
                                    <span class="size-10 sm:size-11 rounded-xl bg-secondary/15 text-secondary border border-secondary/25 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                        <svg width="22" height="22" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $kmpr_type_icons[$id]; // static markup ?></svg>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[13px] sm:text-[15px] font-black text-white leading-snug"><?= esc_html($title); ?></span>
                                        <span class="rq-desc block text-xs text-slate-400 leading-5 mt-1 line-clamp-2"><?= esc_html($kmpr_type_desc[$id]); ?></span>
                                    </span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <!-- STEP 2: features -->
                    <fieldset x-show="step === 2" x-cloak x-transition:enter="rq-step-enter" x-transition:enter-start="rq-from" x-transition:enter-end="rq-to" x-transition:leave="rq-step-leave" x-transition:leave-start="rq-to" x-transition:leave-end="rq-out" class="rq-step pb-3">
                        <legend class="rq-title text-lg sm:text-xl font-black text-white mb-1" tabindex="-1">چه امکاناتی برایتان مهم است؟</legend>
                        <p class="rq-sub text-sm text-slate-400 mb-3">هر چند مورد که لازم دارید (اختیاری).</p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($kmpr_opts['features'] as $id => $title) : ?>
                                <button type="button" @click="toggleFeature('<?= esc_js($id); ?>')"
                                        :aria-pressed="form.features.includes('<?= esc_js($id); ?>')"
                                        :class="form.features.includes('<?= esc_js($id); ?>') ? 'bg-secondary text-slate-950 border-secondary' : 'bg-white/[0.03] text-slate-200 border-white/10 hover:border-white/30'"
                                        class="inline-flex items-center gap-2 px-4 py-2 min-h-11 rounded-full border text-[13px] font-bold whitespace-nowrap transition-[color,background-color,border-color,transform] cursor-pointer active:scale-95">
                                    <svg width="14" height="14" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                                         x-show="form.features.includes('<?= esc_js($id); ?>')"><path fill-rule="evenodd" d="M16.7 4.15a.75.75 0 0 1 .15 1.05l-8 10.5a.75.75 0 0 1-1.13.08l-4.5-4.5a.75.75 0 1 1 1.06-1.06l3.9 3.9 7.47-9.82a.75.75 0 0 1 1.05-.15Z" clip-rule="evenodd"/></svg>
                                    <?= esc_html($title); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <!-- STEP 3: budget + timeline -->
                    <div x-show="step === 3" x-cloak x-transition:enter="rq-step-enter" x-transition:enter-start="rq-from" x-transition:enter-end="rq-to" x-transition:leave="rq-step-leave" x-transition:leave-start="rq-to" x-transition:leave-end="rq-out" class="rq-step pb-3 grid grid-cols-1 md:grid-cols-2 gap-5 content-start">
                        <fieldset>
                            <legend class="rq-title text-base sm:text-lg font-black text-white mb-3" tabindex="-1">بودجه تقریبی</legend>
                            <div class="grid grid-cols-2 md:grid-cols-1 gap-2">
                                <?php foreach ($kmpr_opts['budgets'] as $id => $title) : ?>
                                    <label class="flex items-center gap-2.5 px-3 sm:px-4 py-2 min-h-12 rounded-xl border cursor-pointer transition-colors <?= $id === 'unknown' ? 'col-span-2 md:col-span-1' : ''; ?>"
                                           :class="form.budget === '<?= esc_js($id); ?>' ? 'border-secondary bg-secondary/10 text-white' : 'border-white/10 bg-white/[0.02] text-slate-300 hover:border-white/25'">
                                        <input type="radio" name="budget" value="<?= esc_attr($id); ?>" x-model="form.budget" class="accent-amber-500 size-4 shrink-0">
                                        <span class="text-[13px] sm:text-sm font-bold leading-snug"><?= esc_html($title); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                        <fieldset>
                            <legend class="text-base sm:text-lg font-black text-white mb-3">زمان‌بندی</legend>
                            <div class="grid grid-cols-2 md:grid-cols-1 gap-2">
                                <?php foreach ($kmpr_opts['timelines'] as $id => $title) : ?>
                                    <label class="flex items-center gap-2.5 px-3 sm:px-4 py-2 min-h-12 rounded-xl border cursor-pointer transition-colors"
                                           :class="form.timeline === '<?= esc_js($id); ?>' ? 'border-secondary bg-secondary/10 text-white' : 'border-white/10 bg-white/[0.02] text-slate-300 hover:border-white/25'">
                                        <input type="radio" name="timeline" value="<?= esc_attr($id); ?>" x-model="form.timeline" class="accent-amber-500 size-4 shrink-0">
                                        <span class="text-[13px] sm:text-sm font-bold leading-snug"><?= esc_html($title); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                    </div>

                    <!-- STEP 4: details -->
                    <fieldset x-show="step === 4" x-cloak x-transition:enter="rq-step-enter" x-transition:enter-start="rq-from" x-transition:enter-end="rq-to" x-transition:leave="rq-step-leave" x-transition:leave-start="rq-to" x-transition:leave-end="rq-out" class="rq-step pb-3 space-y-4">
                        <legend class="rq-title text-lg sm:text-xl font-black text-white mb-1" tabindex="-1">کمی بیشتر توضیح دهید</legend>
                        <p class="rq-sub text-sm text-slate-400 -mt-3">همه فیلدهای این مرحله اختیاری‌اند، ولی کمک می‌کنند جلسه اول دقیق‌تر باشد.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="rq-business" class="block text-sm font-bold text-slate-200 mb-2">نام کسب‌وکار / برند</label>
                                <input id="rq-business" type="text" x-model="form.business" maxlength="120" autocomplete="organization" enterkeyhint="next" class="kmpr-input" placeholder="مثلاً: کافه ری‌را">
                            </div>
                            <div>
                                <label for="rq-ref" class="block text-sm font-bold text-slate-200 mb-2">سایت فعلی یا نمونه مورد علاقه</label>
                                <input id="rq-ref" type="text" inputmode="url" dir="ltr" x-model="form.reference" maxlength="300" autocomplete="url" enterkeyhint="next" class="kmpr-input text-left" placeholder="example.com">
                            </div>
                        </div>
                        <div>
                            <label for="rq-msg" class="block text-sm font-bold text-slate-200 mb-2">ایده و هدف پروژه</label>
                            <textarea id="rq-msg" rows="4" x-model="form.message" maxlength="2000" class="kmpr-input resize-none min-h-28"
                                      placeholder="مخاطب شما چه کسانی هستند؟ مهم‌ترین چیزی که سایت باید انجام دهد چیست؟"></textarea>
                            <p class="text-xs text-slate-400 mt-1.5 text-left" dir="ltr"><span x-text="form.message.length"></span>/2000</p>
                        </div>
                    </fieldset>

                    <!-- STEP 5: contact -->
                    <fieldset x-show="step === 5" x-cloak x-transition:enter="rq-step-enter" x-transition:enter-start="rq-from" x-transition:enter-end="rq-to" x-transition:leave="rq-step-leave" x-transition:leave-start="rq-to" x-transition:leave-end="rq-out" class="rq-step pb-3 space-y-4">
                        <legend class="rq-title text-lg sm:text-xl font-black text-white mb-1" tabindex="-1">چطور با شما در ارتباط باشم؟</legend>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-3.5">
                            <div>
                                <label for="rq-name" class="block text-sm font-bold text-slate-200 mb-2">نام و نام خانوادگی <span class="text-secondary">*</span></label>
                                <input id="rq-name" type="text" autocomplete="name" required x-model="form.name" maxlength="80" enterkeyhint="next"
                                       class="kmpr-input" :class="errorField === 'name' && '!border-rose-500'" :aria-invalid="errorField === 'name'">
                            </div>
                            <div>
                                <label for="rq-phone" class="block text-sm font-bold text-slate-200 mb-2">شماره موبایل <span class="text-secondary">*</span></label>
                                <input id="rq-phone" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" required x-model="form.phone" maxlength="16" enterkeyhint="next"
                                       class="kmpr-input text-left" placeholder="0912 123 4567" :class="errorField === 'phone' && '!border-rose-500'" :aria-invalid="errorField === 'phone'">
                            </div>
                            <div>
                                <label for="rq-email" class="block text-sm font-bold text-slate-200 mb-2">ایمیل <span class="text-slate-400 font-normal">(اختیاری)</span></label>
                                <input id="rq-email" type="email" autocomplete="email" dir="ltr" x-model="form.email" maxlength="120" enterkeyhint="next"
                                       class="kmpr-input text-left" :class="errorField === 'email' && '!border-rose-500'" :aria-invalid="errorField === 'email'">
                            </div>
                            <div>
                                <label for="rq-tg" class="block text-sm font-bold text-slate-200 mb-2">آیدی تلگرام <span class="text-slate-400 font-normal">(اختیاری)</span></label>
                                <input id="rq-tg" type="text" dir="ltr" x-model="form.telegram" maxlength="40" autocapitalize="off" enterkeyhint="done" class="kmpr-input text-left" placeholder="@username">
                            </div>
                        </div>
                        <div>
                            <span id="rq-channel-label" class="block text-sm font-bold text-slate-200 mb-2.5">ترجیح می‌دهید از چه راهی ارتباط بگیرم؟</span>
                            <div class="grid grid-cols-4 gap-2" role="radiogroup" aria-labelledby="rq-channel-label">
                                <?php foreach ($kmpr_opts['channels'] as $id => $title) : ?>
                                    <label class="flex items-center justify-center px-1.5 sm:px-3 py-2.5 min-h-12 rounded-xl border cursor-pointer text-[13px] sm:text-sm font-bold whitespace-nowrap transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-secondary/50"
                                           :class="form.channel === '<?= esc_js($id); ?>' ? 'border-secondary bg-secondary/10 text-white' : 'border-white/10 bg-white/[0.02] text-slate-300 hover:border-white/25'">
                                        <input type="radio" name="channel" value="<?= esc_attr($id); ?>" x-model="form.channel" class="sr-only">
                                        <?= esc_html($title); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed flex gap-2 items-start">
                            <svg width="16" height="16" class="shrink-0 mt-0.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                            اطلاعات شما فقط برای پیگیری همین درخواست استفاده می‌شود و در اختیار هیچ شخص یا سرویس دیگری قرار نمی‌گیرد.
                        </p>
                    </fieldset>

                    </div>

                    <!-- Error -->
                    <div x-show="errorMessage" x-cloak role="alert"
                         class="shrink-0 mx-4 sm:mx-7 mt-2 flex items-start gap-3 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                        <svg width="18" height="18" class="shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                        <span x-text="errorMessage"></span>
                    </div>

                    <!-- Action bar (always visible at the bottom of the card) -->
                    <div class="rq-bar shrink-0 flex items-center gap-3 px-4 sm:px-7 pt-3 sm:pt-4 mt-1 border-t border-white/10 bg-slate-900/60">
                        <button type="button" @click="go(step - 1)" x-show="step > 1" aria-label="مرحله قبل"
                                class="inline-flex items-center justify-center gap-2 size-12 sm:size-auto sm:px-5 sm:py-3 sm:min-h-12 shrink-0 rounded-full text-sm font-bold bg-white/[0.06] border border-white/10 text-slate-200 hover:bg-white/10 transition-colors cursor-pointer whitespace-nowrap">
                            <svg width="18" height="18" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.64l-4.16-3.96a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.16-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
                            <span class="hidden sm:inline">مرحله قبل</span>
                        </button>
                        <p x-show="step === 1" class="flex-1 text-xs text-slate-400 leading-relaxed sm:hidden">یک گزینه را لمس کنید تا ادامه دهیم.</p>
                        <button type="submit" :disabled="status === 'sending'" x-show="step > 1 || form.type" :class="step === 1 && 'hidden sm:inline-flex'"
                                class="flex-1 sm:flex-none sm:ms-auto inline-flex items-center justify-center gap-2 px-7 py-3 min-h-12 rounded-full text-[15px] sm:text-sm font-black bg-secondary hover:bg-secondary-hover text-slate-950 hover:text-white transition-colors shadow-lg shadow-secondary/25 cursor-pointer whitespace-nowrap disabled:opacity-60 disabled:cursor-wait">
                            <span x-show="step < totalSteps">ادامه</span>
                            <span x-show="step === totalSteps && status !== 'sending'">ثبت و ارسال درخواست</span>
                            <span x-show="status === 'sending'" class="inline-flex items-center gap-2">
                                <svg width="16" height="16" class="shrink-0 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3"/></svg>
                                در حال ارسال…
                            </span>
                            <svg x-show="status !== 'sending'" width="16" height="16" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                </form>

                <!-- Success -->
                <div x-show="status === 'done'" x-cloak x-transition.opacity.duration.500ms class="flex-1 min-h-0 overflow-y-auto flex flex-col items-center justify-center p-6 sm:p-10 text-center" role="status">
                    <div class="mx-auto size-16 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 flex items-center justify-center mb-5">
                        <svg width="32" height="32" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <h3 class="text-2xl font-black text-white">درخواست شما ثبت شد</h3>
                    <p class="text-slate-300 text-sm sm:text-base mt-3 max-w-md mx-auto leading-relaxed">
                        ممنون <span x-text="form.name"></span>! درخواست مستقیم برای من ارسال شد و به‌زودی از طریق
                        <b class="text-white" x-text="label('channels', form.channel)"></b> با شما در ارتباط خواهم بود.
                    </p>
                    <div class="inline-flex items-center gap-3 mt-6 px-5 py-3 rounded-2xl bg-white/[0.04] border border-white/10">
                        <span class="text-xs text-slate-400">کد پیگیری</span>
                        <code class="font-mono font-black text-secondary text-lg" dir="ltr" x-text="trackingCode"></code>
                        <button type="button" @click="copyCode()" class="text-xs font-bold text-slate-300 hover:text-white cursor-pointer min-h-11 px-2" aria-label="کپی کد پیگیری">کپی</button>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-8">
                        <a :href="directTelegram" target="_blank" rel="noopener"
                           class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-6 py-3 min-h-11 rounded-full bg-sky-600 hover:bg-sky-500 text-white text-sm font-black whitespace-nowrap transition-colors">
                            <svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m20.67 3.72-17.73 6.83c-1.21.49-1.2 1.16-.22 1.47l4.55 1.42 10.53-6.65c.5-.3.95-.14.58.19l-8.53 7.7-.31 4.68c.46 0 .66-.21.92-.46l2.21-2.15 4.6 3.4c.85.47 1.46.23 1.67-.79l3.02-14.23c.31-1.24-.47-1.8-1.28-1.43z"/></svg>
                            پیگیری سریع در تلگرام
                        </a>
                        <button type="button" @click="reset()"
                                class="w-full sm:w-auto px-6 py-3 min-h-11 rounded-full bg-white/[0.06] border border-white/10 text-slate-200 text-sm font-bold hover:bg-white/10 cursor-pointer whitespace-nowrap">
                            ثبت درخواست دیگر
                        </button>
                    </div>
                </div>
            </div>

            <!-- ============ Live Summary ============ -->
            <aside class="flex flex-col gap-4 lg:h-[clamp(500px,calc(100svh-19.5rem),680px)] min-h-0" aria-label="پیش‌نمایش زنده سایت شما">
                <?php if (!empty($kmpr_config['service'])) : ?>
                    <p class="text-xs font-bold text-slate-400">خدمت انتخابی: <span class="text-secondary"><?= esc_html($kmpr_config['service']); ?></span></p>
                <?php endif; ?>
                <div class="hidden lg:block lg:min-h-0 lg:flex-1 lg:overflow-hidden rounded-3xl"><?php get_template_part('template-parts/services/site-preview'); ?></div>

                <div class="shrink-0 bg-white/[0.02] border border-white/10 rounded-3xl p-5 lg:py-3">
                    <h3 class="text-sm font-black text-white mb-3 lg:hidden">بعد از ارسال چه می‌شود؟</h3>
                    <ol class="space-y-2.5 text-xs text-slate-300 leading-relaxed lg:hidden">
                        <li class="flex gap-3"><span class="size-6 rounded-full bg-white/[0.06] text-secondary font-black flex items-center justify-center shrink-0">۱</span>درخواست را بررسی می‌کنم و ظرف چند ساعت کاری تماس می‌گیرم.</li>
                        <li class="flex gap-3"><span class="size-6 rounded-full bg-white/[0.06] text-secondary font-black flex items-center justify-center shrink-0">۲</span>یک جلسه کوتاه رایگان برای شناخت دقیق نیازها.</li>
                        <li class="flex gap-3"><span class="size-6 rounded-full bg-white/[0.06] text-secondary font-black flex items-center justify-center shrink-0">۳</span>پیشنهاد مکتوب شامل محدوده کار، زمان‌بندی و هزینه.</li>
                    </ol>
                    <div class="mt-3 pt-3 border-t border-white/10 lg:mt-0 lg:pt-0 lg:border-0 flex items-center justify-between gap-3">
                        <span class="text-xs text-slate-400">عجله دارید؟</span>
                        <a href="<?= esc_url(kmpr_contact_url('telegram')); ?>" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1.5 text-xs font-black text-sky-400 hover:text-sky-300 whitespace-nowrap min-h-11">
                            پیام مستقیم در تلگرام
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
    <noscript><style>#smart-configurator .rq-fallback{display:flex!important}#smart-configurator .rq-card>:not(.rq-fallback){display:none!important}</style></noscript>
    <script>/* If the wizard script never starts (blocked/failed bundle), offer direct contact instead of a dead form. */
        setTimeout(function () { var s = document.getElementById('smart-configurator'); if (s && !s.classList.contains('rq-live')) s.classList.add('rq-failed'); }, 6000);</script>
</section>
