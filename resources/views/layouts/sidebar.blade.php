<div class="col-md-3 col-lg-2 bg-dark text-white min-vh-100 p-3">
    <h4 class="mb-4">Admin Panel</h4>

    <a href="{{ route('admin.dashboard') }}" 
       class="d-block {{ request()->routeIs('admin.dashboard') ? 'text-warning' : 'text-white' }} text-decoration-none mb-3">
        Dashboard
    </a>

    <a href="{{ route('users') }}" 
        class="d-block {{ request()->routeIs('users') ? 'text-warning' : 'text-white' }} text-decoration-none mb-3">
        User Management
    </a>

    <a href="{{ route('institutes') }}" 
        class="d-block {{ request()->routeIs('institutes') ? 'text-warning' : 'text-white' }} text-decoration-none mb-3">
        Institute Management
    </a>

    <a href="{{ route('classes') }}" 
       class="d-block {{ request()->routeIs('classes') ? 'text-warning' : 'text-white' }} text-decoration-none mb-3">
        Class Management
    </a>

    <a href="{{ route('students') }}" 
       class="d-block {{ request()->routeIs('students') ? 'text-warning' : 'text-white' }} text-decoration-none mb-3">
        Student Management
    </a>

    <a href="{{ route('content') }}" 
       class="d-block {{ request()->routeIs('content') ? 'text-warning' : 'text-white' }} text-decoration-none mb-3">
        Content Management
    </a>

    <a href="{{ route('assessments') }}" 
       class="d-block {{ request()->routeIs('assessments') ? 'text-warning' : 'text-white' }} text-decoration-none mb-3">
        Assessment Management
    </a>

    <a href="{{ route('reports') }}" 
        class="d-block {{ request()->routeIs('reports') ? 'text-warning' : 'text-white' }} text-decoration-none mb-3">
        Reports
    </a>

    <a href="{{ route('notifications') }}" 
        class="d-block {{ request()->routeIs('notifications') ? 'text-warning' : 'text-white' }} text-decoration-none mb-3">
        Notifications
    </a>    
</div>

