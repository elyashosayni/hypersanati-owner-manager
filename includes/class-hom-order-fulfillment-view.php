<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Order_Fulfillment_View {

    public static function render(
        $order
    ) {

        if (
            !($order instanceof WC_Order) ||
            !current_user_can(
                HOM_Capabilities::CAP_MANAGE_FULFILLMENT
            )
        ) {
            return;
        }


        $status =
            $order->get_status();


        if (
            !in_array(
                $status,
                [
                    'processing',
                    'hom-ready',
                    'hom-shipped',
                    'completed',
                ],
                true
            )
        ) {
            return;
        }


        $data =
            HOM_Orders::fulfillment_data(
                $order
            );


        $methods =
            HOM_Orders::shipping_methods();


        $shipping_already_saved =
            '' !==
            trim(
                (string)
                $order->get_meta(
                    '_hom_shipping_updated_at',
                    true
                )
            );


        if (!$shipping_already_saved) {

            foreach ($data as $value) {

                if (
                    '' !==
                    trim(
                        (string)
                        $value
                    )
                ) {

                    $shipping_already_saved =
                        true;

                    break;
                }
            }
        }

        ?>

        <section
            class="hom-card"
            style="margin-top:20px"
        >

            <h2>
                ارسال و تحویل سفارش
            </h2>

            <p>
                وضعیت فعلی:
                <strong>
                    <?php
                    echo esc_html(
                        HOM_Orders::status_label(
                            $status
                        )
                    );
                    ?>
                </strong>
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
                    value="hom_save_order_fulfillment"
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
                    'hom_save_order_fulfillment_' .
                    $order->get_id()
                );
                ?>


                <label class="hom-field">

                    <span>
                        روش ارسال
                    </span>

                    <select
                        name="shipping_method"
                    >

                        <option value="">
                            انتخاب کنید
                        </option>

                        <?php
                        foreach (
                            $methods
                            as $key => $label
                        ) :
                            ?>

                            <option
                                value="<?php
                                echo esc_attr($key);
                                ?>"
                                <?php
                                selected(
                                    $data['method'],
                                    $key
                                );
                                ?>
                            >
                                <?php
                                echo esc_html($label);
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </label>


                <label class="hom-field">

                    <span>
                        شرکت / باربری / شعبه
                    </span>

                    <input
                        type="text"
                        name="shipping_company"
                        value="<?php
                        echo esc_attr(
                            $data['company']
                        );
                        ?>"
                    >

                </label>


                <label class="hom-field">

                    <span>
                        کد رهگیری / شماره بارنامه
                    </span>

                    <input
                        type="text"
                        name="tracking_code"
                        value="<?php
                        echo esc_attr(
                            $data['tracking_code']
                        );
                        ?>"
                        dir="ltr"
                    >

                </label>


                <label class="hom-field">

                    <span>
                        وضعیت کرایه
                    </span>

                    <select
                        name="freight_payment"
                    >

                        <option value="">
                            تعیین نشده
                        </option>

                        <option
                            value="prepaid"
                            <?php
                            selected(
                                $data['freight_payment'],
                                'prepaid'
                            );
                            ?>
                        >
                            پرداخت شده
                        </option>

                        <option
                            value="collect"
                            <?php
                            selected(
                                $data['freight_payment'],
                                'collect'
                            );
                            ?>
                        >
                            پس‌کرایه
                        </option>

                    </select>

                </label>


                <label class="hom-field">

                    <span>
                        توضیحات ارسال
                    </span>

                    <textarea
                        name="shipping_notes"
                        rows="3"
                    ><?php
                    echo esc_textarea(
                        $data['notes']
                    );
                    ?></textarea>

                </label>


                <?php if ($shipping_already_saved) : ?>

                    <label
                        class="hom-field"
                        style="margin-top:14px"
                    >

                        <span>
                            دلیل اصلاح اطلاعات ارسال
                        </span>

                        <textarea
                            name="correction_reason"
                            rows="3"
                            placeholder="اگر اطلاعات ارسال بالا را تغییر داده‌اید، دلیل اصلاح را بنویسید..."
                        ></textarea>

                    </label>

                    <small>
                        در صورت تغییر هرکدام از اطلاعات ارسال ثبت‌شده،
                        وارد کردن دلیل اصلاح الزامی است.
                        برای تغییر صرفاً مرحله سفارش، این فیلد لازم نیست.
                    </small>

                <?php endif; ?>


                <div
                    style="
                        display:flex;
                        flex-wrap:wrap;
                        gap:10px;
                        margin-top:16px
                    "
                >

                    <button
                        type="submit"
                        name="fulfillment_action"
                        value="save"
                        class="hom-button hom-button-secondary"
                    >
                        ذخیره اطلاعات ارسال
                    </button>


                    <?php if ('processing' === $status) : ?>

                        <button
                            type="submit"
                            name="fulfillment_action"
                            value="ready"
                            class="hom-button hom-button-primary"
                        >
                            سفارش آماده ارسال است
                        </button>

                    <?php endif; ?>


                    <?php if ('hom-ready' === $status) : ?>

                        <button
                            type="submit"
                            name="fulfillment_action"
                            value="shipped"
                            class="hom-button hom-button-primary"
                        >
                            ثبت ارسال سفارش
                        </button>

                    <?php endif; ?>


                    <?php if ('hom-shipped' === $status) : ?>

                        <button
                            type="submit"
                            name="fulfillment_action"
                            value="delivered"
                            class="hom-button hom-button-primary"
                        >
                            ثبت تحویل به مشتری
                        </button>

                    <?php endif; ?>

                </div>

            </form>

        </section>

        <?php
    }
}
