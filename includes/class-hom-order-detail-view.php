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

        $contact =
            HOM_Orders::customer_contact_data(
                $order
            );


        $b2b =
            HOM_Orders::b2b_customer_data(
                $order
            );


        $assignee =
            HOM_Orders::assignee_data(
                $order
            );


        $can_manage_customer =
            current_user_can(
                HOM_Capabilities::CAP_MANAGE_PREINVOICES
            );


        $is_preinvoice =
            'yes' ===
            $order->get_meta(
                '_hsb_is_preinvoice',
                true
            );


        $status_label =
            HOM_Orders::status_label(
                $order->get_status()
            );


        $customer_name =
            trim(
                (string)
                $contact['display_name']
            );


        if ('' === $customer_name) {

            $customer_name =
                trim(
                    (string)
                    $b2b['legal_name']
                );
        }


        $b2b_already_saved =
            '' !==
            trim(
                (string)
                $order->get_meta(
                    '_hom_b2b_updated_at',
                    true
                )
            );


        $created =
            $order->get_date_created();


        $created_label =
            $created
                ? wp_date(
                    'Y/m/d H:i',
                    $created->getTimestamp()
                )
                : '—';


        $help_url =
            add_query_arg(
                'view',
                'help-customers',
                HOM_Router::panel_url()
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


        <div class="hom-order-detail-header">

            <div class="hom-order-detail-header__nav">

                <a
                    href="<?php
                    echo esc_url(
                        $back_url
                    );
                    ?>"
                    class="hom-order-back-link"
                >
                    <span aria-hidden="true">←</span>
                    بازگشت به مدیریت و پیگیری مشتریان
                </a>


                <a
                    href="<?php
                    echo esc_url(
                        $help_url
                    );
                    ?>"
                    class="hom-order-help-link"
                >
                    <span aria-hidden="true">👁</span>
                    راهنمای این بخش
                </a>

            </div>


            <div class="hom-order-detail-header__main">

                <div>

                    <div class="hom-order-detail-kicker">
                        <?php
                        echo esc_html(
                            $is_preinvoice
                                ? 'پرونده پیش‌فاکتور'
                                : 'پرونده سفارش'
                        );
                        ?>
                    </div>

                    <div class="hom-order-detail-title-row">

                        <h1>
                            <?php
                            echo esc_html(
                                $is_preinvoice
                                    ? 'پیش‌فاکتور'
                                    : 'سفارش'
                            );
                            ?>

                            <span dir="ltr">
                                #<?php
                                echo esc_html(
                                    $order
                                        ->get_order_number()
                                );
                                ?>
                            </span>
                        </h1>


                        <span class="hom-order-detail-status">
                            <?php
                            echo esc_html(
                                $status_label
                            );
                            ?>
                        </span>

                    </div>

                </div>


                <div class="hom-order-detail-total">

                    <span>
                        مبلغ نهایی
                    </span>

                    <strong>
                        <?php
                        echo wp_kses_post(
                            $order
                                ->get_formatted_order_total()
                        );
                        ?>
                    </strong>

                </div>

            </div>


            <div class="hom-order-summary-grid">

                <div class="hom-order-summary-item">

                    <span class="hom-order-summary-item__label">
                        مشتری
                    </span>

                    <strong>
                        <?php if ($customer_name) : ?>

                            <?php
                            echo esc_html(
                                $customer_name
                            );
                            ?>

                        <?php else : ?>

                            <span class="hom-order-missing">
                                تکمیل نشده
                            </span>

                        <?php endif; ?>
                    </strong>

                </div>


                <div class="hom-order-summary-item">

                    <span class="hom-order-summary-item__label">
                        شماره تماس
                    </span>

                    <strong dir="ltr">

                        <?php if (!empty($contact['phone'])) : ?>

                            <?php
                            echo esc_html(
                                $contact['phone']
                            );
                            ?>

                        <?php else : ?>

                            <span class="hom-order-missing">
                                تکمیل نشده
                            </span>

                        <?php endif; ?>

                    </strong>

                </div>


                <div class="hom-order-summary-item">

                    <span class="hom-order-summary-item__label">
                        مسئول پرونده
                    </span>

                    <strong>
                        <?php
                        echo esc_html(
                            $assignee['name']
                                ?: 'تعیین نشده'
                        );
                        ?>
                    </strong>

                </div>


                <div class="hom-order-summary-item">

                    <span class="hom-order-summary-item__label">
                        تاریخ ثبت
                    </span>

                    <strong dir="ltr">
                        <?php
                        echo esc_html(
                            $created_label
                        );
                        ?>
                    </strong>

                </div>

            </div>


            <div class="hom-order-quick-actions">

                <span class="hom-order-quick-actions__label">
                    دسترسی سریع:
                </span>


                <a
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
                    🧾 فاکتور
                </a>


                <a
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
                    📦 برگه انبار
                </a>


                <a
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
                    🏷️ برچسب ارسال
                </a>

            </div>

        </div>



        <?php if ('b2b-saved' === $b2b_notice) : ?>

            <div class="hom-alert hom-alert-success">
                اطلاعات خریدار با موفقیت ذخیره شد.
            </div>

        <?php elseif ('b2b-error' === $b2b_notice) : ?>

            <div class="hom-alert hom-alert-error">
                ذخیره اطلاعات خریدار انجام نشد.
            </div>

        <?php endif; ?>



        <section class="hom-order-customer-grid">

            <article class="hom-order-info-card">

                <div class="hom-order-info-card__head">

                    <span class="hom-order-info-card__icon">
                        👤
                    </span>

                    <div>
                        <span>
                            مشخصات پایه
                        </span>

                        <h2>
                            اطلاعات مشتری
                        </h2>
                    </div>

                </div>


                <dl class="hom-order-data-list">

                    <div>
                        <dt>نام مشتری</dt>

                        <dd>
                            <?php if ($customer_name) : ?>

                                <?php
                                echo esc_html(
                                    $customer_name
                                );
                                ?>

                            <?php else : ?>

                                <span class="hom-order-missing">
                                    تکمیل نشده
                                </span>

                            <?php endif; ?>
                        </dd>
                    </div>


                    <div>
                        <dt>شماره تماس</dt>

                        <dd dir="ltr">

                            <?php if (!empty($contact['phone'])) : ?>

                                <?php
                                echo esc_html(
                                    $contact['phone']
                                );
                                ?>

                            <?php else : ?>

                                <span class="hom-order-missing">
                                    تکمیل نشده
                                </span>

                            <?php endif; ?>

                        </dd>
                    </div>


                    <div>
                        <dt>ایمیل</dt>

                        <dd dir="ltr">

                            <?php if (!empty($contact['email'])) : ?>

                                <?php
                                echo esc_html(
                                    $contact['email']
                                );
                                ?>

                            <?php else : ?>

                                <span class="hom-order-missing">
                                    تکمیل نشده
                                </span>

                            <?php endif; ?>

                        </dd>
                    </div>


                    <div>
                        <dt>شناسه مشتری</dt>

                        <dd dir="ltr">
                            <?php
                            echo $contact['customer_id']
                                ? esc_html(
                                    '#' .
                                    $contact['customer_id']
                                )
                                : '—';
                            ?>
                        </dd>
                    </div>

                </dl>

            </article>



            <article class="hom-order-info-card">

                <div class="hom-order-info-card__head">

                    <span class="hom-order-info-card__icon">
                        🏢
                    </span>

                    <div>
                        <span>
                            اطلاعات فاکتور
                        </span>

                        <h2>
                            اطلاعات حقوقی خریدار
                        </h2>
                    </div>

                </div>


                <dl class="hom-order-data-list hom-order-data-list--legal">

                    <div>
                        <dt>نام حقوقی / شرکت</dt>
                        <dd>
                            <?php
                            echo $b2b['legal_name']
                                ? esc_html(
                                    $b2b['legal_name']
                                )
                                : '<span class="hom-order-missing">تکمیل نشده</span>';
                            ?>
                        </dd>
                    </div>


                    <div>
                        <dt>شناسه ملی</dt>
                        <dd dir="ltr">
                            <?php
                            echo $b2b['national_id']
                                ? esc_html(
                                    $b2b['national_id']
                                )
                                : '<span class="hom-order-missing">تکمیل نشده</span>';
                            ?>
                        </dd>
                    </div>


                    <div>
                        <dt>کد اقتصادی</dt>
                        <dd dir="ltr">
                            <?php
                            echo $b2b['economic_code']
                                ? esc_html(
                                    $b2b['economic_code']
                                )
                                : '<span class="hom-order-missing">تکمیل نشده</span>';
                            ?>
                        </dd>
                    </div>


                    <div>
                        <dt>شماره ثبت</dt>
                        <dd dir="ltr">
                            <?php
                            echo $b2b['registration_no']
                                ? esc_html(
                                    $b2b['registration_no']
                                )
                                : '<span class="hom-order-missing">تکمیل نشده</span>';
                            ?>
                        </dd>
                    </div>


                    <div>
                        <dt>کدپستی</dt>
                        <dd dir="ltr">
                            <?php
                            echo $b2b['postcode']
                                ? esc_html(
                                    $b2b['postcode']
                                )
                                : '<span class="hom-order-missing">تکمیل نشده</span>';
                            ?>
                        </dd>
                    </div>


                    <div class="is-wide">
                        <dt>آدرس فاکتور</dt>
                        <dd>
                            <?php
                            echo $b2b['address']
                                ? esc_html(
                                    $b2b['address']
                                )
                                : '<span class="hom-order-missing">تکمیل نشده</span>';
                            ?>
                        </dd>
                    </div>

                </dl>


                <?php if ($can_manage_customer) : ?>

                    <details
                        class="hom-order-edit-disclosure"
                        <?php
                        echo $b2b_already_saved
                            ? ''
                            : 'open';
                        ?>
                    >

                        <summary>

                            <span aria-hidden="true">
                                ✎
                            </span>

                            <?php
                            echo esc_html(
                                $b2b_already_saved
                                    ? 'ویرایش اطلاعات حقوقی'
                                    : 'تکمیل اطلاعات حقوقی'
                            );
                            ?>

                        </summary>


                        <div class="hom-order-edit-disclosure__body">

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


                                <div class="hom-order-form-grid">

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


                                <label class="hom-field hom-order-field-wide">

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


                                <?php if ($b2b_already_saved) : ?>

                                    <div class="hom-order-correction-box">

                                        <label class="hom-field">

                                            <span>
                                                دلیل اصلاح
                                            </span>

                                            <textarea
                                                name="correction_reason"
                                                rows="2"
                                                placeholder="اگر اطلاعاتی را تغییر داده‌اید، دلیل اصلاح را کوتاه و واضح بنویسید."
                                            ></textarea>

                                        </label>

                                        <small>
                                            فقط در صورت تغییر اطلاعات
                                            ثبت‌شده، دلیل اصلاح لازم است.
                                        </small>

                                    </div>

                                <?php endif; ?>


                                <div class="hom-order-form-actions">

                                    <button
                                        type="submit"
                                        class="hom-button hom-button-primary"
                                    >
                                        ذخیره اطلاعات خریدار
                                    </button>

                                </div>

                            </form>

                        </div>

                    </details>

                <?php endif; ?>

            </article>

        </section>


        <?php
        $order_item_count =
            count(
                $order->get_items(
                    'line_item'
                )
            );


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


        <details class="hom-order-accordion hom-order-items-accordion">

            <summary>

                <span class="hom-order-accordion__identity">

                    <span class="hom-order-accordion__icon">
                        🧾
                    </span>

                    <span>
                        <strong>
                            اقلام و قیمت‌گذاری
                        </strong>

                        <small>
                            <?php
                            echo esc_html(
                                $order_item_count
                            );
                            ?>
                            قلم
                        </small>
                    </span>

                </span>


                <span class="hom-order-accordion__summary-value">

                    <?php
                    echo wp_kses_post(
                        $order
                            ->get_formatted_order_total()
                    );
                    ?>

                </span>

            </summary>


            <div class="hom-order-accordion__body">


                <?php if ($can_price) : ?>

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
                            value="hom_save_preinvoice_prices"
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
                            'hom_save_preinvoice_prices_' .
                            $order->get_id()
                        );
                        ?>

                <?php endif; ?>


                <div class="hom-order-items-table-wrap">

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
                            $order->get_items(
                                'line_item'
                            )
                            as $item_id => $item
                        ) :

                            $product =
                                $item->get_product();

                            $quantity =
                                max(
                                    1,
                                    (float)
                                    $item->get_quantity()
                                );

                            $unit_price =
                                (float)
                                $item->get_total()
                                /
                                $quantity;
                            ?>

                            <tr>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $item->get_name()
                                    );
                                    ?>
                                </td>

                                <td dir="ltr">
                                    <?php
                                    echo esc_html(
                                        $product
                                            ? (
                                                $product->get_sku()
                                                ?: '—'
                                            )
                                            : '—'
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $quantity
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php if ($can_price) : ?>

                                        <input
                                            class="hom-order-price-input"
                                            type="text"
                                            inputmode="decimal"
                                            name="item_price[<?php
                                            echo esc_attr(
                                                $item_id
                                            );
                                            ?>]"
                                            value="<?php
                                            echo esc_attr(
                                                $unit_price
                                            );
                                            ?>"
                                        >

                                    <?php else : ?>

                                        <?php
                                        echo wp_kses_post(
                                            wc_price(
                                                $unit_price,
                                                [
                                                    'currency' =>
                                                        $order
                                                            ->get_currency(),
                                                ]
                                            )
                                        );
                                        ?>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?php
                                    echo wp_kses_post(
                                        $order
                                            ->get_formatted_line_subtotal(
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

                    <div class="hom-order-pricing-footer">

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
                                    HOM_Orders::
                                    preinvoice_shipping_cost(
                                        $order
                                    )
                                );
                                ?>"
                            >

                            <small>
                                برای پس‌کرایه مبلغ را صفر بگذارید.
                            </small>

                        </label>


                        <?php if ($already_priced) : ?>

                            <label class="hom-field">

                                <span>
                                    دلیل اصلاح قیمت
                                </span>

                                <textarea
                                    name="correction_reason"
                                    rows="2"
                                    required
                                    placeholder="دلیل اصلاح را کوتاه بنویسید..."
                                ></textarea>

                            </label>

                        <?php endif; ?>


                        <button
                            type="submit"
                            class="hom-button hom-button-primary"
                        >
                            ذخیره قیمت‌ها
                        </button>

                    </div>

                    </form>


                    <form
                        method="post"
                        action="<?php
                        echo esc_url(
                            HOM_Router::panel_url()
                        );
                        ?>"
                        class="hom-order-approve-form"
                    >

                        <input
                            type="hidden"
                            name="hom_action"
                            value="hom_approve_preinvoice"
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
                            'hom_approve_preinvoice_' .
                            $order->get_id()
                        );
                        ?>

                        <button
                            type="submit"
                            class="hom-button hom-button-primary"
                            <?php
                            disabled(
                                (float)
                                $order->get_total()
                                <= 0
                            );
                            ?>
                        >
                            تأیید و آماده‌سازی برای پرداخت
                        </button>

                    </form>

                <?php endif; ?>


            </div>

        </details>


        <?php
        $can_confirm_manual_payment =
            current_user_can(
                HOM_Capabilities::
                CAP_MANAGE_PREINVOICES
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


        $payment_confirmed =
            !empty(
                $manual_payment['confirmed_at']
            );


        $payment_actor =
            (
                $payment_confirmed &&
                !empty(
                    $manual_payment['confirmed_by']
                )
            )
                ? get_userdata(
                    $manual_payment['confirmed_by']
                )
                : false;


        $can_correct_payment =
            $payment_confirmed &&
            current_user_can(
                HOM_Capabilities::
                CAP_MANAGE_PREINVOICES
            ) &&
            HOM_Orders::can_correct_manual_payment(
                $order
            );
        ?>


        <?php if ('payment-confirmed' === $payment_notice) : ?>

            <div class="hom-alert hom-alert-success">
                دریافت کامل وجه با موفقیت تأیید شد.
            </div>

        <?php elseif ('payment-error' === $payment_notice) : ?>

            <div class="hom-alert hom-alert-error">
                ثبت پرداخت انجام نشد؛
                مبلغ و مرجع پرداخت را بررسی کنید.
            </div>

        <?php elseif ('payment-corrected' === $payment_notice) : ?>

            <div class="hom-alert hom-alert-success">
                اطلاعات پرداخت اصلاح شد.
            </div>

        <?php elseif (
            'payment-correction-error'
            ===
            $payment_notice
        ) : ?>

            <div class="hom-alert hom-alert-error">
                اصلاح اطلاعات پرداخت انجام نشد.
            </div>

        <?php endif; ?>


        <section
            class="
                hom-order-operation-card
                hom-order-payment-card
            "
        >

            <div class="hom-order-operation-card__head">

                <div>

                    <span class="hom-order-operation-card__eyebrow">
                        امور مالی
                    </span>

                    <h2>
                        پرداخت
                    </h2>

                </div>


                <span
                    class="
                        hom-order-operation-status
                        <?php
                        echo $payment_confirmed
                            ? 'is-complete'
                            : 'is-pending';
                        ?>
                    "
                >
                    <?php
                    echo esc_html(
                        $payment_confirmed
                            ? 'تأیید شده'
                            : 'در انتظار ثبت'
                    );
                    ?>
                </span>

            </div>


            <div class="hom-order-operation-summary">

                <div>
                    <span>مبلغ</span>

                    <strong>
                        <?php
                        echo wp_kses_post(
                            wc_price(
                                $payment_confirmed
                                    ? (float)
                                        $manual_payment[
                                            'amount'
                                        ]
                                    : (float)
                                        $order
                                            ->get_total(),
                                [
                                    'currency' =>
                                        $order
                                            ->get_currency(),
                                ]
                            )
                        );
                        ?>
                    </strong>
                </div>


                <div>
                    <span>مرجع پرداخت</span>

                    <strong dir="ltr">
                        <?php
                        echo esc_html(
                            $payment_confirmed
                                ? (
                                    $manual_payment[
                                        'reference'
                                    ]
                                    ?: '—'
                                )
                                : '—'
                        );
                        ?>
                    </strong>
                </div>


                <div>
                    <span>ثبت‌کننده</span>

                    <strong>
                        <?php
                        echo esc_html(
                            $payment_actor
                                ? (
                                    $payment_actor
                                        ->display_name
                                    ?: $payment_actor
                                        ->user_login
                                )
                                : '—'
                        );
                        ?>
                    </strong>
                </div>


                <div>
                    <span>زمان ثبت</span>

                    <strong dir="ltr">
                        <?php
                        echo esc_html(
                            $payment_confirmed
                                ? (
                                    $manual_payment[
                                        'confirmed_at'
                                    ]
                                    ?: '—'
                                )
                                : '—'
                        );
                        ?>
                    </strong>
                </div>

            </div>


            <?php if ($can_confirm_manual_payment) : ?>

                <details class="hom-order-action-disclosure">

                    <summary>
                        <span>
                            ثبت و تأیید پرداخت دستی
                        </span>

                        <small>
                            فقط هنگام ثبت واریز باز کنید
                        </small>
                    </summary>


                    <div class="hom-order-action-disclosure__body">

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


                            <div class="hom-order-form-grid">

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
                                                $order
                                                    ->get_total()
                                            )
                                        );
                                        ?>"
                                    >

                                </label>


                                <label class="hom-field">

                                    <span>
                                        شماره پیگیری / مرجع
                                    </span>

                                    <input
                                        type="text"
                                        name="payment_reference"
                                        dir="ltr"
                                        required
                                    >

                                </label>

                            </div>


                            <label
                                class="
                                    hom-field
                                    hom-order-field-wide
                                "
                            >

                                <span>
                                    توضیحات پرداخت
                                </span>

                                <textarea
                                    name="payment_notes"
                                    rows="2"
                                    placeholder="اختیاری"
                                ></textarea>

                            </label>


                            <div class="hom-order-form-actions">

                                <button
                                    type="submit"
                                    class="
                                        hom-button
                                        hom-button-primary
                                    "
                                >
                                    تأیید دریافت کامل وجه
                                </button>

                            </div>

                        </form>

                    </div>

                </details>


            <?php elseif ($can_correct_payment) : ?>


                <details class="hom-order-action-disclosure">

                    <summary>
                        <span>
                            اصلاح اطلاعات پرداخت
                        </span>

                        <small>
                            فقط در صورت ثبت اشتباه
                        </small>
                    </summary>


                    <div class="hom-order-action-disclosure__body">

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


                            <div class="hom-order-form-grid">

                                <label class="hom-field">

                                    <span>
                                        شماره پیگیری / مرجع
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


                                <label class="hom-field">

                                    <span>
                                        دلیل اصلاح
                                    </span>

                                    <input
                                        type="text"
                                        name="correction_reason"
                                        required
                                        placeholder="مثلاً اصلاح شماره پیگیری"
                                    >

                                </label>

                            </div>


                            <label
                                class="
                                    hom-field
                                    hom-order-field-wide
                                "
                            >

                                <span>
                                    توضیحات پرداخت
                                </span>

                                <textarea
                                    name="payment_notes"
                                    rows="2"
                                ><?php
                                echo esc_textarea(
                                    $manual_payment[
                                        'notes'
                                    ]
                                );
                                ?></textarea>

                            </label>


                            <div class="hom-order-form-actions">

                                <button
                                    type="submit"
                                    class="
                                        hom-button
                                        hom-button-secondary
                                    "
                                >
                                    ثبت اصلاح
                                </button>

                            </div>

                        </form>

                    </div>

                </details>

            <?php endif; ?>

        </section>


        <?php
        HOM_Order_Fulfillment_View::render(
            $order
        );
        ?>


        <?php
        $timeline =
            HOM_Orders::timeline(
                $order
            );


        if ($timeline) :
            ?>

            <details
                class="
                    hom-order-accordion
                    hom-order-timeline-disclosure
                "
            >

                <summary>

                    <span class="hom-order-accordion__identity">

                        <span class="hom-order-accordion__icon">
                            ◷
                        </span>

                        <span>
                            <strong>
                                مراحل سفارش
                            </strong>

                            <small>
                                مسیر انجام این پرونده
                            </small>
                        </span>

                    </span>


                    <span class="hom-order-accordion__count">
                        <?php
                        echo esc_html(
                            count(
                                $timeline
                            )
                        );
                        ?>
                        مرحله
                    </span>

                </summary>


                <div class="hom-order-accordion__body">

                    <div class="hom-order-timeline__items">

                        <?php
                        foreach (
                            $timeline
                            as $event
                        ) :
                            ?>

                            <div class="hom-order-timeline__item">

                                <span
                                    class="hom-order-timeline__dot"
                                ></span>

                                <div>

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            $event[
                                                'label'
                                            ]
                                        );
                                        ?>
                                    </strong>

                                    <span>
                                        <?php
                                        echo esc_html(
                                            $event[
                                                'date'
                                            ]
                                        );
                                        ?>
                                    </span>

                                    <?php
                                    if (
                                        !empty(
                                            $event[
                                                'description'
                                            ]
                                        )
                                    ) :
                                        ?>

                                        <small>
                                            <?php
                                            echo esc_html(
                                                $event[
                                                    'description'
                                                ]
                                            );
                                            ?>
                                        </small>

                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            </details>

        <?php endif; ?>


        <?php
        HOM_Order_Audit::render(
            $order
        );
        ?>


        <?php
    }
}
