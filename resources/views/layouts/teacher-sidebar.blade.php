<div class="admin-sidebar col-md-2 col-lg-2 min-vh-100 p-0">
    <div class="sidebar-title mb-4">
        <span class="sidebar-title-icon"><i class="fa-solid fa-chalkboard-user"></i></span>
        <span><small>Workspace</small><h4>STEM Engineer</h4></span>
    </div>

    <a href="{{ route('teacher.dashboard') }}"
       class="sidebar-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
        <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
    </a>

    <a href="{{ route('teacher.sessions') }}"
       class="sidebar-link {{ request()->routeIs('teacher.sessions') || request()->routeIs('teacher.classes') || request()->routeIs('teacher.pending-sessions') || request()->routeIs('teacher.content') || request()->routeIs('teacher.ai-prep*') || request()->routeIs('teacher.session.content') ? 'active' : '' }}">
        <i class="fa-solid fa-calendar-check"></i><span>Sessions</span>
    </a>

    <a href="{{ route('teacher.assessments.hub') }}"
       class="sidebar-link {{ request()->routeIs('teacher.assessments.hub') || request()->routeIs('teacher.assessments*') || request()->routeIs('assessment.review') ? 'active' : '' }}">
        <i class="fa-solid fa-file-pen"></i><span>Assessments</span>
    </a>

    <a href="{{ route('teacher.students.hub') }}"
       class="sidebar-link {{ request()->routeIs('teacher.students.hub') || request()->routeIs('teacher.student-management') || request()->routeIs('teacher.students.*') || request()->routeIs('teacher.results') || request()->routeIs('teacher.student.profiles*') || request()->routeIs('teacher.certificates') ? 'active' : '' }}">
        <i class="fa-solid fa-user-graduate"></i><span>Students</span>
    </a>

    <a href="{{ route('teacher.my-space') }}"
       class="sidebar-link {{ request()->routeIs('teacher.my-space') ? 'active' : '' }}">
        <i class="fa-solid fa-lightbulb"></i><span>My Space</span>
    </a>

    <a href="{{ route('teacher.achievements') }}"
       class="sidebar-link {{ request()->routeIs('teacher.achievements') ? 'active' : '' }}">
        <i class="fa-solid fa-trophy"></i><span>My Achievements</span>
    </a>

    <a href="{{ route('teacher.profile') }}"
       class="sidebar-link {{ request()->routeIs('teacher.profile') ? 'active' : '' }}">
        <i class="fa-solid fa-user"></i><span>Profile</span>
    </a>

    <a href="{{ route('teacher.change.password') }}"
       class="sidebar-link {{ request()->routeIs('teacher.change.password') ? 'active' : '' }}">
        <i class="fa-solid fa-key"></i><span>Change Password</span>
    </a>

    <a href="{{ route('teacher.feedback') }}"
       class="sidebar-link {{ request()->routeIs('teacher.feedback*') ? 'active' : '' }}">
        <i class="fa-solid fa-comment-dots"></i><span>Feedback</span>
    </a>

    <a href="{{ route('teacher.notifications') }}"
       class="sidebar-link {{ request()->routeIs('teacher.notifications') ? 'active' : '' }}">
        <i class="fa-solid fa-bell"></i><span>Notifications</span>
    </a>
</div>
