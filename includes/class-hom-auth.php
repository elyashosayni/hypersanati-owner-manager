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

        /*
         * WooCommerce normally redirects users without edit_posts
         * from wp-admin to My Account.
         *
         * Owner users must instead reach our own admin_init redirect,
         * which sends them to the dedicated Owner Panel.
         */
        add_filter(
            'woocommerce_prevent_admin_access',
            [self::class, 'filter_woocommerce_admin_access'],
            1
        );
    }


    public static function handle_request() {

        if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'] ?? '')) {
            return;
        }

        $action = isset($_POST['hom_action'])
            ? sanitize_key(wp_unslash($_POST['hom_action']))
            : '';

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
            !isset($_POST['hom_login_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['hom_login_nonce'])
                ),
                'hom_owner_login'
            )
        ) {
            self::$error =
                'درخواست ورود معتبر نیست. صفحه را تازه‌سازی و دوباره تلاش کنید.';

            return;
        }

        $username = isset($_POST['hom_username'])
            ? sanitize_user(
                wp_unslash($_POST['hom_username']),
                true
            )
            : '';

        $password = isset($_POST['hom_password'])
            ? (string) wp_unslash($_POST['hom_password'])
            : '';

        $remember = !empty($_POST['hom_remember']);

        if ('' === $username || '' === $password) {

            self::$error =
                'نام کاربری و رمز عبور را وارد کنید.';

            return;
        }

        /*
         * Verify credentials WITHOUT creating an authenticated
         * WordPress session first.
         *
         * This prevents non-owner accounts from briefly receiving
         * an authentication cookie before capability validation.
         */
        $user = wp_authenticate(
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


        /**
         * Extension point for a future second factor.
         *
         * HSB Auth is intentionally NOT called here yet.
         */
        $allowed = apply_filters(
            'hom_owner_login_allowed_after_password',
            true,
            $user
        );

        if (is_wp_error($allowed)) {

            self::$error =
                $allowed->get_error_message();

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
            HOM_Router::panel_url()
        );

        exit;
    }


    private static function handle_logout() {

        if (
            !isset($_POST['hom_logout_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['hom_logout_nonce'])
                ),
                'hom_owner_logout'
            )
        ) {
            return;
        }

        wp_logout();

        wp_safe_redirect(
            HOM_Router::panel_url()
        );

        exit;
    }


    public static function is_owner_logged_in() {

        return is_user_logged_in()
            && current_user_can(
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
        if (current_user_can('manage_options')) {
            return;
        }

        if (
            function_exists('wp_doing_ajax') &&
            wp_doing_ajax()
        ) {
            return;
        }

        wp_safe_redirect(
            HOM_Router::panel_url()
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
            !current_user_can('manage_options')
        ) {
            return false;
        }

        return $prevent_access;
    }


    public static function filter_admin_bar($show) {

        if (
            is_user_logged_in() &&
            current_user_can(
                HOM_Capabilities::CAP_ACCESS_PANEL
            ) &&
            !current_user_can('manage_options')
        ) {
            return false;
        }

        return $show;
    }
}
