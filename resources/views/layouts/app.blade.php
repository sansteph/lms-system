<!DOCTYPE html>
<html>
<head>
    <title>LMS System</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
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
    !request()->routeIs('admin.institute.register') &&
    !request()->routeIs('coordinator.login'))

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
            @elseif(session('user_role') == 'Coordinator')
                {{ route('coordinator.dashboard') }}
            @else
                {{ route('home') }}
            @endif
            ">
            TinkEdge Learning Panel
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

                    @elseif(session('user_role') == 'Coordinator')

                        (Coordinator)

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

</body>
</html>
