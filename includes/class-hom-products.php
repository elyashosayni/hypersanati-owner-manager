<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOM_Products {

    public const PER_PAGE = 25;


    private static function normalize_search(
        $value
    ) {

        $value =
            sanitize_text_field(
                (string) $value
            );


        /*
         * Persian digits:
         * ۰۱۲۳۴۵۶۷۸۹
         *
         * Arabic-Indic digits:
         * ٠١٢٣٤٥٦٧٨٩
         *
         * Normalize both to ASCII:
         * 0123456789
         */
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
                ]
            );


        /*
         * Normalize invisible/extra whitespace too.
         */
        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                $value
            );


        return trim(
            (string) $value
        );
    }


    public static function search(
        $search = '',
        $page = 1
    ) {

        global $wpdb;

        $search =
            self::normalize_search(
                $search
            );

        $page = max(
            1,
            absint($page)
        );

        $per_page = self::PER_PAGE;

        $offset = (
            $page - 1
        ) * $per_page;


        $where = [
            "p.post_type = 'product'",
            "p.post_status = 'publish'",
        ];

        $params = [];


        if ('' !== $search) {

            $like =
                '%' .
                $wpdb->esc_like($search) .
                '%';

            $search_parts = [];


            if (ctype_digit($search)) {

                $search_parts[] =
                    'p.ID = %d';

                $params[] =
                    absint($search);
            }


            $search_parts[] =
                'p.post_title LIKE %s';

            $params[] = $like;


            $search_parts[] = "
                EXISTS (
                    SELECT 1
                    FROM {$wpdb->postmeta} sku_pm
                    WHERE sku_pm.post_id = p.ID
                      AND sku_pm.meta_key = '_sku'
                      AND sku_pm.meta_value LIKE %s
                )
            ";

            $params[] = $like;


            $search_parts[] = "
                EXISTS (
                    SELECT 1
                    FROM {$wpdb->postmeta} mpn_pm
                    WHERE mpn_pm.post_id = p.ID
                      AND mpn_pm.meta_key = '_mpn_part_number'
                      AND mpn_pm.meta_value LIKE %s
                )
            ";

            $params[] = $like;


            $search_parts[] = "
                EXISTS (
                    SELECT 1
                    FROM {$wpdb->term_relationships} tr
                    INNER JOIN {$wpdb->term_taxonomy} tt
                        ON tt.term_taxonomy_id =
                           tr.term_taxonomy_id
                    INNER JOIN {$wpdb->terms} t
                        ON t.term_id = tt.term_id
                    WHERE tr.object_id = p.ID
                      AND tt.taxonomy = 'product_brand'
                      AND t.name LIKE %s
                )
            ";

            $params[] = $like;


            $where[] =
                '(' .
                implode(
                    ' OR ',
                    $search_parts
                ) .
                ')';
        }


        $where_sql =
            implode(
                ' AND ',
                $where
            );


        $count_sql = "
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            WHERE {$where_sql}
        ";


        if ($params) {

            $count_sql =
                $wpdb->prepare(
                    $count_sql,
                    ...$params
                );
        }


        $total =
            (int) $wpdb->get_var(
                $count_sql
            );


        $ids_sql = "
            SELECT p.ID
            FROM {$wpdb->posts} p
            WHERE {$where_sql}
            ORDER BY p.ID DESC
            LIMIT %d OFFSET %d
        ";


        $query_params =
            array_merge(
                $params,
                [
                    $per_page,
                    $offset,
                ]
            );


        $ids_sql =
            $wpdb->prepare(
                $ids_sql,
                ...$query_params
            );


        $ids =
            array_map(
                'absint',
                (array) $wpdb->get_col(
                    $ids_sql
                )
            );


        $items = [];


        foreach ($ids as $product_id) {

            $product =
                wc_get_product(
                    $product_id
                );

            if (!$product) {
                continue;
            }


            $brand_names =
                wp_get_post_terms(
                    $product_id,
                    'product_brand',
                    [
                        'fields' => 'names',
                    ]
                );

            if (
                is_wp_error(
                    $brand_names
                )
            ) {
                $brand_names = [];
            }


            $country_names =
                wp_get_post_terms(
                    $product_id,
                    'product_country',
                    [
                        'fields' => 'names',
                    ]
                );

            if (
                is_wp_error(
                    $country_names
                )
            ) {
                $country_names = [];
            }


            $country =
                !empty($country_names)
                    ? implode(
                        '، ',
                        $country_names
                    )
                    : trim(
                        (string) get_post_meta(
                            $product_id,
                            '_country_origin',
                            true
                        )
                    );


            $image_id =
                absint(
                    $product->get_image_id()
                );


            $image_url =
                $image_id
                    ? wp_get_attachment_image_url(
                        $image_id,
                        'thumbnail'
                    )
                    : '';


            $gallery_ids =
                array_values(
                    array_filter(
                        array_map(
                            'absint',
                            (array) $product
                                ->get_gallery_image_ids()
                        )
                    )
                );


            $items[] = [

                'id' =>
                    $product_id,

                'name' =>
                    (string) $product
                        ->get_name(),

                'sku' =>
                    (string) $product
                        ->get_sku(),

                'part_number' =>
                    trim(
                        (string) get_post_meta(
                            $product_id,
                            '_mpn_part_number',
                            true
                        )
                    ),

                'brands' =>
                    (array) $brand_names,

                'country' =>
                    $country,

                'price_html' =>
                    (string) $product
                        ->get_price_html(),

                'image_id' =>
                    $image_id,

                'image_url' =>
                    $image_url
                        ? (string) $image_url
                        : '',

                'gallery_count' =>
                    count($gallery_ids),

                'stock_status' =>
                    (string) $product
                        ->get_stock_status(),
            ];
        }


        $total_pages =
            max(
                1,
                (int) ceil(
                    $total / $per_page
                )
            );


        return [

            'search' =>
                $search,

            'page' =>
                $page,

            'per_page' =>
                $per_page,

            'total' =>
                $total,

            'total_pages' =>
                $total_pages,

            'items' =>
                $items,
        ];
    }
}
