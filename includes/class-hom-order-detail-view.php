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
        $assignee =
            HOM_Orders::assignee_data(
                $order
            );

        $sales_users =
            HOM_Orders::sales_users();
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
                    (@<?php echo esc_html($assignee['login']); ?>)
                </span>
            <?php endif; ?>


            <?php if ($sales_users) : ?>

                <form
                    method="post"
                    action="<?php
                    echo esc_url(
                        admin_url('admin-post.php')
                    );
                    ?>"
                    style="
                        display:flex;
                        flex-wrap:wrap;
                        gap:10px;
                        align-items:end;
                        margin-top:14px
                    "
                >

                    <input
                        type="hidden"
                        name="action"
                        value="hom_assign_order"
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
                        'hom_assign_order_' .
                        $order->get_id()
                    );
                    ?>

                    <label class="hom-field">

                        <span>
                            واگذاری به مسئول فروش
                        </span>

                        <select
                            name="assignee_user_id"
                            required
                        >

                            <?php foreach ($sales_users as $sales_user) : ?>

                                <option
                                    value="<?php
                                    echo esc_attr(
                                        $sales_user['id']
                                    );
                                    ?>"
                                    <?php
                                    selected(
                                        absint($assignee['id']),
                                        absint($sales_user['id'])
                                    );
                                    ?>
                                >
                                    <?php
                                    echo esc_html(
                                        $sales_user['name']
                                    );
                                    ?>
                                    (@<?php
                                    echo esc_html(
                                        $sales_user['login']
                                    );
                                    ?>)
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </label>

                    <button
                        type="submit"
                        class="hom-button hom-button-secondary"
                    >
                        ثبت مسئول پرونده
                    </button>

                </form>

            <?php endif; ?>

        </section>


        <?php if ($can_price) : ?>

            <form
                method="post"
                action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
            >

                <input
                    type="hidden"
                    name="action"
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
                    action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                    style="margin-top:16px"
                >

                    <input
                        type="hidden"
                        name="action"
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
        HOM_Order_Audit::render(
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

            <section
                class="hom-card hom-order-timeline"
                style="margin-top:20px"
            >

                <h2>
                    پیگیری سفارش
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
        HOM_Order_Fulfillment_View::render(
            $order
        );
        ?>

        <?php
    }
}
