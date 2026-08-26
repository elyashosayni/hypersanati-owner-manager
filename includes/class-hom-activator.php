<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOM_Activator {

    public static function activate() {

        if (
            !class_exists('WooCommerce') ||
            !class_exists('HSB_Auth_API')
        ) {

            deactivate_plugins(
                HOM_BASENAME
            );

            wp_die(
                esc_html(
                    'HyperSanati Owner Manager requires WooCommerce and HSB Auth to be active.'
                ),
                esc_html(
                    'Plugin dependency error'
                ),
                [
                    'back_link' => true,
                ]
            );
        }

        HOM_Capabilities::sync_roles();

        update_option(
            'hom_version',
            HOM_VERSION,
            false
        );
    }
}
