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

    <a href="{{ route('teaching-plans') }}"class="sidebar-link {{ request()->routeIs('teaching-plans*') ? 'active' : '' }}">
        Teaching Plans
    </a>

    <a href="{{ route('courses') }}"class="sidebar-link {{ request()->routeIs('courses*') ? 'active' : '' }}">
        Courses Management
    </a>

    <a href="{{ route('students') }}" class="sidebar-link {{ request()->routeIs('students') ? 'active' : '' }}">
        Student Management
    </a>

    <a href="{{ route('reports') }}" class="sidebar-link {{ request()->routeIs('reports') ? 'active' : '' }}">
        Reports
    </a>

    <a href="{{ route('admin.analytics') }}" class="sidebar-link {{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
        Analytics
    </a>

    <a href="{{ route('admin.certificates') }}"class="sidebar-link {{ request()->routeIs('admin.certificates') ? 'active' : '' }}">
        Certificates
    </a>

    <a href="{{ route('admin.achievements') }}"class="sidebar-link {{ request()->routeIs('admin.achievements') ? 'active' : '' }}">
        Student Achievements
    </a>

    <a href="{{ route('admin.my-space') }}"class="sidebar-link {{ request()->routeIs('admin.my-space*') ? 'active' : '' }}">
        My Space Review
    </a>

    <a href="{{ route('admin.assessment.monitoring') }}" class="sidebar-link {{ request()->routeIs('admin.assessment.monitoring') ? 'active' : '' }}">
        Assessment Monitoring
    </a>

    <a href="{{ route('admin.question-papers') }}" class="sidebar-link {{ request()->routeIs('admin.question-papers*') ? 'active' : '' }}">
        Question Paper Approval
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

        <a href="{{ route('admin.institute.requests') }}"class="sidebar-link {{ request()->routeIs('admin.institute.requests') ? 'active' : '' }}">
            Institute Requests
        </a>

        <a href="{{ route('admin.independent.learners') }}"class="sidebar-link {{ request()->routeIs('admin.independent.learners*') ? 'active' : '' }}">
            Hybrid Learners
        </a>

    @endif

    <a href="{{ route('admin.assessment.review.monitoring') }}"class="sidebar-link {{ request()->routeIs('admin.assessment.review.monitoring') ? 'active' : '' }}">
        Assessment Review Monitoring
    </a>

</div>
