<div class="admin-sidebar col-md-2 col-lg-2 min-vh-100 p-3">
    <div class="sidebar-title mb-4">
        <h4>STEM Engineer Panel</h4>
    </div>

    <a href="{{ route('teacher.dashboard') }}"
       class="sidebar-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
        Dashboard
    </a>

    <a href="{{ route('teacher.sessions') }}"
       class="sidebar-link {{ request()->routeIs('teacher.sessions') || request()->routeIs('teacher.classes') || request()->routeIs('teacher.pending-sessions') || request()->routeIs('teacher.content') || request()->routeIs('teacher.ai-prep*') || request()->routeIs('teacher.session.content') ? 'active' : '' }}">
        Sessions
    </a>

    <a href="{{ route('teacher.assessments.hub') }}"
       class="sidebar-link {{ request()->routeIs('teacher.assessments.hub') || request()->routeIs('teacher.assessments*') || request()->routeIs('assessment.review') ? 'active' : '' }}">
        Assessments
    </a>

    <a href="{{ route('teacher.students.hub') }}"
       class="sidebar-link {{ request()->routeIs('teacher.students.hub') || request()->routeIs('teacher.results') || request()->routeIs('teacher.student.profiles*') || request()->routeIs('teacher.certificates') ? 'active' : '' }}">
        Students
    </a>

    <a href="{{ route('teacher.my-space') }}"
       class="sidebar-link {{ request()->routeIs('teacher.my-space') ? 'active' : '' }}">
        My Space
    </a>

    <a href="{{ route('teacher.achievements') }}"
       class="sidebar-link {{ request()->routeIs('teacher.achievements') ? 'active' : '' }}">
        My Achievements
    </a>

    <a href="{{ route('teacher.profile') }}"
       class="sidebar-link {{ request()->routeIs('teacher.profile') ? 'active' : '' }}">
        Profile
    </a>

    <a href="{{ route('teacher.change.password') }}"
       class="sidebar-link {{ request()->routeIs('teacher.change.password') ? 'active' : '' }}">
        Change Password
    </a>

    <a href="{{ route('teacher.feedback') }}"
       class="sidebar-link {{ request()->routeIs('teacher.feedback*') ? 'active' : '' }}">
        Feedback
    </a>

    <a href="{{ route('teacher.notifications') }}"
       class="sidebar-link {{ request()->routeIs('teacher.notifications') ? 'active' : '' }}">
        Notifications
    </a>
</div>
