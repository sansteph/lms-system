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

                <div class="hero-social-links" aria-label="Social links">
                    <a href="https://www.linkedin.com/company/tinkedge/posts/?feedView=all"
                       target="_blank"
                       rel="noopener"
                       title="LinkedIn">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.95v5.66H9.34V9h3.42v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.32 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12zM7.1 20.45H3.53V9H7.1v11.45z"/>
                        </svg>
                    </a>
                    <a href="https://www.instagram.com/tinkedge_/?hl=en"
                       target="_blank"
                       rel="noopener"
                       title="Instagram">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7.75 2h8.5A5.76 5.76 0 0 1 22 7.75v8.5A5.76 5.76 0 0 1 16.25 22h-8.5A5.76 5.76 0 0 1 2 16.25v-8.5A5.76 5.76 0 0 1 7.75 2zm0 2A3.75 3.75 0 0 0 4 7.75v8.5A3.75 3.75 0 0 0 7.75 20h8.5A3.75 3.75 0 0 0 20 16.25v-8.5A3.75 3.75 0 0 0 16.25 4h-8.5z"/>
                            <path d="M12 7.35A4.65 4.65 0 1 1 12 16.65 4.65 4.65 0 0 1 12 7.35zm0 2A2.65 2.65 0 1 0 12 14.65 2.65 2.65 0 0 0 12 9.35z"/>
                            <path d="M17.2 6.65a1.15 1.15 0 1 1 0 2.3 1.15 1.15 0 0 1 0-2.3z"/>
                        </svg>
                    </a>
                    <a href="https://www.youtube.com/@tinkedge9223"
                       target="_blank"
                       rel="noopener"
                       title="YouTube">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M21.58 7.18a2.72 2.72 0 0 0-1.91-1.92C17.98 4.8 12 4.8 12 4.8s-5.98 0-7.67.46a2.72 2.72 0 0 0-1.91 1.92A28.4 28.4 0 0 0 2 12a28.4 28.4 0 0 0 .42 4.82 2.72 2.72 0 0 0 1.91 1.92c1.69.46 7.67.46 7.67.46s5.98 0 7.67-.46a2.72 2.72 0 0 0 1.91-1.92A28.4 28.4 0 0 0 22 12a28.4 28.4 0 0 0-.42-4.82zM10 15.25v-6.5L15.2 12 10 15.25z"/>
                        </svg>
                    </a>
                    <a href="https://www.google.com/maps/place/TinkEdge/@13.0051806,77.5668533,17z/data=!3m1!4b1!4m6!3m5!1s0x3bae162fbb205ae7:0x7fa7f2b1d5bdbcb6!8m2!3d13.0051754!4d77.5694282!16s%2Fg%2F11fsq6_g11?authuser=0&entry=ttu&g_ep=EgoyMDI2MDcxOS4wIKXMDSoASAFQAw%3D%3D"
                       target="_blank"
                       rel="noopener"
                       title="Google Maps">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 2.25a7.25 7.25 0 0 0-7.25 7.25c0 5.44 7.25 12.25 7.25 12.25s7.25-6.81 7.25-12.25A7.25 7.25 0 0 0 12 2.25zm0 10.1a2.85 2.85 0 1 1 0-5.7 2.85 2.85 0 0 1 0 5.7z"/>
                        </svg>
                    </a>
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

            <div class="feature-top-images d-flex justify-content-between align-items-start"
                 style="margin-top: -28px; margin-bottom: 18px;">
                <img src="{{ asset('images/CupRobot(1).png') }}"
                     alt="Company image placeholder"
                     style="width: 100%; max-width: 320px; height: auto; display: block;">
                <img src="{{ asset('images/CupRobot(2).png') }}"
                     alt="Company image placeholder"
                     style="width: 100%; max-width: 320px; height: auto; display: block;">
            </div>

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
