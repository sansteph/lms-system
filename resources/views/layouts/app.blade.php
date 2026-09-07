<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $seoPublicRoutes = [
            'home',
            'newsroom',
            'certificate.verify',
            'coming.soon',
            'independent.register',
            'independent.courses',
        ];

        $seoIsPublic = request()->routeIs(...$seoPublicRoutes);
        $seoTitle = trim($__env->yieldContent('title', 'InnovatEdge | STEM Education and Robotics Learning Platform'));
        $seoDescription = trim($__env->yieldContent(
            'meta_description',
            'InnovatEdge is a modern STEM education platform for schools, ATL labs, robotics programs, assessments, certificates, and AI-assisted learning.'
        ));
        $seoImage = asset('images/InnovatEdgeLogo.png');
        $seoBaseUrl = rtrim(config('app.url'), '/');
        $seoPath = trim(request()->path(), '/');
        $seoUrl = $seoPath === '' ? $seoBaseUrl.'/' : $seoBaseUrl.'/'.$seoPath;
        $showParticles = request()->routeIs(
            'home',
            'portal',
            'admin.login',
            'teacher.login',
            'student.login',
            'independent.register',
            'independent.login',
            'coming.soon',
            'newsroom'
        );
        $showPanelNav = !request()->routeIs(
            'home',
            'portal',
            'admin.login',
            'teacher.login',
            'student.login',
            'independent.register',
            'independent.login',
            'coming.soon',
            'newsroom'
        );
        $enableScrollRestore = !request()->routeIs(
            'home',
            'portal',
            'admin.login*',
            'teacher.login*',
            'student.login*',
            'blogs.login*',
            'independent.login*',
            'independent.register*',
            'coming.soon',
            'content.preview*',
            'content.file*',
            'assessment.paper*',
            'assessment.answer.file*',
            'student.assessment.take*',
            'student.assessment-taking*',
            'teacher.ai-prep.quiz*',
            'student.content.ai-review.quiz*'
        );
        $showNotifications = !request()->routeIs('blogs*');
        $showAiChatbot = !session('student_id') || request()->routeIs('home');
        $panelHomeUrl = route('home');

        if (in_array(session('user_role'), ['Admin', 'InstituteAdmin'], true)) {
            $panelHomeUrl = route('admin.dashboard');
        } elseif (session('user_role') == 'Manager') {
            $panelHomeUrl = route('manager.dashboard');
        } elseif (session('user_role') == 'Principal') {
            $panelHomeUrl = route('principal.dashboard');
        } elseif (session('user_role') == 'Teacher') {
            $panelHomeUrl = route('teacher.dashboard');
        } elseif (session('student_id')) {
            $panelHomeUrl = route('student.dashboard');
        } elseif (session('independent_learner_id')) {
            $panelHomeUrl = route('independent.dashboard');
        }

        $panelUserName = session('user_name') ?? session('student_name') ?? session('independent_learner_name');
        $panelUserRoleLabel = null;

        if (session('user_role') == 'Admin') {
            $panelUserRoleLabel = 'Admin';
        } elseif (session('user_role') == 'InstituteAdmin') {
            $panelUserRoleLabel = 'Institute Admin';
        } elseif (session('user_role') == 'Manager') {
            $panelUserRoleLabel = 'Manager';
        } elseif (session('user_role') == 'Principal') {
            $panelUserRoleLabel = 'Principal';
        } elseif (session('user_role') == 'Teacher') {
            $panelUserRoleLabel = 'STEM Engineer';
        } elseif (session('student_id')) {
            $panelUserRoleLabel = 'Student';
        } elseif (session('independent_learner_id')) {
            $panelUserRoleLabel = 'Hybrid Learner';
        }

        $showLogout = session()->has('user_id') || session()->has('student_id') || session()->has('independent_learner_id');
    @endphp

    <meta charset="utf-8">

    <title>{{ $seoTitle }}</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoIsPublic ? 'index, follow, max-image-preview:large' : 'noindex, nofollow' }}">
    <meta name="theme-color" content="#07184a">
    <meta name="application-name" content="InnovatEdge">
    <meta name="apple-mobile-web-app-title" content="InnovatEdge">
    <link rel="canonical" href="{{ $seoUrl }}">

    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $seoUrl }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:site_name" content="InnovatEdge">
    <meta property="og:locale" content="en_IN">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/TinkEdgeLogo.png') }}">

    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ file_exists(public_path('css/style.css')) ? filemtime(public_path('css/style.css')) : time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @if($seoIsPublic)
        <script type="application/ld+json">
            {
                "@@context": "https://schema.org",
                "@type": "Organization",
                "name": "InnovatEdge",
                "url": "{{ $seoBaseUrl }}",
                "logo": "{{ $seoBaseUrl }}/images/InnovatEdgeLogo.png",
                "sameAs": [
                    "https://www.linkedin.com/company/tinkedge/posts/?feedView=all",
                    "https://www.instagram.com/tinkedge_/",
                    "https://www.youtube.com/@tinkedge9223",
                    "https://www.google.com/maps/place/TinkEdge/@13.0051806,77.5668533,17z"
                ]
            }
        </script>
    @endif
