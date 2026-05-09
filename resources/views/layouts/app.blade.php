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

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">

    <a class="navbar-brand fw-bold text-primary"
       href="{{ session('user_role') == 'Teacher' ? route('teacher.dashboard') : route('admin.dashboard') }}">

        LMS Panel

    </a>

    <div class="ms-auto d-flex align-items-center gap-3">

        @if(session('user_name'))

            <span class="text-muted fw-semibold">
                {{ session('user_name') }}
                ({{ session('user_role') }})
            </span>

        @endif

        @if(session()->has('user_id'))

            <a href="{{ route('logout') }}"
               class="btn btn-sm btn-outline-danger">

                Logout

            </a>

        @endif

    </div>

</nav>

@yield('content')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>