@once
    <style>
        .protected-preview-surface {
            position: relative;
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
        }

        .protected-preview-content {
            position: relative;
            z-index: 1;
        }

        .protected-preview-surface::before {
            content: attr(data-watermark);
            position: absolute;
            left: 50%;
            top: 50%;
            z-index: 5;
            max-width: 90%;
            color: #0f172a;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.08em;
            line-height: 1.5;
            opacity: 0.12;
            pointer-events: none;
            text-align: center;
            text-transform: uppercase;
            transform: translate(-50%, -50%) rotate(-24deg);
            white-space: pre-line;
        }

        .protected-preview-surface::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 6;
            pointer-events: none;
            background-image:
                linear-gradient(135deg, rgba(15, 23, 42, 0.04) 25%, transparent 25%),
                linear-gradient(225deg, rgba(15, 23, 42, 0.04) 25%, transparent 25%);
            background-position: 0 0, 18px 18px;
            background-size: 36px 36px;
        }

        .protected-preview-surface.capture-warning .protected-preview-content {
            filter: blur(14px);
            opacity: 0.18;
        }

        .protected-preview-surface.capture-guard .protected-preview-content {
            filter: blur(14px);
            opacity: 0.18;
        }

        .protected-preview-surface.capture-guard::after,
        .protected-preview-surface.capture-warning::after {
            content: "Screen capture restricted";
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, 0.92);
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-align: center;
        }

        .protected-preview-surface.capture-closed {
            min-height: 220px;
            background: #0f172a;
        }

        .protected-preview-surface.capture-closed .protected-preview-content {
            opacity: 0;
            visibility: hidden;
        }

        .protected-preview-surface.capture-closed::before {
            content: "";
        }

        .protected-preview-surface.capture-closed::after {
            content: "Content preview closed because screen capture is restricted.";
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #0f172a;
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-align: center;
        }
    </style>

    <script>
        (function () {
            const protectedSelector = '.protected-preview-surface';
            const captureLockKey = 'tinkedge_content_preview_capture_lock_until';
            const alertCooldownKey = 'tinkedge_content_preview_alert_until';
            const captureLockDurationMs = 5 * 60 * 1000;
            let previewClosed = false;
            let guardTimer = null;
            let currentPreviewScope = null;

            function protectedSurfaces() {
                return document.querySelectorAll(protectedSelector);
            }

            function hasProtectedTarget(event) {
                return event.target && event.target.closest(protectedSelector);
            }

            function previewScope(surface) {
                return surface && surface.dataset.previewScope
                    ? surface.dataset.previewScope
                    : 'global';
            }

            function scopedKey(baseKey, scope) {
                return baseKey + '_' + (scope || 'global');
            }

            function rememberPreviewScope(event) {
                const surface = event.target && event.target.closest(protectedSelector);

                if (surface) {
                    currentPreviewScope = previewScope(surface);
                }
            }

            function sharedRestrictionWindow() {
                try {
                    return window.top || window;
                } catch (error) {
                    return window;
                }
            }

            function isEmbeddedPreviewWindow() {
                try {
                    return window.self !== window.top;
                } catch (error) {
                    return true;
                }
            }

            function canShowRestrictionAlert(scope) {
                const sharedWindow = sharedRestrictionWindow();
                const storage = lockStorage();
                const now = Date.now();
                const scopedAlertKey = scopedKey(alertCooldownKey, scope);
                const storedAlertUntil = storage ? Number(storage.getItem(scopedAlertKey) || 0) : 0;

                sharedWindow.__tinkedgePreviewRestrictionAlertUntil =
                    sharedWindow.__tinkedgePreviewRestrictionAlertUntil || {};

                if (
                    sharedWindow.__tinkedgePreviewRestrictionAlertUntil[scope] > now ||
                    storedAlertUntil > now
                ) {
                    return false;
                }

                sharedWindow.__tinkedgePreviewRestrictionAlertUntil[scope] = now + 3000;

                if (storage) {
                    storage.setItem(scopedAlertKey, String(now + 3000));
                }

                return true;
            }

            function lockStorage() {
                try {
                    return window.localStorage;
                } catch (error) {
                    return null;
                }
            }

            function setCaptureLock(scope) {
                const storage = lockStorage();

                if (!storage) {
                    return;
                }

                storage.setItem(scopedKey(captureLockKey, scope), String(Date.now() + captureLockDurationMs));
            }

            function captureLockRemainingSeconds(scope) {
                const storage = lockStorage();

                if (!storage) {
                    return 0;
                }

                const scopedCaptureLockKey = scopedKey(captureLockKey, scope);
                const lockedUntil = Number(storage.getItem(scopedCaptureLockKey) || 0);
                const remainingMs = lockedUntil - Date.now();

                if (remainingMs <= 0) {
                    storage.removeItem(scopedCaptureLockKey);
                    return 0;
                }

                return Math.ceil(remainingMs / 1000);
            }

            function activeModalElement() {
                return document.querySelector('.modal.show');
            }

            function activePreviewScope() {
                if (currentPreviewScope) {
                    return currentPreviewScope;
                }

                const modalElement = activeModalElement();
                const firstSurface = modalElement
                    ? modalElement.querySelector(protectedSelector)
                    : document.querySelector(protectedSelector);

                return previewScope(firstSurface);
            }

            function activeProtectedSurfaces(scope) {
                const selectedScope = scope || activePreviewScope();
                const modalElement = activeModalElement();
                const surfaces = modalElement
                    ? modalElement.querySelectorAll(protectedSelector)
                    : protectedSurfaces();

                return Array.from(surfaces).filter(function (surface) {
                    return previewScope(surface) === selectedScope;
                });
            }

            function clearProtectedSurface(surface) {
                surface.classList.add('capture-closed');

                surface.querySelectorAll('iframe').forEach(function (frame) {
                    if (!frame.dataset.protectedSrc && frame.getAttribute('src')) {
                        frame.dataset.protectedSrc = frame.getAttribute('src');
                    }

                    frame.removeAttribute('src');
                });

                surface.querySelectorAll('video, audio').forEach(function (media) {
                    if (!media.dataset.protectedSrc && media.getAttribute('src')) {
                        media.dataset.protectedSrc = media.getAttribute('src');
                    }

                    media.pause();
                    media.removeAttribute('src');
                    media.load();
                });
            }

            function resetProtectedPreviews(scope) {
                previewClosed = false;
                const selectedScope = scope || null;

                protectedSurfaces().forEach(function (surface) {
                    if (selectedScope && previewScope(surface) !== selectedScope) {
                        return;
                    }

                    surface.classList.remove('capture-guard', 'capture-warning', 'capture-closed');

                    surface.querySelectorAll('iframe').forEach(function (frame) {
                        if (frame.dataset.protectedSrc) {
                            frame.setAttribute('src', frame.dataset.protectedSrc);
                        }
                    });

                    surface.querySelectorAll('video, audio').forEach(function (media) {
                        if (media.dataset.protectedSrc) {
                            media.setAttribute('src', media.dataset.protectedSrc);
                            media.load();
                        }
                    });
                });
            }

            function setCaptureGuard(isGuarded, scope) {
                activeProtectedSurfaces(scope).forEach(function (surface) {
                    surface.classList.toggle('capture-guard', isGuarded);
                });
            }

            function clearCaptureGuardSoon(scope) {
                window.clearTimeout(guardTimer);

                guardTimer = window.setTimeout(function () {
                    if (!previewClosed) {
                        setCaptureGuard(false, scope);
                    }
                }, 500);
            }

            function closeProtectedPreview(reason, shouldLock, scope) {
                const selectedScope = scope || activePreviewScope();

                if (previewClosed) {
                    return;
                }

                previewClosed = true;
                window.clearTimeout(guardTimer);

                if (shouldLock !== false) {
                    setCaptureLock(selectedScope);
                }

                activeProtectedSurfaces(selectedScope).forEach(function (surface) {
                    surface.classList.remove('capture-guard');
                    surface.classList.add('capture-warning');
                });

                window.setTimeout(function () {
                    activeProtectedSurfaces(selectedScope).forEach(clearProtectedSurface);

                    if (isEmbeddedPreviewWindow()) {
                        try {
                            window.parent.postMessage({
                                type: 'tinkedge-content-preview-restricted',
                                reason: reason || 'Screen capture is restricted. This content preview will be closed.',
                                scope: selectedScope
                            }, window.location.origin);
                        } catch (error) {}

                        return;
                    }

                    if (canShowRestrictionAlert(selectedScope)) {
                        window.alert(reason || 'Screen capture is restricted. This content preview will be closed.');
                    }

                    const modalElement = activeModalElement();

                    if (modalElement && window.bootstrap) {
                        const modal = window.bootstrap.Modal.getInstance(modalElement);

                        if (modal) {
                            modal.hide();
                        }

                        modalElement.addEventListener('hidden.bs.modal', function () {
                            window.setTimeout(function () {
                                resetProtectedPreviews(selectedScope);
                            }, 3200);
                        }, { once: true });
                        window.setTimeout(function () {
                            resetProtectedPreviews(selectedScope);
                        }, 3200);
                        return;
                    }

                    if (document.getElementById('previewShell')) {
                        window.setTimeout(function () {
                            if (window.opener) {
                                window.close();
                                return;
                            }

                            if (window.history.length > 1) {
                                window.history.back();
                                return;
                            }

                            window.location.replace('about:blank');
                        }, 250);
                    }
                }, 80);
            }

            function blockProtectedEvent(event) {
                if (!hasProtectedTarget(event)) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
            }

            ['contextmenu', 'copy', 'cut', 'dragstart', 'selectstart'].forEach(function (eventName) {
                document.addEventListener(eventName, blockProtectedEvent, true);
            });

            ['pointerdown', 'pointerenter', 'focusin', 'mouseover'].forEach(function (eventName) {
                document.addEventListener(eventName, rememberPreviewScope, true);
            });

            function isRestrictedPreviewShortcut(event) {
                const key = event.key ? event.key.toLowerCase() : '';

                const blocksPrintScreen = key === 'printscreen';
                const blocksWindowsPrintScreen = event.metaKey && key === 'printscreen';
                const blocksWindowsSnippingTool = event.metaKey && event.shiftKey && key === 's';
                const blocksSaveOrPrint = (event.ctrlKey || event.metaKey) && ['p', 's'].includes(key);
                const blocksDevTools = key === 'f12' ||
                    ((event.ctrlKey || event.metaKey) && event.shiftKey && ['c', 'i', 'j', 's'].includes(key));

                return blocksPrintScreen ||
                    blocksWindowsPrintScreen ||
                    blocksWindowsSnippingTool ||
                    blocksSaveOrPrint ||
                    blocksDevTools;
            }

            function isModifierOrCapturePreparation(event) {
                const key = event.key ? event.key.toLowerCase() : '';

                return event.metaKey ||
                    event.ctrlKey ||
                    event.altKey ||
                    event.shiftKey ||
                    ['meta', 'os', 'control', 'ctrl', 'alt', 'shift', 'printscreen'].includes(key);
            }

            function handleRestrictedShortcut(event) {
                const selectedScope = activePreviewScope();

                if (event.type === 'keydown' && isModifierOrCapturePreparation(event)) {
                    setCaptureGuard(true, selectedScope);
                }

                if (!isRestrictedPreviewShortcut(event)) {
                    if (event.type === 'keyup') {
                        clearCaptureGuardSoon(selectedScope);
                    }

                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                if (navigator.clipboard) {
                    navigator.clipboard.writeText('Screenshots are restricted for this LMS content preview.').catch(function () {});
                }

                closeProtectedPreview(
                    'Screen capture, saving, printing, and inspection are restricted for this content. The preview will now close.',
                    true,
                    selectedScope
                );
            }

            function enforceCaptureLock() {
                const selectedScope = activePreviewScope();
                const remainingSeconds = captureLockRemainingSeconds(selectedScope);

                if (!remainingSeconds || !protectedSurfaces().length || previewClosed) {
                    return;
                }

                closeProtectedPreview(
                    'Content preview is temporarily unavailable because a restricted screen-capture attempt was detected. Please try again in about ' +
                        Math.ceil(remainingSeconds / 60) +
                        ' minute(s).',
                    false,
                    selectedScope
                );
            }

            document.addEventListener('keydown', handleRestrictedShortcut, true);
            document.addEventListener('keyup', handleRestrictedShortcut, true);
            document.addEventListener('shown.bs.modal', enforceCaptureLock);
            window.addEventListener('message', function (event) {
                if (event.origin !== window.location.origin) {
                    return;
                }

                if (!event.data || event.data.type !== 'tinkedge-content-preview-restricted') {
                    return;
                }

                closeProtectedPreview(
                    event.data.reason || 'Screen capture is restricted. This content preview will be closed.',
                    false,
                    event.data.scope || activePreviewScope()
                );
            });

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', enforceCaptureLock);
            } else {
                enforceCaptureLock();
            }
        })();
    </script>
@endonce
