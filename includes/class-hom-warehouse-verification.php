<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Warehouse_Verification {

    private const TOKEN_META =
        '_hom_warehouse_verification_token';

    private const VERIFIED_AT_META =
        '_hom_warehouse_verified_at';

    private const VERIFIED_BY_META =
        '_hom_warehouse_verified_by';

    private const VERIFIED_ITEMS_META =
        '_hom_warehouse_verified_items';


    private static function token(
        $order
    ) {

        if (!($order instanceof WC_Order)) {
            return '';
        }


        $token =
            trim(
                (string)
                $order->get_meta(
                    self::TOKEN_META,
                    true
                )
            );


        if ($token) {
            return $token;
        }


        $token =
            wp_generate_password(
                48,
                false,
                false
            );


        $order->update_meta_data(
            self::TOKEN_META,
            $token
        );

        $order->save();


        return $token;
    }


    public static function url(
        $order
    ) {

        if (!($order instanceof WC_Order)) {
            return '';
        }


        $token =
            self::token(
                $order
            );


        if (!$token) {
            return '';
        }


        return add_query_arg(
            [
                'view' =>
                    'warehouse-check',

                'order_id' =>
                    $order->get_id(),

                'warehouse_token' =>
                    $token,
            ],
            HOM_Router::panel_url()
        );
    }


    private static function token_is_valid(
        $order,
        $token
    ) {

        if (!($order instanceof WC_Order)) {
            return false;
        }


        $stored =
            trim(
                (string)
                $order->get_meta(
                    self::TOKEN_META,
                    true
                )
            );


        $token =
            trim(
                (string) $token
            );


        return
            '' !== $stored &&
            '' !== $token &&
            hash_equals(
                $stored,
                $token
            );
    }


    private static function expected_item_ids(
        $order
    ) {

        if (!($order instanceof WC_Order)) {
            return [];
        }


        $ids = [];


        foreach (
            $order->get_items('line_item')
            as $item_id => $item
        ) {

            unset($item);

            $item_id =
                absint(
                    $item_id
                );


            if ($item_id > 0) {

                $ids[] =
                    $item_id;
            }
        }


        sort($ids);


        return array_values(
            array_unique(
                $ids
            )
        );
    }


    public static function confirm(
        $order_id,
        $token,
        array $checked_item_ids,
        $actor_user_id
    ) {

        $order =
            HOM_Orders::get_order(
                $order_id
            );


        if (!$order) {

            return new WP_Error(
                'warehouse_order_missing',
                'سفارش پیدا نشد.'
            );
        }


        if (
            !self::token_is_valid(
                $order,
                $token
            )
        ) {

            return new WP_Error(
                'warehouse_token_invalid',
                'لینک کنترل انبار معتبر نیست.'
            );
        }


        $actor_user_id =
            absint(
                $actor_user_id
            );


        if (
            $actor_user_id < 1 ||
            !user_can(
                $actor_user_id,
                HOM_Capabilities::
                    CAP_MANAGE_FULFILLMENT
            )
        ) {

            return new WP_Error(
                'warehouse_actor_invalid',
                'کاربر مجاز برای تأیید انبار مشخص نیست.'
            );
        }


        if (
            'processing' !==
            $order->get_status()
        ) {

            return new WP_Error(
                'warehouse_status_invalid',
                'فقط سفارش در حال آماده‌سازی قابل تأیید نهایی انبار است.'
            );
        }


        $expected =
            self::expected_item_ids(
                $order
            );


        if (!$expected) {

            return new WP_Error(
                'warehouse_items_missing',
                'این سفارش کالایی برای کنترل انبار ندارد.'
            );
        }


        $checked =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'absint',
                            $checked_item_ids
                        )
                    )
                )
            );


        sort($checked);


        if ($expected !== $checked) {

            return new WP_Error(
                'warehouse_items_incomplete',
                'برای تأیید نهایی، تمام اقلام سفارش باید کنترل و تیک زده شوند.'
            );
        }


        /*
         * Use the existing official fulfillment transition.
         * This keeps processing -> hom-ready in one place.
         */
        $result =
            HOM_Orders::save_fulfillment(
                $order->get_id(),
                HOM_Orders::fulfillment_data(
                    $order
                ),
                'ready',
                $actor_user_id
            );


        if (is_wp_error($result)) {
            return $result;
        }


        $result->update_meta_data(
            self::VERIFIED_AT_META,
            current_time('mysql')
        );

        $result->update_meta_data(
            self::VERIFIED_BY_META,
            $actor_user_id
        );

        $result->update_meta_data(
            self::VERIFIED_ITEMS_META,
            $checked
        );


        HOM_Order_Audit::record(
            $result,
            'warehouse_verified',
            $actor_user_id,
            'تمام اقلام سفارش توسط انبار کنترل و تأیید نهایی شد.',
            [
                'source' =>
                    'warehouse-qr',

                'changes' => [
                    [
                        'field' =>
                            'کنترل اقلام انبار',

                        'before' =>
                            'تأیید نشده',

                        'after' =>
                            sprintf(
                                '%s ردیف کالا تأیید شد',
                                count($checked)
                            ),
                    ],
                    [
                        'field' =>
                            'مرحله سفارش',

                        'before' =>
                            'در حال آماده‌سازی',

                        'after' =>
                            'آماده ارسال',
                    ],
                ],
            ]
        );


        $actor =
            get_userdata(
                $actor_user_id
            );


        $result->add_order_note(
            sprintf(
                'کنترل نهایی انبار توسط %s انجام شد و تمام اقلام سفارش تأیید شدند.',
                $actor instanceof WP_User
                    ? (
                        $actor->display_name
                        ?: $actor->user_login
                    )
                    : 'کاربر انبار'
            )
        );


        $result->save();


        return $result;
    }


    public static function handle_confirm() {

        if (
            !is_user_logged_in() ||
            !current_user_can(
                HOM_Capabilities::
                    CAP_MANAGE_FULFILLMENT
            )
        ) {

            wp_die(
                'دسترسی غیرمجاز.',
                '',
                [
                    'response' => 403,
                ]
            );
        }


        $order_id =
            isset($_POST['order_id'])
                ? absint(
                    $_POST['order_id']
                )
                : 0;


        check_admin_referer(
            'hom_confirm_warehouse_' .
            $order_id
        );


        $token =
            isset($_POST['warehouse_token'])
                ? sanitize_text_field(
                    wp_unslash(
                        $_POST[
                            'warehouse_token'
                        ]
                    )
                )
                : '';


        $items =
            isset($_POST['warehouse_items'])
                ? (array) wp_unslash(
                    $_POST[
                        'warehouse_items'
                    ]
                )
                : [];


        $result =
            self::confirm(
                $order_id,
                $token,
                $items,
                get_current_user_id()
            );


        $order =
            HOM_Orders::get_order(
                $order_id
            );


        $url =
            $order
                ? self::url($order)
                : HOM_Router::panel_url();


        $url =
            add_query_arg(
                'notice',
                is_wp_error($result)
                    ? 'warehouse-error'
                    : 'warehouse-verified',
                $url
            );


        if (is_wp_error($result)) {

            $url =
                add_query_arg(
                    'warehouse_error',
                    rawurlencode(
                        $result
                            ->get_error_message()
                    ),
                    $url
                );
        }


        wp_safe_redirect(
            $url
        );

        exit;
    }


    public static function render() {

        if (
            !current_user_can(
                HOM_Capabilities::
                    CAP_MANAGE_FULFILLMENT
            )
        ) {

            ?>
            <div class="hom-alert hom-alert-error">
                دسترسی به کنترل انبار برای این کاربر مجاز نیست.
            </div>
            <?php

            return;
        }


        $order_id =
            isset($_GET['order_id'])
                ? absint(
                    $_GET['order_id']
                )
                : 0;


        $token =
            isset($_GET['warehouse_token'])
                ? sanitize_text_field(
                    wp_unslash(
                        $_GET[
                            'warehouse_token'
                        ]
                    )
                )
                : '';


        $order =
            HOM_Orders::get_order(
                $order_id
            );


        if (
            !$order ||
            !self::token_is_valid(
                $order,
                $token
            )
        ) {

            ?>
            <div class="hom-warehouse-check">
                <div class="hom-alert hom-alert-error">
                    لینک کنترل انبار معتبر نیست یا سفارش پیدا نشد.
                </div>
            </div>
            <?php

            return;
        }


        $status =
            $order->get_status();


        $verified_at =
            trim(
                (string)
                $order->get_meta(
                    self::VERIFIED_AT_META,
                    true
                )
            );


        $verified_by =
            absint(
                $order->get_meta(
                    self::VERIFIED_BY_META,
                    true
                )
            );


        $verified_items =
            array_values(
                array_filter(
                    array_map(
                        'absint',
                        (array)
                        $order->get_meta(
                            self::VERIFIED_ITEMS_META,
                            true
                        )
                    )
                )
            );


        $verifier =
            $verified_by
                ? get_userdata(
                    $verified_by
                )
                : false;


        $notice =
            isset($_GET['notice'])
                ? sanitize_key(
                    wp_unslash(
                        $_GET['notice']
                    )
                )
                : '';


        $error =
            isset($_GET['warehouse_error'])
                ? sanitize_text_field(
                    rawurldecode(
                        wp_unslash(
                            $_GET[
                                'warehouse_error'
                            ]
                        )
                    )
                )
                : '';


        $can_confirm =
            'processing' === $status &&
            '' === $verified_at;


        $items =
            $order->get_items(
                'line_item'
            );

        ?>

        <div class="hom-warehouse-check">

            <div class="hom-page-heading">

                <div>

                    <span class="hom-eyebrow">
                        WAREHOUSE CHECK
                    </span>

                    <h1>
                        کنترل نهایی انبار
                        #<?php
                        echo esc_html(
                            $order
                                ->get_order_number()
                        );
                        ?>
                    </h1>

                    <p>
                        اقلام سفارش را با کالای آماده‌شده تطبیق دهید و هر ردیف را پس از کنترل تیک بزنید.
                    </p>

                </div>

            </div>


            <?php if (
                'warehouse-verified' ===
                $notice
            ) : ?>

                <div class="hom-alert hom-alert-success">
                    کنترل انبار با موفقیت ثبت شد و سفارش به مرحله «آماده ارسال» منتقل شد.
                </div>

            <?php elseif (
                'warehouse-error' ===
                $notice
            ) : ?>

                <div class="hom-alert hom-alert-error">
                    <?php
                    echo esc_html(
                        $error
                            ?: 'ثبت کنترل انبار انجام نشد.'
                    );
                    ?>
                </div>

            <?php endif; ?>


            <section class="hom-warehouse-summary">

                <div>
                    <span>شماره سفارش</span>

                    <strong>
                        #<?php
                        echo esc_html(
                            $order
                                ->get_order_number()
                        );
                        ?>
                    </strong>
                </div>


                <div>
                    <span>مشتری</span>

                    <strong>
                        <?php
                        echo esc_html(
                            $order
                                ->get_formatted_billing_full_name()
                            ?: 'ثبت نشده'
                        );
                        ?>
                    </strong>
                </div>


                <div>
                    <span>مقصد</span>

                    <strong>
                        <?php
                        echo esc_html(
                            $order
                                ->get_billing_city()
                            ?: 'ثبت نشده'
                        );
                        ?>
                    </strong>
                </div>


                <div>
                    <span>وضعیت</span>

                    <strong>
                        <?php
                        echo esc_html(
                            HOM_Orders::status_label(
                                $status
                            )
                        );
                        ?>
                    </strong>
                </div>

            </section>


            <?php if ($verified_at) : ?>

                <div class="hom-warehouse-verified">

                    <span class="hom-warehouse-verified__icon">
                        ✓
                    </span>

                    <div>

                        <strong>
                            کنترل انبار تکمیل شده است
                        </strong>

                        <p>
                            <?php
                            echo esc_html(
                                $verified_at
                            );
                            ?>

                            <?php if (
                                $verifier instanceof WP_User
                            ) : ?>

                                —
                                توسط
                                <?php
                                echo esc_html(
                                    $verifier
                                        ->display_name
                                    ?: $verifier
                                        ->user_login
                                );
                                ?>

                            <?php endif; ?>
                        </p>

                    </div>

                </div>

            <?php elseif (
                'processing' !== $status
            ) : ?>

                <div class="hom-alert hom-alert-warning">
                    این سفارش در مرحله قابل تأیید انبار نیست.
                    وضعیت فعلی:
                    <?php
                    echo esc_html(
                        HOM_Orders::status_label(
                            $status
                        )
                    );
                    ?>
                </div>

            <?php endif; ?>


            <form
                method="post"
                action="<?php
                echo esc_url(
                    HOM_Router::panel_url()
                );
                ?>"
                class="hom-warehouse-form"
                data-hom-warehouse-form
            >

                <input
                    type="hidden"
                    name="hom_action"
                    value="hom_confirm_warehouse"
                >

                <input
                    type="hidden"
                    name="order_id"
                    value="<?php
                    echo esc_attr(
                        $order->get_id()
                    );
                    ?>"
                >

                <input
                    type="hidden"
                    name="warehouse_token"
                    value="<?php
                    echo esc_attr(
                        $token
                    );
                    ?>"
                >


                <?php
                wp_nonce_field(
                    'hom_confirm_warehouse_' .
                    $order->get_id()
                );
                ?>


                <div class="hom-warehouse-items">

                    <?php
                    $row = 0;

                    foreach (
                        $items
                        as $item_id => $item
                    ) :

                        $row++;

                        $product =
                            $item->get_product();

                        $sku =
                            $product
                                ? trim(
                                    (string)
                                    $product->get_sku()
                                )
                                : '';

                        $product_id =
                            $product
                                ? $product->get_id()
                                : 0;

                        $part_number =
                            $product_id
                                ? trim(
                                    (string)
                                    get_post_meta(
                                        $product_id,
                                        '_mpn_part_number',
                                        true
                                    )
                                )
                                : '';

                        $was_verified =
                            in_array(
                                absint($item_id),
                                $verified_items,
                                true
                            );
                        ?>

                        <label class="hom-warehouse-item">

                            <input
                                type="checkbox"
                                name="warehouse_items[]"
                                value="<?php
                                echo esc_attr(
                                    $item_id
                                );
                                ?>"
                                <?php
                                checked(
                                    $was_verified
                                );
                                ?>
                                <?php
                                disabled(
                                    !$can_confirm
                                );
                                ?>
                                data-hom-warehouse-item
                            >

                            <span class="hom-warehouse-item__check">
                                ✓
                            </span>


                            <span class="hom-warehouse-item__number">
                                <?php
                                echo esc_html(
                                    $row
                                );
                                ?>
                            </span>


                            <span class="hom-warehouse-item__body">

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $item->get_name()
                                    );
                                    ?>
                                </strong>


                                <span>

                                    SKU:
                                    <b dir="ltr">
                                        <?php
                                        echo esc_html(
                                            $sku
                                                ?: '—'
                                        );
                                        ?>
                                    </b>

                                    <i>•</i>

                                    Part Number:
                                    <b dir="ltr">
                                        <?php
                                        echo esc_html(
                                            $part_number
                                                ?: '—'
                                        );
                                        ?>
                                    </b>

                                </span>

                            </span>


                            <span class="hom-warehouse-item__qty">

                                تعداد

                                <strong>
                                    <?php
                                    echo esc_html(
                                        wc_format_localized_decimal(
                                            $item
                                                ->get_quantity()
                                        )
                                    );
                                    ?>
                                </strong>

                            </span>

                        </label>

                    <?php endforeach; ?>

                </div>


                <?php if ($can_confirm) : ?>

                    <div class="hom-warehouse-progress">

                        <div>
                            <strong data-hom-warehouse-count>
                                0
                            </strong>
                            از
                            <strong>
                                <?php
                                echo esc_html(
                                    count($items)
                                );
                                ?>
                            </strong>
                            ردیف کنترل شده
                        </div>

                        <div class="hom-warehouse-progress__bar">
                            <span data-hom-warehouse-progress></span>
                        </div>

                    </div>


                    <button
                        type="submit"
                        class="
                            hom-button
                            hom-button-primary
                            hom-warehouse-confirm
                        "
                        disabled
                        data-hom-warehouse-confirm
                    >
                        تأیید نهایی انبار و آماده ارسال
                    </button>


                    <p class="hom-warehouse-confirm-note">
                        دکمه تأیید نهایی فقط بعد از کنترل همه اقلام فعال می‌شود.
                    </p>

                <?php endif; ?>

            </form>

        </div>


        <?php if ($can_confirm) : ?>

            <script>
            document.addEventListener(
                'DOMContentLoaded',
                function () {

                    var form =
                        document.querySelector(
                            '[data-hom-warehouse-form]'
                        );

                    if (!form) {
                        return;
                    }


                    var items =
                        Array.prototype.slice.call(
                            form.querySelectorAll(
                                '[data-hom-warehouse-item]'
                            )
                        );

                    var button =
                        form.querySelector(
                            '[data-hom-warehouse-confirm]'
                        );

                    var count =
                        form.querySelector(
                            '[data-hom-warehouse-count]'
                        );

                    var progress =
                        form.querySelector(
                            '[data-hom-warehouse-progress]'
                        );


                    function update() {

                        var checked =
                            items.filter(
                                function (item) {
                                    return item.checked;
                                }
                            ).length;

                        var total =
                            items.length;

                        var percent =
                            total > 0
                                ? (
                                    checked /
                                    total
                                ) * 100
                                : 0;


                        if (count) {
                            count.textContent =
                                checked;
                        }


                        if (progress) {
                            progress.style.width =
                                percent + '%';
                        }


                        if (button) {
                            button.disabled =
                                total < 1 ||
                                checked !== total;
                        }
                    }


                    items.forEach(
                        function (item) {

                            item.addEventListener(
                                'change',
                                update
                            );
                        }
                    );


                    form.addEventListener(
                        'submit',
                        function (event) {

                            var allChecked =
                                items.length > 0 &&
                                items.every(
                                    function (item) {
                                        return item.checked;
                                    }
                                );


                            if (!allChecked) {

                                event.preventDefault();

                                window.alert(
                                    'ابتدا تمام اقلام سفارش را کنترل و تیک بزنید.'
                                );
                            }
                        }
                    );


                    update();
                }
            );
            </script>

        <?php endif; ?>

        <?php
    }
}
