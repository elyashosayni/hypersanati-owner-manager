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


    public const WAREHOUSE_ROLE =
        'olfatbearing_warehouse_verifier';

    public const WAREHOUSE_ROLE_LABEL =
        'مسئول تأیید انبار';


    /*
     * Previous role used by versions <= 0.2.0.
     * Existing users are automatically migrated.
     */
    public const LEGACY_ROLE =
        'hypersanati_owner';


    public const ROLE_VERSION =
        '6';


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

    public const CAP_VERIFY_WAREHOUSE =
        'hom_verify_warehouse_orders';

    public const CAP_MANAGE_WAREHOUSE_STAFF =
        'hom_manage_warehouse_staff';


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

            self::CAP_MANAGE_WAREHOUSE_STAFF => true,
        ];
    }



    public static function warehouse_capabilities() {

        return [
            'read' => true,

            self::CAP_VERIFY_WAREHOUSE => true,
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

            self::CAP_VERIFY_WAREHOUSE,

            self::CAP_MANAGE_WAREHOUSE_STAFF,

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
         * Strict separation of duties:
         *
         * A Shop Owner account manages warehouse staff but
         * must not itself act as a warehouse verifier.
         * A separate Warehouse Verifier account is required.
         *
         * remove_cap() is intentional here because previous
         * role versions granted this permission to owners.
         */
        $role->remove_cap(
            self::CAP_VERIFY_WAREHOUSE
        );


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



    private static function ensure_warehouse_role() {

        $role =
            get_role(
                self::WAREHOUSE_ROLE
            );


        if (!$role) {

            add_role(
                self::WAREHOUSE_ROLE,
                self::WAREHOUSE_ROLE_LABEL,
                self::warehouse_capabilities()
            );

            $role =
                get_role(
                    self::WAREHOUSE_ROLE
                );
        }


        if (!$role) {
            return;
        }


        foreach (
            self::warehouse_capabilities()
            as $capability => $grant
        ) {

            $role->add_cap(
                $capability,
                $grant
            );
        }


        /*
         * Warehouse verifiers are deliberately isolated
         * from every other Owner Manager permission.
         */
        foreach (
            self::all_plugin_capabilities()
            as $capability
        ) {

            if (
                self::CAP_VERIFY_WAREHOUSE
                ===
                $capability
            ) {
                continue;
            }


            $role->remove_cap(
                $capability
            );
        }
    }



    private static function enforce_role_separation() {

        /*
         * Defensive cleanup for accounts whose roles may have
         * been changed outside this module.
         *
         * Any account that can access the Owner Panel must not
         * simultaneously hold the Warehouse Verifier role.
         */
        $warehouse_users =
            get_users(
                [
                    'role' =>
                        self::WAREHOUSE_ROLE,

                    'fields' =>
                        'all',
                ]
            );


        foreach ($warehouse_users as $user) {

            if (
                $user instanceof WP_User &&
                user_can(
                    $user,
                    self::CAP_ACCESS_PANEL
                )
            ) {

                $user->remove_role(
                    self::WAREHOUSE_ROLE
                );
            }
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

        self::ensure_warehouse_role();

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


        self::enforce_role_separation();


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
