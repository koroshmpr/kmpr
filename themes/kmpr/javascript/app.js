import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';
import projectRequest from './project-request';
import initHeroPortrait from './hero-portrait';
import initBlog from './blog';
import initPlayNudge from './play-nudge';

window.Alpine = Alpine;
Alpine.plugin(intersect);
window.projectRequest = projectRequest;
initBlog(Alpine);

// Live search (command-palette style): tabs per content type, keyboard navigation, recent searches.
const SEARCH_RECENT_KEY = 'kmpr_recent_searches';
const escHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

window.themeSearch = function (types = {}) {
    const empty = () => Object.fromEntries(['all', ...Object.keys(types)].map((k) => [k, []]));
    return {
        types,
        query: '',
        activeTab: 'all',
        results: empty(),
        counts: {},
        isLoading: false,
        hasSearched: false,
        cursor: -1,
        recent: [],
        _timer: null,
        _ctrl: null,

        init() {
            try { this.recent = JSON.parse(localStorage.getItem(SEARCH_RECENT_KEY) || '[]').slice(0, 6); } catch (e) { this.recent = []; }
            this.$watch('query', (value) => {
                clearTimeout(this._timer);
                this.cursor = -1;
                if (!value || value.trim().length < 2) {
                    this.resetResults();
                    return;
                }
                this.isLoading = true;
                this._timer = setTimeout(() => this.fetchResults(), 260);
            });
            this.$watch('searchOpen', (open) => {
                if (open) this.$nextTick(() => setTimeout(() => this.$refs.searchInput?.focus(), 60));
            });
        },

        get list() {
            return this.results[this.activeTab] || [];
        },

        setTab(tab) {
            this.activeTab = tab;
            this.cursor = -1;
        },

        resetResults() {
            this.results = empty();
            this.counts = {};
            this.isLoading = false;
            this.hasSearched = false;
        },

        clearQuery() {
            this.query = '';
            this.resetResults();
            this.$refs.searchInput?.focus();
        },

        use(term) {
            this.query = term;
            this.$refs.searchInput?.focus();
        },

        remember() {
            const q = this.query.trim();
            if (q.length < 2) return;
            this.recent = [q, ...this.recent.filter((r) => r !== q)].slice(0, 6);
            try { localStorage.setItem(SEARCH_RECENT_KEY, JSON.stringify(this.recent)); } catch (e) { /* private mode */ }
        },

        forget() {
            this.recent = [];
            try { localStorage.removeItem(SEARCH_RECENT_KEY); } catch (e) { /* noop */ }
        },

        highlight(text) {
            const safe = escHtml(text || '');
            const q = this.query.trim();
            if (q.length < 2) return safe;
            const re = new RegExp(escHtml(q).replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
            return safe.replace(re, (m) => `<mark class="bg-secondary/25 text-amber-200 rounded px-0.5">${m}</mark>`);
        },

        move(step) {
            const n = this.list.length;
            if (!n) return;
            this.cursor = (this.cursor + step + n) % n;
            this.$nextTick(() => this.$root.querySelector(`[data-sr="${this.cursor}"]`)?.scrollIntoView({ block: 'nearest' }));
        },

        open() {
            const item = this.list[this.cursor] || this.list[0];
            if (item) {
                this.remember();
                window.location.href = item.url;
            } else if (this.query.trim().length >= 2) {
                this.remember();
                window.location.href = this.fullUrl();
            }
        },

        fullUrl() {
            const root = (typeof jsData !== 'undefined' && jsData.root_url) ? jsData.root_url : '';
            return `${root}/?s=${encodeURIComponent(this.query.trim())}${this.activeTab !== 'all' ? `&post_type=${this.activeTab}` : ''}`;
        },

        fetchResults() {
            const term = this.query.trim();
            if (term.length < 2) return this.resetResults();
            this._ctrl?.abort();
            this._ctrl = new AbortController();
            const root = (typeof jsData !== 'undefined' && jsData.root_url) ? jsData.root_url : '';
            fetch(`${root}/wp-json/search/v1/search?term=${encodeURIComponent(term)}`, { headers: { Accept: 'application/json' }, signal: this._ctrl.signal })
                .then((r) => { if (!r.ok) throw new Error(r.status); return r.json(); })
                .then((data) => {
                    const res = empty();
                    Object.keys(res).forEach((k) => (res[k] = data[k] || []));
                    this.results = res;
                    this.counts = data.counts || {};
                    this.hasSearched = true;
                    this.isLoading = false;
                    if (this.activeTab !== 'all' && !res[this.activeTab].length) this.activeTab = 'all';
                })
                .catch((e) => {
                    if (e.name === 'AbortError') return;
                    this.isLoading = false;
                    this.hasSearched = true;
                });
        },
    };
};

document.addEventListener('DOMContentLoaded', function () {
    performance.mark('kmpr:alpine:start');
    Alpine.start();
    performance.mark('kmpr:alpine:end');
    window.kmprMotion?.scan(); // bind reveal targets rendered by Alpine
    performance.mark('kmpr:motion:end');
    initHeroPortrait();
    initPlayNudge();
    performance.mark('kmpr:portrait:end');

    // Sliders: load Swiper (≈150KB) only on pages that actually contain one.
    if (document.querySelector('.post-slider, .hero_swiper')) {
        import('./sliders').then(m => m.default());
    }

    // Back to top button handler
    const backToTop = document.getElementById("backToTop");
    if (backToTop) {
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
});
