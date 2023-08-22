<div class="bg-secondary copyright py-2 mb-0 rounded-top text-center d-lg-none">
    <div class="container">
        <div class="">
            <!--                social media-->
            <?php
            $sizeSvgX = '30';
            $sizeSvgY = '30';
            $colorSvg = 'white';
            $class = "fill-primary justify-content-center";
            $args = array(
                'class' => $class,
                'sizeSvgX' => $sizeSvgX,
                'sizeSvgY' => $sizeSvgY,
                'colorSvg' => $colorSvg
            );
            get_template_part('template-parts/social-media', null, $args);
            ?>
        </div>

    </div>
</div>