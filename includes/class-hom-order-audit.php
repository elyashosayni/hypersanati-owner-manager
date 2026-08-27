<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Order_Audit {

    private const META_KEY =
        '_hom_order_audit_log';


    private static function actor(
        $user_id,
        $source = ''
    ) {

        $user_id =
            absint($user_id);

        $user =
            $user_id
                ? get_userdata($user_id)
                : false;


        $role_slug = '';
        $role_name = '';


        if ($user instanceof WP_User) {

            $roles =
                array_values(
                    (array) $user->roles
                );


            $role_slug =
                isset($roles[0])
                    ? sanitize_key(
                        $roles[0]
                    )
                    : '';


            if ($role_slug) {

                $roles_object =
                    wp_roles();


                $role_name =
                    isset(
                        $roles_object
                            ->roles[
                                $role_slug
                            ]['name']
                    )
                        ? translate_user_role(
                            $roles_object
                                ->roles[
                                    $role_slug
                                ]['name']
                        )
                        : $role_slug;
            }
        }


        if ($user instanceof WP_User) {

            $name =
                $user->display_name
                    ?: $user->user_login;

        } elseif ('gateway' === $source) {

            $name =
                'درگاه پرداخت';

        } elseif ('customer' === $source) {

            $name =
                'مشتری';

        } else {

            $name =
                'سیستم';
        }


        return [

            'id' =>
                $user_id,

            'name' =>
                $name,

            'login' =>
                $user instanceof WP_User
                    ? $user->user_login
                    : '',

            'role_slug' =>
                $role_slug,

            'role_name' =>
                $role_name,
        ];
    }


    private static function normalize_changes(
        $changes
    ) {

        if (!is_array($changes)) {
            return [];
        }


        $result = [];


        foreach ($changes as $change) {

            if (!is_array($change)) {
                continue;
            }


            $field =
                sanitize_text_field(
                    (string)
                    ($change['field'] ?? '')
                );


            if ('' === $field) {
                continue;
            }


            $before =
                $change['before']
                ?? '';

            $after =
                $change['after']
                ?? '';


            if (
                is_array($before) ||
                is_object($before)
            ) {

                $before =
                    wp_json_encode(
                        $before,
                        JSON_UNESCAPED_UNICODE
                    );
            }


            if (
                is_array($after) ||
                is_object($after)
            ) {

                $after =
                    wp_json_encode(
                        $after,
                        JSON_UNESCAPED_UNICODE
                    );
            }


            $result[] = [

                'field' =>
                    $field,

                'before' =>
                    sanitize_textarea_field(
                        (string) $before
                    ),

                'after' =>
                    sanitize_textarea_field(
                        (string) $after
                    ),
            ];
        }


        return $result;
    }


    public static function source_label(
        $source
    ) {

        $labels = [

            'owner-panel' =>
                'پنل فروش',

            'customer' =>
                'مشتری',

            'gateway' =>
                'درگاه پرداخت',

            'system' =>
                'سیستم',

            'wp-admin' =>
                'پیشخوان وردپرس',
        ];


        $source =
            sanitize_key(
                (string) $source
            );


        return
            $labels[$source]
            ?? (
                $source
                    ?: 'سیستم'
            );
    }


    public static function record(
        $order,
        $event,
        $user_id,
        $description = '',
        array $context = []
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


        $source =
            sanitize_key(
                (string)
                (
                    $context['source']
                    ?? (
                        $user_id
                            ? 'owner-panel'
                            : 'system'
                    )
                )
            );


        $actor =
            self::actor(
                $user_id,
                $source
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

            'role_slug' =>
                $actor['role_slug'],

            'role_name' =>
                $actor['role_name'],

            'source' =>
                $source,

            'description' =>
                sanitize_textarea_field(
                    $description
                ),

            'reason' =>
                sanitize_textarea_field(
                    (string)
                    (
                        $context['reason']
                        ?? ''
                    )
                ),

            'changes' =>
                self::normalize_changes(
                    $context['changes']
                    ?? []
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


        if (count($log) > 250) {

            $log =
                array_slice(
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

            'b2b_customer_updated' =>
                'ویرایش اطلاعات حقوقی خریدار',

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

            'preinvoice_created' =>
                'ثبت درخواست پیش‌فاکتور',

            'payment_confirmed' =>
                'تأیید پرداخت',

            'payment_corrected' =>
                'اصلاح وضعیت پرداخت',

            'price_corrected' =>
                'اصلاح قیمت پیش‌فاکتور',

            'b2b_customer_corrected' =>
                'اصلاح اطلاعات حقوقی خریدار',

            'shipping_corrected' =>
                'اصلاح اطلاعات ارسال',
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
