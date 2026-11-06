<?php
//
//
//function woocommerce_pre_get_posts($query)
//{
//
//    if (!is_admin() && is_shop() && $query->is_main_query()) {
//
//
//        $price = explode(',', $_GET['prices']);
//        $minPrice = intval($price[0]);
//        $maxPrice = intval($price[1]);
//
//        $taxQuery = ['relation' => 'AND'];
//        $metaQuery = ['relation' => 'AND'];
//
//
////        if ($_GET['colors']) {
////            $taxQuery[] = array(
////                'taxonomy' => 'pa_color',
////                'field' => 'term_id',
////                'terms' => $_GET['colors'],
////                'operator' => 'IN',
////            );
////        }
////        if ($_GET['sizes']) {
////            $taxQuery[] = array(
////                'taxonomy' => 'pa_size',
////                'field' => 'term_id',
////                'terms' => $_GET['sizes'],
////                'operator' => 'IN',
////            );
////        }
////        if ($_GET['brands']) {
////            $taxQuery[] = array(
////                'taxonomy' => 'brand',
////                'field' => 'term_id',
////                'terms' => $_GET['brands'],
////                'operator' => 'IN',
////            );
////        }
//        if($_GET['prices']){
//            $metaQuery[] = array(
//                'relation' => 'AND',
//                array(
//                    'key' => '_price',
//                    'value' => $minPrice,
//                    'compare' => '>=',
//                ),
//                array(
//                    'key' => '_price',
//                    'value' => $maxPrice,
//                    'compare' => '<=',
//                )
//            );
//        }
//
//        $query->set('tax_query', $taxQuery);
//        $query->set('meta_query', $metaQuery);
//
//    }
//    if ($query->is_main_query() && $query->is_search() && !is_admin()) {
//        $query->set('post_type', array('post','product'));
//        $query->set('tax_query', array('brand'));
//    }
//}
//
//add_action('pre_get_posts', 'woocommerce_pre_get_posts', 20);