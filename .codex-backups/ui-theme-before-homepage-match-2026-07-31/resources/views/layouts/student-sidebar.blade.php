<div class="admin-sidebar col-md-2 col-lg-2 min-vh-100 p-3">
    <div class="sidebar-title mb-4">
        <span class="sidebar-title-icon"><i class="fa-solid fa-user-graduate"></i></span>
        <span><small>Workspace</small><h4>Student Panel</h4></span>
    </div>

    <a href="{{ route('student.dashboard') }}" class="sidebar-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
        <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
    </a>

    <a href="{{ route('student.content') }}" class="sidebar-link {{ request()->routeIs('student.content') ? 'active' : '' }}">
        <i class="fa-solid fa-book-open"></i><span>Learning Content</span>
    </a>

    <a href="{{ route('student.assessment') }}" class="sidebar-link {{ request()->routeIs('student.assessment') ? 'active' : '' }}">
        <i class="fa-solid fa-clipboard-check"></i><span>Take Assessment</span>
    </a>

    <a href="{{ route('student.history') }}" class="sidebar-link {{ request()->routeIs('student.history') ? 'active' : '' }}">
        <i class="fa-solid fa-clock-rotate-left"></i><span>Assessment History</span>
    </a>

    <a href="{{ route('student.badges') }}" class="sidebar-link {{ request()->routeIs('student.badges') ? 'active' : '' }}">
        <i class="fa-solid fa-trophy"></i><span>Achievements</span>
    </a>

    <a href="{{ route('student.my-space') }}" class="sidebar-link {{ request()->routeIs('student.my-space') ? 'active' : '' }}">
        <i class="fa-solid fa-lightbulb"></i><span>My Space</span>
    </a>

    <a href="{{ route('student.profile') }}" class="sidebar-link {{ request()->routeIs('student.profile') ? 'active' : '' }}">
        <i class="fa-solid fa-user"></i><span>Profile</span>
    </a>

    <a href="{{ route('student.feedback') }}" class="sidebar-link {{ request()->routeIs('student.feedback*') ? 'active' : '' }}">
        <i class="fa-solid fa-comment-dots"></i><span>Feedback</span>
    </a>

    <a href="{{ route('student.notifications') }}" class="sidebar-link {{ request()->routeIs('student.notifications') ? 'active' : '' }}">
        <i class="fa-solid fa-bell"></i><span>Notifications</span>
    </a>

</div>
