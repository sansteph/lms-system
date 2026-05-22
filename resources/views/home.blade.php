@extends('layouts.app')

@section('content')

<div class="modern-homepage">

    <!-- HERO SECTION -->
    <section class="hero-section">

       <div class="hero-overlay"></div>

        <div class="hero-glow glow-1"></div>
        <div class="hero-glow glow-2"></div>

        <div class="container">

            <div class="row align-items-center hero-row">

                <!-- LEFT CONTENT -->
                <div class="col-lg-6">

                    <div class="hero-content">

                        <div class="hero-badge">

                            <i class="fa fa-bolt"></i>

                            AI Powered Learning Ecosystem

                        </div>

                        <h1 class="hero-title">

                            Modern Learning <br>

                            <span>
                                Reimagined
                            </span>

                        </h1>

                        <p class="hero-description">

                            Smart learning, AI assessments, certificates,
                            analytics, gamification and institutional learning
                            management for schools, teachers and independent learners.

                        </p>

                        <div class="hero-buttons">

                            <a href="{{ route('portal') }}"
                               class="btn hero-btn-primary">

                                <i class="fa fa-sign-in-alt me-2"></i>

                                Institutional Portal

                            </a>

                            <a href="#courses"
                               class="btn hero-btn-secondary">

                                <i class="fa fa-book-open me-2"></i>

                                Explore Courses

                            </a>

                        </div>

                        <div class="hero-stats">

                            <div class="hero-stat-card">

                                <h3>10K+</h3>

                                <span>Learners</span>

                            </div>

                            <div class="hero-stat-card">

                                <h3>500+</h3>

                                <span>Assessments</span>

                            </div>

                            <div class="hero-stat-card">

                                <h3>120+</h3>

                                <span>Institutes</span>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- RIGHT VISUAL -->
                <div class="col-lg-6">

                    <div class="hero-dashboard">

                        <!-- floating badge -->
                        <div class="hero-floating hero-floating-1">
                            <i class="fa fa-brain"></i>
                            AI Evaluation
                        </div>

                        <div class="hero-floating hero-floating-2">
                            <i class="fa fa-chart-line"></i>
                            Live Analytics
                        </div>

                        <!-- MAIN DASHBOARD -->
                        <div class="dashboard-glass">

                            <!-- top -->
                            <div class="dashboard-top">

                                <div>
                                    <small>Welcome Back</small>
                                    <h4>Learning Dashboard</h4>
                                </div>

                                <div class="dashboard-avatar">
                                    <i class="fa fa-user"></i>
                                </div>

                            </div>

                            <!-- stats -->
                            <div class="dashboard-stats">

                                <div class="dashboard-stat-box">
                                    <h3>92%</h3>
                                    <span>Progress</span>
                                </div>

                                <div class="dashboard-stat-box">
                                    <h3>18</h3>
                                    <span>Courses</span>
                                </div>

                            </div>

                            <!-- course card -->
                            <div class="course-preview-card">

                                <div class="course-icon">
                                    <i class="fa fa-laptop-code"></i>
                                </div>

                                <div class="course-info">
                                    <h5>AI & Machine Learning</h5>

                                    <div class="course-progress">
                                        <div class="course-progress-bar"></div>
                                    </div>

                                    <small>75% Completed</small>
                                </div>

                            </div>

                            <!-- analytics -->
                            <div class="analytics-card">

                                <div class="analytics-line analytics-line-1"></div>
                                <div class="analytics-line analytics-line-2"></div>
                                <div class="analytics-line analytics-line-3"></div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <!-- FEATURE SECTION -->
    <section class="feature-section">

        <div class="container">

            <div class="section-header text-center mb-5">

                <h2>
                    Powerful Learning Features
                </h2>

                <p>
                    Everything needed for modern education and digital learning.
                </p>

            </div>

            <div class="row g-4">

                <div class="col-md-4">

                    <div class="feature-card">

                        <div class="feature-icon">

                            <i class="fa fa-brain"></i>

                        </div>

                        <h4>
                            AI Powered Assessments
                        </h4>

                        <p>
                            Automatically generate smart question papers,
                            evaluations and adaptive assessments.
                        </p>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="feature-card">

                        <div class="feature-icon">

                            <i class="fa fa-chart-pie"></i>

                        </div>

                        <h4>
                            Learning Analytics
                        </h4>

                        <p>
                            Track student performance, engagement and
                            learning outcomes with detailed insights.
                        </p>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="feature-card">

                        <div class="feature-icon">

                            <i class="fa fa-certificate"></i>

                        </div>

                        <h4>
                            Smart Certificates
                        </h4>

                        <p>
                            Automatically generate and verify digital
                            certificates for assessments and learning programs.
                        </p>

                        <a href="#" class="feature-link">
                            Learn More →
                        </a>

                    </div>

                </div>
                
            </div>

        </div>

    </section>

    <!-- ACCESS SECTION -->
    <section class="access-section">

        <div class="container">

            <div class="row g-4">

                <div class="col-lg-6">

                    <div class="access-card">

                        <div class="access-icon">

                            <i class="fa fa-university"></i>

                        </div>

                        <h3>
                            Institutional Learning
                        </h3>

                        <p>

                            Schools and institutes can manage students,
                            teachers, assessments, certificates and
                            learning content using the LMS portal.

                        </p>

                        <a href="{{ route('portal') }}"
                           class="btn access-btn">

                            Access Portal

                        </a>

                    </div>

                </div>

                <div class="col-lg-6">

                    <div class="access-card premium-card">

                        <div class="access-icon">

                            <i class="fa fa-user-graduate"></i>

                        </div>

                        <h3>
                            Independent Learning
                        </h3>

                        <p>

                            Learn independently through premium courses,
                            AI assessments and skill certifications.

                        </p>

                        <a href="#"
                           class="btn access-btn-premium">

                            Coming Soon

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <!-- FOOTER SECTION -->
    <section class="footer-section">

        <div class="container">

            <div class="row align-items-center">

                <div class="col-md-6">

                    <h4 class="footer-logo">
                        TinkEdge LMS
                    </h4>

                    <p class="footer-text">

                        AI Powered Learning Ecosystem

                    </p>

                </div>

                <div class="col-md-6 text-md-end">

                    <a href="{{ route('admin.login') }}"
                       class="footer-link">

                        Admin Login

                    </a>

                    <a href="{{ route('certificate.verify') }}"
                       class="footer-link">

                        Verify Certificate

                    </a>

                </div>

            </div>

        </div>

    </section>

</div>

@endsection