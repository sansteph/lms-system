@extends('layouts.app')

@section('content')

<div class="modern-homepage">

    <!-- HERO SECTION -->
    <section class="hero-section">

        <div class="hero-bg-gradient hero-glow-left"></div>
        <div class="hero-bg-gradient hero-glow-right"></div>
        <div class="hero-grid"></div>
        <div class="hero-orb hero-orb-one"></div>
        <div class="hero-orb hero-orb-two"></div>

        <div class="container hero-container">

            <!-- TOP BRAND ONLY - NO MENU / NO TOP LOGIN -->
            <div class="hero-topbar">

                <div class="hero-logo">

                    <div class="logo-image-placeholder">
                        <img src="{{ asset('images/TinkEdgeLogo.png') }}" >
                    </div>

                    <div class="logo-text">
                        <h5>TinkEdge LMS</h5>
                        <span>AI Powered Learning</span>
                    </div>

                </div>

            </div>

            <!-- HERO MAIN -->
            <div class="row align-items-center hero-main-row">

                <!-- LEFT CONTENT -->
                <div class="col-xl-5 col-lg-6">

                    <div class="hero-content">

                        <div class="hero-badge">
                            <i class="fa fa-sparkles"></i>
                            AI POWERED LEARNING ECOSYSTEM
                        </div>

                        <h1 class="hero-title">
                            Smart Learning <br>
                            for a <span>Better Future</span>
                        </h1>

                        <p class="hero-description">
                            Empowering institutions, teachers and learners with AI
                            assessments, analytics, certificates and a complete
                            learning management system.
                        </p>

                        <div class="hero-buttons">

                            <a href="{{ route('portal') }}" class="btn hero-btn-primary">
                                Access Portal
                                <i class="fa fa-arrow-right"></i>
                            </a>

                            <a href="#features" class="btn hero-btn-secondary">
                                <i class="fa fa-circle-play"></i>
                                Explore Features
                            </a>

                        </div>

                        <div class="hero-stats">

                            <div class="hero-stat-item">
                                <div class="stat-icon purple">
                                    <i class="fa fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <h4>10K+</h4>
                                    <span>Active Learners</span>
                                </div>
                            </div>

                            <div class="hero-stat-item">
                                <div class="stat-icon blue">
                                    <i class="fa fa-chart-column"></i>
                                </div>
                                <div class="stat-content">
                                    <h4>500+</h4>
                                    <span>Assessments</span>
                                </div>
                            </div>

                            <div class="hero-stat-item">
                                <div class="stat-icon orange">
                                    <i class="fa fa-building-columns"></i>
                                </div>
                                <div class="stat-content">
                                    <h4>120+</h4>
                                    <span>Institutions</span>
                                </div>
                                
                            </div>

                        </div>

                    </div>

                </div>

                <!-- RIGHT DASHBOARD -->
                <div class="col-xl-7 col-lg-6">

                    <div class="dashboard-area">

                        <div class="dashboard-wrapper">

                            <!-- MAIN DASHBOARD -->
                            <div class="dashboard-card-main">

                                <div class="dashboard-header">

                                    <div>
                                        <small>Welcome back, User</small>
                                        <h3>Dashboard Overview</h3>
                                    </div>

                                    <div class="dashboard-profile">
                                        <i class="fa fa-user"></i>
                                        <span></span>
                                    </div>

                                </div>

                                <div class="dashboard-stats-grid">

                                    <div class="dashboard-stat-box">
                                        <span>Overall Progress</span>
                                        <div class="stat-box-row">
                                            <h2>92%</h2>
                                            <i class="fa fa-chart-line"></i>
                                        </div>
                                        <small>
                                            <i class="fa fa-arrow-up"></i>
                                            12% from last month
                                        </small>
                                    </div>

                                    <div class="dashboard-stat-box">
                                        <span>Enrolled Courses</span>
                                        <div class="stat-box-row">
                                            <h2>18</h2>
                                            <div class="mini-book-icon">
                                                <i class="fa fa-book-open"></i>
                                            </div>
                                        </div>
                                        <small>Active learning</small>
                                    </div>

                                </div>

                                <div class="dashboard-course-card">

                                    <div class="course-icon">
                                        <i class="fa fa-brain"></i>
                                    </div>

                                    <div class="course-content">

                                        <div class="course-header">
                                            <div>
                                                <h5>AI & Machine Learning</h5>
                                                <small>75% Completed</small>
                                            </div>
                                            <span>75%</span>
                                        </div>

                                        <div class="course-progress">
                                            <div class="course-progress-fill"></div>
                                        </div>

                                    </div>

                                </div>

                                <div class="dashboard-analytics">

                                    <div class="analytics-top">
                                        <h6>Learning Analytics</h6>
                                        <span>This Week <i class="fa fa-angle-down"></i></span>
                                    </div>

                                    <div class="analytics-body">

                                        <div class="analytics-scale">
                                            <span>100%</span>
                                            <span>50%</span>
                                            <span>0%</span>
                                        </div>

                                        <div class="analytics-chart">

                                            <div class="analytics-bars">
                                                <div class="bar b1"></div>
                                                <div class="bar b2"></div>
                                                <div class="bar b3"></div>
                                                <div class="bar b4"></div>
                                                <div class="bar b5"></div>
                                                <div class="bar b6"></div>
                                                <div class="bar b7"></div>
                                            </div>

                                            <div class="analytics-days">
                                                <span>Mon</span>
                                                <span>Tue</span>
                                                <span>Wed</span>
                                                <span>Thu</span>
                                                <span>Fri</span>
                                                <span>Sat</span>
                                                <span>Sun</span>
                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <!-- FLOATING SIDE CARDS - INSIDE FRAME -->
                            <div class="floating-cards-column">

                                <div class="dashboard-side-card side-card-1">
                                    <i class="fa fa-brain"></i>
                                    <span>AI Evaluation</span>
                                </div>

                                <div class="dashboard-side-card side-card-2">
                                    <i class="fa fa-chart-line"></i>
                                    <span>Live Analytics</span>
                                </div>

                                <div class="dashboard-side-card side-card-3">
                                    <i class="fa fa-certificate"></i>
                                    <span>Smart Certificates</span>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="hero-wave-wrapper">
            <div class="hero-wave wave-1"></div>
            <div class="hero-wave wave-2"></div>
            <div class="hero-wave wave-3"></div>
        </div>

    </section>

    <!-- FEATURES SECTION -->
    <section class="feature-section" id="features">

        <div class="container">

            <div class="section-heading text-center">

                <span class="section-mini-title">
                    POWERFUL FEATURES
                </span>

                <h2>
                    Everything you need for modern education
                </h2>

                <p>
                    Advanced tools and intelligent features to enhance teaching
                    and accelerate learning outcomes.
                </p>

            </div>

            <div class="row g-4 mt-4">

                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon purple">
                            <i class="fa fa-brain"></i>
                        </div>
                        <h4>AI Powered Assessments</h4>
                        <p>Generate smart question papers, evaluations and adaptive assessments.</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon blue">
                            <i class="fa fa-chart-column"></i>
                        </div>
                        <h4>Learning Analytics</h4>
                        <p>Track student performance, engagement and learning outcomes with insights.</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon orange">
                            <i class="fa fa-certificate"></i>
                        </div>
                        <h4>Smart Certificates</h4>
                        <p>Generate and verify secure digital certificates instantly.</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon green">
                            <i class="fa fa-shield-halved"></i>
                        </div>
                        <h4>Secure Platform</h4>
                        <p>Enterprise-grade security and protected institutional data management.</p>
                    </div>
                </div>

            </div>

        </div>

    </section>

    <!-- ACCESS SECTION -->
    <section class="access-section">

        <div class="container">

            <div class="row g-4 align-items-stretch">

                <div class="col-lg-6 d-flex">
                    <div class="access-card access-blue w-100">

                        <div class="access-content">

                            <div class="access-text">
                                <h3>Institutional Learning</h3>
                                <p>
                                    Schools and institutes can manage students,
                                    teachers, assessments and certificates
                                    using the LMS portal.
                                </p>

                                <a href="{{ route('portal') }}" class="btn access-btn">
                                    Access Portal
                                    <i class="fa fa-arrow-right"></i>
                                </a>
                            </div>

                            <div class="access-illustration access-building">
                                <img src="{{ asset('images/InstitutionalLogo.png') }}">
                            </div>

                        </div>

                    </div>
                </div>

                <div class="col-lg-6 d-flex">
                    <div class="access-card access-orange w-100">

                        <div class="access-content">

                            <div class="access-text">
                                <h3>Independent Learning</h3>
                                <p>
                                    Learn independently through premium courses,
                                    AI assessments and skill certifications.
                                </p>

                                <a href="{{ route('independent.register') }}" class="btn access-btn-alt">
                                    Get Started
                                    <i class="fa fa-arrow-right"></i>
                                </a>
                            </div>

                            <div class="access-illustration access-cap">
                                <img src="{{ asset('images/IndependentLogo.png') }}">
                            </div>

                        </div>

                    </div>
                </div>

            </div>

        </div>

    </section>

    <!-- FOOTER -->
    <footer class="footer-section">

        <div class="container">

            <div class="footer-wrapper">

                <div class="footer-brand">

                    <div class="footer-logo-placeholder">
                        <img src="{{ asset('images/TinkEdgeLogo.png') }}" >
                    </div>

                    <div>
                        <h4>TinkEdge LMS</h4>
                        <p>AI Powered Learning Ecosystem</p>
                    </div>

                </div>

                <div class="footer-action-card">
                    <div class="footer-action-icon blue">
                        <i class="fa fa-user-shield"></i>
                    </div>

                    <div>
                        <a href="{{ route('admin.login') }}">Admin Login</a>
                        <span>Secure admin access</span>
                    </div>

                    <a href="{{ route('admin.login') }}" class="footer-arrow-link">
                        <i class="fa fa-arrow-right footer-arrow"></i>
                    </a>
                </div>

                <div class="footer-action-card">
                    <div class="footer-action-icon orange">
                        <i class="fa fa-certificate"></i>
                    </div>

                    <div>
                        <a href="{{ route('certificate.verify') }}">Verify Certificate</a>
                        <span>Verify your certificates</span>
                    </div>

                    <a href="{{ route('certificate.verify') }}" class="footer-arrow-link">
                        <i class="fa fa-arrow-right footer-arrow"></i>
                    </a>
                </div>

                <div class="footer-copy">
                    © {{ date('Y') }} TinkEdge LMS<br>
                    All rights reserved.
                </div>

            </div>

        </div>

    </footer>

</div>

@endsection