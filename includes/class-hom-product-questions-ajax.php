<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Product_Questions_Ajax {


    public static function register() {

        add_action(
            'wp_ajax_hom_question_action',
            [self::class, 'handle']
        );

    }


    public static function handle() {

        check_ajax_referer(
            'hom_question_action',
            'nonce'
        );


        $question_id = isset($_POST['question_id'])
            ? absint($_POST['question_id'])
            : 0;


        $action = isset($_POST['question_action'])
            ? sanitize_key($_POST['question_action'])
            : '';


        if (!$question_id) {
            wp_send_json_error();
        }


        switch ($action) {

            case 'approve':
                HOM_Product_Questions::approve_question($question_id);
                break;


            case 'reject':
                HOM_Product_Questions::reject_question($question_id);
                break;


            case 'delete':
                HOM_Product_Questions::delete_question($question_id);
                break;

        }


        wp_send_json_success([
            'action' => $action,
        ]);

    }


}
