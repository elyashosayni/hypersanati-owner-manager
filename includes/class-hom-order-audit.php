<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Order_Audit {

    private const META_KEY =
        '_hom_order_audit_log';


    private static function actor(
        $user_id
    ) {

        $user_id =
            absint($user_id);

        $user =
            $user_id
                ? get_userdata($user_id)
                : false;


        return [
            'id' =>
                $user_id,

            'name' =>
                $user
                    ? (
                        $user->display_name
                            ?: $user->user_login
                    )
                    : 'کاربر نامشخص',

            'login' =>
                $user
                    ? $user->user_login
                    : '',
        ];
    }


    public static function record(
        $order,
        $event,
        $user_id,
        $description = ''
    ) {

        if (!($order instanceof WC_Order)) {
            return;
        }


        $log =
            $order->get_meta(
                self::META_KEY,
                true
            );


        if (!is_array($log)) {
            $log = [];
        }


        $actor =
            self::actor(
                $user_id
            );


        $log[] = [

            'event' =>
                sanitize_key(
                    $event
                ),

            'user_id' =>
                $actor['id'],

            'user_name' =>
                $actor['name'],

            'user_login' =>
                $actor['login'],

            'description' =>
                sanitize_text_field(
                    $description
                ),

            'timestamp' =>
                current_time(
                    'timestamp',
                    true
                ),

            'date' =>
                current_time(
                    'mysql'
                ),
        ];


        /*
         * Prevent unlimited meta growth.
         */
        if (count($log) > 250) {
            $log = array_slice(
                $log,
                -250
            );
        }


        $order->update_meta_data(
            self::META_KEY,
            $log
        );
    }


    public static function get(
        $order
    ) {

        if (!($order instanceof WC_Order)) {
            return [];
        }


        $log =
            $order->get_meta(
                self::META_KEY,
                true
            );


        if (!is_array($log)) {
            return [];
        }


        return array_reverse(
            $log
        );
    }


    public static function event_label(
        $event
    ) {

        $labels = [

            'assignee_changed' =>
                'تغییر مسئول پرونده',

            'price_updated' =>
                'قیمت‌گذاری پیش‌فاکتور',

            'preinvoice_approved' =>
                'تأیید پیش‌فاکتور',

            'shipping_updated' =>
                'ویرایش اطلاعات ارسال',

            'order_ready' =>
                'آماده ارسال',

            'order_shipped' =>
                'ثبت ارسال سفارش',

            'order_delivered' =>
                'ثبت تحویل سفارش',
        ];


        return
            $labels[$event]
            ?? $event;
    }


    public static function render(
        $order
    ) {

        $log =
            self::get(
                $order
            );


        if (!$log) {
            return;
        }

        ?>

        <section
            class="hom-card hom-order-audit"
            style="margin-top:20px"
        >

            <h2>
                سوابق اقدامات مسئولان فروش
            </h2>

            <div class="hom-order-audit__list">

                <?php foreach ($log as $row) : ?>

                    <article class="hom-order-audit__item">

                        <div>

                            <strong>
                                <?php
                                echo esc_html(
                                    self::event_label(
                                        $row['event'] ?? ''
                                    )
                                );
                                ?>
                            </strong>

                            <?php
                            if (
                                !empty(
                                    $row['description']
                                )
                            ) :
                                ?>

                                <span>
                                    <?php
                                    echo esc_html(
                                        $row['description']
                                    );
                                    ?>
                                </span>

                            <?php endif; ?>

                        </div>


                        <div class="hom-order-audit__actor">

                            <strong>
                                <?php
                                echo esc_html(
                                    $row['user_name']
                                    ?? 'کاربر نامشخص'
                                );
                                ?>
                            </strong>

                            <?php
                            if (
                                !empty(
                                    $row['user_login']
                                )
                            ) :
                                ?>

                                <span dir="ltr">
                                    @<?php
                                    echo esc_html(
                                        $row['user_login']
                                    );
                                    ?>
                                </span>

                            <?php endif; ?>

                            <small>
                                User ID:
                                <?php
                                echo esc_html(
                                    absint(
                                        $row['user_id']
                                        ?? 0
                                    )
                                );
                                ?>
                            </small>

                        </div>


                        <time>

                            <?php
                            echo esc_html(
                                !empty(
                                    $row['timestamp']
                                )
                                    ? wp_date(
                                        'Y/m/d H:i',
                                        absint(
                                            $row['timestamp']
                                        )
                                    )
                                    : (
                                        $row['date']
                                        ?? '—'
                                    )
                            );
                            ?>

                        </time>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>

        <?php
    }
}
