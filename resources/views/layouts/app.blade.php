<!DOCTYPE html>
<html>
<head>
    <title>LMS System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
    <a class="navbar-brand fw-bold text-primary" href="{{ route('admin.dashboard') }}">
        LMS Panel
    </a>

    <div class="ms-auto">
        <a href="{{ route('admin.login') }}" class="btn btn-sm btn-outline-danger">Logout</a>
    </div>
</nav>

@yield('content')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>