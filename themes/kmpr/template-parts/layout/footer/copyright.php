<div class="pt-6 mt-2 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
    <div class="flex items-center gap-3 flex-wrap justify-center sm:justify-start">
        <p class="text-center sm:text-start">
            تمامی حقوق مادی و معنوی این وب‌سایت محفوظ است.
        </p>
        <span class="text-slate-700 hidden sm:inline">•</span>
        <a href="<?= esc_url(home_url('/resume/')); ?>" class="text-slate-400 hover:text-secondary transition-colors inline-flex items-center gap-1.5 underline-offset-4 hover:underline font-bold">
            <svg class="size-3.5 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
            <span>رزومه آنلاین کوروش محمدپور</span>
        </a>
    </div>
    <p class="text-slate-500 dir-ltr font-mono">
        &copy; <?= date('Y'); ?> <?= esc_html(get_bloginfo('name')); ?>. All rights reserved.
    </p>
</div>
