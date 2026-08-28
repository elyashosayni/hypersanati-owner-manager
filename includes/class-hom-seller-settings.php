<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Seller_Settings {

    private const OPTION_KEY =
        'hom_seller_legal_data';


    public static function register() {

        add_action(
            'admin_post_hom_save_seller_settings',
            [self::class, 'handle_save']
        );
    }


    public static function data() {

        $stored =
            get_option(
                self::OPTION_KEY,
                []
            );


        if (!is_array($stored)) {
            $stored = [];
        }


        $country_state =
            (string)
            get_option(
                'woocommerce_default_country',
                ''
            );


        [$country, $state] =
            array_pad(
                explode(
                    ':',
                    $country_state,
                    2
                ),
                2,
                ''
            );


        $state_label =
            $state;


        if (
            $country &&
            $state &&
            class_exists('WC_Countries')
        ) {

            $countries =
                new WC_Countries();

            $states =
                $countries->get_states(
                    $country
                );


            if (
                is_array($states) &&
                isset($states[$state])
            ) {

                $state_label =
                    (string)
                    $states[$state];
            }
        }


        $woo_address =
            trim(
                implode(
                    '، ',
                    array_filter(
                        [
                            get_option(
                                'woocommerce_store_address',
                                ''
                            ),

                            get_option(
                                'woocommerce_store_address_2',
                                ''
                            ),

                            get_option(
                                'woocommerce_store_city',
                                ''
                            ),

                            $state_label,
                        ]
                    )
                )
            );


        $defaults = [

            'legal_name' =>
                get_bloginfo('name'),

            'national_id' =>
                '',

            'economic_code' =>
                '',

            'registration_no' =>
                '',

            'postcode' =>
                (string)
                get_option(
                    'woocommerce_store_postcode',
                    ''
                ),

            'phone' =>
                (string)
                get_option(
                    'woocommerce_store_phone',
                    ''
                ),

            'address' =>
                $woo_address,
        ];


        $data =
            wp_parse_args(
                $stored,
                $defaults
            );


        foreach (
            $defaults
            as $key => $default
        ) {

            $data[$key] =
                trim(
                    (string)
                    ($data[$key] ?? $default)
                );
        }


        /*
         * Older saved seller addresses may contain the raw
         * WooCommerce province code (for example THR).
         * Convert it to the human-readable province name.
         */
        if (
            $state &&
            $state_label &&
            $state !== $state_label &&
            !empty($data['address'])
        ) {

            $data['address'] =
                preg_replace(
                    '/(?<![\pL\pN])' .
                    preg_quote(
                        $state,
                        '/'
                    ) .
                    '(?![\pL\pN])/u',
                    $state_label,
                    $data['address']
                );
        }


        return $data;
    }


    public static function save(
        array $input,
        $actor_user_id
    ) {

        $actor_user_id =
            absint(
                $actor_user_id
            );


        if ($actor_user_id < 1) {

            return new WP_Error(
                'invalid_actor',
                'کاربر ثبت‌کننده معتبر نیست.'
            );
        }


        $data = [

            'legal_name' =>
                sanitize_text_field(
                    (string)
                    ($input['legal_name'] ?? '')
                ),

            'national_id' =>
                sanitize_text_field(
                    (string)
                    ($input['national_id'] ?? '')
                ),

            'economic_code' =>
                sanitize_text_field(
                    (string)
                    ($input['economic_code'] ?? '')
                ),

            'registration_no' =>
                sanitize_text_field(
                    (string)
                    ($input['registration_no'] ?? '')
                ),

            'postcode' =>
                sanitize_text_field(
                    (string)
                    ($input['postcode'] ?? '')
                ),

            'phone' =>
                sanitize_text_field(
                    (string)
                    ($input['phone'] ?? '')
                ),

            'address' =>
                sanitize_textarea_field(
                    (string)
                    ($input['address'] ?? '')
                ),

            'updated_at' =>
                current_time(
                    'mysql'
                ),

            'updated_by' =>
                $actor_user_id,
        ];


        $saved =
            update_option(
                self::OPTION_KEY,
                $data,
                false
            );


        /*
         * update_option() returns false when the value
         * is identical to the existing value.
         */
        if (
            false === $saved &&
            $data !== get_option(
                self::OPTION_KEY,
                []
            )
        ) {

            return new WP_Error(
                'save_failed',
                'ذخیره اطلاعات فروشنده انجام نشد.'
            );
        }


        return $data;
    }


    public static function handle_save() {

        if (
            !is_user_logged_in() ||
            !current_user_can(
                HOM_Capabilities::CAP_MANAGE_PREINVOICES
            )
        ) {

            wp_die(
                'دسترسی غیرمجاز.',
                '',
                [
                    'response' =>
                        403,
                ]
            );
        }


        check_admin_referer(
            'hom_save_seller_settings'
        );


        $result =
            self::save(
                [
                    'legal_name' =>
                        isset($_POST['legal_name'])
                            ? wp_unslash(
                                $_POST['legal_name']
                            )
                            : '',

                    'national_id' =>
                        isset($_POST['national_id'])
                            ? wp_unslash(
                                $_POST['national_id']
                            )
                            : '',

                    'economic_code' =>
                        isset($_POST['economic_code'])
                            ? wp_unslash(
                                $_POST['economic_code']
                            )
                            : '',

                    'registration_no' =>
                        isset($_POST['registration_no'])
                            ? wp_unslash(
                                $_POST['registration_no']
                            )
                            : '',

                    'postcode' =>
                        isset($_POST['postcode'])
                            ? wp_unslash(
                                $_POST['postcode']
                            )
                            : '',

                    'phone' =>
                        isset($_POST['phone'])
                            ? wp_unslash(
                                $_POST['phone']
                            )
                            : '',

                    'address' =>
                        isset($_POST['seller_address'])
                            ? wp_unslash(
                                $_POST['seller_address']
                            )
                            : '',
                ],
                get_current_user_id()
            );


        $url =
            add_query_arg(
                [
                    'view' =>
                        'seller-settings',

                    'notice' =>
                        is_wp_error($result)
                            ? 'seller-error'
                            : 'seller-saved',
                ],
                HOM_Router::panel_url()
            );


        wp_safe_redirect(
            $url
        );

        exit;
    }
}
