<!DOCTYPE html>
<html>
<head>
    <title>LMS System</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    @if(
        request()->routeIs('home') ||
        request()->routeIs('admin.login') ||
        request()->routeIs('teacher.login') ||
        request()->routeIs('student.login')
    )

        <div id="particles-js"></div>

    @endif

    @if(!request()->routeIs('home') &&
        !request()->routeIs('admin.login') &&
        !request()->routeIs('teacher.login') &&
        !request()->routeIs('student.login'))

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">

        <a class="navbar-brand fw-bold text-primary"
        href="
            @if(session('user_role') == 'Admin')
                {{ route('admin.dashboard') }}
            @elseif(session('user_role') == 'Teacher')
                {{ route('teacher.dashboard') }}
            @elseif(session('student_id'))
                {{ route('student.dashboard') }}
            @else
                {{ route('home') }}
            @endif
            ">
            LMS Panel
        </a>

        <div class="ms-auto d-flex align-items-center gap-3">

            @if(session('user_name') || session('student_name'))
                <span class="text-muted fw-semibold">
                    {{ session('user_name') ?? session('student_name') }}

                    @if(session('user_role'))
                        ({{ session('user_role') }})
                    @elseif(session('student_id'))
                        (Student)
                    @endif
                </span>
            @endif

            @if(session()->has('user_id') || session()->has('student_id'))
                <a href="{{ route('logout') }}" class="btn btn-sm btn-outline-danger">
                    Logout
                </a>
            @endif

        </div>

    </nav>

    @endif
    <div class="page-transition">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2/tsparticles.bundle.min.js"></script>
    @if(
        request()->routeIs('home') ||
        request()->routeIs('admin.login') ||
        request()->routeIs('teacher.login') ||
        request()->routeIs('student.login')
    )
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        tsParticles.load("particles-js", {
            background: { color: "transparent" },
            fpsLimit: 60,
            particles: {
                number: {
                    value: 55
                },

                color: {
                    value: [
                        "#2563eb",
                        "#4f46e5",
                        "#7c3aed",
                        "#0ea5e9"
                    ]
                },

                shape: {
                    type: "circle"
                },

                opacity: {
                    value: 0.38
                },

                size: {
                    value: {
                        min: 2,
                        max: 5
                    }
                },

                links: {
                    enable: true,
                    color: "#93c5fd",
                    distance: 150,
                    opacity: 0.28,
                    width: 1.2
                },

                move: {
                    enable: true,
                    speed: 1.3,
                    direction: "none",
                    random: false,
                    straight: false,
                    outModes: {
                        default: "bounce"
                    }
                }

            },
            detectRetina: true
        });
    });
    </script>
    @endif

</body>
</html>