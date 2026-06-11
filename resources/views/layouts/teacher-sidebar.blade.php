<div class="admin-sidebar col-md-2 col-lg-2 min-vh-100 p-3">
    <div class="sidebar-title mb-4">
        <h4>STEM Engineer Panel</h4>
    </div>

    <a href="{{ route('teacher.dashboard') }}"
       class="sidebar-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
        Dashboard
    </a>

    <a href="{{ route('teacher.classes') }}"
    class="sidebar-link {{ request()->routeIs('teacher.classes') ? 'active' : '' }}">
        My Classes
    </a>

    <a href="{{ route('teacher.content') }}"
    class="sidebar-link {{ request()->routeIs('teacher.content') ? 'active' : '' }}">
        Learning Content
    </a>

    <a href="{{ route('teacher.assessments') }}"
    class="sidebar-link {{ request()->routeIs('teacher.assessments') ? 'active' : '' }}">
        Assessments
    </a>

    <a href="{{ route('teacher.reports') }}"
    class="sidebar-link {{ request()->routeIs('teacher.reports') ? 'active' : '' }}">
        Reports
    </a>

    <a href="{{ route('teacher.results') }}"
    class="sidebar-link {{ request()->routeIs('teacher.results') ? 'active' : '' }}">
        Student Results
    </a>

    <a href="{{ route('teacher.student.profiles') }}"
    class="sidebar-link {{ request()->routeIs('teacher.student.profiles') ? 'active' : '' }}">
        Student Details
    </a>
    <a href="{{ route('teacher.certificates') }}"
    class="sidebar-link {{ request()->routeIs('teacher.certificates') ? 'active' : '' }}">
        Certificates
    </a>

    <a href="{{ route('teacher.notifications') }}"
    class="sidebar-link {{ request()->routeIs('teacher.notifications') ? 'active' : '' }}">
        Notifications
    </a>

    <a href="{{ route('teacher.my-space') }}"
    class="sidebar-link {{ request()->routeIs('teacher.my-space') ? 'active' : '' }}">
        My Space
    </a>

    <a href="{{ route('teacher.profile') }}"
    class="sidebar-link {{ request()->routeIs('teacher.profile') ? 'active' : '' }}">
        Profile
    </a>

    <a href="{{ route('assessment.review') }}" class="sidebar-link">
        Assessment Review
    </a>

    <a href="{{ route('assessment.review') }}"class="sidebar-link {{ request()->routeIs('assessment.review') ? 'active' : '' }}">
        Assessment Evaluation
    </a>
</div>