</head>

<body class="{{ $showPanelNav ? 'lms-panel-body' : 'lms-public-body' }}">

@if($showParticles)
    <div id="particles-js"></div>
@endif

@if($showPanelNav)

    <nav class="navbar navbar-expand-lg panel-navbar px-3 px-md-4">

        <a class="navbar-brand panel-navbar-brand"
           href="{{ $panelHomeUrl }}">
            <img src="{{ asset('images/InnovatEdgeLogo.png') }}" alt="InnovatEdge">
            <span>
                <strong>InnovatEdge</strong>
                <small>{{ $panelUserRoleLabel ?: 'Learning Panel' }}</small>
            </span>
        </a>

        <div class="ms-auto d-flex align-items-center gap-2 gap-md-3 panel-navbar-actions">

            @if($panelUserName)
                <span class="panel-user-chip">
                    <i class="fa-regular fa-circle-user"></i>
                    <span>
                        <strong>{{ $panelUserName }}</strong>
                        @if($panelUserRoleLabel)
                            <small>{{ $panelUserRoleLabel }}</small>
                        @endif
                    </span>
                </span>
            @endif

            @if($showLogout)
                <a href="{{ route('logout') }}"
                   class="btn btn-sm btn-outline-danger panel-logout-btn"
                   title="Logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            @endif

        </div>

    </nav>

@endif

