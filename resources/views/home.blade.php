@extends('layouts.app')

@section('title', 'InnovatEdge | STEM Education, Robotics and AI Learning Platform')
@section('meta_description', 'InnovatEdge helps schools and institutes manage STEM education, ATL labs, robotics learning, assessments, certificates, AI prep quizzes, and learner progress.')

@section('content')

@php
    $blogsRoute = route('blogs.community-feed');
    $newsroomRoute = route('newsroom');
@endphp

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
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:21px;height:21px;display:block;fill:#ffffff;">
                            <path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.95v5.66H9.34V9h3.42v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.32 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12zM7.1 20.45H3.53V9H7.1v11.45z"/>
                        </svg>
                    </a>
                    <a href="https://www.instagram.com/tinkedge_/"
                       target="_blank"
                       rel="noopener"
                       title="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:21px;height:21px;display:block;fill:#ffffff;">
                            <path d="M7.75 2h8.5A5.76 5.76 0 0 1 22 7.75v8.5A5.76 5.76 0 0 1 16.25 22h-8.5A5.76 5.76 0 0 1 2 16.25v-8.5A5.76 5.76 0 0 1 7.75 2zm0 2A3.75 3.75 0 0 0 4 7.75v8.5A3.75 3.75 0 0 0 7.75 20h8.5A3.75 3.75 0 0 0 20 16.25v-8.5A3.75 3.75 0 0 0 16.25 4h-8.5z"/>
                            <path d="M12 7.35A4.65 4.65 0 1 1 12 16.65 4.65 4.65 0 0 1 12 7.35zm0 2A2.65 2.65 0 1 0 12 14.65 2.65 2.65 0 0 0 12 9.35z"/>
                            <path d="M17.2 6.65a1.15 1.15 0 1 1 0 2.3 1.15 1.15 0 0 1 0-2.3z"/>
                        </svg>
                    </a>
                    <a href="https://www.youtube.com/@tinkedge9223"
                       target="_blank"
                       rel="noopener"
                       title="YouTube">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:21px;height:21px;display:block;fill:#ffffff;">
                            <path d="M21.58 7.18a2.72 2.72 0 0 0-1.91-1.92C17.98 4.8 12 4.8 12 4.8s-5.98 0-7.67.46a2.72 2.72 0 0 0-1.91 1.92A28.4 28.4 0 0 0 2 12a28.4 28.4 0 0 0 .42 4.82 2.72 2.72 0 0 0 1.91 1.92c1.69.46 7.67.46 7.67.46s5.98 0 7.67-.46a2.72 2.72 0 0 0 1.91-1.92A28.4 28.4 0 0 0 22 12a28.4 28.4 0 0 0-.42-4.82zM10 15.25v-6.5L15.2 12 10 15.25z"/>
                        </svg>
                    </a>
                    <a href="https://www.google.com/maps/place/TinkEdge/@13.0051806,77.5668533,17z/data=!3m1!4b1!4m6!3m5!1s0x3bae162fbb205ae7:0x7fa7f2b1d5bdbcb6!8m2!3d13.0051754!4d77.5694282!16s%2Fg%2F11fsq6_g11?authuser=0&entry=ttu&g_ep=EgoyMDI2MDcxOS4wIKXMDSoASAFQAw%3D%3D"
                       target="_blank"
                       rel="noopener"
                       title="Google Maps">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:21px;height:21px;display:block;fill:#ffffff;">
                            <path d="M12 2.25a7.25 7.25 0 0 0-7.25 7.25c0 5.44 7.25 12.25 7.25 12.25s7.25-6.81 7.25-12.25A7.25 7.25 0 0 0 12 2.25zm0 10.1a2.85 2.85 0 1 1 0-5.7 2.85 2.85 0 0 1 0 5.7z"/>
                        </svg>
                    </a>
                    <a href="{{ asset('downloads/InnovatEdge.apk') }}"
                       class="hero-social-download"
                       download
                       title="Download Android app">
                        <i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i>
                        <span class="visually-hidden">Download Android app</span>
                    </a>
                </div>

            </div>

            <aside class="android-launch-announcement"
                   id="androidLaunchAnnouncement"
                   aria-label="InnovatEdge Android app announcement">
                <div class="android-launch-icon" aria-hidden="true">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                </div>
                <div class="android-launch-copy">
                    <div class="android-launch-kicker">
                        <span class="android-launch-dot"></span>
                        Now available
                    </div>
                    <strong>Take InnovatEdge wherever learning happens.</strong>
                    <span>Install the official Android app for direct access to your learning workspace.</span>
                </div>
                <a href="{{ asset('downloads/InnovatEdge.apk') }}"
                   class="android-launch-cta"
                   download>
                    <i class="fa-solid fa-download" aria-hidden="true"></i>
                    Download for Android
                </a>
                <button type="button"
                        class="android-launch-dismiss"
                        id="dismissAndroidLaunch"
                        aria-label="Dismiss Android app announcement">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </aside>

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
                            Empowering institutions, STEM Engineers and learners with AI powered
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

                        <div class="hero-ai-assistant ai-chatbot-trigger"
                             id="heroAiAssistant"
                             aria-label="Open InnovatEdge Assistant"
                             role="button"
                             tabindex="0">
                            <span class="assistant-chat-bubble" aria-hidden="true">Chat with me</span>
                            <img src="{{ asset('images/ai-assistant-frames/climb/climb-01.png') }}"
                                 id="heroAiFrame"
                                 alt="">
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

    <section class="home-module-rail-section" aria-label="News and blogs">
        <div class="container">
            <div class="home-module-gateway">
                <a href="{{ $newsroomRoute }}" class="home-module-card home-module-card-active home-module-newsroom">
                    <div class="home-module-card-icon">
                        <i class="fa fa-newspaper"></i>
                    </div>
                    <div class="home-module-card-copy">
                        <strong>Newsroom</strong>
                        <small>Curated STEM, ATL and robotics updates for schools and learners.</small>
                    </div>
                </a>

                <a href="{{ $blogsRoute }}" class="home-module-card home-module-card-active home-module-community">
                    <div class="home-module-card-icon">
                        <i class="fa fa-pen-nib"></i>
                    </div>
                    <div class="home-module-card-copy">
                        <strong>Community</strong>
                        <small>Achievements, ideas, projects and classroom stories from the InnovatEdge community.</small>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="feature-section" id="features">

        <div class="homepage-particle-field" aria-hidden="true"></div>

        <div class="container">

            <div class="section-heading text-center">

                <span class="section-mini-title">
                    POWERFUL FEATURES
                </span>

                <h2>
                    Everything you need for modern education
                </h2>

                <p>
                    AI powered tools and intelligent features to enhance teaching
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

        <div class="homepage-particle-field homepage-particle-field-alt" aria-hidden="true"></div>

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
                                    AI powered assessments and skill certifications.
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
                            <p>AI Powered Learning Ecosystem</p>
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

    <div class="floating-ai-assistant ai-chatbot-trigger"
         id="floatingAiAssistant"
         aria-label="Open InnovatEdge Assistant"
         role="button"
         tabindex="0">
        <span class="assistant-chat-bubble" aria-hidden="true">Chat with me</span>
        <div class="floating-ai-robot">
            <img src="{{ asset('images/ai-assistant-frames/climb/climb-01.png') }}"
                 id="floatingAiFrame"
                 alt="AI assistant">
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const assistant = document.getElementById('floatingAiAssistant');
        const frameImage = document.getElementById('floatingAiFrame');
        const heroAssistant = document.getElementById('heroAiAssistant');
        const heroFrameImage = document.getElementById('heroAiFrame');
        const assistantBubble = assistant ? assistant.querySelector('.assistant-chat-bubble') : null;
        const heroBubble = heroAssistant ? heroAssistant.querySelector('.assistant-chat-bubble') : null;

        if (!assistant || !frameImage || !heroAssistant || !heroFrameImage || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const frameSet = function (action, count) {
            return Array.from({ length: count || 8 }, function (_, index) {
                const frameNumber = String(index + 1).padStart(2, '0');
                return '{{ asset('images/ai-assistant-frames') }}/' + action + '/' + action + '-' + frameNumber + '.png';
            });
        };

        const assistantFrames = {
            climb: frameSet('climb'),
            sleep: frameSet('sleep'),
            hop: frameSet('hop'),
            slide: frameSet('slide'),
            backflip: frameSet('backflip', 9),
        };

        const clamp = function (value, min, max) {
            return Math.max(min, Math.min(max, value));
        };

        const setScene = function (sceneClass, x, y, tilt, size) {
            assistant.className = 'floating-ai-assistant ' + sceneClass;
            assistant.style.left = clamp(x, 74, window.innerWidth - 74) + 'px';
            assistant.style.top = clamp(y, 78, window.innerHeight - 78) + 'px';
            assistant.style.setProperty('--assistant-tilt', (tilt || 0) + 'deg');
            assistant.style.setProperty('--assistant-size', (size || 126) + 'px');
        };

        const syncAssistantBubble = function (visible) {
            [assistantBubble, heroBubble].forEach(function (bubble) {
                if (!bubble) {
                    return;
                }

                bubble.classList.toggle('is-visible', !!visible);
            });
        };

        let frameTimer = null;
        let heroFrameTimer = null;
        let heroPeekTimer = null;
        let heroResetTimer = null;
        let featureHopTimers = [];

        Object.keys(assistantFrames).forEach(function (key) {
            assistantFrames[key].forEach(function (source) {
                const image = new Image();
                image.src = source;
            });
        });

        const clearFeatureHopTimers = function () {
            featureHopTimers.forEach(function (timer) {
                window.clearTimeout(timer);
            });
            featureHopTimers = [];
        };

        const playFrames = function (targetImage, timerName, action, frameDelay, loopCount, onComplete) {
            const frames = assistantFrames[action] || [];
            const loops = loopCount || 1;
            let index = 0;
            let playedLoops = 0;

            if (!frames.length) {
                return;
            }

            if (timerName === 'hero' && heroFrameTimer) {
                window.clearInterval(heroFrameTimer);
            }

            if (timerName === 'floating' && frameTimer) {
                window.clearInterval(frameTimer);
            }

            targetImage.src = frames[0];
            targetImage.animate([
                { filter: 'blur(1.4px)', transform: 'translateX(-3px) scale(0.995)' },
                { filter: 'blur(0)', transform: 'translateX(0) scale(1)' }
            ], { duration: Math.min(220, frameDelay - 60), easing: 'ease-out' });

            const timer = window.setInterval(function () {
                index += 1;

                if (index >= frames.length) {
                    playedLoops += 1;

                    if (playedLoops >= loops) {
                        window.clearInterval(timer);
                        if (timerName === 'hero') {
                            heroFrameTimer = null;
                        } else {
                            frameTimer = null;
                        }
                        targetImage.src = frames[frames.length - 1];
                        if (typeof onComplete === 'function') {
                            onComplete();
                        }
                        return;
                    }

                    index = 0;
                }

                targetImage.src = frames[index];
                targetImage.animate([
                    { filter: 'blur(1.4px)', transform: 'translateX(-3px) scale(0.995)' },
                    { filter: 'blur(0)', transform: 'translateX(0) scale(1)' }
                ], { duration: Math.min(220, frameDelay - 60), easing: 'ease-out' });
            }, frameDelay);

            if (timerName === 'hero') {
                heroFrameTimer = timer;
            } else {
                frameTimer = timer;
            }
        };

        const elementPoint = function (selector, xRatio, yRatio, fallbackX, fallbackY) {
            const element = document.querySelector(selector);

            if (!element) {
                return { x: fallbackX, y: fallbackY };
            }

            const rect = element.getBoundingClientRect();

            return {
                x: rect.left + (rect.width * xRatio),
                y: rect.top + (rect.height * yRatio),
            };
        };

        const scenes = {
            peekHero: function () {
                window.clearTimeout(heroPeekTimer);
                window.clearTimeout(heroResetTimer);

                heroAssistant.className = 'hero-ai-assistant hero-ai-peek hero-ai-reset';
                syncAssistantBubble(false);
                heroAssistant.style.left = '16%';
                heroAssistant.style.top = '0%';
                heroAssistant.style.setProperty('--hero-assistant-size', '108px');
                heroAssistant.style.setProperty('--hero-assistant-tilt', '-4deg');
                heroFrameImage.src = assistantFrames.climb[0];

                void heroAssistant.offsetWidth;

                heroResetTimer = window.setTimeout(function () {
                    heroAssistant.classList.remove('hero-ai-reset');
                }, 120);

                playFrames(heroFrameImage, 'hero', 'climb', 360, 1, function () {
                    if (activeSection === 'hero') {
                        syncAssistantBubble(true);
                    }
                });

                heroPeekTimer = window.setTimeout(function () {
                    if (activeSection === 'hero') {
                        heroAssistant.classList.add('hero-ai-on-top');
                        heroAssistant.style.left = '16%';
                        heroAssistant.style.top = '0%';
                    }
                }, 2880);
            },
            slideHero: function () {
                window.clearTimeout(heroPeekTimer);
                window.clearTimeout(heroResetTimer);

                heroAssistant.className = 'hero-ai-assistant hero-ai-slide hero-ai-on-top';
                syncAssistantBubble(false);
                heroAssistant.style.left = '16%';
                heroAssistant.style.top = '4%';
                heroAssistant.style.setProperty('--hero-assistant-size', '108px');
                heroAssistant.style.setProperty('--hero-assistant-tilt', '-4deg');
                playFrames(heroFrameImage, 'hero', 'slide', 340, 1, function () {
                    if (activeSection === 'hero') {
                        syncAssistantBubble(true);
                    }
                });

                window.setTimeout(function () {
                    if (activeSection !== 'hero') {
                        return;
                    }

                    heroAssistant.style.left = '86%';
                    heroAssistant.style.top = '78%';
                    heroAssistant.style.setProperty('--hero-assistant-tilt', '5deg');
                }, 280);
            },
            hopFeatures: function () {
                clearFeatureHopTimers();

                const cards = Array.from(document.querySelectorAll('.feature-card'));
                const cardPoint = function (card) {
                    const rect = card.getBoundingClientRect();
                    return {
                        x: rect.left + (rect.width * 0.5),
                        y: rect.top - 18,
                    };
                };

                if (!cards.length) {
                    setScene('assistant-scene-hop', window.innerWidth * 0.72, window.innerHeight * 0.62, 0, 110);
                    playFrames(frameImage, 'floating', 'hop', 52, 1);
                    return;
                }

                const firstPoint = cardPoint(cards[0]);
                setScene('assistant-scene-feature-rest', firstPoint.x, firstPoint.y, -2, 104);
                frameImage.src = assistantFrames.hop[0];

                cards.slice(1).forEach(function (card, index) {
                    const timer = window.setTimeout(function () {
                        if (activeSection !== 'features') {
                            return;
                        }

                        const point = cardPoint(card);

                        setScene('assistant-scene-hop', point.x, point.y, index % 2 === 0 ? 2 : -2, 104);
                        syncAssistantBubble(false);
                        playFrames(frameImage, 'floating', 'hop', 115, 1, function () {
                            if (activeSection === 'features') {
                                syncAssistantBubble(true);
                            }
                        });
                    }, 760 + (index * 1240));

                    featureHopTimers.push(timer);
                });

                const returnStart = 760 + ((cards.length - 1) * 1240) + 60;
                cards.slice(0, -1).reverse().forEach(function (card, reverseIndex) {
                    const timer = window.setTimeout(function () {
                        if (activeSection !== 'features') {
                            return;
                        }

                        const point = cardPoint(card);
                        setScene('assistant-scene-backflip', point.x, point.y, reverseIndex % 2 === 0 ? 4 : -3, 104);
                        syncAssistantBubble(false);
                        playFrames(frameImage, 'floating', 'backflip', 92, 1, function () {
                            if (activeSection === 'features') {
                                syncAssistantBubble(true);
                            }
                        });
                    }, returnStart + (reverseIndex * 1180));

                    featureHopTimers.push(timer);
                });
            },
            sleepFooter: function () {
                const point = elementPoint('.footer-wrapper', 0.90, 0.06, window.innerWidth - 160, window.innerHeight - 118);
                setScene('assistant-scene-sleep', point.x, point.y, -3, 104);
                syncAssistantBubble(false);
                playFrames(frameImage, 'floating', 'sleep', 390, 1, function () {
                    if (activeSection === 'footer') {
                        syncAssistantBubble(true);
                    }
                });
            },
        };

        let activeSection = null;
        let sectionTimer = null;
        let lastScrollY = window.scrollY;
        let lastHeroSlideAt = 0;

        const visibleRatio = function (element) {
            if (!element) {
                return 0;
            }

            const rect = element.getBoundingClientRect();
            const visibleHeight = Math.min(rect.bottom, window.innerHeight) - Math.max(rect.top, 0);

            return Math.max(0, visibleHeight) / Math.max(1, Math.min(rect.height, window.innerHeight));
        };

        const currentSection = function () {
            const hero = document.querySelector('.hero-section');
            const features = document.querySelector('.feature-section');
            const moduleRail = document.querySelector('.home-module-rail-section');
            const footer = document.querySelector('.footer-section');
            const access = document.querySelector('.access-section');

            const ratios = [
                { name: 'hero', ratio: visibleRatio(hero) },
                { name: 'features', ratio: Math.max(visibleRatio(features), visibleRatio(moduleRail)) },
                { name: 'footer', ratio: Math.max(visibleRatio(footer), visibleRatio(access)) },
            ];

            ratios.sort(function (a, b) {
                return b.ratio - a.ratio;
            });

            return ratios[0].ratio > 0.18 ? ratios[0].name : null;
        };

        const activateSection = function (section) {
            if (!section || section === activeSection) {
                return;
            }

            if (activeSection === 'hero' && section !== 'hero') {
                window.clearTimeout(heroPeekTimer);
                window.clearTimeout(heroResetTimer);
            }

            if (activeSection === 'features' && section !== 'features') {
                clearFeatureHopTimers();
            }

            activeSection = section;
            window.clearInterval(sectionTimer);

            if (section === 'hero') {
                assistant.classList.add('assistant-hidden');
                heroAssistant.classList.remove('hero-ai-hidden');
                syncAssistantBubble(false);
                scenes.peekHero();
                return;
            }

            if (section === 'features') {
                heroAssistant.classList.add('hero-ai-hidden');
                assistant.classList.remove('assistant-hidden');
                scenes.hopFeatures();
                sectionTimer = window.setInterval(function () {
                    if (activeSection === 'features') {
                        scenes.hopFeatures();
                    }
                }, 10600);
                return;
            }

            if (section === 'footer') {
                heroAssistant.classList.add('hero-ai-hidden');
                assistant.classList.remove('assistant-hidden');
                scenes.sleepFooter();
                sectionTimer = window.setInterval(function () {
                    if (activeSection === 'footer') {
                        scenes.sleepFooter();
                    }
                }, 7600);
            }
        };

        let scrollTimer = null;
        const handleViewport = function () {
            const currentY = window.scrollY;
            const isScrollingDown = currentY > lastScrollY + 8;
            lastScrollY = currentY;

            if (activeSection === 'hero' && isScrollingDown) {
                const now = Date.now();

                if (now - lastHeroSlideAt > 2600) {
                    lastHeroSlideAt = now;
                    scenes.slideHero();
                }
            }

            window.clearTimeout(scrollTimer);
            scrollTimer = window.setTimeout(function () {
                activateSection(currentSection());
            }, 90);
        };

        activateSection(currentSection() || 'hero');
        window.addEventListener('scroll', handleViewport, { passive: true });
        window.addEventListener('resize', handleViewport);
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const colors = [
            'rgba(23, 105, 210, .68)',
            'rgba(19, 168, 199, .7)',
            'rgba(21, 149, 109, .58)',
            'rgba(227, 154, 35, .62)'
        ];

        const randomBetween = function (minimum, maximum) {
            return minimum + Math.random() * (maximum - minimum);
        };

        document.querySelectorAll('.homepage-particle-field').forEach(function (field) {
            const particleCount = window.innerWidth < 768
                ? 52
                : (window.innerWidth < 1200 ? 88 : 124);
            const particles = [];
            let fieldWidth = field.clientWidth;
            let fieldHeight = field.clientHeight;

            for (let index = 0; index < particleCount; index += 1) {
                const particle = document.createElement('span');
                const size = randomBetween(1.5, 4.4);
                const color = colors[Math.floor(Math.random() * colors.length)];

                particle.className = 'homepage-particle';
                particle.style.setProperty('--particle-size', size.toFixed(2) + 'px');
                particle.style.setProperty('--particle-glow', randomBetween(4, 10).toFixed(1) + 'px');
                particle.style.setProperty('--particle-color', color);
                field.appendChild(particle);

                const state = {
                    element: particle,
                    x: randomBetween(0, fieldWidth),
                    y: randomBetween(0, fieldHeight),
                    angle: randomBetween(0, Math.PI * 2),
                    speed: randomBetween(8, 22),
                    turnRate: randomBetween(-.7, .7),
                    targetTurn: randomBetween(-1.15, 1.15),
                    nextSteerAt: performance.now() + randomBetween(450, 1800),
                    wobble: randomBetween(.18, .5),
                    wobbleSpeed: randomBetween(.7, 1.8),
                    phase: randomBetween(0, Math.PI * 2),
                    pulseSpeed: randomBetween(.7, 1.7)
                };

                particles.push(state);
                particle.style.transform = 'translate3d(' + state.x + 'px,' + state.y + 'px,0)';
                particle.style.opacity = reducedMotion ? '.45' : randomBetween(.45, .9).toFixed(2);
            }

            if (reducedMotion) {
                return;
            }

            let previousTime = performance.now();

            const moveParticles = function (currentTime) {
                const elapsed = Math.min((currentTime - previousTime) / 1000, .04);
                previousTime = currentTime;

                particles.forEach(function (state) {
                    if (currentTime >= state.nextSteerAt) {
                        state.targetTurn = randomBetween(-1.15, 1.15);
                        state.nextSteerAt = currentTime + randomBetween(450, 1800);
                    }

                    state.turnRate += (state.targetTurn - state.turnRate) * Math.min(1, elapsed * 2.4);
                    state.angle += (
                        state.turnRate
                        + Math.sin(currentTime / 1000 * state.wobbleSpeed + state.phase) * state.wobble
                    ) * elapsed;

                    state.x += Math.cos(state.angle) * state.speed * elapsed;
                    state.y += Math.sin(state.angle) * state.speed * elapsed;

                    if (state.x <= 0 || state.x >= fieldWidth) {
                        state.x = Math.min(fieldWidth, Math.max(0, state.x));
                        state.angle = Math.PI - state.angle;
                        state.turnRate *= -1;
                    }

                    if (state.y <= 0 || state.y >= fieldHeight) {
                        state.y = Math.min(fieldHeight, Math.max(0, state.y));
                        state.angle = -state.angle;
                        state.turnRate *= -1;
                    }

                    const pulse = .78 + Math.sin(currentTime / 1000 * state.pulseSpeed + state.phase) * .22;
                    state.element.style.transform = 'translate3d(' + state.x.toFixed(2) + 'px,' + state.y.toFixed(2) + 'px,0) scale(' + pulse.toFixed(2) + ')';
                    state.element.style.opacity = (.48 + pulse * .38).toFixed(2);
                });

                window.requestAnimationFrame(moveParticles);
            };

            window.addEventListener('resize', function () {
                fieldWidth = field.clientWidth;
                fieldHeight = field.clientHeight;
            });

            window.requestAnimationFrame(moveParticles);
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const announcement = document.getElementById('androidLaunchAnnouncement');
        const dismissButton = document.getElementById('dismissAndroidLaunch');

        if (!announcement || !dismissButton) return;

        dismissButton.addEventListener('click', function () {
            announcement.hidden = true;
        });
    });
</script>

@endsection
