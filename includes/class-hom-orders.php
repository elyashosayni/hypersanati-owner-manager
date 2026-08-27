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

        add_filter(
            'woocommerce_valid_order_statuses_for_payment_complete',
            [self::class, 'allow_preinvoice_payment_complete'],
            20,
            2
        );

        add_filter(
            'woocommerce_payment_complete_order_status',
            [self::class, 'manual_payment_processing_status'],
            20,
            3
        );

        add_action(
            'admin_post_hom_confirm_manual_payment',
            [self::class, 'handle_confirm_manual_payment']
        );

        add_action(
            'admin_post_hom_correct_manual_payment',
            [self::class, 'handle_correct_manual_payment']
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


    public static function allow_preinvoice_payment_complete(
        $statuses,
        $order
    ) {

        if (
            $order instanceof WC_Order &&
            'yes' ===
                $order->get_meta(
                    '_hsb_is_preinvoice',
                    true
                )
        ) {

            $statuses[] =
                'preinv-approved';
        }


        return
            array_values(
                array_unique(
                    $statuses
                )
            );
    }


    public static function manual_payment_processing_status(
        $status,
        $order_id,
        $order
    ) {

        unset($order_id);


        if (
            $order instanceof WC_Order &&
            'yes' ===
                $order->get_meta(
                    '_hsb_is_preinvoice',
                    true
                ) &&
            'hom_manual_transfer' ===
                $order->get_payment_method()
        ) {

            return 'processing';
        }


        return $status;
    }


    public static function can_confirm_manual_payment(
        $order
    ) {

        return
            $order instanceof WC_Order &&
            'yes' ===
                $order->get_meta(
                    '_hsb_is_preinvoice',
                    true
                ) &&
            'preinv-approved' ===
                $order->get_status() &&
            !$order->is_paid() &&
            (float)
                $order->get_total()
                > 0;
    }


    public static function manual_payment_data(
        $order
    ) {

        if (!($order instanceof WC_Order)) {

            return [

                'amount' =>
                    '',

                'reference' =>
                    '',

                'notes' =>
                    '',

                'confirmed_at' =>
                    '',

                'confirmed_by' =>
                    0,
            ];
        }


        return [

            'amount' =>
                trim(
                    (string)
                    $order->get_meta(
                        '_hom_manual_payment_amount',
                        true
                    )
                ),

            'reference' =>
                trim(
                    (string)
                    $order->get_meta(
                        '_hom_manual_payment_reference',
                        true
                    )
                ),

            'notes' =>
                trim(
                    (string)
                    $order->get_meta(
                        '_hom_manual_payment_notes',
                        true
                    )
                ),

            'confirmed_at' =>
                trim(
                    (string)
                    $order->get_meta(
                        '_hom_manual_payment_confirmed_at',
                        true
                    )
                ),

            'confirmed_by' =>
                absint(
                    $order->get_meta(
                        '_hom_manual_payment_confirmed_by',
                        true
                    )
                ),
        ];
    }


    public static function confirm_manual_payment(
        $order_id,
        array $data,
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


        if (
            !self::can_confirm_manual_payment(
                $order
            )
        ) {

            return new WP_Error(
                'manual_payment_not_allowed',
                'این سفارش در وضعیت قابل تأیید پرداخت دستی نیست.'
            );
        }


        $actor_user_id =
            absint(
                $actor_user_id
            );


        if ($actor_user_id < 1) {

            return new WP_Error(
                'payment_actor_required',
                'کاربر تأییدکننده پرداخت مشخص نیست.'
            );
        }


        $amount =
            self::normalize_decimal(
                $data['amount']
                ?? ''
            );


        $order_total =
            (float)
            $order->get_total();


        if ($amount <= 0) {

            return new WP_Error(
                'payment_amount_required',
                'مبلغ پرداخت را وارد کنید.'
            );
        }


        if (
            abs(
                $amount
                -
                $order_total
            ) >= 0.01
        ) {

            return new WP_Error(
                'payment_amount_mismatch',
                'مبلغ تأییدشده باید دقیقاً برابر مبلغ نهایی سفارش باشد.'
            );
        }


        $reference =
            sanitize_text_field(
                (string)
                ($data['reference'] ?? '')
            );


        if ('' === $reference) {

            return new WP_Error(
                'payment_reference_required',
                'شماره پیگیری / مرجع پرداخت الزامی است.'
            );
        }


        $notes =
            sanitize_textarea_field(
                (string)
                ($data['notes'] ?? '')
            );


        /*
         * All validation is complete.
         * From here WooCommerce owns the paid-state transition.
         */
        $order->set_payment_method(
            'hom_manual_transfer'
        );


        $order->set_payment_method_title(
            'کارت‌به‌کارت / واریز دستی'
        );


        $order->update_meta_data(
            '_hom_payment_source',
            'manual'
        );


        $order->update_meta_data(
            '_hom_manual_payment_amount',
            wc_format_decimal(
                $amount
            )
        );


        $order->update_meta_data(
            '_hom_manual_payment_reference',
            $reference
        );


        $order->update_meta_data(
            '_hom_manual_payment_notes',
            $notes
        );


        $order->update_meta_data(
            '_hom_manual_payment_confirmed_at',
            current_time('mysql')
        );


        $order->update_meta_data(
            '_hom_manual_payment_confirmed_by',
            $actor_user_id
        );


        $completed =
            $order->payment_complete(
                $reference
            );


        if (!$completed) {

            return new WP_Error(
                'payment_complete_failed',
                'ثبت پرداخت در ووکامرس کامل نشد.'
            );
        }


        /*
         * The manual payment filter above forces this B2B
         * workflow into processing, even for unusual products.
         */
        if (
            'processing' !==
            $order->get_status()
        ) {

            return new WP_Error(
                'payment_status_failed',
                'پرداخت ثبت شد اما سفارش وارد وضعیت آماده‌سازی نشد.'
            );
        }


        HOM_Order_Audit::record(
            $order,
            'payment_confirmed',
            $actor_user_id,
            'دریافت کامل وجه سفارش به‌صورت دستی تأیید شد.',
            [
                'source' =>
                    'owner-panel',

                'changes' => [

                    [
                        'field' =>
                            'وضعیت پرداخت',

                        'before' =>
                            'تأیید نشده',

                        'after' =>
                            'پرداخت کامل تأیید شد',
                    ],

                    [
                        'field' =>
                            'روش پرداخت',

                        'before' =>
                            '—',

                        'after' =>
                            'کارت‌به‌کارت / واریز دستی',
                    ],

                    [
                        'field' =>
                            'مبلغ پرداخت',

                        'before' =>
                            '—',

                        'after' =>
                            wc_format_decimal(
                                $amount
                            )
                            . ' '
                            . $order->get_currency(),
                    ],

                    [
                        'field' =>
                            'مرجع پرداخت',

                        'before' =>
                            '—',

                        'after' =>
                            $reference,
                    ],
                ],
            ]
        );


        $actor =
            get_userdata(
                $actor_user_id
            );


        $order->add_order_note(
            sprintf(
                'دریافت کامل وجه به‌صورت کارت‌به‌کارت / واریز دستی توسط %s تأیید شد. مرجع پرداخت: %s',
                $actor
                    ? (
                        $actor->display_name
                        ?: $actor->user_login
                    )
                    : 'کاربر فروش',
                $reference
            )
        );


        $order->save();


        return $order;
    }


    public static function can_correct_manual_payment(
        $order
    ) {

        if (!($order instanceof WC_Order)) {
            return false;
        }


        $payment =
            self::manual_payment_data(
                $order
            );


        return
            'hom_manual_transfer'
                ===
                $order->get_payment_method() &&
            'manual'
                ===
                $order->get_meta(
                    '_hom_payment_source',
                    true
                ) &&
            !empty(
                $payment['confirmed_at']
            ) &&
            $order->is_paid();
    }


    public static function correct_manual_payment(
        $order_id,
        array $data,
        $actor_user_id = 0,
        $correction_reason = ''
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
            !self::can_correct_manual_payment(
                $order
            )
        ) {

            return new WP_Error(
                'payment_correction_not_allowed',
                'اطلاعات پرداخت این سفارش قابل اصلاح نیست.'
            );
        }


        $actor_user_id =
            absint(
                $actor_user_id
            );


        if ($actor_user_id < 1) {

            return new WP_Error(
                'payment_actor_required',
                'کاربر اصلاح‌کننده پرداخت مشخص نیست.'
            );
        }


        $correction_reason =
            sanitize_textarea_field(
                (string)
                $correction_reason
            );


        if ('' === $correction_reason) {

            return new WP_Error(
                'payment_correction_reason_required',
                'برای اصلاح اطلاعات پرداخت، دلیل اصلاح را وارد کنید.'
            );
        }


        $current =
            self::manual_payment_data(
                $order
            );


        $reference =
            sanitize_text_field(
                (string)
                ($data['reference'] ?? '')
            );


        if ('' === $reference) {

            return new WP_Error(
                'payment_reference_required',
                'شماره پیگیری / مرجع پرداخت الزامی است.'
            );
        }


        $notes =
            sanitize_textarea_field(
                (string)
                ($data['notes'] ?? '')
            );


        $changes = [];


        if (
            $reference !==
            (
                $current['reference']
                ?? ''
            )
        ) {

            $changes[] = [

                'field' =>
                    'مرجع پرداخت',

                'before' =>
                    $current['reference']
                    ?: '—',

                'after' =>
                    $reference,
            ];
        }


        if (
            $notes !==
            (
                $current['notes']
                ?? ''
            )
        ) {

            $changes[] = [

                'field' =>
                    'توضیحات پرداخت',

                'before' =>
                    $current['notes']
                    ?: '—',

                'after' =>
                    $notes
                    ?: '—',
            ];
        }


        if (!$changes) {

            return new WP_Error(
                'payment_no_changes',
                'هیچ تغییری در اطلاعات پرداخت ایجاد نشده است.'
            );
        }


        /*
         * Safe correction only:
         * amount, paid date and paid status remain unchanged.
         */
        $order->update_meta_data(
            '_hom_manual_payment_reference',
            $reference
        );


        $order->update_meta_data(
            '_hom_manual_payment_notes',
            $notes
        );


        $order->update_meta_data(
            '_hom_manual_payment_corrected_at',
            current_time('mysql')
        );


        $order->update_meta_data(
            '_hom_manual_payment_corrected_by',
            $actor_user_id
        );


        /*
         * Keep WooCommerce transaction ID synchronized
         * with our corrected manual-payment reference.
         */
        $order->set_transaction_id(
            $reference
        );


        HOM_Order_Audit::record(
            $order,
            'payment_corrected',
            $actor_user_id,
            'اطلاعات ثبت‌شده پرداخت دستی اصلاح شد.',
            [
                'source' =>
                    'owner-panel',

                'reason' =>
                    $correction_reason,

                'changes' =>
                    $changes,
            ]
        );


        $actor =
            get_userdata(
                $actor_user_id
            );


        $order->add_order_note(
            sprintf(
                'اطلاعات پرداخت دستی توسط %s اصلاح شد. دلیل: %s',
                $actor
                    ? (
                        $actor->display_name
                        ?: $actor->user_login
                    )
                    : 'کاربر فروش',
                $correction_reason
            )
        );


        $order->save();


        return $order;
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
        $shipping_cost = null,
        $correction_reason = ''
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


        $already_priced =
            '' !==
            trim(
                (string)
                $order->get_meta(
                    '_hom_preinvoice_priced_at',
                    true
                )
            );


        $correction_reason =
            sanitize_textarea_field(
                (string)
                $correction_reason
            );


        /*
         * Stage every requested change first.
         * Nothing is persisted before validation completes.
         */
        $item_updates = [];

        $changes = [];


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


            $old_unit_price =
                (float)
                $item->get_total()
                /
                $quantity;


            if (
                abs(
                    $old_unit_price
                    -
                    $unit_price
                ) < 0.00001
            ) {
                continue;
            }


            $item_updates[] = [

                'item' =>
                    $item,

                'line_total' =>
                    $unit_price
                    *
                    $quantity,
            ];


            $changes[] = [

                'field' =>
                    'قیمت واحد: ' .
                    $item->get_name(),

                'before' =>
                    wc_format_decimal(
                        $old_unit_price
                    ),

                'after' =>
                    wc_format_decimal(
                        $unit_price
                    ),
            ];
        }


        $shipping_update =
            false;

        $new_shipping_cost =
            null;


        if (null !== $shipping_cost) {

            $old_shipping_cost =
                (float)
                self::preinvoice_shipping_cost(
                    $order
                );


            $new_shipping_cost =
                self::normalize_decimal(
                    $shipping_cost
                );


            if (
                abs(
                    $old_shipping_cost
                    -
                    $new_shipping_cost
                ) >= 0.00001
            ) {

                $shipping_update =
                    true;


                $changes[] = [

                    'field' =>
                        'هزینه ارسال',

                    'before' =>
                        wc_format_decimal(
                            $old_shipping_cost
                        ),

                    'after' =>
                        wc_format_decimal(
                            $new_shipping_cost
                        ),
                ];
            }
        }


        if (!$changes) {

            return new WP_Error(
                'no_changes',
                'هیچ تغییری در قیمت‌ها یا هزینه ارسال ایجاد نشده است.'
            );
        }


        /*
         * An existing pricing record is financially sensitive.
         * Require the reason BEFORE writing any order data.
         */
        if (
            $already_priced &&
            '' === $correction_reason
        ) {

            return new WP_Error(
                'correction_reason_required',
                'برای اصلاح قیمت‌های ثبت‌شده، دلیل اصلاح را وارد کنید.'
            );
        }


        /*
         * Validation passed. Persist all staged changes.
         */
        foreach ($item_updates as $update) {

            $item =
                $update['item'];


            $item->set_subtotal(
                $update['line_total']
            );

            $item->set_total(
                $update['line_total']
            );

            $item->save();
        }


        if ($shipping_update) {

            self::set_preinvoice_shipping_cost(
                $order,
                $new_shipping_cost
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
            $already_priced
                ? 'price_corrected'
                : 'price_updated',
            $actor_user_id,
            $already_priced
                ? 'قیمت‌های پیش‌فاکتور اصلاح شد.'
                : 'قیمت اقلام پیش‌فاکتور ثبت شد.',
            [
                'source' =>
                    'owner-panel',

                'reason' =>
                    $already_priced
                        ? $correction_reason
                        : '',

                'changes' =>
                    $changes,
            ]
        );


        $order->add_order_note(
            $already_priced
                ? 'قیمت پیش‌فاکتور توسط مدیر فروش اصلاح شد.'
                : 'قیمت اقلام پیش‌فاکتور توسط مدیر فروشگاه ثبت شد.'
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
                    : null,
                isset($_POST['correction_reason'])
                    ? wp_unslash(
                        $_POST['correction_reason']
                    )
                    : ''
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

    public static function handle_confirm_manual_payment() {

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
            'hom_confirm_manual_payment_' .
            $order_id
        );


        $result =
            self::confirm_manual_payment(
                $order_id,
                [
                    'amount' =>
                        isset($_POST['payment_amount'])
                            ? wp_unslash(
                                $_POST['payment_amount']
                            )
                            : '',

                    'reference' =>
                        isset($_POST['payment_reference'])
                            ? wp_unslash(
                                $_POST['payment_reference']
                            )
                            : '',

                    'notes' =>
                        isset($_POST['payment_notes'])
                            ? wp_unslash(
                                $_POST['payment_notes']
                            )
                            : '',
                ],
                get_current_user_id()
            );


        wp_safe_redirect(
            add_query_arg(
                'notice',
                is_wp_error($result)
                    ? 'payment-error'
                    : 'payment-confirmed',
                self::detail_url(
                    $order_id
                )
            )
        );

        exit;
    }


    public static function handle_correct_manual_payment() {

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
            'hom_correct_manual_payment_' .
            $order_id
        );


        $result =
            self::correct_manual_payment(
                $order_id,
                [
                    'reference' =>
                        isset(
                            $_POST[
                                'payment_reference'
                            ]
                        )
                            ? wp_unslash(
                                $_POST[
                                    'payment_reference'
                                ]
                            )
                            : '',

                    'notes' =>
                        isset(
                            $_POST[
                                'payment_notes'
                            ]
                        )
                            ? wp_unslash(
                                $_POST[
                                    'payment_notes'
                                ]
                            )
                            : '',
                ],
                get_current_user_id(),
                isset($_POST['correction_reason'])
                    ? wp_unslash(
                        $_POST['correction_reason']
                    )
                    : ''
            );


        wp_safe_redirect(
            add_query_arg(
                'notice',
                is_wp_error($result)
                    ? 'payment-correction-error'
                    : 'payment-corrected',
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
        $actor_user_id = 0,
        $correction_reason = ''
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

            return new WP_Error(
                'invalid_fulfillment_transition',
                'عملیات ارسال معتبر نیست.'
            );
        }


        $actor_user_id =
            absint(
                $actor_user_id
            );


        $correction_reason =
            sanitize_textarea_field(
                (string)
                $correction_reason
            );


        $method =
            sanitize_key(
                $data['method']
                ?? ''
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


        /*
         * Read the persisted state before doing anything.
         */
        $before =
            self::fulfillment_data(
                $order
            );


        $already_saved =
            '' !==
            trim(
                (string)
                $order->get_meta(
                    '_hom_shipping_updated_at',
                    true
                )
            );


        if (!$already_saved) {

            foreach ($before as $value) {

                if (
                    '' !==
                    trim(
                        (string)
                        $value
                    )
                ) {

                    $already_saved =
                        true;

                    break;
                }
            }
        }


        $after = [

            'method' =>
                $method,

            'company' =>
                $company,

            'tracking_code' =>
                $tracking,

            'freight_payment' =>
                $freight_payment,

            'notes' =>
                $notes,
        ];


        $field_labels = [

            'method' =>
                'روش ارسال',

            'company' =>
                'شرکت / باربری / شعبه',

            'tracking_code' =>
                'کد رهگیری / شماره بارنامه',

            'freight_payment' =>
                'وضعیت کرایه',

            'notes' =>
                'توضیحات ارسال',
        ];


        $freight_labels = [

            '' =>
                'تعیین نشده',

            'prepaid' =>
                'پرداخت شده',

            'collect' =>
                'پس‌کرایه',
        ];


        $changes = [];


        foreach (
            $field_labels
            as $field => $label
        ) {

            $old_value =
                trim(
                    (string)
                    ($before[$field] ?? '')
                );


            $new_value =
                trim(
                    (string)
                    ($after[$field] ?? '')
                );


            if ($old_value === $new_value) {
                continue;
            }


            $old_display =
                $old_value;

            $new_display =
                $new_value;


            if ('method' === $field) {

                $old_display =
                    '' === $old_value
                        ? 'تعیین نشده'
                        : (
                            $methods[$old_value]
                            ?? $old_value
                        );


                $new_display =
                    '' === $new_value
                        ? 'تعیین نشده'
                        : (
                            $methods[$new_value]
                            ?? $new_value
                        );
            }


            if (
                'freight_payment'
                ===
                $field
            ) {

                $old_display =
                    $freight_labels[
                        $old_value
                    ]
                    ?? $old_value;


                $new_display =
                    $freight_labels[
                        $new_value
                    ]
                    ?? $new_value;
            }


            $changes[] = [

                'field' =>
                    $label,

                'before' =>
                    $old_display,

                'after' =>
                    $new_display,
            ];
        }


        /*
         * Validate lifecycle transition BEFORE any write.
         */
        $current =
            $order->get_status();


        if (
            'ready' === $transition &&
            'processing' !== $current
        ) {

            return new WP_Error(
                'ready_transition_invalid',
                'فقط سفارش در حال آماده‌سازی را می‌توان آماده ارسال کرد.'
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
        }


        if (
            'delivered' === $transition &&
            'hom-shipped' !== $current
        ) {

            return new WP_Error(
                'delivered_transition_invalid',
                'فقط سفارش ارسال‌شده را می‌توان تحویل‌شده ثبت کرد.'
            );
        }


        /*
         * Saving the same shipping data alone is not an action.
         */
        if (
            'save' === $transition &&
            !$changes
        ) {

            return new WP_Error(
                'shipping_no_changes',
                'هیچ تغییری در اطلاعات ارسال ایجاد نشده است.'
            );
        }


        /*
         * Existing shipping data is sensitive.
         * Require reason before any meta/status mutation.
         */
        if (
            $already_saved &&
            $changes &&
            '' === $correction_reason
        ) {

            return new WP_Error(
                'shipping_correction_reason_required',
                'برای اصلاح اطلاعات ارسال ثبت‌شده، دلیل اصلاح را وارد کنید.'
            );
        }


        /*
         * All validation passed.
         * Persist shipping information only when changed.
         */
        if ($changes) {

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

            $order->update_meta_data(
                '_hom_shipping_updated_at',
                current_time('mysql')
            );

            $order->update_meta_data(
                '_hom_shipping_updated_by',
                $actor_user_id
            );


            HOM_Order_Audit::record(
                $order,
                $already_saved
                    ? 'shipping_corrected'
                    : 'shipping_updated',
                $actor_user_id,
                $already_saved
                    ? 'اطلاعات ارسال سفارش اصلاح شد.'
                    : 'اطلاعات ارسال سفارش ثبت شد.',
                [
                    'source' =>
                        'owner-panel',

                    'reason' =>
                        $already_saved
                            ? $correction_reason
                            : '',

                    'changes' =>
                        $changes,
                ]
            );
        }


        /*
         * Lifecycle transitions stay independent
         * from shipping metadata changes.
         */
        if ('ready' === $transition) {

            $order->update_meta_data(
                '_hom_ready_at',
                current_time('mysql')
            );

            $order->update_meta_data(
                '_hom_ready_by',
                $actor_user_id
            );

            $order->set_status(
                'hom-ready',
                'سفارش آماده ارسال شد.'
            );


            HOM_Order_Audit::record(
                $order,
                'order_ready',
                $actor_user_id,
                'سفارش آماده ارسال اعلام شد.',
                [
                    'source' =>
                        'owner-panel',
                ]
            );
        }


        if ('shipped' === $transition) {

            $order->update_meta_data(
                '_hom_shipped_at',
                current_time('mysql')
            );

            $order->update_meta_data(
                '_hom_shipped_by',
                $actor_user_id
            );

            $order->set_status(
                'hom-shipped',
                'سفارش ارسال شد.'
            );


            HOM_Order_Audit::record(
                $order,
                'order_shipped',
                $actor_user_id,
                'ارسال سفارش ثبت شد.',
                [
                    'source' =>
                        'owner-panel',
                ]
            );
        }


        if ('delivered' === $transition) {

            $order->update_meta_data(
                '_hom_delivered_at',
                current_time('mysql')
            );

            $order->update_meta_data(
                '_hom_delivered_by',
                $actor_user_id
            );

            $order->set_status(
                'completed',
                'تحویل سفارش به مشتری ثبت شد.'
            );


            HOM_Order_Audit::record(
                $order,
                'order_delivered',
                $actor_user_id,
                'تحویل سفارش به مشتری ثبت شد.',
                [
                    'source' =>
                        'owner-panel',
                ]
            );
        }


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
                get_current_user_id(),
                isset($_POST['correction_reason'])
                    ? wp_unslash(
                        $_POST['correction_reason']
                    )
                    : ''
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
        $actor_user_id,
        $correction_reason = ''
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


        $correction_reason =
            sanitize_textarea_field(
                (string)
                $correction_reason
            );


        $already_saved =
            '' !==
            trim(
                (string)
                $order->get_meta(
                    '_hom_b2b_updated_at',
                    true
                )
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


        /*
         * For an existing order snapshot, compare against
         * that immutable order-level data.
         *
         * For the first save, compare against the effective
         * profile/billing data currently shown in the form.
         */
        $before =
            $already_saved
                ? [
                    'legal_name' =>
                        trim(
                            (string)
                            $order->get_meta(
                                '_hom_b2b_legal_name',
                                true
                            )
                        ),

                    'national_id' =>
                        trim(
                            (string)
                            $order->get_meta(
                                '_hom_b2b_national_id',
                                true
                            )
                        ),

                    'economic_code' =>
                        trim(
                            (string)
                            $order->get_meta(
                                '_hom_b2b_economic_code',
                                true
                            )
                        ),

                    'registration_no' =>
                        trim(
                            (string)
                            $order->get_meta(
                                '_hom_b2b_registration_no',
                                true
                            )
                        ),

                    'postcode' =>
                        trim(
                            (string)
                            $order->get_meta(
                                '_hom_b2b_postcode',
                                true
                            )
                        ),

                    'address' =>
                        trim(
                            (string)
                            $order->get_meta(
                                '_hom_b2b_address',
                                true
                            )
                        ),
                ]
                : self::b2b_customer_data(
                    $order
                );


        $labels = [

            'legal_name' =>
                'نام حقوقی / نام شرکت',

            'national_id' =>
                'شناسه ملی',

            'economic_code' =>
                'کد اقتصادی',

            'registration_no' =>
                'شماره ثبت',

            'postcode' =>
                'کدپستی',

            'address' =>
                'آدرس فاکتور',
        ];


        $changes = [];


        foreach ($labels as $field => $label) {

            $old_value =
                trim(
                    (string)
                    ($before[$field] ?? '')
                );


            $new_value =
                trim(
                    (string)
                    ($clean[$field] ?? '')
                );


            if ($old_value === $new_value) {
                continue;
            }


            $changes[] = [

                'field' =>
                    $label,

                'before' =>
                    $old_value,

                'after' =>
                    $new_value,
            ];
        }


        /*
         * Once this order already owns a saved legal snapshot,
         * every actual correction must include a reason.
         */
        if (
            $already_saved &&
            $changes &&
            '' === $correction_reason
        ) {

            return new WP_Error(
                'b2b_correction_reason_required',
                'برای اصلاح اطلاعات حقوقی ثبت‌شده، دلیل اصلاح را وارد کنید.'
            );
        }


        /*
         * Re-submitting an existing snapshot without changing
         * anything should not create a fake audit event.
         */
        if (
            $already_saved &&
            !$changes
        ) {

            return new WP_Error(
                'b2b_no_changes',
                'هیچ تغییری در اطلاعات حقوقی ایجاد نشده است.'
            );
        }


        /*
         * Validation is complete. Persist order snapshot.
         */
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


        /*
         * Keep the permanent customer profile synchronized
         * only after all correction validation has passed.
         */
        self::save_b2b_customer_profile(
            $order->get_customer_id(),
            $clean
        );


        HOM_Order_Audit::record(
            $order,
            $already_saved
                ? 'b2b_customer_corrected'
                : 'b2b_customer_updated',
            $actor_user_id,
            $already_saved
                ? 'اطلاعات حقوقی خریدار اصلاح و پروفایل دائمی مشتری به‌روزرسانی شد.'
                : 'اطلاعات حقوقی خریدار ثبت و در پروفایل دائمی مشتری ذخیره شد.',
            [
                'source' =>
                    'owner-panel',

                'reason' =>
                    $already_saved
                        ? $correction_reason
                        : '',

                'changes' =>
                    $changes,
            ]
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
                get_current_user_id(),
                isset($_POST['correction_reason'])
                    ? wp_unslash(
                        $_POST['correction_reason']
                    )
                    : ''
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
