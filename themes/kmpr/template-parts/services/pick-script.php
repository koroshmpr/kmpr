<?php
/**
 * Services — CTA → request wizard preselect (type / service / plan) before the anchor scrolls.
 * Links: <a href="#smart-configurator" data-srv-pick data-type="seo" data-service="سئو" data-plan="پلن ۶ ماهه">
 */
?>
<script>
document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-srv-pick]');
    var el = document.getElementById('smart-configurator');
    if (!a || !el || !window.Alpine || typeof window.Alpine.$data !== 'function') return;
    try {
        var d = window.Alpine.$data(el);
        if (a.dataset.type && d.options && d.options.types && d.options.types[a.dataset.type]) d.form.type = a.dataset.type;
        if (a.dataset.service) d.service = a.dataset.service;
        if (a.dataset.plan) {
            var line = 'پلن مورد نظر: ' + a.dataset.plan;
            if ((d.form.message || '').indexOf(line) === -1) d.form.message = (line + '\n' + (d.form.message || '')).trim();
        }
    } catch (err) { /* wizard not ready */ }
});
</script>
