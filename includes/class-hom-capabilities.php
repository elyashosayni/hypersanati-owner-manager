<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOM_Capabilities {

    public const ROLE = 'hypersanati_owner';

    public const ROLE_VERSION = '1';

    public const CAP_ACCESS_PANEL =
        'hom_access_owner_panel';

    public const CAP_VIEW_PRODUCTS =
        'hom_view_products';

    public const CAP_MANAGE_PRODUCT_IMAGES =
        'hom_manage_product_images';

    /*
     * Reserved capabilities for future modules.
     * They are intentionally NOT granted to the Owner role yet.
     */
    public const CAP_MANAGE_PRODUCT_PRICES =
        'hom_manage_product_prices';

    public const CAP_MANAGE_PRODUCT_CONTENT =
        'hom_manage_product_content';

    public const CAP_MANAGE_PRODUCT_STOCK =
        'hom_manage_product_stock';


    public static function owner_capabilities() {

        return [
            'read' => true,

            self::CAP_ACCESS_PANEL => true,

            self::CAP_VIEW_PRODUCTS => true,

            self::CAP_MANAGE_PRODUCT_IMAGES => true,
        ];
    }


    public static function all_plugin_capabilities() {

        return [
            self::CAP_ACCESS_PANEL,

            self::CAP_VIEW_PRODUCTS,

            self::CAP_MANAGE_PRODUCT_IMAGES,

            self::CAP_MANAGE_PRODUCT_PRICES,

            self::CAP_MANAGE_PRODUCT_CONTENT,

            self::CAP_MANAGE_PRODUCT_STOCK,
        ];
    }


    public static function sync_roles() {

        $role = get_role(self::ROLE);

        if (!$role) {

            add_role(
                self::ROLE,
                'مدیر کسب‌وکار',
                self::owner_capabilities()
            );

            $role = get_role(self::ROLE);
        }

        if ($role) {

            foreach (
                self::owner_capabilities()
                as $capability => $grant
            ) {
                $role->add_cap(
                    $capability,
                    $grant
                );
            }
        }


        /*
         * Administrators receive all plugin capabilities so that
         * the system can always be tested and managed safely.
         */
        $administrator = get_role('administrator');

        if ($administrator) {

            foreach (
                self::all_plugin_capabilities()
                as $capability
            ) {
                $administrator->add_cap(
                    $capability
                );
            }
        }


        update_option(
            'hom_role_version',
            self::ROLE_VERSION,
            false
        );
    }


    public static function maybe_sync() {

        $current = (string) get_option(
            'hom_role_version',
            ''
        );

        if ($current === self::ROLE_VERSION) {
            return;
        }

        self::sync_roles();
    }
}
