<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Plugin {

    private static $instance = null;

    private $booted = false;


    private function __construct() {
    }


    public static function instance() {

        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    public function boot() {

        if ($this->booted) {
            return;
        }

        $this->booted = true;

        HOM_Capabilities::maybe_sync();

        HOM_Router::register();
        HOM_Auth::register();
        HOM_Product_Images::register();

        /**
         * Fires after Owner Manager dependencies
         * and core services are ready.
         */
        do_action(
            'hom_loaded',
            $this
        );
    }
}
