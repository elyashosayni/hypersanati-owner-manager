(function () {
    'use strict';

    function clamp(value, min, max) {
        return Math.max(
            min,
            Math.min(max, value)
        );
    }


    function distance(a, b) {
        var dx = b.x - a.x;
        var dy = b.y - a.y;

        return Math.sqrt(
            dx * dx + dy * dy
        );
    }


    function angle(a, b) {
        return Math.atan2(
            b.y - a.y,
            b.x - a.x
        );
    }


    function normalizeDegrees(value) {
        var degree = Number(value || 0);

        while (degree > 180) {
            degree -= 360;
        }

        while (degree < -180) {
            degree += 360;
        }

        return degree;
    }


    function outputFilename(name) {
        var base = String(
            name || 'product-image'
        )
            .replace(/\.[^.]+$/, '')
            .replace(/[^a-zA-Z0-9_-]+/g, '-')
            .replace(/^-+|-+$/g, '');

        if (!base) {
            base = 'product-image';
        }

        return (
            base +
            '-square-edited.jpg'
        );
    }


    function createEditor(root, options) {
        options = options || {};

        var modal = root.querySelector(
            '[data-hom-editor-modal]'
        );

        if (!modal) {
            return null;
        }


        var canvas = modal.querySelector(
            '[data-hom-editor-canvas]'
        );

        var context = canvas.getContext(
            '2d',
            {
                alpha: false
            }
        );

        var zoomRange = modal.querySelector(
            '[data-hom-editor-zoom]'
        );

        var zoomOutput = modal.querySelector(
            '[data-hom-editor-zoom-output]'
        );

        var rotationRange = modal.querySelector(
            '[data-hom-editor-rotation]'
        );

        var rotationOutput = modal.querySelector(
            '[data-hom-editor-rotation-output]'
        );

        var status = modal.querySelector(
            '[data-hom-editor-status]'
        );

        var confirmButton = modal.querySelector(
            '[data-hom-editor-confirm]'
        );

        var cancelButtons = modal.querySelectorAll(
            '[data-hom-editor-cancel]'
        );

        var fitButton = modal.querySelector(
            '[data-hom-editor-fit]'
        );

        var fillButton = modal.querySelector(
            '[data-hom-editor-fill]'
        );

        var zeroRotationButton = modal.querySelector(
            '[data-hom-editor-zero-rotation]'
        );

        var resetButton = modal.querySelector(
            '[data-hom-editor-reset]'
        );


        var previewSize =
            Number(
                canvas.width ||
                720
            );

        var outputSize =
            Number(
                options.outputSize ||
                1800
            );


        var current = null;

        var image = null;

        var sourceObjectUrl = '';

        var resolveOpen = null;

        var pointers = new Map();

        var dragState = null;

        var gestureState = null;


        var transform = {
            x: previewSize / 2,
            y: previewSize / 2,
            scale: 1,
            rotation: 0,
            fitScale: 1,
            coverScale: 1
        };


        function cleanupObjectUrl() {
            if (sourceObjectUrl) {
                URL.revokeObjectURL(
                    sourceObjectUrl
                );

                sourceObjectUrl = '';
            }
        }


        function closeModal(result) {
            modal.hidden = true;

            document.body.classList.remove(
                'hom-image-editor-open'
            );

            pointers.clear();

            dragState = null;
            gestureState = null;

            cleanupObjectUrl();

            image = null;
            current = null;

            var resolver = resolveOpen;

            resolveOpen = null;

            if (resolver) {
                resolver(result);
            }
        }


        function updateOutputs() {
            var zoom =
                transform.fitScale > 0
                    ? Math.round(
                        transform.scale /
                        transform.fitScale *
                        100
                    )
                    : 100;

            zoom = clamp(
                zoom,
                25,
                400
            );

            zoomRange.value =
                String(zoom);

            zoomOutput.textContent =
                zoom + '٪';


            transform.rotation =
                normalizeDegrees(
                    transform.rotation
                );

            rotationRange.value =
                String(
                    transform.rotation
                );

            rotationOutput.textContent =
                (
                    Math.round(
                        transform.rotation *
                        10
                    ) / 10
                ) +
                '°';
        }


        function draw() {
            context.save();

            context.fillStyle = '#ffffff';

            context.fillRect(
                0,
                0,
                previewSize,
                previewSize
            );

            if (image) {
                context.imageSmoothingEnabled =
                    true;

                context.imageSmoothingQuality =
                    'high';

                context.translate(
                    transform.x,
                    transform.y
                );

                context.rotate(
                    transform.rotation *
                    Math.PI /
                    180
                );

                context.scale(
                    transform.scale,
                    transform.scale
                );

                context.drawImage(
                    image,
                    -image.naturalWidth / 2,
                    -image.naturalHeight / 2
                );
            }

            context.restore();

            updateOutputs();
        }


        function centerImage() {
            transform.x =
                previewSize / 2;

            transform.y =
                previewSize / 2;
        }


        function fitWithMargin() {
            if (!image) {
                return;
            }

            centerImage();

            /*
             * 88% of the square is used intentionally,
             * leaving a clean white product margin.
             */
            transform.scale =
                transform.fitScale *
                0.88;

            draw();
        }


        function fillSquare() {
            if (!image) {
                return;
            }

            centerImage();

            transform.scale =
                transform.coverScale;

            draw();
        }


        function resetEditor() {
            if (!image) {
                return;
            }

            transform.rotation = 0;

            /*
             * Default view keeps the entire product visible
             * with a modest white breathing space.
             */
            fitWithMargin();
        }


        function setRotation(value) {
            transform.rotation =
                normalizeDegrees(
                    Number(value || 0)
                );

            draw();
        }


        function setZoomPercent(value) {
            if (!image) {
                return;
            }

            var percent =
                clamp(
                    Number(value || 100),
                    25,
                    400
                );

            transform.scale =
                transform.fitScale *
                percent /
                100;

            draw();
        }


        function pointerPoint(event) {
            var rect =
                canvas.getBoundingClientRect();

            var scaleX =
                previewSize /
                rect.width;

            var scaleY =
                previewSize /
                rect.height;

            return {
                x:
                    (
                        event.clientX -
                        rect.left
                    ) *
                    scaleX,

                y:
                    (
                        event.clientY -
                        rect.top
                    ) *
                    scaleY
            };
        }


        function startTwoPointerGesture() {
            if (
                pointers.size <
                2
            ) {
                gestureState = null;
                return;
            }

            var points =
                Array.from(
                    pointers.values()
                );

            var a = points[0];
            var b = points[1];

            gestureState = {
                distance:
                    Math.max(
                        1,
                        distance(a, b)
                    ),

                angle:
                    angle(a, b),

                scale:
                    transform.scale,

                rotation:
                    transform.rotation,

                midX:
                    (a.x + b.x) / 2,

                midY:
                    (a.y + b.y) / 2,

                imageX:
                    transform.x,

                imageY:
                    transform.y
            };
        }


        canvas.addEventListener(
            'pointerdown',
            function (event) {
                if (!image) {
                    return;
                }

                event.preventDefault();

                canvas.setPointerCapture(
                    event.pointerId
                );

                var point =
                    pointerPoint(event);

                pointers.set(
                    event.pointerId,
                    point
                );


                if (
                    pointers.size ===
                    1
                ) {
                    dragState = {
                        pointerId:
                            event.pointerId,

                        startX:
                            point.x,

                        startY:
                            point.y,

                        imageX:
                            transform.x,

                        imageY:
                            transform.y,

                        rotation:
                            transform.rotation,

                        angle:
                            Math.atan2(
                                point.y -
                                transform.y,

                                point.x -
                                transform.x
                            ),

                        rotate:
                            event.pointerType ===
                            'mouse' &&
                            event.shiftKey
                    };
                }


                if (
                    pointers.size >=
                    2
                ) {
                    startTwoPointerGesture();
                }
            }
        );


        canvas.addEventListener(
            'pointermove',
            function (event) {
                if (
                    !pointers.has(
                        event.pointerId
                    )
                ) {
                    return;
                }

                event.preventDefault();

                var point =
                    pointerPoint(event);

                pointers.set(
                    event.pointerId,
                    point
                );


                if (
                    pointers.size >=
                    2
                ) {
                    if (!gestureState) {
                        startTwoPointerGesture();
                    }

                    var points =
                        Array.from(
                            pointers.values()
                        );

                    var a = points[0];
                    var b = points[1];

                    var newDistance =
                        Math.max(
                            1,
                            distance(a, b)
                        );

                    var newAngle =
                        angle(a, b);

                    var midX =
                        (a.x + b.x) / 2;

                    var midY =
                        (a.y + b.y) / 2;


                    transform.scale =
                        clamp(
                            gestureState.scale *
                            (
                                newDistance /
                                gestureState.distance
                            ),
                            transform.fitScale *
                            0.25,
                            transform.fitScale *
                            4
                        );


                    transform.rotation =
                        normalizeDegrees(
                            gestureState.rotation +
                            (
                                newAngle -
                                gestureState.angle
                            ) *
                            180 /
                            Math.PI
                        );


                    transform.x =
                        gestureState.imageX +
                        (
                            midX -
                            gestureState.midX
                        );

                    transform.y =
                        gestureState.imageY +
                        (
                            midY -
                            gestureState.midY
                        );

                    draw();

                    return;
                }


                if (
                    !dragState ||
                    dragState.pointerId !==
                    event.pointerId
                ) {
                    return;
                }


                if (
                    dragState.rotate
                ) {
                    var currentAngle =
                        Math.atan2(
                            point.y -
                            transform.y,

                            point.x -
                            transform.x
                        );

                    transform.rotation =
                        normalizeDegrees(
                            dragState.rotation +
                            (
                                currentAngle -
                                dragState.angle
                            ) *
                            180 /
                            Math.PI
                        );
                } else {
                    transform.x =
                        dragState.imageX +
                        (
                            point.x -
                            dragState.startX
                        );

                    transform.y =
                        dragState.imageY +
                        (
                            point.y -
                            dragState.startY
                        );
                }

                draw();
            }
        );


        function releasePointer(event) {
            pointers.delete(
                event.pointerId
            );

            gestureState = null;

            if (
                pointers.size ===
                1
            ) {
                var entry =
                    Array.from(
                        pointers.entries()
                    )[0];

                dragState = {
                    pointerId:
                        entry[0],

                    startX:
                        entry[1].x,

                    startY:
                        entry[1].y,

                    imageX:
                        transform.x,

                    imageY:
                        transform.y,

                    rotation:
                        transform.rotation,

                    angle:
                        Math.atan2(
                            entry[1].y -
                            transform.y,

                            entry[1].x -
                            transform.x
                        ),

                    rotate:
                        false
                };
            } else {
                dragState = null;
            }
        }


        canvas.addEventListener(
            'pointerup',
            releasePointer
        );

        canvas.addEventListener(
            'pointercancel',
            releasePointer
        );


        canvas.addEventListener(
            'wheel',
            function (event) {
                if (!image) {
                    return;
                }

                event.preventDefault();

                var multiplier =
                    event.deltaY < 0
                        ? 1.06
                        : 0.94;

                transform.scale =
                    clamp(
                        transform.scale *
                        multiplier,
                        transform.fitScale *
                        0.25,
                        transform.fitScale *
                        4
                    );

                draw();
            },
            {
                passive: false
            }
        );


        zoomRange.addEventListener(
            'input',
            function () {
                setZoomPercent(
                    zoomRange.value
                );
            }
        );


        rotationRange.addEventListener(
            'input',
            function () {
                setRotation(
                    rotationRange.value
                );
            }
        );


        zeroRotationButton.addEventListener(
            'click',
            function () {
                setRotation(0);
            }
        );


        fitButton.addEventListener(
            'click',
            fitWithMargin
        );


        fillButton.addEventListener(
            'click',
            fillSquare
        );


        resetButton.addEventListener(
            'click',
            resetEditor
        );


        cancelButtons.forEach(
            function (button) {
                button.addEventListener(
                    'click',
                    function () {
                        closeModal(null);
                    }
                );
            }
        );


        function exportEditedFile() {
            if (!image || !current) {
                return;
            }

            confirmButton.disabled =
                true;

            confirmButton.textContent =
                'در حال آماده‌سازی...';


            var output =
                document.createElement(
                    'canvas'
                );

            output.width =
                outputSize;

            output.height =
                outputSize;

            var outputContext =
                output.getContext(
                    '2d',
                    {
                        alpha: false
                    }
                );


            outputContext.fillStyle =
                '#ffffff';

            outputContext.fillRect(
                0,
                0,
                outputSize,
                outputSize
            );

            outputContext.imageSmoothingEnabled =
                true;

            outputContext.imageSmoothingQuality =
                'high';


            var factor =
                outputSize /
                previewSize;


            outputContext.save();

            outputContext.translate(
                transform.x *
                factor,

                transform.y *
                factor
            );

            outputContext.rotate(
                transform.rotation *
                Math.PI /
                180
            );

            outputContext.scale(
                transform.scale *
                factor,

                transform.scale *
                factor
            );

            outputContext.drawImage(
                image,
                -image.naturalWidth / 2,
                -image.naturalHeight / 2
            );

            outputContext.restore();


            output.toBlob(
                function (blob) {
                    confirmButton.disabled =
                        false;

                    confirmButton.textContent =
                        'تأیید و آماده‌سازی تصویر';


                    if (!blob) {
                        status.textContent =
                            'ساخت تصویر نهایی انجام نشد.';

                        return;
                    }


                    var file =
                        new File(
                            [blob],
                            outputFilename(
                                current.name
                            ),
                            {
                                type:
                                    'image/jpeg',

                                lastModified:
                                    Date.now()
                            }
                        );


                    closeModal(
                        {
                            file: file,

                            rotation:
                                transform.rotation,

                            outputSize:
                                outputSize
                        }
                    );
                },
                'image/jpeg',
                0.95
            );
        }


        confirmButton.addEventListener(
            'click',
            exportEditedFile
        );


        document.addEventListener(
            'keydown',
            function (event) {
                if (
                    modal.hidden ||
                    event.key !==
                    'Escape'
                ) {
                    return;
                }

                closeModal(null);
            }
        );


        function loadSource(source) {
            return new Promise(
                function (resolve, reject) {
                    if (
                        source.file instanceof
                        Blob
                    ) {
                        sourceObjectUrl =
                            URL.createObjectURL(
                                source.file
                            );

                        resolve(
                            sourceObjectUrl
                        );

                        return;
                    }


                    if (!source.url) {
                        reject(
                            new Error(
                                'منبع تصویر معتبر نیست.'
                            )
                        );

                        return;
                    }


                    fetch(
                        source.url,
                        {
                            credentials:
                                'same-origin'
                        }
                    )
                        .then(
                            function (response) {
                                if (!response.ok) {
                                    throw new Error(
                                        'دریافت تصویر از رسانه‌های سایت انجام نشد.'
                                    );
                                }

                                return response.blob();
                            }
                        )
                        .then(
                            function (blob) {
                                sourceObjectUrl =
                                    URL.createObjectURL(
                                        blob
                                    );

                                resolve(
                                    sourceObjectUrl
                                );
                            }
                        )
                        .catch(reject);
                }
            );
        }


        function open(source) {
            if (
                resolveOpen
            ) {
                return Promise.reject(
                    new Error(
                        'ویرایشگر در حال استفاده است.'
                    )
                );
            }


            current = source || {};

            modal.hidden =
                false;

            document.body.classList.add(
                'hom-image-editor-open'
            );

            status.textContent =
                'در حال آماده‌سازی تصویر...';

            confirmButton.disabled =
                true;


            context.fillStyle =
                '#ffffff';

            context.fillRect(
                0,
                0,
                previewSize,
                previewSize
            );


            return new Promise(
                function (resolve) {
                    resolveOpen =
                        resolve;


                    loadSource(current)
                        .then(
                            function (url) {
                                return new Promise(
                                    function (
                                        imageResolve,
                                        imageReject
                                    ) {
                                        var loaded =
                                            new Image();

                                        loaded.onload =
                                            function () {
                                                imageResolve(
                                                    loaded
                                                );
                                            };

                                        loaded.onerror =
                                            function () {
                                                imageReject(
                                                    new Error(
                                                        'این فرمت تصویر در مرورگر قابل ویرایش نیست.'
                                                    )
                                                );
                                            };

                                        loaded.src =
                                            url;
                                    }
                                );
                            }
                        )
                        .then(
                            function (loadedImage) {
                                image =
                                    loadedImage;


                                transform.fitScale =
                                    Math.min(
                                        previewSize /
                                        image.naturalWidth,

                                        previewSize /
                                        image.naturalHeight
                                    );


                                transform.coverScale =
                                    Math.max(
                                        previewSize /
                                        image.naturalWidth,

                                        previewSize /
                                        image.naturalHeight
                                    );


                                transform.rotation =
                                    0;

                                fitWithMargin();


                                status.textContent =
                                    'تصویر را تنظیم کنید و سپس تأیید را بزنید.';

                                confirmButton.disabled =
                                    false;
                            }
                        )
                        .catch(
                            function (error) {
                                status.textContent =
                                    error.message ||
                                    'باز کردن تصویر انجام نشد.';

                                confirmButton.disabled =
                                    true;
                            }
                        );
                }
            );
        }


        return {
            open: open
        };
    }


    window.HOMImageEditor = {
        create: createEditor
    };
})();


