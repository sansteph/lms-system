<div class="admin-sidebar col-md-2 col-lg-2 min-vh-100 p-3">
    <div class="sidebar-title mb-4">
        <h4>Student Panel</h4>
    </div>

    <a href="{{ route('student.dashboard') }}"
       class="sidebar-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
        Dashboard
    </a>

    <a href="{{ route('student.content') }}"
        class="sidebar-link">
        Learning Content
    </a>

    <a href="{{ route('student.assessment') }}"
        class="sidebar-link {{ request()->routeIs('student.assessment') ? 'active' : '' }}">
        Take Assessment
    </a>

    <a href="{{ route('student.history') }}"
    class="sidebar-link {{ request()->routeIs('student.history') ? 'active' : '' }}">
        Assessment History
    </a>

    <a href="{{ route('student.badges') }}"
    class="sidebar-link {{ request()->routeIs('student.badges') ? 'active' : '' }}">
        Achievements
    </a>

    <a href="{{ route('student.notifications') }}"
    class="sidebar-link {{ request()->routeIs('student.notifications') ? 'active' : '' }}">
        Notifications
    </a>

    <a href="{{ route('student.profile') }}"
    class="sidebar-link {{ request()->routeIs('student.profile') ? 'active' : '' }}">
        Profile
    </a>

</div>