</main>
<footer>
    <?php
    //          main footer
    get_template_part('template-parts/layout/footer/index');
//    back to top button
    get_template_part('template-parts/layout/backToTop');

    get_template_part('template-parts/search-mobile');

    if (is_singular( 'services' ) ) {  get_template_part('template-parts/backToArchive') ;}

    ?>
</footer>
<?php wp_footer(); ?>
</body>
</html>