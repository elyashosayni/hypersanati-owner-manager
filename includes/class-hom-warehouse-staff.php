<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Warehouse_Staff {

    private const META_MANAGED =
        '_hom_warehouse_staff_managed';

    private const META_ACTIVE =
        '_hom_warehouse_staff_active';

    private const META_CREATED_AT =
        '_hom_warehouse_staff_created_at';

    private const META_CREATED_BY =
        '_hom_warehouse_staff_created_by';

    private const META_UPDATED_AT =
        '_hom_warehouse_staff_updated_at';

    private const META_UPDATED_BY =
        '_hom_warehouse_staff_updated_by';


    public static function register() {

        add_action(
            'admin_post_hom_create_warehouse_staff',
            [self::class, 'handle_create']
        );

        add_action(
            'admin_post_hom_toggle_warehouse_staff',
            [self::class, 'handle_toggle']
        );
    }


    public static function url() {

        return add_query_arg(
            'view',
            'warehouse-staff',
            HOM_Router::panel_url()
        );
    }


    private static function is_owner_account(
        $user
    ) {

        if (!($user instanceof WP_User)) {
            return false;
        }


        return user_can(
            $user,
            HOM_Capabilities::
                CAP_ACCESS_PANEL
        );
    }



    public static function users() {

        $by_id = [];


        $managed =
            get_users(
                [
                    'meta_key' =>
                        self::META_MANAGED,

                    'meta_value' =>
                        'yes',

                    'orderby' =>
                        'display_name',

                    'order' =>
                        'ASC',
                ]
            );


        foreach ($managed as $user) {

            if ($user instanceof WP_User) {

                $by_id[$user->ID] =
                    $user;
            }
        }


        /*
         * Also include users that already have the role
         * even if they predate this management module.
         */
        $role_users =
            get_users(
                [
                    'role' =>
                        HOM_Capabilities::
                            WAREHOUSE_ROLE,

                    'orderby' =>
                        'display_name',

                    'order' =>
                        'ASC',
                ]
            );


        foreach ($role_users as $user) {

            if ($user instanceof WP_User) {

                $by_id[$user->ID] =
                    $user;
            }
        }


        $users =
            array_values(
                $by_id
            );


        usort(
            $users,
            static function (
                $a,
                $b
            ) {

                return strcasecmp(
                    (string)
                    $a->display_name,
                    (string)
                    $b->display_name
                );
            }
        );


        return $users;
    }


    public static function is_active(
        $user
    ) {

        if (!($user instanceof WP_User)) {
            return false;
        }


        return
            in_array(
                HOM_Capabilities::
                    WAREHOUSE_ROLE,
                (array) $user->roles,
                true
            ) &&
            user_can(
                $user,
                HOM_Capabilities::
                    CAP_VERIFY_WAREHOUSE
            );
    }


    public static function create(
        array $input,
        $actor_user_id
    ) {

        $actor_user_id =
            absint(
                $actor_user_id
            );


        if (
            $actor_user_id < 1 ||
            !user_can(
                $actor_user_id,
                HOM_Capabilities::
                    CAP_MANAGE_WAREHOUSE_STAFF
            )
        ) {

            return new WP_Error(
                'invalid_actor',
                'اجازه مدیریت مسئولین انبار وجود ندارد.'
            );
        }


        $display_name =
            sanitize_text_field(
                (string)
                ($input['display_name'] ?? '')
            );


        $raw_username =
            trim(
                (string)
                ($input['username'] ?? '')
            );


        $username =
            sanitize_user(
                $raw_username,
                true
            );


        $password =
            (string)
            ($input['password'] ?? '');


        if ('' === $display_name) {

            return new WP_Error(
                'display_name_required',
                'نام مسئول انبار را وارد کنید.'
            );
        }


        if (
            '' === $username ||
            !validate_username(
                $username
            )
        ) {

            return new WP_Error(
                'username_invalid',
                'نام کاربری معتبر وارد کنید.'
            );
        }


        $existing_user_id =
            username_exists(
                $username
            );


        if ($existing_user_id) {

            $existing_user =
                get_userdata(
                    $existing_user_id
                );


            if (
                self::is_owner_account(
                    $existing_user
                )
            ) {

                return new WP_Error(
                    'owner_account_conflict',
                    'این نام کاربری متعلق به مدیر فروشگاه است. برای مسئول تأیید انبار باید یک حساب کاربری مستقل ایجاد شود.'
                );
            }


            return new WP_Error(
                'username_exists',
                'این نام کاربری قبلاً ثبت شده است.'
            );
        }


        if (strlen($password) < 8) {

            return new WP_Error(
                'password_short',
                'رمز اولیه باید حداقل ۸ کاراکتر باشد.'
            );
        }


        $user_id =
            wp_insert_user(
                [
                    'user_login' =>
                        $username,

                    'user_pass' =>
                        $password,

                    'display_name' =>
                        $display_name,

                    'role' =>
                        HOM_Capabilities::
                            WAREHOUSE_ROLE,
                ]
            );


        if (is_wp_error($user_id)) {
            return $user_id;
        }


        $user_id =
            absint(
                $user_id
            );


        update_user_meta(
            $user_id,
            self::META_MANAGED,
            'yes'
        );

        update_user_meta(
            $user_id,
            self::META_ACTIVE,
            'yes'
        );

        update_user_meta(
            $user_id,
            self::META_CREATED_AT,
            current_time('mysql')
        );

        update_user_meta(
            $user_id,
            self::META_CREATED_BY,
            $actor_user_id
        );

        update_user_meta(
            $user_id,
            self::META_UPDATED_AT,
            current_time('mysql')
        );

        update_user_meta(
            $user_id,
            self::META_UPDATED_BY,
            $actor_user_id
        );


        return get_userdata(
            $user_id
        );
    }


    public static function set_active(
        $user_id,
        $active,
        $actor_user_id
    ) {

        $user_id =
            absint(
                $user_id
            );

        $actor_user_id =
            absint(
                $actor_user_id
            );


        if (
            $actor_user_id < 1 ||
            !user_can(
                $actor_user_id,
                HOM_Capabilities::
                    CAP_MANAGE_WAREHOUSE_STAFF
            )
        ) {

            return new WP_Error(
                'invalid_actor',
                'اجازه مدیریت مسئولین انبار وجود ندارد.'
            );
        }


        $user =
            get_userdata(
                $user_id
            );


        if (!($user instanceof WP_User)) {

            return new WP_Error(
                'staff_missing',
                'کاربر موردنظر پیدا نشد.'
            );
        }


        $managed =
            'yes' ===
            get_user_meta(
                $user_id,
                self::META_MANAGED,
                true
            );


        $has_role =
            in_array(
                HOM_Capabilities::
                    WAREHOUSE_ROLE,
                (array) $user->roles,
                true
            );


        if (
            !$managed &&
            !$has_role
        ) {

            return new WP_Error(
                'staff_not_managed',
                'این حساب جزو مسئولین تأیید انبار نیست.'
            );
        }


        $active =
            (bool) $active;


        /*
         * Never allow one WordPress account to become both
         * Shop Owner and Warehouse Verifier.
         *
         * Disabling is still allowed so an accidental legacy
         * conflict can always be removed safely.
         */
        if (
            $active &&
            self::is_owner_account(
                $user
            )
        ) {

            return new WP_Error(
                'owner_account_conflict',
                'حساب مدیر فروشگاه نمی‌تواند به‌عنوان مسئول تأیید انبار فعال شود. یک حساب مستقل برای انبار ایجاد کنید.'
            );
        }


        if ($active) {

            if (!$has_role) {

                $user->add_role(
                    HOM_Capabilities::
                        WAREHOUSE_ROLE
                );
            }


            update_user_meta(
                $user_id,
                self::META_ACTIVE,
                'yes'
            );

        } else {

            if ($has_role) {

                $user->remove_role(
                    HOM_Capabilities::
                        WAREHOUSE_ROLE
                );
            }


            update_user_meta(
                $user_id,
                self::META_ACTIVE,
                'no'
            );
        }


        update_user_meta(
            $user_id,
            self::META_MANAGED,
            'yes'
        );

        update_user_meta(
            $user_id,
            self::META_UPDATED_AT,
            current_time('mysql')
        );

        update_user_meta(
            $user_id,
            self::META_UPDATED_BY,
            $actor_user_id
        );


        clean_user_cache(
            $user_id
        );


        return get_userdata(
            $user_id
        );
    }


    private static function redirect(
        $notice,
        $error = ''
    ) {

        $args = [
            'notice' =>
                sanitize_key(
                    (string) $notice
                ),
        ];


        if ($error) {

            $args['warehouse_staff_error'] =
                sanitize_key(
                    (string) $error
                );
        }


        wp_safe_redirect(
            add_query_arg(
                $args,
                self::url()
            )
        );

        exit;
    }


    public static function handle_create() {

        if (
            !is_user_logged_in() ||
            !current_user_can(
                HOM_Capabilities::
                    CAP_MANAGE_WAREHOUSE_STAFF
            )
        ) {

            wp_die(
                'دسترسی غیرمجاز.',
                '',
                [
                    'response' => 403,
                ]
            );
        }


        check_admin_referer(
            'hom_create_warehouse_staff'
        );


        $result =
            self::create(
                [
                    'display_name' =>
                        isset($_POST['display_name'])
                            ? wp_unslash(
                                $_POST['display_name']
                            )
                            : '',

                    'username' =>
                        isset($_POST['username'])
                            ? wp_unslash(
                                $_POST['username']
                            )
                            : '',

                    'password' =>
                        isset($_POST['password'])
                            ? (string)
                            wp_unslash(
                                $_POST['password']
                            )
                            : '',
                ],
                get_current_user_id()
            );


        if (is_wp_error($result)) {

            self::redirect(
                'warehouse-staff-error',
                $result->get_error_code()
            );
        }


        self::redirect(
            'warehouse-staff-created'
        );
    }


    public static function handle_toggle() {

        if (
            !is_user_logged_in() ||
            !current_user_can(
                HOM_Capabilities::
                    CAP_MANAGE_WAREHOUSE_STAFF
            )
        ) {

            wp_die(
                'دسترسی غیرمجاز.',
                '',
                [
                    'response' => 403,
                ]
            );
        }


        $user_id =
            isset($_POST['user_id'])
                ? absint(
                    $_POST['user_id']
                )
                : 0;


        check_admin_referer(
            'hom_toggle_warehouse_staff_' .
            $user_id
        );


        $active =
            isset($_POST['staff_active']) &&
            '1' ===
            (string)
            wp_unslash(
                $_POST['staff_active']
            );


        $result =
            self::set_active(
                $user_id,
                $active,
                get_current_user_id()
            );


        if (is_wp_error($result)) {

            self::redirect(
                'warehouse-staff-error',
                $result->get_error_code()
            );
        }


        self::redirect(
            $active
                ? 'warehouse-staff-enabled'
                : 'warehouse-staff-disabled'
        );
    }
}
