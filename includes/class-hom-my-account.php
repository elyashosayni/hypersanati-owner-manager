<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOM_My_Account {


    public static function register() {

        add_action(
            'wp_enqueue_scripts',
            [self::class, 'enqueue_assets']
        );
    }



    public static function should_show_owner_entry() {

        if (!is_user_logged_in()) {
            return false;
        }

        if (
            !current_user_can(
                HOM_Capabilities::CAP_ACCESS_PANEL
            )
        ) {
            return false;
        }


        $user =
            wp_get_current_user();


        if (!$user instanceof WP_User) {
            return false;
        }


        /*
         * The My Account management entry is intentionally
         * limited to these two roles only:
         *
         * - Dedicated Olfat shop-panel manager
         * - WordPress Administrator
         *
         * Other roles must not receive this entry merely
         * because they happen to have a plugin capability.
         */
        $allowed_roles = [
            HOM_Capabilities::ROLE,
            'administrator',
        ];


        if (
            !array_intersect(
                $allowed_roles,
                (array) $user->roles
            )
        ) {
            return false;
        }

        if (
            !function_exists('is_account_page') ||
            !is_account_page()
        ) {
            return false;
        }

        return true;
    }



    public static function enqueue_assets() {

        if (!self::should_show_owner_entry()) {
            return;
        }


        $css_path =
            HOM_PATH
            . 'assets/css/my-account-owner.css';

        $js_path =
            HOM_PATH
            . 'assets/js/my-account-owner.js';


        wp_enqueue_style(
            'hom-owner-my-account',
            HOM_URL
                . 'assets/css/my-account-owner.css',
            [],
            file_exists($css_path)
                ? (string) filemtime($css_path)
                : HOM_VERSION
        );


        wp_enqueue_script(
            'hom-owner-my-account',
            HOM_URL
                . 'assets/js/my-account-owner.js',
            [],
            file_exists($js_path)
                ? (string) filemtime($js_path)
                : HOM_VERSION,
            true
        );


        wp_localize_script(
            'hom-owner-my-account',
            'HOMOwnerAccount',
            [
                'panelUrl' =>
                    HOM_Router::panel_url(),

                'title' =>
                    'مدیریت فروشگاه',

                'description' =>
                    'دسترسی ویژه شما برای مدیریت فروشگاه و سامان‌دهی تصاویر محصولات',

                'buttonLabel' =>
                    'ورود به پنل مدیریت فروشگاه',

                'sidebarLabel' =>
                    'مدیریت فروشگاه',

                'helpUrl' =>
                    add_query_arg(
                        'view',
                        'help',
                        HOM_Router::panel_url()
                    ),

                'badgeLabel' =>
                    'دسترسی ویژه مدیر فروشگاه',

                'noticeTitle' =>
                    'دسترسی مدیریت فروشگاه برای شما فعال است',

                'noticeText' =>
                    'به دلیل معرفی شما به‌عنوان مدیر فروشگاه الفت، دسترسی ویژه مدیریت فروشگاه برای این حساب فعال شده است. همکاری شما در کنترل و سامان‌دهی اطلاعات و تصاویر محصولات، نقش مهمی در مدیریت بهتر وب‌سایت دارد.',

                'warningTitle' =>
                    'تغییرات این بخش مستقیماً روی فروشگاه اعمال می‌شود',

                'warningText' =>
                    'پیش از حذف، جایگزینی یا ذخیره تصاویر، نام محصول، برند و کد فنی آن را بررسی کنید. اگر درباره یک تغییر مطمئن نیستید، آن را ذخیره نکنید.',

                'helpLabel' =>
                    'خواندن راهنمای مدیر فروشگاه',
            ]
        );
    }
}
