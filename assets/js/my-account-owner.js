(function () {
    'use strict';

    function createElement(
        tag,
        className,
        text
    ) {

        var element =
            document.createElement(tag);

        if (className) {
            element.className = className;
        }

        if (typeof text === 'string') {
            element.textContent = text;
        }

        return element;
    }


    function renderOwnerSidebarEntry() {

        var config =
            window.HOMOwnerAccount || {};

        if (!config.panelUrl) {
            return;
        }

        var navigation =
            document.querySelector(
                '.dashboard-nav'
            );

        if (!navigation) {
            return;
        }

        if (
            navigation.querySelector(
                '[data-hom-owner-sidebar-entry]'
            )
        ) {
            return;
        }

        var link =
            createElement(
                'a',
                'dashboard-nav-btn hom-owner-sidebar-link'
            );

        link.href =
            config.panelUrl;

        link.setAttribute(
            'data-hom-owner-sidebar-entry',
            ''
        );

        link.setAttribute(
            'aria-label',
            'ورود به مدیریت فروشگاه'
        );


        var icon =
            createElement(
                'i',
                'fa-solid fa-boxes-stacked'
            );

        icon.setAttribute(
            'aria-hidden',
            'true'
        );


        var label =
            createElement(
                'span',
                '',
                config.sidebarLabel ||
                    'مدیریت فروشگاه'
            );


        link.appendChild(
            icon
        );

        link.appendChild(
            label
        );


        var logout =
            navigation.querySelector(
                '.logout-btn'
            );


        if (logout) {

            navigation.insertBefore(
                link,
                logout
            );

        } else {

            navigation.appendChild(
                link
            );
        }
    }



    function renderOwnerEntry() {

        var config =
            window.HOMOwnerAccount || {};

        if (!config.panelUrl) {
            return;
        }


        var accountTab =
            document.querySelector(
                '#tab-account'
            );

        if (!accountTab) {
            return;
        }


        if (
            accountTab.querySelector(
                '[data-hom-owner-entry]'
            )
        ) {
            return;
        }


        var header =
            accountTab.querySelector(
                '.dashboard-header'
            );


        var card =
            createElement(
                'section',
                'hom-owner-account-entry'
            );

        card.setAttribute(
            'data-hom-owner-entry',
            ''
        );


        var icon =
            createElement(
                'div',
                'hom-owner-account-entry__icon'
            );

        icon.setAttribute(
            'aria-hidden',
            'true'
        );

        icon.textContent = '▦';


        var content =
            createElement(
                'div',
                'hom-owner-account-entry__content'
            );


        var title =
            createElement(
                'h3',
                'hom-owner-account-entry__title',
                config.title ||
                    'مدیریت فروشگاه'
            );


        var description =
            createElement(
                'p',
                'hom-owner-account-entry__description',
                config.description ||
                    'دسترسی ویژه شما برای مدیریت فروشگاه و سامان‌دهی تصاویر محصولات'
            );


        var button =
            createElement(
                'a',
                'hom-owner-account-entry__button',
                config.buttonLabel ||
                    'ورود به پنل مدیریت فروشگاه'
            );

        button.href =
            config.panelUrl;

        button.setAttribute(
            'aria-label',
            'ورود به پنل مدیریت فروشگاه'
        );


        var arrow =
            createElement(
                'span',
                'hom-owner-account-entry__arrow',
                '←'
            );

        arrow.setAttribute(
            'aria-hidden',
            'true'
        );

        button.appendChild(
            arrow
        );


        content.appendChild(
            title
        );

        content.appendChild(
            description
        );

        content.appendChild(
            button
        );


        card.appendChild(
            icon
        );

        card.appendChild(
            content
        );


        if (
            header &&
            header.parentNode
        ) {

            header.insertAdjacentElement(
                'afterend',
                card
            );

        } else {

            accountTab.prepend(
                card
            );
        }
    }

    function renderOwnerGuidance() {

        var config =
            window.HOMOwnerAccount || {};


        var ownerEntry =
            document.querySelector(
                '[data-hom-owner-entry]'
            );

        if (!ownerEntry) {
            return;
        }


        if (
            document.querySelector(
                '[data-hom-owner-guidance]'
            )
        ) {
            return;
        }


        var section =
            createElement(
                'section',
                'hom-owner-guidance'
            );

        section.setAttribute(
            'data-hom-owner-guidance',
            ''
        );


        var header =
            createElement(
                'div',
                'hom-owner-guidance__header'
            );


        var shield =
            createElement(
                'div',
                'hom-owner-guidance__shield',
                '★'
            );

        shield.setAttribute(
            'aria-hidden',
            'true'
        );


        var heading =
            createElement(
                'div',
                'hom-owner-guidance__heading'
            );


        var badge =
            createElement(
                'span',
                'hom-owner-guidance__badge',
                config.badgeLabel ||
                    'دسترسی ویژه مدیر فروشگاه'
            );


        var title =
            createElement(
                'strong',
                'hom-owner-guidance__title',
                config.noticeTitle ||
                    'دسترسی مدیریت فروشگاه برای شما فعال است'
            );


        heading.appendChild(
            badge
        );

        heading.appendChild(
            title
        );

        header.appendChild(
            shield
        );

        header.appendChild(
            heading
        );


        var notice =
            createElement(
                'p',
                'hom-owner-guidance__text',
                config.noticeText || ''
            );


        var warning =
            createElement(
                'div',
                'hom-owner-guidance__warning'
            );


        var warningIcon =
            createElement(
                'span',
                'hom-owner-guidance__warning-icon',
                '⚠'
            );

        warningIcon.setAttribute(
            'aria-hidden',
            'true'
        );


        var warningContent =
            createElement(
                'div',
                'hom-owner-guidance__warning-content'
            );


        var warningTitle =
            createElement(
                'strong',
                '',
                config.warningTitle ||
                    'پیش از اعمال تغییرات دقت کنید'
            );


        var warningText =
            createElement(
                'p',
                '',
                config.warningText || ''
            );


        warningContent.appendChild(
            warningTitle
        );

        warningContent.appendChild(
            warningText
        );

        warning.appendChild(
            warningIcon
        );

        warning.appendChild(
            warningContent
        );


        section.appendChild(
            header
        );

        section.appendChild(
            notice
        );

        section.appendChild(
            warning
        );


        if (config.helpUrl) {

            var footer =
                createElement(
                    'div',
                    'hom-owner-guidance__footer'
                );


            var helpLink =
                createElement(
                    'a',
                    'hom-owner-guidance__help'
                );

            helpLink.href =
                config.helpUrl;


            var helpIcon =
                createElement(
                    'span',
                    '',
                    '?'
                );

            helpIcon.setAttribute(
                'aria-hidden',
                'true'
            );


            var helpText =
                createElement(
                    'span',
                    '',
                    config.helpLabel ||
                        'خواندن راهنمای مدیر فروشگاه'
                );


            helpLink.appendChild(
                helpIcon
            );

            helpLink.appendChild(
                helpText
            );

            footer.appendChild(
                helpLink
            );

            section.appendChild(
                footer
            );
        }


        ownerEntry.insertAdjacentElement(
            'afterend',
            section
        );
    }



    if (
        document.readyState ===
        'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            function () {
                renderOwnerSidebarEntry();
                renderOwnerEntry();
                renderOwnerGuidance();
            }
        );

    } else {

        renderOwnerSidebarEntry();
        renderOwnerEntry();
        renderOwnerGuidance();
    }

}());
