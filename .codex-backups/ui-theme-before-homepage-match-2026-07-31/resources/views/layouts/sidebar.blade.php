<div class="admin-sidebar col-md-2 col-lg-2 min-vh-100 p-3">
    <div class="sidebar-title mb-4">
        <span class="sidebar-title-icon"><i class="fa-solid fa-shield-halved"></i></span>
        <span><small>Workspace</small><h4>Admin Panel</h4></span>
    </div>

    <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
    </a>

    <a href="{{ route('admin.change.password') }}" class="sidebar-link {{ request()->routeIs('admin.change.password') ? 'active' : '' }}">
        <i class="fa-solid fa-key"></i><span>Change Password</span>
    </a>

    <a href="{{ route('admin.management') }}" class="sidebar-link {{ request()->routeIs('admin.management') || request()->routeIs('users') || request()->routeIs('students') || request()->routeIs('classes') || request()->routeIs('courses*') || request()->routeIs('teaching-plans*') || request()->routeIs('institutes') ? 'active' : '' }}">
        <i class="fa-solid fa-layer-group"></i><span>Management</span>
    </a>

    <a href="{{ route('admin.reports.hub') }}" class="sidebar-link {{ request()->routeIs('admin.reports.hub') || request()->routeIs('admin.class-session.report*') || request()->routeIs('reports.*') ? 'active' : '' }}">
        <i class="fa-solid fa-chart-column"></i><span>Reports</span>
    </a>

    <a href="{{ route('admin.approvals') }}" class="sidebar-link {{ request()->routeIs('admin.approvals') || request()->routeIs('admin.question-papers*') || request()->routeIs('admin.certificates') || request()->routeIs('admin.my-space*') || request()->routeIs('admin.teacher-achievements*') || request()->routeIs('admin.achievements') ? 'active' : '' }}">
        <i class="fa-solid fa-circle-check"></i><span>Approvals</span>
    </a>

    <a href="{{ route('admin.monitoring') }}" class="sidebar-link {{ request()->routeIs('admin.monitoring') || request()->routeIs('admin.activity.monitoring') || request()->routeIs('admin.assessment.monitoring') || request()->routeIs('admin.assessment.review.monitoring') ? 'active' : '' }}">
        <i class="fa-solid fa-display"></i><span>Monitoring</span>
    </a>

    <a href="{{ route('notifications') }}" class="sidebar-link {{ request()->routeIs('notifications*') ? 'active' : '' }}">
        <i class="fa-solid fa-bell"></i><span>Notifications</span>
    </a>

    @if(session('user_role') == 'Admin')
        <a href="{{ route('admin.independent.learners') }}" class="sidebar-link {{ request()->routeIs('admin.independent.learners*') ? 'active' : '' }}">
            <i class="fa-solid fa-laptop-code"></i><span>Hybrid Learners</span>
        </a>
    @endif
</div>
