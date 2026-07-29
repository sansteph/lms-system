<div class="admin-sidebar col-md-2 col-lg-2 min-vh-100 p-3">
    <div class="sidebar-title mb-4">
        <h4>Admin Panel</h4>
    </div>

    <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        Dashboard
    </a>

    <a href="{{ route('admin.change.password') }}" class="sidebar-link {{ request()->routeIs('admin.change.password') ? 'active' : '' }}">
        Change Password
    </a>

    <a href="{{ route('admin.management') }}" class="sidebar-link {{ request()->routeIs('admin.management') || request()->routeIs('users') || request()->routeIs('students') || request()->routeIs('classes') || request()->routeIs('courses*') || request()->routeIs('teaching-plans*') || request()->routeIs('institutes') ? 'active' : '' }}">
        Management
    </a>

    <a href="{{ route('admin.reports.hub') }}" class="sidebar-link {{ request()->routeIs('admin.reports.hub') || request()->routeIs('admin.class-session.report*') || request()->routeIs('reports.*') ? 'active' : '' }}">
        Reports
    </a>

    <a href="{{ route('admin.approvals') }}" class="sidebar-link {{ request()->routeIs('admin.approvals') || request()->routeIs('admin.question-papers*') || request()->routeIs('admin.certificates') || request()->routeIs('admin.my-space*') || request()->routeIs('admin.teacher-achievements*') || request()->routeIs('admin.achievements') ? 'active' : '' }}">
        Approvals
    </a>

    <a href="{{ route('admin.monitoring') }}" class="sidebar-link {{ request()->routeIs('admin.monitoring') || request()->routeIs('admin.activity.monitoring') || request()->routeIs('admin.assessment.monitoring') || request()->routeIs('admin.assessment.review.monitoring') ? 'active' : '' }}">
        Monitoring
    </a>

    <a href="{{ route('notifications') }}" class="sidebar-link {{ request()->routeIs('notifications*') ? 'active' : '' }}">
        Notifications
    </a>

    @if(session('user_role') == 'Admin')
        <a href="{{ route('admin.independent.learners') }}" class="sidebar-link {{ request()->routeIs('admin.independent.learners*') ? 'active' : '' }}">
            Hybrid Learners
        </a>
    @endif
</div>
