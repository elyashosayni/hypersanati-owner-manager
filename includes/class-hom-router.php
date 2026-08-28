<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOM_Router {

    public const QUERY_VAR = 'hom_owner_panel';


    public static function register() {

        add_action(
            'init',
            [self::class, 'register_rewrite_rule']
        );

        add_filter(
            'query_vars',
            [self::class, 'register_query_var']
        );

        add_action(
            'template_redirect',
            [self::class, 'maybe_render']
        );
    }


    public static function register_rewrite_rule() {

        add_rewrite_rule(
            '^owner-panel/?$',
            'index.php?' . self::QUERY_VAR . '=1',
            'top'
        );
    }


    public static function register_query_var($vars) {

        $vars[] = self::QUERY_VAR;

        return $vars;
    }


    public static function is_owner_panel() {

        return '1' === (string) get_query_var(
            self::QUERY_VAR
        );
    }


    public static function panel_url() {

        return home_url('/owner-panel/');
    }


    private static function handle_owner_action_request() {

        $method =
            strtoupper(
                $_SERVER['REQUEST_METHOD']
                ?? 'GET'
            );


        if ('POST' === $method) {

            $action =
                isset($_POST['hom_action'])
                    ? sanitize_key(
                        wp_unslash(
                            $_POST['hom_action']
                        )
                    )
                    : '';

        } else {

            $action =
                isset($_GET['hom_action'])
                    ? sanitize_key(
                        wp_unslash(
                            $_GET['hom_action']
                        )
                    )
                    : '';
        }


        if ('' === $action) {
            return;
        }


        $post_actions = [

            'hom_save_preinvoice_prices' =>
                [
                    HOM_Orders::class,
                    'handle_save_preinvoice_prices',
                ],

            'hom_approve_preinvoice' =>
                [
                    HOM_Orders::class,
                    'handle_approve_preinvoice',
                ],

            'hom_save_order_fulfillment' =>
                [
                    HOM_Orders::class,
                    'handle_save_order_fulfillment',
                ],

            'hom_confirm_warehouse' =>
                [
                    HOM_Warehouse_Verification::class,
                    'handle_confirm',
                ],

            'hom_create_warehouse_staff' =>
                [
                    HOM_Warehouse_Staff::class,
                    'handle_create',
                ],

            'hom_toggle_warehouse_staff' =>
                [
                    HOM_Warehouse_Staff::class,
                    'handle_toggle',
                ],

            'hom_confirm_manual_payment' =>
                [
                    HOM_Orders::class,
                    'handle_confirm_manual_payment',
                ],

            'hom_correct_manual_payment' =>
                [
                    HOM_Orders::class,
                    'handle_correct_manual_payment',
                ],

            'hom_save_b2b_customer' =>
                [
                    HOM_Orders::class,
                    'handle_save_b2b_customer',
                ],

            'hom_save_seller_settings' =>
                [
                    HOM_Seller_Settings::class,
                    'handle_save',
                ],
        ];


        $get_actions = [

            'hom_print_order_document' =>
                [
                    HOM_Order_Documents::class,
                    'handle_print',
                ],
        ];


        if (
            'POST' === $method &&
            isset($post_actions[$action])
        ) {

            call_user_func(
                $post_actions[$action]
            );

            return;
        }


        if (
            'GET' === $method &&
            isset($get_actions[$action])
        ) {

            call_user_func(
                $get_actions[$action]
            );

            return;
        }
    }


    public static function maybe_render() {

        if (!self::is_owner_panel()) {
            return;
        }

        global $wp_query;

        if ($wp_query) {
            $wp_query->is_404 = false;
        }

        status_header(200);
        nocache_headers();

        /*
         * Owner Panel must never be indexed by search engines.
         * Keep this HTTP protection in addition to the HTML robots meta.
         */
        header(
            'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet',
            true
        );

        HOM_Auth::guard_owner_panel();

        HOM_Auth::handle_request();

        self::handle_owner_action_request();


        if (
            HOM_Auth::is_warehouse_check_page() &&
            !current_user_can(
                HOM_Capabilities::CAP_ACCESS_PANEL
            ) &&
            current_user_can(
                HOM_Capabilities::
                    CAP_VERIFY_WAREHOUSE
            )
        ) {

            HOM_Warehouse_Verification::
                render_standalone();

            exit;
        }


        HOM_View::render();

        exit;
    }
}
