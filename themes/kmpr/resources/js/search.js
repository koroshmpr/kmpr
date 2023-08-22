import $ from "jquery";

class Search {
    constructor() {
        this.closeButton = $(".search-overlay__close, .mobile-overlay__close , body");
        this.searchOverlay = $(".search-overlay");
        this.searchField = $("input[type=search]");
        this.resultsDiv = $(".search-overlay__results");
        this.searchForm = $(".search-form");
        this.searchForm.on("submit", (event) => this.submitForm(event));

        this.events();

        this.isOverlayOpen = false;
        this.isSpinnerVisible = false;
        //
        // this.previousValue;
        // this.typingTimer;
    }

    events() {
        this.closeButton.on("click", this.closeOverlay.bind(this));
        this.searchForm.on("submit", this.submitForm.bind(this));
        this.searchField.on("keyup", this.typingLogic.bind(this));
    }

    typingLogic() {
        if (this.searchField.val()) {
            $(".mobile-overlay__close").removeClass('d-none');
        }
        if (this.searchField.val() != this.previousValue) {
            clearTimeout(this.typingTimer);
            if (this.searchField.val()) {
                if (!this.isSpinnerVisible) {
                    this.resultsDiv.html(`<div class="text-center mt-2"><div class="spinner-border align-baseline text-primary" role="status"></div></div>`);
                    this.isSpinnerVisible = true;

                }
                this.typingTimer = setTimeout(this.getResults.bind(this), 750);
            } else {
                this.resultsDiv.html('');
                this.isSpinnerVisible = false;
            }
        }
        this.previousValue = this.searchField.val();
    }

    getResults() {
        $.getJSON(jsData.root_url + '/wp-json/wp/v2/posts?search=' + this.searchField.val() + '&_embed', (results) => {
            this.resultsDiv.html(`
            <div class="pt-3">
                <div class="g-3">
                    <!-- POSTS -->
                    <div class="container">
                    <h5 class="mb-2 text-center fw-bold fs-3">مقالات</h5>
                    ${results.length ? '<div class="row row-cols-lg-3 row-cols-1 py-4">' : '<p class="p-2' +
                ' m-0' +
                ' border-top">هیچ مقاله ای یافت' +
                ' نشد</p>'}
                    ${results.map(item =>
                `<a class="my-2" href="${item.link}" alt="${item.title.rendered}">
                            <div class="card p-2 border-top shadow-sm my-2">
                                <div class="row gx-2 gy-0 align-items-center">
                                    <div class="col-3">
                                        <div class="ratio ratio-1x1">
                                            <img src="${item._embedded['wp:featuredmedia'][0].source_url}"
                                                 class="rounded object-fit"
                                                 alt="${item.title.rendered}">
                                        </div>
                                    </div>

                                    <div class="col">
                                        <div class="vstack h-100 py-2">
                                            <h6 class="text-primary fw-bold fw-4 mb-1">${item.title.rendered}</h6>
                                            <p class="text-muted">${item.content.rendered.split(' ').slice(0, 18).join(' ')}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>`
            ).join('')}
                    ${results.length ? '</div>' : ''}
                    </div>
                </div>
            </div>
        `)
            this.isSpinnerVisible = false;
        });
    }
    openOverlay() {
        this.searchField.val('');
        setTimeout(() => this.searchField.focus(), 301);
        this.isOverlayOpen = true;
        return false;
    }
    closeOverlay() {
        this.resultsDiv.html('');
        // this.searchField.val('');
        this.isOverlayOpen = false;
    }
    submitForm() {
        const searchTerm = this.searchField.val();
        if (searchTerm) {
            window.location.href = jsData.root_url + "/?s=" + searchTerm;
        }
    }

}

export default Search;