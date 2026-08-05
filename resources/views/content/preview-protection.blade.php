@once
    <style>
        .protected-preview-surface {
            position: relative;
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
            --restriction-message: "Screen capture, right click, saving, printing, and inspection are restricted for this content.";
        }

        .protected-preview-content {
            position: relative;
            z-index: 1;
            height: 100%;
            overflow: hidden;
        }

        .protected-preview-mouse-shield {
            position: absolute;
            inset: 0;
            z-index: 8;
            cursor: default;
            background: transparent;
            pointer-events: auto;
        }

        .protected-preview-surface.allow-scroll-through .protected-preview-mouse-shield {
            pointer-events: none;
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

        .protected-preview-surface.capture-focus-loss .protected-preview-content {
            filter: blur(18px);
            opacity: 0.14;
        }

        .protected-preview-surface.capture-guard .protected-preview-content {
            filter: blur(14px);
            opacity: 0.18;
        }

        .protected-preview-surface.capture-guard::after,
        .protected-preview-surface.capture-warning::after,
        .protected-preview-surface.capture-focus-loss::after {
            content: var(--restriction-message);
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
            content: var(--restriction-message);
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
            const captureLockKey = 'tinkedge_content_preview_capture_lock_until_v3';
            const legacyCaptureLockPrefix = 'tinkedge_content_preview_capture_lock_until';
            const captureLockDurationMs = 3 * 60 * 1000;
            const warningDisplayMs = 1400;
            const closedPreviewScopes = new Set();
            let guardTimer = null;
            let scrollThroughTimer = null;
            let focusLossTimer = null;
            let currentPreviewScope = null;

            function protectedSurfaces() {
                return document.querySelectorAll(protectedSelector);
            }

            function isSurfaceVisible(surface) {
                if (!surface) {
                    return false;
                }

                if (document.getElementById('previewShell')) {
                    return true;
                }

                const modalElement = surface.closest('.modal');

                if (modalElement) {
                    return modalElement.classList.contains('show');
                }

                return !!(surface.offsetWidth || surface.offsetHeight || surface.getClientRects().length);
            }

            function visibleProtectedSurfaces() {
                return Array.from(protectedSurfaces()).filter(isSurfaceVisible);
            }

            function hasProtectedTarget(event) {
                const surface = event.target && event.target.closest(protectedSelector);

                return surface && isSurfaceVisible(surface);
            }

            function hasProtectedPreview() {
                return visibleProtectedSurfaces().length > 0;
            }

            function previewScope(surface) {
                return surface && surface.dataset.previewScope
                    ? surface.dataset.previewScope
                    : 'global';
            }

            function rememberPreviewScope(event) {
                const surface = event.target && event.target.closest(protectedSelector);

                if (surface) {
                    currentPreviewScope = previewScope(surface);
                }
            }

            function isEmbeddedPreviewWindow() {
                try {
                    return window.self !== window.top;
                } catch (error) {
                    return true;
                }
            }

            function lockStorage() {
                try {
                    return window.localStorage;
                } catch (error) {
                    return null;
                }
            }

            function scopedKey(baseKey, scope) {
                return baseKey + '_' + (scope || 'global');
            }

            function clearStoredCaptureLocks() {
                const storage = lockStorage();

                if (!storage) {
                    return;
                }

                Object.keys(storage).forEach(function (key) {
                    if (key.indexOf(legacyCaptureLockPrefix) === 0 && key.indexOf(captureLockKey) !== 0) {
                        storage.removeItem(key);
                    }
                });
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

            function scopeFromContainer(container) {
                const surface = container
                    ? container.querySelector(protectedSelector)
                    : null;

                return previewScope(surface);
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
                    : visibleProtectedSurfaces();

                return Array.from(surfaces).filter(function (surface) {
                    return previewScope(surface) === selectedScope;
                });
            }

            function isPreviewClosed(scope) {
                return closedPreviewScopes.has(scope || activePreviewScope());
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
                const selectedScope = scope || null;

                if (selectedScope) {
                    closedPreviewScopes.delete(selectedScope);
                } else {
                    closedPreviewScopes.clear();
                }

                visibleProtectedSurfaces().forEach(function (surface) {
                    if (selectedScope && previewScope(surface) !== selectedScope) {
                        return;
                    }

                    surface.classList.remove('capture-guard', 'capture-warning', 'capture-closed');
                    surface.style.removeProperty('--restriction-message');

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
                    if (isGuarded) {
                        surface.style.setProperty(
                            '--restriction-message',
                            '"Screen capture shortcuts are restricted for this content."'
                        );
                    }

                    surface.classList.toggle('capture-guard', isGuarded);
                });
            }

            function clearCaptureGuardSoon(scope) {
                window.clearTimeout(guardTimer);

                guardTimer = window.setTimeout(function () {
                    if (!isPreviewClosed(scope)) {
                        setCaptureGuard(false, scope);
                    }
                }, 500);
            }

            function closeProtectedPreview(reason, shouldLock, scope) {
                const selectedScope = scope || activePreviewScope();

                if (isPreviewClosed(selectedScope)) {
                    return;
                }

                closedPreviewScopes.add(selectedScope);
                window.clearTimeout(guardTimer);

                if (shouldLock !== false) {
                    setCaptureLock(selectedScope);
                }

                const displayMessage = reason || 'This content preview was closed because a restricted action was detected.';

                activeProtectedSurfaces(selectedScope).forEach(function (surface) {
                    surface.style.setProperty('--restriction-message', '"' + displayMessage.replace(/"/g, '\\"') + '"');
                    surface.classList.remove('capture-guard', 'capture-focus-loss');
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
                }, warningDisplayMs);
            }

            function blockProtectedEvent(event) {
                if (!hasProtectedTarget(event) && event.type !== 'contextmenu') {
                    return;
                }

                if (event.type === 'contextmenu' && !hasProtectedPreview()) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                if (event.type === 'contextmenu') {
                    closeProtectedPreview(
                        'Right click, saving, printing, and inspection are restricted for this content. The preview will now close.',
                        true,
                        activePreviewScope()
                    );
                }
            }

            ['contextmenu', 'copy', 'cut', 'dragstart', 'selectstart'].forEach(function (eventName) {
                document.addEventListener(eventName, blockProtectedEvent, true);
            });

            function handleShieldMouseAttempt(event) {
                const shield = event.target && event.target.closest('.protected-preview-mouse-shield');

                if (!shield) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                const surface = shield.closest(protectedSelector);
                const selectedScope = previewScope(surface);
                currentPreviewScope = selectedScope;

                if (event.button === 2) {
                    closeProtectedPreview(
                        'Right click, saving, printing, and inspection are restricted for this content. The preview will now close.',
                        true,
                        selectedScope
                    );
                }
            }

            document.addEventListener('pointerdown', handleShieldMouseAttempt, true);
            document.addEventListener('mousedown', handleShieldMouseAttempt, true);

            function scrollProtectedFrame(event) {
                const shield = event.target && event.target.closest('.protected-preview-mouse-shield');

                if (!shield) {
                    return;
                }

                const surface = shield.closest(protectedSelector);

                if (!isSurfaceVisible(surface)) {
                    return;
                }

                surface.classList.add('allow-scroll-through');
                window.clearTimeout(scrollThroughTimer);
                scrollThroughTimer = window.setTimeout(function () {
                    surface.classList.remove('allow-scroll-through');
                }, 1200);
            }

            document.addEventListener('wheel', scrollProtectedFrame, { capture: true, passive: true });

            document.addEventListener('contextmenu', function (event) {
                const shield = event.target && event.target.closest('.protected-preview-mouse-shield');

                if (!shield) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                const surface = shield.closest(protectedSelector);
                const selectedScope = previewScope(surface);
                currentPreviewScope = selectedScope;

                closeProtectedPreview(
                    'Right click, saving, printing, and inspection are restricted for this content. The preview will now close.',
                    true,
                    selectedScope
                );
            }, true);

            function installFrameProtection(frame) {
                try {
                    const frameDocument = frame.contentDocument || frame.contentWindow.document;

                    if (!frameDocument || !frameDocument.documentElement) {
                        return;
                    }

                    if (frameDocument.documentElement.dataset.tinkedgeProtectionInstalled) {
                        return;
                    }

                    frameDocument.documentElement.dataset.tinkedgeProtectionInstalled = 'true';
                    frameDocument.addEventListener('contextmenu', function (event) {
                        event.preventDefault();
                        event.stopPropagation();

                        const surface = frame.closest(protectedSelector);
                        const selectedScope = previewScope(surface);
                        currentPreviewScope = selectedScope;

                        closeProtectedPreview(
                            'Right click, saving, printing, and inspection are restricted for this content. The preview will now close.',
                            true,
                            selectedScope
                        );
                    }, true);
                } catch (error) {}
            }

            document.querySelectorAll(protectedSelector + ' iframe').forEach(function (frame) {
                frame.addEventListener('load', function () {
                    installFrameProtection(frame);
                });
                installFrameProtection(frame);
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
                const blocksViewSourceOrOpen = (event.ctrlKey || event.metaKey) && ['o', 'u'].includes(key);
                const blocksDevTools = key === 'f12' ||
                    ((event.ctrlKey || event.metaKey) && event.shiftKey && ['c', 'i', 'j', 's'].includes(key));

                return blocksPrintScreen ||
                    blocksWindowsPrintScreen ||
                    blocksWindowsSnippingTool ||
                    blocksSaveOrPrint ||
                    blocksViewSourceOrOpen ||
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
                if (!hasProtectedPreview()) {
                    return;
                }

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

            function lockedPreviewMessage(remainingSeconds) {
                const remainingMinutes = Math.max(1, Math.ceil(remainingSeconds / 60));

                return 'This content is temporarily inaccessible because a restricted action was detected. Please try again in about ' +
                    remainingMinutes +
                    ' minute(s).';
            }

            function enforceCaptureLock(event) {
                const selectedScope = event && event.target
                    ? scopeFromContainer(event.target)
                    : activePreviewScope();
                const remainingSeconds = captureLockRemainingSeconds(selectedScope);

                if (!remainingSeconds || !activeProtectedSurfaces(selectedScope).length || isPreviewClosed(selectedScope)) {
                    return;
                }

                closeProtectedPreview(
                    lockedPreviewMessage(remainingSeconds),
                    false,
                    selectedScope
                );
            }

            function setFocusLossGuard(isBlurred, scope) {
                const selectedScope = scope || activePreviewScope();

                activeProtectedSurfaces(selectedScope).forEach(function (surface) {
                    if (isBlurred) {
                        surface.style.setProperty(
                            '--restriction-message',
                            '"Preview blurred because the page lost focus. Return to the page to continue viewing."'
                        );
                        surface.classList.add('capture-focus-loss');
                    } else if (!isPreviewClosed(selectedScope)) {
                        surface.classList.remove('capture-focus-loss');
                    }
                });
            }

            function handleWindowFocusState() {
                const selectedScope = activePreviewScope();

                if (!hasProtectedPreview()) {
                    return;
                }

                if (document.hidden || !document.hasFocus()) {
                    window.clearTimeout(focusLossTimer);
                    focusLossTimer = window.setTimeout(function () {
                        if (!hasProtectedPreview()) {
                            return;
                        }

                        setFocusLossGuard(true, selectedScope);
                    }, 120);
                    return;
                }

                window.clearTimeout(focusLossTimer);
                setFocusLossGuard(false, selectedScope);
            }

            clearStoredCaptureLocks();
            document.addEventListener('keydown', handleRestrictedShortcut, true);
            document.addEventListener('keyup', handleRestrictedShortcut, true);
            window.addEventListener('blur', handleWindowFocusState);
            window.addEventListener('focus', handleWindowFocusState);
            document.addEventListener('visibilitychange', handleWindowFocusState);
            window.addEventListener('beforeprint', function (event) {
                if (!hasProtectedPreview()) {
                    return;
                }

                event.preventDefault();
                closeProtectedPreview(
                    'Printing is restricted for this content. The preview will now close.',
                    true,
                    activePreviewScope()
                );
            });
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

            document.addEventListener('shown.bs.modal', function (event) {
                currentPreviewScope = scopeFromContainer(event.target);
                enforceCaptureLock(event);
            });

            if (document.getElementById('previewShell')) {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', enforceCaptureLock);
                } else {
                    enforceCaptureLock();
                }
            }
        })();
    </script>
@endonce
