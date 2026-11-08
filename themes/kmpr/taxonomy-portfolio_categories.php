<?php
/**
 * Portfolio category archive — same layout as the portfolio archive, filtered to one term.
 */
get_header();
$kmpr_term = get_queried_object();
get_template_part('template-parts/portfolio/archive-layout', null, array('term' => ($kmpr_term instanceof WP_Term) ? $kmpr_term : null));
get_footer();
