<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$role = get_role('hypersanati_owner');

if ($role) {
    remove_role('hypersanati_owner');
}

$administrator = get_role('administrator');

if ($administrator) {

    $capabilities = [
        'hom_access_owner_panel',
        'hom_view_products',
        'hom_manage_product_images',
        'hom_manage_product_prices',
        'hom_manage_product_content',
        'hom_manage_product_stock',
    ];

    foreach ($capabilities as $capability) {
        $administrator->remove_cap($capability);
    }
}

delete_option('hom_version');
delete_option('hom_role_version');
