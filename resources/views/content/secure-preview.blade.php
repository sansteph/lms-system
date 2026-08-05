<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#111827">
    <title>{{ $content->content_title }} Preview</title>
    <style>
        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            background: #111827;
            font-family: Arial, Helvetica, sans-serif;
            overflow: hidden;
        }

        .preview-shell {
            display: flex;
            flex-direction: column;
            width: 100vw;
            height: 100vh;
            height: 100dvh;
            background: #111827;
            overflow: hidden;
        }

        .preview-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            min-height: 74px;
            padding: 12px 24px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.22);
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
            z-index: 2;
        }

        .preview-heading {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .preview-heading-main {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            flex-wrap: wrap;
        }

        .preview-title-wrap {
            min-width: 0;
        }

        .preview-title {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 18px;
            line-height: 1.2;
            font-weight: 700;
            color: #0f172a;
        }

        .preview-subtitle {
            margin-top: 4px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.02em;
            color: #64748b;
            text-transform: uppercase;
        }

        .preview-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .preview-pill,
        .preview-toolbar-btn {
            display: inline-flex;
            align-items: center;
            min-height: 36px;
            padding: 0 12px;
            border: 1px solid #dbeafe;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
        }

        .preview-toolbar-btn {
            gap: 8px;
            cursor: pointer;
            border: 1px solid rgba(37, 99, 235, 0.16);
            background: linear-gradient(135deg, #f8fbff, #e8f1ff);
            box-shadow: 0 8px 22px rgba(37, 99, 235, 0.10);
            transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
        }

        .preview-toolbar-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(37, 99, 235, 0.16);
        }

        .preview-toolbar-btn:active {
            transform: translateY(0);
        }

        .preview-toolbar-btn.is-fullscreen-only,
        .preview-pill.is-fullscreen-only {
            display: none;
        }

        .preview-warning {
            color: #b91c1c;
            font-size: 12px;
            font-weight: 700;
        }

        .android-preview-fallback {
            display: none;
            padding: 16px;
            text-align: center;
            color: #334155;
            background: #f8fafc;
        }

        .mobile-pdf-viewer {
            display: none;
            width: 100%;
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            background: #374151;
            padding: 12px;
            box-sizing: border-box;
        }

        .mobile-pdf-pages {
            display: block;
            width: 100%;
            max-width: 980px;
            margin: 0 auto;
            transform-origin: top center;
            transition: transform 0.18s ease, width 0.18s ease;
        }

        .mobile-pdf-page {
            display: block;
            width: 100%;
            height: auto;
            margin: 0 auto 14px;
            background: #ffffff;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.28);
        }

        .mobile-pdf-status {
            margin: 18px auto;
            max-width: 520px;
            border-radius: 12px;
            padding: 18px;
            color: #0f172a;
            background: #ffffff;
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            box-shadow: 0 12px 34px rgba(0, 0, 0, 0.22);
        }

        .preview-frame {
            display: none;
        }

        .mobile-pdf-viewer {
            display: block;
        }

        .protected-preview-content {
            overflow: hidden;
        }

        .protected-preview-mouse-shield {
            pointer-events: none;
        }

        .android-preview-fallback {
            display: none;
        }


        .preview-frame-wrap {
            flex: 1;
            min-height: 0;
            padding: 18px 20px 22px;
            background:
                radial-gradient(circle at top, rgba(59, 130, 246, 0.12), transparent 30%),
                #111827;
            overflow: hidden;
        }

        .preview-stage {
            position: relative;
            width: 100%;
            height: 100%;
            max-width: none;
            margin: 0 auto;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.26);
            border-radius: 10px;
            background: #1f2937;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.34);
        }

        .fullscreen-gate {
            position: fixed;
            top: 74px;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            display: none;
            align-items: flex-start;
            justify-content: flex-start;
            padding: 16px 20px;
            background: linear-gradient(180deg, rgba(17, 24, 39, 0.18), rgba(17, 24, 39, 0.58));
            backdrop-filter: blur(10px);
        }

        .fullscreen-gate-note {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            max-width: min(760px, 100%);
            border-radius: 14px;
            padding: 12px 14px;
            border: 1px solid rgba(59, 130, 246, 0.28);
            background: rgba(255, 255, 255, 0.9);
            color: #0f172a;
            box-shadow: 0 16px 45px rgba(15, 23, 42, 0.18);
            font-size: 14px;
            line-height: 1.5;
            font-weight: 600;
        }

        .fullscreen-gate-note .btn {
            min-height: 36px;
            border-radius: 999px;
        }

        .preview-stage::before {
            content: "";
            display: block;
            height: 8px;
            background: linear-gradient(90deg, #2563eb, #14b8a6);
        }

        .preview-frame-holder {
            position: relative;
            height: calc(100% - 8px);
            background: #ffffff;
            overflow: hidden;
        }

        .preview-frame {
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
            background: #ffffff;
        }

        .preview-unavailable {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 34px;
            text-align: center;
            color: #334155;
            background: #f8fafc;
        }

        .preview-unavailable-card {
            max-width: 520px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 28px;
            background: #ffffff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.1);
        }

        .preview-unavailable-title {
            margin-bottom: 10px;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .preview-shell:fullscreen .preview-toolbar {
            min-height: 58px;
            padding: 10px 18px;
            background: #0f172a;
            border-color: rgba(148, 163, 184, 0.24);
            box-shadow: none;
        }

        .preview-shell:fullscreen .preview-title {
            color: #ffffff;
        }

        .preview-shell:fullscreen .preview-subtitle {
            color: #94a3b8;
        }

        .preview-shell:fullscreen .preview-pill {
            border-color: rgba(96, 165, 250, 0.32);
            background: rgba(37, 99, 235, 0.14);
            color: #bfdbfe;
        }

        .preview-shell:fullscreen .preview-toolbar-btn {
            border-color: rgba(96, 165, 250, 0.32);
            background: rgba(37, 99, 235, 0.14);
            color: #dbeafe;
            box-shadow: none;
        }

        .preview-shell:fullscreen .desktop-fullscreen-action {
            display: none;
        }

        .preview-shell:fullscreen .preview-toolbar-btn.is-fullscreen-only {
            display: inline-flex;
        }

        .preview-shell:fullscreen .preview-pill.is-fullscreen-only {
            display: inline-flex;
        }

        .preview-shell:fullscreen .preview-frame-wrap {
            padding: 14px 16px 18px;
        }

        .preview-shell:fullscreen .preview-stage {
            max-width: none;
            border-radius: 10px;
        }

        .preview-shell.is-desktop-gated .desktop-fullscreen-action {
            display: inline-flex;
        }

        .preview-shell.is-desktop-gated .preview-stage {
            opacity: 0;
            pointer-events: none;
        }

        .preview-shell.is-desktop-gated .fullscreen-gate {
            display: flex;
        }

        @media (max-width: 768px) {
            .fullscreen-gate {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .preview-toolbar {
                align-items: flex-start;
                flex-direction: column;
                min-height: auto;
                padding: 14px;
            }

            .preview-heading {
                width: 100%;
            }

            .preview-actions {
                width: 100%;
                justify-content: space-between;
            }

            .preview-heading-main {
                width: 100%;
            }

            .preview-frame-wrap {
                padding: 12px;
            }

            .preview-stage {
                border-radius: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="preview-shell" id="previewShell">
        <div class="preview-toolbar">
            <div class="preview-heading">
                <div class="preview-heading-main">
                    <div class="preview-title-wrap">
                        <div class="preview-title">{{ $content->content_title }}</div>
                        <div class="preview-subtitle">Secure Preview</div>
                    </div>
                    <button type="button" class="preview-toolbar-btn desktop-fullscreen-action" id="desktopFullscreenAction">
                        Enter fullscreen
                    </button>
                </div>
            </div>

            <div class="preview-actions">
                <span class="preview-warning">Screenshots and recording are not allowed</span>
                <span class="preview-pill">View only</span>
                <button type="button" class="preview-toolbar-btn is-fullscreen-only" id="previewBackButton">
                    Back to preview
                </button>
                <button type="button" class="preview-toolbar-btn is-fullscreen-only" id="zoomOutButton" aria-label="Zoom out">
                    -
                </button>
                <span class="preview-pill is-fullscreen-only">Zoom</span>
                <button type="button" class="preview-toolbar-btn is-fullscreen-only" id="zoomInButton" aria-label="Zoom in">
                    +
                </button>
            </div>
        </div>

        <div class="preview-frame-wrap">
            <div class="preview-stage protected-preview-surface"
                 data-watermark="InnovatEdge&#10;View Only"
                 data-preview-scope="content-{{ $content->id }}">
                <div class="preview-frame-holder protected-preview-content">
                    @if(!empty($previewUnavailableMessage))
                        <div class="preview-unavailable">
                            <div class="preview-unavailable-card">
                                <div class="preview-unavailable-title">Preview unavailable</div>
                                <div>{{ $previewUnavailableMessage }}</div>
                            </div>
                        </div>
                    @else
                        <div class="mobile-pdf-viewer" id="mobilePdfViewer">
                            <div class="mobile-pdf-status" id="mobilePdfStatus">
                                Preparing secure android preview...
                            </div>
                            <div class="mobile-pdf-pages" id="mobilePdfPages"></div>
                        </div>
                        <div class="android-preview-fallback" id="androidPreviewFallback">
                            Secure mobile preview could not load. Please refresh this page.
                        </div>
                        <div class="protected-preview-mouse-shield"
                             aria-hidden="true">
                        </div>
                    @endif
                </div>
                <div class="fullscreen-gate" id="fullscreenGate" aria-hidden="true">
                    <div class="fullscreen-gate-note">
                        Desktop previews are locked until fullscreen is enabled.
                        <button type="button" class="preview-toolbar-btn" id="enterFullscreenBtn">
                            Enter fullscreen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('content.preview-protection')
    <script>
        window.addEventListener('pagehide', function () {
            if (!navigator.sendBeacon) {
                return;
            }

            const payload = new FormData();
            payload.append('_token', '{{ csrf_token() }}');
            navigator.sendBeacon('{{ route('activity-monitoring.end-current') }}', payload);
        });
    </script>
    <script>
        (function () {
            function isDesktopPreview() {
                return window.matchMedia('(min-width: 769px) and (pointer: fine)').matches;
            }

            function toggleDesktopGate(active) {
                var shell = document.getElementById('previewShell');
                var gate = document.getElementById('fullscreenGate');

                if (!shell || !gate) {
                    return;
                }

                shell.classList.toggle('is-desktop-gated', !!active);
                gate.setAttribute('aria-hidden', active ? 'false' : 'true');
            }

            function requestFullscreenShell() {
                var shell = document.getElementById('previewShell');

                if (!shell || !shell.requestFullscreen) {
                    if (shell && shell.webkitRequestFullscreen) {
                        return shell.webkitRequestFullscreen();
                    }

                    return Promise.reject(new Error('Fullscreen is not supported'));
                }

                return shell.requestFullscreen();
            }

            function exitFullscreenShell() {
                if (document.fullscreenElement && document.exitFullscreen) {
                    return document.exitFullscreen();
                }

                if (document.webkitFullscreenElement && document.webkitExitFullscreen) {
                    return document.webkitExitFullscreen();
                }

                return Promise.resolve();
            }

            function isFullscreenActive() {
                return !!(document.fullscreenElement || document.webkitFullscreenElement);
            }

            function openFullscreenAndReveal() {
                toggleDesktopGate(false);

                return Promise.resolve(requestFullscreenShell()).catch(function () {
                    toggleDesktopGate(true);
                    throw new Error('Fullscreen could not be opened');
                });
            }

            function ready(callback) {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', callback);
                } else {
                    callback();
                }
            }

            function showFallback(message) {
                var statusBox = document.getElementById('mobilePdfStatus');
                var fallback = document.getElementById('androidPreviewFallback');

                if (statusBox) {
                    statusBox.style.display = 'none';
                }

                if (fallback) {
                    fallback.innerHTML = message || 'Secure preview could not load on this device. Please refresh this page.';
                    fallback.style.display = 'block';
                }
            }

            function loadScript(src, done, fail) {
                var existing = document.querySelector('script[src="' + src + '"]');

                if (existing) {
                    existing.onload = done;
                    existing.onerror = fail;
                    return;
                }

                var script = document.createElement('script');
                script.src = src;
                script.async = true;
                script.onload = done;
                script.onerror = fail;
                document.getElementsByTagName('head')[0].appendChild(script);
            }

            function renderPage(pdf, pageNumber, pagesContainer, statusBox) {
                if (pageNumber > pdf.numPages) {
                    statusBox.style.display = 'none';
                    return;
                }

                statusBox.innerHTML = 'Loaded page ' + (pageNumber - 1) + ' of ' + pdf.numPages;

                pdf.getPage(pageNumber).then(function (page) {
                    var viewport = page.getViewport({ scale: 1 });
                    var availableWidth = Math.max(280, pagesContainer.clientWidth || window.innerWidth || 360);
                    var scale = Math.min(2.8, availableWidth / viewport.width);
                    var scaledViewport = page.getViewport({ scale: scale });
                    var canvas = document.createElement('canvas');
                    var context = canvas.getContext('2d', { alpha: false });

                    canvas.className = 'mobile-pdf-page';
                    canvas.width = Math.floor(scaledViewport.width);
                    canvas.height = Math.floor(scaledViewport.height);
                    canvas.style.maxWidth = '100%';

                    pagesContainer.appendChild(canvas);

                    page.render({
                        canvasContext: context,
                        viewport: scaledViewport
                    }).promise.then(function () {
                        renderPage(pdf, pageNumber + 1, pagesContainer, statusBox);
                    }).catch(function () {
                        showFallback();
                    });
                }).catch(function () {
                    showFallback();
                });
            }

            function applyZoom(pagesContainer, zoomLevel) {
                if (!pagesContainer) {
                    return;
                }

                var clamped = Math.max(0.75, Math.min(2.5, zoomLevel || 1));
                pagesContainer.dataset.zoomLevel = String(clamped);
                pagesContainer.style.width = (100 / clamped) + '%';
                pagesContainer.style.transform = 'scale(' + clamped + ')';
            }

            function renderPages(pdfDocument, pagesContainer, statusBox) {
                var renderRequestId = (pagesContainer && Number(pagesContainer.dataset.renderRequestId || 0)) + 1;
                var currentRequestId = renderRequestId;

                if (pagesContainer) {
                    pagesContainer.dataset.renderRequestId = String(renderRequestId);
                }

                if (!pdfDocument || !pagesContainer || !statusBox) {
                    return;
                }

                while (pagesContainer.firstChild) {
                    pagesContainer.removeChild(pagesContainer.firstChild);
                }

                statusBox.style.display = 'block';
                statusBox.innerHTML = 'Loading ' + pdfDocument.numPages + ' page' + (pdfDocument.numPages === 1 ? '' : 's') + '...';

                (function renderNext(pageNumber) {
                    if (!pagesContainer || Number(pagesContainer.dataset.renderRequestId || 0) !== currentRequestId) {
                        return;
                    }

                    if (pageNumber > pdfDocument.numPages) {
                        statusBox.style.display = 'none';
                        return;
                    }

                    statusBox.innerHTML = 'Loaded page ' + (pageNumber - 1) + ' of ' + pdfDocument.numPages;

                    pdfDocument.getPage(pageNumber).then(function (page) {
                        if (!pagesContainer || Number(pagesContainer.dataset.renderRequestId || 0) !== currentRequestId) {
                            return;
                        }

                        var viewport = page.getViewport({ scale: 1 });
                        var availableWidth = Math.max(280, pagesContainer.clientWidth || window.innerWidth || 360);
                        var scale = Math.min(3, Math.max(0.8, availableWidth / viewport.width));
                        var scaledViewport = page.getViewport({ scale: scale });
                        var canvas = document.createElement('canvas');
                        var context = canvas.getContext('2d', { alpha: false });

                        canvas.className = 'mobile-pdf-page';
                        canvas.width = Math.floor(scaledViewport.width);
                        canvas.height = Math.floor(scaledViewport.height);
                        canvas.style.maxWidth = '100%';

                        pagesContainer.appendChild(canvas);

                        page.render({
                            canvasContext: context,
                            viewport: scaledViewport
                        }).promise.then(function () {
                            renderNext(pageNumber + 1);
                        }).catch(function () {
                            showFallback();
                        });
                    }).catch(function () {
                        showFallback();
                    });
                })(1);
            }

            ready(function () {
                var protectedSurface = document.querySelector('.protected-preview-surface');
                var pagesContainer = document.getElementById('mobilePdfPages');
                var statusBox = document.getElementById('mobilePdfStatus');
                var sourceUrl = @json($sourceUrl ?? null);
                var fullscreenBtn = document.getElementById('enterFullscreenBtn');
                var desktopFullscreenAction = document.getElementById('desktopFullscreenAction');
                var previewBackButton = document.getElementById('previewBackButton');
                var pdfDocument = null;
                var zoomLevel = 1;
                var zoomInButton = document.getElementById('zoomInButton');
                var zoomOutButton = document.getElementById('zoomOutButton');

                if (protectedSurface) {
                    protectedSurface.classList.add('allow-scroll-through');
                }

                if (isDesktopPreview()) {
                    toggleDesktopGate(true);

                    if (fullscreenBtn) {
                        fullscreenBtn.addEventListener('click', function () {
                            openFullscreenAndReveal().catch(function () {});
                        });
                    }

                    if (desktopFullscreenAction) {
                        desktopFullscreenAction.addEventListener('click', function () {
                            openFullscreenAndReveal().catch(function () {});
                        });
                    }

                    if (previewBackButton) {
                        previewBackButton.addEventListener('click', function () {
                            exitFullscreenShell().catch(function () {});
                        });
                    }

                    if (zoomInButton) {
                        zoomInButton.addEventListener('click', function () {
                            if (!pdfDocument) {
                                return;
                            }

                            zoomLevel = Math.min(2.5, Math.round((zoomLevel + 0.15) * 100) / 100);
                            applyZoom(pagesContainer, zoomLevel);
                        });
                    }

                    if (zoomOutButton) {
                        zoomOutButton.addEventListener('click', function () {
                            if (!pdfDocument) {
                                return;
                            }

                            zoomLevel = Math.max(0.75, Math.round((zoomLevel - 0.15) * 100) / 100);
                            applyZoom(pagesContainer, zoomLevel);
                        });
                    }

                }

                document.addEventListener('fullscreenchange', function () {
                    if (!isDesktopPreview()) {
                        toggleDesktopGate(false);
                        return;
                    }

                    toggleDesktopGate(!isFullscreenActive());
                });

                document.addEventListener('webkitfullscreenchange', function () {
                    if (!isDesktopPreview()) {
                        toggleDesktopGate(false);
                        return;
                    }

                    toggleDesktopGate(!isFullscreenActive());
                });

                if (!window.Promise || !window.fetch || !sourceUrl || !pagesContainer || !statusBox) {
                    showFallback('This panel browser is too old for secure preview rendering.');
                    return;
                }

                loadScript(
                    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js',
                    function () {
                        if (!window.pdfjsLib) {
                            showFallback();
                            return;
                        }

                        window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

                        window.pdfjsLib.getDocument({
                            url: sourceUrl,
                            withCredentials: true,
                            disableAutoFetch: true,
                            disableStream: true
                        }).promise.then(function (pdf) {
                            pdfDocument = pdf;
                            zoomLevel = 1;
                            applyZoom(pagesContainer, zoomLevel);
                            renderPages(pdfDocument, pagesContainer, statusBox);
                        }).catch(function () {
                            showFallback();
                        });
                    },
                    function () {
                        showFallback('Secure preview library could not load. Please check panel internet access and refresh.');
                    }
                );
            });
        })();
    </script>
</body>
</html>
