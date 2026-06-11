<div class="admin-sidebar col-md-2 col-lg-2 min-vh-100 p-3">
    <div class="sidebar-title mb-4">
        <h4>Admin Panel</h4>
    </div>

    <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        Dashboard
    </a>

    <a href="{{ route('admin.change.password') }}" class="sidebar-link">
        Change Password
    </a>

    <a href="{{ route('users') }}" class="sidebar-link {{ request()->routeIs('users') ? 'active' : '' }}">
        STEM Engineers Management
    </a>

    <a href="{{ route('classes') }}" class="sidebar-link {{ request()->routeIs('classes') ? 'active' : '' }}">
        Class Management
    </a>

    <a href="{{ route('courses') }}"class="sidebar-link {{ request()->routeIs('courses*') ? 'active' : '' }}">
        Courses Management
    </a>

    <a href="{{ route('content') }}" class="sidebar-link {{ request()->routeIs('content') ? 'active' : '' }}">
        Content Management
    </a>

    <a href="{{ route('students') }}" class="sidebar-link {{ request()->routeIs('students') ? 'active' : '' }}">
        Student Management
    </a>

    <a href="{{ route('assessments') }}" class="sidebar-link {{ request()->routeIs('assessments') ? 'active' : '' }}">
        Assessment Management
    </a>
    <a href="{{ route('assessment-questions') }}" class="sidebar-link {{ request()->routeIs('assessment-questions') ? 'active' : '' }}">
        Assessment Questions
    </a>

    <a href="{{ route('reports') }}" class="sidebar-link {{ request()->routeIs('reports') ? 'active' : '' }}">
        Reports
    </a>

    <a href="{{ route('admin.analytics') }}" class="sidebar-link {{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
        Analytics
    </a>

    <a href="{{ route('notifications') }}" class="sidebar-link {{ request()->routeIs('notifications') ? 'active' : '' }}">
        Notifications
    </a>

    <a href="{{ route('admin.certificates') }}"class="sidebar-link {{ request()->routeIs('admin.certificates') ? 'active' : '' }}">
        Certificates
    </a>

    <a href="{{ route('admin.achievements') }}"class="sidebar-link">
        Student Achievements
    </a>

    <a href="{{ route('admin.my-space') }}"class="sidebar-link {{ request()->routeIs('admin.my-space*') ? 'active' : '' }}">
        My Space Review
    </a>

    <a href="{{ route('admin.assessment.monitoring') }}" class="sidebar-link {{ request()->routeIs('admin.assessment.monitoring') ? 'active' : '' }}">
        Assessment Monitoring
    </a>

    <a href="{{ route('admin.class-session.report') }}"class="sidebar-link {{ request()->routeIs('admin.class-session.report') ? 'active' : '' }}">
        Class Session Report
    </a>

    @if(session('user_role') == 'Admin')

        <a href="{{ route('admin.activity.monitoring') }}"class="sidebar-link {{ request()->routeIs('admin.activity.monitoring') ? 'active' : '' }}">
            Activity Monitoring
        </a>

        <a href="{{ route('institutes') }}" class="sidebar-link {{ request()->routeIs('institutes') ? 'active' : '' }}">
            Institute Management
        </a>

        <a href="{{ route('admin.institute.requests') }}"class="sidebar-link">
            Institute Requests
        </a>

        <a href="{{ route('admin.independent.learners') }}"class="sidebar-link {{ request()->routeIs('admin.independent.learners*') ? 'active' : '' }}">
            Hybrid Learners
        </a>

    @endif

    <a href="{{ route('assessment.review') }}"class="sidebar-link">
        Assessment Review
    </a>

</div>