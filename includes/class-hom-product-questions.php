<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Product_Questions {


    public static function get_questions($args = []) {

        $defaults = [
            'status'   => 'approve',
            'type'     => 'product_question',
            'parent'   => 0,
            'number'   => 50,
            'orderby'  => 'comment_date_gmt',
            'order'    => 'DESC',
        ];


        $args = array_merge(
            $defaults,
            $args
        );


        if (!empty($args['keyword'])) {

            $args['search'] = sanitize_text_field(
                $args['keyword']
            );

            unset($args['keyword']);

        }


        return get_comments($args);

    }


    public static function get_answers($question_id) {

        return get_comments([
            'status'  => 'approve',
            'type'    => 'product_answer',
            'parent'  => absint($question_id),
            'orderby' => 'comment_date_gmt',
            'order'   => 'ASC',
        ]);

    }



    public static function add_answer($question_id, $content) {

        $question = get_comment($question_id);

        if (!$question) {
            return false;
        }

        return wp_insert_comment([
            'comment_post_ID'  => $question->comment_post_ID,
            'comment_content'  => sanitize_textarea_field($content),
            'comment_type'     => 'product_answer',
            'comment_parent'   => absint($question_id),
            'user_id'          => get_current_user_id(),
            'comment_approved' => 1,
        ]);

    }


    public static function update_answer($answer_id, $content) {

        return wp_update_comment([
            'comment_ID'      => absint($answer_id),
            'comment_content' => sanitize_textarea_field($content),
        ]);

    }


    public static function delete_answer($answer_id) {

        return wp_delete_comment(
            absint($answer_id),
            false
        );

    }



    public static function count_questions() {

        return (int) get_comments([
            'status' => 'approve',
            'type'   => 'product_question',
            'parent' => 0,
            'count'  => true,
        ]);

    }


    public static function approve_question($question_id) {

        return wp_set_comment_status(
            absint($question_id),
            'approve'
        );

    }


    public static function reject_question($question_id) {

        $question_id = absint($question_id);

        if (!$question_id) {
            return false;
        }

        update_comment_meta(
            $question_id,
            'qa_status',
            'rejected'
        );

        return wp_set_comment_status(
            $question_id,
            'hold'
        );

    }


    public static function delete_question($question_id) {

        return wp_delete_comment(
            absint($question_id),
            false
        );

    }


    public static function update_question($question_id, $content) {

        return wp_update_comment([
            'comment_ID'      => absint($question_id),
            'comment_content' => sanitize_textarea_field($content),
        ]);

    }



}
