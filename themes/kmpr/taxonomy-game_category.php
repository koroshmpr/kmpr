<?php
/**
 * Game genre archive (/games/genre/{slug}/) — same hub layout filtered by genre.
 */
get_header();
get_template_part('template-parts/games/hub', null, array('term' => get_queried_object()));
get_footer();
