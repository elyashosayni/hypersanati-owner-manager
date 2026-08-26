(function () {
    'use strict';

    var config = window.HOMProductImages || {};
    var root = document.querySelector('[data-hom-image-manager]');

    if (!root) {
        return;
    }

    var state = {
        main: config.initialMain || null,
        gallery: Array.isArray(config.initialGallery)
            ? config.initialGallery.slice()
            : [],

        initialMain:
            config.initialMain || null,

        initialGallery:
            Array.isArray(config.initialGallery)
                ? config.initialGallery.slice()
                : [],

        stagedIds:
            new Set(),

        initialMainId: config.initialMain
            ? Number(config.initialMain.id)
            : 0,
        initialGalleryIds: Array.isArray(config.initialGallery)
            ? config.initialGallery.map(function (item) {
                return Number(item.id);
            })
            : [],
        pendingMain: null,
        pendingGallery: [],
        uploading: 0,
        saving: false,
        sequence: 0
    };

    var media = {
        mode: null,
        page: 1,
        search: '',
        loading: false,
        totalPages: 1,
        items: new Map(),
        selected: new Map()
    };

    var mainCurrent = root.querySelector('[data-hom-main-current]');
    var mainPending = root.querySelector('[data-hom-main-pending]');
    var galleryReady = root.querySelector('[data-hom-gallery-ready]');
    var galleryPending = root.querySelector('[data-hom-gallery-pending]');
    var galleryCount = root.querySelector('[data-hom-gallery-count]');
    var galleryPendingToolbar = root.querySelector(
        '[data-hom-gallery-pending-toolbar]'
    );
    var uploadAllButton = root.querySelector('[data-hom-upload-all]');
    var finalSaveButton = root.querySelector('[data-hom-final-save]');
    var finalResetButton = root.querySelector('[data-hom-final-reset]');
    var finalStatus = root.querySelector('[data-hom-final-status]');
    var notice = root.querySelector('[data-hom-notice]');

    var mediaModal = root.querySelector('[data-hom-media-modal]');
    var mediaGrid = root.querySelector('[data-hom-media-grid]');
    var mediaEmpty = root.querySelector('[data-hom-media-empty]');
    var mediaMore = root.querySelector('[data-hom-media-more]');
    var mediaSearchInput = root.querySelector('[data-hom-media-search]');
    var mediaConfirm = root.querySelector('[data-hom-media-confirm]');
    var mediaSelectedCount = root.querySelector(
        '[data-hom-media-selected-count]'
    );


    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }


    function showNotice(message, type) {
        if (!notice) {
            return;
        }

        notice.hidden = false;
        notice.className =
            'hom-image-notice ' +
            (type === 'error'
                ? 'is-error'
                : 'is-success');

        notice.textContent = message;

        notice.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }


    function hideNotice() {
        if (notice) {
            notice.hidden = true;
        }
    }


    function uniqueIds(items) {
        return items.map(function (item) {
            return Number(item.id);
        });
    }


    function arraysEqual(a, b) {
        if (a.length !== b.length) {
            return false;
        }

        for (var i = 0; i < a.length; i++) {
            if (Number(a[i]) !== Number(b[i])) {
                return false;
            }
        }

        return true;
    }


    function isDirty() {
        var mainId = state.main
            ? Number(state.main.id)
            : 0;

        return (
            mainId !== state.initialMainId ||
            !arraysEqual(
                uniqueIds(state.gallery),
                state.initialGalleryIds
            )
        );
    }


    function hasPendingUploads() {
        return Boolean(
            state.pendingMain ||
            state.pendingGallery.length
        );
    }


    function updateFinalState() {
        var dirty = isDirty();
        var pending = hasPendingUploads();
        var busy = state.uploading > 0 || state.saving;

        finalSaveButton.disabled =
            !dirty ||
            pending ||
            busy;


        if (finalResetButton) {

            finalResetButton.disabled =
                busy ||
                (
                    !dirty &&
                    !pending &&
                    state.stagedIds.size === 0
                );
        }


        if (state.saving) {
            finalStatus.textContent =
                'در حال ذخیره تغییرات...';
            return;
        }

        if (state.uploading > 0) {
            finalStatus.textContent =
                'آپلود تصاویر در حال انجام است؛ تا پایان صبر کنید.';
            return;
        }

        if (pending) {
            finalStatus.textContent =
                'ابتدا تصاویر انتخاب‌شده را آپلود کنید.';
            return;
        }

        if (dirty) {
            finalStatus.textContent =
                'تغییرات آماده ذخیره نهایی است.';
            return;
        }

        finalStatus.textContent =
            'تغییری برای ذخیره وجود ندارد.';
    }


    function makePending(file, role, index) {
        state.sequence += 1;

        return {
            localId: 'hom-local-' + state.sequence,
            file: file,
            role: role,
            index: index,
            progress: 0,
            status: 'pending',
            message: 'آماده آپلود',
            previewUrl: URL.createObjectURL(file)
        };
    }


    function validateFile(file) {
        if (!file) {
            return 'فایل معتبر نیست.';
        }

        if (
            config.maxUploadBytes &&
            Number(file.size) > Number(config.maxUploadBytes)
        ) {
            return (
                'حجم تصویر بیشتر از ' +
                config.maxUploadLabel +
                ' است.'
            );
        }

        var validExtension =
            /\.(jpe?g|png|webp|avif|heic|heif)$/i.test(
                file.name || ''
            );

        var validMime =
            String(file.type || '').indexOf('image/') === 0;

        if (!validExtension && !validMime) {
            return 'فرمت فایل تصویر پشتیبانی نمی‌شود.';
        }

        return '';
    }


    function uploadIcon() {
        return (
            '<svg viewBox="0 0 24 24" aria-hidden="true">' +
                '<path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14v5h14v-5"/>' +
            '</svg>'
        );
    }


    function readyImageCard(item, role) {
        var alt = escapeHtml(item.alt || item.title || '');
        var image = escapeHtml(item.thumb || item.full || '');
        var title = escapeHtml(item.title || 'تصویر');
        var id = Number(item.id);

        return (
            '<article class="hom-ready-image-card">' +
                '<a href="' +
                    escapeHtml(item.full || image) +
                    '" target="_blank" rel="noopener">' +
                    '<img src="' + image + '" alt="' + alt + '">' +
                '</a>' +

                '<div class="hom-ready-image-card__meta">' +
                    '<strong>' + title + '</strong>' +
                    '<span>Media ID: ' + id + '</span>' +
                '</div>' +

                '<button type="button"' +
                    ' class="hom-remove-ready"' +
                    ' data-hom-remove-ready="' + id + '"' +
                    ' data-hom-ready-role="' + role + '">' +
                    'حذف از انتخاب' +
                '</button>' +
            '</article>'
        );
    }


    function renderMain() {
        if (!state.main) {
            mainCurrent.innerHTML =
                '<div class="hom-main-empty">' +
                    '<strong>تصویر اصلی ندارد</strong>' +
                    '<span>یک تصویر جدید انتخاب کنید.</span>' +
                '</div>';
        } else {
            mainCurrent.innerHTML =
                '<div class="hom-main-current-card">' +
                    readyImageCard(
                        state.main,
                        'main'
                    ) +
                '</div>';
        }

        renderPendingMain();
    }


    function pendingCard(item, isGallery) {
        var buttonText =
            item.status === 'uploading'
                ? 'در حال آپلود...'
                : 'آپلود تصویر';

        var disabled =
            item.status === 'uploading'
                ? ' disabled'
                : '';

        return (
            '<article class="hom-pending-card"' +
                ' data-hom-pending-id="' +
                escapeHtml(item.localId) +
                '">' +

                '<img src="' +
                    escapeHtml(item.previewUrl) +
                    '" alt="">' +

                '<div class="hom-pending-card__body">' +

                    '<div class="hom-pending-card__name">' +
                        escapeHtml(item.file.name) +
                    '</div>' +

                    '<div class="hom-progress">' +
                        '<div class="hom-progress__track">' +
                            '<div class="hom-progress__bar"' +
                                ' data-hom-progress-bar' +
                                ' style="width:' +
                                Number(item.progress || 0) +
                                '%"></div>' +
                        '</div>' +

                        '<span data-hom-progress-text>' +
                            escapeHtml(item.message) +
                        '</span>' +
                    '</div>' +

                    '<div class="hom-pending-card__actions">' +

                        '<button type="button"' +
                            ' class="hom-upload-one"' +
                            ' data-hom-upload-one="' +
                            escapeHtml(item.localId) +
                            '"' +
                            disabled +
                        '>' +
                            uploadIcon() +
                            '<span>' + buttonText + '</span>' +
                        '</button>' +

                        '<button type="button"' +
                            ' class="hom-remove-pending"' +
                            ' data-hom-remove-pending="' +
                            escapeHtml(item.localId) +
                        '">' +
                            'حذف' +
                        '</button>' +

                    '</div>' +

                '</div>' +

            '</article>'
        );
    }


    function renderPendingMain() {
        if (!state.pendingMain) {
            mainPending.innerHTML = '';
            updateFinalState();
            return;
        }

        mainPending.innerHTML =
            '<div class="hom-pending-main-title">' +
                'پیش‌نمایش تصویر انتخاب‌شده' +
            '</div>' +
            pendingCard(
                state.pendingMain,
                false
            );

        updateFinalState();
    }


    function renderGallery() {
        galleryCount.textContent =
            state.gallery.length + ' تصویر';

        if (!state.gallery.length) {
            galleryReady.innerHTML =
                '<div class="hom-gallery-empty">' +
                    'هنوز تصویری برای گالری انتخاب نشده است.' +
                '</div>';
        } else {
            galleryReady.innerHTML =
                state.gallery.map(function (item) {
                    return readyImageCard(
                        item,
                        'gallery'
                    );
                }).join('');
        }

        renderPendingGallery();
    }


    function renderPendingGallery() {
        galleryPendingToolbar.hidden =
            state.pendingGallery.length === 0;

        uploadAllButton.disabled =
            state.uploading > 0 ||
            state.pendingGallery.length === 0;

        if (!state.pendingGallery.length) {
            galleryPending.innerHTML = '';
            updateFinalState();
            return;
        }

        galleryPending.innerHTML =
            state.pendingGallery.map(function (item) {
                return pendingCard(
                    item,
                    true
                );
            }).join('');

        updateFinalState();
    }


    function addMainFile(file) {
        hideNotice();

        var error = validateFile(file);

        if (error) {
            showNotice(error, 'error');
            return;
        }

        if (
            state.pendingMain &&
            state.pendingMain.previewUrl
        ) {
            URL.revokeObjectURL(
                state.pendingMain.previewUrl
            );
        }

        state.pendingMain =
            makePending(
                file,
                'main',
                0
            );

        renderPendingMain();
    }


    function addGalleryFiles(files) {
        hideNotice();

        Array.from(files || []).forEach(function (file) {
            var error = validateFile(file);

            if (error) {
                showNotice(
                    file.name + ': ' + error,
                    'error'
                );
                return;
            }

            var index =
                state.gallery.length +
                state.pendingGallery.length +
                1;

            state.pendingGallery.push(
                makePending(
                    file,
                    'gallery',
                    index
                )
            );
        });

        renderPendingGallery();
    }


    function findPending(localId) {
        if (
            state.pendingMain &&
            state.pendingMain.localId === localId
        ) {
            return state.pendingMain;
        }

        return state.pendingGallery.find(function (item) {
            return item.localId === localId;
        }) || null;
    }


    function updateProgressDom(item) {
        var card = root.querySelector(
            '[data-hom-pending-id="' +
            CSS.escape(item.localId) +
            '"]'
        );

        if (!card) {
            return;
        }

        var bar = card.querySelector(
            '[data-hom-progress-bar]'
        );

        var text = card.querySelector(
            '[data-hom-progress-text]'
        );

        if (bar) {
            bar.style.width =
                Math.max(
                    0,
                    Math.min(
                        100,
                        Number(item.progress || 0)
                    )
                ) + '%';
        }

        if (text) {
            text.textContent = item.message;
        }
    }


    function uploadPending(item) {
        return new Promise(function (resolve, reject) {
            if (
                !item ||
                item.status === 'uploading'
            ) {
                resolve(false);
                return;
            }

            item.status = 'uploading';
            item.progress = 0;
            item.message = 'شروع آپلود...';

            state.uploading += 1;

            renderPendingMain();
            renderPendingGallery();
            updateFinalState();

            var xhr = new XMLHttpRequest();
            var form = new FormData();

            form.append(
                'action',
                'hom_upload_product_image'
            );

            form.append(
                'nonce',
                config.nonce
            );

            form.append(
                'product_id',
                config.productId
            );

            form.append(
                'role',
                item.role
            );

            form.append(
                'index',
                item.index
            );

            form.append(
                'image',
                item.file,
                item.file.name
            );


            xhr.upload.addEventListener(
                'progress',
                function (event) {
                    if (!event.lengthComputable) {
                        return;
                    }

                    item.progress =
                        Math.round(
                            event.loaded /
                            event.total *
                            100
                        );

                    item.message =
                        'در حال آپلود... ' +
                        item.progress +
                        '٪';

                    updateProgressDom(item);
                }
            );


            xhr.upload.addEventListener(
                'load',
                function () {
                    item.progress = 100;
                    item.message =
                        'آپلود کامل شد؛ در حال پردازش تصویر...';

                    updateProgressDom(item);
                }
            );


            xhr.addEventListener(
                'load',
                function () {
                    state.uploading =
                        Math.max(
                            0,
                            state.uploading - 1
                        );

                    var response = null;

                    try {
                        response =
                            JSON.parse(
                                xhr.responseText
                            );
                    } catch (error) {
                        response = null;
                    }


                    if (
                        xhr.status < 200 ||
                        xhr.status >= 300 ||
                        !response ||
                        !response.success
                    ) {
                        item.status = 'error';
                        item.message =
                            response &&
                            response.data &&
                            response.data.message
                                ? response.data.message
                                : 'آپلود تصویر انجام نشد.';

                        renderPendingMain();
                        renderPendingGallery();

                        showNotice(
                            item.message,
                            'error'
                        );

                        reject(
                            new Error(
                                item.message
                            )
                        );

                        return;
                    }


                    var attachment =
                        response.data.attachment;


                    if (
                        attachment &&
                        attachment.staged
                    ) {

                        state.stagedIds.add(
                            Number(
                                attachment.id
                            )
                        );
                    }


                    if (item.previewUrl) {
                        URL.revokeObjectURL(
                            item.previewUrl
                        );
                    }


                    if (item.role === 'main') {
                        state.main = attachment;
                        state.pendingMain = null;
                    } else {
                        var exists =
                            state.gallery.some(
                                function (galleryItem) {
                                    return (
                                        Number(galleryItem.id) ===
                                        Number(attachment.id)
                                    );
                                }
                            );

                        if (!exists) {
                            state.gallery.push(
                                attachment
                            );
                        }

                        state.pendingGallery =
                            state.pendingGallery.filter(
                                function (pendingItem) {
                                    return (
                                        pendingItem.localId !==
                                        item.localId
                                    );
                                }
                            );
                    }


                    renderMain();
                    renderGallery();

                    showNotice(
                        'تصویر آپلود شد؛ برای اتصال به محصول، ذخیره نهایی را بزنید.',
                        'success'
                    );

                    resolve(true);
                }
            );


            xhr.addEventListener(
                'error',
                function () {
                    state.uploading =
                        Math.max(
                            0,
                            state.uploading - 1
                        );

                    item.status = 'error';
                    item.message =
                        'ارتباط هنگام آپلود قطع شد.';

                    renderPendingMain();
                    renderPendingGallery();

                    showNotice(
                        item.message,
                        'error'
                    );

                    reject(
                        new Error(
                            item.message
                        )
                    );
                }
            );


            xhr.open(
                'POST',
                config.ajaxUrl,
                true
            );

            xhr.send(form);
        });
    }


    async function uploadAllGallery() {
        var queue =
            state.pendingGallery.slice();

        uploadAllButton.disabled = true;

        for (var i = 0; i < queue.length; i++) {
            try {
                await uploadPending(
                    queue[i]
                );
            } catch (error) {
                // Continue with the remaining files.
            }
        }

        renderPendingGallery();
    }


    function removePending(localId) {
        if (
            state.pendingMain &&
            state.pendingMain.localId === localId
        ) {
            URL.revokeObjectURL(
                state.pendingMain.previewUrl
            );

            state.pendingMain = null;
            renderPendingMain();
            return;
        }

        var item = findPending(localId);

        if (item && item.previewUrl) {
            URL.revokeObjectURL(
                item.previewUrl
            );
        }

        state.pendingGallery =
            state.pendingGallery.filter(
                function (pendingItem) {
                    return (
                        pendingItem.localId !==
                        localId
                    );
                }
            );

        renderPendingGallery();
    }


    function removeReady(id, role) {
        id = Number(id);

        if (role === 'main') {
            if (
                state.main &&
                Number(state.main.id) === id
            ) {
                state.main = null;
            }

            renderMain();
            updateFinalState();
            return;
        }

        state.gallery =
            state.gallery.filter(function (item) {
                return Number(item.id) !== id;
            });

        renderGallery();
        updateFinalState();
    }


    function ajaxForm(action, extra) {
        var form = new FormData();

        form.append(
            'action',
            action
        );

        form.append(
            'nonce',
            config.nonce
        );

        form.append(
            'product_id',
            config.productId
        );

        Object.keys(extra || {}).forEach(function (key) {
            form.append(
                key,
                extra[key]
            );
        });

        return form;
    }


    async function discardStagedUploads(ids) {

        ids =
            Array.from(
                new Set(
                    (ids || []).map(Number)
                )
            ).filter(Boolean);


        if (!ids.length) {
            return [];
        }


        var response =
            await fetch(
                config.ajaxUrl,
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: ajaxForm(
                        'hom_discard_staged_product_images',
                        {
                            attachment_ids:
                                JSON.stringify(
                                    ids
                                )
                        }
                    )
                }
            );


        var json =
            await response.json();


        if (
            !response.ok ||
            !json.success
        ) {

            throw new Error(
                json &&
                json.data &&
                json.data.message
                    ? json.data.message
                    : 'پاکسازی تصاویر موقت انجام نشد.'
            );
        }


        return (
            json.data.deleted_ids ||
            []
        );
    }


    function clearPendingLocalFiles() {

        if (
            state.pendingMain &&
            state.pendingMain.previewUrl
        ) {

            URL.revokeObjectURL(
                state.pendingMain.previewUrl
            );
        }


        state.pendingGallery.forEach(
            function (item) {

                if (item.previewUrl) {

                    URL.revokeObjectURL(
                        item.previewUrl
                    );
                }
            }
        );


        state.pendingMain =
            null;

        state.pendingGallery =
            [];
    }


    async function resetToInitialState() {

        if (
            state.uploading > 0 ||
            state.saving
        ) {
            return;
        }


        if (
            !isDirty() &&
            !hasPendingUploads() &&
            state.stagedIds.size === 0
        ) {
            return;
        }


        var confirmed =
            window.confirm(
                'همه تغییرات انجام‌شده در این صفحه لغو شود و تصاویر به وضعیت اولیه برگردد؟'
            );


        if (!confirmed) {
            return;
        }


        hideNotice();


        var stagedIds =
            Array.from(
                state.stagedIds
            );


        finalResetButton.disabled =
            true;

        finalResetButton.textContent =
            'در حال بازنشانی...';


        try {

            if (stagedIds.length) {

                await discardStagedUploads(
                    stagedIds
                );
            }


            state.stagedIds.clear();


            clearPendingLocalFiles();


            state.main =
                state.initialMain;

            state.gallery =
                state.initialGallery.slice();


            renderMain();
            renderGallery();
            updateFinalState();


            showNotice(
                'همه تغییرات لغو شد و تصاویر به وضعیت اولیه برگشت.',
                'success'
            );


        } catch (error) {

            /*
             * Product relations have not changed yet,
             * so restoring the visual state is still safe.
             * Keep staged IDs so cleanup can be retried.
             */

            clearPendingLocalFiles();


            state.main =
                state.initialMain;

            state.gallery =
                state.initialGallery.slice();


            renderMain();
            renderGallery();
            updateFinalState();


            showNotice(
                (
                    error.message ||
                    'بازنشانی انجام شد اما پاکسازی فایل‌های موقت کامل نشد.'
                ),
                'error'
            );


        } finally {

            finalResetButton.textContent =
                '↺ لغو تغییرات';

            updateFinalState();
        }
    }


    async function saveFinal() {
        if (
            finalSaveButton.disabled ||
            state.saving
        ) {
            return;
        }

        state.saving = true;
        hideNotice();
        updateFinalState();

        finalSaveButton.textContent =
            'در حال ذخیره...';

        try {
            var response =
                await fetch(
                    config.ajaxUrl,
                    {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: ajaxForm(
                            'hom_save_product_images',
                            {
                                main_id:
                                    state.main
                                        ? Number(state.main.id)
                                        : 0,

                                gallery_ids:
                                    JSON.stringify(
                                        uniqueIds(
                                            state.gallery
                                        )
                                    )
                            }
                        )
                    }
                );

            var json =
                await response.json();

            if (
                !response.ok ||
                !json.success
            ) {
                throw new Error(
                    json &&
                    json.data &&
                    json.data.message
                        ? json.data.message
                        : 'ذخیره نهایی انجام نشد.'
                );
            }

            state.initialMainId =
                state.main
                    ? Number(state.main.id)
                    : 0;

            state.initialGalleryIds =
                uniqueIds(
                    state.gallery
                );


            state.initialMain =
                state.main;

            state.initialGallery =
                state.gallery.slice();


            var usedIds =
                new Set(
                    (
                        state.main
                            ? [Number(state.main.id)]
                            : []
                    ).concat(
                        uniqueIds(
                            state.gallery
                        )
                    )
                );


            var unusedStagedIds =
                Array.from(
                    state.stagedIds
                ).filter(
                    function (id) {

                        return !usedIds.has(
                            Number(id)
                        );
                    }
                );


            if (unusedStagedIds.length) {

                try {

                    await discardStagedUploads(
                        unusedStagedIds
                    );

                } catch (cleanupError) {

                    console.warn(
                        'HOM staged image cleanup:',
                        cleanupError
                    );
                }
            }


            state.stagedIds.clear();


            showNotice(
                'تغییرات تصاویر محصول با موفقیت ذخیره شد.',
                'success'
            );

        } catch (error) {
            showNotice(
                error.message ||
                'خطا در ذخیره نهایی.',
                'error'
            );

        } finally {
            state.saving = false;

            finalSaveButton.textContent =
                '✓ ذخیره تغییرات';

            updateFinalState();
        }
    }


    function openMedia(mode) {
        media.mode = mode;
        media.page = 1;
        media.search = '';
        media.items.clear();
        media.selected.clear();

        mediaSearchInput.value = '';
        mediaGrid.innerHTML = '';
        mediaEmpty.hidden = true;
        mediaMore.hidden = true;

        mediaModal.hidden = false;
        document.body.classList.add(
            'hom-media-open'
        );

        updateMediaSelectionUi();
        loadMedia(false);
    }


    function closeMedia() {
        mediaModal.hidden = true;

        document.body.classList.remove(
            'hom-media-open'
        );
    }


    async function loadMedia(append) {
        if (media.loading) {
            return;
        }

        media.loading = true;

        if (!append) {
            mediaGrid.innerHTML =
                '<div class="hom-media-loading">' +
                    'در حال دریافت تصاویر...' +
                '</div>';
        }

        try {
            var response =
                await fetch(
                    config.ajaxUrl,
                    {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: ajaxForm(
                            'hom_product_media_library',
                            {
                                page: media.page,
                                search: media.search
                            }
                        )
                    }
                );

            var json =
                await response.json();

            if (
                !response.ok ||
                !json.success
            ) {
                throw new Error(
                    json &&
                    json.data &&
                    json.data.message
                        ? json.data.message
                        : 'دریافت رسانه‌ها انجام نشد.'
                );
            }

            var items =
                json.data.items || [];

            media.totalPages =
                Number(
                    json.data.total_pages || 1
                );

            if (!append) {
                mediaGrid.innerHTML = '';
                media.items.clear();
            }

            items.forEach(function (item) {
                media.items.set(
                    Number(item.id),
                    item
                );

                var button =
                    document.createElement(
                        'button'
                    );

                button.type = 'button';
                button.className =
                    'hom-media-item';

                button.dataset.mediaId =
                    String(item.id);

                button.innerHTML =
                    '<img src="' +
                        escapeHtml(
                            item.thumb ||
                            item.full ||
                            ''
                        ) +
                        '" alt="">' +

                    '<span class="hom-media-item__title">' +
                        escapeHtml(
                            item.title ||
                            item.filename ||
                            'تصویر'
                        ) +
                    '</span>' +

                    '<span class="hom-media-item__safe">' +
                        'بدون تغییر فایل' +
                    '</span>';

                mediaGrid.appendChild(
                    button
                );
            });

            mediaEmpty.hidden =
                media.items.size !== 0;

            mediaMore.hidden =
                media.page >=
                media.totalPages;

            renderMediaSelection();

        } catch (error) {
            mediaGrid.innerHTML = '';

            mediaEmpty.hidden = false;
            mediaEmpty.textContent =
                error.message ||
                'خطا در دریافت تصاویر.';

        } finally {
            media.loading = false;
        }
    }


    function renderMediaSelection() {
        mediaGrid
            .querySelectorAll(
                '[data-media-id]'
            )
            .forEach(function (button) {
                var id =
                    Number(
                        button.dataset.mediaId
                    );

                button.classList.toggle(
                    'is-selected',
                    media.selected.has(id)
                );
            });

        updateMediaSelectionUi();
    }


    function updateMediaSelectionUi() {
        var count =
            media.selected.size;

        mediaConfirm.disabled =
            count === 0;

        if (!count) {
            mediaSelectedCount.textContent =
                'تصویری انتخاب نشده است.';
        } else {
            mediaSelectedCount.textContent =
                count + ' تصویر انتخاب شده';
        }

        mediaConfirm.textContent =
            media.mode === 'main'
                ? 'انتخاب به‌عنوان تصویر اصلی'
                : 'افزودن تصاویر انتخاب‌شده';
    }


    function toggleMediaSelection(id) {
        id = Number(id);

        var item =
            media.items.get(id);

        if (!item) {
            return;
        }

        if (media.mode === 'main') {
            media.selected.clear();
            media.selected.set(
                id,
                item
            );
        } else {
            if (media.selected.has(id)) {
                media.selected.delete(id);
            } else {
                media.selected.set(
                    id,
                    item
                );
            }
        }

        renderMediaSelection();
    }


    function confirmMediaSelection() {
        var selected =
            Array.from(
                media.selected.values()
            );

        if (!selected.length) {
            return;
        }

        if (media.mode === 'main') {
            state.main =
                selected[0];

            if (
                state.pendingMain &&
                state.pendingMain.previewUrl
            ) {
                URL.revokeObjectURL(
                    state.pendingMain.previewUrl
                );
            }

            state.pendingMain = null;

            renderMain();

        } else {
            selected.forEach(function (item) {
                var id = Number(item.id);

                if (
                    state.main &&
                    Number(state.main.id) === id
                ) {
                    return;
                }

                var exists =
                    state.gallery.some(
                        function (galleryItem) {
                            return (
                                Number(galleryItem.id) ===
                                id
                            );
                        }
                    );

                if (!exists) {
                    state.gallery.push(item);
                }
            });

            renderGallery();
        }

        closeMedia();
        updateFinalState();

        showNotice(
            'تصویر از Media Library انتخاب شد و فایل اصلی آن تغییری نکرد.',
            'success'
        );
    }


    function handleDeviceInput(input, mode) {
        var files =
            input.files || [];

        if (!files.length) {
            return;
        }

        if (mode === 'main') {
            addMainFile(
                files[0]
            );
        } else {
            addGalleryFiles(
                files
            );
        }

        input.value = '';
    }


    root.addEventListener(
        'click',
        function (event) {
            var uploadButton =
                event.target.closest(
                    '[data-hom-upload-one]'
                );

            if (uploadButton) {
                var item =
                    findPending(
                        uploadButton.dataset.homUploadOne
                    );

                if (item) {
                    uploadPending(item)
                        .catch(function () {});
                }

                return;
            }


            var removePendingButton =
                event.target.closest(
                    '[data-hom-remove-pending]'
                );

            if (removePendingButton) {
                removePending(
                    removePendingButton
                        .dataset
                        .homRemovePending
                );

                return;
            }


            var removeReadyButton =
                event.target.closest(
                    '[data-hom-remove-ready]'
                );

            if (removeReadyButton) {
                removeReady(
                    removeReadyButton
                        .dataset
                        .homRemoveReady,

                    removeReadyButton
                        .dataset
                        .homReadyRole
                );

                return;
            }


            var mediaOpenButton =
                event.target.closest(
                    '[data-hom-open-media]'
                );

            if (mediaOpenButton) {
                openMedia(
                    mediaOpenButton
                        .dataset
                        .homOpenMedia
                );

                return;
            }


            var mediaItem =
                event.target.closest(
                    '[data-media-id]'
                );

            if (mediaItem) {
                toggleMediaSelection(
                    mediaItem.dataset.mediaId
                );
            }
        }
    );


    root
        .querySelectorAll(
            '[data-hom-main-device], [data-hom-main-camera]'
        )
        .forEach(function (input) {
            input.addEventListener(
                'change',
                function () {
                    handleDeviceInput(
                        input,
                        'main'
                    );
                }
            );
        });


    root
        .querySelectorAll(
            '[data-hom-gallery-device], [data-hom-gallery-camera]'
        )
        .forEach(function (input) {
            input.addEventListener(
                'change',
                function () {
                    handleDeviceInput(
                        input,
                        'gallery'
                    );
                }
            );
        });


    uploadAllButton.addEventListener(
        'click',
        uploadAllGallery
    );


    finalSaveButton.addEventListener(
        'click',
        saveFinal
    );


    if (finalResetButton) {

        finalResetButton.addEventListener(
            'click',
            resetToInitialState
        );
    }


    root
        .querySelectorAll(
            '[data-hom-media-close]'
        )
        .forEach(function (button) {
            button.addEventListener(
                'click',
                closeMedia
            );
        });


    root
        .querySelector(
            '[data-hom-media-search-button]'
        )
        .addEventListener(
            'click',
            function () {
                media.search =
                    mediaSearchInput.value.trim();

                media.page = 1;

                loadMedia(false);
            }
        );


    mediaSearchInput.addEventListener(
        'keydown',
        function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();

            media.search =
                mediaSearchInput.value.trim();

            media.page = 1;

            loadMedia(false);
        }
    );


    mediaMore.addEventListener(
        'click',
        function () {
            if (
                media.page >=
                media.totalPages
            ) {
                return;
            }

            media.page += 1;

            loadMedia(true);
        }
    );


    mediaConfirm.addEventListener(
        'click',
        confirmMediaSelection
    );


    root
        .querySelectorAll(
            '[data-hom-back]'
        )
        .forEach(function (link) {
            link.addEventListener(
                'click',
                function (event) {
                    if (
                        !isDirty() &&
                        !hasPendingUploads()
                    ) {
                        return;
                    }

                    if (
                        !window.confirm(
                            'تغییرات ذخیره‌نشده دارید. از صفحه خارج شوید؟'
                        )
                    ) {
                        event.preventDefault();
                    }
                }
            );
        });


    window.addEventListener(
        'beforeunload',
        function (event) {
            if (
                state.saving ||
                (
                    !isDirty() &&
                    !hasPendingUploads()
                )
            ) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        }
    );


    renderMain();
    renderGallery();
    updateFinalState();
})();
