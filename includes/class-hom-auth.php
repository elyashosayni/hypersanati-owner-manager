<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOM_Auth {

    private static $error = '';



    public static function register() {

        add_action(
            'admin_init',
            [self::class, 'restrict_wp_admin']
        );

        add_filter(
            'show_admin_bar',
            [self::class, 'filter_admin_bar']
        );

        add_filter(
            'woocommerce_prevent_admin_access',
            [self::class, 'filter_woocommerce_admin_access'],
            1
        );

        /*
         * Dedicated shop owners always land in the normal
         * WooCommerce My Account area after a standard login.
         */
        add_filter(
            'woocommerce_login_redirect',
            [self::class, 'filter_woocommerce_login_redirect'],
            10,
            2
        );

        add_filter(
            'login_redirect',
            [self::class, 'filter_login_redirect'],
            10,
            3
        );
    }



    public static function account_url() {

        if (
            function_exists(
                'wc_get_page_permalink'
            )
        ) {

            $url =
                wc_get_page_permalink(
                    'myaccount'
                );

            if (!empty($url)) {
                return $url;
            }
        }

        return home_url(
            '/my-account/'
        );
    }



    private static function is_restricted_owner_user(
        $user
    ) {

        if (!($user instanceof WP_User)) {
            return false;
        }

        if (
            !user_can(
                $user,
                HOM_Capabilities::CAP_ACCESS_PANEL
            )
        ) {
            return false;
        }

        /*
         * Administrators keep their normal WordPress
         * administration access and login behavior.
         */
        if (
            user_can(
                $user,
                'manage_options'
            )
        ) {
            return false;
        }

        return true;
    }



    public static function guard_owner_panel() {

        /*
         * Owner Panel is never a public login page anymore.
         *
         * Login happens through the normal website /
         * WooCommerce My Account flow.
         */
        if (!is_user_logged_in()) {

            wp_safe_redirect(
                self::account_url()
            );

            exit;
        }


        if (
            !current_user_can(
                HOM_Capabilities::CAP_ACCESS_PANEL
            )
        ) {

            wp_safe_redirect(
                self::account_url()
            );

            exit;
        }
    }



    public static function filter_woocommerce_login_redirect(
        $redirect,
        $user
    ) {

        if (
            self::is_restricted_owner_user(
                $user
            )
        ) {

            return self::account_url();
        }

        return $redirect;
    }



    public static function filter_login_redirect(
        $redirect_to,
        $requested_redirect_to,
        $user
    ) {

        if (
            self::is_restricted_owner_user(
                $user
            )
        ) {

            return self::account_url();
        }

        return $redirect_to;
    }



    public static function handle_request() {

        if (
            'POST' !==
            strtoupper(
                $_SERVER['REQUEST_METHOD']
                ?? ''
            )
        ) {
            return;
        }

        $action =
            isset($_POST['hom_action'])
                ? sanitize_key(
                    wp_unslash(
                        $_POST['hom_action']
                    )
                )
                : '';


        /*
         * Kept for backward compatibility.
         * The public Owner Panel login screen is no longer
         * reachable because guard_owner_panel() redirects
         * unauthenticated users to My Account.
         */
        if ('login' === $action) {

            self::handle_login();
            return;
        }


        if ('logout' === $action) {

            self::handle_logout();
        }
    }



    private static function handle_login() {

        if (
            !isset(
                $_POST['hom_login_nonce']
            ) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash(
                        $_POST[
                            'hom_login_nonce'
                        ]
                    )
                ),
                'hom_owner_login'
            )
        ) {

            self::$error =
                'درخواست ورود معتبر نیست. صفحه را تازه‌سازی و دوباره تلاش کنید.';

            return;
        }


        $username =
            isset($_POST['hom_username'])
                ? sanitize_user(
                    wp_unslash(
                        $_POST[
                            'hom_username'
                        ]
                    ),
                    true
                )
                : '';


        $password =
            isset($_POST['hom_password'])
                ? (string) wp_unslash(
                    $_POST[
                        'hom_password'
                    ]
                )
                : '';


        $remember =
            !empty(
                $_POST['hom_remember']
            );


        if (
            '' === $username ||
            '' === $password
        ) {

            self::$error =
                'نام کاربری و رمز عبور را وارد کنید.';

            return;
        }


        $user =
            wp_authenticate(
                $username,
                $password
            );


        if (
            is_wp_error($user) ||
            !($user instanceof WP_User) ||
            !user_can(
                $user,
                HOM_Capabilities::CAP_ACCESS_PANEL
            )
        ) {

            self::$error =
                'نام کاربری یا رمز عبور صحیح نیست.';

            return;
        }


        $allowed =
            apply_filters(
                'hom_owner_login_allowed_after_password',
                true,
                $user
            );


        if (is_wp_error($allowed)) {

            self::$error =
                $allowed
                    ->get_error_message();

            return;
        }


        if (true !== $allowed) {

            self::$error =
                'ورود در حال حاضر مجاز نیست.';

            return;
        }


        wp_set_current_user(
            $user->ID
        );

        wp_set_auth_cookie(
            $user->ID,
            $remember,
            is_ssl()
        );

        do_action(
            'wp_login',
            $user->user_login,
            $user
        );

        do_action(
            'hom_owner_logged_in',
            $user
        );


        wp_safe_redirect(
            self::account_url()
        );

        exit;
    }



    private static function handle_logout() {

        if (
            !isset(
                $_POST['hom_logout_nonce']
            ) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash(
                        $_POST[
                            'hom_logout_nonce'
                        ]
                    )
                ),
                'hom_owner_logout'
            )
        ) {
            return;
        }


        wp_logout();


        wp_safe_redirect(
            home_url('/')
        );

        exit;
    }



    public static function is_owner_logged_in() {

        return
            is_user_logged_in() &&
            current_user_can(
                HOM_Capabilities::CAP_ACCESS_PANEL
            );
    }



    public static function get_error() {

        return self::$error;
    }



    public static function restrict_wp_admin() {

        if (!is_user_logged_in()) {
            return;
        }


        if (
            !current_user_can(
                HOM_Capabilities::CAP_ACCESS_PANEL
            )
        ) {
            return;
        }


        /*
         * Full administrators retain normal wp-admin access.
         */
        if (
            current_user_can(
                'manage_options'
            )
        ) {
            return;
        }


        /*
         * Owner Panel AJAX requests use admin-ajax.php and
         * must remain available.
         */
        if (
            function_exists(
                'wp_doing_ajax'
            ) &&
            wp_doing_ajax()
        ) {
            return;
        }


        /*
         * Dedicated shop owners never see wp-admin.
         * They return to the custom My Account dashboard.
         */
        wp_safe_redirect(
            self::account_url()
        );

        exit;
    }



    public static function filter_woocommerce_admin_access(
        $prevent_access
    ) {

        if (
            is_user_logged_in() &&
            current_user_can(
                HOM_Capabilities::CAP_ACCESS_PANEL
            ) &&
            !current_user_can(
                'manage_options'
            )
        ) {

            /*
             * Let our admin_init handler perform the
             * controlled redirect to My Account.
             */
            return false;
        }

        return $prevent_access;
    }



    public static function filter_admin_bar(
        $show
    ) {

        if (
            is_user_logged_in() &&
            current_user_can(
                HOM_Capabilities::CAP_ACCESS_PANEL
            ) &&
            !current_user_can(
                'manage_options'
            )
        ) {

            return false;
        }

        return $show;
    }
}