@yield('content')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ordinalPattern = /(\b\d+)(st|nd|rd|th)\b/gi;

        const shouldSkipOrdinalFormatting = function (node) {
            if (!node) {
                return true;
            }

            return Boolean(
                node.closest('script, style, textarea, input, select, option, pre, code, kbd, samp, svg, noscript, .no-ordinal-format') ||
                node.closest('.modal')?.dataset.ordinalFormatDisabled === '1'
            );
        };

        const formatOrdinalTextNode = function (textNode) {
            if (!textNode || !textNode.nodeValue || !ordinalPattern.test(textNode.nodeValue)) {
                ordinalPattern.lastIndex = 0;
                return;
            }

            ordinalPattern.lastIndex = 0;

            const fragment = document.createDocumentFragment();
            let lastIndex = 0;
            let match;
            const text = textNode.nodeValue;

            while ((match = ordinalPattern.exec(text)) !== null) {
                const start = match.index;
                const end = start + match[0].length;

                if (start > lastIndex) {
                    fragment.appendChild(document.createTextNode(text.slice(lastIndex, start)));
                }

                fragment.appendChild(document.createTextNode(match[1]));

                const sup = document.createElement('sup');
                sup.textContent = match[2];
                sup.className = 'ordinal-suffix';
                fragment.appendChild(sup);

                lastIndex = end;
            }

            if (lastIndex < text.length) {
                fragment.appendChild(document.createTextNode(text.slice(lastIndex)));
            }

            textNode.parentNode.replaceChild(fragment, textNode);
        };

        const formatOrdinalsInNode = function (root) {
            if (!root || shouldSkipOrdinalFormatting(root)) {
                return;
            }

            const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
                acceptNode: function (node) {
                    if (!node.parentElement || shouldSkipOrdinalFormatting(node.parentElement)) {
                        return NodeFilter.FILTER_REJECT;
                    }

                    return ordinalPattern.test(node.nodeValue || '')
                        ? NodeFilter.FILTER_ACCEPT
                        : NodeFilter.FILTER_SKIP;
                }
            });

            const textNodes = [];
            let currentNode;

            ordinalPattern.lastIndex = 0;
            while ((currentNode = walker.nextNode())) {
                textNodes.push(currentNode);
            }

            textNodes.forEach(formatOrdinalTextNode);
        };

        formatOrdinalsInNode(document.body);

        const normalizeTableValue = function (value) {
            return String(value || '')
                .replace(/\s+/g, ' ')
                .replace(/[^\S\r\n]+/g, ' ')
                .trim();
        };

        const detectCellValue = function (cell) {
            if (!cell) {
                return '';
            }

            const input = cell.querySelector('input[type="hidden"]');
            if (input && input.value) {
                return input.value;
            }

            const text = normalizeTableValue(cell.textContent);
            return text;
        };

        const compareValues = function (a, b, direction) {
            const directionFactor = direction === 'desc' ? -1 : 1;
            const numericA = parseFloat(a.replace(/[^0-9.-]/g, ''));
            const numericB = parseFloat(b.replace(/[^0-9.-]/g, ''));
            const aIsNumeric = a !== '' && !Number.isNaN(numericA) && /^[0-9.,\-%\s]+$/.test(a.replace(/,/g, ''));
            const bIsNumeric = b !== '' && !Number.isNaN(numericB) && /^[0-9.,\-%\s]+$/.test(b.replace(/,/g, ''));

            if (aIsNumeric && bIsNumeric) {
                return (numericA - numericB) * directionFactor;
            }

            return a.localeCompare(b, undefined, {
                numeric: true,
                sensitivity: 'base',
            }) * directionFactor;
        };

        const sortTableRows = function (table, columnIndex, direction) {
            const tbody = table.tBodies && table.tBodies[0] ? table.tBodies[0] : null;

            if (!tbody) {
                return false;
            }

            const rows = Array.from(tbody.rows);
            if (!rows.length) {
                return false;
            }

            const canSort = rows.every(function (row) {
                return row.cells.length > columnIndex && !row.querySelector('[colspan]');
            });

            if (!canSort) {
                return false;
            }

            const sortedRows = rows.slice().sort(function (rowA, rowB) {
                const cellA = detectCellValue(rowA.cells[columnIndex]);
                const cellB = detectCellValue(rowB.cells[columnIndex]);
                return compareValues(cellA, cellB, direction);
            });

            sortedRows.forEach(function (row) {
                tbody.appendChild(row);
            });

            return true;
        };

        const decorateSortableHeaders = function (table) {
            const headCells = Array.from(table.querySelectorAll('thead th'));
            if (!headCells.length || table.dataset.sortableInitialized === '1') {
                return;
            }

            table.dataset.sortableInitialized = '1';

            headCells.forEach(function (th, index) {
                if (th.closest('.no-table-sort') || th.dataset.sortable === 'false') {
                    return;
                }

                th.classList.add('table-sortable-header');
                th.dataset.sortDirection = 'none';

                th.addEventListener('click', function () {
                    const nextDirection = th.dataset.sortDirection === 'asc' ? 'desc' : 'asc';
                    const sorted = sortTableRows(table, index, nextDirection);

                    if (!sorted) {
                        return;
                    }

                    table.querySelectorAll('thead th').forEach(function (cell) {
                        cell.dataset.sortDirection = 'none';
                        cell.classList.remove('sort-asc', 'sort-desc');
                    });

                    th.dataset.sortDirection = nextDirection;
                    th.classList.add(nextDirection === 'asc' ? 'sort-asc' : 'sort-desc');
                });
            });
        };

        const initializeSortableTables = function (root) {
            const scope = root || document;

            scope.querySelectorAll('table.table, table').forEach(function (table) {
                if (
                    table.closest('.no-table-sort') ||
                    table.closest('.blogs-social-page') ||
                    table.closest('.community-post-card') ||
                    table.closest('.modal')
                ) {
                    return;
                }

                const headerCells = table.querySelectorAll('thead th');
                const bodyRows = table.tBodies && table.tBodies[0] ? Array.from(table.tBodies[0].rows) : [];
                const hasSortableStructure = headerCells.length > 1 && bodyRows.length > 0;

                if (hasSortableStructure) {
                    decorateSortableHeaders(table);
                }
            });
        };

        initializeSortableTables(document);

        if (window.MutationObserver) {
            const tableSortObserver = new MutationObserver(function (mutations) {
                let shouldRefresh = false;

                mutations.forEach(function (mutation) {
                    if (shouldRefresh) {
                        return;
                    }

                    mutation.addedNodes.forEach(function (node) {
                        if (shouldRefresh) {
                            return;
                        }

                        if (node && node.nodeType === 1 && (
                            (node.matches && node.matches('table.table, table')) ||
                            (node.querySelector && node.querySelector('table.table, table'))
                        )) {
                            shouldRefresh = true;
                        }
                    });
                });

                if (shouldRefresh) {
                    initializeSortableTables(document);
                }
            });

            tableSortObserver.observe(document.body, {
                childList: true,
                subtree: true,
            });
        }

        const markRequiredLabel = function (label) {
            if (!label || label.querySelector('.required-field-marker')) {
                return;
            }

            const marker = document.createElement('span');
            marker.className = 'required-field-marker';
            marker.textContent = ' *';
            marker.setAttribute('aria-hidden', 'true');
            label.appendChild(marker);
        };

        const findLabelForField = function (field) {
            if (!field || field.type === 'hidden' || field.disabled) {
                return null;
            }

            const labels = field.labels && field.labels.length ? Array.from(field.labels) : [];
            if (labels.length) {
                return labels[0];
            }

            const fieldWrap = field.closest('.col-md-3, .col-md-4, .col-md-6, .col-md-12, .mb-3, .form-group, .input-group, .modern-input, .auth-input-group');
            if (fieldWrap) {
                const wrapLabel = fieldWrap.querySelector(':scope > label, :scope > .form-label');
                if (wrapLabel) {
                    return wrapLabel;
                }
            }

            const parent = field.parentElement;
            if (parent) {
                const previousLabel = parent.querySelector(':scope > label, :scope > .form-label');
                if (previousLabel) {
                    return previousLabel;
                }
            }

            return null;
        };

        document.querySelectorAll('form').forEach(function (form) {
            form.querySelectorAll('input, textarea, select').forEach(function (field) {
                if (!field.hasAttribute('required')) {
                    return;
                }

                if (['hidden', 'button', 'submit', 'reset', 'image', 'file'].includes(field.type) && field.type !== 'file') {
                    return;
                }

                const label = findLabelForField(field);
                markRequiredLabel(label);
            });
        });

        const hiddenEyeIcon = `
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M3 3l18 18"></path>
                <path d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58"></path>
                <path d="M9.88 5.09A9.77 9.77 0 0 1 12 4.86c5.52 0 9 5.14 9 7.14a5.86 5.86 0 0 1-1.23 2.73"></path>
                <path d="M6.11 6.11C4.19 7.41 3 10.02 3 12c0 2 3.48 7.14 9 7.14a9.54 9.54 0 0 0 4.05-.9"></path>
            </svg>`;
        const visibleEyeIcon = `
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>`;

        document.querySelectorAll('input[type="password"]').forEach(function (input) {
            if (input.dataset.passwordToggleAttached === '1' || input.closest('.password-toggle-wrap')) {
                return;
            }

            input.dataset.passwordToggleAttached = '1';

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'password-toggle-btn';
            button.setAttribute('aria-label', 'Show password');
            button.innerHTML = hiddenEyeIcon;

            button.addEventListener('click', function () {
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                button.innerHTML = isHidden ? visibleEyeIcon : hiddenEyeIcon;
            });

            const existingGroup = input.closest('.auth-input-group, .modern-input, .input-group');

            if (existingGroup) {
                existingGroup.classList.add('password-toggle-wrap', 'password-toggle-group');
                existingGroup.appendChild(button);
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'password-toggle-wrap';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            wrapper.appendChild(button);
        });
    });
