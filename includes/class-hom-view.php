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
            'product-images',
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

            if ('product-images' === $current_view) {

                self::render_product_images_content();

            } elseif ('products' === $current_view) {

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
                                انتخاب تصویر بدون تغییر نام یا اطلاعات فایل
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
