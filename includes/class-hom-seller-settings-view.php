<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Seller_Settings_View {

    public static function render() {

        if (
            !current_user_can(
                HOM_Capabilities::CAP_MANAGE_PREINVOICES
            )
        ) {
            ?>
            <div class="hom-alert hom-alert-error">
                شما اجازه ویرایش اطلاعات فروشنده را ندارید.
            </div>
            <?php
            return;
        }


        $data =
            HOM_Seller_Settings::data();


        $notice =
            isset($_GET['notice'])
                ? sanitize_key(
                    wp_unslash(
                        $_GET['notice']
                    )
                )
                : '';

        ?>

        <div class="hom-page-heading">

            <div>

                <span class="hom-eyebrow">
                    SELLER SETTINGS
                </span>

                <h1>
                    اطلاعات فروشنده
                </h1>

                <p>
                    اطلاعات حقوقی و تماس شرکت که در اسناد فروش و فاکتور استفاده می‌شود.
                </p>

            </div>

        </div>


        <?php if ('seller-saved' === $notice) : ?>

            <div class="hom-alert hom-alert-success">
                اطلاعات فروشنده با موفقیت ذخیره شد.
            </div>

        <?php elseif ('seller-error' === $notice) : ?>

            <div class="hom-alert hom-alert-error">
                ذخیره اطلاعات فروشنده انجام نشد.
            </div>

        <?php endif; ?>


        <section class="hom-card">

            <form
                method="post"
                action="<?php
                echo esc_url(
                    admin_url(
                        'admin-post.php'
                    )
                );
                ?>"
            >

                <input
                    type="hidden"
                    name="action"
                    value="hom_save_seller_settings"
                >

                <?php
                wp_nonce_field(
                    'hom_save_seller_settings'
                );
                ?>


                <div
                    style="
                        display:grid;
                        grid-template-columns:
                            repeat(2,minmax(0,1fr));
                        gap:16px
                    "
                >

                    <label class="hom-field">

                        <span>
                            نام حقوقی شرکت
                        </span>

                        <input
                            type="text"
                            name="legal_name"
                            value="<?php
                            echo esc_attr(
                                $data['legal_name']
                            );
                            ?>"
                            required
                        >

                    </label>


                    <label class="hom-field">

                        <span>
                            شناسه ملی
                        </span>

                        <input
                            type="text"
                            name="national_id"
                            dir="ltr"
                            inputmode="numeric"
                            value="<?php
                            echo esc_attr(
                                $data['national_id']
                            );
                            ?>"
                        >

                    </label>


                    <label class="hom-field">

                        <span>
                            کد اقتصادی
                        </span>

                        <input
                            type="text"
                            name="economic_code"
                            dir="ltr"
                            inputmode="numeric"
                            value="<?php
                            echo esc_attr(
                                $data['economic_code']
                            );
                            ?>"
                        >

                    </label>


                    <label class="hom-field">

                        <span>
                            شماره ثبت
                        </span>

                        <input
                            type="text"
                            name="registration_no"
                            dir="ltr"
                            value="<?php
                            echo esc_attr(
                                $data['registration_no']
                            );
                            ?>"
                        >

                    </label>


                    <label class="hom-field">

                        <span>
                            کدپستی
                        </span>

                        <input
                            type="text"
                            name="postcode"
                            dir="ltr"
                            inputmode="numeric"
                            value="<?php
                            echo esc_attr(
                                $data['postcode']
                            );
                            ?>"
                        >

                    </label>


                    <label class="hom-field">

                        <span>
                            تلفن شرکت
                        </span>

                        <input
                            type="text"
                            name="phone"
                            dir="ltr"
                            value="<?php
                            echo esc_attr(
                                $data['phone']
                            );
                            ?>"
                        >

                    </label>

                </div>


                <label
                    class="hom-field"
                    style="margin-top:16px"
                >

                    <span>
                        آدرس کامل شرکت
                    </span>

                    <textarea
                        name="seller_address"
                        rows="4"
                    ><?php
                    echo esc_textarea(
                        $data['address']
                    );
                    ?></textarea>

                </label>


                <div style="margin-top:18px">

                    <button
                        type="submit"
                        class="hom-button hom-button-primary"
                    >
                        ذخیره اطلاعات فروشنده
                    </button>

                </div>

            </form>

        </section>

        <?php
    }
}
