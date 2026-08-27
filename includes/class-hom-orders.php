<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Orders {

    public const PER_PAGE = 25;


    public static function register() {

        add_action(
            'init',
            [self::class, 'register_fulfillment_statuses']
        );

        add_filter(
            'wc_order_statuses',
            [self::class, 'add_fulfillment_statuses']
        );

        add_filter(
            'woocommerce_order_is_paid_statuses',
            [self::class, 'add_paid_statuses']
        );

        add_action(
            'admin_post_hom_save_preinvoice_prices',
            [self::class, 'handle_save_preinvoice_prices']
        );

        add_action(
            'admin_post_hom_approve_preinvoice',
            [self::class, 'handle_approve_preinvoice']
        );

        add_action(
            'admin_post_hom_save_order_fulfillment',
            [self::class, 'handle_save_order_fulfillment']
        );

        add_action(
            'admin_post_hom_save_b2b_customer',
            [self::class, 'handle_save_b2b_customer']
        );
    }


    public static function shipping_methods() {

        return [
            'post' =>
                'پست',

            'tipax' =>
                'تیپاکس',

            'freight' =>
                'باربری',

            'tehran_courier' =>
                'پیک تهران',

            'pickup' =>
                'تحویل حضوری',

            'other' =>
                'سایر',
        ];
    }


    public static function status_label(
        $status
    ) {

        $status =
            sanitize_key(
                (string) $status
            );

        $labels = [

            'preinvoice-review' =>
                'درخواست پیش‌فاکتور',

            'preinv-approved' =>
                'پیش‌فاکتور تأیید شده',

            'pending' =>
                'در انتظار پرداخت',

            'on-hold' =>
                'در انتظار بررسی',

            'processing' =>
                'پرداخت شده / در حال آماده‌سازی',

            'hom-ready' =>
                'آماده ارسال',

            'hom-shipped' =>
                'ارسال شده',

            'completed' =>
                'تحویل شده',

            'cancelled' =>
                'لغو شده',

            'refunded' =>
                'مسترد شده',

            'failed' =>
                'ناموفق',
        ];


        if (isset($labels[$status])) {
            return $labels[$status];
        }


        if (
            function_exists(
                'wc_get_order_status_name'
            )
        ) {

            $label =
                wc_get_order_status_name(
                    $status
                );

            if ($label) {
                return $label;
            }
        }


        return $status;
    }


    public static function summary_counts() {

        $statuses = [
            'preinvoice-review',
            'preinv-approved',
            'pending',
            'on-hold',
            'processing',
            'hom-ready',
            'hom-shipped',
            'completed',
        ];


        $counts = [
            'all' => 0,
        ];


        if (
            !function_exists(
                'wc_get_order_statuses'
            )
        ) {
            return $counts;
        }


        foreach (
            wc_get_order_statuses()
            as $key => $label
        ) {

            unset($label);

            $status =
                str_replace(
                    'wc-',
                    '',
                    $key
                );


            if (
                function_exists(
                    'wc_orders_count'
                )
            ) {

                $counts['all'] +=
                    absint(
                        wc_orders_count(
                            $status
                        )
                    );
            }
        }


        foreach (
            $statuses
            as $status
        ) {

            $counts[$status] =
                function_exists(
                    'wc_orders_count'
                )
                    ? absint(
                        wc_orders_count(
                            $status
                        )
                    )
                    : 0;
        }


        return $counts;
    }


    public static function query(
        $status = '',
        $page = 1,
        $search = ''
    ) {

        $status =
            sanitize_key(
                (string) $status
            );

        $page =
            max(
                1,
                absint($page)
            );


        $search =
            sanitize_text_field(
                (string) $search
            );


        $args = [

            'limit' =>
                self::PER_PAGE,

            'paged' =>
                $page,

            'paginate' =>
                true,

            'orderby' =>
                'date',

            'order' =>
                'DESC',
        ];


        $search_total_override = null;


        /*
         * WooCommerce search is used only for candidate discovery.
         * Final matching is performed explicitly so unrelated HPOS
         * search results cannot leak into the Owner Panel.
         */
        if ('' !== $search) {

            $search =
                strtr(
                    $search,
                    [
                        '۰' => '0',
                        '۱' => '1',
                        '۲' => '2',
                        '۳' => '3',
                        '۴' => '4',
                        '۵' => '5',
                        '۶' => '6',
                        '۷' => '7',
                        '۸' => '8',
                        '۹' => '9',

                        '٠' => '0',
                        '١' => '1',
                        '٢' => '2',
                        '٣' => '3',
                        '٤' => '4',
                        '٥' => '5',
                        '٦' => '6',
                        '٧' => '7',
                        '٨' => '8',
                        '٩' => '9',
                    ]
                );


            $candidate_ids =
                (array)
                wc_get_orders(
                    [
                        'limit' =>
                            -1,

                        'return' =>
                            'ids',

                        'search' =>
                            '*' .
                            $search .
                            '*',
                    ]
                );


            $tracking_ids =
                (array)
                wc_get_orders(
                    [
                        'limit' =>
                            -1,

                        'return' =>
                            'ids',

                        'meta_query' =>
                            [
                                [
                                    'key' =>
                                        '_hom_shipping_tracking_code',

                                    'value' =>
                                        $search,

                                    'compare' =>
                                        'LIKE',
                                ],
                            ],
                    ]
                );


            $candidate_ids =
                array_merge(
                    $candidate_ids,
                    $tracking_ids
                );


            if (ctype_digit($search)) {

                $direct_order =
                    wc_get_order(
                        absint($search)
                    );

                if ($direct_order) {

                    $candidate_ids[] =
                        $direct_order
                            ->get_id();
                }
            }


            $candidate_ids =
                array_values(
                    array_unique(
                        array_filter(
                            array_map(
                                'absint',
                                $candidate_ids
                            )
                        )
                    )
                );


            $contains =
                static function (
                    $haystack,
                    $needle
                ) {

                    $haystack =
                        (string) $haystack;

                    $needle =
                        (string) $needle;


                    if (
                        function_exists(
                            'mb_stripos'
                        )
                    ) {

                        return false !==
                            mb_stripos(
                                $haystack,
                                $needle,
                                0,
                                'UTF-8'
                            );
                    }


                    return false !==
                        stripos(
                            $haystack,
                            $needle
                        );
                };


            $matched_orders = [];


            foreach (
                $candidate_ids
                as $candidate_id
            ) {

                $candidate =
                    wc_get_order(
                        $candidate_id
                    );


                if (
                    !($candidate instanceof WC_Order)
                ) {
                    continue;
                }


                if (
                    '' !== $status &&
                    'all' !== $status &&
                    $candidate->get_status() !==
                        $status
                ) {
                    continue;
                }


                $fields = [

                    $candidate
                        ->get_order_number(),

                    $candidate
                        ->get_billing_first_name(),

                    $candidate
                        ->get_billing_last_name(),

                    $candidate
                        ->get_formatted_billing_full_name(),

                    $candidate
                        ->get_billing_company(),

                    $candidate
                        ->get_billing_phone(),

                    $candidate
                        ->get_billing_email(),

                    $candidate
                        ->get_billing_city(),

                    $candidate
                        ->get_meta(
                            '_hom_shipping_tracking_code',
                            true
                        ),
                ];


                $matched = false;


                foreach ($fields as $field) {

                    if (
                        $contains(
                            $field,
                            $search
                        )
                    ) {

                        $matched = true;
                        break;
                    }
                }


                if (!$matched) {
                    continue;
                }


                $matched_orders[] =
                    $candidate;
            }


            usort(
                $matched_orders,
                static function (
                    $a,
                    $b
                ) {

                    $a_date =
                        $a->get_date_created();

                    $b_date =
                        $b->get_date_created();


                    $a_time =
                        $a_date
                            ? $a_date
                                ->getTimestamp()
                            : 0;

                    $b_time =
                        $b_date
                            ? $b_date
                                ->getTimestamp()
                            : 0;


                    return
                        $b_time
                        <=>
                        $a_time;
                }
            );


            $search_total_override =
                count(
                    $matched_orders
                );


            if (
                0 ===
                $search_total_override
            ) {

                return [
                    'status' =>
                        $status,

                    'search' =>
                        $search,

                    'page' =>
                        $page,

                    'total' =>
                        0,

                    'total_pages' =>
                        1,

                    'items' =>
                        [],
                ];
            }


            $matched_ids =
                array_map(
                    static function (
                        $order
                    ) {

                        return
                            $order
                                ->get_id();
                    },
                    $matched_orders
                );


            $matched_ids =
                array_slice(
                    $matched_ids,
                    (
                        $page - 1
                    )
                    *
                    self::PER_PAGE,
                    self::PER_PAGE
                );


            $args['include'] =
                $matched_ids;

            $args['limit'] =
                -1;

            $args['paged'] =
                1;

            $args['paginate'] =
                false;
        }


        if (
            '' !== $status &&
            'all' !== $status
        ) {

            $valid_statuses =
                array_map(
                    static function ($key) {

                        return str_replace(
                            'wc-',
                            '',
                            $key
                        );

                    },
                    array_keys(
                        wc_get_order_statuses()
                    )
                );


            if (
                in_array(
                    $status,
                    $valid_statuses,
                    true
                )
            ) {

                $args['status'] =
                    $status;
            }
        }


        $result =
            wc_get_orders(
                $args
            );


        $orders = [];


        if (
            is_object($result) &&
            isset($result->orders)
        ) {

            $orders =
                (array) $result->orders;

            $total =
                absint(
                    $result->total ?? 0
                );

            $max_pages =
                max(
                    1,
                    absint(
                        $result->max_num_pages ?? 1
                    )
                );

        } else {

            $orders =
                is_array($result)
                    ? $result
                    : [];

            $total =
                count($orders);

            $max_pages = 1;
        }


        if (
            null !==
            $search_total_override
        ) {

            $total =
                $search_total_override;

            $max_pages =
                max(
                    1,
                    (int)
                    ceil(
                        $total /
                        self::PER_PAGE
                    )
                );
        }


        $items = [];


        foreach ($orders as $order) {

            if (
                !($order instanceof WC_Order)
            ) {
                continue;
            }


            $date_created =
                $order->get_date_created();


            $shipping_method_key =
                sanitize_key(
                    (string)
                    $order->get_meta(
                        '_hom_shipping_method',
                        true
                    )
                );


            $shipping_methods =
                self::shipping_methods();


            $shipping_method_label =
                isset(
                    $shipping_methods[
                        $shipping_method_key
                    ]
                )
                    ? $shipping_methods[
                        $shipping_method_key
                    ]
                    : '';


            if (
                '' ===
                $shipping_method_label
            ) {

                $shipping_method_label =
                    trim(
                        (string)
                        $order
                            ->get_shipping_method()
                    );
            }


            $customer_name =
                trim(
                    $order
                        ->get_formatted_billing_full_name()
                );


            if (
                '' ===
                $customer_name
            ) {

                $customer_name =
                    trim(
                        (string)
                        $order
                            ->get_billing_company()
                    );
            }


            if (
                '' ===
                $customer_name
            ) {

                $customer_name =
                    'مشتری بدون نام';
            }


            $items[] = [

                'id' =>
                    $order->get_id(),

                'number' =>
                    $order->get_order_number(),

                'status' =>
                    $order->get_status(),

                'status_label' =>
                    self::status_label(
                        $order->get_status()
                    ),

                'is_preinvoice' =>
                    'yes' ===
                    $order->get_meta(
                        '_hsb_is_preinvoice',
                        true
                    ),

                'customer_name' =>
                    $customer_name,

                'company' =>
                    trim(
                        (string)
                        $order
                            ->get_billing_company()
                    ),

                'city' =>
                    trim(
                        (string)
                        $order
                            ->get_billing_city()
                    ),

                'phone' =>
                    trim(
                        (string)
                        $order
                            ->get_billing_phone()
                    ),

                'total_html' =>
                    $order
                        ->get_formatted_order_total(),

                'payment_method' =>
                    trim(
                        (string)
                        $order
                            ->get_payment_method_title()
                    ),

                'shipping_method' =>
                    $shipping_method_label,

                'tracking_code' =>
                    trim(
                        (string)
                        $order->get_meta(
                            '_hom_shipping_tracking_code',
                            true
                        )
                    ),

                'assignee' =>
                    self::assignee_data(
                        $order
                    ),

                'date' =>
                    $date_created
                        ? $date_created
                            ->date_i18n(
                                'Y/m/d H:i'
                            )
                        : '—',
            ];
        }


        return [

            'status' =>
                $status,

            'search' =>
                $search,

            'page' =>
                $page,

            'total' =>
                $total,

            'total_pages' =>
                $max_pages,

            'items' =>
                $items,
        ];
    }


    public static function detail_url(
        $order_id
    ) {

        return add_query_arg(
            [
                'view' =>
                    'orders',

                'order_id' =>
                    absint($order_id),
            ],
            HOM_Router::panel_url()
        );
    }


    public static function orders_url(
        $notice = ''
    ) {

        $args = [
            'view' =>
                'orders',
        ];


        if ('' !== $notice) {

            $args['notice'] =
                sanitize_key(
                    $notice
                );
        }


        return add_query_arg(
            $args,
            HOM_Router::panel_url()
        );
    }


    public static function get_order(
        $order_id
    ) {

        $order =
            wc_get_order(
                absint($order_id)
            );


        return
            $order instanceof WC_Order
                ? $order
                : null;
    }


    public static function can_price_preinvoice(
        $order
    ) {

        return
            $order instanceof WC_Order &&
            'yes' ===
                $order->get_meta(
                    '_hsb_is_preinvoice',
                    true
                ) &&
            'preinvoice-review' ===
                $order->get_status();
    }


    private static function normalize_decimal(
        $value
    ) {

        $value =
            trim(
                (string) $value
            );


        $value =
            strtr(
                $value,
                [
                    '۰' => '0',
                    '۱' => '1',
                    '۲' => '2',
                    '۳' => '3',
                    '۴' => '4',
                    '۵' => '5',
                    '۶' => '6',
                    '۷' => '7',
                    '۸' => '8',
                    '۹' => '9',

                    '٠' => '0',
                    '١' => '1',
                    '٢' => '2',
                    '٣' => '3',
                    '٤' => '4',
                    '٥' => '5',
                    '٦' => '6',
                    '٧' => '7',
                    '٨' => '8',
                    '٩' => '9',

                    ',' => '',
                    '٬' => '',
                    '،' => '',
                ]
            );


        return
            max(
                0,
                (float)
                wc_format_decimal(
                    $value
                )
            );
    }


    public static function save_preinvoice_prices(
        $order_id,
        array $prices,
        $actor_user_id = 0,
        $shipping_cost = null
    ) {

        $order =
            self::get_order(
                $order_id
            );


        if (!$order) {

            return new WP_Error(
                'order_missing',
                'سفارش پیدا نشد.'
            );
        }


        if (
            !self::can_price_preinvoice(
                $order
            )
        ) {

            return new WP_Error(
                'order_not_editable',
                'این پیش‌فاکتور در وضعیت قابل قیمت‌گذاری نیست.'
            );
        }


        $changed = 0;


        foreach (
            $order->get_items('line_item')
            as $item_id => $item
        ) {

            if (
                !array_key_exists(
                    $item_id,
                    $prices
                ) &&
                !array_key_exists(
                    (string) $item_id,
                    $prices
                )
            ) {
                continue;
            }


            $raw_price =
                array_key_exists(
                    $item_id,
                    $prices
                )
                    ? $prices[$item_id]
                    : $prices[
                        (string) $item_id
                    ];


            $unit_price =
                self::normalize_decimal(
                    $raw_price
                );


            $quantity =
                max(
                    1,
                    (float)
                    $item->get_quantity()
                );


            $line_total =
                $unit_price *
                $quantity;


            $item->set_subtotal(
                $line_total
            );

            $item->set_total(
                $line_total
            );

            $item->save();

            $changed++;
        }


        if ($changed < 1) {

            return new WP_Error(
                'no_prices',
                'هیچ قیمت معتبری برای ذخیره ارسال نشده است.'
            );
        }


        if (null !== $shipping_cost) {

            self::set_preinvoice_shipping_cost(
                $order,
                $shipping_cost
            );
        }


        $order->calculate_totals(
            false
        );


        $order->update_meta_data(
            '_hom_preinvoice_priced_at',
            current_time('mysql')
        );


        if ($actor_user_id > 0) {

            $order->update_meta_data(
                '_hom_preinvoice_priced_by',
                absint(
                    $actor_user_id
                )
            );
        }


        self::auto_assign(
            $order,
            $actor_user_id
        );


        HOM_Order_Audit::record(
            $order,
            'price_updated',
            $actor_user_id,
            'قیمت اقلام پیش‌فاکتور به‌روزرسانی شد.'
        );


        $order->add_order_note(
            'قیمت اقلام پیش‌فاکتور توسط مدیر فروشگاه به‌روزرسانی شد.'
        );

        $order->save();


        return $order;
    }


    public static function approve_preinvoice(
        $order_id,
        $actor_user_id = 0
    ) {

        $order =
            self::get_order(
                $order_id
            );


        if (!$order) {

            return new WP_Error(
                'order_missing',
                'پیش‌فاکتور پیدا نشد.'
            );
        }


        if (
            !self::can_price_preinvoice(
                $order
            )
        ) {

            return new WP_Error(
                'invalid_status',
                'این پیش‌فاکتور در وضعیت قابل تأیید نیست.'
            );
        }


        $order->calculate_totals(
            false
        );


        if (
            (float)
            $order->get_total()
            <= 0
        ) {

            return new WP_Error(
                'zero_total',
                'مبلغ نهایی پیش‌فاکتور هنوز صفر است.'
            );
        }


        $order->update_meta_data(
            '_hsb_preinvoice_approved_at',
            current_time('mysql')
        );


        $order->update_meta_data(
            '_hsb_preinvoice_approved_by',
            absint(
                $actor_user_id
            )
        );


        self::auto_assign(
            $order,
            $actor_user_id
        );


        HOM_Order_Audit::record(
            $order,
            'preinvoice_approved',
            $actor_user_id,
            'پیش‌فاکتور برای پرداخت مشتری تأیید شد.'
        );


        $order->set_status(
            'preinv-approved',
            'پیش‌فاکتور توسط مدیر فروشگاه قیمت‌گذاری و تأیید شد.'
        );


        $order->save();


        /*
         * Preserve the existing SMS integration contract.
         * No direct dependency on the theme implementation.
         */
        do_action(
            'hsb_preinvoice_approved_sms',
            $order->get_id(),
            $order
        );


        return $order;
    }


    public static function handle_save_preinvoice_prices() {

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
                    'response' => 403,
                ]
            );
        }


        $order_id =
            isset($_POST['order_id'])
                ? absint(
                    $_POST['order_id']
                )
                : 0;


        check_admin_referer(
            'hom_save_preinvoice_prices_' .
            $order_id
        );


        $prices =
            isset($_POST['item_price']) &&
            is_array($_POST['item_price'])
                ? wp_unslash(
                    $_POST['item_price']
                )
                : [];


        $result =
            self::save_preinvoice_prices(
                $order_id,
                $prices,
                get_current_user_id(),
                isset($_POST['shipping_cost'])
                    ? wp_unslash(
                        $_POST['shipping_cost']
                    )
                    : null
            );


        $notice =
            is_wp_error($result)
                ? 'price-error'
                : 'prices-saved';


        wp_safe_redirect(
            add_query_arg(
                'notice',
                $notice,
                self::detail_url(
                    $order_id
                )
            )
        );

        exit;
    }


    public static function handle_approve_preinvoice() {

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
                    'response' => 403,
                ]
            );
        }


        $order_id =
            isset($_POST['order_id'])
                ? absint(
                    $_POST['order_id']
                )
                : 0;


        check_admin_referer(
            'hom_approve_preinvoice_' .
            $order_id
        );


        $result =
            self::approve_preinvoice(
                $order_id,
                get_current_user_id()
            );


        $notice =
            is_wp_error($result)
                ? 'approve-error'
                : 'approved';


        wp_safe_redirect(
            add_query_arg(
                'notice',
                $notice,
                self::detail_url(
                    $order_id
                )
            )
        );

        exit;
    }

    public static function register_fulfillment_statuses() {

        register_post_status(
            'wc-hom-ready',
            [
                'label' =>
                    'آماده ارسال',

                'public' =>
                    true,

                'exclude_from_search' =>
                    false,

                'show_in_admin_all_list' =>
                    true,

                'show_in_admin_status_list' =>
                    true,

                'label_count' =>
                    _n_noop(
                        'آماده ارسال <span class="count">(%s)</span>',
                        'آماده ارسال <span class="count">(%s)</span>',
                        'hypersanati-owner-manager'
                    ),
            ]
        );


        register_post_status(
            'wc-hom-shipped',
            [
                'label' =>
                    'ارسال شده',

                'public' =>
                    true,

                'exclude_from_search' =>
                    false,

                'show_in_admin_all_list' =>
                    true,

                'show_in_admin_status_list' =>
                    true,

                'label_count' =>
                    _n_noop(
                        'ارسال شده <span class="count">(%s)</span>',
                        'ارسال شده <span class="count">(%s)</span>',
                        'hypersanati-owner-manager'
                    ),
            ]
        );
    }


    public static function add_fulfillment_statuses(
        $statuses
    ) {

        $result = [];


        foreach ($statuses as $key => $label) {

            $result[$key] =
                $label;


            if ('wc-processing' === $key) {

                $result['wc-hom-ready'] =
                    'آماده ارسال';

                $result['wc-hom-shipped'] =
                    'ارسال شده';
            }
        }


        return $result;
    }


    public static function add_paid_statuses(
        $statuses
    ) {

        $statuses[] =
            'hom-ready';

        $statuses[] =
            'hom-shipped';


        return array_values(
            array_unique(
                $statuses
            )
        );
    }


    public static function fulfillment_data(
        $order
    ) {

        if (!($order instanceof WC_Order)) {
            return [];
        }


        return [

            'method' =>
                sanitize_key(
                    (string)
                    $order->get_meta(
                        '_hom_shipping_method',
                        true
                    )
                ),

            'company' =>
                trim(
                    (string)
                    $order->get_meta(
                        '_hom_shipping_company',
                        true
                    )
                ),

            'tracking_code' =>
                trim(
                    (string)
                    $order->get_meta(
                        '_hom_shipping_tracking_code',
                        true
                    )
                ),

            'freight_payment' =>
                sanitize_key(
                    (string)
                    $order->get_meta(
                        '_hom_shipping_freight_payment',
                        true
                    )
                ),

            'notes' =>
                trim(
                    (string)
                    $order->get_meta(
                        '_hom_shipping_notes',
                        true
                    )
                ),
        ];
    }


    public static function save_fulfillment(
        $order_id,
        array $data,
        $transition = 'save',
        $actor_user_id = 0
    ) {

        $order =
            self::get_order(
                $order_id
            );


        if (!$order) {

            return new WP_Error(
                'order_missing',
                'سفارش پیدا نشد.'
            );
        }


        $allowed_statuses = [
            'processing',
            'hom-ready',
            'hom-shipped',
            'completed',
        ];


        if (
            !in_array(
                $order->get_status(),
                $allowed_statuses,
                true
            )
        ) {

            return new WP_Error(
                'invalid_fulfillment_status',
                'این سفارش هنوز وارد مرحله ارسال نشده است.'
            );
        }


        $method =
            sanitize_key(
                $data['method'] ?? ''
            );


        $methods =
            self::shipping_methods();


        if (
            '' !== $method &&
            !isset($methods[$method])
        ) {

            return new WP_Error(
                'invalid_shipping_method',
                'روش ارسال معتبر نیست.'
            );
        }


        $company =
            sanitize_text_field(
                (string)
                ($data['company'] ?? '')
            );


        $tracking =
            sanitize_text_field(
                (string)
                ($data['tracking_code'] ?? '')
            );


        $freight_payment =
            sanitize_key(
                (string)
                ($data['freight_payment'] ?? '')
            );


        if (
            !in_array(
                $freight_payment,
                [
                    '',
                    'prepaid',
                    'collect',
                ],
                true
            )
        ) {
            $freight_payment = '';
        }


        $notes =
            sanitize_textarea_field(
                (string)
                ($data['notes'] ?? '')
            );


        $order->update_meta_data(
            '_hom_shipping_method',
            $method
        );

        $order->update_meta_data(
            '_hom_shipping_company',
            $company
        );

        $order->update_meta_data(
            '_hom_shipping_tracking_code',
            $tracking
        );

        $order->update_meta_data(
            '_hom_shipping_freight_payment',
            $freight_payment
        );

        $order->update_meta_data(
            '_hom_shipping_notes',
            $notes
        );


        $current =
            $order->get_status();


        if ('ready' === $transition) {

            if ('processing' !== $current) {

                return new WP_Error(
                    'ready_transition_invalid',
                    'فقط سفارش در حال آماده‌سازی را می‌توان آماده ارسال کرد.'
                );
            }


            $order->update_meta_data(
                '_hom_ready_at',
                current_time('mysql')
            );

            $order->update_meta_data(
                '_hom_ready_by',
                absint($actor_user_id)
            );

            $order->set_status(
                'hom-ready',
                'سفارش آماده ارسال شد.'
            );
        }


        if ('shipped' === $transition) {

            if ('hom-ready' !== $current) {

                return new WP_Error(
                    'shipped_transition_invalid',
                    'ابتدا سفارش را در وضعیت آماده ارسال قرار دهید.'
                );
            }


            if ('' === $method) {

                return new WP_Error(
                    'shipping_method_required',
                    'برای ثبت ارسال، روش ارسال را انتخاب کنید.'
                );
            }


            $order->update_meta_data(
                '_hom_shipped_at',
                current_time('mysql')
            );

            $order->update_meta_data(
                '_hom_shipped_by',
                absint($actor_user_id)
            );

            $order->set_status(
                'hom-shipped',
                'سفارش ارسال شد.'
            );
        }


        if ('delivered' === $transition) {

            if ('hom-shipped' !== $current) {

                return new WP_Error(
                    'delivered_transition_invalid',
                    'فقط سفارش ارسال‌شده را می‌توان تحویل‌شده ثبت کرد.'
                );
            }


            $order->update_meta_data(
                '_hom_delivered_at',
                current_time('mysql')
            );

            $order->update_meta_data(
                '_hom_delivered_by',
                absint($actor_user_id)
            );

            $order->set_status(
                'completed',
                'تحویل سفارش به مشتری ثبت شد.'
            );
        }


        $audit_event = [

            'save' =>
                'shipping_updated',

            'ready' =>
                'order_ready',

            'shipped' =>
                'order_shipped',

            'delivered' =>
                'order_delivered',

        ][$transition] ?? 'shipping_updated';


        $audit_description = [

            'save' =>
                'اطلاعات ارسال سفارش ویرایش شد.',

            'ready' =>
                'سفارش آماده ارسال اعلام شد.',

            'shipped' =>
                'ارسال سفارش ثبت شد.',

            'delivered' =>
                'تحویل سفارش به مشتری ثبت شد.',

        ][$transition] ?? '';


        HOM_Order_Audit::record(
            $order,
            $audit_event,
            $actor_user_id,
            $audit_description
        );


        $order->save();


        return $order;
    }


    public static function handle_save_order_fulfillment() {

        if (
            !is_user_logged_in() ||
            !current_user_can(
                HOM_Capabilities::CAP_MANAGE_FULFILLMENT
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


        $order_id =
            isset($_POST['order_id'])
                ? absint(
                    $_POST['order_id']
                )
                : 0;


        check_admin_referer(
            'hom_save_order_fulfillment_' .
            $order_id
        );


        $transition =
            isset($_POST['fulfillment_action'])
                ? sanitize_key(
                    wp_unslash(
                        $_POST['fulfillment_action']
                    )
                )
                : 'save';


        if (
            !in_array(
                $transition,
                [
                    'save',
                    'ready',
                    'shipped',
                    'delivered',
                ],
                true
            )
        ) {
            $transition = 'save';
        }


        $result =
            self::save_fulfillment(
                $order_id,
                [
                    'method' =>
                        isset($_POST['shipping_method'])
                            ? wp_unslash(
                                $_POST['shipping_method']
                            )
                            : '',

                    'company' =>
                        isset($_POST['shipping_company'])
                            ? wp_unslash(
                                $_POST['shipping_company']
                            )
                            : '',

                    'tracking_code' =>
                        isset($_POST['tracking_code'])
                            ? wp_unslash(
                                $_POST['tracking_code']
                            )
                            : '',

                    'freight_payment' =>
                        isset($_POST['freight_payment'])
                            ? wp_unslash(
                                $_POST['freight_payment']
                            )
                            : '',

                    'notes' =>
                        isset($_POST['shipping_notes'])
                            ? wp_unslash(
                                $_POST['shipping_notes']
                            )
                            : '',
                ],
                $transition,
                get_current_user_id()
            );


        $notice =
            is_wp_error($result)
                ? 'fulfillment-error'
                : 'fulfillment-saved';


        wp_safe_redirect(
            add_query_arg(
                'notice',
                $notice,
                self::detail_url(
                    $order_id
                )
            )
        );

        exit;
    }


    public static function preinvoice_shipping_cost(
        $order
    ) {

        if (!($order instanceof WC_Order)) {
            return 0;
        }


        foreach (
            $order->get_items('fee')
            as $fee
        ) {

            if (
                'yes' ===
                $fee->get_meta(
                    '_hom_preinvoice_shipping_fee',
                    true
                )
            ) {

                return
                    (float)
                    $fee->get_total();
            }
        }


        return 0;
    }


    private static function set_preinvoice_shipping_cost(
        $order,
        $raw_cost
    ) {

        if (!($order instanceof WC_Order)) {
            return;
        }


        $cost =
            self::normalize_decimal(
                $raw_cost
            );


        foreach (
            $order->get_items('fee')
            as $item_id => $fee
        ) {

            if (
                'yes' ===
                $fee->get_meta(
                    '_hom_preinvoice_shipping_fee',
                    true
                )
            ) {

                $order->remove_item(
                    $item_id
                );
            }
        }


        if ($cost <= 0) {
            return;
        }


        $fee =
            new WC_Order_Item_Fee();


        $fee->set_name(
            'هزینه ارسال'
        );

        $fee->set_amount(
            $cost
        );

        $fee->set_total(
            $cost
        );

        $fee->set_tax_status(
            'none'
        );

        $fee->add_meta_data(
            '_hom_preinvoice_shipping_fee',
            'yes',
            true
        );


        $order->add_item(
            $fee
        );
    }


    public static function timeline(
        $order
    ) {

        if (!($order instanceof WC_Order)) {
            return [];
        }


        $events = [];


        $add =
            static function (
                $label,
                $value,
                $description = ''
            ) use (&$events) {

                if (!$value) {
                    return;
                }


                if ($value instanceof WC_DateTime) {

                    $timestamp =
                        $value->getTimestamp();

                    $formatted =
                        $value->date_i18n(
                            'Y/m/d H:i'
                        );

                } else {

                    $timestamp =
                        strtotime(
                            (string) $value
                        );

                    if (!$timestamp) {
                        return;
                    }

                    $formatted =
                        wp_date(
                            'Y/m/d H:i',
                            $timestamp
                        );
                }


                $events[] = [

                    'label' =>
                        $label,

                    'date' =>
                        $formatted,

                    'timestamp' =>
                        $timestamp,

                    'description' =>
                        $description,
                ];
            };


        $add(
            'ثبت سفارش / درخواست',
            $order->get_date_created()
        );


        $add(
            'قیمت‌گذاری پیش‌فاکتور',
            $order->get_meta(
                '_hom_preinvoice_priced_at',
                true
            )
        );


        $add(
            'تأیید پیش‌فاکتور',
            $order->get_meta(
                '_hsb_preinvoice_approved_at',
                true
            )
        );


        $add(
            'پرداخت سفارش',
            $order->get_date_paid()
        );


        $add(
            'آماده ارسال',
            $order->get_meta(
                '_hom_ready_at',
                true
            )
        );


        $shipping =
            self::fulfillment_data(
                $order
            );


        $shipping_methods =
            self::shipping_methods();


        $shipping_description = '';


        if (
            !empty($shipping['method']) &&
            isset(
                $shipping_methods[
                    $shipping['method']
                ]
            )
        ) {

            $shipping_description =
                $shipping_methods[
                    $shipping['method']
                ];
        }


        if (
            !empty(
                $shipping['tracking_code']
            )
        ) {

            $shipping_description .=
                (
                    $shipping_description
                        ? ' · '
                        : ''
                )
                .
                'رهگیری: '
                .
                $shipping[
                    'tracking_code'
                ];
        }


        $add(
            'ارسال سفارش',
            $order->get_meta(
                '_hom_shipped_at',
                true
            ),
            $shipping_description
        );


        $delivered =
            $order->get_meta(
                '_hom_delivered_at',
                true
            );


        if (!$delivered) {

            $delivered =
                $order->get_date_completed();
        }


        $add(
            'تحویل / تکمیل سفارش',
            $delivered
        );


        usort(
            $events,
            static function (
                $a,
                $b
            ) {

                return
                    $a['timestamp']
                    <=>
                    $b['timestamp'];
            }
        );


        return $events;
    }


    public static function assignee_data(
        $order
    ) {

        if (!($order instanceof WC_Order)) {

            return [
                'id' => 0,
                'name' => '',
                'login' => '',
            ];
        }


        $user_id =
            absint(
                $order->get_meta(
                    '_hom_assigned_sales_user_id',
                    true
                )
            );


        $user =
            $user_id
                ? get_userdata($user_id)
                : false;


        return [

            'id' =>
                $user_id,

            'name' =>
                $user
                    ? (
                        $user->display_name
                            ?: $user->user_login
                    )
                    : '',

            'login' =>
                $user
                    ? $user->user_login
                    : '',
        ];
    }


    public static function sales_users() {

        $users =
            get_users([
                'fields' =>
                    'all',
            ]);


        $result = [];


        foreach ($users as $user) {

            if (
                !user_can(
                    $user,
                    HOM_Capabilities::CAP_MANAGE_PREINVOICES
                )
            ) {
                continue;
            }


            $result[] = [

                'id' =>
                    absint($user->ID),

                'name' =>
                    $user->display_name
                        ?: $user->user_login,

                'login' =>
                    $user->user_login,
            ];
        }


        usort(
            $result,
            static function ($a, $b) {

                return strnatcasecmp(
                    $a['name'],
                    $b['name']
                );
            }
        );


        return $result;
    }


    private static function auto_assign(
        $order,
        $actor_user_id
    ) {

        if (
            !($order instanceof WC_Order) ||
            $actor_user_id < 1
        ) {
            return;
        }


        $current =
            absint(
                $order->get_meta(
                    '_hom_assigned_sales_user_id',
                    true
                )
            );


        if (
            $current ===
            absint($actor_user_id)
        ) {
            return;
        }


        self::assign_order_object(
            $order,
            $actor_user_id,
            $actor_user_id,
            true
        );
    }


    private static function assign_order_object(
        $order,
        $assignee_user_id,
        $actor_user_id,
        $automatic = false
    ) {

        if (!($order instanceof WC_Order)) {
            return false;
        }


        $assignee_user_id =
            absint(
                $assignee_user_id
            );


        $assignee =
            $assignee_user_id
                ? get_userdata(
                    $assignee_user_id
                )
                : false;


        if (
            !$assignee ||
            !user_can(
                $assignee,
                HOM_Capabilities::CAP_MANAGE_PREINVOICES
            )
        ) {
            return false;
        }


        $previous =
            self::assignee_data(
                $order
            );


        if (
            absint($previous['id']) ===
            $assignee_user_id
        ) {
            return true;
        }


        $order->update_meta_data(
            '_hom_assigned_sales_user_id',
            $assignee_user_id
        );


        $order->update_meta_data(
            '_hom_assigned_sales_at',
            current_time('mysql')
        );


        $order->update_meta_data(
            '_hom_assigned_sales_by',
            absint($actor_user_id)
        );


        $description =
            $automatic
                ? 'مسئول جاری پرونده به‌صورت خودکار بر اساس آخرین اقدام قیمت‌گذاری یا تأیید پیش‌فاکتور به‌روزرسانی شد.'
                : 'مسئول جاری پرونده تغییر کرد.';


        if (!empty($previous['name'])) {

            $description .=
                ' مسئول قبلی: ' .
                $previous['name'] .
                '.';
        }


        $description .=
            ' مسئول جدید: ' .
            (
                $assignee->display_name
                    ?: $assignee->user_login
            )
            .
            '.';


        HOM_Order_Audit::record(
            $order,
            'assignee_changed',
            $actor_user_id,
            $description
        );


        $order->save();

        return true;
    }


    public static function assign_order(
        $order_id,
        $assignee_user_id,
        $actor_user_id
    ) {

        $order =
            self::get_order(
                $order_id
            );


        if (!$order) {

            return new WP_Error(
                'order_missing',
                'سفارش پیدا نشد.'
            );
        }


        if (
            !self::assign_order_object(
                $order,
                $assignee_user_id,
                $actor_user_id,
                false
            )
        ) {

            return new WP_Error(
                'invalid_assignee',
                'مسئول فروش انتخاب‌شده معتبر نیست.'
            );
        }


        return $order;
    }


    public static function handle_assign_order() {

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
                    'response' => 403,
                ]
            );
        }


        $order_id =
            isset($_POST['order_id'])
                ? absint(
                    $_POST['order_id']
                )
                : 0;


        $assignee_user_id =
            isset($_POST['assignee_user_id'])
                ? absint(
                    $_POST['assignee_user_id']
                )
                : 0;


        check_admin_referer(
            'hom_assign_order_' .
            $order_id
        );


        $result =
            self::assign_order(
                $order_id,
                $assignee_user_id,
                get_current_user_id()
            );


        wp_safe_redirect(
            add_query_arg(
                'notice',
                is_wp_error($result)
                    ? 'assignee-error'
                    : 'assignee-saved',
                self::detail_url(
                    $order_id
                )
            )
        );

        exit;
    }



    private static function b2b_profile_meta_map() {

        return [

            'legal_name' =>
                '_hom_b2b_legal_name',

            'national_id' =>
                '_hom_b2b_national_id',

            'economic_code' =>
                '_hom_b2b_economic_code',

            'registration_no' =>
                '_hom_b2b_registration_no',

            'postcode' =>
                '_hom_b2b_postcode',

            'address' =>
                '_hom_b2b_address',
        ];
    }


    public static function b2b_customer_profile(
        $customer_id
    ) {

        $customer_id =
            absint(
                $customer_id
            );


        $data = [

            'legal_name' => '',
            'national_id' => '',
            'economic_code' => '',
            'registration_no' => '',
            'postcode' => '',
            'address' => '',
        ];


        if ($customer_id < 1) {
            return $data;
        }


        foreach (
            self::b2b_profile_meta_map()
            as $field => $meta_key
        ) {

            $data[$field] =
                trim(
                    (string)
                    get_user_meta(
                        $customer_id,
                        $meta_key,
                        true
                    )
                );
        }


        $customer =
            new WC_Customer(
                $customer_id
            );


        if (
            '' === $data['legal_name']
        ) {

            $data['legal_name'] =
                trim(
                    (string)
                    $customer
                        ->get_billing_company()
                );
        }


        if (
            '' === $data['postcode']
        ) {

            $data['postcode'] =
                trim(
                    (string)
                    $customer
                        ->get_billing_postcode()
                );
        }


        if (
            '' === $data['address']
        ) {

            $parts =
                array_filter(
                    [
                        $customer
                            ->get_billing_address_1(),

                        $customer
                            ->get_billing_address_2(),

                        $customer
                            ->get_billing_city(),

                        $customer
                            ->get_billing_state(),
                    ]
                );


            $data['address'] =
                trim(
                    implode(
                        '، ',
                        $parts
                    )
                );
        }


        return $data;
    }


    public static function b2b_customer_data(
        $order
    ) {

        $empty = [

            'legal_name' => '',
            'national_id' => '',
            'economic_code' => '',
            'registration_no' => '',
            'postcode' => '',
            'address' => '',
        ];


        if (!($order instanceof WC_Order)) {
            return $empty;
        }


        $profile =
            self::b2b_customer_profile(
                $order->get_customer_id()
            );


        $data = [];


        foreach (
            self::b2b_profile_meta_map()
            as $field => $meta_key
        ) {

            $order_value =
                trim(
                    (string)
                    $order->get_meta(
                        $meta_key,
                        true
                    )
                );


            $data[$field] =
                '' !== $order_value
                    ? $order_value
                    : (
                        $profile[$field]
                        ?? ''
                    );
        }


        if (
            '' === $data['legal_name']
        ) {

            $data['legal_name'] =
                trim(
                    (string)
                    $order
                        ->get_billing_company()
                );
        }


        if (
            '' === $data['postcode']
        ) {

            $data['postcode'] =
                trim(
                    (string)
                    $order
                        ->get_billing_postcode()
                );
        }


        if (
            '' === $data['address']
        ) {

            $data['address'] =
                trim(
                    wp_strip_all_tags(
                        $order
                            ->get_formatted_billing_address()
                    )
                );
        }


        return $data;
    }


    private static function save_b2b_customer_profile(
        $customer_id,
        array $data
    ) {

        $customer_id =
            absint(
                $customer_id
            );


        if ($customer_id < 1) {
            return;
        }


        foreach (
            self::b2b_profile_meta_map()
            as $field => $meta_key
        ) {

            update_user_meta(
                $customer_id,
                $meta_key,
                $data[$field] ?? ''
            );
        }


        update_user_meta(
            $customer_id,
            '_hom_b2b_updated_at',
            current_time('mysql')
        );
    }


    public static function save_b2b_customer(
        $order_id,
        array $data,
        $actor_user_id
    ) {

        $order =
            self::get_order(
                $order_id
            );


        if (!$order) {

            return new WP_Error(
                'order_missing',
                'سفارش پیدا نشد.'
            );
        }


        $actor_user_id =
            absint(
                $actor_user_id
            );


        $clean = [

            'legal_name' =>
                sanitize_text_field(
                    (string)
                    ($data['legal_name'] ?? '')
                ),

            'national_id' =>
                sanitize_text_field(
                    (string)
                    ($data['national_id'] ?? '')
                ),

            'economic_code' =>
                sanitize_text_field(
                    (string)
                    ($data['economic_code'] ?? '')
                ),

            'registration_no' =>
                sanitize_text_field(
                    (string)
                    ($data['registration_no'] ?? '')
                ),

            'postcode' =>
                sanitize_text_field(
                    (string)
                    ($data['postcode'] ?? '')
                ),

            'address' =>
                sanitize_textarea_field(
                    (string)
                    ($data['address'] ?? '')
                ),
        ];


        foreach (
            self::b2b_profile_meta_map()
            as $field => $meta_key
        ) {

            $order->update_meta_data(
                $meta_key,
                $clean[$field]
            );
        }


        $order->update_meta_data(
            '_hom_b2b_updated_at',
            current_time('mysql')
        );


        $order->update_meta_data(
            '_hom_b2b_updated_by',
            $actor_user_id
        );


        self::save_b2b_customer_profile(
            $order->get_customer_id(),
            $clean
        );


        HOM_Order_Audit::record(
            $order,
            'b2b_customer_updated',
            $actor_user_id,
            'اطلاعات حقوقی خریدار و پروفایل دائمی مشتری به‌روزرسانی شد.'
        );


        $order->save();


        return $order;
    }


    public static function handle_save_b2b_customer() {

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


        $order_id =
            isset($_POST['order_id'])
                ? absint(
                    $_POST['order_id']
                )
                : 0;


        check_admin_referer(
            'hom_save_b2b_customer_' .
            $order_id
        );


        $data = [

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

            'address' =>
                isset($_POST['b2b_address'])
                    ? wp_unslash(
                        $_POST['b2b_address']
                    )
                    : '',
        ];


        $result =
            self::save_b2b_customer(
                $order_id,
                $data,
                get_current_user_id()
            );


        wp_safe_redirect(
            add_query_arg(
                'notice',
                is_wp_error($result)
                    ? 'b2b-error'
                    : 'b2b-saved',
                self::detail_url(
                    $order_id
                )
            )
        );

        exit;
    }

}
