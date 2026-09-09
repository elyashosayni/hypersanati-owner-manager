<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Product_Questions_Actions {


    public static function register() {

        add_action(
            'admin_post_hom_question_action',
            [self::class, 'handle']
        );

    }


    public static function handle() {

        if (!current_user_can('manage_woocommerce')) {
            wp_die('دسترسی غیرمجاز');
        }


        check_admin_referer(
            'hom_question_action',
            'hom_question_nonce'
        );


        $action = isset($_POST['question_action'])
            ? sanitize_text_field(wp_unslash($_POST['question_action']))
            : '';

        $question_id = isset($_POST['question_id'])
            ? absint($_POST['question_id'])
            : 0;

        error_log('QUESTION ACTION: ' . $action);
        error_log('QUESTION CONTENT: ' . ($_POST['question_content'] ?? 'EMPTY'));
        error_log('ANSWER CONTENT: ' . ($_POST['answer_content'] ?? 'EMPTY'));


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


            case 'answer':

                $content = isset($_POST['answer_content'])
                    ? sanitize_textarea_field(
                        wp_unslash($_POST['answer_content'])
                    )
                    : '';

                if ($content) {

                    $result = HOM_Product_Questions::add_answer(
                        $question_id,
                        $content
                    );

                    if (!$result) {
                        wp_die('پاسخ ذخیره نشد');
                    }

                } else {
                    wp_die('متن پاسخ خالی است');
                }

                break;


            case 'answer_edit':

                $content = isset($_POST['answer_content'])
                    ? sanitize_textarea_field(
                        wp_unslash($_POST['answer_content'])
                    )
                    : '';

                if ($content) {
                    HOM_Product_Questions::update_answer(
                        absint($_POST['answer_id']),
                        $content
                    );
                }

                break;


            case 'answer_delete':

                HOM_Product_Questions::delete_answer(
                    absint($_POST['answer_id'])
                );

                break;



            case 'edit':

                $content = isset($_POST['question_content'])
                    ? sanitize_textarea_field(
                        wp_unslash($_POST['question_content'])
                    )
                    : '';

                if ($content) {
                    HOM_Product_Questions::update_question(
                        $question_id,
                        $content
                    );
                }

                break;

        }


        wp_safe_redirect(
            add_query_arg(
                [
                    'view' => 'customer-center',
                    'section' => 'questions',
                ],
                HOM_Router::panel_url()
            )
        );

        exit;

    }

}
