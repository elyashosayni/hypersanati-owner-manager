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
                HOM_Capabilities::
                CAP_MANAGE_FULFILLMENT
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


        $method_label =
            (
                !empty(
                    $data['method']
                ) &&
                isset(
                    $methods[
                        $data['method']
                    ]
                )
            )
                ? $methods[
                    $data['method']
                ]
                : 'تعیین نشده';


        $freight_label =
            'prepaid' ===
            $data['freight_payment']
                ? 'پرداخت شده'
                : (
                    'collect' ===
                    $data['freight_payment']
                        ? 'پس‌کرایه'
                        : 'تعیین نشده'
                );


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
            class="
                hom-order-operation-card
                hom-order-fulfillment-card
            "
        >

            <div class="hom-order-operation-card__head">

                <div>

                    <span class="hom-order-operation-card__eyebrow">
                        عملیات سفارش
                    </span>

                    <h2>
                        ارسال و تحویل
                    </h2>

                </div>


                <span
                    class="
                        hom-order-operation-status
                        <?php
                        echo 'completed' === $status
                            ? 'is-complete'
                            : 'is-pending';
                        ?>
                    "
                >
                    <?php
                    echo esc_html(
                        HOM_Orders::status_label(
                            $status
                        )
                    );
                    ?>
                </span>

            </div>


            <div class="hom-order-operation-summary">

                <div>
                    <span>روش ارسال</span>

                    <strong>
                        <?php
                        echo esc_html(
                            $method_label
                        );
                        ?>
                    </strong>
                </div>


                <div>
                    <span>شرکت / باربری</span>

                    <strong>
                        <?php
                        echo esc_html(
                            $data['company']
                                ?: '—'
                        );
                        ?>
                    </strong>
                </div>


                <div>
                    <span>کد رهگیری</span>

                    <strong dir="ltr">
                        <?php
                        echo esc_html(
                            $data[
                                'tracking_code'
                            ]
                            ?: '—'
                        );
                        ?>
                    </strong>
                </div>


                <div>
                    <span>کرایه</span>

                    <strong>
                        <?php
                        echo esc_html(
                            $freight_label
                        );
                        ?>
                    </strong>
                </div>

            </div>


            <details class="hom-order-action-disclosure">

                <summary>

                    <span>
                        <?php
                        echo esc_html(
                            $shipping_already_saved
                                ? 'مشاهده / اصلاح اطلاعات ارسال'
                                : 'ثبت اطلاعات ارسال'
                        );
                        ?>
                    </span>

                    <small>
                        فقط هنگام انجام عملیات باز کنید
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


                        <div class="hom-order-form-grid">

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
                                            echo esc_attr(
                                                $key
                                            );
                                            ?>"
                                            <?php
                                            selected(
                                                $data[
                                                    'method'
                                                ],
                                                $key
                                            );
                                            ?>
                                        >
                                            <?php
                                            echo esc_html(
                                                $label
                                            );
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
                                        $data[
                                            'company'
                                        ]
                                    );
                                    ?>"
                                >

                            </label>


                            <label class="hom-field">

                                <span>
                                    کد رهگیری / بارنامه
                                </span>

                                <input
                                    type="text"
                                    name="tracking_code"
                                    dir="ltr"
                                    value="<?php
                                    echo esc_attr(
                                        $data[
                                            'tracking_code'
                                        ]
                                    );
                                    ?>"
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
                                            $data[
                                                'freight_payment'
                                            ],
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
                                            $data[
                                                'freight_payment'
                                            ],
                                            'collect'
                                        );
                                        ?>
                                    >
                                        پس‌کرایه
                                    </option>

                                </select>

                            </label>

                        </div>


                        <label
                            class="
                                hom-field
                                hom-order-field-wide
                            "
                        >

                            <span>
                                توضیحات ارسال
                            </span>

                            <textarea
                                name="shipping_notes"
                                rows="2"
                            ><?php
                            echo esc_textarea(
                                $data['notes']
                            );
                            ?></textarea>

                        </label>


                        <?php
                        if (
                            $shipping_already_saved
                        ) :
                            ?>

                            <label
                                class="
                                    hom-field
                                    hom-order-field-wide
                                "
                            >

                                <span>
                                    دلیل اصلاح
                                </span>

                                <input
                                    type="text"
                                    name="correction_reason"
                                    placeholder="فقط اگر اطلاعات بالا تغییر کرده است"
                                >

                            </label>

                        <?php endif; ?>


                        <div class="hom-order-action-buttons">

                            <button
                                type="submit"
                                name="fulfillment_action"
                                value="save"
                                class="
                                    hom-button
                                    hom-button-secondary
                                "
                            >
                                ذخیره اطلاعات
                            </button>


                            <?php if ('processing' === $status) : ?>

                                <button
                                    type="submit"
                                    name="fulfillment_action"
                                    value="ready"
                                    class="
                                        hom-button
                                        hom-button-primary
                                    "
                                >
                                    آماده ارسال
                                </button>

                            <?php endif; ?>


                            <?php if ('hom-ready' === $status) : ?>

                                <button
                                    type="submit"
                                    name="fulfillment_action"
                                    value="shipped"
                                    class="
                                        hom-button
                                        hom-button-primary
                                    "
                                >
                                    ثبت ارسال
                                </button>

                            <?php endif; ?>


                            <?php if ('hom-shipped' === $status) : ?>

                                <button
                                    type="submit"
                                    name="fulfillment_action"
                                    value="delivered"
                                    class="
                                        hom-button
                                        hom-button-primary
                                    "
                                >
                                    ثبت تحویل
                                </button>

                            <?php endif; ?>

                        </div>

                    </form>

                </div>

            </details>

        </section>

        <?php
    }
}
