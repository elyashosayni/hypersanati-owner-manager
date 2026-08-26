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
</head>

<body class="hom-page">
        <?php
    }


    private static function document_end() {
        ?>
</body>
</html>
        <?php
    }


    private static function render_login() {

        self::document_start(
            'ورود به پنل مدیریت'
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
                پنل مدیریت کسب‌وکار
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
                    ورود به پنل مدیریت
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
            'پنل مدیریت کسب‌وکار'
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
                    مدیریت کسب‌وکار
                </span>
            </div>

        </div>


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
                <span class="hom-nav-icon">
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
                echo 'products' === $current_view
                    ? 'is-active'
                    : '';
                ?>"
            >
                <span class="hom-nav-icon">
                    ▦
                </span>

                <span>
                    محصولات
                </span>
            </a>

        </nav>

    </aside>


    <div class="hom-workspace">

        <header class="hom-topbar">

            <div>

                <span class="hom-topbar-label">
                    پنل مدیریت
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

            if ('products' === $current_view) {

                self::render_products_content();

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
                    پنل مدیریت کسب‌وکار
                </h1>

                <p>
                    از این بخش می‌توانید محصولات فروشگاه را مشاهده و جستجو کنید.
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
                    پنل مدیریت آماده استفاده است
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
                    محصولات
                </strong>

                <span class="hom-card-meta">
                    مشاهده و جستجوی محصولات
                </span>

            </article>

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

        ?>

        <div class="hom-page-heading hom-products-heading">

            <div>

                <span class="hom-eyebrow">
                    PRODUCTS
                </span>

                <h1>
                    محصولات
                </h1>

                <p>
                    جستجو بر اساس نام، ID، SKU، Part Number یا برند
                </p>

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
                        placeholder="مثلاً 6205، KSM-1203 یا 5799"
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

                                <button
                                    type="button"
                                    class="hom-button hom-button-secondary"
                                    disabled
                                    title="در مرحله بعد فعال می‌شود"
                                >
                                    مدیریت تصاویر
                                </button>

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

}
