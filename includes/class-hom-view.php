<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOM_View {


    private static function store_name() {

        $name =
            trim(
                wp_strip_all_tags(
                    (string)
                    get_bloginfo('name')
                )
            );

        return '' !== $name
            ? $name
            : 'صنعت گستران الفت';
    }


    private static function store_label() {

        return
            'فروشگاه ' .
            self::store_name();
    }


    private static function brand_logo_url() {

        $logo_id =
            absint(
                get_theme_mod(
                    'custom_logo'
                )
            );


        if (!$logo_id) {

            $logo_id =
                absint(
                    get_option(
                        'site_icon'
                    )
                );
        }


        if (!$logo_id) {
            return '';
        }


        $url =
            wp_get_attachment_image_url(
                $logo_id,
                'full'
            );


        return $url
            ? (string) $url
            : '';
    }



    private static function sidebar_logo_url() {

        /*
         * The compact white/orange Site Icon is better suited
         * to the dark and narrow management sidebar than the
         * horizontal website logo.
         */

        $site_icon_id =
            absint(
                get_option(
                    'site_icon'
                )
            );


        if ($site_icon_id) {

            $url =
                wp_get_attachment_image_url(
                    $site_icon_id,
                    'full'
                );


            if ($url) {
                return (string) $url;
            }
        }


        return self::brand_logo_url();
    }




    public static function render() {

        if (HOM_Auth::is_owner_logged_in()) {
            self::render_dashboard();
            return;
        }

        self::render_login();
    }


    private static function document_start($title) {

        $css_url = HOM_URL
            . 'assets/css/owner-panel.css?ver='
            . rawurlencode(HOM_VERSION);

        ?>
<!doctype html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover"
    >
    <meta
        name="robots"
        content="noindex,nofollow,noarchive,nosnippet"
    >
    <title><?php echo esc_html($title); ?></title>

    <link
        rel="stylesheet"
        href="<?php echo esc_url($css_url); ?>"
    >


        <style id="hom-official-anjomanmax">

        @font-face {
            font-family: "AnjomanMax";

            src:
                url("<?php
                echo esc_url(
                    get_stylesheet_directory_uri()
                    . '/assets/fonts/AnjomanMaxVF.woff2'
                );
                ?>")
                format("woff2"),

                url("<?php
                echo esc_url(
                    get_stylesheet_directory_uri()
                    . '/assets/fonts/AnjomanMaxVF.woff'
                );
                ?>")
                format("woff");

            font-weight: 100 900;
            font-style: normal;
            font-display: swap;
        }


        body.hom-page,
        .hom-app,
        .hom-shell,
        .hom-sidebar,
        .hom-main,
        .hom-main-content,
        .hom-topbar,
        .hom-login-card,
        .hom-image-manager,
        .hom-media-modal,
        .hom-media-modal__dialog,
        .hom-final-action-bar,
        button,
        input,
        select,
        textarea {
            font-family:
                "AnjomanMax",
                sans-serif !important;
        }

        </style>

</head>

<body class="hom-page">
        <?php
    }


    private static function document_end() {
        ?>

<script>
/* HOM GLOBAL FIELD STATE UI */
(function () {

    'use strict';


    function shouldHandle(field) {

        if (!field) {
            return false;
        }


        var tag =
            String(
                field.tagName || ''
            ).toLowerCase();


        if (
            tag !== 'input' &&
            tag !== 'select' &&
            tag !== 'textarea'
        ) {
            return false;
        }


        var type =
            String(
                field.type || ''
            ).toLowerCase();


        return ![
            'hidden',
            'submit',
            'button',
            'reset',
            'checkbox',
            'radio',
            'file',
            'image'
        ].includes(type);
    }


    function hasValue(field) {

        if (
            field.tagName &&
            field.tagName.toLowerCase() === 'select'
        ) {

            return String(
                field.value || ''
            ).trim() !== '';
        }


        return String(
            field.value || ''
        ).trim() !== '';
    }


    function updateField(field) {

        if (!shouldHandle(field)) {
            return;
        }


        var filled =
            hasValue(field);

        var requiredEmpty =
            field.required &&
            !filled;


        field.classList.toggle(
            'hom-field-is-filled',
            filled
        );


        field.classList.toggle(
            'hom-field-is-required-empty',
            requiredEmpty
        );
    }


    function scan(root) {

        var context =
            root &&
            root.querySelectorAll
                ? root
                : document;


        context
            .querySelectorAll(
                'input, select, textarea'
            )
            .forEach(
                updateField
            );
    }


    document.addEventListener(
        'DOMContentLoaded',
        function () {

            scan(document);


            document.addEventListener(
                'input',
                function (event) {

                    updateField(
                        event.target
                    );
                }
            );


            document.addEventListener(
                'change',
                function (event) {

                    updateField(
                        event.target
                    );
                }
            );


            document.addEventListener(
                'blur',
                function (event) {

                    updateField(
                        event.target
                    );
                },
                true
            );


            var observer =
                new MutationObserver(
                    function (mutations) {

                        mutations.forEach(
                            function (mutation) {

                                mutation
                                    .addedNodes
                                    .forEach(
                                        function (node) {

                                            if (
                                                node.nodeType !== 1
                                            ) {
                                                return;
                                            }


                                            if (
                                                node.matches &&
                                                node.matches(
                                                    'input, select, textarea'
                                                )
                                            ) {

                                                updateField(
                                                    node
                                                );
                                            }


                                            scan(node);
                                        }
                                    );
                            }
                        );
                    }
                );


            observer.observe(
                document.body,
                {
                    childList: true,
                    subtree: true
                }
            );
        }
    );

})();
</script>

</body>
</html>
        <?php
    }


    private static function render_login() {

        self::document_start(
            'ورود به پنل مدیریت ' .
            self::store_label()
        );

        $brand_logo_url =
            self::brand_logo_url();

        $error = HOM_Auth::get_error();

        ?>
<div class="hom-login-layout">

    <section class="hom-login-brand">

        <div class="hom-login-store-brand">

            <?php if ($brand_logo_url) : ?>

                <img
                    src="<?php
                    echo esc_url(
                        $brand_logo_url
                    );
                    ?>"
                    alt="<?php
                    echo esc_attr(
                        self::store_name()
                    );
                    ?>"
                    class="hom-store-logo hom-store-logo--login"
                >

            <?php else : ?>

                <strong class="hom-store-logo-fallback">
                    <?php
                    echo esc_html(
                        self::store_name()
                    );
                    ?>
                </strong>

            <?php endif; ?>

            <span>
                <?php
                echo esc_html(
                    self::store_label()
                );
                ?>
            </span>

        </div>

    </section>


    <main class="hom-login-main">

        <div class="hom-login-card">

            <header class="hom-login-header">

                <span class="hom-eyebrow">
                    OWNER PANEL
                </span>

                <h1>
                    ورود به پنل مدیریت
                    <?php
                    echo esc_html(
                        self::store_label()
                    );
                    ?>
                </h1>

                <p>
                    نام کاربری و رمز عبور اختصاصی خود را وارد کنید.
                </p>

            </header>


            <?php if ($error) : ?>

                <div
                    class="hom-alert hom-alert-error"
                    role="alert"
                >
                    <?php echo esc_html($error); ?>
                </div>

            <?php endif; ?>


            <form
                method="post"
                action="<?php echo esc_url(HOM_Router::panel_url()); ?>"
                class="hom-login-form"
                autocomplete="on"
            >

                <input
                    type="hidden"
                    name="hom_action"
                    value="login"
                >

                <?php
                wp_nonce_field(
                    'hom_owner_login',
                    'hom_login_nonce'
                );
                ?>


                <label class="hom-field">

                    <span>
                        نام کاربری
                    </span>

                    <input
                        type="text"
                        name="hom_username"
                        autocomplete="username"
                        inputmode="text"
                        required
                        autofocus
                    >

                </label>


                <label class="hom-field">

                    <span>
                        رمز عبور
                    </span>

                    <input
                        type="password"
                        name="hom_password"
                        autocomplete="current-password"
                        required
                    >

                </label>


                <label class="hom-checkbox">

                    <input
                        type="checkbox"
                        name="hom_remember"
                        value="1"
                    >

                    <span>
                        ورود من حفظ شود
                    </span>

                </label>


                <button
                    type="submit"
                    class="hom-button hom-button-primary hom-button-full"
                >
                    ورود به پنل
                </button>

            </form>

        </div>

    </main>

</div>
        <?php

        self::document_end();
    }


    private static function current_view() {

        $view = isset($_GET['view'])
            ? sanitize_key(
                wp_unslash(
                    $_GET['view']
                )
            )
            : 'dashboard';

        $allowed = [
            'dashboard',
            'products',
            'product-images',
            'orders',
            'seller-settings',
            'help',
            'help-customers',
            'help-product-images',
        ];

        return in_array(
            $view,
            $allowed,
            true
        )
            ? $view
            : 'dashboard';
    }


    private static function render_dashboard() {

        $user =
            wp_get_current_user();

        $current_view =
            self::current_view();


        self::document_start(
            'پنل مدیریت ' .
            self::store_label()
        );

        $brand_logo_url =
            self::sidebar_logo_url();

        ?>
<div class="hom-app">

    <aside class="hom-sidebar">

        <div class="hom-sidebar-brand">

            <div class="hom-sidebar-store-brand">

                <?php if ($brand_logo_url) : ?>

                    <img
                        src="<?php
                        echo esc_url(
                            $brand_logo_url
                        );
                        ?>"
                        alt="<?php
                        echo esc_attr(
                            self::store_name()
                        );
                        ?>"
                        class="hom-store-logo hom-store-logo--sidebar"
                    >

                <?php else : ?>

                    <strong class="hom-store-logo-fallback">
                        <?php
                        echo esc_html(
                            self::store_name()
                        );
                        ?>
                    </strong>

                <?php endif; ?>


                <span class="hom-sidebar-store-label">
                    <?php
                    echo esc_html(
                        self::store_label()
                    );
                    ?>
                </span>

            </div>

        </div>


        <button
            type="button"
            class="hom-menu-toggle"
            data-hom-menu-toggle
            aria-expanded="false"
            aria-label="باز کردن منوی مدیریت"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>


        <nav
            class="hom-nav"
            aria-label="منوی مدیریت"
        >

            <a
                href="<?php
                echo esc_url(
                    HOM_Router::panel_url()
                );
                ?>"
                class="hom-nav-item <?php
                echo 'dashboard' === $current_view
                    ? 'is-active'
                    : '';
                ?>"
            >
                <span
                    class="hom-nav-icon"
                    aria-hidden="true"
                >
                    ⌂
                </span>

                <span>
                    داشبورد مدیریت فروشگاه
                </span>
            </a>


            <a
                href="<?php
                echo esc_url(
                    add_query_arg(
                        'view',
                        'products',
                        HOM_Router::panel_url()
                    )
                );
                ?>"
                class="hom-nav-item <?php
                echo in_array(
                    $current_view,
                    [
                        'products',
                        'product-images',
                    ],
                    true
                )
                    ? 'is-active'
                    : '';
                ?>"
            >
                <span
                    class="hom-nav-icon"
                    aria-hidden="true"
                >
                    ▦
                </span>

                <span>
                    مدیریت تصاویر محصولات
                </span>
            </a>


            <a
                href="<?php
                echo esc_url(
                    add_query_arg(
                        'view',
                        'orders',
                        HOM_Router::panel_url()
                    )
                );
                ?>"
                class="hom-nav-item <?php
                echo 'orders' === $current_view
                    ? 'is-active'
                    : '';
                ?>"
            >
                <span
                    class="hom-nav-icon"
                    aria-hidden="true"
                >
                    ≡
                </span>

                <span>
                    مدیریت و پیگیری مشتریان
                </span>
            </a>


            <a
                href="<?php
                echo esc_url(
                    add_query_arg(
                        'view',
                        'seller-settings',
                        HOM_Router::panel_url()
                    )
                );
                ?>"
                class="hom-nav-item <?php
                echo 'seller-settings' === $current_view
                    ? 'is-active'
                    : '';
                ?>"
            >
                <span
                    class="hom-nav-icon"
                    aria-hidden="true"
                >
                    ⚙
                </span>

                <span>
                    اطلاعات
                    <?php
                    echo esc_html(
                        self::store_name()
                    );
                    ?>
                </span>
            </a>



            <a
                href="<?php
                echo esc_url(
                    add_query_arg(
                        'view',
                        'help',
                        HOM_Router::panel_url()
                    )
                );
                ?>"
                class="hom-nav-item <?php
                echo in_array(
                    $current_view,
                    [
                        'help',
                        'help-customers',
                        'help-product-images',
                    ],
                    true
                )
                    ? 'is-active'
                    : '';
                ?>"
            >
                <span
                    class="hom-nav-icon"
                    aria-hidden="true"
                >
                    ?
                </span>

                <span>
                    راهنمای پنل
                </span>
            </a>


            <a
                href="<?php
                echo esc_url(
                    HOM_Auth::account_url()
                );
                ?>"
                class="hom-nav-item hom-nav-account-return"
            >
                <span
                    class="hom-nav-icon"
                    aria-hidden="true"
                >
                    ←
                </span>

                <span>
                    بازگشت به پنل کاربری
                </span>
            </a>

        </nav>

    </aside>


    <script>
    /* HOM RESPONSIVE SIDEBAR MENU */
    document.addEventListener(
        'DOMContentLoaded',
        function () {

            var sidebar =
                document.querySelector(
                    '.hom-sidebar'
                );

            var toggle =
                document.querySelector(
                    '[data-hom-menu-toggle]'
                );

            if (!sidebar || !toggle) {
                return;
            }


            function closeMenu() {

                sidebar.classList.remove(
                    'is-menu-open'
                );

                toggle.setAttribute(
                    'aria-expanded',
                    'false'
                );
            }


            toggle.addEventListener(
                'click',
                function () {

                    var open =
                        sidebar.classList.toggle(
                            'is-menu-open'
                        );

                    toggle.setAttribute(
                        'aria-expanded',
                        open
                            ? 'true'
                            : 'false'
                    );
                }
            );


            sidebar
                .querySelectorAll(
                    '.hom-nav-item'
                )
                .forEach(
                    function (item) {

                        item.addEventListener(
                            'click',
                            closeMenu
                        );
                    }
                );


            window.addEventListener(
                'resize',
                function () {

                    if (
                        window.innerWidth >
                        980
                    ) {
                        closeMenu();
                    }
                }
            );
        }
    );
    </script>


    <div class="hom-workspace">

        <header class="hom-topbar">

            <div>

                <span class="hom-topbar-label">
                    مدیریت فروشگاه
                </span>

                <strong>
                    <?php
                    echo esc_html(
                        $user->display_name
                            ?: $user->user_login
                    );
                    ?>
                </strong>

            </div>


            <a
                href="<?php echo esc_url(HOM_Auth::account_url()); ?>"
                class="hom-account-return"
            >
                <span
                    class="hom-account-return__icon"
                    aria-hidden="true"
                >
                    ←
                </span>

                <span>
                    بازگشت به پنل کاربری
                </span>
            </a>

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
                    value="logout"
                >

                <?php
                wp_nonce_field(
                    'hom_owner_logout',
                    'hom_logout_nonce'
                );
                ?>

                <button
                    type="submit"
                    class="hom-button hom-button-secondary"
                >
                    خروج
                </button>

            </form>

        </header>


        <main class="hom-content">

            <?php

            if ('seller-settings' === $current_view) {

                HOM_Seller_Settings_View::render();

            } elseif ('help' === $current_view) {

                self::render_help_index_content();

            } elseif (
                'help-customers' ===
                $current_view
            ) {

                self::render_help_customers_content();

            } elseif (
                'help-product-images' ===
                $current_view
            ) {

                self::render_help_product_images_content();

            } elseif ('product-images' === $current_view) {

                self::render_product_images_content();

            } elseif ('products' === $current_view) {

                self::render_products_content();

            } elseif ('orders' === $current_view) {

                self::render_orders_content();

            } else {

                self::render_dashboard_content();
            }

            ?>

        </main>

    </div>

</div>
        <?php

        self::document_end();
    }


    private static function render_dashboard_content() {

        global $wpdb;


        /*
         * ---------------------------------------------------------
         * SALES OVERVIEW
         * ---------------------------------------------------------
         */

        $counts =
            HOM_Orders::summary_counts();


        $active_statuses = [
            'preinvoice-review',
            'preinv-approved',
            'pending',
            'on-hold',
            'processing',
            'hom-ready',
            'hom-shipped',
        ];


        $active_orders = 0;


        foreach (
            $active_statuses
            as $active_status
        ) {

            $active_orders +=
                absint(
                    $counts[
                        $active_status
                    ] ?? 0
                );
        }


        /*
         * ---------------------------------------------------------
         * RECENT ORDERS
         * ---------------------------------------------------------
         */

        $recent_result =
            HOM_Orders::query(
                'all',
                1,
                ''
            );


        $recent_orders =
            array_slice(
                $recent_result['items'] ?? [],
                0,
                5
            );


        /*
         * ---------------------------------------------------------
         * PRODUCT IMAGE HEALTH
         * ---------------------------------------------------------
         */

        $product_post_counts =
            wp_count_posts(
                'product'
            );


        $total_products =
            isset(
                $product_post_counts->publish
            )
                ? absint(
                    $product_post_counts->publish
                )
                : 0;


        $missing_main_images =
            absint(
                $wpdb->get_var(
                    "
                    SELECT
                        COUNT(DISTINCT p.ID)

                    FROM
                        {$wpdb->posts} p

                    LEFT JOIN
                        {$wpdb->postmeta} pm

                        ON
                            pm.post_id = p.ID
                            AND
                            pm.meta_key = '_thumbnail_id'

                    WHERE
                        p.post_type = 'product'
                        AND
                        p.post_status = 'publish'
                        AND
                        (
                            pm.meta_id IS NULL
                            OR
                            pm.meta_value = ''
                            OR
                            pm.meta_value = '0'
                        )
                    "
                )
            );


        $products_with_image =
            max(
                0,
                $total_products
                -
                $missing_main_images
            );


        $image_coverage =
            $total_products > 0
                ? round(
                    (
                        $products_with_image
                        /
                        $total_products
                    )
                    *
                    100
                )
                : 0;


        /*
         * ---------------------------------------------------------
         * SELLER DATA HEALTH
         * ---------------------------------------------------------
         */

        $seller =
            HOM_Seller_Settings::data();


        $seller_required_fields = [
            'legal_name',
            'national_id',
            'economic_code',
            'registration_no',
            'postcode',
            'phone',
            'address',
        ];


        $seller_missing = [];


        foreach (
            $seller_required_fields
            as $seller_field
        ) {

            if (
                '' ===
                trim(
                    (string)
                    (
                        $seller[
                            $seller_field
                        ] ?? ''
                    )
                )
            ) {

                $seller_missing[] =
                    $seller_field;
            }
        }


        /*
         * ---------------------------------------------------------
         * URLS
         * ---------------------------------------------------------
         */

        $orders_url =
            add_query_arg(
                'view',
                'orders',
                HOM_Router::panel_url()
            );


        $products_url =
            add_query_arg(
                'view',
                'products',
                HOM_Router::panel_url()
            );


        $seller_url =
            add_query_arg(
                'view',
                'seller-settings',
                HOM_Router::panel_url()
            );


        $help_url =
            add_query_arg(
                'view',
                'help',
                HOM_Router::panel_url()
            );


        $status_url =
            static function (
                $status
            ) {

                return add_query_arg(
                    [
                        'view' =>
                            'orders',

                        'status' =>
                            $status,
                    ],
                    HOM_Router::panel_url()
                );
            };


        $action_cards = [

            [
                'status' =>
                    'preinvoice-review',

                'label' =>
                    'پیش‌فاکتور جدید',

                'description' =>
                    'نیازمند بررسی و قیمت‌گذاری',

                'icon' =>
                    '🧾',
            ],

            [
                'status' =>
                    'preinv-approved',

                'label' =>
                    'تأیید شده',

                'description' =>
                    'منتظر ادامه فرایند پرداخت',

                'icon' =>
                    '✓',
            ],

            [
                'status' =>
                    'pending',

                'label' =>
                    'انتظار پرداخت',

                'description' =>
                    'پرونده‌های در انتظار پرداخت',

                'icon' =>
                    '💳',
            ],

            [
                'status' =>
                    'on-hold',

                'label' =>
                    'در انتظار بررسی',

                'description' =>
                    'نیازمند بررسی واحد فروش',

                'icon' =>
                    '!',
            ],

            [
                'status' =>
                    'processing',

                'label' =>
                    'در حال آماده‌سازی',

                'description' =>
                    'سفارش‌های وارد عملیات',

                'icon' =>
                    '📦',
            ],

            [
                'status' =>
                    'hom-ready',

                'label' =>
                    'آماده ارسال',

                'description' =>
                    'آماده ثبت ارسال و رهگیری',

                'icon' =>
                    '🚚',
            ],
        ];

        ?>


        <div class="hom-dashboard">


            <div class="hom-page-heading hom-dashboard-heading">

                <div>

                    <span class="hom-eyebrow">
                        DASHBOARD
                    </span>

                    <h1>
                        داشبورد مدیریت
                        <?php
                        echo esc_html(
                            self::store_label()
                        );
                        ?>
                    </h1>

                    <p>
                        وضعیت فروش، پرونده‌های مشتریان،
                        عملیات ارسال و تصاویر محصولات را
                        از یک نقطه کنترل کنید.
                    </p>

                </div>


                <a
                    href="<?php
                    echo esc_url(
                        $help_url
                    );
                    ?>"
                    class="hom-dashboard-help"
                >
                    <span aria-hidden="true">
                        ?
                    </span>

                    راهنمای پنل
                </a>

            </div>



            <!-- ==================================================
                 MAIN KPI
                 ================================================== -->

            <section class="hom-dashboard-kpis">


                <a
                    href="<?php
                    echo esc_url(
                        $orders_url
                    );
                    ?>"
                    class="hom-dashboard-kpi"
                >

                    <span class="hom-dashboard-kpi__icon">
                        ◉
                    </span>

                    <div>

                        <span>
                            پرونده‌های فعال
                        </span>

                        <strong>
                            <?php
                            echo esc_html(
                                number_format_i18n(
                                    $active_orders
                                )
                            );
                            ?>
                        </strong>

                        <small>
                            پرونده در جریان عملیات
                        </small>

                    </div>

                </a>


                <a
                    href="<?php
                    echo esc_url(
                        $status_url(
                            'preinvoice-review'
                        )
                    );
                    ?>"
                    class="
                        hom-dashboard-kpi
                        is-attention
                    "
                >

                    <span class="hom-dashboard-kpi__icon">
                        🧾
                    </span>

                    <div>

                        <span>
                            پیش‌فاکتور جدید
                        </span>

                        <strong>
                            <?php
                            echo esc_html(
                                number_format_i18n(
                                    absint(
                                        $counts[
                                            'preinvoice-review'
                                        ] ?? 0
                                    )
                                )
                            );
                            ?>
                        </strong>

                        <small>
                            نیازمند بررسی فروش
                        </small>

                    </div>

                </a>


                <a
                    href="<?php
                    echo esc_url(
                        $status_url(
                            'hom-ready'
                        )
                    );
                    ?>"
                    class="hom-dashboard-kpi"
                >

                    <span class="hom-dashboard-kpi__icon">
                        🚚
                    </span>

                    <div>

                        <span>
                            آماده ارسال
                        </span>

                        <strong>
                            <?php
                            echo esc_html(
                                number_format_i18n(
                                    absint(
                                        $counts[
                                            'hom-ready'
                                        ] ?? 0
                                    )
                                )
                            );
                            ?>
                        </strong>

                        <small>
                            منتظر ثبت ارسال
                        </small>

                    </div>

                </a>


                <a
                    href="<?php
                    echo esc_url(
                        $products_url
                    );
                    ?>"
                    class="hom-dashboard-kpi"
                >

                    <span class="hom-dashboard-kpi__icon">
                        🖼️
                    </span>

                    <div>

                        <span>
                            پوشش تصویر محصولات
                        </span>

                        <strong>
                            <?php
                            echo esc_html(
                                number_format_i18n(
                                    $image_coverage
                                )
                            );
                            ?>%
                        </strong>

                        <small>
                            <?php
                            echo esc_html(
                                number_format_i18n(
                                    $missing_main_images
                                )
                            );
                            ?>
                            محصول بدون تصویر اصلی
                        </small>

                    </div>

                </a>


            </section>



            <!-- ==================================================
                 NEEDS ATTENTION
                 ================================================== -->

            <section class="hom-dashboard-section">

                <div class="hom-dashboard-section__head">

                    <div>

                        <span>
                            عملیات فروش
                        </span>

                        <h2>
                            نیازمند اقدام
                        </h2>

                        <p>
                            برای ورود مستقیم به هر مرحله،
                            کارت مربوط را انتخاب کنید.
                        </p>

                    </div>


                    <a
                        href="<?php
                        echo esc_url(
                            $orders_url
                        );
                        ?>"
                    >
                        مشاهده همه پرونده‌ها
                        ←
                    </a>

                </div>


                <div class="hom-dashboard-actions">

                    <?php
                    foreach (
                        $action_cards
                        as $action_card
                    ) :

                        $action_count =
                            absint(
                                $counts[
                                    $action_card[
                                        'status'
                                    ]
                                ] ?? 0
                            );
                        ?>

                        <a
                            href="<?php
                            echo esc_url(
                                $status_url(
                                    $action_card[
                                        'status'
                                    ]
                                )
                            );
                            ?>"
                            class="
                                hom-dashboard-action
                                <?php
                                echo $action_count > 0
                                    ? 'has-items'
                                    : '';
                                ?>
                            "
                        >

                            <span
                                class="
                                    hom-dashboard-action__icon
                                "
                                aria-hidden="true"
                            >
                                <?php
                                echo esc_html(
                                    $action_card[
                                        'icon'
                                    ]
                                );
                                ?>
                            </span>


                            <div>

                                <strong>
                                    <?php
                                    echo esc_html(
                                        number_format_i18n(
                                            $action_count
                                        )
                                    );
                                    ?>
                                </strong>

                                <span>
                                    <?php
                                    echo esc_html(
                                        $action_card[
                                            'label'
                                        ]
                                    );
                                    ?>
                                </span>

                                <small>
                                    <?php
                                    echo esc_html(
                                        $action_card[
                                            'description'
                                        ]
                                    );
                                    ?>
                                </small>

                            </div>

                            <span
                                class="
                                    hom-dashboard-action__arrow
                                "
                                aria-hidden="true"
                            >
                                ←
                            </span>

                        </a>

                    <?php endforeach; ?>

                </div>

            </section>



            <!-- ==================================================
                 OPERATIONAL CONTENT
                 ================================================== -->

            <section class="hom-dashboard-main-grid">


                <!-- RECENT CASES -->

                <article
                    class="
                        hom-dashboard-panel
                        hom-dashboard-recent
                    "
                >

                    <div class="hom-dashboard-panel__head">

                        <div>

                            <span>
                                آخرین فعالیت‌ها
                            </span>

                            <h2>
                                پرونده‌های اخیر
                            </h2>

                        </div>


                        <a
                            href="<?php
                            echo esc_url(
                                $orders_url
                            );
                            ?>"
                        >
                            همه پرونده‌ها
                        </a>

                    </div>


                    <?php
                    if (!$recent_orders) :
                        ?>

                        <div class="hom-dashboard-empty">

                            هنوز پرونده‌ای ثبت نشده است.

                        </div>

                    <?php else : ?>


                        <div class="hom-dashboard-recent-list">

                            <?php
                            foreach (
                                $recent_orders
                                as $recent_item
                            ) :

                                $recent_order =
                                    HOM_Orders::get_order(
                                        $recent_item[
                                            'id'
                                        ]
                                    );


                                $recent_contact =
                                    $recent_order
                                        ? HOM_Orders::
                                            customer_contact_data(
                                                $recent_order
                                            )
                                        : [];


                                $recent_name =
                                    trim(
                                        (string)
                                        (
                                            $recent_contact[
                                                'display_name'
                                            ]
                                            ?? ''
                                        )
                                    );


                                if (
                                    '' === $recent_name ||
                                    'مشتری بدون نام'
                                        ===
                                        $recent_name
                                ) {

                                    $recent_name =
                                        trim(
                                            (string)
                                            (
                                                $recent_item[
                                                    'customer_name'
                                                ]
                                                ?? ''
                                            )
                                        );
                                }


                                if (
                                    '' === $recent_name ||
                                    'مشتری بدون نام'
                                        ===
                                        $recent_name
                                ) {

                                    $recent_name =
                                        'نام ثبت نشده';
                                }


                                $recent_contact_value =
                                    trim(
                                        (string)
                                        (
                                            $recent_contact[
                                                'phone'
                                            ]
                                            ?? ''
                                        )
                                    );


                                if (
                                    '' ===
                                    $recent_contact_value
                                ) {

                                    $recent_contact_value =
                                        trim(
                                            (string)
                                            (
                                                $recent_contact[
                                                    'email'
                                                ]
                                                ?? ''
                                            )
                                        );
                                }
                                ?>

                                <a
                                    href="<?php
                                    echo esc_url(
                                        HOM_Orders::detail_url(
                                            $recent_item[
                                                'id'
                                            ]
                                        )
                                    );
                                    ?>"
                                    class="
                                        hom-dashboard-recent-row
                                    "
                                >

                                    <div
                                        class="
                                            hom-dashboard-recent-number
                                        "
                                    >
                                        <strong>
                                            #<?php
                                            echo esc_html(
                                                $recent_item[
                                                    'number'
                                                ]
                                            );
                                            ?>
                                        </strong>

                                        <span>
                                            <?php
                                            echo esc_html(
                                                $recent_item[
                                                    'is_preinvoice'
                                                ]
                                                    ? 'پیش‌فاکتور'
                                                    : 'سفارش'
                                            );
                                            ?>
                                        </span>
                                    </div>


                                    <div
                                        class="
                                            hom-dashboard-recent-customer
                                        "
                                    >

                                        <strong>
                                            <?php
                                            echo esc_html(
                                                $recent_name
                                            );
                                            ?>
                                        </strong>


                                        <?php
                                        if (
                                            $recent_contact_value
                                        ) :
                                            ?>

                                            <span dir="ltr">
                                                <?php
                                                echo esc_html(
                                                    $recent_contact_value
                                                );
                                                ?>
                                            </span>

                                        <?php endif; ?>

                                    </div>


                                    <span
                                        class="
                                            hom-dashboard-recent-status
                                        "
                                    >
                                        <?php
                                        echo esc_html(
                                            $recent_item[
                                                'status_label'
                                            ]
                                        );
                                        ?>
                                    </span>


                                    <strong
                                        class="
                                            hom-dashboard-recent-total
                                        "
                                    >
                                        <?php
                                        echo wp_kses_post(
                                            $recent_item[
                                                'total_html'
                                            ]
                                        );
                                        ?>
                                    </strong>


                                    <span
                                        class="
                                            hom-dashboard-recent-arrow
                                        "
                                        aria-hidden="true"
                                    >
                                        ←
                                    </span>

                                </a>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </article>



                <!-- SIDE HEALTH PANELS -->

                <div class="hom-dashboard-side">


                    <article class="hom-dashboard-panel">

                        <div class="hom-dashboard-panel__head">

                            <div>

                                <span>
                                    وضعیت کاتالوگ
                                </span>

                                <h2>
                                    تصاویر محصولات
                                </h2>

                            </div>

                            <span
                                class="
                                    hom-dashboard-health-icon
                                "
                            >
                                🖼️
                            </span>

                        </div>


                        <div class="hom-dashboard-image-health">

                            <div
                                class="
                                    hom-dashboard-progress
                                "
                                role="progressbar"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="<?php
                                echo esc_attr(
                                    $image_coverage
                                );
                                ?>"
                            >

                                <span
                                    style="<?php
                                    echo esc_attr(
                                        'width:' .
                                        $image_coverage .
                                        '%'
                                    );
                                    ?>"
                                ></span>

                            </div>


                            <strong>
                                <?php
                                echo esc_html(
                                    number_format_i18n(
                                        $products_with_image
                                    )
                                );
                                ?>
                                از
                                <?php
                                echo esc_html(
                                    number_format_i18n(
                                        $total_products
                                    )
                                );
                                ?>
                                محصول دارای تصویر اصلی
                            </strong>


                            <p>
                                <?php if (
                                    $missing_main_images > 0
                                ) : ?>

                                    هنوز
                                    <strong>
                                        <?php
                                        echo esc_html(
                                            number_format_i18n(
                                                $missing_main_images
                                            )
                                        );
                                        ?>
                                    </strong>
                                    محصول بدون تصویر اصلی است.

                                <?php else : ?>

                                    همه محصولات منتشرشده
                                    تصویر اصلی دارند.

                                <?php endif; ?>
                            </p>


                            <a
                                href="<?php
                                echo esc_url(
                                    $products_url
                                );
                                ?>"
                                class="
                                    hom-dashboard-panel-action
                                "
                            >
                                مدیریت تصاویر محصولات
                                ←
                            </a>

                        </div>

                    </article>



                    <article class="hom-dashboard-panel">

                        <div class="hom-dashboard-panel__head">

                            <div>

                                <span>
                                    اطلاعات سازمانی
                                </span>

                                <h2>
                                    اطلاعات
                                    <?php
                                    echo esc_html(
                                        self::store_name()
                                    );
                                    ?>
                                </h2>

                            </div>

                            <span
                                class="
                                    hom-dashboard-health-icon
                                "
                            >
                                ⚙️
                            </span>

                        </div>


                        <div class="hom-dashboard-seller-health">

                            <?php if (
                                empty(
                                    $seller_missing
                                )
                            ) : ?>

                                <div
                                    class="
                                        hom-dashboard-health-state
                                        is-complete
                                    "
                                >
                                    <span>✓</span>

                                    <div>
                                        <strong>
                                            اطلاعات کامل است
                                        </strong>

                                        <small>
                                            اطلاعات موردنیاز
                                            اسناد فروش ثبت شده است.
                                        </small>
                                    </div>
                                </div>

                            <?php else : ?>

                                <div
                                    class="
                                        hom-dashboard-health-state
                                        is-warning
                                    "
                                >
                                    <span>!</span>

                                    <div>
                                        <strong>
                                            <?php
                                            echo esc_html(
                                                number_format_i18n(
                                                    count(
                                                        $seller_missing
                                                    )
                                                )
                                            );
                                            ?>
                                            مورد نیاز به تکمیل
                                        </strong>

                                        <small>
                                            برای کامل بودن
                                            اسناد فروش بررسی شود.
                                        </small>
                                    </div>
                                </div>

                            <?php endif; ?>


                            <a
                                href="<?php
                                echo esc_url(
                                    $seller_url
                                );
                                ?>"
                                class="
                                    hom-dashboard-panel-action
                                "
                            >
                                بررسی اطلاعات
                                <?php
                                echo esc_html(
                                    self::store_name()
                                );
                                ?>
                                ←
                            </a>

                        </div>

                    </article>


                </div>

            </section>



            <!-- ==================================================
                 QUICK ACCESS
                 ================================================== -->

            <section class="hom-dashboard-section">

                <div class="hom-dashboard-section__head">

                    <div>

                        <span>
                            میانبرها
                        </span>

                        <h2>
                            دسترسی سریع
                        </h2>

                    </div>

                </div>


                <div class="hom-dashboard-shortcuts">


                    <a href="<?php echo esc_url($orders_url); ?>">

                        <span>☰</span>

                        <div>
                            <strong>
                                مدیریت و پیگیری مشتریان
                            </strong>

                            <small>
                                پیش‌فاکتورها، سفارش‌ها،
                                پرداخت و ارسال
                            </small>
                        </div>

                    </a>


                    <a href="<?php echo esc_url($products_url); ?>">

                        <span>▦</span>

                        <div>
                            <strong>
                                مدیریت تصاویر محصولات
                            </strong>

                            <small>
                                تصویر اصلی و گالری محصولات
                            </small>
                        </div>

                    </a>


                    <a href="<?php echo esc_url($seller_url); ?>">

                        <span>⚙</span>

                        <div>
                            <strong>
                                اطلاعات
                                <?php
                                echo esc_html(
                                    self::store_name()
                                );
                                ?>
                            </strong>

                            <small>
                                مشخصات مورد استفاده
                                در اسناد فروش
                            </small>
                        </div>

                    </a>


                    <a href="<?php echo esc_url($help_url); ?>">

                        <span>?</span>

                        <div>
                            <strong>
                                راهنمای پنل
                            </strong>

                            <small>
                                آموزش استفاده از بخش‌های پنل
                            </small>
                        </div>

                    </a>


                </div>

            </section>


        </div>

        <?php
    }


    private static function render_orders_content() {

        if (
            !current_user_can(
                HOM_Capabilities::CAP_VIEW_ORDERS
            )
        ) {
            ?>
            <div class="hom-alert hom-alert-error">
                شما اجازه مشاهده سفارش‌ها را ندارید.
            </div>
            <?php

            return;
        }


        $status =
            isset($_GET['status'])
                ? sanitize_key(
                    wp_unslash(
                        $_GET['status']
                    )
                )
                : 'all';


        $page =
            isset($_GET['order_page'])
                ? max(
                    1,
                    absint(
                        $_GET['order_page']
                    )
                )
                : 1;


        $search =
            isset($_GET['q'])
                ? sanitize_text_field(
                    wp_unslash(
                        $_GET['q']
                    )
                )
                : '';


        $order_id =
            isset($_GET['order_id'])
                ? absint($_GET['order_id'])
                : 0;

        if ($order_id) {
            HOM_Order_Detail_View::render($order_id);
            return;
        }

        $result =
            HOM_Orders::query(
                $status,
                $page,
                $search
            );


        $counts =
            HOM_Orders::summary_counts();


        $customer_help_url =
            add_query_arg(
                'view',
                'help-customers',
                HOM_Router::panel_url()
            );


        $filters = [
            'all' =>
                'همه',

            'preinvoice-review' =>
                'پیش‌فاکتور جدید',

            'preinv-approved' =>
                'تأیید شده',

            'pending' =>
                'انتظار پرداخت',

            'on-hold' =>
                'در انتظار بررسی',

            'processing' =>
                'در حال آماده‌سازی',

            'hom-ready' =>
                'آماده ارسال',

            'hom-shipped' =>
                'ارسال شده',

            'completed' =>
                'تحویل شده',
        ];

        ?>

        <div class="hom-page-heading hom-orders-heading">

            <div>

                <span class="hom-eyebrow">
                    SALES & ORDERS
                </span>

                <h1>
                    مدیریت و پیگیری مشتریان
                </h1>

                <p>
                    مدیریت یکپارچه درخواست پیش‌فاکتور، اطلاعات مشتری،
                    قیمت‌گذاری، پرداخت، آماده‌سازی، ارسال و تحویل سفارش‌ها
                </p>

                <a
                    href="<?php
                    echo esc_url(
                        $customer_help_url
                    );
                    ?>"
                    class="hom-section-help-link"
                >
                    <span aria-hidden="true">👁</span>
                    <span>
                        راهنمای مدیریت و پیگیری مشتریان
                    </span>
                </a>

            </div>

            <div class="hom-products-count">

                <strong>
                    <?php
                    echo esc_html(
                        number_format_i18n(
                            absint(
                                $result['total']
                            )
                        )
                    );
                    ?>
                </strong>

                <span>
                    مورد
                </span>

            </div>

        </div>


        <form
            method="get"
            action="<?php
            echo esc_url(
                HOM_Router::panel_url()
            );
            ?>"
            class="hom-orders-search"
        >

            <input
                type="hidden"
                name="view"
                value="orders"
            >

            <input
                type="hidden"
                name="status"
                value="<?php
                echo esc_attr(
                    $status
                );
                ?>"
            >

            <input
                type="search"
                name="q"
                value="<?php
                echo esc_attr(
                    $search
                );
                ?>"
                placeholder="شماره سفارش، نام، موبایل یا کد رهگیری..."
            >

            <button
                type="submit"
                class="hom-button hom-button-secondary"
            >
                جستجو
            </button>

        </form>


        <section class="hom-order-status-grid">

            <?php
            foreach (
                $filters
                as $filter_status => $label
            ) :

                $url =
                    add_query_arg(
                        [
                            'view' =>
                                'orders',

                            'status' =>
                                $filter_status,

                            'q' =>
                                $search,
                        ],
                        HOM_Router::panel_url()
                    );

                $count =
                    absint(
                        $counts[
                            $filter_status
                        ] ?? 0
                    );
                ?>

                <a
                    href="<?php
                    echo esc_url($url);
                    ?>"
                    class="hom-order-status-card <?php
                    echo $status === $filter_status
                        ? 'is-active'
                        : '';
                    ?>"
                >

                    <strong>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                $count
                            )
                        );
                        ?>
                    </strong>

                    <span>
                        <?php
                        echo esc_html(
                            $label
                        );
                        ?>
                    </span>

                </a>

            <?php endforeach; ?>

        </section>


        <section class="hom-orders-panel">

            <?php
            if (
                empty(
                    $result['items']
                )
            ) :
                ?>

                <div class="hom-orders-empty">

                    <strong>
                        سفارشی در این بخش وجود ندارد
                    </strong>

                    <p>
                        درخواست‌های پیش‌فاکتور و سفارش‌های مشتریان در این قسمت نمایش داده می‌شوند.
                    </p>

                </div>

            <?php else : ?>

                <div class="hom-table-wrap">

                    <table class="hom-products-table hom-orders-table">

                        <thead>
                            <tr>
                                <th>پرونده</th>
                                <th>نوع</th>
                                <th>مشتری</th>
                                <th>شهر</th>
                                <th>وضعیت</th>
                                <th>مسئول</th>
                                <th>مبلغ</th>
                                <th>ارسال</th>
                                <th>تاریخ</th>
                                <th>دسترسی سریع</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php
                        foreach (
                            $result['items']
                            as $item
                        ) :

                            $row_order =
                                HOM_Orders::get_order(
                                    $item['id']
                                );


                            $detail_url =
                                HOM_Orders::detail_url(
                                    $item['id']
                                );


                            $contact =
                                $row_order
                                    ? HOM_Orders::customer_contact_data(
                                        $row_order
                                    )
                                    : [
                                        'display_name' => '',
                                        'phone' => '',
                                        'email' => '',
                                        'billing' => [
                                            'city' => '',
                                        ],
                                    ];


                            $customer_name =
                                trim(
                                    (string)
                                    (
                                        $contact['display_name']
                                        ?? ''
                                    )
                                );


                            if (
                                '' === $customer_name ||
                                'مشتری بدون نام'
                                    ===
                                    $customer_name
                            ) {

                                $customer_name =
                                    trim(
                                        (string)
                                        (
                                            $item['customer_name']
                                            ?? ''
                                        )
                                    );
                            }


                            if (
                                '' === $customer_name ||
                                'مشتری بدون نام'
                                    ===
                                    $customer_name
                            ) {

                                $customer_name =
                                    'نام ثبت نشده';
                            }


                            $customer_phone =
                                trim(
                                    (string)
                                    (
                                        $contact['phone']
                                        ?? ''
                                    )
                                );


                            if (
                                '' === $customer_phone
                            ) {

                                $customer_phone =
                                    trim(
                                        (string)
                                        (
                                            $item['phone']
                                            ?? ''
                                        )
                                    );
                            }


                            $customer_email =
                                trim(
                                    (string)
                                    (
                                        $contact['email']
                                        ?? ''
                                    )
                                );


                            $customer_city =
                                trim(
                                    (string)
                                    (
                                        $contact['billing']['city']
                                        ?? ''
                                    )
                                );


                            if (
                                '' === $customer_city
                            ) {

                                $customer_city =
                                    trim(
                                        (string)
                                        (
                                            $item['city']
                                            ?? ''
                                        )
                                    );
                            }


                            $tracking_code =
                                $row_order
                                    ? trim(
                                        (string)
                                        $row_order->get_meta(
                                            '_hom_shipping_tracking_code',
                                            true
                                        )
                                    )
                                    : '';


                            $invoice_url =
                                $row_order
                                    ? HOM_Order_Documents::url(
                                        $item['id'],
                                        'invoice'
                                    )
                                    : '';


                            $warehouse_url =
                                $row_order
                                    ? HOM_Order_Documents::url(
                                        $item['id'],
                                        'warehouse'
                                    )
                                    : '';


                            $shipping_url =
                                $row_order
                                    ? HOM_Order_Documents::url(
                                        $item['id'],
                                        'shipping'
                                    )
                                    : '';
                            ?>

                            <tr
                                class="hom-order-row"
                                data-href="<?php
                                echo esc_url(
                                    $detail_url
                                );
                                ?>"
                                tabindex="0"
                                aria-label="<?php
                                echo esc_attr(
                                    'باز کردن پرونده شماره ' .
                                    $item['number']
                                );
                                ?>"
                            >

                                <td data-label="پرونده">

                                    <div class="hom-order-number-cell">

                                        <strong>

                                            <a
                                                href="<?php
                                                echo esc_url(
                                                    $detail_url
                                                );
                                                ?>"
                                                class="hom-order-number-link"
                                            >
                                                #<?php
                                                echo esc_html(
                                                    $item['number']
                                                );
                                                ?>
                                            </a>

                                        </strong>

                                        <span>
                                            مشاهده پرونده
                                        </span>

                                    </div>

                                </td>


                                <td data-label="نوع">

                                    <span class="hom-order-type-badge">
                                        <?php
                                        echo esc_html(
                                            $item['is_preinvoice']
                                                ? 'پیش‌فاکتور'
                                                : 'سفارش'
                                        );
                                        ?>
                                    </span>

                                </td>


                                <td data-label="مشتری">

                                    <div class="hom-order-customer">

                                        <strong>
                                            <?php
                                            echo esc_html(
                                                $customer_name
                                            );
                                            ?>
                                        </strong>


                                        <?php
                                        if ($customer_phone) :
                                            ?>

                                            <span
                                                dir="ltr"
                                                class="hom-order-customer__phone"
                                            >
                                                <?php
                                                echo esc_html(
                                                    $customer_phone
                                                );
                                                ?>
                                            </span>

                                        <?php
                                        elseif ($customer_email) :
                                            ?>

                                            <span
                                                dir="ltr"
                                                class="hom-order-customer__email"
                                            >
                                                <?php
                                                echo esc_html(
                                                    $customer_email
                                                );
                                                ?>
                                            </span>

                                        <?php else : ?>

                                            <span class="hom-order-customer__missing">
                                                اطلاعات تماس تکمیل نشده
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <td data-label="شهر">

                                    <?php if ($customer_city) : ?>

                                        <?php
                                        echo esc_html(
                                            $customer_city
                                        );
                                        ?>

                                    <?php else : ?>

                                        <span class="hom-order-list-muted">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td data-label="وضعیت">

                                    <span class="hom-order-status-badge">
                                        <?php
                                        echo esc_html(
                                            $item['status_label']
                                        );
                                        ?>
                                    </span>

                                </td>


                                <td data-label="مسئول">

                                    <div class="hom-order-assignee">

                                        <strong>
                                            <?php
                                            echo esc_html(
                                                !empty(
                                                    $item['assignee']['name']
                                                )
                                                    ? $item['assignee']['name']
                                                    : 'تعیین نشده'
                                            );
                                            ?>
                                        </strong>

                                    </div>

                                </td>


                                <td data-label="مبلغ">

                                    <strong class="hom-order-list-total">
                                        <?php
                                        echo wp_kses_post(
                                            $item['total_html']
                                        );
                                        ?>
                                    </strong>

                                </td>


                                <td data-label="ارسال">

                                    <div class="hom-order-shipping-summary">

                                        <strong>
                                            <?php
                                            echo esc_html(
                                                $item['shipping_method']
                                                    ?: 'تعیین نشده'
                                            );
                                            ?>
                                        </strong>


                                        <?php if ($tracking_code) : ?>

                                            <span dir="ltr">
                                                رهگیری:
                                                <?php
                                                echo esc_html(
                                                    $tracking_code
                                                );
                                                ?>
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <td data-label="تاریخ">

                                    <span
                                        class="hom-order-list-date"
                                        dir="ltr"
                                    >
                                        <?php
                                        echo esc_html(
                                            $item['date']
                                        );
                                        ?>
                                    </span>

                                </td>


                                <td data-label="دسترسی سریع">

                                    <?php if ($row_order) : ?>

                                        <div class="hom-order-row-actions">

                                            <a
                                                href="<?php
                                                echo esc_url(
                                                    $invoice_url
                                                );
                                                ?>"
                                                target="_blank"
                                                rel="noopener"
                                                class="hom-order-row-action"
                                                title="مشاهده فاکتور"
                                                aria-label="مشاهده فاکتور"
                                            >
                                                فاکتور
                                            </a>


                                            <a
                                                href="<?php
                                                echo esc_url(
                                                    $warehouse_url
                                                );
                                                ?>"
                                                target="_blank"
                                                rel="noopener"
                                                class="hom-order-row-action"
                                                title="برگه انبار بدون قیمت"
                                                aria-label="برگه انبار بدون قیمت"
                                            >
                                                انبار
                                            </a>


                                            <a
                                                href="<?php
                                                echo esc_url(
                                                    $shipping_url
                                                );
                                                ?>"
                                                target="_blank"
                                                rel="noopener"
                                                class="hom-order-row-action"
                                                title="برچسب ارسال"
                                                aria-label="برچسب ارسال"
                                            >
                                                برچسب
                                            </a>

                                        </div>

                                    <?php else : ?>

                                        <span class="hom-order-list-muted">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>


                    <script>
                    (function () {

                        if (
                            window.homOrderRowsReady
                        ) {
                            return;
                        }

                        window.homOrderRowsReady = true;


                        function interactiveTarget(
                            target
                        ) {

                            return target.closest(
                                'a, button, input, select, textarea, label, summary, details'
                            );

                        }


                        document.addEventListener(
                            'click',
                            function (event) {

                                var row =
                                    event.target.closest(
                                        '.hom-order-row[data-href]'
                                    );


                                if (
                                    !row ||
                                    interactiveTarget(
                                        event.target
                                    )
                                ) {
                                    return;
                                }


                                window.location.href =
                                    row.dataset.href;

                            }
                        );


                        document.addEventListener(
                            'keydown',
                            function (event) {

                                if (
                                    event.key !== 'Enter' &&
                                    event.key !== ' '
                                ) {
                                    return;
                                }


                                var row =
                                    event.target.closest(
                                        '.hom-order-row[data-href]'
                                    );


                                if (!row) {
                                    return;
                                }


                                event.preventDefault();

                                window.location.href =
                                    row.dataset.href;

                            }
                        );

                    }());
                    </script>

                </div>

            <?php endif; ?>

        </section>

        <?php
    }



    private static function render_help_index_content() {

        $customer_help_url =
            add_query_arg(
                'view',
                'help-customers',
                HOM_Router::panel_url()
            );


        $images_help_url =
            add_query_arg(
                'view',
                'help-product-images',
                HOM_Router::panel_url()
            );

        ?>

        <div class="hom-help-page">

            <section class="hom-help-hero">

                <div class="hom-help-hero__icon">
                    ?
                </div>

                <div class="hom-help-hero__content">

                    <span class="hom-help-kicker">
                        مرکز راهنمای پنل فروشگاه
                    </span>

                    <h1>
                        راهنمای پنل مدیریت فروشگاه
                    </h1>

                    <p>
                        راهنمای بخش موردنظر را انتخاب کنید.
                        هر راهنما در صفحه‌ای مستقل قرار دارد تا
                        با اضافه شدن امکانات جدید، بتوان راهنماهای
                        بیشتری بدون شلوغ شدن منوی اصلی به این بخش
                        اضافه کرد.
                    </p>

                </div>

            </section>


            <section class="hom-help-branch-grid">

                <a
                    href="<?php
                    echo esc_url(
                        $customer_help_url
                    );
                    ?>"
                    class="hom-help-branch-card is-primary"
                >

                    <span class="hom-help-branch-card__icon">
                        👥
                    </span>

                    <div>

                        <span class="hom-help-kicker">
                            پیش‌فاکتور و سفارش
                        </span>

                        <h2>
                            راهنمای مدیریت و پیگیری مشتریان
                        </h2>

                        <p>
                            اطلاعات مشتری، قیمت‌گذاری،
                            تأیید پیش‌فاکتور، پرداخت،
                            آماده‌سازی، ارسال، تحویل،
                            اصلاحات، اسناد و رخدادها.
                        </p>

                    </div>

                    <span class="hom-help-branch-card__arrow">
                        ←
                    </span>

                </a>


                <a
                    href="<?php
                    echo esc_url(
                        $images_help_url
                    );
                    ?>"
                    class="hom-help-branch-card"
                >

                    <span class="hom-help-branch-card__icon">
                        🖼️
                    </span>

                    <div>

                        <span class="hom-help-kicker">
                            محصولات
                        </span>

                        <h2>
                            راهنمای مدیریت تصاویر محصولات
                        </h2>

                        <p>
                            جستجوی محصول، تصویر اصلی،
                            گالری، ویرایش تصویر، واترمارک،
                            آپلود و ذخیره نهایی.
                        </p>

                    </div>

                    <span class="hom-help-branch-card__arrow">
                        ←
                    </span>

                </a>

            </section>

        </div>

        <?php
    }




    private static function render_help_customers_content() {

        $orders_url =
            add_query_arg(
                'view',
                'orders',
                HOM_Router::panel_url()
            );


        $help_index_url =
            add_query_arg(
                'view',
                'help',
                HOM_Router::panel_url()
            );

        ?>

        <div class="hom-help-page hom-customer-help-page">


            <section class="hom-help-hero">

                <div>

                    <span class="hom-help-hero__eyebrow">
                        راهنمای کار روزانه واحد فروش
                    </span>

                    <h1>
                        راهنمای مدیریت و پیگیری مشتریان
                    </h1>

                    <p>
                        این بخش برای مدیریت پرونده مشتری از زمان
                        ثبت پیش‌فاکتور تا قیمت‌گذاری، پرداخت،
                        آماده‌سازی، ارسال و تحویل طراحی شده است.
                        لازم نیست همه قسمت‌های صفحه را همیشه باز کنید؛
                        فقط بخشی را باز کنید که برای اقدام فعلی شما لازم است.
                    </p>

                </div>


                <div class="hom-help-hero__actions">

                    <a
                        href="<?php echo esc_url($orders_url); ?>"
                        class="hom-help-action hom-help-action-primary"
                    >
                        رفتن به مدیریت مشتریان
                    </a>

                    <a
                        href="<?php echo esc_url($help_index_url); ?>"
                        class="hom-help-action hom-help-action-secondary"
                    >
                        ← بازگشت به راهنمای پنل
                    </a>

                </div>

            </section>



            <section class="hom-help-topic">

                <div class="hom-help-topic__heading">

                    <span class="hom-help-topic__icon">
                        🧭
                    </span>

                    <div>

                        <span class="hom-help-topic__eyebrow">
                            قبل از شروع
                        </span>

                        <h2>
                            منطق صفحه را در یک نگاه بشناسید
                        </h2>

                    </div>

                </div>


                <div class="hom-customer-help-principles">

                    <article>

                        <strong>
                            اطلاعات مهم همیشه دیده می‌شوند
                        </strong>

                        <p>
                            وضعیت پرونده، مشتری، مسئول پرونده،
                            مبلغ و خلاصه عملیات بدون باز کردن
                            فرم‌ها قابل مشاهده هستند.
                        </p>

                    </article>


                    <article>

                        <strong>
                            فرم‌ها فقط هنگام نیاز باز می‌شوند
                        </strong>

                        <p>
                            قیمت‌گذاری، پرداخت، ارسال، اصلاحات
                            و جزئیات اضافی داخل بخش‌های جمع‌شونده
                            قرار دارند تا صفحه شلوغ نشود.
                        </p>

                    </article>


                    <article>

                        <strong>
                            سوابق برای بازرسی است، نه کار روزانه
                        </strong>

                        <p>
                            برای پیگیری سفارش از وضعیت و مراحل سفارش
                            استفاده کنید. «سوابق و رخدادها» فقط برای
                            بررسی اتفاقات گذشته است.
                        </p>

                    </article>

                </div>

            </section>



            <section class="hom-help-topic">

                <div class="hom-help-topic__heading">

                    <span class="hom-help-topic__icon">
                        🗺️
                    </span>

                    <div>

                        <span class="hom-help-topic__eyebrow">
                            نقشه صفحه
                        </span>

                        <h2>
                            هر قسمت صفحه چه کاری انجام می‌دهد؟
                        </h2>

                    </div>

                </div>


                <div class="hom-customer-help-map">

                    <article>
                        <span>۱</span>

                        <div>
                            <strong>
                                سربرگ پرونده
                            </strong>

                            <p>
                                شماره سفارش، وضعیت، مبلغ،
                                مشتری، مسئول پرونده و دسترسی
                                سریع به اسناد.
                            </p>
                        </div>
                    </article>


                    <article>
                        <span>۲</span>

                        <div>
                            <strong>
                                اطلاعات مشتری
                            </strong>

                            <p>
                                نام، تلفن، ایمیل و مشخصات پایه
                                برای شناسایی سریع مشتری.
                            </p>
                        </div>
                    </article>


                    <article>
                        <span>۳</span>

                        <div>
                            <strong>
                                اطلاعات حقوقی خریدار
                            </strong>

                            <p>
                                اطلاعات موردنیاز فاکتور رسمی،
                                شرکت، شناسه ملی، کد اقتصادی،
                                کدپستی و آدرس.
                            </p>
                        </div>
                    </article>


                    <article>
                        <span>۴</span>

                        <div>
                            <strong>
                                اقلام و قیمت‌گذاری
                            </strong>

                            <p>
                                مشاهده کالاها و در صورت نیاز
                                ثبت یا اصلاح قیمت و هزینه ارسال.
                            </p>
                        </div>
                    </article>


                    <article>
                        <span>۵</span>

                        <div>
                            <strong>
                                پرداخت
                            </strong>

                            <p>
                                مشاهده خلاصه پرداخت و در صورت
                                نیاز ثبت یا اصلاح اطلاعات واریز.
                            </p>
                        </div>
                    </article>


                    <article>
                        <span>۶</span>

                        <div>
                            <strong>
                                ارسال و تحویل
                            </strong>

                            <p>
                                ثبت روش ارسال، باربری،
                                رهگیری، کرایه و پیشرفت
                                وضعیت ارسال.
                            </p>
                        </div>
                    </article>


                    <article>
                        <span>۷</span>

                        <div>
                            <strong>
                                مراحل سفارش
                            </strong>

                            <p>
                                مسیر کلی پرونده و مراحل طی‌شده
                                را به‌صورت خلاصه نشان می‌دهد.
                            </p>
                        </div>
                    </article>


                    <article>
                        <span>۸</span>

                        <div>
                            <strong>
                                سوابق و رخدادها
                            </strong>

                            <p>
                                برای بررسی دقیق اینکه چه کاری،
                                چه زمانی و توسط چه کسی انجام شده است.
                            </p>
                        </div>
                    </article>

                </div>

            </section>



            <section class="hom-help-topic">

                <div class="hom-help-topic__heading">

                    <span class="hom-help-topic__icon">
                        🚦
                    </span>

                    <div>

                        <span class="hom-help-topic__eyebrow">
                            تشخیص اقدام بعدی
                        </span>

                        <h2>
                            وضعیت پرونده به شما می‌گوید چه کاری لازم است
                        </h2>

                    </div>

                </div>


                <div class="hom-help-status-table">

                    <div class="hom-help-status-row">
                        <strong>پیش‌فاکتور جدید</strong>
                        <span>
                            اطلاعات مشتری را بررسی کنید،
                            سپس اقلام را قیمت‌گذاری کنید.
                        </span>
                    </div>


                    <div class="hom-help-status-row">
                        <strong>تأیید شده</strong>
                        <span>
                            قیمت‌گذاری انجام شده و پرونده
                            برای ادامه فرایند پرداخت آماده است.
                        </span>
                    </div>


                    <div class="hom-help-status-row">
                        <strong>انتظار پرداخت</strong>
                        <span>
                            وضعیت پرداخت مشتری را بررسی کنید.
                        </span>
                    </div>


                    <div class="hom-help-status-row">
                        <strong>در انتظار بررسی</strong>
                        <span>
                            پرونده نیازمند بررسی واحد فروش است.
                        </span>
                    </div>


                    <div class="hom-help-status-row">
                        <strong>در حال آماده‌سازی</strong>
                        <span>
                            سفارش وارد عملیات تأمین،
                            جمع‌آوری یا بسته‌بندی شده است.
                        </span>
                    </div>


                    <div class="hom-help-status-row">
                        <strong>آماده ارسال</strong>
                        <span>
                            اطلاعات ارسال را کنترل و
                            ارسال سفارش را ثبت کنید.
                        </span>
                    </div>


                    <div class="hom-help-status-row">
                        <strong>ارسال شده</strong>
                        <span>
                            کد رهگیری و وضعیت تحویل
                            را در صورت نیاز بررسی کنید.
                        </span>
                    </div>


                    <div class="hom-help-status-row">
                        <strong>تحویل شده</strong>
                        <span>
                            عملیات اصلی پرونده پایان یافته است.
                            فقط در صورت نیاز سوابق را بررسی کنید.
                        </span>
                    </div>

                </div>

            </section>



            <section class="hom-help-topic">

                <div class="hom-help-topic__heading">

                    <span class="hom-help-topic__icon">
                        👤
                    </span>

                    <div>

                        <span class="hom-help-topic__eyebrow">
                            شناخت مشتری
                        </span>

                        <h2>
                            اطلاعات مشتری و اطلاعات حقوقی
                        </h2>

                    </div>

                </div>


                <div class="hom-help-two-column">

                    <article class="hom-help-info-panel">

                        <h3>
                            اطلاعات مشتری
                        </h3>

                        <p>
                            این قسمت برای شناسایی سریع شخصی است
                            که پرونده به او مربوط می‌شود.
                        </p>

                        <ul>
                            <li>نام مشتری</li>
                            <li>شماره تماس</li>
                            <li>ایمیل</li>
                            <li>شناسه مشتری</li>
                        </ul>

                        <p>
                            اگر اطلاعاتی موجود نباشد،
                            عبارت «تکمیل نشده» نمایش داده می‌شود.
                            این یک خطای سیستم نیست.
                        </p>

                    </article>


                    <article class="hom-help-info-panel">

                        <h3>
                            اطلاعات حقوقی خریدار
                        </h3>

                        <p>
                            این اطلاعات برای فاکتور و امور
                            مالی و حقوقی خریدار استفاده می‌شود.
                        </p>

                        <ul>
                            <li>نام حقوقی یا شرکت</li>
                            <li>شناسه ملی</li>
                            <li>کد اقتصادی</li>
                            <li>شماره ثبت</li>
                            <li>کدپستی</li>
                            <li>آدرس فاکتور</li>
                        </ul>

                    </article>

                </div>


                <div class="hom-help-important">

                    <strong>
                        برای ویرایش اطلاعات چه کار کنم؟
                    </strong>

                    <p>
                        روی «ویرایش اطلاعات حقوقی» کلیک کنید.
                        فرم فقط در همان زمان باز می‌شود.
                        پس از پایان کار آن را دوباره ببندید
                        تا صفحه خلوت باقی بماند.
                    </p>

                </div>

            </section>



            <section class="hom-help-topic">

                <div class="hom-help-topic__heading">

                    <span class="hom-help-topic__icon">
                        💰
                    </span>

                    <div>

                        <span class="hom-help-topic__eyebrow">
                            عملیات فروش
                        </span>

                        <h2>
                            اقلام و قیمت‌گذاری پیش‌فاکتور
                        </h2>

                    </div>

                </div>


                <div class="hom-help-flow">

                    <article>
                        <span>۱</span>
                        <div>
                            <strong>
                                آکاردئون اقلام و قیمت‌گذاری را باز کنید
                            </strong>
                            <p>
                                در حالت عادی بسته است و فقط
                                تعداد اقلام و مبلغ کل را نشان می‌دهد.
                            </p>
                        </div>
                    </article>


                    <article>
                        <span>۲</span>
                        <div>
                            <strong>
                                اقلام و تعداد را کنترل کنید
                            </strong>
                            <p>
                                نام محصول، SKU، تعداد،
                                قیمت واحد و جمع هر ردیف
                                در جدول دیده می‌شود.
                            </p>
                        </div>
                    </article>


                    <article>
                        <span>۳</span>
                        <div>
                            <strong>
                                قیمت‌ها را وارد کنید
                            </strong>
                            <p>
                                فقط در زمانی که پرونده نیازمند
                                قیمت‌گذاری است قیمت واحد
                                و هزینه ارسال را ثبت کنید.
                            </p>
                        </div>
                    </article>


                    <article>
                        <span>۴</span>
                        <div>
                            <strong>
                                ذخیره و تأیید کنید
                            </strong>
                            <p>
                                ابتدا قیمت‌ها را ذخیره کنید.
                                سپس در زمان مناسب پیش‌فاکتور
                                را برای پرداخت تأیید کنید.
                            </p>
                        </div>
                    </article>

                </div>


                <div class="hom-help-warning">

                    اگر قیمت قبلاً ثبت شده باشد و بخواهید
                    آن را تغییر دهید، سیستم دلیل اصلاح می‌خواهد.
                    دلیل را کوتاه، دقیق و قابل فهم بنویسید؛
                    مانند «اصلاح قیمت طبق اعلام تأمین‌کننده».
                </div>

            </section>



            <section class="hom-help-topic">

                <div class="hom-help-topic__heading">

                    <span class="hom-help-topic__icon">
                        💳
                    </span>

                    <div>

                        <span class="hom-help-topic__eyebrow">
                            امور مالی
                        </span>

                        <h2>
                            بخش پرداخت چگونه استفاده می‌شود؟
                        </h2>

                    </div>

                </div>


                <div class="hom-help-important">

                    <strong>
                        ابتدا خلاصه پرداخت را نگاه کنید.
                    </strong>

                    <p>
                        بدون باز کردن هیچ فرمی می‌توانید
                        مبلغ، مرجع پرداخت، ثبت‌کننده،
                        زمان ثبت و وضعیت تأیید را ببینید.
                    </p>

                </div>


                <div class="hom-help-flow">

                    <article>
                        <span>۱</span>
                        <div>
                            <strong>
                                پرداخت هنوز ثبت نشده
                            </strong>
                            <p>
                                فقط پس از مشاهده و اطمینان
                                از واریز واقعی مشتری،
                                «ثبت و تأیید پرداخت دستی»
                                را باز کنید.
                            </p>
                        </div>
                    </article>


                    <article>
                        <span>۲</span>
                        <div>
                            <strong>
                                مبلغ و مرجع پرداخت
                            </strong>
                            <p>
                                مبلغ دریافتی و شماره پیگیری
                                یا مرجع بانکی را با دقت ثبت کنید.
                            </p>
                        </div>
                    </article>


                    <article>
                        <span>۳</span>
                        <div>
                            <strong>
                                پرداخت قبلاً ثبت شده
                            </strong>
                            <p>
                                فرم اصلاح را فقط زمانی باز کنید
                                که مرجع یا توضیحات پرداخت
                                اشتباه ثبت شده باشد.
                            </p>
                        </div>
                    </article>

                </div>


                <div class="hom-help-warning">

                    تأیید پرداخت یک اقدام مهم مالی است.
                    قبل از ثبت، صرفاً به گفته مشتری اکتفا نکنید
                    و واریز را از روش مورد تأیید شرکت بررسی کنید.
                </div>

            </section>



            <section class="hom-help-topic">

                <div class="hom-help-topic__heading">

                    <span class="hom-help-topic__icon">
                        🖨️
                    </span>

                    <div>

                        <span class="hom-help-topic__eyebrow">
                            دسترسی سریع
                        </span>

                        <h2>
                            فاکتور، برگه انبار و برچسب ارسال
                        </h2>

                    </div>

                </div>


                <div class="hom-help-document-grid">

                    <article>

                        <span>🧾</span>

                        <div>
                            <strong>
                                فاکتور
                            </strong>

                            <p>
                                برای مشاهده یا چاپ سند مالی
                                مشتری استفاده کنید.
                            </p>
                        </div>

                    </article>


                    <article>

                        <span>📦</span>

                        <div>
                            <strong>
                                برگه انبار بدون قیمت
                            </strong>

                            <p>
                                برای تحویل به انبار و آماده‌سازی
                                کالا بدون نمایش اطلاعات مالی.
                            </p>
                        </div>

                    </article>


                    <article>

                        <span>🏷️</span>

                        <div>
                            <strong>
                                برچسب ارسال
                            </strong>

                            <p>
                                برای آماده‌سازی بسته و اطلاعات
                                موردنیاز ارسال استفاده کنید.
                            </p>
                        </div>

                    </article>

                </div>


                <div class="hom-help-important">

                    این سه گزینه در بالای پرونده قرار دارند؛
                    لازم نیست برای چاپ اسناد در صفحه
                    به دنبال بخش دیگری بگردید.
                </div>

            </section>



            <section class="hom-help-topic">

                <div class="hom-help-topic__heading">

                    <span class="hom-help-topic__icon">
                        🚚
                    </span>

                    <div>

                        <span class="hom-help-topic__eyebrow">
                            عملیات ارسال
                        </span>

                        <h2>
                            ارسال و تحویل سفارش
                        </h2>

                    </div>

                </div>


                <p class="hom-help-lead">
                    در حالت بسته، مهم‌ترین اطلاعات ارسال
                    به‌صورت خلاصه نمایش داده می‌شود.
                    برای انجام عملیات، بخش را باز کنید.
                </p>


                <div class="hom-help-field-guide">

                    <div>
                        <strong>روش ارسال</strong>
                        <span>
                            نوع ارسال انتخاب‌شده برای سفارش.
                        </span>
                    </div>

                    <div>
                        <strong>شرکت / باربری / شعبه</strong>
                        <span>
                            نام شرکت حمل، پیک، باربری
                            یا شعبه مقصد.
                        </span>
                    </div>

                    <div>
                        <strong>کد رهگیری / بارنامه</strong>
                        <span>
                            شماره‌ای که برای پیگیری ارسال
                            استفاده می‌شود.
                        </span>
                    </div>

                    <div>
                        <strong>وضعیت کرایه</strong>
                        <span>
                            مشخص می‌کند هزینه حمل پرداخت شده
                            یا پس‌کرایه است.
                        </span>
                    </div>

                    <div>
                        <strong>توضیحات ارسال</strong>
                        <span>
                            فقط نکات ضروری و کاربردی ارسال
                            را در این قسمت بنویسید.
                        </span>
                    </div>

                </div>


                <div class="hom-help-warning">

                    اگر فقط مرحله سفارش را جلو می‌برید
                    و اطلاعات ارسال را تغییر نداده‌اید،
                    لازم نیست دلیل اصلاح بنویسید.
                    دلیل اصلاح فقط برای تغییر اطلاعات
                    قبلاً ثبت‌شده است.
                </div>

            </section>



            <section class="hom-help-topic">

                <div class="hom-help-topic__heading">

                    <span class="hom-help-topic__icon">
                        🪜
                    </span>

                    <div>

                        <span class="hom-help-topic__eyebrow">
                            مسیر پرونده
                        </span>

                        <h2>
                            بخش «مراحل سفارش» چه کاربردی دارد؟
                        </h2>

                    </div>

                </div>


                <div class="hom-help-important">

                    <p>
                        اگر می‌خواهید سریع بفهمید پرونده
                        تا کجا پیش رفته است،
                        ابتدا «مراحل سفارش» را باز کنید.
                    </p>

                    <p>
                        این بخش خلاصه مسیر پرونده است
                        و برای پیگیری روزانه بسیار مناسب‌تر
                        از بخش سوابق و رخدادهاست.
                    </p>

                </div>

            </section>



            <section
                id="hom-help-customer-audit"
                class="hom-help-topic hom-help-audit-guide"
            >

                <div class="hom-help-topic__heading">

                    <span class="hom-help-topic__icon">
                        🕘
                    </span>

                    <div>

                        <span class="hom-help-topic__eyebrow">
                            بررسی سابقه پرونده
                        </span>

                        <h2>
                            «سوابق و رخدادها» دقیقاً برای چه کاری است؟
                        </h2>

                    </div>

                </div>


                <div class="hom-help-audit-important">

                    <strong>
                        این بخش محل پیگیری روزانه سفارش نیست.
                    </strong>

                    <p>
                        اگر می‌خواهید بدانید سفارش در چه مرحله‌ای است،
                        ابتدا وضعیت بالای پرونده و سپس
                        «مراحل سفارش» را بررسی کنید.
                    </p>

                    <p>
                        «سوابق و رخدادها» زمانی استفاده می‌شود
                        که بخواهید اتفاقات گذشته پرونده را
                        مانند یک گزارش دقیق بررسی کنید.
                    </p>

                </div>


                <div class="hom-help-question-grid">

                    <article>
                        <span>؟</span>
                        <p>
                            چه کسی قیمت را تغییر داده است؟
                        </p>
                    </article>

                    <article>
                        <span>؟</span>
                        <p>
                            پرداخت در چه زمانی تأیید شده است؟
                        </p>
                    </article>

                    <article>
                        <span>؟</span>
                        <p>
                            چه کسی شماره پیگیری را اصلاح کرده است؟
                        </p>
                    </article>

                    <article>
                        <span>؟</span>
                        <p>
                            اطلاعات مشتری قبلاً چه مقداری داشته است؟
                        </p>
                    </article>

                </div>


                <div class="hom-help-audit-grid">

                    <article>

                        <span class="hom-help-audit-number">
                            ۱
                        </span>

                        <div>

                            <h3>
                                سوابق و رخدادها را باز کنید
                            </h3>

                            <p>
                                این قسمت عمداً در حالت عادی بسته است
                                تا صفحه کار شما شلوغ نشود.
                            </p>

                        </div>

                    </article>


                    <article>

                        <span class="hom-help-audit-number">
                            ۲
                        </span>

                        <div>

                            <h3>
                                ردیف موردنظر را پیدا کنید
                            </h3>

                            <p>
                                در هر ردیف، عنوان رخداد،
                                نام انجام‌دهنده و زمان اقدام
                                نمایش داده می‌شود.
                            </p>

                        </div>

                    </article>


                    <article>

                        <span class="hom-help-audit-number">
                            ۳
                        </span>

                        <div>

                            <h3>
                                همان رخداد را باز کنید
                            </h3>

                            <p>
                                فقط جزئیات همان اتفاق باز می‌شود
                                و سایر رخدادها همچنان جمع می‌مانند.
                            </p>

                        </div>

                    </article>


                    <article>

                        <span class="hom-help-audit-number">
                            ۴
                        </span>

                        <div>

                            <h3>
                                قبل و بعد را مقایسه کنید
                            </h3>

                            <p>
                                اگر تغییری انجام شده باشد،
                                مقدار قبل و بعد را کنار هم
                                مشاهده خواهید کرد.
                            </p>

                        </div>

                    </article>


                    <article>

                        <span class="hom-help-audit-number">
                            ۵
                        </span>

                        <div>

                            <h3>
                                دلیل اصلاح را بخوانید
                            </h3>

                            <p>
                                برای تغییرات اصلاحی،
                                علت ثبت‌شده توسط اپراتور
                                نیز در همان رخداد دیده می‌شود.
                            </p>

                        </div>

                    </article>


                    <article>

                        <span class="hom-help-audit-number">
                            ۶
                        </span>

                        <div>

                            <h3>
                                انجام‌دهنده را شناسایی کنید
                            </h3>

                            <p>
                                نام کاربر، نقش او،
                                حساب کاربری و منبع ثبت عملیات
                                در جزئیات رخداد قابل مشاهده است.
                            </p>

                        </div>

                    </article>

                </div>


                <div class="hom-help-audit-example">

                    <strong>
                        مثال عملی:
                    </strong>

                    فرض کنید شماره پیگیری پرداخت با چیزی
                    که اکنون در پرونده دیده می‌شود متفاوت است.
                    برای فهمیدن علت، وارد «سوابق و رخدادها» شوید،
                    رخداد مربوط به پرداخت را پیدا کنید و باز کنید.
                    سپس مقدار قبلی، مقدار جدید، زمان تغییر،
                    نام انجام‌دهنده و دلیل اصلاح را بررسی کنید.

                </div>

            </section>



            <section class="hom-help-topic">

                <div class="hom-help-topic__heading">

                    <span class="hom-help-topic__icon">
                        ✏️
                    </span>

                    <div>

                        <span class="hom-help-topic__eyebrow">
                            اصلاح اطلاعات
                        </span>

                        <h2>
                            دلیل اصلاح را چگونه بنویسیم؟
                        </h2>

                    </div>

                </div>


                <div class="hom-help-do-dont">

                    <div class="is-good">

                        <strong>
                            مناسب
                        </strong>

                        <ul>
                            <li>
                                اصلاح قیمت طبق اعلام تأمین‌کننده
                            </li>

                            <li>
                                اصلاح شماره پیگیری ثبت‌شده اشتباه
                            </li>

                            <li>
                                تغییر باربری طبق درخواست مشتری
                            </li>

                            <li>
                                اصلاح شناسه ملی طبق مدرک مشتری
                            </li>
                        </ul>

                    </div>


                    <div class="is-bad">

                        <strong>
                            نامناسب
                        </strong>

                        <ul>
                            <li>اصلاح شد</li>
                            <li>اشتباه بود</li>
                            <li>تغییر</li>
                            <li>اوکی شد</li>
                        </ul>

                    </div>

                </div>


                <p class="hom-help-lead">
                    هدف از دلیل اصلاح این است که اگر چند روز
                    یا چند ماه بعد شخص دیگری پرونده را بررسی کرد،
                    بدون سؤال از اپراتور قبلی متوجه علت تغییر شود.
                </p>

            </section>



            <section class="hom-help-topic">

                <div class="hom-help-topic__heading">

                    <span class="hom-help-topic__icon">
                        🔍
                    </span>

                    <div>

                        <span class="hom-help-topic__eyebrow">
                            پیدا کردن سریع پاسخ
                        </span>

                        <h2>
                            دنبال چه چیزی هستید؟
                        </h2>

                    </div>

                </div>


                <div class="hom-help-find-answer">

                    <div>
                        <strong>
                            سفارش الان در چه مرحله‌ای است؟
                        </strong>
                        <span>
                            وضعیت بالای پرونده + مراحل سفارش
                        </span>
                    </div>


                    <div>
                        <strong>
                            مشتری چه کسی است؟
                        </strong>
                        <span>
                            اطلاعات مشتری
                        </span>
                    </div>


                    <div>
                        <strong>
                            اطلاعات فاکتور رسمی چیست؟
                        </strong>
                        <span>
                            اطلاعات حقوقی خریدار
                        </span>
                    </div>


                    <div>
                        <strong>
                            قیمت‌ها چقدر هستند؟
                        </strong>
                        <span>
                            اقلام و قیمت‌گذاری
                        </span>
                    </div>


                    <div>
                        <strong>
                            پرداخت تأیید شده است؟
                        </strong>
                        <span>
                            خلاصه پرداخت
                        </span>
                    </div>


                    <div>
                        <strong>
                            سفارش چگونه ارسال شده؟
                        </strong>
                        <span>
                            ارسال و تحویل
                        </span>
                    </div>


                    <div>
                        <strong>
                            چه کسی این اطلاعات را تغییر داده؟
                        </strong>
                        <span>
                            سوابق و رخدادها
                        </span>
                    </div>


                    <div>
                        <strong>
                            فاکتور یا برگه انبار می‌خواهم
                        </strong>
                        <span>
                            دسترسی سریع بالای پرونده
                        </span>
                    </div>

                </div>

            </section>



            <section class="hom-help-topic">

                <div class="hom-help-topic__heading">

                    <span class="hom-help-topic__icon">
                        ✅
                    </span>

                    <div>

                        <span class="hom-help-topic__eyebrow">
                            روال پیشنهادی
                        </span>

                        <h2>
                            روش ساده کار با هر پرونده
                        </h2>

                    </div>

                </div>


                <ol class="hom-help-daily-checklist">

                    <li>
                        ابتدا وضعیت بالای پرونده را ببینید.
                    </li>

                    <li>
                        نام مشتری و اطلاعات ضروری را کنترل کنید.
                    </li>

                    <li>
                        فقط بخش مربوط به اقدام فعلی را باز کنید.
                    </li>

                    <li>
                        عملیات موردنیاز را انجام و ذخیره کنید.
                    </li>

                    <li>
                        وضعیت جدید پرونده را بررسی کنید.
                    </li>

                    <li>
                        اگر نیاز به سند دارید از دسترسی سریع
                        بالای صفحه استفاده کنید.
                    </li>

                    <li>
                        فقط در صورت وجود سؤال درباره اتفاقات گذشته،
                        به «سوابق و رخدادها» مراجعه کنید.
                    </li>

                </ol>

            </section>



            <?php
            self::render_help_security_content();
            ?>


            <div class="hom-help-page-back">

                <a
                    href="<?php echo esc_url($help_index_url); ?>"
                    class="hom-help-action hom-help-action-secondary"
                >
                    <span aria-hidden="true">←</span>
                    بازگشت به راهنمای پنل
                </a>

            </div>


        </div>

        <?php
    }



    private static function render_help_product_images_content() {

        $products_url =
            add_query_arg(
                'view',
                'products',
                HOM_Router::panel_url()
            );


        $help_index_url =
            add_query_arg(
                'view',
                'help',
                HOM_Router::panel_url()
            );

        ?>

        <div class="hom-help-page">

            <section class="hom-help-hero">

                <div class="hom-help-hero__icon">
                    🖼️
                </div>

                <div class="hom-help-hero__content">

                    <span class="hom-help-kicker">
                        راهنمای مدیریت تصاویر محصولات
                    </span>

                    <h1>
                        راهنمای کامل مدیریت تصاویر محصولات
                    </h1>

                    <p>
                        در این صفحه روش پیدا کردن محصول صحیح،
                        کنترل کدهای فنی، انتخاب تصویر اصلی،
                        مدیریت گالری، کار با ویرایشگر، واترمارک،
                        آپلود و ذخیره نهایی تصاویر توضیح داده شده است.
                    </p>

                </div>

            </section>


            <div class="hom-help-page-back">

                <a
                    href="<?php
                    echo esc_url(
                        $help_index_url
                    );
                    ?>"
                    class="hom-help-action hom-help-action-secondary"
                >
                    <span aria-hidden="true">←</span>
                    بازگشت به راهنمای پنل
                </a>

            </div>


            <section
                id="hom-help-product-images"
                class="hom-help-topic"
            >

                <div class="hom-help-topic__head">

                    <div class="hom-help-topic__icon">
                        🖼️
                    </div>

                    <div>

                        <span class="hom-help-kicker">
                            راهنمای مدیریت تصاویر محصولات
                        </span>

                        <h2>
                            روش صحیح اصلاح تصویر اصلی و گالری
                        </h2>

                        <p>
                            محصول صحیح را پیدا کنید، تصاویر را
                            انتخاب و ویرایش کنید و در پایان تغییرات
                            را روی محصول ثبت نمایید.
                        </p>

                    </div>

                    <a
                        href="<?php
                        echo esc_url(
                            $products_url
                        );
                        ?>"
                        class="hom-help-action hom-help-action-primary"
                    >
                        رفتن به مدیریت تصاویر محصولات
                    </a>

                </div>


                <section class="hom-help-important">

                    <div class="hom-help-important__icon">
                        ℹ️
                    </div>

                    <div>

                        <strong>
                            کدهای محصول را بشناسید
                        </strong>

                        <p>
                            <strong>ID:</strong>
                            شناسه داخلی محصول در وردپرس و ووکامرس.
                        </p>

                        <p>
                            <strong>SKU:</strong>
                            کد انبار یا کد فروش محصول.
                        </p>

                        <p>
                            <strong>Part Number:</strong>
                            کد یا شماره فنی محصول که معمولاً روی
                            کالا، جعبه یا کاتالوگ دیده می‌شود.
                        </p>

                        <p>
                            محصول را می‌توانید با نام، ID، SKU،
                            Part Number یا برند جستجو کنید.
                        </p>

                    </div>

                </section>


                <div class="hom-help-section-heading">
                    <span>مسیر استاندارد کار</span>
                    <h2>مراحل مدیریت تصاویر</h2>
                </div>


                <section class="hom-help-steps">

                    <article class="hom-help-step">
                        <div class="hom-help-step__number">۱</div>
                        <div class="hom-help-step__icon">🔎</div>
                        <h3>محصول را پیدا کنید</h3>
                        <p>
                            با نام، ID، SKU، Part Number یا برند
                            محصول صحیح را جستجو کنید.
                        </p>
                    </article>

                    <article class="hom-help-step">
                        <div class="hom-help-step__number">۲</div>
                        <div class="hom-help-step__icon">🏷️</div>
                        <h3>مشخصات را کنترل کنید</h3>
                        <p>
                            نام، برند و کدهای محصول را قبل از
                            تغییر تصویر بررسی کنید.
                        </p>
                    </article>

                    <article class="hom-help-step">
                        <div class="hom-help-step__number">۳</div>
                        <div class="hom-help-step__icon">🖼️</div>
                        <h3>تصویر اصلی را انتخاب کنید</h3>
                        <p>
                            واضح‌ترین و مناسب‌ترین نمای همان کالا
                            را به‌عنوان تصویر اصلی انتخاب کنید.
                        </p>
                    </article>

                    <article class="hom-help-step">
                        <div class="hom-help-step__number">۴</div>
                        <div class="hom-help-step__icon">🗂️</div>
                        <h3>گالری را تکمیل کنید</h3>
                        <p>
                            تصاویر تکمیلی، زوایای دیگر، بسته‌بندی،
                            نقشه یا ابعاد فنی را اضافه کنید.
                        </p>
                    </article>

                    <article class="hom-help-step">
                        <div class="hom-help-step__number">۵</div>
                        <div class="hom-help-step__icon">✥</div>
                        <h3>تصویر را تنظیم کنید</h3>
                        <p>
                            تصویر را جابه‌جا، زوم و بچرخانید.
                            خروجی نهایی مربع ۱:۱ است.
                        </p>
                    </article>

                    <article class="hom-help-step">
                        <div class="hom-help-step__number">۶</div>
                        <div class="hom-help-step__icon">✅</div>
                        <h3>آپلود و ذخیره کنید</h3>
                        <p>
                            پس از آماده‌سازی تصاویر، آپلود را
                            انجام دهید و در پایان حتماً
                            «ذخیره تغییرات» را بزنید.
                        </p>
                    </article>

                </section>


                <section class="hom-help-important hom-help-image-editor-guide">

                    <div class="hom-help-important__icon">
                        !
                    </div>

                    <div>

                        <strong>
                            نکات مهم ویرایشگر تصویر
                        </strong>

                        <p>
                            تصاویر انتخاب‌شده از دستگاه، دوربین
                            یا Media Library قبل از آپلود وارد
                            ویرایشگر می‌شوند.
                        </p>

                        <p>
                            امکان جابه‌جایی، زوم، چرخش آزاد،
                            مشاهده درجه چرخش و صفر کردن زاویه وجود
                            دارد. در موبایل زوم و چرخش با دو انگشت
                            نیز قابل انجام است.
                        </p>

                        <p>
                            خروجی نهایی همیشه مربع ۱:۱ است.
                            بهتر است اطراف کالا فضای مناسب باقی بماند.
                        </p>

                        <p>
                            ابزار «حاشیه سفید» کل کالا را با فضای
                            مناسب داخل کادر نگه می‌دارد و
                            «پر کردن کادر» تصویر را تا لبه‌های
                            مربع گسترش می‌دهد.
                        </p>

                        <p>
                            واترمارک «صنعت گستران الفت» به‌صورت
                            خودکار روی نسخه جدید اعمال می‌شود.
                        </p>

                        <p>
                            فایل اصلی Media Library تغییر نمی‌کند؛
                            سیستم نسخه جدید ویرایش‌شده ایجاد می‌کند.
                        </p>

                        <p>
                            «جدا کردن از محصول» فایل را حذف نمی‌کند
                            و فقط ارتباط آن تصویر با محصول را برمی‌دارد.
                        </p>

                    </div>

                </section>


                <div class="hom-help-section-heading">
                    <span>ابزارهای تصاویر</span>
                    <h2>هر دکمه چه کاری انجام می‌دهد؟</h2>
                </div>


                <section class="hom-help-tools">

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">📁</span>
                        <div>
                            <h3>انتخاب از دستگاه</h3>
                            <p>انتخاب عکس از کامپیوتر یا موبایل.</p>
                        </div>
                    </article>

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">📷</span>
                        <div>
                            <h3>دوربین موبایل</h3>
                            <p>گرفتن مستقیم عکس با دوربین دستگاه.</p>
                        </div>
                    </article>

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">▦</span>
                        <div>
                            <h3>رسانه‌های سایت</h3>
                            <p>انتخاب تصویر موجود در Media Library.</p>
                        </div>
                    </article>

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">⬆️</span>
                        <div>
                            <h3>آپلود همه تصاویر</h3>
                            <p>انتقال تصاویر آماده‌شده به سایت.</p>
                        </div>
                    </article>

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">↺</span>
                        <div>
                            <h3>لغو تغییرات</h3>
                            <p>بازگشت تغییرات ذخیره‌نشده به وضعیت اولیه.</p>
                        </div>
                    </article>

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">✓</span>
                        <div>
                            <h3>ذخیره تغییرات</h3>
                            <p>ثبت نهایی تصویر اصلی و گالری روی محصول.</p>
                        </div>
                    </article>

                </section>


                <section class="hom-help-warning">

                    <div class="hom-help-warning__head">

                        <span class="hom-help-warning__icon">⚠️</span>

                        <div>
                            <span>قبل از ذخیره</span>
                            <h2>نکات مهم تصاویر محصولات</h2>
                        </div>

                    </div>

                    <ul>
                        <li>
                            کد، برند و مدل محصول را با تصویر تطبیق دهید.
                        </li>
                        <li>
                            اگر درباره تصویر مطمئن نیستید، آن را ذخیره نکنید.
                        </li>
                        <li>
                            هنگام آپلود صفحه را نبندید.
                        </li>
                        <li>
                            تصویر محصول یا برند دیگری را استفاده نکنید.
                        </li>
                        <li>
                            از تصاویر بی‌کیفیت و نامرتبط خودداری کنید.
                        </li>
                        <li>
                            قبل از ذخیره نهایی، تصویر اصلی و گالری
                            را دوباره کنترل کنید.
                        </li>
                    </ul>

                </section>

            </section>


            <?php
            self::render_help_security_content();
            ?>

        </div>

        <?php
    }



    private static function render_help_security_content() {

        ?>

            <section class="hom-help-security">

                <div class="hom-help-security__icon">
                    🔐
                </div>

                <div class="hom-help-security__content">

                    <span class="hom-help-kicker">
                        امنیت حساب مدیریت
                    </span>

                    <h2>
                        اطلاعات دسترسی شما محرمانه است
                    </h2>

                    <p>
                        این حساب امکان ایجاد تغییرات واقعی در
                        فروشگاه را دارد. نام کاربری، رمز عبور
                        و لینک‌های مدیریتی را در اختیار افراد
                        غیرمجاز قرار ندهید.
                    </p>

                    <div class="hom-help-security__rules">

                        <div>
                            <strong>رمز عبور</strong>
                            <span>آن را برای دیگران ارسال نکنید.</span>
                        </div>

                        <div>
                            <strong>دستگاه مشترک</strong>
                            <span>پس از پایان کار از حساب خارج شوید.</span>
                        </div>

                        <div>
                            <strong>ثبت اطلاعات</strong>
                            <span>پیش از ذخیره هر تغییر، اطلاعات را کنترل کنید.</span>
                        </div>

                        <div>
                            <strong>فعالیت مشکوک</strong>
                            <span>موضوع را سریعاً به مدیر اصلی سایت اطلاع دهید.</span>
                        </div>

                    </div>

                </div>

            </section>

        <?php
    }



    private static function render_products_content() {

        if (
            !current_user_can(
                HOM_Capabilities::CAP_VIEW_PRODUCTS
            )
        ) {

            ?>
            <div class="hom-alert hom-alert-error">
                شما اجازه مشاهده محصولات را ندارید.
            </div>
            <?php

            return;
        }


        $search = isset($_GET['q'])
            ? sanitize_text_field(
                wp_unslash(
                    $_GET['q']
                )
            )
            : '';


        $page = isset($_GET['paged'])
            ? max(
                1,
                absint(
                    $_GET['paged']
                )
            )
            : 1;


        $result =
            HOM_Products::search(
                $search,
                $page
            );


        $items =
            $result['items'];


        $images_help_url =
            add_query_arg(
                'view',
                'help-product-images',
                HOM_Router::panel_url()
            );

        ?>

        <div class="hom-page-heading hom-products-heading">

            <div>

                <span class="hom-eyebrow">
                    PRODUCTS
                </span>

                <h1>
                    مدیریت تصاویر محصولات
                </h1>

                <p>
                    محصول را با نام، شناسه محصول (ID)، کد SKU،
                    Part Number یا برند پیدا کنید و تصویر اصلی
                    و گالری آن را مدیریت نمایید.
                </p>

                <a
                    href="<?php
                    echo esc_url(
                        $images_help_url
                    );
                    ?>"
                    class="hom-section-help-link"
                >
                    <span aria-hidden="true">👁</span>
                    <span>
                        راهنمای مدیریت تصاویر محصولات
                    </span>
                </a>

            </div>

            <div class="hom-products-count">
                <strong>
                    <?php
                    echo esc_html(
                        number_format_i18n(
                            $result['total']
                        )
                    );
                    ?>
                </strong>

                <span>
                    محصول
                </span>
            </div>

        </div>


        <section class="hom-products-toolbar">

            <form
                method="get"
                action="<?php
                echo esc_url(
                    HOM_Router::panel_url()
                );
                ?>"
                class="hom-search-form"
            >

                <input
                    type="hidden"
                    name="view"
                    value="products"
                >

                <div class="hom-search-field">

                    <input
                        type="search"
                        name="q"
                        value="<?php
                        echo esc_attr(
                            $result['search']
                        );
                        ?>"
                        placeholder="مثلاً KSM-1203، 6205، SKF یا نام محصول"
                        autocomplete="off"
                    >

                    <button
                        type="submit"
                        class="hom-button hom-button-primary"
                    >
                        جستجو
                    </button>

                </div>


                <?php if ('' !== $result['search']) : ?>

                    <a
                        class="hom-clear-search"
                        href="<?php
                        echo esc_url(
                            add_query_arg(
                                'view',
                                'products',
                                HOM_Router::panel_url()
                            )
                        );
                        ?>"
                    >
                        پاک کردن جستجو
                    </a>

                <?php endif; ?>

            </form>

        </section>


        <?php if (empty($items)) : ?>

            <section class="hom-empty-state">

                <strong>
                    محصولی پیدا نشد
                </strong>

                <p>
                    عبارت جستجو را تغییر دهید و دوباره تلاش کنید.
                </p>

            </section>

        <?php else : ?>


            <div class="hom-table-wrap">

                <table class="hom-products-table">

                    <thead>
                        <tr>
                            <th>تصویر</th>
                            <th>محصول</th>
                            <th>ID</th>
                            <th>SKU</th>
                            <th>Part Number</th>
                            <th>برند</th>
                            <th>کشور</th>
                            <th>قیمت</th>
                            <th>تصاویر</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>


                    <tbody>

                    <?php foreach ($items as $item) : ?>

                        <tr>

                            <td
                                data-label="تصویر"
                                class="hom-product-image-cell"
                            >

                                <?php if ($item['image_url']) : ?>

                                    <img
                                        src="<?php
                                        echo esc_url(
                                            $item['image_url']
                                        );
                                        ?>"
                                        alt=""
                                        loading="lazy"
                                        class="hom-product-thumb"
                                    >

                                <?php else : ?>

                                    <span class="hom-product-no-image">
                                        بدون تصویر
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td
                                data-label="محصول"
                                class="hom-product-name-cell"
                            >

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $item['name']
                                    );
                                    ?>
                                </strong>

                                <span>
                                    <?php
                                    echo esc_html(
                                        'وضعیت: ' .
                                        (
                                            'instock' ===
                                            $item['stock_status']
                                                ? 'موجود'
                                                : 'ناموجود'
                                        )
                                    );
                                    ?>
                                </span>

                            </td>


                            <td data-label="ID">
                                <span class="hom-ltr">
                                    <?php
                                    echo esc_html(
                                        $item['id']
                                    );
                                    ?>
                                </span>
                            </td>


                            <td data-label="SKU">
                                <span class="hom-ltr hom-code">
                                    <?php
                                    echo esc_html(
                                        $item['sku']
                                            ?: '—'
                                    );
                                    ?>
                                </span>
                            </td>


                            <td data-label="Part Number">
                                <span class="hom-ltr hom-code">
                                    <?php
                                    echo esc_html(
                                        $item['part_number']
                                            ?: '—'
                                    );
                                    ?>
                                </span>
                            </td>


                            <td data-label="برند">
                                <?php
                                echo esc_html(
                                    $item['brands']
                                        ? implode(
                                            '، ',
                                            $item['brands']
                                        )
                                        : '—'
                                );
                                ?>
                            </td>


                            <td data-label="کشور">
                                <?php
                                echo esc_html(
                                    $item['country']
                                        ?: '—'
                                );
                                ?>
                            </td>


                            <td data-label="قیمت">

                                <?php
                                if ($item['price_html']) {

                                    echo wp_kses_post(
                                        $item['price_html']
                                    );

                                } else {

                                    echo 'بدون قیمت';
                                }
                                ?>

                            </td>


                            <td data-label="تصاویر">

                                <?php if ($item['image_id']) : ?>

                                    <span class="hom-image-status is-ok">
                                        تصویر اصلی
                                    </span>

                                    <small>
                                        <?php
                                        echo esc_html(
                                            number_format_i18n(
                                                $item['gallery_count']
                                            )
                                        );
                                        ?>
                                        تصویر گالری
                                    </small>

                                <?php else : ?>

                                    <span class="hom-image-status is-missing">
                                        بدون تصویر اصلی
                                    </span>

                                    <?php if ($item['gallery_count']) : ?>

                                        <small>
                                            <?php
                                            echo esc_html(
                                                number_format_i18n(
                                                    $item['gallery_count']
                                                )
                                            );
                                            ?>
                                            تصویر گالری
                                        </small>

                                    <?php endif; ?>

                                <?php endif; ?>

                            </td>


                            <td
                                data-label="عملیات"
                                class="hom-product-actions"
                            >

                                <a
                                    class="hom-button hom-button-secondary"
                                    href="<?php
                                    echo esc_url(
                                        add_query_arg(
                                            [
                                                'view' =>
                                                    'product-images',

                                                'product_id' =>
                                                    $item['id'],
                                            ],
                                            HOM_Router::panel_url()
                                        )
                                    );
                                    ?>"
                                >
                                    ویرایش محصول
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <?php if ($result['total_pages'] > 1) : ?>

                <nav
                    class="hom-pagination"
                    aria-label="صفحه‌بندی محصولات"
                >

                    <span>
                        صفحه
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                $result['page']
                            )
                        );
                        ?>
                        از
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                $result['total_pages']
                            )
                        );
                        ?>
                    </span>


                    <div>

                        <?php if ($result['page'] > 1) : ?>

                            <a
                                class="hom-button hom-button-secondary"
                                href="<?php
                                echo esc_url(
                                    add_query_arg(
                                        [
                                            'view' =>
                                                'products',

                                            'q' =>
                                                $result['search'],

                                            'paged' =>
                                                $result['page'] - 1,
                                        ],
                                        HOM_Router::panel_url()
                                    )
                                );
                                ?>"
                            >
                                صفحه قبل
                            </a>

                        <?php endif; ?>


                        <?php
                        if (
                            $result['page'] <
                            $result['total_pages']
                        ) :
                        ?>

                            <a
                                class="hom-button hom-button-secondary"
                                href="<?php
                                echo esc_url(
                                    add_query_arg(
                                        [
                                            'view' =>
                                                'products',

                                            'q' =>
                                                $result['search'],

                                            'paged' =>
                                                $result['page'] + 1,
                                        ],
                                        HOM_Router::panel_url()
                                    )
                                );
                                ?>"
                            >
                                صفحه بعد
                            </a>

                        <?php endif; ?>

                    </div>

                </nav>

            <?php endif; ?>


        <?php endif;
    }


    private static function render_product_images_content() {

        if (
            !current_user_can(
                HOM_Capabilities::CAP_MANAGE_PRODUCT_IMAGES
            )
        ) {

            ?>
            <div class="hom-alert hom-alert-error">
                شما اجازه ویرایش محصول محصولات را ندارید.
            </div>
            <?php

            return;
        }


        $product_id =
            isset($_GET['product_id'])
                ? absint(
                    $_GET['product_id']
                )
                : 0;


        $product =
            HOM_Product_Images::get_product(
                $product_id
            );


        if (!$product) {

            ?>
            <div class="hom-alert hom-alert-error">
                محصول موردنظر پیدا نشد.
            </div>
            <?php

            return;
        }


        $images =
            HOM_Product_Images::get_product_images(
                $product
            );


        $part_number =
            trim(
                (string) get_post_meta(
                    $product_id,
                    '_mpn_part_number',
                    true
                )
            );


        $brands =
            wp_get_post_terms(
                $product_id,
                'product_brand',
                [
                    'fields' => 'names',
                ]
            );

        if (is_wp_error($brands)) {
            $brands = [];
        }


        $products_url =
            add_query_arg(
                'view',
                'products',
                HOM_Router::panel_url()
            );


        ?>

        <script>
        document.body.classList.add(
            'hom-product-images-screen'
        );
        </script>


        <div
            class="hom-image-manager"
            data-hom-image-manager
        >

            <div class="hom-page-heading">

                <div>

                    <a
                        class="hom-back-link"
                        data-hom-back
                        href="<?php
                        echo esc_url(
                            $products_url
                        );
                        ?>"
                    >
                        ← بازگشت به لیست محصولات
                    </a>

                    <span class="hom-eyebrow">
                        PRODUCT MANAGEMENT
                    </span>

                    <h1>
                        مدیریت محصول
                    </h1>

                    <p>
                        <?php
                        echo esc_html(
                            $product->get_name()
                        );
                        ?>
                    </p>

                </div>

            </div>


            <section class="hom-product-image-summary">

                <div>
                    <span>شناسه محصول</span>

                    <strong class="hom-ltr">
                        <?php
                        echo esc_html(
                            $product_id
                        );
                        ?>
                    </strong>
                </div>


                <div>
                    <span>SKU</span>

                    <strong class="hom-ltr">
                        <?php
                        echo esc_html(
                            $product->get_sku()
                                ?: '—'
                        );
                        ?>
                    </strong>
                </div>


                <div>
                    <span>Part Number</span>

                    <strong class="hom-ltr">
                        <?php
                        echo esc_html(
                            $part_number
                                ?: '—'
                        );
                        ?>
                    </strong>
                </div>


                <div>
                    <span>برند</span>

                    <strong>
                        <?php
                        echo esc_html(
                            $brands
                                ? implode(
                                    '، ',
                                    $brands
                                )
                                : '—'
                        );
                        ?>
                    </strong>
                </div>

            </section>


            <div
                class="hom-image-notice"
                data-hom-notice
                hidden
            ></div>


            <!-- MAIN IMAGE -->
            <section class="hom-image-section">

                <div class="hom-image-section-heading">

                    <div>
                        <h2>
                            تصویر اصلی محصول
                        </h2>

                        <p>
                            تصویر نهایی فقط پس از زدن
                            «ذخیره تغییرات»
                            به محصول متصل می‌شود.
                        </p>
                    </div>

                </div>


                <div
                    class="hom-current-main-workspace"
                    data-hom-main-current
                ></div>


                <div
                    class="hom-source-panel"
                >

                    <div class="hom-source-panel__heading">

                        <strong>
                            انتخاب تصویر اصلی جدید
                        </strong>

                        <span>
                            یکی از روش‌های زیر را انتخاب کنید.
                        </span>

                    </div>


                    <div class="hom-source-actions">

                        <label
                            class="hom-source-button"
                        >

                            <span class="hom-source-icon">
                                📁
                            </span>

                            <span>
                                انتخاب از دستگاه
                            </span>

                            <input
                                type="file"
                                data-hom-main-device
                                accept=".jpg,.jpeg,.png,.webp,.avif,.heic,.heif,image/jpeg,image/png,image/webp,image/avif,image/heic,image/heif"
                            >

                        </label>


                        <label
                            class="hom-source-button"
                        >

                            <span class="hom-source-icon">
                                📷
                            </span>

                            <span>
                                دوربین موبایل
                            </span>

                            <input
                                type="file"
                                data-hom-main-camera
                                accept="image/*"
                                capture="environment"
                            >

                        </label>


                        <button
                            type="button"
                            class="hom-source-button"
                            data-hom-open-media="main"
                        >

                            <span class="hom-source-icon">
                                ▦
                            </span>

                            <span>
                                رسانه‌های سایت
                            </span>

                        </button>

                    </div>


                    <div
                        class="hom-pending-main"
                        data-hom-main-pending
                    ></div>

                </div>

            </section>


            <!-- GALLERY -->
            <section class="hom-image-section">

                <div class="hom-image-section-heading">

                    <div>
                        <h2>
                            گالری محصول
                        </h2>

                        <p>
                            تصاویر را می‌توانید جداگانه یا یکجا آپلود کنید.
                        </p>
                    </div>

                    <strong
                        class="hom-gallery-counter"
                        data-hom-gallery-count
                    ></strong>

                </div>


                <div
                    class="hom-gallery-grid"
                    data-hom-gallery-ready
                ></div>


                <div class="hom-source-panel">

                    <div class="hom-source-panel__heading">

                        <strong>
                            افزودن تصاویر گالری
                        </strong>

                        <span>
                            دوربین، دستگاه یا Media Library سایت
                        </span>

                    </div>


                    <div class="hom-source-actions">

                        <label
                            class="hom-source-button"
                        >

                            <span class="hom-source-icon">
                                📁
                            </span>

                            <span>
                                انتخاب از دستگاه
                            </span>

                            <input
                                type="file"
                                data-hom-gallery-device
                                accept=".jpg,.jpeg,.png,.webp,.avif,.heic,.heif,image/jpeg,image/png,image/webp,image/avif,image/heic,image/heif"
                                multiple
                            >

                        </label>


                        <label
                            class="hom-source-button"
                        >

                            <span class="hom-source-icon">
                                📷
                            </span>

                            <span>
                                دوربین موبایل
                            </span>

                            <input
                                type="file"
                                data-hom-gallery-camera
                                accept="image/*"
                                capture="environment"
                                multiple
                            >

                        </label>


                        <button
                            type="button"
                            class="hom-source-button"
                            data-hom-open-media="gallery"
                        >

                            <span class="hom-source-icon">
                                ▦
                            </span>

                            <span>
                                رسانه‌های سایت
                            </span>

                        </button>

                    </div>


                    <div
                        class="hom-gallery-pending-toolbar"
                        data-hom-gallery-pending-toolbar
                        hidden
                    >

                        <strong>
                            تصاویر آماده آپلود
                        </strong>

                        <button
                            type="button"
                            class="hom-button hom-upload-all-button"
                            data-hom-upload-all
                        >
                            ↑ آپلود همه تصاویر
                        </button>

                    </div>


                    <div
                        class="hom-pending-gallery-grid"
                        data-hom-gallery-pending
                    ></div>

                </div>

            </section>


            <section class="hom-media-safety-note">

                <div>
                    <strong>
                        تصاویر جدید
                    </strong>

                    <p>
                        تصاویر آپلودشده از دستگاه یا دوربین،
                        با نام مرتبط با محصول و Alt و Title مناسب
                        به‌صورت خودکار ساخته می‌شوند.
                    </p>
                </div>


                <div>
                    <strong>
                        تصاویر موجود در Media Library
                    </strong>

                    <p>
                        تصاویر انتخاب‌شده از رسانه‌های سایت
                        هرگز Rename نمی‌شوند و Title، Alt و Parent
                        آن‌ها نیز تغییر نمی‌کند؛ چون ممکن است
                        در بخش دیگری از سایت استفاده شده باشند.
                    </p>
                </div>

            </section>


            <!-- STICKY FINAL ACTION -->
            <div
                class="hom-final-action-bar"
                data-hom-final-bar
            >

                <a
                    href="<?php
                    echo esc_url(
                        $products_url
                    );
                    ?>"
                    class="hom-button hom-final-back"
                    data-hom-back
                >
                    ← بازگشت به محصولات
                </a>


                <button
                    type="button"
                    class="hom-button hom-final-reset"
                    data-hom-final-reset
                    title="بازگشت تصاویر به وضعیت اولیه"
                    disabled
                >
                    ↺ لغو تغییرات
                </button>


                <div
                    class="hom-final-status"
                    data-hom-final-status
                >
                    تغییری برای ذخیره وجود ندارد.
                </div>


                <button
                    type="button"
                    class="hom-button hom-final-save"
                    data-hom-final-save
                    disabled
                >
                    ✓ ذخیره تغییرات
                </button>

            </div>



            <!-- PRODUCT IMAGE EDITOR -->
            <div
                class="hom-editor-modal"
                data-hom-editor-modal
                hidden
            >

                <div
                    class="hom-editor-modal__backdrop"
                    data-hom-editor-cancel
                ></div>


                <div
                    class="hom-editor-dialog"
                    role="dialog"
                    aria-modal="true"
                    aria-label="ویرایش تصویر محصول"
                >

                    <div class="hom-editor-header">

                        <div>
                            <strong>
                                تنظیم تصویر محصول
                            </strong>

                            <span>
                                خروجی نهایی مربع ۱:۱
                            </span>
                        </div>


                        <button
                            type="button"
                            class="hom-editor-close"
                            data-hom-editor-cancel
                            aria-label="بستن"
                        >
                            ×
                        </button>

                    </div>


                    <div class="hom-editor-body">

                        <div class="hom-editor-canvas-column">

                            <div class="hom-editor-workspace">

                                <div class="hom-editor-canvas-wrap">

                                    <canvas
                                        width="720"
                                        height="720"
                                        data-hom-editor-canvas
                                    ></canvas>

                                </div>


                                <!-- MOBILE TOOLBAR -->
                                <div class="hom-editor-toolbar">

                                    <button
                                        type="button"
                                        class="hom-editor-tool"
                                        data-hom-editor-tool="rotate"
                                        aria-label="چرخش"
                                        title="چرخش"
                                    >
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M20 11a8 8 0 1 0-2.3 5.7M20 5v6h-6"/>
                                        </svg>
                                    </button>


                                    <button
                                        type="button"
                                        class="hom-editor-tool"
                                        data-hom-editor-tool="zoom"
                                        aria-label="زوم"
                                        title="زوم"
                                    >
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="10.5" cy="10.5" r="6.5"/>
                                            <path d="m15.5 15.5 5 5M10.5 7.5v6M7.5 10.5h6"/>
                                        </svg>
                                    </button>


                                    <button
                                        type="button"
                                        class="hom-editor-tool"
                                        data-hom-editor-fit
                                        aria-label="حاشیه سفید"
                                        title="حاشیه سفید"
                                    >
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                            <rect x="7" y="7" width="10" height="10" rx="1"/>
                                        </svg>
                                    </button>


                                    <button
                                        type="button"
                                        class="hom-editor-tool"
                                        data-hom-editor-fill
                                        aria-label="پر کردن کادر"
                                        title="پر کردن کادر"
                                    >
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M4 9V4h5M15 4h5v5M20 15v5h-5M9 20H4v-5"/>
                                        </svg>
                                    </button>


                                    <button
                                        type="button"
                                        class="hom-editor-tool"
                                        data-hom-editor-reset
                                        aria-label="بازنشانی"
                                        title="بازنشانی"
                                    >
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M4 4v6h6M5.5 15a7 7 0 1 0 1-8L4 10"/>
                                        </svg>
                                    </button>


                                    <button
                                        type="button"
                                        class="hom-editor-tool"
                                        data-hom-editor-tool="help"
                                        aria-label="راهنما"
                                        title="راهنما"
                                    >
                                        <strong>!</strong>
                                    </button>

                                </div>


                                <!-- TOOL FLYOUTS -->
                                <div
                                    class="hom-editor-flyout"
                                    data-hom-editor-panel="rotate"
                                    hidden
                                >
                                    <div class="hom-editor-flyout__head">
                                        <strong>چرخش تصویر</strong>

                                        <output
                                            data-hom-editor-rotation-output
                                        >
                                            0°
                                        </output>
                                    </div>

                                    <input
                                        type="range"
                                        min="-180"
                                        max="180"
                                        step="0.1"
                                        value="0"
                                        data-hom-editor-rotation
                                    >

                                    <button
                                        type="button"
                                        class="hom-editor-zero-button"
                                        data-hom-editor-zero-rotation
                                    >
                                        صفر کردن زاویه
                                    </button>
                                </div>


                                <div
                                    class="hom-editor-flyout"
                                    data-hom-editor-panel="zoom"
                                    hidden
                                >
                                    <div class="hom-editor-flyout__head">
                                        <strong>زوم تصویر</strong>

                                        <output
                                            data-hom-editor-zoom-output
                                        >
                                            100٪
                                        </output>
                                    </div>

                                    <input
                                        type="range"
                                        min="25"
                                        max="400"
                                        step="1"
                                        value="100"
                                        data-hom-editor-zoom
                                    >
                                </div>


                                <div
                                    class="hom-editor-flyout hom-editor-help"
                                    data-hom-editor-panel="help"
                                    hidden
                                >
                                    <strong>
                                        راهنمای ابزار
                                    </strong>

                                    <p>
                                        تصویر را با موس یا یک انگشت جابه‌جا کنید.
                                        با اسکرول موس زوم کنید.
                                        در موبایل با دو انگشت زوم و چرخش انجام می‌شود.
                                    </p>

                                    <p>
                                        «حاشیه سفید» کل محصول را با فاصله مناسب
                                        داخل کادر نگه می‌دارد و «پر کردن کادر»
                                        تصویر را تا لبه‌های مربع گسترش می‌دهد.
                                    </p>


                                    <p>
                                        سیستم به‌صورت خودکار آرم «صنعت گستران الفت»
                                        را به‌عنوان واترمارک روی نسخه نهایی تصویر
                                        قرار می‌دهد. فایل اصلی تصویر بدون تغییر
                                        باقی می‌ماند و می‌توانید محل و ظاهر واترمارک
                                        را در همین کادر به‌صورت پیش‌نمایش مشاهده کنید.
                                    </p>
                                </div>

                            </div>

                        </div>


                        <!-- DESKTOP COMPACT CONTROLS -->
                        <aside class="hom-editor-desktop-controls">

                            <div class="hom-editor-control">

                                <div class="hom-editor-control__head">
                                    <strong>چرخش</strong>

                                    <output
                                        data-hom-editor-rotation-output-desktop
                                    >
                                        0°
                                    </output>
                                </div>

                                <input
                                    type="range"
                                    min="-180"
                                    max="180"
                                    step="0.1"
                                    value="0"
                                    data-hom-editor-rotation-desktop
                                >

                                <button
                                    type="button"
                                    class="hom-editor-small-button"
                                    data-hom-editor-zero-rotation
                                >
                                    صفر کردن چرخش
                                </button>

                            </div>


                            <div class="hom-editor-control">

                                <div class="hom-editor-control__head">
                                    <strong>زوم</strong>

                                    <output
                                        data-hom-editor-zoom-output-desktop
                                    >
                                        100٪
                                    </output>
                                </div>

                                <input
                                    type="range"
                                    min="25"
                                    max="400"
                                    step="1"
                                    value="100"
                                    data-hom-editor-zoom-desktop
                                >

                            </div>


                            <div class="hom-editor-quick-actions">

                                <button
                                    type="button"
                                    data-hom-editor-fit
                                >
                                    حاشیه سفید
                                </button>

                                <button
                                    type="button"
                                    data-hom-editor-fill
                                >
                                    پر کردن کادر
                                </button>

                                <button
                                    type="button"
                                    data-hom-editor-reset
                                >
                                    بازنشانی
                                </button>

                                <button
                                    type="button"
                                    data-hom-editor-tool="help"
                                >
                                    ! راهنما
                                </button>

                            </div>

                        </aside>

                    </div>


                    <div class="hom-editor-footer">

                        <span
                            class="hom-editor-status"
                            data-hom-editor-status
                        >
                            تصویر را تنظیم کنید.
                        </span>


                        <div class="hom-editor-footer__actions">

                            <button
                                type="button"
                                class="hom-editor-cancel-button"
                                data-hom-editor-cancel
                            >
                                انصراف
                            </button>


                            <button
                                type="button"
                                class="hom-button hom-editor-confirm"
                                data-hom-editor-confirm
                            >
                                تأیید تصویر
                            </button>

                        </div>

                    </div>

                </div>

            </div>


            <!-- MEDIA LIBRARY MODAL -->
            <div
                class="hom-media-modal"
                data-hom-media-modal
                hidden
            >

                <div
                    class="hom-media-modal__backdrop"
                    data-hom-media-close
                ></div>


                <div
                    class="hom-media-modal__dialog"
                    role="dialog"
                    aria-modal="true"
                    aria-label="رسانه‌های سایت"
                >

                    <div class="hom-media-modal__header">

                        <div>
                            <strong>
                                رسانه‌های سایت
                            </strong>

                            <span>
                                اصل فایل بدون تغییر می‌ماند و نسخه ویرایش‌شده جدید ساخته می‌شود
                            </span>
                        </div>


                        <button
                            type="button"
                            class="hom-media-close"
                            data-hom-media-close
                            aria-label="بستن"
                        >
                            ×
                        </button>

                    </div>


                    <div class="hom-media-search">

                        <input
                            type="search"
                            data-hom-media-search
                            placeholder="جستجو در تصاویر..."
                        >

                        <button
                            type="button"
                            class="hom-button"
                            data-hom-media-search-button
                        >
                            جستجو
                        </button>

                    </div>


                    <div
                        class="hom-media-grid"
                        data-hom-media-grid
                    ></div>


                    <div
                        class="hom-media-empty"
                        data-hom-media-empty
                        hidden
                    >
                        تصویری پیدا نشد.
                    </div>


                    <button
                        type="button"
                        class="hom-media-more"
                        data-hom-media-more
                        hidden
                    >
                        نمایش تصاویر بیشتر
                    </button>


                    <div class="hom-media-modal__footer">

                        <span
                            data-hom-media-selected-count
                        >
                            تصویری انتخاب نشده است.
                        </span>


                        <button
                            type="button"
                            class="hom-button hom-media-confirm"
                            data-hom-media-confirm
                            disabled
                        >
                            افزودن تصویر انتخاب‌شده
                        </button>

                    </div>

                </div>

            </div>

        </div>


        <script>
        window.HOMProductImages = <?php
        echo wp_json_encode(
            [
                'ajaxUrl' =>
                    admin_url(
                        'admin-ajax.php'
                    ),

                'nonce' =>
                    wp_create_nonce(
                        'hom_product_images_ajax'
                    ),

                'productId' =>
                    $product_id,

                'productsUrl' =>
                    $products_url,

                'maxUploadBytes' =>
                    wp_max_upload_size(),

                'maxUploadLabel' =>
                    size_format(
                        wp_max_upload_size()
                    ),

                /*
                 * High-resolution square output.
                 * Watermark is applied server-side afterward.
                 */
                'editorOutputSize' =>
                    1800,

                'watermarkReady' =>
                    HOM_Product_Images::watermark_is_ready(),

                'watermarkUrl' =>
                    HOM_Product_Images::watermark_url(),

                'initialMain' =>
                    $images['main'],

                'initialGallery' =>
                    $images['gallery'],
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );
        ?>;
        </script>


        <script
            src="<?php
            echo esc_url(
                HOM_URL .
                'assets/js/image-editor.js?ver=' .
                (
                    file_exists(
                        HOM_PATH .
                        'assets/js/image-editor.js'
                    )
                        ? filemtime(
                            HOM_PATH .
                            'assets/js/image-editor.js'
                        )
                        : HOM_VERSION
                )
            );
            ?>"
            defer
        ></script>


        <script
            src="<?php
            echo esc_url(
                HOM_URL .
                'assets/js/product-images.js?ver=' .
                (
                    file_exists(
                        HOM_PATH .
                        'assets/js/product-images.js'
                    )
                        ? filemtime(
                            HOM_PATH .
                            'assets/js/product-images.js'
                        )
                        : HOM_VERSION
                )
            );
            ?>"
            defer
        ></script>

        <?php
    }

}
