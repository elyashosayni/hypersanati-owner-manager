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


        HOM_View::render();

        exit;
    }
}
