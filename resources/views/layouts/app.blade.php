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
        $seoUrl = url()->current();
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
        $panelHomeUrl = route('home');

        if (session('user_role') == 'Admin') {
            $panelHomeUrl = route('admin.dashboard');
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
    <link rel="icon" type="image/png" href="{{ asset('images/InnovatEdgeLogo.png') }}">

    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ file_exists(public_path('css/style.css')) ? filemtime(public_path('css/style.css')) : time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @if($seoIsPublic)
        <script type="application/ld+json">
            {
                "@@context": "https://schema.org",
                "@type": "Organization",
                "name": "InnovatEdge",
                "url": "https://tinkedge.tech",
                "logo": "https://tinkedge.tech/images/InnovatEdgeLogo.png",
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

<body>

@if($showParticles)
    <div id="particles-js"></div>
@endif

@if($showPanelNav)

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">

        <a class="navbar-brand fw-bold text-primary"
           href="{{ $panelHomeUrl }}">
            InnovatEdge Panel
        </a>

        <div class="ms-auto d-flex align-items-center gap-3">

            @if($panelUserName)
                <span class="text-muted fw-semibold">

                    {{ $panelUserName }}

                    @if($panelUserRoleLabel)
                        ({{ $panelUserRoleLabel }})
                    @endif

                </span>
            @endif

            @if($showLogout)
                <a href="{{ route('logout') }}"
                   class="btn btn-sm btn-outline-danger">
                    Logout
                </a>
            @endif

        </div>

    </nav>

@endif

@yield('content')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
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
@include('partials.ai-chatbot')

</body>
</html>
