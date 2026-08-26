<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}


/*
 * IMPORTANT:
 *
 * The dedicated olfatbearing_shop_owner role is deliberately
 * NOT removed during uninstall.
 *
 * WordPress roles are persisted in the database, therefore
 * users assigned to this role remain valid even when the
 * plugin is deactivated or removed.
 *
 * If the plugin is installed again, its capabilities will
 * be synchronized automatically.
 */


$administrator =
    get_role(
        'administrator'
    );


if ($administrator) {

    $capabilities = [
        'hom_access_owner_panel',
        'hom_view_products',
        'hom_manage_product_images',
        'hom_manage_product_prices',
        'hom_manage_product_content',
        'hom_manage_product_stock',
    ];


    foreach (
        $capabilities
        as $capability
    ) {

        $administrator->remove_cap(
            $capability
        );
    }
}


delete_option(
    'hom_version'
);

delete_option(
    'hom_role_version'
);
