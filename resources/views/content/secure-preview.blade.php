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
        }

        .preview-pill {
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

        .preview-shell:fullscreen .preview-frame-wrap {
            padding: 14px 16px 18px;
        }

        .preview-shell:fullscreen .preview-stage {
            max-width: none;
            border-radius: 10px;
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
                <div class="preview-title-wrap">
                    <div class="preview-title">{{ $content->content_title }}</div>
                    <div class="preview-subtitle">Secure Preview</div>
                </div>
            </div>

            <div class="preview-actions">
                <span class="preview-warning">Screenshots and recording are not allowed</span>
                <span class="preview-pill">View only</span>
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
                    var scale = Math.min(2.2, availableWidth / viewport.width);
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

            ready(function () {
                var protectedSurface = document.querySelector('.protected-preview-surface');
                var pagesContainer = document.getElementById('mobilePdfPages');
                var statusBox = document.getElementById('mobilePdfStatus');
                var sourceUrl = @json($sourceUrl ?? null);

                if (protectedSurface) {
                    protectedSurface.classList.add('allow-scroll-through');
                }

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
                            statusBox.innerHTML = 'Loading ' + pdf.numPages + ' page' + (pdf.numPages === 1 ? '' : 's') + '...';
                            renderPage(pdf, 1, pagesContainer, statusBox);
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
