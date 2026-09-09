<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Product_Reviews {


    public static function get_reviews($args = []) {

        $defaults = [
            'type'     => 'review',
            'status'   => 'all',
            'number'   => 50,
            'orderby'  => 'comment_date_gmt',
            'order'    => 'DESC',
        ];

        $args = array_merge($defaults, $args);

        if (!empty($args['keyword'])) {
            $args['search'] = sanitize_text_field($args['keyword']);
            unset($args['keyword']);
        }

        return get_comments($args);
    }


    public static function approve_review($review_id) {

        return wp_set_comment_status(
            absint($review_id),
            'approve'
        );

    }


    public static function reject_review($review_id) {

        return wp_set_comment_status(
            absint($review_id),
            'hold'
        );

    }


    public static function delete_review($review_id) {

        return wp_delete_comment(
            absint($review_id),
            false
        );

    }


    public static function update_review($review_id, $content, $rating = null) {

        $result = wp_update_comment([
            'comment_ID'      => absint($review_id),
            'comment_content' => sanitize_textarea_field($content),
        ]);


        if ($rating !== null) {

            update_comment_meta(
                absint($review_id),
                'rating',
                absint($rating)
            );

        }


        return $result;

    }


    public static function get_rating($review_id) {

        return (int) get_comment_meta(
            absint($review_id),
            'rating',
            true
        );

    }


    public static function refresh_product_rating($product_id) {

        if (function_exists('wc_delete_product_transients')) {
            wc_delete_product_transients($product_id);
        }

        clean_post_cache($product_id);

    }

}
