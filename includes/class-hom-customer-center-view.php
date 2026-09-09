<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Customer_Center_View {


    public static function render() {

        $active_section = isset($_GET['section'])
            ? sanitize_key($_GET['section'])
            : 'reviews';

        if (!in_array($active_section, ['reviews', 'questions', 'tickets'], true)) {
            $active_section = 'reviews';
        }

        ?>

        <section class="hom-dashboard">


            <div class="hom-page-heading hom-dashboard-heading">

                <div>

                    <span class="hom-eyebrow">
                        CUSTOMER CENTER
                    </span>


                    <h1>
                        پشتیبانی مشتریان
                    </h1>


                    <p>
                        مدیریت نقد و نظرات، پرسش‌های محصولات و درخواست‌های پشتیبانی مشتریان
                    </p>

                </div>


                <a
                    href="<?php
                    echo esc_url(
                        add_query_arg(
                            'view',
                            'help-customer-center',
                            HOM_Router::panel_url()
                        )
                    );
                    ?>"
                    class="hom-dashboard-help"
                >
                    <span aria-hidden="true">
                        ?
                    </span>

                    راهنمای مدیریت مشتری‌ها
                </a>


            </div>



            <div class="hom-customer-center-cards">


                <button
                    type="button"
                    class="hom-card hom-customer-center-card <?php echo $active_section === 'reviews' ? 'is-active' : ''; ?>"
                    data-section="reviews"
                >

                    <h2>
                        ⭐ نقد و نظرات
                    </h2>

                    <p>
                        بررسی و پاسخ به نظرات مشتریان
                    </p>

                </button>



                <button
                    type="button"
                    class="hom-card hom-customer-center-card <?php echo $active_section === 'questions' ? 'is-active' : ''; ?>"
                    data-section="questions"
                >

                    <h2>
                        ❓ پرسش‌های محصولات
                    </h2>

                    <p>
                        مدیریت سوالات ثبت شده برای محصولات
                    </p>

                </button>



                <button
                    type="button"
                    class="hom-card hom-customer-center-card <?php echo $active_section === 'tickets' ? 'is-active' : ''; ?>"
                    data-section="tickets"
                >

                    <h2>
                        🎫 تیکت‌ها
                    </h2>

                    <p>
                        پشتیبانی و درخواست‌های مشتریان
                    </p>

                </button>


            </div>



            <div
                class="hom-customer-center-panel"
                id="hom-customer-center-panel"
            >

                <div data-panel="reviews"<?php echo 'reviews' === $active_section ? '' : ' hidden'; ?>>
                    <?php self::render_reviews_panel(); ?>
                </div>


                <div data-panel="questions"<?php echo 'questions' === $active_section ? '' : ' hidden'; ?>>
                    <?php self::render_questions_panel(); ?>
                </div>


                <div data-panel="tickets"<?php echo 'tickets' === $active_section ? '' : ' hidden'; ?>>
                    <?php self::render_tickets_panel(); ?>
                </div>


            </div>

            <?php self::render_question_edit_script(); ?>

        </section>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const cards = document.querySelectorAll('.hom-customer-center-card');
    const panels = document.querySelectorAll('[data-panel]');


    function activateSection(section, updateUrl = false) {

        cards.forEach(function (card) {

            card.classList.toggle(
                'is-active',
                card.dataset.section === section
            );

        });


        panels.forEach(function (panel) {

            panel.hidden = panel.dataset.panel !== section;

        });


        if (updateUrl) {

            const url = new URL(window.location.href);

            url.searchParams.set(
                'section',
                section
            );

            window.history.pushState(
                {},
                '',
                url
            );

        }

    }


    cards.forEach(function (card) {

        card.addEventListener('click', function () {

            activateSection(
                this.dataset.section,
                true
            );

        });

    });


    window.addEventListener('popstate', function () {

        const url = new URL(window.location.href);

        activateSection(
            url.searchParams.get('section') || 'reviews'
        );

    });

});

    document.querySelectorAll('.hom-question-actions form')
    .forEach(function(form){

        form.addEventListener('submit', function(){

            sessionStorage.setItem(
                'hom_question_scroll',
                window.scrollY
            );

        });

    });


    const savedScroll = sessionStorage.getItem(
        'hom_question_scroll'
    );

    if (savedScroll) {

        setTimeout(function(){

            window.scrollTo(
                0,
                parseInt(savedScroll)
            );

            sessionStorage.removeItem(
                'hom_question_scroll'
            );

        }, 300);

    }

