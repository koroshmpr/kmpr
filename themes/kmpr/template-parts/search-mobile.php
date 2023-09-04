<button class="search-btn btn-fixed d-lg-none border-1 position-fixed border-primary border-opacity-10 border bg-secondary text-primary mb-5 mb-lg-0 rounded-circle d-flex justify-content-center align-items-center"
        type="button" data-bs-toggle="offcanvas"
        data-bs-target="#offcanvasBottom"
        aria-controls="offcanvasBottom"
        data-aos="fade-right" >
    <i class="bi bi-search mt-1"></i>
</button>

<div class="offcanvas offcanvas-bottom bg-primary" tabindex="-1" id="offcanvasBottom" aria-labelledby="offcanvasBottomLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title text-white" id="offcanvasBottomLabel">جسنجو در سایت</h5>
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