</script>

@if($enableScrollRestore)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const scrollKey = 'innovatedge-scroll:' + window.location.pathname + window.location.search;
        const maxAgeMs = 30 * 60 * 1000;

        const restoreScroll = function () {
            let saved = null;

            try {
                saved = JSON.parse(sessionStorage.getItem(scrollKey) || 'null');
                sessionStorage.removeItem(scrollKey);
            } catch (error) {
                sessionStorage.removeItem(scrollKey);
            }

            if (!saved || typeof saved.y !== 'number' || Date.now() - saved.time > maxAgeMs) {
                return;
            }

            window.requestAnimationFrame(function () {
                window.scrollTo({
                    top: saved.y,
                    left: saved.x || 0,
                    behavior: 'auto'
                });
            });
        };

        const rememberScroll = function () {
            try {
                sessionStorage.setItem(scrollKey, JSON.stringify({
                    x: window.scrollX || 0,
                    y: window.scrollY || 0,
                    time: Date.now()
                }));
            } catch (error) {
                // Ignore storage failures; the original form/link action should continue normally.
            }
        };

        restoreScroll();

        document.addEventListener('submit', function (event) {
            const form = event.target;

            if (!(form instanceof HTMLFormElement) || form.dataset.noScrollRestore === 'true') {
                return;
            }

            rememberScroll();
        }, true);

        document.addEventListener('click', function (event) {
            const action = event.target.closest('a[href], button[type="submit"], input[type="submit"]');

            if (!action || action.dataset.noScrollRestore === 'true') {
                return;
            }

            if (action.matches('a[href]')) {
                const href = action.getAttribute('href') || '';

                if (
                    !href ||
                    href.startsWith('#') ||
                    href.startsWith('javascript:') ||
                    action.target === '_blank' ||
                    action.closest('.admin-sidebar, .navbar, .pagination')
                ) {
                    return;
                }

                const currentPath = window.location.pathname.replace(/\/+$/, '');
                let targetPath = '';

                try {
                    targetPath = new URL(href, window.location.href).pathname.replace(/\/+$/, '');
                } catch (error) {
                    return;
                }

                if (targetPath !== currentPath && !action.hasAttribute('onclick')) {
                    return;
                }
            }

            rememberScroll();
        }, true);
    });
</script>
@endif

@if($showNotifications)
    @include('notifications.popup')
@endif
@if($showAiChatbot)
    @include('partials.ai-chatbot')
@endif

@stack('scripts')
</body>
</html>