</script>

        <?php

    }


    private static function render_reviews_panel() {

        ?>

        <h2>
            ⭐ نقد و نظرات
        </h2>


        <?php

        $review_status = isset($_GET['review_status'])
            ? sanitize_text_field($_GET['review_status'])
            : 'all';

        if (!in_array($review_status, ['all', '1', '0'], true)) {
            $review_status = 'all';
        }

        $all_count = count(
            HOM_Product_Reviews::get_reviews([
                'status' => 'all',
            ])
        );

        $approved_count = count(
            HOM_Product_Reviews::get_reviews([
                'status' => '1',
            ])
        );

        $pending_count = count(
            HOM_Product_Reviews::get_reviews([
                'status' => '0',
            ])
        );

        ?>

        <div class="hom-review-filters">

            <a class="hom-review-filter-card <?php echo $review_status === 'all' ? 'active' : ''; ?>"
               href="<?php echo esc_url(add_query_arg(
                    [
                        'view' => 'customer-center',
                        'review_status' => 'all'
                    ],
                    HOM_Router::panel_url()
                )); ?>">
                <strong>همه</strong>
                <span><?php echo esc_html($all_count); ?></span>
                <small>نظر</small>
            </a>


            <a class="hom-review-filter-card <?php echo $review_status === '1' ? 'active' : ''; ?>"
               href="<?php echo esc_url(add_query_arg(
                    [
                        'view' => 'customer-center',
                        'review_status' => '1'
                    ],
                    HOM_Router::panel_url()
                )); ?>">
                <strong>تأیید شده</strong>
                <span><?php echo esc_html($approved_count); ?></span>
                <small>نظر</small>
            </a>


            <a class="hom-review-filter-card <?php echo $review_status === '0' ? 'active' : ''; ?>"
               href="<?php echo esc_url(add_query_arg(
                    [
                        'view' => 'customer-center',
                        'review_status' => '0'
                    ],
                    HOM_Router::panel_url()
                )); ?>">
                <strong>در انتظار تأیید</strong>
                <span><?php echo esc_html($pending_count); ?></span>
                <small>نظر</small>
            </a>

        </div>


        <?php

        $review_status = isset($_GET['review_status'])
            ? sanitize_text_field($_GET['review_status'])
            : 'all';


        $reviews = HOM_Product_Reviews::get_reviews([
            'status' => $review_status === 'all'
                ? 'all'
                : $review_status,
        ]);

        if (!empty($reviews)) :

        ?>

        <div class="hom-customer-review-list">

        <?php foreach ($reviews as $review) : ?>

            <?php
            $status = $review->comment_approved;

            if ($status === '1') {
                $status_label = 'تأیید شده';
            } else {
                $status_label = 'در انتظار بررسی';
            }

            $rating = HOM_Product_Reviews::get_rating(
                $review->comment_ID
            );
            ?>

            <article class="hom-customer-review-item">

                <strong>
                    <a href="<?php echo esc_url(get_permalink($review->comment_post_ID)); ?>"
                       target="_blank">
                        <?php
                        echo esc_html(
                            get_the_title(
                                $review->comment_post_ID
                            )
                        );
                        ?>
                    </a>
                </strong>


                <div class="hom-review-content">

                    <p class="hom-review-text">
                        <?php
                        echo esc_html(
                            $review->comment_content
                        );
                        ?>
                    </p>


                </div>





                <small>
                    مشتری:
                    <?php
                    echo esc_html(
                        $review->comment_author
                    );
                    ?>

                    |

                    امتیاز:
                    ⭐ <?php echo esc_html($rating ?: 0); ?>/5

                    |

                    وضعیت:

                    <span class="hom-question-status status-<?php echo esc_attr($status); ?>">
                        <?php echo esc_html($status_label); ?>
                    </span>
                </small>


                <div class="hom-review-actions">


                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">


                        <?php wp_nonce_field(
                            'hom_review_action',
                            'hom_review_nonce'
                        ); ?>


                        <input type="hidden" name="action" value="hom_review_action">

                        <input type="hidden"
                               name="review_id"
                               value="<?php echo esc_attr($review->comment_ID); ?>">

                        <textarea
                            class="hom-review-edit-field"
                            name="content"
                            hidden
                        ><?php echo esc_textarea($review->comment_content); ?></textarea>





                        <button class="hom-button hom-button-primary"
                                name="review_action"
                                value="approve">
                            ✓ تأیید
                        </button>


                        <button class="hom-button hom-button-secondary"
                                name="review_action"
                                value="reject">
                            × عدم تأیید
                        </button>


                        <button class="hom-button hom-button-secondary"
                                name="review_action"
                                value="delete">
                            ⌫ حذف
                        </button>

                        <button
                            type="button"
                            class="hom-button hom-button-secondary hom-review-edit-toggle"
                        >
                            ✎ ویرایش
                        </button>


                        <button
                            class="hom-button hom-button-primary hom-review-save"
                            name="review_action"
                            value="edit"
                            hidden
                        >
                            ✓ ذخیره
                        </button>


                    </form>


                </div>


            </article>

        <?php endforeach; ?>

        </div>


        <?php else : ?>

            <p>
                هنوز نقدی ثبت نشده است.
            </p>

        <?php endif;

    }



    private static function render_questions_panel() {

        ?>

        <h2>
            ❓ پرسش‌های محصولات
        </h2>


        <?php

        $question_status = isset($_GET['question_status'])
            ? sanitize_text_field($_GET['question_status'])
            : 'all';


        $all_questions = HOM_Product_Questions::get_questions([
            'status' => 'all',
        ]);

        $approved_questions = HOM_Product_Questions::get_questions([
            'status' => '1',
        ]);

        $pending_questions = HOM_Product_Questions::get_questions([
            'status' => '0',
        ]);


        ?>


        <div class="hom-review-filters">


            <a class="hom-review-filter-card <?php echo $question_status === 'all' ? 'active' : ''; ?>"
               href="<?php echo esc_url(add_query_arg(['view'=>'customer-center', 'section'=>'questions', 'question_status'=>'all'], HOM_Router::panel_url())); ?>">
                <strong>همه</strong>
                <span><?php echo count($all_questions); ?></span>
                <small>پرسش</small>
            </a>


            <a class="hom-review-filter-card <?php echo $question_status === '1' ? 'active' : ''; ?>"
               href="<?php echo esc_url(add_query_arg(['view'=>'customer-center', 'section'=>'questions', 'question_status'=>'1'], HOM_Router::panel_url())); ?>">
                <strong>تأیید شده</strong>
                <span><?php echo count($approved_questions); ?></span>
                <small>پرسش</small>
            </a>


            <a class="hom-review-filter-card <?php echo $question_status === '0' ? 'active' : ''; ?>"
               href="<?php echo esc_url(add_query_arg(['view'=>'customer-center', 'section'=>'questions', 'question_status'=>'0'], HOM_Router::panel_url())); ?>">
                <strong>در انتظار تأیید</strong>
                <span><?php echo count($pending_questions); ?></span>
                <small>پرسش</small>
            </a>


        </div>


        <?php

        $questions = HOM_Product_Questions::get_questions([
            'status' => $question_status,
        ]);


        if (!empty($questions)) :

        ?>

        <div class="hom-customer-question-list">

        <?php foreach ($questions as $question) : ?>

            <?php
            $status = $question->comment_approved;

            if ($status === '1') {
                $status_label = 'تأیید شده';
            } elseif ($status === '0') {
                $status_label = 'در انتظار بررسی';
            } else {
                $status_label = 'رد شده';
            }
            ?>


            <article class="hom-customer-question-item">


                <strong>
                    <a href="<?php echo esc_url(get_permalink($question->comment_post_ID)); ?>"
                       target="_blank">
                        <?php
                        echo esc_html(
                            get_the_title($question->comment_post_ID)
                        );
                        ?>
                    </a>
                </strong>


                <div class="hom-question-content">

                    <p class="hom-question-text">
                        <?php
                        echo esc_html(
                            $question->comment_content
                        );
                        ?>
                    </p>


                </div>


                <?php
                $answers = HOM_Product_Questions::get_answers(
                    $question->comment_ID
                );

                if (!empty($answers)) :
                ?>

                    <div class="hom-question-answers">

                        <?php foreach ($answers as $answer) : ?>

                            <div class="hom-question-answer-item">

                                <strong>
                                    پاسخ مدیریت:
                                </strong>

                                <p>
                                    <?php echo esc_html($answer->comment_content); ?>
                                </p>


                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

                                    <?php wp_nonce_field(
                                        'hom_question_action',
                                        'hom_question_nonce'
                                    ); ?>

                                    <input type="hidden" name="action" value="hom_question_action">

                                    <input type="hidden"
                                           name="answer_id"
                                           value="<?php echo esc_attr($answer->comment_ID); ?>">


                                    <textarea
                                        class="hom-question-answer-edit-field"
                                        name="answer_content"
                                        hidden
                                    ><?php echo esc_textarea($answer->comment_content); ?></textarea>


                                    <div class="hom-question-answer-actions">

                                    <button
                                        type="button"
                                        class="hom-button hom-button-secondary hom-answer-edit-toggle"
                                    >
                                        ✎ ویرایش
                                    </button>


                                    <button
                                        class="hom-button hom-button-primary hom-answer-save"
                                        name="question_action"
                                        value="answer_edit"
                                        hidden
                                    >
                                        ✓ ذخیره
                                    </button>


                                    <button
                                        class="hom-button hom-button-secondary"
                                        name="question_action"
                                        value="answer_delete"
                                    >
                                        ⌫ حذف
                                    </button>

                                    </div>


                                </form>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


                <small>
                    مشتری:
                    <?php
                    echo esc_html(
                        $question->comment_author
                    );
                    ?>

                    |
                    وضعیت:
                    <?php
                    echo '<span class="hom-question-status status-' . esc_attr($status) . '">'
                        . esc_html($status_label)
                        . '</span>';
                    ?>
                </small>


                <div class="hom-question-actions">


                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

                        <?php wp_nonce_field(
                            'hom_question_action',
                            'hom_question_nonce'
                        ); ?>


                        <input type="hidden" name="action" value="hom_question_action">

                        <input type="hidden" name="section" value="questions">
                        <input type="hidden" name="question_id" value="<?php echo esc_attr($question->comment_ID); ?>">


                        <textarea
                            class="hom-question-answer-field"
                            name="answer_content"
                            hidden
                        ></textarea>


                        <textarea
                            class="hom-question-edit-field"
                            name="question_content"
                            hidden
                        ><?php echo esc_textarea($question->comment_content); ?></textarea>



                        <button class="hom-button hom-button-primary" name="question_action" value="approve">
                            ✓ تأیید
                        </button>


                        <button class="hom-button hom-button-secondary" name="question_action" value="reject">
                            × عدم تأیید
                        </button>


                        <button class="hom-button hom-button-secondary" name="question_action" value="delete">
                            ⌫ حذف
                        </button>

                        <button
                            type="button"
                            class="hom-button hom-button-secondary hom-question-edit-toggle"
                        >
                            ✎ ویرایش سوال
                        </button>

                        <button
                            class="hom-button hom-button-primary hom-question-save"
                            name="question_action"
                            value="edit"
                            hidden
                        >
                            ✓ ذخیره
                        </button>

                        <button
                            type="button"
                            class="hom-button hom-button-secondary hom-question-answer-toggle"
                        >
                            پاسخ
                        </button>

                        <button
                            class="hom-button hom-button-primary hom-question-answer-save"
                            name="question_action"
                            value="answer"
                            hidden
                        >
                            ✓ ارسال پاسخ
                        </button>



                    </form>


                </div>


            </article>


        <?php endforeach; ?>

        </div>


        <?php else : ?>

            <p>
                هنوز پرسشی ثبت نشده است.
            </p>

        <?php endif;


    }



    private static function render_question_edit_script() {

        ?>
        <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {

                document.querySelectorAll(
                    '.hom-question-edit-toggle, .hom-review-edit-toggle'
                ).forEach(
                    function(button) {

                        button.addEventListener(
                            'click',
                            function() {

                                var card =
                                    button.closest(
                                        '.hom-customer-question-item, .hom-customer-review-item'
                                    );

                                if (!card) {
                                    return;
                                }

                                var text =
                                    card.querySelector(
                                        '.hom-question-text, .hom-review-text'
                                    );

                                var textarea =
                                    card.querySelector(
                                        '.hom-question-edit-field, .hom-review-edit-field'
                                    );

                                var save =
                                    card.querySelector(
                                        '.hom-question-save, .hom-review-save'
                                    );


                                if (!text || !textarea || !save) {
                                    return;
                                }


                                text.hidden = true;
                                textarea.hidden = false;
                                save.hidden = false;

                            }
                        );

                    }
                );


            
            document.querySelectorAll(
                '.hom-question-answer-toggle'
            ).forEach(
                function(button) {

                    button.addEventListener(
                        'click',
                        function() {

                            var card =
                                button.closest(
                                    '.hom-customer-question-item'
                                );

                            if (!card) {
                                return;
                            }

                            var textarea =
                                card.querySelector(
                                    '.hom-question-answer-field'
                                );

                            var save =
                                card.querySelector(
                                    '.hom-question-answer-save'
                                );


                            if (!textarea || !save) {
                                return;
                            }


                            textarea.hidden = false;
                            save.hidden = false;

                        }
                    );

                }
            );

            document.querySelectorAll(
                '.hom-answer-edit-toggle'
            ).forEach(
                function(button) {

                    button.addEventListener(
                        'click',
                        function() {

                            var item =
                                button.closest(
                                    '.hom-question-answer-item'
                                );

                            if (!item) {
                                return;
                            }

                            var text =
                                item.querySelector('p');

                            var textarea =
                                item.querySelector(
                                    '.hom-question-answer-edit-field'
                                );

                            var save =
                                item.querySelector(
                                    '.hom-answer-save'
                                );


                            if (!text || !textarea || !save) {
                                return;
                            }


                            text.hidden = true;
                            textarea.hidden = false;
                            save.hidden = false;

                        }
                    );

                }
            );


            }
        );
        </script>
        <?php

    }


    private static function render_tickets_panel() {

        ?>
        <h2>
            🎫 تیکت‌ها
        </h2>

        <p>
            بخش مدیریت تیکت‌های مشتریان در این قسمت نمایش داده خواهد شد.
        </p>
        <?php

    }


    }

