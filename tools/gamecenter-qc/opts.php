<?php
// Usage: php opts.php  — prints relevant Game Center options (local debug).
define('WP_USE_THEMES', false);
require dirname(__DIR__, 2) . '/../wp-load.php';
foreach (['sub_on','am_on_pass','am_on_support','am_support_url','am_on_sponsor','am_on_adv'] as $k) echo $k, '=', var_export(get_field($k,'option'), true), "\n";
echo 'games=', wp_count_posts('game')->publish, "\n";
$q = get_posts(['post_type'=>'game','numberposts'=>100,'fields'=>'ids']); foreach ($q as $id) echo get_post_field('post_name',$id), ' ';
echo "\n404 tpl: ", file_exists(get_template_directory().'/404.php') ? 'y':'n', "\n";
