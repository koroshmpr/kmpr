<button class="search-btn btn-fixed d-lg-none border-1 position-fixed border-primary border-opacity-10 border bg-secondary text-primary mb-5 mb-lg-0 rounded-circle d-flex justify-content-center align-items-center"
        type="button" data-bs-toggle="offcanvas"
        data-bs-target="#offcanvasBottom"
        aria-controls="offcanvasBottom"
        data-aos="fade-right"
        aria-labelledby="offcanvasBottom">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-search text-primary" viewBox="0 0 16 16">
        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
    </svg>
</button>

<div class="offcanvas offcanvas-bottom bg-primary" tabindex="-1" id="offcanvasBottom" aria-labelledby="offcanvasBottomLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title text-white" id="offcanvasBottomLabel">جستجو در سایت</h5>
        <button type="button" class="btn-close bg-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body small">
        <?php
        $place = 'mobile';
        $sizeSearch = 'col';
        $inputClass = 'py-3 bg-white';
        $buttonClass = "px-4";
        $dropdownClass = 'd-none';
        $args = array(
            'place' => $place,
            'size' => $sizeSearch,
            'inputClass' => $inputClass,
            'buttonClass' => $buttonClass,
            'dropdownClass' => $dropdownClass
        );

        get_template_part('template-parts/search-bar', null ,$args); ?>
    </div>
</div>