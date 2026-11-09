/**
 * Classic editor (TinyMCE 4) plugin: «بنر» button → dialog → [kmpr_banner …] shortcode,
 * plus a wp.mce.views preview card so the shortcode reads as a banner in the visual tab.
 * Hand-written (no build step); data comes from window.kmprBanner (inc/article-banners.php).
 */
(function () {
	'use strict';

	var LABELS = { game: 'بازی', service: 'خدمات', portfolio: 'نمونه‌کار', course: 'دوره', ad: 'تبلیغ' };
	var COLORS = { game: '#e879f9', service: '#f59e0b', portfolio: '#38bdf8', course: '#34d399', ad: '#a78bfa' };

	function choices() {
		return (window.kmprBanner && window.kmprBanner.choices) || [{ value: 'ad:', text: 'تبلیغ سفارشی' }];
	}

	function labelFor(type, item) {
		var list = choices();
		for (var i = 0; i < list.length; i++) {
			if (list[i].value === type + ':' + item) {
				return list[i].text;
			}
		}
		return (LABELS[type] || type) + (item ? ' — ' + item : '');
	}

	function esc(s) {
		return String(s || '').replace(/["\[\]]/g, '');
	}

	function build(v) {
		var parts = String(v.choice || 'ad:').split(':');
		var sc = '[kmpr_banner type="' + parts[0] + '"';
		if (parts[1]) {
			sc += ' item="' + parts[1] + '"';
		}
		['title', 'text', 'cta', 'url', 'image', 'sponsor'].forEach(function (k) {
			if (v[k]) {
				sc += ' ' + k + '="' + esc(v[k]) + '"';
			}
		});
		return sc + ']';
	}

	tinymce.PluginManager.add('kmpr_banner', function (editor) {
		function open(values) {
			values = values || {};
			editor.windowManager.open({
				title: 'درج بنر داخل مقاله',
				width: 560,
				height: 380,
				body: [
					{ type: 'listbox', name: 'choice', label: 'بنر', values: choices(), value: values.choice || choices()[0].value },
					{ type: 'container', html: '<p style="margin:4px 0 8px;color:#646970;white-space:normal">فیلدهای زیر اختیاری‌اند و متن پیش‌فرض را عوض می‌کنند. برای «تبلیغ سفارشی» عنوان و لینک الزامی است.</p>' },
					{ type: 'textbox', name: 'title', label: 'عنوان', value: values.title || '' },
					{ type: 'textbox', name: 'text', label: 'توضیح کوتاه', value: values.text || '' },
					{ type: 'textbox', name: 'cta', label: 'متن دکمه', value: values.cta || '' },
					{ type: 'textbox', name: 'url', label: 'لینک (فقط تبلیغ)', value: values.url || '' },
					{ type: 'textbox', name: 'image', label: 'تصویر (آدرس یا ID)', value: values.image || '' },
					{ type: 'textbox', name: 'sponsor', label: 'اسپانسر (فقط تبلیغ)', value: values.sponsor || '' }
				],
				onsubmit: function (e) {
					if (e.data.choice === 'ad:' && (!e.data.title || !e.data.url)) {
						editor.windowManager.alert('برای تبلیغ سفارشی، عنوان و لینک را وارد کنید.');
						return false;
					}
					editor.insertContent('<p>' + build(e.data) + '</p>');
				}
			});
		}

		editor.addButton('kmpr_banner', {
			text: 'بنر',
			icon: 'dashicon dashicons-megaphone',
			tooltip: 'درج بنر (بازی، خدمات، نمونه‌کار، دوره یا تبلیغ)',
			onclick: function () {
				open();
			}
		});
	});

	/* Visual-tab preview card. */
	if (window.wp && wp.mce && wp.mce.views) {
		wp.mce.views.register('kmpr_banner', {
			initialize: function () {
				var a = this.shortcode.attrs.named;
				var type = a.type || 'ad';
				var color = COLORS[type] || '#a78bfa';
				var title = a.title || labelFor(type, a.item || '');
				this.render(
					'<div style="direction:rtl;font-family:inherit;display:flex;align-items:center;gap:12px;padding:14px 16px;margin:6px 0;border-radius:16px;' +
						'background:linear-gradient(135deg,#0b1224,#111a33);border:1px solid ' + color + ';color:#e2e8f0">' +
						'<span style="flex:none;padding:4px 10px;border-radius:999px;background:' + color + ';color:#0b1224;font-weight:700;font-size:12px">' + (LABELS[type] || type) + '</span>' +
						'<strong style="flex:1;font-size:14px">' + tinymce.DOM.encode(title) + '</strong>' +
						'<span style="flex:none;font-size:12px;opacity:.7">بنر داخل مقاله</span></div>'
				);
			},
			edit: function (text, update) {
				var a = (wp.shortcode.next('kmpr_banner', text) || { shortcode: { attrs: { named: {} } } }).shortcode.attrs.named;
				var editor = tinymce.activeEditor;
				var values = { choice: (a.type || 'ad') + ':' + (a.item || ''), title: a.title, text: a.text, cta: a.cta, url: a.url, image: a.image, sponsor: a.sponsor };
				editor.windowManager.open({
					title: 'ویرایش بنر',
					width: 560,
					height: 380,
					body: [
						{ type: 'listbox', name: 'choice', label: 'بنر', values: choices(), value: values.choice },
						{ type: 'textbox', name: 'title', label: 'عنوان', value: values.title || '' },
						{ type: 'textbox', name: 'text', label: 'توضیح کوتاه', value: values.text || '' },
						{ type: 'textbox', name: 'cta', label: 'متن دکمه', value: values.cta || '' },
						{ type: 'textbox', name: 'url', label: 'لینک (فقط تبلیغ)', value: values.url || '' },
						{ type: 'textbox', name: 'image', label: 'تصویر (آدرس یا ID)', value: values.image || '' },
						{ type: 'textbox', name: 'sponsor', label: 'اسپانسر (فقط تبلیغ)', value: values.sponsor || '' }
					],
					onsubmit: function (e) {
						update(build(e.data));
					}
				});
			}
		});
	}
})();
