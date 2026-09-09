<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Product_Reviews_Actions {


    public static function register() {

        add_action(
            'admin_post_hom_review_action',
            [self::class, 'handle']
        );

    }


    public static function handle() {


        if (!current_user_can('manage_woocommerce')) {
            wp_die('دسترسی غیرمجاز');
        }


        if (
            !isset($_POST['hom_review_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['hom_review_nonce'])
                ),
                'hom_review_action'
            )
        ) {
            wp_die('درخواست نامعتبر');
        }


        $action = isset($_POST['review_action'])
            ? sanitize_text_field(
                wp_unslash($_POST['review_action'])
            )
            : '';


        $review_id = isset($_POST['review_id'])
            ? absint($_POST['review_id'])
            : 0;


        $review = get_comment($review_id);


        if (!$review || $review->comment_type !== 'review') {
            wp_die('نظر پیدا نشد');
        }


        $product_id = (int) $review->comment_post_ID;


        switch ($action) {

            case 'approve':

                HOM_Product_Reviews::approve_review($review_id);
                break;


            case 'reject':

                HOM_Product_Reviews::reject_review($review_id);
                break;


            case 'delete':

                HOM_Product_Reviews::delete_review($review_id);
                break;


            case 'edit':

                HOM_Product_Reviews::update_review(
                    $review_id,
                    $_POST['content'] ?? '',
                    $_POST['rating'] ?? null
                );
                break;


            default:

                wp_die('عملیات نامعتبر');
        }


        HOM_Product_Reviews::refresh_product_rating($product_id);


        wp_safe_redirect(
            wp_get_referer()
        );

        exit;

    }

}