/* HOM IMAGE EDITOR RESPONSIVE UI BRIDGE */
(function () {
    'use strict';

    var modal =
        document.querySelector(
            '[data-hom-editor-modal]'
        );

    if (!modal) {
        return;
    }


    var mobileZoom =
        modal.querySelector(
            '[data-hom-editor-zoom]'
        );

    var desktopZoom =
        modal.querySelector(
            '[data-hom-editor-zoom-desktop]'
        );

    var mobileZoomOutput =
        modal.querySelector(
            '[data-hom-editor-zoom-output]'
        );

    var desktopZoomOutput =
        modal.querySelector(
            '[data-hom-editor-zoom-output-desktop]'
        );


    var mobileRotation =
        modal.querySelector(
            '[data-hom-editor-rotation]'
        );

    var desktopRotation =
        modal.querySelector(
            '[data-hom-editor-rotation-desktop]'
        );

    var mobileRotationOutput =
        modal.querySelector(
            '[data-hom-editor-rotation-output]'
        );

    var desktopRotationOutput =
        modal.querySelector(
            '[data-hom-editor-rotation-output-desktop]'
        );


    var panels =
        modal.querySelectorAll(
            '[data-hom-editor-panel]'
        );

    var tools =
        modal.querySelectorAll(
            '[data-hom-editor-tool]'
        );


    function closePanels() {

        panels.forEach(
            function (panel) {
                panel.hidden = true;
            }
        );

        tools.forEach(
            function (button) {
                button.classList.remove(
                    'is-active'
                );
            }
        );
    }


    function togglePanel(name, button) {

        var panel =
            modal.querySelector(
                '[data-hom-editor-panel="' +
                name +
                '"]'
            );

        if (!panel) {
            return;
        }

        var open =
            panel.hidden;

        closePanels();

        if (open) {

            panel.hidden = false;

            button.classList.add(
                'is-active'
            );
        }
    }


    tools.forEach(
        function (button) {

            button.addEventListener(
                'click',
                function () {

                    togglePanel(
                        button.dataset.homEditorTool,
                        button
                    );
                }
            );
        }
    );


    function forwardInput(target, value) {

        if (!target) {
            return;
        }

        target.value = value;

        target.dispatchEvent(
            new Event(
                'input',
                {
                    bubbles: true
                }
            )
        );
    }


    if (
        desktopZoom &&
        mobileZoom
    ) {

        desktopZoom.addEventListener(
            'input',
            function () {

                forwardInput(
                    mobileZoom,
                    desktopZoom.value
                );
            }
        );
    }


    if (
        desktopRotation &&
        mobileRotation
    ) {

        desktopRotation.addEventListener(
            'input',
            function () {

                forwardInput(
                    mobileRotation,
                    desktopRotation.value
                );
            }
        );
    }


    function syncZoom() {

        if (
            desktopZoom &&
            mobileZoom
        ) {
            desktopZoom.value =
                mobileZoom.value;
        }

        if (
            desktopZoomOutput &&
            mobileZoomOutput
        ) {
            desktopZoomOutput.textContent =
                mobileZoomOutput.textContent;
        }
    }


    function syncRotation() {

        if (
            desktopRotation &&
            mobileRotation
        ) {
            desktopRotation.value =
                mobileRotation.value;
        }

        if (
            desktopRotationOutput &&
            mobileRotationOutput
        ) {
            desktopRotationOutput.textContent =
                mobileRotationOutput.textContent;
        }
    }


    if (mobileZoomOutput) {

        new MutationObserver(
            syncZoom
        ).observe(
            mobileZoomOutput,
            {
                childList: true,
                characterData: true,
                subtree: true
            }
        );
    }


    if (mobileRotationOutput) {

        new MutationObserver(
            syncRotation
        ).observe(
            mobileRotationOutput,
            {
                childList: true,
                characterData: true,
                subtree: true
            }
        );
    }


    /*
     * On mobile, slider disappears after finger release.
     */
    if (mobileZoom) {

        mobileZoom.addEventListener(
            'change',
            closePanels
        );
    }


    if (mobileRotation) {

        mobileRotation.addEventListener(
            'change',
            closePanels
        );
    }


    /*
     * Existing core editor controls the first
     * fit/fill/reset buttons. Desktop buttons forward
     * to those same proven actions.
     */
    var desktopControls =
        modal.querySelector(
            '.hom-editor-desktop-controls'
        );


    if (desktopControls) {

        [
            'zero-rotation',
            'fit',
            'fill',
            'reset'
        ].forEach(
            function (name) {

                var desktopButton =
                    desktopControls.querySelector(
                        '[data-hom-editor-' +
                        name +
                        ']'
                    );

                var firstButton =
                    modal.querySelector(
                        '[data-hom-editor-' +
                        name +
                        ']'
                    );

                if (
                    desktopButton &&
                    firstButton &&
                    desktopButton !== firstButton
                ) {

                    desktopButton.addEventListener(
                        'click',
                        function () {
                            firstButton.click();
                        }
                    );
                }
            }
        );
    }


    modal.querySelectorAll(
        '[data-hom-editor-fit],' +
        '[data-hom-editor-fill],' +
        '[data-hom-editor-reset],' +
        '[data-hom-editor-cancel]'
    ).forEach(
        function (button) {

            button.addEventListener(
                'click',
                closePanels
            );
        }
    );


    syncZoom();
    syncRotation();

})();


/* HOM WATERMARK EDITOR PREVIEW */
(function () {
    'use strict';

    var config =
        window.HOMProductImages || {};

    if (
        !config.watermarkReady ||
        !config.watermarkUrl
    ) {
        return;
    }

    var modal =
        document.querySelector(
            '[data-hom-editor-modal]'
        );

    if (!modal) {
        return;
    }

    var wrap =
        modal.querySelector(
            '.hom-editor-canvas-wrap'
        );

    if (!wrap) {
        return;
    }

    if (
        wrap.querySelector(
            '.hom-editor-watermark-preview'
        )
    ) {
        return;
    }

    var image =
        document.createElement('img');

    image.className =
        'hom-editor-watermark-preview';

    image.src =
        config.watermarkUrl;

    image.alt =
        '';

    image.setAttribute(
        'aria-hidden',
        'true'
    );

    wrap.appendChild(image);

})();
