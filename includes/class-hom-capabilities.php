<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOM_Capabilities {

    /*
     * Permanent dedicated business-panel role.
     *
     * IMPORTANT:
     * This role is intentionally kept in WordPress even if
     * the plugin is deactivated or uninstalled, so users
     * never lose their assigned role.
     */
    public const ROLE =
        'olfatbearing_shop_owner';

    public const ROLE_LABEL =
        'مدیر پنل فروشگاه الفت';


    /*
     * Previous role used by versions <= 0.2.0.
     * Existing users are automatically migrated.
     */
    public const LEGACY_ROLE =
        'hypersanati_owner';


    public const ROLE_VERSION =
        '3';


    public const CAP_ACCESS_PANEL =
        'hom_access_owner_panel';

    public const CAP_VIEW_PRODUCTS =
        'hom_view_products';

    public const CAP_MANAGE_PRODUCT_IMAGES =
        'hom_manage_product_images';


    public const CAP_VIEW_ORDERS =
        'hom_view_orders';

    public const CAP_MANAGE_PREINVOICES =
        'hom_manage_preinvoices';

    public const CAP_MANAGE_FULFILLMENT =
        'hom_manage_order_fulfillment';


    /*
     * Reserved capabilities for future modules.
     * They are intentionally NOT granted to the
     * shop-owner role yet.
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

            self::CAP_VIEW_ORDERS => true,

            self::CAP_MANAGE_PREINVOICES => true,

            self::CAP_MANAGE_FULFILLMENT => true,
        ];
    }



    public static function all_plugin_capabilities() {

        return [
            self::CAP_ACCESS_PANEL,

            self::CAP_VIEW_PRODUCTS,

            self::CAP_MANAGE_PRODUCT_IMAGES,

            self::CAP_VIEW_ORDERS,

            self::CAP_MANAGE_PREINVOICES,

            self::CAP_MANAGE_FULFILLMENT,

            self::CAP_MANAGE_PRODUCT_PRICES,

            self::CAP_MANAGE_PRODUCT_CONTENT,

            self::CAP_MANAGE_PRODUCT_STOCK,
        ];
    }



    private static function ensure_owner_role() {

        $role =
            get_role(
                self::ROLE
            );


        if (!$role) {

            add_role(
                self::ROLE,
                self::ROLE_LABEL,
                self::owner_capabilities()
            );

            $role =
                get_role(
                    self::ROLE
                );
        }


        if (!$role) {
            return;
        }


        /*
         * Always make sure the dedicated role contains
         * exactly the currently enabled Owner permissions.
         */
        foreach (
            self::owner_capabilities()
            as $capability => $grant
        ) {

            $role->add_cap(
                $capability,
                $grant
            );
        }


        /*
         * Future capabilities must not accidentally become
         * available before their modules are released.
         */
        foreach (
            [
                self::CAP_MANAGE_PRODUCT_PRICES,
                self::CAP_MANAGE_PRODUCT_CONTENT,
                self::CAP_MANAGE_PRODUCT_STOCK,
            ]
            as $capability
        ) {

            $role->remove_cap(
                $capability
            );
        }
    }



    private static function migrate_legacy_role() {

        $legacy_role =
            get_role(
                self::LEGACY_ROLE
            );


        if (!$legacy_role) {
            return;
        }


        $users =
            get_users(
                [
                    'role' =>
                        self::LEGACY_ROLE,

                    'fields' =>
                        'all',
                ]
            );


        foreach ($users as $user) {

            if (
                !in_array(
                    self::ROLE,
                    (array) $user->roles,
                    true
                )
            ) {

                $user->add_role(
                    self::ROLE
                );
            }


            $user->remove_role(
                self::LEGACY_ROLE
            );
        }


        /*
         * Only remove the obsolete role AFTER every
         * assigned user has been migrated.
         */
        remove_role(
            self::LEGACY_ROLE
        );
    }



    public static function sync_roles() {

        self::ensure_owner_role();

        self::migrate_legacy_role();


        /*
         * Administrators retain all plugin capabilities.
         */
        $administrator =
            get_role(
                'administrator'
            );


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

        $current =
            (string) get_option(
                'hom_role_version',
                ''
            );


        if (
            $current ===
            self::ROLE_VERSION
        ) {
            return;
        }


        self::sync_roles();
    }
}
