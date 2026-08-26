<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOM_Dependencies {

    public static function is_ready() {

        return empty(self::missing());
    }


    public static function missing() {

        $missing = [];

        if (!class_exists('WooCommerce')) {
            $missing[] = 'WooCommerce';
        }

        if (
            !class_exists('HSB_Auth_API') ||
            !class_exists('HSB_Login_Controller')
        ) {
            $missing[] = 'HSB Auth';
        }

        return $missing;
    }


    public static function register_notices() {

        add_action(
            'admin_notices',
            [self::class, 'render_admin_notice']
        );
    }


    public static function render_admin_notice() {

        if (!current_user_can('manage_options')) {
            return;
        }

        $missing = self::missing();

        if (empty($missing)) {
            return;
        }

        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html(
                sprintf(
                    'HyperSanati Owner Manager requires: %s',
                    implode(', ', $missing)
                )
            )
        );
    }
}
