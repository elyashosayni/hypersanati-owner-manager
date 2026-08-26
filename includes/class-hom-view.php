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


    private static function render_dashboard() {

        $user = wp_get_current_user();

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
                href="<?php echo esc_url(HOM_Router::panel_url()); ?>"
                class="hom-nav-item is-active"
            >
                <span class="hom-nav-icon">
                    ⌂
                </span>

                <span>
                    صفحه اصلی
                </span>
            </a>


            <span class="hom-nav-item is-disabled">

                <span class="hom-nav-icon">
                    ▦
                </span>

                <span>
                    محصولات
                </span>

                <small>
                    مرحله بعد
                </small>

            </span>

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
                action="<?php echo esc_url(HOM_Router::panel_url()); ?>"
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

            <div class="hom-page-heading">

                <div>

                    <span class="hom-eyebrow">
                        DASHBOARD
                    </span>

                    <h1>
                        پنل مدیریت کسب‌وکار
                    </h1>

                    <p>
                        زیرساخت پنل آماده است. مدیریت محصولات در مرحله بعد به همین بخش اضافه می‌شود.
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
                        دسترسی مدیر کسب‌وکار فعال شد
                    </h2>

                    <p>
                        این پنل مستقل از مدیریت وردپرس طراحی شده و روی موبایل، تبلت، لپ‌تاپ و دسکتاپ قابل استفاده است.
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
                        بخش بعدی
                    </span>

                    <strong class="hom-card-value hom-card-value-text">
                        محصولات
                    </strong>

                    <span class="hom-card-meta">
                        جستجو، مشاهده و تصاویر
                    </span>

                </article>

            </section>

        </main>

    </div>

</div>
        <?php

        self::document_end();
    }
}
