<div class="admin-sidebar col-md-2 col-lg-2 min-vh-100 p-3">

    <div class="sidebar-title mb-4">
        <h4>Coordinator Panel</h4>
    </div>

    <a href="{{ route('coordinator.dashboard') }}"
       class="sidebar-link {{ request()->routeIs('coordinator.dashboard') ? 'active' : '' }}">
        Dashboard
    </a>

    <a href="{{ route('coordinator.live-sessions') }}"
       class="sidebar-link {{ request()->routeIs('coordinator.live-sessions') ? 'active' : '' }}">
        Live Sessions
    </a>

    <a href="{{ route('coordinator.daily-report') }}"
       class="sidebar-link {{ request()->routeIs('coordinator.daily-report') ? 'active' : '' }}">
        Daily Teaching Report
    </a>

    <a href="{{ route('coordinator.content-tracker') }}"
       class="sidebar-link {{ request()->routeIs('coordinator.content-tracker') ? 'active' : '' }}">
        Content Tracker
    </a>

    <a href="{{ route('coordinator.assessment-monitoring') }}"
       class="sidebar-link {{ request()->routeIs('coordinator.assessment-monitoring') ? 'active' : '' }}">
        Assessment Monitoring
    </a>

</div>
