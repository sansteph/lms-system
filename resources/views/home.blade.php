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
                        <img src="{{ asset('images/InnovatEdgeLogo.png') }}" >
                    </div>

                    <div class="logo-text">
                        <h5>InnovatEdge</h5>
                        <span>Learning Made Easy</span>
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
                            ADVANCED LEARNING ECOSYSTEM
                        </div>

                        <h1 class="hero-title">
                            Smart Learning <br>
                            for a <span>Better Future</span>
                        </h1>

                        <p class="hero-description">
                            Empowering institutions, STEM Engineers and learners with automated
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
                            <div class="hero-stat-item w-100 justify-content-center">
                                <div class="overflow-hidden rounded-3 border bg-light"
                                     style="width: 100%; max-width: 420px; aspect-ratio: 16 / 9;">
                                    <img src="{{ asset('images/InstitutionalLogo.png') }}"
                                         alt="Company image placeholder"
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- HERO IMAGE -->
                <div class="col-xl-7 col-lg-6">

                    <div class="hero-image-wrapper text-center">

                        <img src="{{ asset('images/Hero_Homepage.png') }}"
                            alt="InnovatEdge"
                            class="img-fluid hero-main-image">

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
                        <h4>Automated Assessments</h4>
                        <p>Experience progress wise assessment flow for enhanced learning.</p>
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
                                    STEM Engineers, assessments and certificates
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
                                <h3>Hybrid Learning</h3>
                                <p>
                                    Learn independently through premium courses,
                                    automated assessments and skill certifications.
                                </p>

                                <a href="{{ route('coming.soon') }}" class="btn access-btn-alt">
                                    Coming Soon
                                    <i class="fa fa-arrow-right"></i>
                                </a>
                            </div>

                            <div class="access-illustration access-cap">
                                <img src="{{ asset('images/HybridLogo.png') }}">
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

                <div class="footer-top">

                    <div class="footer-brand">
                        <div class="footer-logo-placeholder">
                            <img src="{{ asset('images/InnovatEdgeLogo.png') }}">
                        </div>

                        <div>
                            <h4>InnovatEdge</h4>
                            <p>Advanced Learning Ecosystem</p>
                        </div>
                    </div>

                    <div class="footer-actions">

                        <div class="footer-action-card">
                            <div class="footer-action-icon blue">
                                <i class="fa fa-user-shield"></i>
                            </div>

                            <div>
                                <a href="{{ route('admin.login') }}">Admin Login</a>
                                <span>Secure admin access</span>
                            </div>
                        </div>

                        <div class="footer-action-card">
                            <div class="footer-action-icon orange">
                                <i class="fa fa-certificate"></i>
                            </div>

                            <div>
                                <a href="{{ route('certificate.verify') }}">Verify Certificate</a>
                                <span>Verify your certificates</span>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="footer-copy">
                    © {{ date('Y') }} InnovatEdge. All rights reserved.
                </div>

            </div>

        </div>

    </footer>

</div>

@endsection
