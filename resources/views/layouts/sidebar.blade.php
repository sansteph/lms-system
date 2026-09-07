@php
    $panelRole = session('user_role');
    $isManager = $panelRole === 'Manager';
    $isPrincipal = $panelRole === 'Principal';
    $dashboardRoute = $isManager ? route('manager.dashboard') : ($isPrincipal ? route('principal.dashboard') : route('admin.dashboard'));
    $reportsRoute = $isManager ? route('manager.reports.hub') : ($isPrincipal ? route('principal.reports.hub') : route('admin.reports.hub'));
    $changePasswordRoute = $isPrincipal ? route('principal.change.password') : route('admin.change.password');
    $feedbackRoute = $isPrincipal ? route('principal.feedback') : route('manager.feedback');
    $workspaceTitle = $isManager ? 'Manager Panel' : ($isPrincipal ? 'Principal Panel' : 'Admin Panel');
@endphp

<div class="admin-sidebar col-md-2 col-lg-2 min-vh-100 p-0">
    <div class="sidebar-title mb-4">
        <span class="sidebar-title-icon"><i class="fa-solid fa-shield-halved"></i></span>
        <span><small>Workspace</small><h4>{{ $workspaceTitle }}</h4></span>
    </div>

    <a href="{{ $dashboardRoute }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') || request()->routeIs('manager.dashboard') || request()->routeIs('principal.dashboard') ? 'active' : '' }}">
        <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
    </a>

    <a href="{{ $changePasswordRoute }}" class="sidebar-link {{ request()->routeIs('admin.change.password') || request()->routeIs('principal.change.password') ? 'active' : '' }}">
        <i class="fa-solid fa-key"></i><span>Change Password</span>
    </a>

    @php($mfaRoute = $isPrincipal ? route('principal.mfa.settings') : route('admin.mfa.settings'))
    <a href="{{ $mfaRoute }}" class="sidebar-link {{ request()->routeIs('admin.mfa.*') || request()->routeIs('principal.mfa.*') ? 'active' : '' }}">
        <i class="fa-solid fa-shield-halved"></i><span>Two-Factor Authentication</span>
    </a>

    @unless($isManager || $isPrincipal)
        <a href="{{ route('admin.management') }}" class="sidebar-link {{ request()->routeIs('admin.management') || request()->routeIs('users') || request()->routeIs('students') || request()->routeIs('classes') || request()->routeIs('courses*') || request()->routeIs('teaching-plans*') || request()->routeIs('institutes') || request()->routeIs('principals*') ? 'active' : '' }}">
            <i class="fa-solid fa-layer-group"></i><span>Management</span>
        </a>
    @endunless

    <a href="{{ $reportsRoute }}" class="sidebar-link {{ request()->routeIs('admin.reports.hub') || request()->routeIs('manager.reports.hub') || request()->routeIs('principal.reports.hub') || request()->routeIs('admin.class-session.report*') || request()->routeIs('manager.class-session.report*') || request()->routeIs('principal.class-session.report*') || request()->routeIs('reports.*') || request()->routeIs('manager.reports.*') || request()->routeIs('principal.reports.*') ? 'active' : '' }}">
        <i class="fa-solid fa-chart-column"></i><span>Reports</span>
    </a>

    @if($isManager || $isPrincipal)
        <a href="{{ $feedbackRoute }}" class="sidebar-link {{ request()->routeIs('manager.feedback*') || request()->routeIs('principal.feedback*') ? 'active' : '' }}">
            <i class="fa-solid fa-comment-dots"></i><span>Feedback</span>
        </a>
    @endif

    @unless($isPrincipal)
        <a href="{{ route('admin.approvals') }}" class="sidebar-link {{ request()->routeIs('admin.approvals') || request()->routeIs('admin.question-papers*') || request()->routeIs('admin.certificates') || request()->routeIs('admin.my-space*') || request()->routeIs('admin.teacher-achievements*') || request()->routeIs('admin.achievements') ? 'active' : '' }}">
            <i class="fa-solid fa-circle-check"></i><span>Approvals</span>
        </a>
    @endunless

    @unless($isManager || $isPrincipal)
        <a href="{{ route('admin.monitoring') }}" class="sidebar-link {{ request()->routeIs('admin.monitoring') || request()->routeIs('admin.activity.monitoring') || request()->routeIs('admin.assessment.monitoring') || request()->routeIs('admin.assessment.review.monitoring') ? 'active' : '' }}">
            <i class="fa-solid fa-display"></i><span>Monitoring</span>
        </a>
    @endunless

    @unless($isPrincipal)
        <a href="{{ route('notifications') }}" class="sidebar-link {{ request()->routeIs('notifications*') ? 'active' : '' }}">
            <i class="fa-solid fa-bell"></i><span>Notifications</span>
        </a>
    @endunless

    @if(session('user_role') == 'Admin')
        <a href="{{ route('admin.independent.learners') }}" class="sidebar-link {{ request()->routeIs('admin.independent.learners*') ? 'active' : '' }}">
            <i class="fa-solid fa-laptop-code"></i><span>Hybrid Learners</span>
        </a>
    @endif
</div>
