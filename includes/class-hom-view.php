<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOM_View {


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
            'ورود به پنل مدیریت فروشگاه'
        );

        $error = HOM_Auth::get_error();

        ?>
<div class="hom-login-layout">

    <section class="hom-login-brand">

        <div class="hom-brand-mark">
            H
        </div>

        <div>
            <div class="hom-brand-name">
                هایپر صنعتی
            </div>

            <div class="hom-brand-subtitle">
                پنل مدیریت فروشگاه
            </div>
        </div>

    </section>


    <main class="hom-login-main">

        <div class="hom-login-card">

            <header class="hom-login-header">

                <span class="hom-eyebrow">
                    OWNER PANEL
                </span>

                <h1>
                    ورود به پنل مدیریت فروشگاه
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
            'پنل مدیریت فروشگاه'
        );

        ?>
<div class="hom-app">

    <aside class="hom-sidebar">

        <div class="hom-sidebar-brand">

            <div class="hom-brand-mark">
                H
            </div>

            <div>
                <strong>
                    هایپر صنعتی
                </strong>

                <span>
                    مدیریت فروشگاه
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
                    صفحه اصلی
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
                    اطلاعات فروشنده
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

        ?>

        <div class="hom-page-heading">

            <div>

                <span class="hom-eyebrow">
                    DASHBOARD
                </span>

                <h1>
                    پنل مدیریت فروشگاه
                </h1>

                <p>
                    از این پنل می‌توانید مشتریان و سفارش‌ها را پیگیری کنید
                    و تصاویر محصولات فروشگاه را مدیریت نمایید.
                </p>

            </div>

        </div>


        <section class="hom-grid">

            <article class="hom-card hom-card-wide">

                <div class="hom-card-status">
                    <span class="hom-status-dot"></span>
                    سیستم فعال است
                </div>

                <h2>
                    پنل مدیریت فروشگاه آماده استفاده است
                </h2>

                <p>
                    دسترسی این حساب محدود به امکانات اختصاصی مدیر کسب‌وکار است.
                </p>

            </article>


            <article class="hom-card">

                <span class="hom-card-label">
                    سطح دسترسی
                </span>

                <strong class="hom-card-value">
                    Owner
                </strong>

                <span class="hom-card-meta">
                    دسترسی محدود و اختصاصی
                </span>

            </article>


            <article class="hom-card">

                <span class="hom-card-label">
                    بخش فعال
                </span>

                <strong class="hom-card-value hom-card-value-text">
                    مدیریت فروشگاه
                </strong>

                <span class="hom-card-meta">
                    پیگیری مشتریان و مدیریت تصاویر محصولات
                </span>

            </article>

        </section>

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
                                <th>شماره</th>
                                <th>نوع</th>
                                <th>مشتری</th>
                                <th>شهر</th>
                                <th>وضعیت</th>
                                <th>مسئول</th>
                                <th>مبلغ</th>
                                <th>ارسال</th>
                                <th>تاریخ</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php
                        foreach (
                            $result['items']
                            as $item
                        ) :
                            ?>

                            <tr>

                                <td
                                    data-label="شماره"
                                >
                                    <strong>
                                        <a href="<?php
                                        echo esc_url(
                                            HOM_Orders::detail_url(
                                                $item['id']
                                            )
                                        );
                                        ?>">
                                            #<?php
                                            echo esc_html(
                                                $item['number']
                                            );
                                            ?>
                                        </a>
                                    </strong>
                                </td>

                                <td
                                    data-label="نوع"
                                >
                                    <?php
                                    echo esc_html(
                                        $item[
                                            'is_preinvoice'
                                        ]
                                            ? 'پیش‌فاکتور'
                                            : 'سفارش'
                                    );
                                    ?>
                                </td>

                                <td
                                    data-label="مشتری"
                                >
                                    <div class="hom-order-customer">

                                        <strong>
                                            <?php
                                            echo esc_html(
                                                $item[
                                                    'customer_name'
                                                ]
                                            );
                                            ?>
                                        </strong>

                                        <?php
                                        if (
                                            !empty(
                                                $item['phone']
                                            )
                                        ) :
                                            ?>

                                            <span dir="ltr">
                                                <?php
                                                echo esc_html(
                                                    $item[
                                                        'phone'
                                                    ]
                                                );
                                                ?>
                                            </span>

                                        <?php endif; ?>

                                    </div>
                                </td>

                                <td
                                    data-label="شهر"
                                >
                                    <?php
                                    echo esc_html(
                                        $item['city']
                                            ?: '—'
                                    );
                                    ?>
                                </td>

                                <td
                                    data-label="وضعیت"
                                >
                                    <span class="hom-order-status-badge">
                                        <?php
                                        echo esc_html(
                                            $item[
                                                'status_label'
                                            ]
                                        );
                                        ?>
                                    </span>
                                </td>

                                <td
                                    data-label="مسئول"
                                >
                                    <?php
                                    echo esc_html(
                                        !empty(
                                            $item['assignee']['name']
                                        )
                                            ? $item['assignee']['name']
                                            : 'تعیین نشده'
                                    );
                                    ?>
                                </td>


                                <td
                                    data-label="مبلغ"
                                >
                                    <?php
                                    echo wp_kses_post(
                                        $item[
                                            'total_html'
                                        ]
                                    );
                                    ?>
                                </td>

                                <td
                                    data-label="ارسال"
                                >
                                    <?php
                                    echo esc_html(
                                        $item[
                                            'shipping_method'
                                        ]
                                            ?: 'تعیین نشده'
                                    );
                                    ?>
                                </td>

                                <td
                                    data-label="تاریخ"
                                >
                                    <?php
                                    echo esc_html(
                                        $item['date']
                                    );
                                    ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

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

        <div class="hom-help-page">

            <section class="hom-help-hero">

                <div class="hom-help-hero__icon">
                    👥
                </div>

                <div class="hom-help-hero__content">

                    <span class="hom-help-kicker">
                        راهنمای مدیریت و پیگیری مشتریان
                    </span>

                    <h1>
                        راهنمای کامل مدیریت و پیگیری مشتریان
                    </h1>

                    <p>
                        در این صفحه تمام مراحل رسیدگی به درخواست
                        پیش‌فاکتور، تکمیل اطلاعات مشتری، قیمت‌گذاری،
                        تأیید، پرداخت، آماده‌سازی، ارسال، تحویل،
                        اصلاح اطلاعات، چاپ اسناد و بررسی سوابق
                        توضیح داده شده است.
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
                id="hom-help-customers"
                class="hom-help-topic"
            >

                <div class="hom-help-topic__head">

                    <div class="hom-help-topic__icon">
                        👥
                    </div>

                    <div>

                        <span class="hom-help-kicker">
                            راهنمای مدیریت و پیگیری مشتریان
                        </span>

                        <h2>
                            مسیر کامل رسیدگی به مشتری
                        </h2>

                        <p>
                            از ورود درخواست پیش‌فاکتور تا قیمت‌گذاری،
                            پرداخت، آماده‌سازی، ارسال و تحویل نهایی.
                        </p>

                    </div>

                    <a
                        href="<?php
                        echo esc_url(
                            $orders_url
                        );
                        ?>"
                        class="hom-help-action hom-help-action-primary"
                    >
                        رفتن به مدیریت و پیگیری مشتریان
                    </a>

                </div>


                <div class="hom-help-section-heading">

                    <span>
                        مسیر استاندارد کار
                    </span>

                    <h2>
                        مراحل رسیدگی به پیش‌فاکتور و سفارش
                    </h2>

                    <p>
                        برای جلوگیری از خطا، مراحل را به ترتیب انجام دهید.
                    </p>

                </div>


                <section class="hom-help-steps">

                    <article class="hom-help-step">
                        <div class="hom-help-step__number">۱</div>
                        <div class="hom-help-step__icon">🔎</div>
                        <h3>مشتری یا سفارش را پیدا کنید</h3>
                        <p>
                            با شماره سفارش، نام، موبایل یا کد رهگیری
                            جستجو کنید و در صورت نیاز از فیلتر وضعیت
                            برای محدود کردن نتایج استفاده کنید.
                        </p>
                    </article>


                    <article class="hom-help-step">
                        <div class="hom-help-step__number">۲</div>
                        <div class="hom-help-step__icon">👤</div>
                        <h3>اطلاعات مشتری را بررسی کنید</h3>
                        <p>
                            مشخصات مشتری و اطلاعات حقوقی خریدار شامل
                            نام حقوقی، شناسه ملی، کد اقتصادی، شماره ثبت،
                            کدپستی و نشانی را کنترل و در صورت نیاز تکمیل کنید.
                        </p>
                    </article>


                    <article class="hom-help-step">
                        <div class="hom-help-step__number">۳</div>
                        <div class="hom-help-step__icon">💰</div>
                        <h3>قیمت‌گذاری را انجام دهید</h3>
                        <p>
                            قیمت واحد اقلام و هزینه ارسال را بررسی
                            و ثبت کنید و سپس «ذخیره قیمت‌ها» را بزنید.
                            اصلاح قیمت ثبت‌شده نیازمند دلیل اصلاح است.
                        </p>
                    </article>


                    <article class="hom-help-step">
                        <div class="hom-help-step__number">۴</div>
                        <div class="hom-help-step__icon">✓</div>
                        <h3>پیش‌فاکتور را تأیید کنید</h3>
                        <p>
                            پس از کنترل نهایی قیمت‌ها، دکمه
                            «تأیید و آماده‌سازی برای پرداخت»
                            را بزنید تا مشتری بتواند مرحله پرداخت
                            را ادامه دهد.
                        </p>
                    </article>


                    <article class="hom-help-step">
                        <div class="hom-help-step__number">۵</div>
                        <div class="hom-help-step__icon">💳</div>
                        <h3>پرداخت را تأیید کنید</h3>
                        <p>
                            فقط پس از مشاهده قطعی واریز، مبلغ،
                            شماره پیگیری یا مرجع پرداخت و توضیحات
                            را ثبت کرده و «تأیید دریافت کامل وجه»
                            را بزنید.
                        </p>
                    </article>


                    <article class="hom-help-step">
                        <div class="hom-help-step__number">۶</div>
                        <div class="hom-help-step__icon">📦</div>
                        <h3>سفارش را آماده ارسال کنید</h3>
                        <p>
                            روش ارسال، شرکت یا باربری، کد رهگیری،
                            وضعیت کرایه و توضیحات را تکمیل کنید و
                            پس از آماده شدن کالا وضعیت «آماده ارسال»
                            را ثبت نمایید.
                        </p>
                    </article>


                    <article class="hom-help-step">
                        <div class="hom-help-step__number">۷</div>
                        <div class="hom-help-step__icon">🚚</div>
                        <h3>ارسال را ثبت کنید</h3>
                        <p>
                            پس از تحویل واقعی کالا به باربری یا
                            شرکت حمل، سفارش را به وضعیت
                            «ارسال شده» منتقل کنید.
                        </p>
                    </article>


                    <article class="hom-help-step">
                        <div class="hom-help-step__number">۸</div>
                        <div class="hom-help-step__icon">✅</div>
                        <h3>تحویل را نهایی کنید</h3>
                        <p>
                            پس از اطمینان از رسیدن کالا به مشتری،
                            وضعیت «تحویل شده» را ثبت کنید.
                        </p>
                    </article>

                </section>


                <div class="hom-help-section-heading">
                    <span>وضعیت پرونده</span>
                    <h2>وضعیت‌ها چه معنایی دارند؟</h2>
                </div>


                <section class="hom-help-tools">

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">🆕</span>
                        <div>
                            <h3>پیش‌فاکتور جدید</h3>
                            <p>درخواست تازه‌ای که باید بررسی و قیمت‌گذاری شود.</p>
                        </div>
                    </article>

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">✓</span>
                        <div>
                            <h3>تأیید شده</h3>
                            <p>پیش‌فاکتور تأیید و برای پرداخت آماده شده است.</p>
                        </div>
                    </article>

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">⌛</span>
                        <div>
                            <h3>انتظار پرداخت</h3>
                            <p>پرونده در انتظار انجام یا تأیید پرداخت است.</p>
                        </div>
                    </article>

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">🔍</span>
                        <div>
                            <h3>در انتظار بررسی</h3>
                            <p>سفارش پیش از ادامه کار به بررسی نیاز دارد.</p>
                        </div>
                    </article>

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">⚙️</span>
                        <div>
                            <h3>در حال آماده‌سازی</h3>
                            <p>سفارش وارد مرحله آماده‌سازی کالا شده است.</p>
                        </div>
                    </article>

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">📦</span>
                        <div>
                            <h3>آماده ارسال</h3>
                            <p>کالا آماده تحویل به باربری یا شرکت حمل است.</p>
                        </div>
                    </article>

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">🚚</span>
                        <div>
                            <h3>ارسال شده</h3>
                            <p>کالا ارسال و اطلاعات حمل ثبت شده است.</p>
                        </div>
                    </article>

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">✅</span>
                        <div>
                            <h3>تحویل شده</h3>
                            <p>سفارش تحویل مشتری شده و عملیات پایان یافته است.</p>
                        </div>
                    </article>

                </section>


                <section class="hom-help-important">

                    <div class="hom-help-important__icon">
                        ✎
                    </div>

                    <div>

                        <strong>
                            دلیل اصلاح چه زمانی لازم است؟
                        </strong>

                        <p>
                            زمانی که اطلاعاتی قبلاً ثبت شده و اکنون
                            قصد تغییر آن را دارید، سیستم ممکن است
                            «دلیل اصلاح» درخواست کند. دلیل واضح و
                            واقعی بنویسید تا سابقه تغییر قابل پیگیری باشد.
                        </p>

                        <p>
                            در اطلاعات پرداخت پس از تأیید دریافت وجه،
                            مبلغ و وضعیت پرداخت قابل تغییر نیستند.
                            فقط مرجع و توضیحات پرداخت را می‌توان با
                            ثبت دلیل اصلاح کرد.
                        </p>

                        <p>
                            اصلاح اطلاعات ثبت‌شده مشتری، قیمت‌ها
                            و اطلاعات ارسال نیز در سوابق عملیات
                            ثبت می‌شود.
                        </p>

                    </div>

                </section>


                <div class="hom-help-section-heading">
                    <span>اسناد و سوابق</span>
                    <h2>ابزارهای تکمیلی پرونده مشتری</h2>
                </div>


                <section class="hom-help-tools">

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">🧾</span>
                        <div>
                            <h3>چاپ فاکتور</h3>
                            <p>
                                نسخه چاپی فاکتور شامل اطلاعات طرفین،
                                اقلام و مبالغ سفارش.
                            </p>
                        </div>
                    </article>

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">📦</span>
                        <div>
                            <h3>برگه انبار بدون قیمت</h3>
                            <p>
                                نسخه مناسب انبار و آماده‌سازی کالا
                                بدون نمایش قیمت فروش.
                            </p>
                        </div>
                    </article>

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">🏷️</span>
                        <div>
                            <h3>برچسب ارسال</h3>
                            <p>
                                اطلاعات موردنیاز برای شناسایی مرسوله
                                و مقصد ارسال.
                            </p>
                        </div>
                    </article>

                    <article class="hom-help-tool">
                        <span class="hom-help-tool__icon">🕘</span>
                        <div>
                            <h3>رخدادها و سوابق</h3>
                            <p>
                                سابقه قیمت‌گذاری، تأیید، اصلاح،
                                پرداخت و مراحل ارسال را نشان می‌دهد.
                            </p>
                        </div>
                    </article>

                </section>


                <section class="hom-help-warning">

                    <div class="hom-help-warning__head">

                        <span class="hom-help-warning__icon">⚠️</span>

                        <div>
                            <span>قبل از ثبت نهایی</span>
                            <h2>نکات مهم رسیدگی به مشتری</h2>
                        </div>

                    </div>

                    <ul>
                        <li>
                            قیمت اقلام و هزینه ارسال را پیش از
                            تأیید پیش‌فاکتور دوباره کنترل کنید.
                        </li>

                        <li>
                            پرداخت را فقط پس از مشاهده قطعی واریز
                            تأیید کنید.
                        </li>

                        <li>
                            برای اصلاح اطلاعات قبلی، دلیل واقعی
                            و قابل فهم ثبت کنید.
                        </li>

                        <li>
                            پیش از ارسال، مقصد، روش حمل، باربری
                            و کد رهگیری را کنترل کنید.
                        </li>

                        <li>
                            وضعیت ارسال یا تحویل را پیش از انجام
                            واقعی آن مرحله ثبت نکنید.
                        </li>

                        <li>
                            اطلاعات آزمایشی یا غیرواقعی در پرونده
                            مشتری ثبت نکنید؛ رخدادها سابقه رسمی
                            عملیات پنل هستند.
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
