<?php
/**
 * Plugin Name: HyperSanati Owner Manager
 * Plugin URI: https://olfatbearing.com
 * Description: Scalable frontend business management panel for HyperSanati WooCommerce.
 * Version: 0.1.0
 * Author: HyperSanati
 * Text Domain: hypersanati-owner-manager
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce, hsb-auth
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HOM_VERSION', '0.1.0');
define('HOM_PATH', plugin_dir_path(__FILE__));
define('HOM_URL', plugin_dir_url(__FILE__));
define('HOM_BASENAME', plugin_basename(__FILE__));

require_once HOM_PATH . 'includes/class-hom-dependencies.php';
require_once HOM_PATH . 'includes/class-hom-capabilities.php';
require_once HOM_PATH . 'includes/class-hom-router.php';
require_once HOM_PATH . 'includes/class-hom-auth.php';
require_once HOM_PATH . 'includes/class-hom-products.php';
require_once HOM_PATH . 'includes/class-hom-view.php';
require_once HOM_PATH . 'includes/class-hom-activator.php';
require_once HOM_PATH . 'includes/class-hom-plugin.php';

register_activation_hook(
    __FILE__,
    ['HOM_Activator', 'activate']
);

function hom_bootstrap() {

    if (!HOM_Dependencies::is_ready()) {
        HOM_Dependencies::register_notices();
        return;
    }

    HOM_Plugin::instance()->boot();
}

add_action(
    'plugins_loaded',
    'hom_bootstrap',
    20
);
