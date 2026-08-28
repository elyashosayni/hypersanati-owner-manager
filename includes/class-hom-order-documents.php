<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Order_Documents {


    private const DOCUMENT_LOGO_FILENAME =
        'logo-for-invoice';


    public static function register() {

        add_action(
            'admin_post_hom_print_order_document',
            [self::class, 'handle_print']
        );
    }


    public static function url(
        $order_id,
        $document
    ) {

        $order_id =
            absint($order_id);

        $document =
            sanitize_key(
                (string) $document
            );


        $url =
            add_query_arg(
                [
                    'hom_action' =>
                        'hom_print_order_document',

                    'order_id' =>
                        $order_id,

                    'document' =>
                        $document,
                ],
                HOM_Router::panel_url()
            );


        return wp_nonce_url(
            $url,
            'hom_print_order_document_' .
            $order_id .
            '_' .
            $document
        );
    }


    private static function allowed_documents() {

        return [
            'invoice',
            'warehouse',
            'shipping',
        ];
    }


    private static function document_logo_url() {

        $attachments =
            get_posts(
                [
                    'post_type' =>
                        'attachment',

                    'post_status' =>
                        'inherit',

                    'post_mime_type' =>
                        'image',

                    'posts_per_page' =>
                        1,

                    'fields' =>
                        'ids',

                    'orderby' =>
                        'ID',

                    'order' =>
                        'DESC',

                    'meta_query' =>
                        [
                            [
                                'key' =>
                                    '_wp_attached_file',

                                'value' =>
                                    self::DOCUMENT_LOGO_FILENAME,

                                'compare' =>
                                    'LIKE',
                            ],
                        ],
                ]
            );


        if (empty($attachments)) {
            return '';
        }


        $url =
            wp_get_attachment_image_url(
                absint($attachments[0]),
                'full'
            );


        return $url
            ? (string) $url
            : '';
    }


    private static function seller_data() {

        $data =
            HOM_Seller_Settings::data();


        return [

            'name' =>
                $data['legal_name']
                    ?: get_bloginfo('name'),

            'legal_name' =>
                $data['legal_name'],

            'national_id' =>
                $data['national_id'],

            'economic_code' =>
                $data['economic_code'],

            'registration_no' =>
                $data['registration_no'],

            'postcode' =>
                $data['postcode'],

            'phone' =>
                $data['phone'],

            'address' =>
                $data['address'],

            'url' =>
                home_url('/'),
        ];
    }


    private static function customer_address(
        $order
    ) {

        $shipping =
            trim(
                wp_strip_all_tags(
                    $order
                        ->get_formatted_shipping_address()
                )
            );


        if ($shipping) {
            return $shipping;
        }


        return trim(
            wp_strip_all_tags(
                $order
                    ->get_formatted_billing_address()
            )
        );
    }


    private static function header(
        $title,
        $order
    ) {

        $seller =
            self::seller_data();


        $logo_url =
            self::document_logo_url();


        $created =
            $order->get_date_created();

        ?>
<!doctype html>
<html <?php language_attributes(); ?> dir="rtl">
<head>

    <meta charset="<?php bloginfo('charset'); ?>">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <meta
        name="robots"
        content="noindex,nofollow,noarchive"
    >

    <title>
        <?php echo esc_html($title); ?>
        -
        #<?php echo esc_html($order->get_order_number()); ?>
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
            font-family:
                Tahoma,
                Arial,
                sans-serif;
            font-size: 13px;
            line-height: 1.8;
        }

        .hom-print-page {
            width: min(1000px, calc(100% - 32px));
            margin: 24px auto;
            padding: 28px;
            border: 1px solid #e5e7eb;
            background: #fff;
        }

        .hom-print-toolbar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .hom-print-button {
            padding: 9px 18px;
            border: 0;
            border-radius: 8px;
            background: #111827;
            color: #fff;
            cursor: pointer;
            font: inherit;
        }

        .hom-print-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 18px;
            border-bottom: 2px solid #111827;
        }

        .hom-print-header h1 {
            margin: 0 0 6px;
            font-size: 24px;
        }


        .hom-print-document-logo {
            display: flex;
            align-items: center;

            min-height: 54px;

            margin-bottom: 12px;
        }

        .hom-print-document-logo img {
            display: block;

            width: 200px;
            max-width: 100%;

            height: 40px;

            object-fit: contain;
            object-position: right center;
        }

        .hom-print-meta {
            text-align: left;
            white-space: nowrap;
        }

        .hom-print-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin: 20px 0;
        }

        .hom-print-card {
            padding: 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }

        .hom-print-card strong {
            display: block;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        th,
        td {
            padding: 9px;
            border: 1px solid #d1d5db;
            text-align: right;
            vertical-align: top;
        }

        th {
            background: #f9fafb;
            white-space: nowrap;
        }

        .hom-print-total {
            width: min(420px, 100%);
            margin: 20px 0 0 auto;
        }

        .hom-print-total div {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 7px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .hom-print-total div:last-child {
            border-bottom: 2px solid #111827;
            font-weight: 700;
        }

        .hom-shipping-label {
            max-width: 700px;
            margin: 0 auto;
            font-size: 15px;
        }

        .hom-shipping-label h2 {
            margin-top: 0;
        }

        .hom-shipping-label-row {
            padding: 10px 0;
            border-bottom: 1px dashed #9ca3af;
        }

        .hom-shipping-label-row strong {
            display: inline-block;
            min-width: 140px;
        }

        .hom-warehouse-qr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;

            margin-top: 24px;
            padding: 18px;

            border: 2px solid #111827;
            border-radius: 10px;
        }

        .hom-warehouse-qr__text {
            flex: 1;
        }

        .hom-warehouse-qr__text strong {
            display: block;
            margin-bottom: 5px;
            font-size: 15px;
        }

        .hom-warehouse-qr__text p {
            margin: 0;
            color: #4b5563;
        }

        .hom-warehouse-qr__code {
            width: 150px;
            min-width: 150px;
            height: 150px;

            padding: 5px;
            background: #fff;
        }

        .hom-warehouse-qr__code img,
        .hom-warehouse-qr__code canvas {
            width: 140px !important;
            height: 140px !important;
        }

        .hom-print-footer {
            margin-top: 28px;
            padding-top: 14px;
            border-top: 1px solid #d1d5db;
            color: #6b7280;
            font-size: 11px;
        }

        @media print {

            @page {
                margin: 10mm;
            }

            body {
                background: #fff;
            }

            .hom-print-page {
                width: 100%;
                margin: 0;
                padding: 0;
                border: 0;
            }

            .hom-print-toolbar {
                display: none;
            }
        }

        @media (max-width: 650px) {

            .hom-print-header,
            .hom-print-grid {
                display: block;
            }

            .hom-print-meta {
                margin-top: 12px;
                text-align: right;
            }

            .hom-print-card {
                margin-bottom: 10px;
            }
        }
    </style>

</head>

<body>

<div class="hom-print-page">

    <div class="hom-print-toolbar">
        <button
            type="button"
            class="hom-print-button"
            onclick="window.print()"
        >
            چاپ
        </button>
    </div>


    <header class="hom-print-header">

        <div>

            <?php if ($logo_url) : ?>

                <div class="hom-print-document-logo">

                    <img
                        src="<?php
                        echo esc_url(
                            $logo_url
                        );
                        ?>"
                        alt="<?php
                        echo esc_attr(
                            $seller['name']
                        );
                        ?>"
                    >

                </div>

            <?php endif; ?>


            <h1>
                <?php echo esc_html($title); ?>
            </h1>

            <strong>
                <?php echo esc_html($seller['name']); ?>
            </strong>

            <?php if ($seller['address']) : ?>

                <div>
                    <?php echo esc_html($seller['address']); ?>
                </div>

            <?php endif; ?>


            <?php if ($seller['phone']) : ?>

                <div>
                    تلفن:
                    <span dir="ltr">
                        <?php echo esc_html($seller['phone']); ?>
                    </span>
                </div>

            <?php endif; ?>


            <?php if ($seller['national_id']) : ?>

                <div>
                    شناسه ملی:
                    <span dir="ltr">
                        <?php
                        echo esc_html(
                            $seller['national_id']
                        );
                        ?>
                    </span>
                </div>

            <?php endif; ?>


            <?php if ($seller['economic_code']) : ?>

                <div>
                    کد اقتصادی:
                    <span dir="ltr">
                        <?php
                        echo esc_html(
                            $seller['economic_code']
                        );
                        ?>
                    </span>
                </div>

            <?php endif; ?>


            <?php if ($seller['registration_no']) : ?>

                <div>
                    شماره ثبت:
                    <span dir="ltr">
                        <?php
                        echo esc_html(
                            $seller['registration_no']
                        );
                        ?>
                    </span>
                </div>

            <?php endif; ?>


            <?php if ($seller['postcode']) : ?>

                <div>
                    کدپستی:
                    <span dir="ltr">
                        <?php
                        echo esc_html(
                            $seller['postcode']
                        );
                        ?>
                    </span>
                </div>

            <?php endif; ?>

        </div>


        <div class="hom-print-meta">

            <div>
                شماره:
                <strong>
                    #<?php echo esc_html($order->get_order_number()); ?>
                </strong>
            </div>

            <div>
                تاریخ:
                <strong>
                    <?php
                    echo esc_html(
                        $created
                            ? $created->date_i18n('Y/m/d H:i')
                            : '—'
                    );
                    ?>
                </strong>
            </div>

        </div>

    </header>
        <?php
    }


    private static function footer() {

        ?>
    <footer class="hom-print-footer">
        این سند از پنل مدیریت فروشگاه هایپر صنعتی تولید شده است.
    </footer>

</div>

</body>
</html>
        <?php
    }


    public static function render_document(
        $order,
        $document
    ) {

        if (!($order instanceof WC_Order)) {
            return '';
        }


        ob_start();


        if ('warehouse' === $document) {

            self::render_warehouse(
                $order
            );

        } elseif ('shipping' === $document) {

            self::render_shipping(
                $order
            );

        } else {

            self::render_invoice(
                $order
            );
        }


        return (string)
            ob_get_clean();
    }


    private static function customer_block(
        $order
    ) {

        $name =
            trim(
                $order
                    ->get_formatted_billing_full_name()
            );

        $company =
            trim(
                (string)
                $order->get_billing_company()
            );

        $phone =
            trim(
                (string)
                $order->get_billing_phone()
            );

        $address =
            self::customer_address(
                $order
            );

        ?>
        <section class="hom-print-grid">

            <div class="hom-print-card">

                <strong>
                    خریدار / مشتری
                </strong>

                <div>
                    <?php
                    echo esc_html(
                        $name
                            ?: 'ثبت نشده'
                    );
                    ?>
                </div>

                <?php if ($company) : ?>

                    <div>
                        شرکت:
                        <?php echo esc_html($company); ?>
                    </div>

                <?php endif; ?>

                <?php if ($phone) : ?>

                    <div dir="ltr">
                        <?php echo esc_html($phone); ?>
                    </div>

                <?php endif; ?>

            </div>


            <div class="hom-print-card">

                <strong>
                    آدرس
                </strong>

                <div>
                    <?php
                    echo esc_html(
                        $address
                            ?: 'ثبت نشده'
                    );
                    ?>
                </div>

            </div>

        </section>
        <?php
    }


    private static function b2b_customer_block(
        $order
    ) {

        $data =
            HOM_Orders::b2b_customer_data(
                $order
            );


        $has_data =
            '' !== $data['legal_name'] ||
            '' !== $data['national_id'] ||
            '' !== $data['economic_code'] ||
            '' !== $data['registration_no'] ||
            '' !== $data['postcode'] ||
            '' !== $data['address'];


        if (!$has_data) {
            return;
        }

        ?>

        <section
            class="hom-print-card"
            style="margin-top:14px"
        >

            <strong>
                اطلاعات حقوقی خریدار
            </strong>


            <div>
                نام حقوقی / شرکت:
                <strong>
                    <?php
                    echo esc_html(
                        $data['legal_name']
                            ?: '—'
                    );
                    ?>
                </strong>
            </div>


            <div>
                شناسه ملی:
                <span dir="ltr">
                    <?php
                    echo esc_html(
                        $data['national_id']
                            ?: '—'
                    );
                    ?>
                </span>
            </div>


            <div>
                کد اقتصادی:
                <span dir="ltr">
                    <?php
                    echo esc_html(
                        $data['economic_code']
                            ?: '—'
                    );
                    ?>
                </span>
            </div>


            <div>
                شماره ثبت:
                <span dir="ltr">
                    <?php
                    echo esc_html(
                        $data['registration_no']
                            ?: '—'
                    );
                    ?>
                </span>
            </div>


            <div>
                کدپستی:
                <span dir="ltr">
                    <?php
                    echo esc_html(
                        $data['postcode']
                            ?: '—'
                    );
                    ?>
                </span>
            </div>


            <div>
                آدرس فاکتور:
                <?php
                echo esc_html(
                    $data['address']
                        ?: '—'
                );
                ?>
            </div>

        </section>

        <?php
    }


    private static function render_invoice(
        $order
    ) {

        self::header(
            'فاکتور فروش',
            $order
        );


        self::customer_block(
            $order
        );


        self::b2b_customer_block(
            $order
        );

        ?>
        <table>

            <thead>
                <tr>
                    <th>#</th>
                    <th>محصول</th>
                    <th>SKU</th>
                    <th>تعداد</th>
                    <th>قیمت واحد</th>
                    <th>جمع</th>
                </tr>
            </thead>

            <tbody>

            <?php
            $row = 0;

            foreach (
                $order->get_items('line_item')
                as $item
            ) :

                $row++;

                $product =
                    $item->get_product();

                $qty =
                    max(
                        1,
                        (float)
                        $item->get_quantity()
                    );

                $unit =
                    (float)
                    $item->get_total()
                    / $qty;
                ?>

                <tr>

                    <td>
                        <?php echo esc_html($row); ?>
                    </td>

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
                        <?php
                        echo esc_html(
                            wc_format_localized_decimal(
                                $qty
                            )
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo wp_kses_post(
                            wc_price(
                                $unit,
                                [
                                    'currency' =>
                                        $order->get_currency(),
                                ]
                            )
                        );
                        ?>
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


        <div class="hom-print-total">

            <?php
            foreach (
                $order->get_order_item_totals()
                as $total
            ) :
                ?>

                <div>

                    <span>
                        <?php
                        echo wp_kses_post(
                            $total['label']
                        );
                        ?>
                    </span>

                    <strong>
                        <?php
                        echo wp_kses_post(
                            $total['value']
                        );
                        ?>
                    </strong>

                </div>

            <?php endforeach; ?>

        </div>
        <?php

        self::footer();
    }


    private static function render_warehouse(
        $order
    ) {

        self::header(
            'برگه انبار',
            $order
        );


        $shipping =
            HOM_Orders::fulfillment_data(
                $order
            );


        $methods =
            HOM_Orders::shipping_methods();


        $warehouse_url =
            HOM_Warehouse_Verification::url(
                $order
            );

        ?>
        <section class="hom-print-grid">

            <div class="hom-print-card">

                <strong>
                    گیرنده
                </strong>

                <?php
                echo esc_html(
                    $order
                        ->get_formatted_billing_full_name()
                    ?: 'ثبت نشده'
                );
                ?>

            </div>


            <div class="hom-print-card">

                <strong>
                    مقصد / روش ارسال
                </strong>

                <?php
                echo esc_html(
                    $order->get_billing_city()
                    ?: '—'
                );
                ?>

                <?php if ($shipping['method']) : ?>

                    ·

                    <?php
                    echo esc_html(
                        $methods[
                            $shipping['method']
                        ] ?? $shipping['method']
                    );
                    ?>

                <?php endif; ?>

            </div>

        </section>


        <table>

            <thead>
                <tr>
                    <th>#</th>
                    <th>محصول</th>
                    <th>SKU</th>
                    <th>Part Number</th>
                    <th>تعداد</th>
                    <th>کنترل انبار</th>
                </tr>
            </thead>

            <tbody>

            <?php
            $row = 0;

            foreach (
                $order->get_items('line_item')
                as $item
            ) :

                $row++;

                $product =
                    $item->get_product();

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
                ?>

                <tr>

                    <td>
                        <?php echo esc_html($row); ?>
                    </td>

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
                        <?php
                        echo esc_html(
                            $part_number
                                ?: '—'
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            wc_format_localized_decimal(
                                $item->get_quantity()
                            )
                        );
                        ?>
                    </td>

                    <td>
                        □
                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>


        <?php if ($warehouse_url) : ?>

            <section class="hom-warehouse-qr">

                <div class="hom-warehouse-qr__text">

                    <strong>
                        کنترل آنلاین انبار
                    </strong>

                    <p>
                        مسئول انبار این QR را با موبایل اسکن کند،
                        همه اقلام سفارش را کنترل و سپس تأیید نهایی را ثبت کند.
                    </p>

                </div>


                <div
                    id="hom-warehouse-qr-code"
                    class="hom-warehouse-qr__code"
                    data-url="<?php
                    echo esc_attr(
                        $warehouse_url
                    );
                    ?>"
                ></div>

            </section>


            <script
                src="<?php
                echo esc_url(
                    HOM_URL .
                    'assets/vendor/qrcodejs/qrcode.min.js?ver=' .
                    HOM_VERSION
                );
                ?>"
            ></script>

            <script>
            (function () {

                var target =
                    document.getElementById(
                        'hom-warehouse-qr-code'
                    );

                if (
                    !target ||
                    typeof QRCode === 'undefined'
                ) {
                    return;
                }


                new QRCode(
                    target,
                    {
                        text:
                            target.getAttribute(
                                'data-url'
                            ),

                        width: 140,
                        height: 140,

                        correctLevel:
                            QRCode.CorrectLevel.M
                    }
                );
            }());
            </script>

        <?php endif; ?>


        <?php

        self::footer();
    }


    private static function render_shipping(
        $order
    ) {

        self::header(
            'برچسب ارسال',
            $order
        );


        $shipping =
            HOM_Orders::fulfillment_data(
                $order
            );


        $methods =
            HOM_Orders::shipping_methods();


        $method =
            $shipping['method']
                ? (
                    $methods[
                        $shipping['method']
                    ] ?? $shipping['method']
                )
                : 'تعیین نشده';


        $name =
            trim(
                $order
                    ->get_formatted_shipping_full_name()
            );


        if (!$name) {

            $name =
                trim(
                    $order
                        ->get_formatted_billing_full_name()
                );
        }


        $phone =
            trim(
                (string)
                $order->get_billing_phone()
            );


        $address =
            self::customer_address(
                $order
            );

        ?>
        <section class="hom-shipping-label">

            <h2>
                اطلاعات گیرنده
            </h2>


            <div class="hom-shipping-label-row">

                <strong>
                    گیرنده:
                </strong>

                <?php
                echo esc_html(
                    $name
                        ?: 'ثبت نشده'
                );
                ?>

            </div>


            <div class="hom-shipping-label-row">

                <strong>
                    شرکت:
                </strong>

                <?php
                echo esc_html(
                    $order->get_billing_company()
                    ?: '—'
                );
                ?>

            </div>


            <div class="hom-shipping-label-row">

                <strong>
                    تلفن:
                </strong>

                <span dir="ltr">
                    <?php echo esc_html($phone ?: '—'); ?>
                </span>

            </div>


            <div class="hom-shipping-label-row">

                <strong>
                    مقصد:
                </strong>

                <?php echo esc_html($address ?: '—'); ?>

            </div>


            <div class="hom-shipping-label-row">

                <strong>
                    روش ارسال:
                </strong>

                <?php echo esc_html($method); ?>

            </div>


            <div class="hom-shipping-label-row">

                <strong>
                    شرکت / باربری:
                </strong>

                <?php
                echo esc_html(
                    $shipping['company']
                        ?: '—'
                );
                ?>

            </div>


            <div class="hom-shipping-label-row">

                <strong>
                    کد رهگیری / بارنامه:
                </strong>

                <span dir="ltr">
                    <?php
                    echo esc_html(
                        $shipping['tracking_code']
                            ?: '—'
                    );
                    ?>
                </span>

            </div>

        </section>
        <?php

        self::footer();
    }


    public static function handle_print() {

        if (
            !is_user_logged_in() ||
            !current_user_can(
                HOM_Capabilities::CAP_VIEW_ORDERS
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
            isset($_GET['order_id'])
                ? absint(
                    $_GET['order_id']
                )
                : 0;


        $document =
            isset($_GET['document'])
                ? sanitize_key(
                    wp_unslash(
                        $_GET['document']
                    )
                )
                : '';


        if (
            !$order_id ||
            !in_array(
                $document,
                self::allowed_documents(),
                true
            )
        ) {

            wp_die(
                'درخواست چاپ نامعتبر است.',
                '',
                [
                    'response' => 400,
                ]
            );
        }


        check_admin_referer(
            'hom_print_order_document_' .
            $order_id .
            '_' .
            $document
        );


        $order =
            HOM_Orders::get_order(
                $order_id
            );


        if (!$order) {

            wp_die(
                'سفارش پیدا نشد.',
                '',
                [
                    'response' => 404,
                ]
            );
        }


        nocache_headers();

        header(
            'X-Robots-Tag: noindex, nofollow, noarchive',
            true
        );

        echo self::render_document(
            $order,
            $document
        );

        exit;
    }
}
