<!DOCTYPE html>
<html>
<head>
    <title>LMS System</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ file_exists(public_path('css/style.css')) ? filemtime(public_path('css/style.css')) : time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

@if(
    request()->routeIs('home') ||
    request()->routeIs('portal') ||
    request()->routeIs('admin.login') ||
    request()->routeIs('teacher.login') ||
    request()->routeIs('student.login') ||
    request()->routeIs('independent.register') ||
    request()->routeIs('independent.login') ||
    request()->routeIs('coming.soon') ||
    request()->routeIs('admin.institute.register')
)
    <div id="particles-js"></div>
@endif

@if(!request()->routeIs('home') &&
    !request()->routeIs('portal') &&
    !request()->routeIs('admin.login') &&
    !request()->routeIs('teacher.login') &&
    !request()->routeIs('student.login') &&
    !request()->routeIs('independent.register') &&
    !request()->routeIs('independent.login') &&
    !request()->routeIs('coming.soon') &&
    !request()->routeIs('admin.institute.register'))

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">

        <a class="navbar-brand fw-bold text-primary"
           href="
            @if(session('user_role') == 'Admin')
                {{ route('admin.dashboard') }}
            @elseif(session('user_role') == 'Teacher')
                {{ route('teacher.dashboard') }}
            @elseif(session('student_id'))
                {{ route('student.dashboard') }}
            @elseif(session('independent_learner_id'))
                {{ route('independent.dashboard') }}
            @else
                {{ route('home') }}
            @endif
            ">
            InnovatEdge Panel
        </a>

        <div class="ms-auto d-flex align-items-center gap-3">

            @if(
                session('user_name') ||
                session('student_name') ||
                session('independent_learner_name')
            )
                <span class="text-muted fw-semibold">

                    {{ session('user_name')
                        ?? session('student_name')
                        ?? session('independent_learner_name') }}

                    @if(session('user_role') == 'Admin')

                        (Admin)

                    @elseif(session('user_role') == 'InstituteAdmin')

                        (Institute Admin)

                    @elseif(session('user_role') == 'Teacher')

                        (STEM Engineer)

                    @elseif(session('student_id'))

                        (Student)

                    @elseif(session('independent_learner_id'))

                        (Hybrid Learner)

                    @endif

                </span>
            @endif

            @if(
                session()->has('user_id') ||
                session()->has('student_id') ||
                session()->has('independent_learner_id')
            )
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

@include('notifications.popup')

</body>
</html>
