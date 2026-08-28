<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOM_Auth {

    private static $error = '';


    private const WAREHOUSE_RETURN_COOKIE =
        'hom_warehouse_return';

    private const WAREHOUSE_RETURN_TTL =
        1800;



    private static function base64url_encode(
        $value
    ) {

        return rtrim(
            strtr(
                base64_encode(
                    (string) $value
                ),
                '+/',
                '-_'
            ),
            '='
        );
    }



    private static function base64url_decode(
        $value
    ) {

        $value =
            strtr(
                (string) $value,
                '-_',
                '+/'
            );


        $padding =
            strlen($value) % 4;


        if ($padding) {

            $value .=
                str_repeat(
                    '=',
                    4 - $padding
                );
        }


        $decoded =
            base64_decode(
                $value,
                true
            );


        return false === $decoded
            ? ''
            : (string) $decoded;
    }



    private static function set_warehouse_return_cookie(
        $url
    ) {

        $url =
            esc_url_raw(
                (string) $url
            );


        if ('' === $url) {
            return;
        }


        $payload =
            self::base64url_encode(
                $url
            );


        $signature =
            hash_hmac(
                'sha256',
                $payload,
                wp_salt('auth')
            );


        $value =
            $payload .
            '.' .
            $signature;


        $options = [

            'expires' =>
                time() +
                self::WAREHOUSE_RETURN_TTL,

            'path' =>
                defined('COOKIEPATH') &&
                COOKIEPATH
                    ? COOKIEPATH
                    : '/',

            'secure' =>
                is_ssl(),

            'httponly' =>
                true,

            'samesite' =>
                'Lax',
        ];


        if (
            defined('COOKIE_DOMAIN') &&
            COOKIE_DOMAIN
        ) {

            $options['domain'] =
                COOKIE_DOMAIN;
        }


        setcookie(
            self::WAREHOUSE_RETURN_COOKIE,
            $value,
            $options
        );


        $_COOKIE[
            self::WAREHOUSE_RETURN_COOKIE
        ] =
            $value;
    }



    private static function clear_warehouse_return_cookie() {

        $options = [

            'expires' =>
                time() - HOUR_IN_SECONDS,

            'path' =>
                defined('COOKIEPATH') &&
                COOKIEPATH
                    ? COOKIEPATH
                    : '/',

            'secure' =>
                is_ssl(),

            'httponly' =>
                true,

            'samesite' =>
                'Lax',
        ];


        if (
            defined('COOKIE_DOMAIN') &&
            COOKIE_DOMAIN
        ) {

            $options['domain'] =
                COOKIE_DOMAIN;
        }


        setcookie(
            self::WAREHOUSE_RETURN_COOKIE,
            '',
            $options
        );


        unset(
            $_COOKIE[
                self::WAREHOUSE_RETURN_COOKIE
            ]
        );
    }



    private static function read_warehouse_return_cookie() {

        $raw =
            isset(
                $_COOKIE[
                    self::WAREHOUSE_RETURN_COOKIE
                ]
            )
                ? sanitize_text_field(
                    wp_unslash(
                        $_COOKIE[
                            self::WAREHOUSE_RETURN_COOKIE
                        ]
                    )
                )
                : '';


        if (
            '' === $raw ||
            false === strpos(
                $raw,
                '.'
            )
        ) {
            return '';
        }


        [
            $payload,
            $signature,
        ] =
            array_pad(
                explode(
                    '.',
                    $raw,
                    2
                ),
                2,
                ''
            );


        $expected =
            hash_hmac(
                'sha256',
                $payload,
                wp_salt('auth')
            );


        if (
            '' === $signature ||
            !hash_equals(
                $expected,
                $signature
            )
        ) {

            self::clear_warehouse_return_cookie();

            return '';
        }


        $url =
            self::base64url_decode(
                $payload
            );


        if ('' === $url) {

            self::clear_warehouse_return_cookie();

            return '';
        }


        $home =
            wp_parse_url(
                home_url('/')
            );


        $target =
            wp_parse_url(
                $url
            );


        if (
            !$target ||
            empty($target['host']) ||
            empty($home['host']) ||
            strtolower($target['host'])
                !==
            strtolower($home['host']) ||
            (int) ($target['port'] ?? 0)
                !==
            (int) ($home['port'] ?? 0)
        ) {

            self::clear_warehouse_return_cookie();

            return '';
        }


        $query = [];


        parse_str(
            (string)
            ($target['query'] ?? ''),
            $query
        );


        $view =
            sanitize_key(
                (string)
                ($query['view'] ?? '')
            );


        $order_id =
            absint(
                $query['order_id']
                ?? 0
            );


        $token =
            sanitize_text_field(
                (string)
                (
                    $query[
                        'warehouse_token'
                    ]
                    ?? ''
                )
            );


        if (
            'warehouse-check' !== $view ||
            !$order_id ||
            '' === $token ||
            !HOM_Warehouse_Verification::
                is_valid_request(
                    $order_id,
                    $token
                )
        ) {

            self::clear_warehouse_return_cookie();

            return '';
        }


        return add_query_arg(
            [
                'view' =>
                    'warehouse-check',

                'order_id' =>
                    $order_id,

                'warehouse_token' =>
                    $token,
            ],
            HOM_Router::panel_url()
        );
    }



    private static function warehouse_return_for_user(
        $user
    ) {

        $url =
            self::read_warehouse_return_cookie();


        if ('' === $url) {
            return '';
        }


        if (
            !($user instanceof WP_User) ||
            !user_can(
                $user,
                HOM_Capabilities::
                    CAP_VERIFY_WAREHOUSE
            )
        ) {

            self::clear_warehouse_return_cookie();

            return '';
        }


        self::clear_warehouse_return_cookie();


        return $url;
    }




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

        /*
         * Optional integration with HSB Auth.
         *
         * HSB Auth remains fully independent and only
         * exposes a generic WordPress filter. This plugin
         * decides where its dedicated owner role lands.
         */
        add_filter(
            'hsb_staff_login_redirect',
            [self::class, 'filter_hsb_staff_login_redirect'],
            10,
            2
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



    public static function is_warehouse_check_page() {

        if (
            'GET' !==
            strtoupper(
                $_SERVER['REQUEST_METHOD']
                ?? 'GET'
            )
        ) {
            return false;
        }


        $view =
            isset($_GET['view'])
                ? sanitize_key(
                    wp_unslash(
                        $_GET['view']
                    )
                )
                : '';


        return
            'warehouse-check'
            ===
            $view;
    }



    private static function is_warehouse_confirm_action() {

        if (
            'POST' !==
            strtoupper(
                $_SERVER['REQUEST_METHOD']
                ?? ''
            )
        ) {
            return false;
        }


        $action =
            isset($_POST['hom_action'])
                ? sanitize_key(
                    wp_unslash(
                        $_POST[
                            'hom_action'
                        ]
                    )
                )
                : '';


        return
            'hom_confirm_warehouse'
            ===
            $action;
    }



    private static function is_warehouse_request() {

        return
            self::is_warehouse_check_page() ||
            self::is_warehouse_confirm_action();
    }



    private static function current_warehouse_return_url() {

        if (!self::is_warehouse_check_page()) {
            return '';
        }


        $order_id =
            isset($_GET['order_id'])
                ? absint(
                    $_GET['order_id']
                )
                : 0;


        $token =
            isset($_GET['warehouse_token'])
                ? sanitize_text_field(
                    wp_unslash(
                        $_GET[
                            'warehouse_token'
                        ]
                    )
                )
                : '';


        if (
            !$order_id ||
            '' === $token ||
            !HOM_Warehouse_Verification::
                is_valid_request(
                    $order_id,
                    $token
                )
        ) {
            return '';
        }


        return add_query_arg(
            [
                'view' =>
                    'warehouse-check',

                'order_id' =>
                    $order_id,

                'warehouse_token' =>
                    $token,
            ],
            HOM_Router::panel_url()
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

        $warehouse_request =
            self::is_warehouse_request();


        if (!is_user_logged_in()) {

            if (
                self::is_warehouse_check_page()
            ) {

                $return_url =
                    self::current_warehouse_return_url();


                if ($return_url) {

                    self::set_warehouse_return_cookie(
                        $return_url
                    );
                }
            }


            wp_safe_redirect(
                self::account_url()
            );

            exit;
        }


        /*
         * Warehouse verifiers may open ONLY the QR workflow.
         * They do not receive access to the Owner dashboard.
         */
        if ($warehouse_request) {

            if (
                current_user_can(
                    HOM_Capabilities::
                        CAP_VERIFY_WAREHOUSE
                )
            ) {
                return;
            }


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

        $warehouse_return =
            self::warehouse_return_for_user(
                $user
            );


        if ($warehouse_return) {
            return $warehouse_return;
        }


        if (
            self::is_restricted_owner_user(
                $user
            )
        ) {

            return self::account_url();
        }

        return $redirect;
    }



    public static function filter_hsb_staff_login_redirect(
        $redirect,
        $user
    ) {

        $warehouse_return =
            self::warehouse_return_for_user(
                $user
            );


        if ($warehouse_return) {
            return $warehouse_return;
        }


        if (
            self::is_restricted_owner_user(
                $user
            )
        ) {
            return HOM_Router::panel_url();
        }

        return $redirect;
    }



    public static function filter_login_redirect(
        $redirect_to,
        $requested_redirect_to,
        $user
    ) {

        $warehouse_return =
            self::warehouse_return_for_user(
                $user
            );


        if ($warehouse_return) {
            return $warehouse_return;
        }


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
