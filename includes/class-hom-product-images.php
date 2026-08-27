<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOM_Product_Images {

    private const WATERMARK_FILENAME =
        'sgo-watermark-auto-products.png';

    private const WATERMARK_OPACITY =
        1.0;


    private static $notice = '';
    private static $notice_type = 'success';


    public static function handle_request() {

        if (
            'POST' !== strtoupper(
                $_SERVER['REQUEST_METHOD'] ?? ''
            )
        ) {
            return;
        }

        if (!HOM_Auth::is_owner_logged_in()) {
            return;
        }

        $action = isset($_POST['hom_action'])
            ? sanitize_key(
                wp_unslash($_POST['hom_action'])
            )
            : '';

        $allowed_actions = [
            'upload_main_image',
            'upload_gallery_images',
            'remove_main_image',
            'remove_gallery_image',
        ];

        if (
            !in_array(
                $action,
                $allowed_actions,
                true
            )
        ) {
            return;
        }

        if (
            !current_user_can(
                HOM_Capabilities::CAP_MANAGE_PRODUCT_IMAGES
            )
        ) {
            self::error(
                'شما اجازه مدیریت تصاویر محصولات را ندارید.'
            );

            return;
        }

        $product_id = isset($_POST['product_id'])
            ? absint($_POST['product_id'])
            : 0;

        $product = self::get_product(
            $product_id
        );

        if (!$product) {

            self::error(
                'محصول معتبر نیست.'
            );

            return;
        }

        if (
            !isset($_POST['hom_product_images_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash(
                        $_POST['hom_product_images_nonce']
                    )
                ),
                'hom_product_images_' . $product_id
            )
        ) {

            self::error(
                'درخواست معتبر نیست. صفحه را تازه‌سازی کنید.'
            );

            return;
        }


        switch ($action) {

            case 'upload_main_image':
                self::upload_main_image(
                    $product
                );
                break;

            case 'upload_gallery_images':
                self::upload_gallery_images(
                    $product
                );
                break;

            case 'remove_main_image':
                self::remove_main_image(
                    $product
                );
                break;

            case 'remove_gallery_image':
                self::remove_gallery_image(
                    $product
                );
                break;
        }
    }


    public static function get_notice() {

        return self::$notice;
    }


    public static function get_notice_type() {

        return self::$notice_type;
    }


    public static function get_product(
        $product_id
    ) {

        $product_id = absint(
            $product_id
        );

        if (!$product_id) {
            return false;
        }

        if (
            'product' !==
            get_post_type($product_id)
        ) {
            return false;
        }

        $product =
            wc_get_product(
                $product_id
            );

        return $product ?: false;
    }


    public static function get_product_images(
        $product
    ) {

        if (
            !($product instanceof WC_Product)
        ) {
            return [
                'main'    => null,
                'gallery' => [],
            ];
        }


        $main_id =
            absint(
                $product->get_image_id()
            );

        $main = $main_id
            ? self::attachment_data(
                $main_id
            )
            : null;


        $gallery = [];

        foreach (
            (array) $product
                ->get_gallery_image_ids()
            as $attachment_id
        ) {

            $attachment_id =
                absint(
                    $attachment_id
                );

            if (!$attachment_id) {
                continue;
            }

            $data =
                self::attachment_data(
                    $attachment_id
                );

            if ($data) {
                $gallery[] = $data;
            }
        }


        return [
            'main'    => $main,
            'gallery' => $gallery,
        ];
    }


    private static function attachment_data(
        $attachment_id
    ) {

        $attachment_id =
            absint(
                $attachment_id
            );

        if (
            !$attachment_id ||
            'attachment' !==
            get_post_type(
                $attachment_id
            )
        ) {
            return null;
        }

        $thumb =
            wp_get_attachment_image_url(
                $attachment_id,
                'medium'
            );

        $full =
            wp_get_attachment_image_url(
                $attachment_id,
                'full'
            );

        return [
            'id'    => $attachment_id,
            'title' => get_the_title(
                $attachment_id
            ),
            'alt'   => (string) get_post_meta(
                $attachment_id,
                '_wp_attachment_image_alt',
                true
            ),
            'thumb' => $thumb
                ? (string) $thumb
                : '',
            'full'  => $full
                ? (string) $full
                : '',
        ];
    }


    private static function upload_main_image(
        $product
    ) {

        if (
            empty($_FILES['main_image']) ||
            empty($_FILES['main_image']['name'])
        ) {

            self::error(
                'ابتدا تصویر اصلی را انتخاب کنید.'
            );

            return;
        }


        $attachment_id =
            self::upload_one(
                $_FILES['main_image'],
                $product,
                'main',
                0
            );


        if (is_wp_error($attachment_id)) {

            self::error(
                $attachment_id
                    ->get_error_message()
            );

            return;
        }


        $product->set_image_id(
            $attachment_id
        );

        $product->save();


        self::success(
            'تصویر اصلی محصول با موفقیت ذخیره شد.'
        );
    }


    private static function upload_gallery_images(
        $product
    ) {

        if (
            empty($_FILES['gallery_images']) ||
            empty($_FILES['gallery_images']['name'])
        ) {

            self::error(
                'ابتدا تصویر یا تصاویر گالری را انتخاب کنید.'
            );

            return;
        }


        $files =
            self::normalize_multiple_files(
                $_FILES['gallery_images']
            );

        if (!$files) {

            self::error(
                'تصویر معتبری برای گالری انتخاب نشده است.'
            );

            return;
        }


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


        $start_index =
            count($gallery_ids) + 1;

        $uploaded = 0;
        $errors = [];


        foreach (
            $files as $offset => $file
        ) {

            $attachment_id =
                self::upload_one(
                    $file,
                    $product,
                    'gallery',
                    $start_index + $offset
                );


            if (
                is_wp_error(
                    $attachment_id
                )
            ) {

                $errors[] =
                    $attachment_id
                        ->get_error_message();

                continue;
            }


            $gallery_ids[] =
                absint(
                    $attachment_id
                );

            $uploaded++;
        }


        if ($uploaded) {

            $product
                ->set_gallery_image_ids(
                    array_values(
                        array_unique(
                            $gallery_ids
                        )
                    )
                );

            $product->save();
        }


        if (
            $uploaded &&
            !$errors
        ) {

            self::success(
                sprintf(
                    '%s تصویر به گالری اضافه شد.',
                    number_format_i18n(
                        $uploaded
                    )
                )
            );

            return;
        }


        if ($uploaded) {

            self::error(
                sprintf(
                    '%s تصویر ذخیره شد؛ برخی فایل‌ها خطا داشتند: %s',
                    number_format_i18n(
                        $uploaded
                    ),
                    implode(
                        ' | ',
                        array_unique(
                            $errors
                        )
                    )
                )
            );

            return;
        }


        self::error(
            implode(
                ' | ',
                array_unique(
                    $errors
                )
            )
        );
    }


    private static function remove_main_image(
        $product
    ) {

        $product->set_image_id(0);
        $product->save();

        /*
         * The media file itself is intentionally NOT deleted.
         * It may be used elsewhere in WordPress.
         */
        self::success(
            'تصویر اصلی از این محصول حذف شد.'
        );
    }


    private static function remove_gallery_image(
        $product
    ) {

        $attachment_id =
            isset($_POST['attachment_id'])
                ? absint(
                    $_POST['attachment_id']
                )
                : 0;

        if (!$attachment_id) {

            self::error(
                'تصویر گالری معتبر نیست.'
            );

            return;
        }


        $gallery_ids =
            array_values(
                array_filter(
                    array_map(
                        'absint',
                        (array) $product
                            ->get_gallery_image_ids()
                    ),
                    static function ($id)
                    use ($attachment_id) {

                        return
                            $id !==
                            $attachment_id;
                    }
                )
            );


        $product
            ->set_gallery_image_ids(
                $gallery_ids
            );

        $product->save();


        /*
         * Only the relationship with this product is removed.
         * The media file is kept for safety.
         */
        self::success(
            'تصویر از گالری این محصول حذف شد.'
        );
    }


    private static function upload_one(
        $file,
        $product,
        $role,
        $index
    ) {

        if (
            !($product instanceof WC_Product)
        ) {
            return new WP_Error(
                'hom_invalid_product',
                'محصول معتبر نیست.'
            );
        }


        if (
            !is_array($file) ||
            empty($file['tmp_name']) ||
            empty($file['name'])
        ) {
            return new WP_Error(
                'hom_invalid_file',
                'فایل تصویر معتبر نیست.'
            );
        }


        $error =
            isset($file['error'])
                ? (int) $file['error']
                : UPLOAD_ERR_OK;

        if (
            UPLOAD_ERR_OK !==
            $error
        ) {
            return new WP_Error(
                'hom_upload_error',
                'بارگذاری فایل با خطا مواجه شد.'
            );
        }


        $size =
            isset($file['size'])
                ? absint(
                    $file['size']
                )
                : 0;

        if (
            !$size ||
            $size >
            wp_max_upload_size()
        ) {
            return new WP_Error(
                'hom_file_size',
                sprintf(
                    'حجم هر تصویر باید کمتر از %s باشد.',
                    size_format(
                        wp_max_upload_size()
                    )
                )
            );
        }


        $extension =
            strtolower(
                pathinfo(
                    (string) $file['name'],
                    PATHINFO_EXTENSION
                )
            );

        $allowed_extensions = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'heic' => 'image/heic',
            'heif' => 'image/heif',
        ];


        if (
            !isset(
                $allowed_extensions[
                    $extension
                ]
            )
        ) {
            return new WP_Error(
                'hom_file_type',
                'فرمت مجاز: JPG، PNG، WebP یا AVIF.'
            );
        }


        $check =
            wp_check_filetype_and_ext(
                $file['tmp_name'],
                $file['name']
            );


        if (
            empty($check['type']) ||
            0 !== strpos(
                $check['type'],
                'image/'
            )
        ) {
            return new WP_Error(
                'hom_invalid_image',
                'فایل انتخاب‌شده تصویر معتبر نیست.'
            );
        }


        require_once ABSPATH
            . 'wp-admin/includes/file.php';

        require_once ABSPATH
            . 'wp-admin/includes/image.php';

        require_once ABSPATH
            . 'wp-admin/includes/media.php';


        /*
         * The browser editor sends a NEW square copy.
         * Apply the automatic watermark only to that copy.
         */
        $watermark_result =
            self::apply_auto_watermark(
                $file['tmp_name']
            );

        if (is_wp_error($watermark_result)) {
            return $watermark_result;
        }


        $file['name'] =
            self::build_filename(
                $product,
                $role,
                $index,
                $extension
            );


        $upload =
            wp_handle_sideload(
                $file,
                [
                    'test_form' => false,
                    'mimes'     => [
                        'jpg|jpeg|jpe' =>
                            'image/jpeg',

                        'png' =>
                            'image/png',

                        'webp' =>
                            'image/webp',

                        'avif' =>
                            'image/avif',

                        'heic' =>
                            'image/heic',

                        'heif' =>
                            'image/heif',
                    ],
                ]
            );


        if (
            !empty($upload['error'])
        ) {
            return new WP_Error(
                'hom_handle_upload',
                (string) $upload['error']
            );
        }


        if (
            empty($upload['file']) ||
            empty($upload['type'])
        ) {
            return new WP_Error(
                'hom_handle_upload',
                'ذخیره فایل تصویر انجام نشد.'
            );
        }


        $title =
            self::build_attachment_title(
                $product,
                $role,
                $index
            );


        $attachment_id =
            wp_insert_attachment(
                [
                    'post_mime_type' =>
                        $upload['type'],

                    'post_title' =>
                        $title,

                    'post_content' =>
                        '',

                    'post_status' =>
                        'inherit',

                    'post_parent' =>
                        $product->get_id(),
                ],
                $upload['file'],
                $product->get_id(),
                true
            );


        if (
            is_wp_error(
                $attachment_id
            )
        ) {

            /*
             * Owner Panel never deletes physical media files.
             */
            return $attachment_id;
        }


        $metadata =
            wp_generate_attachment_metadata(
                $attachment_id,
                $upload['file']
            );

        wp_update_attachment_metadata(
            $attachment_id,
            $metadata
        );


        update_post_meta(
            $attachment_id,
            '_wp_attachment_image_alt',
            self::build_alt_text(
                $product,
                $role,
                $index
            )
        );


        update_post_meta(
            $attachment_id,
            '_hom_auto_watermarked',
            1
        );


        update_post_meta(
            $attachment_id,
            '_hom_product_image_product_id',
            $product->get_id()
        );

        update_post_meta(
            $attachment_id,
            '_hom_product_image_role',
            $role
        );


        return absint(
            $attachment_id
        );
    }


    private static function get_watermark_attachment_id() {

        $items =
            get_posts(
                [
                    'post_type' =>
                        'attachment',

                    'post_status' =>
                        'inherit',

                    'post_mime_type' =>
                        'image/png',

                    'posts_per_page' =>
                        1,

                    'fields' =>
                        'ids',

                    'orderby' =>
                        'ID',

                    'order' =>
                        'DESC',

                    'meta_query' =>
                        [
                            [
                                'key' =>
                                    '_wp_attached_file',

                                'value' =>
                                    self::WATERMARK_FILENAME,

                                'compare' =>
                                    'LIKE',
                            ],
                        ],
                ]
            );


        return !empty($items)
            ? absint($items[0])
            : 0;
    }



    private static function get_watermark_path() {

        $attachment_id =
            self::get_watermark_attachment_id();


        if (!$attachment_id) {

            return new WP_Error(
                'hom_watermark_missing',
                sprintf(
                    'واترمارک %s در رسانه‌های سایت پیدا نشد.',
                    self::WATERMARK_FILENAME
                )
            );
        }


        $path =
            get_attached_file(
                $attachment_id
            );


        if (
            !$path ||
            !is_readable($path)
        ) {

            return new WP_Error(
                'hom_watermark_unreadable',
                'فایل واترمارک قابل خواندن نیست.'
            );
        }


        return $path;
    }



    public static function watermark_is_ready() {

        return !is_wp_error(
            self::get_watermark_path()
        );
    }



    public static function watermark_url() {

        $attachment_id =
            self::get_watermark_attachment_id();

        if (!$attachment_id) {
            return '';
        }

        return (string) wp_get_attachment_url(
            $attachment_id
        );
    }



    private static function apply_auto_watermark(
        $target_path
    ) {

        $watermark_path =
            self::get_watermark_path();


        if (is_wp_error($watermark_path)) {
            return $watermark_path;
        }


        if (class_exists('Imagick')) {

            return self::apply_watermark_imagick(
                $target_path,
                $watermark_path
            );
        }


        if (
            extension_loaded('gd') &&
            function_exists('imagecreatefromjpeg')
        ) {

            return self::apply_watermark_gd(
                $target_path,
                $watermark_path
            );
        }


        return new WP_Error(
            'hom_watermark_engine',
            'امکان پردازش واترمارک روی سرور فعال نیست.'
        );
    }



    private static function apply_watermark_imagick(
        $target_path,
        $watermark_path
    ) {

        try {

            $image =
                new Imagick(
                    $target_path
                );

            $logo =
                new Imagick(
                    $watermark_path
                );


            $width =
                (int) $image->getImageWidth();

            $height =
                (int) $image->getImageHeight();


            if (
                !$width ||
                !$height
            ) {
                throw new RuntimeException(
                    'Invalid image dimensions'
                );
            }


            /*
             * Logo = 18% of final image width.
             */
            $logo_width =
                max(
                    80,
                    (int) round(
                        $width * 0.18
                    )
                );


            $logo->thumbnailImage(
                $logo_width,
                $logo_width,
                true
            );


            $logo->setImageAlphaChannel(
                Imagick::ALPHACHANNEL_ACTIVATE
            );


            /*
             * Preserve the original PNG alpha channel.
             *
             * WATERMARK_OPACITY is currently 1.0, so the visible
             * parts of the logo stay at full strength while fully
             * transparent pixels remain transparent.
             */
            if (self::WATERMARK_OPACITY < 1.0) {

                $logo->evaluateImage(
                    Imagick::EVALUATE_MULTIPLY,
                    self::WATERMARK_OPACITY,
                    Imagick::CHANNEL_ALPHA
                );
            }


            $margin_x =
                max(
                    18,
                    (int) round(
                        $width * 0.035
                    )
                );

            $margin_y =
                max(
                    18,
                    (int) round(
                        $height * 0.035
                    )
                );


            /*
             * Soft shadow.
             */
            try {

                $shadow =
                    clone $logo;

                $shadow->setImageBackgroundColor(
                    new ImagickPixel(
                        'rgba(0,0,0,0.40)'
                    )
                );

                $shadow->shadowImage(
                    35,
                    2,
                    2,
                    3
                );

                $image->compositeImage(
                    $shadow,
                    Imagick::COMPOSITE_OVER,
                    $margin_x + 2,
                    $margin_y + 3
                );

                $shadow->clear();
                $shadow->destroy();

            } catch (Throwable $e) {
                // Shadow is optional.
            }


            /*
             * Top-left watermark.
             */
            $image->compositeImage(
                $logo,
                Imagick::COMPOSITE_OVER,
                $margin_x,
                $margin_y
            );


            $image->setImageCompressionQuality(
                94
            );


            if (
                !$image->writeImage(
                    $target_path
                )
            ) {
                throw new RuntimeException(
                    'Image write failed'
                );
            }


            $logo->clear();
            $logo->destroy();

            $image->clear();
            $image->destroy();


            return true;

        } catch (Throwable $e) {

            return new WP_Error(
                'hom_watermark_imagick',
                'اعمال واترمارک روی تصویر انجام نشد.'
            );
        }
    }



    private static function apply_watermark_gd(
        $target_path,
        $watermark_path
    ) {

        $image =
            @imagecreatefromjpeg(
                $target_path
            );

        $logo =
            @imagecreatefrompng(
                $watermark_path
            );


        if (
            !$image ||
            !$logo
        ) {

            if ($image) {
                imagedestroy($image);
            }

            if ($logo) {
                imagedestroy($logo);
            }


            return new WP_Error(
                'hom_watermark_gd',
                'پردازش واترمارک انجام نشد.'
            );
        }


        $width =
            imagesx($image);

        $height =
            imagesy($image);

        $source_width =
            imagesx($logo);

        $source_height =
            imagesy($logo);


        $logo_width =
            max(
                80,
                (int) round(
                    $width * 0.18
                )
            );

        $logo_height =
            (int) round(
                $source_height *
                (
                    $logo_width /
                    $source_width
                )
            );


        $scaled =
            imagecreatetruecolor(
                $logo_width,
                $logo_height
            );


        imagealphablending(
            $scaled,
            false
        );

        imagesavealpha(
            $scaled,
            true
        );


        $transparent =
            imagecolorallocatealpha(
                $scaled,
                255,
                255,
                255,
                127
            );


        imagefill(
            $scaled,
            0,
            0,
            $transparent
        );


        imagecopyresampled(
            $scaled,
            $logo,
            0,
            0,
            0,
            0,
            $logo_width,
            $logo_height,
            $source_width,
            $source_height
        );


        $margin_x =
            max(
                18,
                (int) round(
                    $width * 0.035
                )
            );

        $margin_y =
            max(
                18,
                (int) round(
                    $height * 0.035
                )
            );


        /*
         * IMPORTANT:
         *
         * Do not use imagecopymerge() here. It does not preserve
         * per-pixel PNG alpha correctly and can turn transparent
         * areas into dark/black pixels.
         *
         * imagecopy() respects the alpha channel of the scaled PNG.
         */
        imagealphablending(
            $image,
            true
        );

        imagecopy(
            $image,
            $scaled,
            $margin_x,
            $margin_y,
            0,
            0,
            $logo_width,
            $logo_height
        );


        $saved =
            imagejpeg(
                $image,
                $target_path,
                94
            );


        imagedestroy($scaled);
        imagedestroy($logo);
        imagedestroy($image);


        if (!$saved) {

            return new WP_Error(
                'hom_watermark_save',
                'ذخیره تصویر واترمارک‌شده انجام نشد.'
            );
        }


        return true;
    }



    private static function build_filename(
        $product,
        $role,
        $index,
        $extension
    ) {

        $product_id =
            $product->get_id();

        $sku =
            (string) $product
                ->get_sku();

        $part_number =
            trim(
                (string) get_post_meta(
                    $product_id,
                    '_mpn_part_number',
                    true
                )
            );


        $source =
            $sku ?: $part_number;

        if (!$source) {
            $source =
                'product-' .
                $product_id;
        }


        $source =
            strtolower(
                remove_accents(
                    $source
                )
            );

        $source =
            preg_replace(
                '/[^a-z0-9]+/i',
                '-',
                $source
            );

        $source =
            trim(
                (string) $source,
                '-'
            );


        if (!$source) {
            $source =
                'product-' .
                $product_id;
        }


        if ('main' === $role) {

            $suffix =
                'main';

        } else {

            $suffix =
                'gallery-' .
                str_pad(
                    (string) max(
                        1,
                        absint($index)
                    ),
                    2,
                    '0',
                    STR_PAD_LEFT
                );
        }


        $filename =
            sanitize_file_name(
                sprintf(
                    '%s-product-%d-%s.%s',
                    $source,
                    $product_id,
                    $suffix,
                    strtolower($extension)
                )
            );


        return self::make_unique_seo_filename(
            $filename
        );
    }


    private static function make_unique_seo_filename(
        $filename
    ) {

        $filename =
            sanitize_file_name(
                (string) $filename
            );


        if ('' === $filename) {
            return $filename;
        }


        $uploads =
            wp_upload_dir();


        if (
            !empty($uploads['error']) ||
            empty($uploads['path'])
        ) {
            /*
             * WordPress will still apply its own unique filename
             * protection during the final upload.
             */
            return $filename;
        }


        $directory =
            trailingslashit(
                $uploads['path']
            );


        $original_path =
            $directory .
            $filename;


        /*
         * Keep the clean SEO filename when there is no collision.
         */
        if (
            !file_exists(
                $original_path
            )
        ) {
            return $filename;
        }


        $extension =
            strtolower(
                pathinfo(
                    $filename,
                    PATHINFO_EXTENSION
                )
            );


        $basename =
            pathinfo(
                $filename,
                PATHINFO_FILENAME
            );


        /*
         * Internal revision token:
         *
         * r + YYYYMMDDHHMMSS + short entropy
         *
         * Example:
         * r20260826150231-a7f3c1
         *
         * It is only added when the clean filename already exists.
         */
        $attempt = 0;


        do {

            $attempt++;


            $timestamp =
                wp_date(
                    'YmdHis'
                );


            $entropy =
                substr(
                    hash(
                        'sha256',
                        implode(
                            '|',
                            [
                                microtime(true),
                                wp_rand(),
                                $filename,
                                $attempt,
                            ]
                        )
                    ),
                    0,
                    6
                );


            $revision =
                'r' .
                $timestamp .
                '-' .
                $entropy;


            $candidate =
                sanitize_file_name(
                    $basename .
                    '-' .
                    $revision .
                    (
                        $extension
                            ? '.' . $extension
                            : ''
                    )
                );


            $candidate_path =
                $directory .
                $candidate;


        } while (
            file_exists(
                $candidate_path
            ) &&
            $attempt < 20
        );


        /*
         * Extremely defensive fallback. Normally the loop above
         * succeeds on its first iteration.
         */
        if (
            file_exists(
                $candidate_path
            )
        ) {

            $candidate =
                wp_unique_filename(
                    $uploads['path'],
                    $candidate
                );
        }


        return $candidate;
    }


    private static function build_attachment_title(
        $product,
        $role,
        $index
    ) {

        $name =
            wp_strip_all_tags(
                $product->get_name()
            );

        if ('main' === $role) {

            return
                $name .
                ' - تصویر اصلی';
        }

        return sprintf(
            '%s - تصویر گالری %s',
            $name,
            number_format_i18n(
                max(
                    1,
                    absint($index)
                )
            )
        );
    }


    private static function build_alt_text(
        $product,
        $role,
        $index
    ) {

        $name =
            wp_strip_all_tags(
                $product->get_name()
            );

        if ('main' === $role) {
            return $name;
        }

        return sprintf(
            '%s - تصویر %s',
            $name,
            number_format_i18n(
                max(
                    1,
                    absint($index)
                )
            )
        );
    }


    private static function normalize_multiple_files(
        $files
    ) {

        if (
            !isset($files['name']) ||
            !is_array($files['name'])
        ) {
            return [];
        }


        $normalized = [];

        foreach (
            $files['name']
            as $index => $name
        ) {

            if ('' === (string) $name) {
                continue;
            }

            $normalized[] = [
                'name' =>
                    $name,

                'type' =>
                    $files['type'][$index]
                    ?? '',

                'tmp_name' =>
                    $files['tmp_name'][$index]
                    ?? '',

                'error' =>
                    $files['error'][$index]
                    ?? UPLOAD_ERR_NO_FILE,

                'size' =>
                    $files['size'][$index]
                    ?? 0,
            ];
        }


        return $normalized;
    }


    private static function success(
        $message
    ) {

        self::$notice =
            (string) $message;

        self::$notice_type =
            'success';
    }


    private static function error(
        $message
    ) {

        self::$notice =
            (string) $message;

        self::$notice_type =
            'error';
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX / STAGED IMAGE WORKFLOW
    |--------------------------------------------------------------------------
    |
    | Uploading an image only creates a Media Library attachment.
    | The WooCommerce product itself is changed only by ajax_save_images().
    |
    */

    public static function register() {

        add_action(
            'wp_ajax_hom_upload_product_image',
            [self::class, 'ajax_upload_image']
        );

        add_action(
            'wp_ajax_hom_product_media_library',
            [self::class, 'ajax_media_library']
        );

        add_action(
            'wp_ajax_hom_save_product_images',
            [self::class, 'ajax_save_images']
        );


        add_action(
            'wp_ajax_hom_discard_staged_product_images',
            [self::class, 'ajax_discard_staged_images']
        );
    }


    public static function ajax_upload_image() {

        $product =
            self::ajax_authorize_product();

        if (is_wp_error($product)) {
            self::ajax_error($product);
        }


        $role =
            isset($_POST['role'])
                ? sanitize_key(
                    wp_unslash($_POST['role'])
                )
                : '';


        if (
            !in_array(
                $role,
                ['main', 'gallery'],
                true
            )
        ) {
            wp_send_json_error(
                [
                    'message' =>
                        'نوع تصویر معتبر نیست.',
                ],
                400
            );
        }


        $index =
            isset($_POST['index'])
                ? max(
                    0,
                    absint($_POST['index'])
                )
                : 0;


        if (
            empty($_FILES['image']) ||
            empty($_FILES['image']['name'])
        ) {
            wp_send_json_error(
                [
                    'message' =>
                        'فایل تصویر ارسال نشده است.',
                ],
                400
            );
        }


        $attachment_id =
            self::upload_one(
                $_FILES['image'],
                $product,
                $role,
                $index
            );


        if (is_wp_error($attachment_id)) {
            self::ajax_error(
                $attachment_id
            );
        }


        /*
         * A newly uploaded file is staged first.
         *
         * Do not attach it to the WooCommerce product until
         * the owner clicks "Final Save".
         */
        wp_update_post(
            [
                'ID' =>
                    $attachment_id,

                'post_parent' =>
                    0,
            ]
        );

        update_post_meta(
            $attachment_id,
            '_hom_product_image_staged',
            1
        );


        $data =
            self::attachment_data(
                $attachment_id
            );


        if (!$data) {

            wp_send_json_error(
                [
                    'message' =>
                        'تصویر ذخیره شد اما اطلاعات رسانه قابل دریافت نیست.',
                ],
                500
            );
        }


        $data['filename'] =
            basename(
                (string) get_post_meta(
                    $attachment_id,
                    '_wp_attached_file',
                    true
                )
            );

        $data['role'] =
            $role;

        $data['staged'] =
            true;


        wp_send_json_success(
            [
                'message' =>
                    'تصویر با موفقیت آپلود شد.',

                'attachment' =>
                    $data,
            ]
        );
    }


    public static function ajax_media_library() {

        $product =
            self::ajax_authorize_product();

        if (is_wp_error($product)) {
            self::ajax_error($product);
        }


        $page =
            isset($_POST['page'])
                ? max(
                    1,
                    absint($_POST['page'])
                )
                : 1;


        $search =
            isset($_POST['search'])
                ? sanitize_text_field(
                    wp_unslash(
                        $_POST['search']
                    )
                )
                : '';


        $per_page = 24;


        $query_args = [
            'post_type' =>
                'attachment',

            'post_status' =>
                'inherit',

            'post_mime_type' =>
                'image',


            'post__not_in' =>
                array_filter(
                    [
                        self::get_watermark_attachment_id(),
                    ]
                ),

            'posts_per_page' =>
                $per_page,

            'paged' =>
                $page,

            'orderby' =>
                'date',

            'order' =>
                'DESC',

            /*
             * Never expose unfinished staged uploads
             * inside the reusable Media Library.
             */
            'meta_query' =>
                [
                    [
                        'key' =>
                            '_hom_product_image_staged',

                        'compare' =>
                            'NOT EXISTS',
                    ],
                ],
        ];


        if ('' !== $search) {
            $query_args['s'] =
                $search;
        }


        $query =
            new WP_Query(
                $query_args
            );


        $items = [];


        foreach (
            $query->posts
            as $attachment
        ) {

            $attachment_id =
                absint(
                    $attachment->ID
                );

            $data =
                self::attachment_data(
                    $attachment_id
                );

            if (!$data) {
                continue;
            }


            $metadata =
                wp_get_attachment_metadata(
                    $attachment_id
                );


            $data['filename'] =
                basename(
                    (string) get_post_meta(
                        $attachment_id,
                        '_wp_attached_file',
                        true
                    )
                );

            $data['width'] =
                isset($metadata['width'])
                    ? absint(
                        $metadata['width']
                    )
                    : 0;

            $data['height'] =
                isset($metadata['height'])
                    ? absint(
                        $metadata['height']
                    )
                    : 0;

            $data['staged'] =
                false;

            $items[] =
                $data;
        }


        wp_send_json_success(
            [
                'items' =>
                    $items,

                'page' =>
                    $page,

                'total' =>
                    absint(
                        $query->found_posts
                    ),

                'total_pages' =>
                    absint(
                        $query->max_num_pages
                    ),
            ]
        );
    }


    public static function ajax_save_images() {

        $product =
            self::ajax_authorize_product();

        if (is_wp_error($product)) {
            self::ajax_error($product);
        }


        $product_id =
            $product->get_id();


        $main_id =
            isset($_POST['main_id'])
                ? absint(
                    $_POST['main_id']
                )
                : 0;


        $gallery_raw =
            isset($_POST['gallery_ids'])
                ? wp_unslash(
                    $_POST['gallery_ids']
                )
                : '[]';


        $gallery_ids =
            json_decode(
                $gallery_raw,
                true
            );


        if (!is_array($gallery_ids)) {

            wp_send_json_error(
                [
                    'message' =>
                        'اطلاعات گالری معتبر نیست.',
                ],
                400
            );
        }


        $gallery_ids =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'absint',
                            $gallery_ids
                        )
                    )
                )
            );


        /*
         * An image cannot be both the main image
         * and a gallery image at the same time.
         */
        if ($main_id) {

            $gallery_ids =
                array_values(
                    array_filter(
                        $gallery_ids,
                        static function ($id)
                        use ($main_id) {

                            return
                                $id !==
                                $main_id;
                        }
                    )
                );
        }


        $all_ids =
            array_merge(
                $main_id
                    ? [$main_id]
                    : [],
                $gallery_ids
            );


        foreach ($all_ids as $attachment_id) {

            if (
                !self::is_valid_image_attachment(
                    $attachment_id
                )
            ) {

                wp_send_json_error(
                    [
                        'message' =>
                            sprintf(
                                'رسانه شماره %d تصویر معتبر نیست.',
                                $attachment_id
                            ),
                    ],
                    400
                );
            }


            /*
             * A staged upload may only be finalized
             * for the same product that created it.
             */
            $is_staged =
                (bool) get_post_meta(
                    $attachment_id,
                    '_hom_product_image_staged',
                    true
                );


            if ($is_staged) {

                $source_product_id =
                    absint(
                        get_post_meta(
                            $attachment_id,
                            '_hom_product_image_product_id',
                            true
                        )
                    );


                if (
                    $source_product_id !==
                    $product_id
                ) {

                    wp_send_json_error(
                        [
                            'message' =>
                                'تصویر آپلودشده متعلق به این محصول نیست.',
                        ],
                        400
                    );
                }
            }
        }


        /*
         * Only this final action changes WooCommerce product image relations.
         */
        $product->set_image_id(
            $main_id
        );

        $product->set_gallery_image_ids(
            $gallery_ids
        );

        $product->save();


        foreach ($all_ids as $attachment_id) {

            $is_staged =
                (bool) get_post_meta(
                    $attachment_id,
                    '_hom_product_image_staged',
                    true
                );


            if ($is_staged) {

                /*
                 * Only NEW files uploaded from this panel are attached
                 * to this product after Final Save.
                 */
                wp_update_post(
                    [
                        'ID' =>
                            $attachment_id,

                        'post_parent' =>
                            $product_id,
                    ]
                );


                delete_post_meta(
                    $attachment_id,
                    '_hom_product_image_staged'
                );
            }

            /*
             * IMPORTANT:
             *
             * Existing Media Library images are reusable assets.
             * Never rename them and never overwrite their:
             *
             * - physical filename
             * - attachment title
             * - ALT text
             * - attachment parent
             *
             * because another product/page may already use them.
             */
        }


        clean_post_cache(
            $product_id
        );


        wp_send_json_success(
            [
                'message' =>
                    'تغییرات تصاویر محصول با موفقیت ذخیره شد.',

                'product_id' =>
                    $product_id,

                'main_id' =>
                    $main_id,

                'gallery_ids' =>
                    $gallery_ids,

                'gallery_count' =>
                    count(
                        $gallery_ids
                    ),
            ]
        );
    }


    public static function ajax_discard_staged_images() {

        $product =
            self::ajax_authorize_product();

        if (is_wp_error($product)) {
            self::ajax_error($product);
        }

        $product_id =
            $product->get_id();

        $raw_ids =
            isset($_POST['attachment_ids'])
                ? wp_unslash($_POST['attachment_ids'])
                : '[]';

        $attachment_ids =
            json_decode(
                $raw_ids,
                true
            );

        if (!is_array($attachment_ids)) {

            wp_send_json_error(
                [
                    'message' =>
                        'اطلاعات تصاویر موقت معتبر نیست.',
                ],
                400
            );
        }

        $attachment_ids =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'absint',
                            $attachment_ids
                        )
                    )
                )
            );

        $released = [];

        foreach ($attachment_ids as $attachment_id) {

            $is_staged =
                (bool) get_post_meta(
                    $attachment_id,
                    '_hom_product_image_staged',
                    true
                );

            if (!$is_staged) {
                continue;
            }

            $source_product_id =
                absint(
                    get_post_meta(
                        $attachment_id,
                        '_hom_product_image_product_id',
                        true
                    )
                );

            if ($source_product_id !== $product_id) {
                continue;
            }

            if (
                'attachment' !==
                get_post_type($attachment_id)
            ) {
                continue;
            }

            /*
             * فقط وضعیت موقت برداشته می‌شود.
             * فایل و Media Library هرگز حذف نمی‌شوند.
             */
            delete_post_meta(
                $attachment_id,
                '_hom_product_image_staged'
            );

            wp_update_post(
                [
                    'ID' =>
                        $attachment_id,

                    'post_parent' =>
                        0,
                ]
            );

            $released[] =
                $attachment_id;
        }

        wp_send_json_success(
            [
                'message' =>
                    'تغییرات موقت لغو شد؛ هیچ فایل رسانه‌ای حذف نشد.',

                'released_ids' =>
                    $released,

                'released_count' =>
                    count($released),
            ]
        );
    }



    private static function ajax_authorize_product() {

        if (!is_user_logged_in()) {

            return new WP_Error(
                'hom_not_logged_in',
                'نشست کاربری معتبر نیست.'
            );
        }


        if (
            !current_user_can(
                HOM_Capabilities::CAP_ACCESS_PANEL
            ) ||
            !current_user_can(
                HOM_Capabilities::CAP_MANAGE_PRODUCT_IMAGES
            )
        ) {

            return new WP_Error(
                'hom_forbidden',
                'شما اجازه مدیریت تصاویر محصولات را ندارید.'
            );
        }


        $nonce =
            isset($_POST['nonce'])
                ? sanitize_text_field(
                    wp_unslash(
                        $_POST['nonce']
                    )
                )
                : '';


        if (
            !wp_verify_nonce(
                $nonce,
                'hom_product_images_ajax'
            )
        ) {

            return new WP_Error(
                'hom_bad_nonce',
                'اعتبار درخواست منقضی شده است. صفحه را تازه‌سازی کنید.'
            );
        }


        $product_id =
            isset($_POST['product_id'])
                ? absint(
                    $_POST['product_id']
                )
                : 0;


        $product =
            self::get_product(
                $product_id
            );


        if (!$product) {

            return new WP_Error(
                'hom_invalid_product',
                'محصول معتبر نیست.'
            );
        }


        return $product;
    }


    private static function is_valid_image_attachment(
        $attachment_id
    ) {

        $attachment_id =
            absint(
                $attachment_id
            );


        if (
            !$attachment_id ||
            'attachment' !==
            get_post_type(
                $attachment_id
            )
        ) {
            return false;
        }


        $mime =
            (string) get_post_mime_type(
                $attachment_id
            );


        return
            0 === strpos(
                $mime,
                'image/'
            );
    }


    private static function ajax_error(
        $error
    ) {

        $message =
            is_wp_error($error)
                ? $error->get_error_message()
                : 'خطای ناشناخته رخ داد.';


        wp_send_json_error(
            [
                'message' =>
                    $message,
            ],
            400
        );
    }

}
