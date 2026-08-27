<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Order_Detail_View {

    public static function render($order_id) {

        $order = HOM_Orders::get_order($order_id);

        if (!$order) {
            echo '<div class="hom-alert hom-alert-error">سفارش پیدا نشد.</div>';
            return;
        }

        $can_price =
            current_user_can(
                HOM_Capabilities::CAP_MANAGE_PREINVOICES
            ) &&
            HOM_Orders::can_price_preinvoice($order);

        $back_url = add_query_arg(
            'view',
            'orders',
            HOM_Router::panel_url()
        );

        ?>
        <div class="hom-page-heading">
            <div>
                <a href="<?php echo esc_url($back_url); ?>">
                    ← بازگشت به سفارش‌ها
                </a>

                <h1>
                    <?php
                    echo 'yes' === $order->get_meta(
                        '_hsb_is_preinvoice',
                        true
                    )
                        ? 'پیش‌فاکتور'
                        : 'سفارش';
                    ?>

                    #<?php
                    echo esc_html(
                        $order->get_order_number()
                    );
                    ?>
                </h1>

                <p>
                    وضعیت:
                    <?php
                    echo esc_html(
                        HOM_Orders::status_label(
                            $order->get_status()
                        )
                    );
                    ?>
                </p>
            </div>
        </div>

        <section class="hom-card" style="margin-bottom:20px">

            <strong>
                مشتری:
                <?php
                echo esc_html(
                    $order->get_formatted_billing_full_name()
                    ?: 'ثبت نشده'
                );
                ?>
            </strong>

            <p>
                تلفن:
                <span dir="ltr">
                    <?php
                    echo esc_html(
                        $order->get_billing_phone()
                        ?: '—'
                    );
                    ?>
                </span>
            </p>

            <p>
                شهر:
                <?php
                echo esc_html(
                    $order->get_billing_city()
                    ?: '—'
                );
                ?>
            </p>

        </section>


        <?php
        $b2b =
            HOM_Orders::b2b_customer_data(
                $order
            );

        $b2b_notice =
            isset($_GET['notice'])
                ? sanitize_key(
                    wp_unslash(
                        $_GET['notice']
                    )
                )
                : '';
        ?>


        <?php if ('b2b-saved' === $b2b_notice) : ?>

            <div class="hom-alert hom-alert-success">
                اطلاعات حقوقی خریدار ذخیره شد.
            </div>

        <?php elseif ('b2b-error' === $b2b_notice) : ?>

            <div class="hom-alert hom-alert-error">
                ذخیره اطلاعات حقوقی خریدار انجام نشد.
            </div>

        <?php endif; ?>


        <section
            class="hom-card hom-b2b-customer-card"
            style="margin-bottom:20px"
        >

            <h2>
                اطلاعات حقوقی خریدار
            </h2>


            <?php
            if (
                current_user_can(
                    HOM_Capabilities::CAP_MANAGE_PREINVOICES
                )
            ) :
                ?>

                <form
                    method="post"
                    action="<?php
                    echo esc_url(
                        HOM_Router::panel_url()
                    );
                    ?>"
                >

                    <input
                        type="hidden"
                        name="hom_action"
                        value="hom_save_b2b_customer"
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

                    <?php
                    wp_nonce_field(
                        'hom_save_b2b_customer_' .
                        $order->get_id()
                    );
                    ?>


                    <div
                        style="
                            display:grid;
                            grid-template-columns:
                                repeat(2,minmax(0,1fr));
                            gap:14px
                        "
                    >

                        <label class="hom-field">

                            <span>
                                نام حقوقی / نام شرکت
                            </span>

                            <input
                                type="text"
                                name="legal_name"
                                value="<?php
                                echo esc_attr(
                                    $b2b['legal_name']
                                );
                                ?>"
                            >

                        </label>


                        <label class="hom-field">

                            <span>
                                شناسه ملی
                            </span>

                            <input
                                type="text"
                                name="national_id"
                                inputmode="numeric"
                                dir="ltr"
                                value="<?php
                                echo esc_attr(
                                    $b2b['national_id']
                                );
                                ?>"
                            >

                        </label>


                        <label class="hom-field">

                            <span>
                                کد اقتصادی
                            </span>

                            <input
                                type="text"
                                name="economic_code"
                                inputmode="numeric"
                                dir="ltr"
                                value="<?php
                                echo esc_attr(
                                    $b2b['economic_code']
                                );
                                ?>"
                            >

                        </label>


                        <label class="hom-field">

                            <span>
                                شماره ثبت
                            </span>

                            <input
                                type="text"
                                name="registration_no"
                                dir="ltr"
                                value="<?php
                                echo esc_attr(
                                    $b2b['registration_no']
                                );
                                ?>"
                            >

                        </label>


                        <label class="hom-field">

                            <span>
                                کدپستی
                            </span>

                            <input
                                type="text"
                                name="postcode"
                                inputmode="numeric"
                                dir="ltr"
                                value="<?php
                                echo esc_attr(
                                    $b2b['postcode']
                                );
                                ?>"
                            >

                        </label>

                    </div>


                    <label
                        class="hom-field"
                        style="margin-top:14px"
                    >

                        <span>
                            آدرس فاکتور
                        </span>

                        <textarea
                            name="b2b_address"
                            rows="3"
                        ><?php
                        echo esc_textarea(
                            $b2b['address']
                        );
                        ?></textarea>

                    </label>


                    <?php
                    $b2b_already_saved =
                        '' !==
                        trim(
                            (string)
                            $order->get_meta(
                                '_hom_b2b_updated_at',
                                true
                            )
                        );
                    ?>


                    <?php if ($b2b_already_saved) : ?>

                        <label
                            class="hom-field"
                            style="margin-top:14px"
                        >

                            <span>
                                دلیل اصلاح اطلاعات حقوقی
                            </span>

                            <textarea
                                name="correction_reason"
                                rows="3"
                                required
                                placeholder="علت اصلاح اطلاعات خریدار را بنویسید..."
                            ></textarea>

                        </label>

                        <small>
                            اطلاعات حقوقی این سفارش قبلاً ثبت شده است؛
                            برای هر تغییر بعدی، ثبت دلیل الزامی است.
                        </small>

                    <?php endif; ?>


                    <button
                        type="submit"
                        class="hom-button hom-button-secondary"
                        style="margin-top:14px"
                    >
                        ذخیره اطلاعات حقوقی
                    </button>

                </form>


            <?php else : ?>


                <p>
                    نام حقوقی:
                    <strong>
                        <?php
                        echo esc_html(
                            $b2b['legal_name']
                                ?: '—'
                        );
                        ?>
                    </strong>
                </p>

                <p>
                    شناسه ملی:
                    <span dir="ltr">
                        <?php
                        echo esc_html(
                            $b2b['national_id']
                                ?: '—'
                        );
                        ?>
                    </span>
                </p>

                <p>
                    کد اقتصادی:
                    <span dir="ltr">
                        <?php
                        echo esc_html(
                            $b2b['economic_code']
                                ?: '—'
                        );
                        ?>
                    </span>
                </p>

                <p>
                    شماره ثبت:
                    <span dir="ltr">
                        <?php
                        echo esc_html(
                            $b2b['registration_no']
                                ?: '—'
                        );
                        ?>
                    </span>
                </p>

                <p>
                    کدپستی:
                    <span dir="ltr">
                        <?php
                        echo esc_html(
                            $b2b['postcode']
                                ?: '—'
                        );
                        ?>
                    </span>
                </p>

                <p>
                    آدرس:
                    <?php
                    echo esc_html(
                        $b2b['address']
                            ?: '—'
                    );
                    ?>
                </p>


            <?php endif; ?>

        </section>


        <?php
        $assignee =
            HOM_Orders::assignee_data(
                $order
            );
        ?>

        <section
            class="hom-card hom-order-assignee"
            style="margin-bottom:20px"
        >

            <strong>
                مسئول جاری پرونده:
                <?php
                echo esc_html(
                    $assignee['name']
                        ?: 'هنوز تعیین نشده'
                );
                ?>
            </strong>

            <?php if (!empty($assignee['login'])) : ?>

                <span dir="ltr">
                    (@<?php
                    echo esc_html(
                        $assignee['login']
                    );
                    ?>)
                </span>

            <?php endif; ?>

            <p
                class="hom-muted"
                style="margin:8px 0 0"
            >
                مسئول پرونده به‌صورت خودکار بر اساس آخرین
                قیمت‌گذاری یا تأیید پیش‌فاکتور ثبت می‌شود.
            </p>

        </section>


        <?php if ($can_price) : ?>

            <form
                method="post"
                action="<?php echo esc_url(HOM_Router::panel_url()); ?>"
            >

                <input
                    type="hidden"
                    name="hom_action"
                    value="hom_save_preinvoice_prices"
                >

                <input
                    type="hidden"
                    name="order_id"
                    value="<?php echo esc_attr($order->get_id()); ?>"
                >

                <?php
                wp_nonce_field(
                    'hom_save_preinvoice_prices_' .
                    $order->get_id()
                );
                ?>

        <?php endif; ?>


        <div class="hom-table-wrap">

            <table class="hom-products-table">

                <thead>
                    <tr>
                        <th>محصول</th>
                        <th>SKU</th>
                        <th>تعداد</th>
                        <th>قیمت واحد</th>
                        <th>جمع</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                foreach (
                    $order->get_items('line_item')
                    as $item_id => $item
                ) :

                    $product = $item->get_product();

                    $quantity = max(
                        1,
                        (float) $item->get_quantity()
                    );

                    $unit_price =
                        (float) $item->get_total()
                        / $quantity;
                    ?>

                    <tr>

                        <td>
                            <?php echo esc_html($item->get_name()); ?>
                        </td>

                        <td>
                            <?php
                            echo esc_html(
                                $product
                                    ? ($product->get_sku() ?: '—')
                                    : '—'
                            );
                            ?>
                        </td>

                        <td>
                            <?php echo esc_html($quantity); ?>
                        </td>

                        <td>

                            <?php if ($can_price) : ?>

                                <input
                                    type="text"
                                    inputmode="decimal"
                                    name="item_price[<?php echo esc_attr($item_id); ?>]"
                                    value="<?php echo esc_attr($unit_price); ?>"
                                    style="max-width:160px"
                                >

                            <?php else : ?>

                                <?php
                                echo wp_kses_post(
                                    wc_price(
                                        $unit_price,
                                        [
                                            'currency' =>
                                                $order->get_currency(),
                                        ]
                                    )
                                );
                                ?>

                            <?php endif; ?>

                        </td>

                        <td>
                            <?php
                            echo wp_kses_post(
                                $order->get_formatted_line_subtotal(
                                    $item
                                )
                            );
                            ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>


        <?php if ($can_price) : ?>

            <div style="margin-top:16px;max-width:320px">

                <label class="hom-field">

                    <span>
                        هزینه ارسال
                    </span>

                    <input
                        type="text"
                        inputmode="decimal"
                        name="shipping_cost"
                        value="<?php
                        echo esc_attr(
                            HOM_Orders::preinvoice_shipping_cost(
                                $order
                            )
                        );
                        ?>"
                    >

                </label>

                <small>
                    برای پس‌کرایه، مبلغ را صفر بگذارید.
                </small>

            </div>

            <?php
            $already_priced =
                '' !==
                trim(
                    (string)
                    $order->get_meta(
                        '_hom_preinvoice_priced_at',
                        true
                    )
                );
            ?>


            <?php if ($already_priced) : ?>

                <div style="margin-top:16px">

                    <label class="hom-field">

                        <span>
                            دلیل اصلاح قیمت
                        </span>

                        <textarea
                            name="correction_reason"
                            rows="3"
                            required
                            placeholder="علت تغییر قیمت یا هزینه ارسال را بنویسید..."
                        ></textarea>

                    </label>

                    <small>
                        این پیش‌فاکتور قبلاً قیمت‌گذاری شده است؛
                        برای هر اصلاح، ثبت دلیل الزامی است.
                    </small>

                </div>

            <?php endif; ?>


            <p style="margin-top:16px">

                <button
                    type="submit"
                    class="hom-button hom-button-primary"
                >
                    ذخیره قیمت‌ها
                </button>

            </p>

            </form>

        <?php endif; ?>


        <section class="hom-card" style="margin-top:20px">

            <strong>
                مبلغ نهایی:
                <?php
                echo wp_kses_post(
                    $order->get_formatted_order_total()
                );
                ?>
            </strong>


            <?php if ($can_price) : ?>

                <form
                    method="post"
                    action="<?php echo esc_url(HOM_Router::panel_url()); ?>"
                    style="margin-top:16px"
                >

                    <input
                        type="hidden"
                        name="hom_action"
                        value="hom_approve_preinvoice"
                    >

                    <input
                        type="hidden"
                        name="order_id"
                        value="<?php echo esc_attr($order->get_id()); ?>"
                    >

                    <?php
                    wp_nonce_field(
                        'hom_approve_preinvoice_' .
                        $order->get_id()
                    );
                    ?>

                    <button
                        type="submit"
                        class="hom-button hom-button-primary"
                        <?php disabled((float) $order->get_total() <= 0); ?>
                    >
                        تأیید و آماده‌سازی برای پرداخت
                    </button>

                </form>

            <?php elseif (
                'preinv-approved' === $order->get_status()
            ) : ?>

                <p>
                    پیش‌فاکتور تأیید شده و آماده پرداخت مشتری است.
                </p>

            <?php endif; ?>

        </section>

        <?php
        $can_confirm_manual_payment =
            current_user_can(
                HOM_Capabilities::CAP_MANAGE_PREINVOICES
            ) &&
            HOM_Orders::can_confirm_manual_payment(
                $order
            );


        $manual_payment =
            HOM_Orders::manual_payment_data(
                $order
            );


        $payment_notice =
            isset($_GET['notice'])
                ? sanitize_key(
                    wp_unslash(
                        $_GET['notice']
                    )
                )
                : '';
        ?>


        <?php if ('payment-confirmed' === $payment_notice) : ?>

            <div
                class="hom-alert hom-alert-success"
                style="margin-top:20px"
            >
                دریافت کامل وجه با موفقیت تأیید شد و سفارش
                وارد مرحله آماده‌سازی گردید.
            </div>

        <?php elseif ('payment-error' === $payment_notice) : ?>

            <div
                class="hom-alert hom-alert-error"
                style="margin-top:20px"
            >
                تأیید پرداخت انجام نشد. اطلاعات مبلغ و مرجع
                پرداخت را بررسی کنید.
            </div>

        <?php elseif ('payment-corrected' === $payment_notice) : ?>

            <div
                class="hom-alert hom-alert-success"
                style="margin-top:20px"
            >
                اطلاعات پرداخت با موفقیت اصلاح و در سوابق
                تغییرات ثبت شد.
            </div>

        <?php elseif ('payment-correction-error' === $payment_notice) : ?>

            <div
                class="hom-alert hom-alert-error"
                style="margin-top:20px"
            >
                اصلاح اطلاعات پرداخت انجام نشد.
            </div>

        <?php endif; ?>


        <?php if ($can_confirm_manual_payment) : ?>

            <section
                class="hom-card hom-manual-payment-card"
                style="margin-top:20px"
            >

                <h2>
                    تأیید پرداخت دستی
                </h2>

                <p class="hom-muted">
                    فقط پس از مشاهده و تأیید قطعی واریز بانکی،
                    دریافت کامل وجه را ثبت کنید.
                </p>


                <form
                    method="post"
                    action="<?php
                    echo esc_url(
                        HOM_Router::panel_url()
                    );
                    ?>"
                >

                    <input
                        type="hidden"
                        name="hom_action"
                        value="hom_confirm_manual_payment"
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

                    <?php
                    wp_nonce_field(
                        'hom_confirm_manual_payment_' .
                        $order->get_id()
                    );
                    ?>


                    <label class="hom-field">

                        <span>
                            مبلغ واریزشده
                        </span>

                        <input
                            type="text"
                            inputmode="decimal"
                            name="payment_amount"
                            required
                            value="<?php
                            echo esc_attr(
                                wc_format_decimal(
                                    $order->get_total()
                                )
                            );
                            ?>"
                        >

                    </label>

                    <small>
                        باید دقیقاً برابر مبلغ نهایی سفارش باشد.
                    </small>


                    <label
                        class="hom-field"
                        style="margin-top:14px"
                    >

                        <span>
                            شماره پیگیری / مرجع پرداخت
                        </span>

                        <input
                            type="text"
                            name="payment_reference"
                            dir="ltr"
                            required
                        >

                    </label>


                    <label
                        class="hom-field"
                        style="margin-top:14px"
                    >

                        <span>
                            توضیحات پرداخت
                        </span>

                        <textarea
                            name="payment_notes"
                            rows="3"
                            placeholder="در صورت نیاز توضیح تکمیلی بنویسید..."
                        ></textarea>

                    </label>


                    <button
                        type="submit"
                        class="hom-button hom-button-primary"
                        style="margin-top:16px"
                    >
                        تأیید دریافت کامل وجه
                    </button>

                </form>

            </section>


        <?php elseif (
            !empty(
                $manual_payment['confirmed_at']
            )
        ) : ?>

            <?php
            $payment_actor =
                !empty(
                    $manual_payment['confirmed_by']
                )
                    ? get_userdata(
                        $manual_payment['confirmed_by']
                    )
                    : false;
            ?>

            <section
                class="hom-card hom-manual-payment-card"
                style="margin-top:20px"
            >

                <h2>
                    اطلاعات پرداخت
                </h2>

                <p>
                    روش:
                    <strong>
                        کارت‌به‌کارت / واریز دستی
                    </strong>
                </p>

                <p>
                    مبلغ:
                    <strong>
                        <?php
                        echo wp_kses_post(
                            wc_price(
                                (float)
                                $manual_payment['amount'],
                                [
                                    'currency' =>
                                        $order->get_currency(),
                                ]
                            )
                        );
                        ?>
                    </strong>
                </p>

                <p>
                    مرجع پرداخت:
                    <strong dir="ltr">
                        <?php
                        echo esc_html(
                            $manual_payment['reference']
                            ?: '—'
                        );
                        ?>
                    </strong>
                </p>

                <p>
                    تأییدکننده:
                    <strong>
                        <?php
                        echo esc_html(
                            $payment_actor
                                ? (
                                    $payment_actor->display_name
                                    ?: $payment_actor->user_login
                                )
                                : '—'
                        );
                        ?>
                    </strong>
                </p>

                <p>
                    زمان تأیید:
                    <strong>
                        <?php
                        echo esc_html(
                            $manual_payment['confirmed_at']
                            ?: '—'
                        );
                        ?>
                    </strong>
                </p>


                <?php if (!empty($manual_payment['notes'])) : ?>

                    <p>
                        توضیحات:
                        <?php
                        echo esc_html(
                            $manual_payment['notes']
                        );
                        ?>
                    </p>

                <?php endif; ?>


                <?php
                if (
                    current_user_can(
                        HOM_Capabilities::CAP_MANAGE_PREINVOICES
                    ) &&
                    HOM_Orders::can_correct_manual_payment(
                        $order
                    )
                ) :
                    ?>

                    <hr
                        style="
                            margin:20px 0;
                            border:0;
                            border-top:1px solid #e5e7eb
                        "
                    >

                    <h3>
                        اصلاح اطلاعات پرداخت
                    </h3>

                    <p class="hom-muted">
                        مبلغ و وضعیت پرداخت قابل تغییر نیستند.
                        فقط مرجع و توضیحات پرداخت را در صورت
                        ثبت اشتباه اصلاح کنید.
                    </p>


                    <form
                        method="post"
                        action="<?php
                        echo esc_url(
                            HOM_Router::panel_url()
                        );
                        ?>"
                    >

                        <input
                            type="hidden"
                            name="hom_action"
                            value="hom_correct_manual_payment"
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

                        <?php
                        wp_nonce_field(
                            'hom_correct_manual_payment_' .
                            $order->get_id()
                        );
                        ?>


                        <label class="hom-field">

                            <span>
                                شماره پیگیری / مرجع پرداخت
                            </span>

                            <input
                                type="text"
                                name="payment_reference"
                                dir="ltr"
                                required
                                value="<?php
                                echo esc_attr(
                                    $manual_payment[
                                        'reference'
                                    ]
                                );
                                ?>"
                            >

                        </label>


                        <label
                            class="hom-field"
                            style="margin-top:14px"
                        >

                            <span>
                                توضیحات پرداخت
                            </span>

                            <textarea
                                name="payment_notes"
                                rows="3"
                            ><?php
                            echo esc_textarea(
                                $manual_payment[
                                    'notes'
                                ]
                            );
                            ?></textarea>

                        </label>


                        <label
                            class="hom-field"
                            style="margin-top:14px"
                        >

                            <span>
                                دلیل اصلاح
                            </span>

                            <textarea
                                name="correction_reason"
                                rows="3"
                                required
                                placeholder="دلیل اصلاح اطلاعات پرداخت را بنویسید..."
                            ></textarea>

                        </label>


                        <button
                            type="submit"
                            class="hom-button hom-button-secondary"
                            style="margin-top:16px"
                        >
                            ثبت اصلاح اطلاعات پرداخت
                        </button>

                    </form>

                <?php endif; ?>

            </section>

        <?php endif; ?>


        <section
            class="hom-card hom-order-document-actions"
            style="margin-top:20px"
        >

            <h2>
                اسناد و چاپ
            </h2>

            <div
                style="
                    display:flex;
                    flex-wrap:wrap;
                    gap:10px
                "
            >

                <a
                    class="hom-button hom-button-secondary"
                    target="_blank"
                    rel="noopener"
                    href="<?php
                    echo esc_url(
                        HOM_Order_Documents::url(
                            $order->get_id(),
                            'invoice'
                        )
                    );
                    ?>"
                >
                    چاپ فاکتور
                </a>


                <a
                    class="hom-button hom-button-secondary"
                    target="_blank"
                    rel="noopener"
                    href="<?php
                    echo esc_url(
                        HOM_Order_Documents::url(
                            $order->get_id(),
                            'warehouse'
                        )
                    );
                    ?>"
                >
                    برگه انبار بدون قیمت
                </a>


                <a
                    class="hom-button hom-button-secondary"
                    target="_blank"
                    rel="noopener"
                    href="<?php
                    echo esc_url(
                        HOM_Order_Documents::url(
                            $order->get_id(),
                            'shipping'
                        )
                    );
                    ?>"
                >
                    برچسب ارسال
                </a>

            </div>

        </section>


        <?php
        HOM_Order_Fulfillment_View::render(
            $order
        );
        ?>


        <hr
            style="
                margin:34px 0 22px;
                border:0;
                border-top:1px solid #dfe3e8
            "
        >

        <section
            class="hom-card hom-order-tracking-header"
            style="margin-top:0"
        >

            <h2 style="margin-bottom:6px">
                پیگیری و سوابق تغییرات
            </h2>

            <p
                class="hom-muted"
                style="margin:0"
            >
                تمام مراحل، تأییدها، اصلاحات و اقدامات انجام‌شده
                روی این پرونده در این بخش نگهداری می‌شوند.
            </p>

        </section>


        <?php
        $timeline =
            HOM_Orders::timeline(
                $order
            );

        if ($timeline) :
            ?>

            <section
                class="hom-card hom-order-timeline"
                style="margin-top:20px"
            >

                <h2>
                    مراحل سفارش
                </h2>

                <div class="hom-order-timeline__items">

                    <?php foreach ($timeline as $event) : ?>

                        <div class="hom-order-timeline__item">

                            <span class="hom-order-timeline__dot"></span>

                            <div>

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $event['label']
                                    );
                                    ?>
                                </strong>

                                <span>
                                    <?php
                                    echo esc_html(
                                        $event['date']
                                    );
                                    ?>
                                </span>

                                <?php if (!empty($event['description'])) : ?>

                                    <small>
                                        <?php
                                        echo esc_html(
                                            $event['description']
                                        );
                                        ?>
                                    </small>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </section>

        <?php endif; ?>


        <?php
        HOM_Order_Audit::render(
            $order
        );
        ?>


        <?php
    }
}
