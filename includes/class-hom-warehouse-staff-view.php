<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOM_Warehouse_Staff_View {

    private static function error_message(
        $code
    ) {

        $messages = [

            'display_name_required' =>
                'نام مسئول انبار را وارد کنید.',

            'username_invalid' =>
                'نام کاربری معتبر وارد کنید.',

            'username_exists' =>
                'این نام کاربری قبلاً ثبت شده است.',

            'owner_account_conflict' =>
                'این حساب متعلق به مدیر فروشگاه است و نمی‌تواند مسئول تأیید انبار باشد. برای انبار یک نام کاربری و رمز عبور مستقل ایجاد کنید.',

            'password_short' =>
                'رمز اولیه باید حداقل ۸ کاراکتر باشد.',

            'staff_missing' =>
                'کاربر موردنظر پیدا نشد.',

            'staff_not_managed' =>
                'این حساب جزو مسئولین تأیید انبار نیست.',

            'invalid_actor' =>
                'اجازه انجام این عملیات وجود ندارد.',
        ];


        return
            $messages[$code]
            ?? 'انجام عملیات با خطا مواجه شد.';
    }


    public static function render() {

        if (
            !current_user_can(
                HOM_Capabilities::
                    CAP_MANAGE_WAREHOUSE_STAFF
            )
        ) {

            ?>
            <div class="hom-alert hom-alert-error">
                شما اجازه مدیریت مسئولین تأیید انبار را ندارید.
            </div>
            <?php

            return;
        }


        $help_url =
            add_query_arg(
                'view',
                'help-warehouse-staff',
                HOM_Router::panel_url()
            );


        $users =
            HOM_Warehouse_Staff::users();


        $active_count = 0;


        foreach ($users as $user) {

            if (
                HOM_Warehouse_Staff::
                    is_active($user)
            ) {

                $active_count++;
            }
        }


        $inactive_count =
            max(
                0,
                count($users) -
                $active_count
            );


        $notice =
            isset($_GET['notice'])
                ? sanitize_key(
                    wp_unslash(
                        $_GET['notice']
                    )
                )
                : '';


        $error_code =
            isset(
                $_GET[
                    'warehouse_staff_error'
                ]
            )
                ? sanitize_key(
                    wp_unslash(
                        $_GET[
                            'warehouse_staff_error'
                        ]
                    )
                )
                : '';

        ?>

        <div class="hom-page-heading">

            <div>

                <span class="hom-eyebrow">
                    WAREHOUSE ACCESS
                </span>

                <h1>
                    مسئولین تأیید انبار
                </h1>

                <p>
                    افرادی که اجازه اسکن QR،
                    کنترل اقلام و تأیید نهایی انبار را دارند
                    از این بخش مدیریت می‌شوند.
                </p>

                <a
                    href="<?php
                    echo esc_url(
                        $help_url
                    );
                    ?>"
                    class="hom-section-help-link"
                >
                    <span aria-hidden="true">?</span>
                    <span>
                        راهنمای مسئولین تأیید انبار
                    </span>
                </a>

            </div>

        </div>


        <?php if (
            'warehouse-staff-created' ===
            $notice
        ) : ?>

            <div class="hom-alert hom-alert-success">
                مسئول جدید انبار با موفقیت ایجاد شد.
            </div>

        <?php elseif (
            'warehouse-staff-enabled' ===
            $notice
        ) : ?>

            <div class="hom-alert hom-alert-success">
                دسترسی مسئول انبار فعال شد.
            </div>

        <?php elseif (
            'warehouse-staff-disabled' ===
            $notice
        ) : ?>

            <div class="hom-alert hom-alert-success">
                دسترسی مسئول انبار غیرفعال شد؛
                حساب کاربری حذف نشده است.
            </div>

        <?php elseif (
            'warehouse-staff-error' ===
            $notice
        ) : ?>

            <div class="hom-alert hom-alert-error">
                <?php
                echo esc_html(
                    self::error_message(
                        $error_code
                    )
                );
                ?>
            </div>

        <?php endif; ?>


        <section class="hom-warehouse-staff-summary">

            <div>

                <span>
                    کل مسئولین
                </span>

                <strong>
                    <?php
                    echo esc_html(
                        number_format_i18n(
                            count($users)
                        )
                    );
                    ?>
                </strong>

            </div>


            <div class="is-active">

                <span>
                    فعال
                </span>

                <strong>
                    <?php
                    echo esc_html(
                        number_format_i18n(
                            $active_count
                        )
                    );
                    ?>
                </strong>

            </div>


            <div class="is-inactive">

                <span>
                    غیرفعال
                </span>

                <strong>
                    <?php
                    echo esc_html(
                        number_format_i18n(
                            $inactive_count
                        )
                    );
                    ?>
                </strong>

            </div>

        </section>


        <div class="hom-warehouse-staff-layout">


            <section class="hom-card hom-warehouse-staff-create">

                <div class="hom-warehouse-staff-section-head">

                    <div>

                        <span>
                            حساب جدید
                        </span>

                        <h2>
                            افزودن مسئول انبار
                        </h2>

                    </div>

                </div>


                <form
                    method="post"
                    action="<?php
                    echo esc_url(
                        HOM_Router::panel_url()
                    );
                    ?>"
                    autocomplete="off"
                >

                    <input
                        type="hidden"
                        name="hom_action"
                        value="hom_create_warehouse_staff"
                    >


                    <?php
                    wp_nonce_field(
                        'hom_create_warehouse_staff'
                    );
                    ?>


                    <label class="hom-field">

                        <span>
                            نام و نام خانوادگی
                        </span>

                        <input
                            type="text"
                            name="display_name"
                            required
                        >

                    </label>


                    <label class="hom-field">

                        <span>
                            نام کاربری
                        </span>

                        <input
                            type="text"
                            name="username"
                            dir="ltr"
                            autocomplete="off"
                            required
                        >

                        <small>
                            برای ورود مسئول انبار استفاده می‌شود.
                        </small>

                    </label>


                    <label class="hom-field">

                        <span>
                            رمز عبور اولیه
                        </span>

                        <input
                            type="password"
                            name="password"
                            dir="ltr"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                        <small>
                            حداقل ۸ کاراکتر.
                            رمز در وردپرس به‌صورت هش‌شده ذخیره می‌شود.
                        </small>

                    </label>


                    <button
                        type="submit"
                        class="hom-button hom-button-primary hom-button-full"
                    >
                        ایجاد مسئول انبار
                    </button>

                </form>


                <div class="hom-warehouse-staff-future">

                    <strong>
                        احراز هویت موبایلی
                    </strong>

                    <p>
                        اتصال شماره موبایل و OTP در مرحله بعد
                        روی همین حساب‌ها اضافه می‌شود و نیازی به
                        تغییر ساختار دسترسی انبار نخواهد بود.
                    </p>

                </div>

            </section>


            <section class="hom-card hom-warehouse-staff-list">

                <div class="hom-warehouse-staff-section-head">

                    <div>

                        <span>
                            دسترسی‌های فعلی
                        </span>

                        <h2>
                            فهرست مسئولین
                        </h2>

                    </div>

                </div>


                <?php if (!$users) : ?>

                    <div class="hom-warehouse-staff-empty">

                        <strong>
                            هنوز مسئول انباری تعریف نشده است.
                        </strong>

                        <p>
                            اولین حساب را از فرم کنار صفحه ایجاد کنید.
                        </p>

                    </div>

                <?php else : ?>


                    <div class="hom-warehouse-staff-users">

                        <?php
                        foreach ($users as $user) :

                            $active =
                                HOM_Warehouse_Staff::
                                    is_active(
                                        $user
                                    );

                            $created_at =
                                trim(
                                    (string)
                                    get_user_meta(
                                        $user->ID,
                                        '_hom_warehouse_staff_created_at',
                                        true
                                    )
                                );
                            ?>

                            <article
                                class="
                                    hom-warehouse-staff-user
                                    <?php
                                    echo $active
                                        ? 'is-active'
                                        : 'is-inactive';
                                    ?>
                                "
                            >

                                <div class="hom-warehouse-staff-user__identity">

                                    <span
                                        class="hom-warehouse-staff-avatar"
                                        aria-hidden="true"
                                    >
                                        <?php
                                        echo esc_html(
                                            mb_substr(
                                                $user->display_name
                                                    ?: $user->user_login,
                                                0,
                                                1
                                            )
                                        );
                                        ?>
                                    </span>


                                    <div>

                                        <strong>
                                            <?php
                                            echo esc_html(
                                                $user->display_name
                                                    ?: $user->user_login
                                            );
                                            ?>
                                        </strong>

                                        <span dir="ltr">
                                            @<?php
                                            echo esc_html(
                                                $user->user_login
                                            );
                                            ?>
                                        </span>

                                    </div>

                                </div>


                                <div class="hom-warehouse-staff-user__meta">

                                    <span
                                        class="
                                            hom-warehouse-staff-status
                                            <?php
                                            echo $active
                                                ? 'is-active'
                                                : 'is-inactive';
                                            ?>
                                        "
                                    >
                                        <?php
                                        echo $active
                                            ? 'فعال'
                                            : 'غیرفعال';
                                        ?>
                                    </span>


                                    <small>
                                        ID:
                                        <?php
                                        echo esc_html(
                                            $user->ID
                                        );
                                        ?>

                                        <?php if ($created_at) : ?>

                                            · ایجاد:
                                            <?php
                                            echo esc_html(
                                                $created_at
                                            );
                                            ?>

                                        <?php endif; ?>
                                    </small>

                                </div>


                                <form
                                    method="post"
                                    action="<?php
                                    echo esc_url(
                                        HOM_Router::panel_url()
                                    );
                                    ?>"
                                >

                                    <input
                                        type="hidden"
                                        name="hom_action"
                                        value="hom_toggle_warehouse_staff"
                                    >

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?php
                                        echo esc_attr(
                                            $user->ID
                                        );
                                        ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="staff_active"
                                        value="<?php
                                        echo $active
                                            ? '0'
                                            : '1';
                                        ?>"
                                    >


                                    <?php
                                    wp_nonce_field(
                                        'hom_toggle_warehouse_staff_' .
                                        $user->ID
                                    );
                                    ?>


                                    <button
                                        type="submit"
                                        class="
                                            hom-button
                                            <?php
                                            echo $active
                                                ? 'hom-button-secondary'
                                                : 'hom-button-primary';
                                            ?>
                                        "
                                    >
                                        <?php
                                        echo $active
                                            ? 'غیرفعال کردن دسترسی'
                                            : 'فعال کردن دسترسی';
                                        ?>
                                    </button>

                                </form>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

        </div>

        <?php
    }
}
