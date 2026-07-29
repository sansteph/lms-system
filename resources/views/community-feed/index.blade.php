@extends('layouts.app')

@section('content')

@php
    $isBlogsModule = $isBlogsModule ?? false;
    $routePrefix = $isBlogsModule
        ? 'blogs'
        : ($actor['type'] === 'Teacher'
            ? 'teacher'
            : ($actor['type'] === 'Student' ? 'student' : 'admin'));
    $isAdmin = in_array($actor['type'], ['Admin', 'InstituteAdmin'], true);
    $canModerate = in_array($actor['type'], ['Admin', 'InstituteAdmin', 'Teacher'], true);
    $activeTab = $activeTab ?? 'feed';
    $baseRoute = $routePrefix . '.community-feed';
    $actorLabel = $actor['type'] === 'Teacher' ? 'STEM Engineer' : ($actor['type'] === 'InstituteAdmin' ? 'Institute Admin' : $actor['type']);

    $navItems = [
        'feed' => ['label' => 'Feed', 'icon' => 'fa-newspaper', 'description' => 'Active posts'],
        'profile' => ['label' => 'Profile', 'icon' => 'fa-user-circle', 'description' => 'Your profile'],
    ];

    if ($canModerate) {
        $navItems['approvals'] = ['label' => 'Approvals', 'icon' => 'fa-user-shield', 'description' => 'Review posts'];
    }

    $navItems['post'] = ['label' => 'Post', 'icon' => 'fa-edit', 'description' => 'Create post'];
@endphp

<div class="container-fluid {{ $isBlogsModule ? 'blogs-social-page' : '' }}">
    <div class="community-post-focus-backdrop" data-community-post-backdrop></div>

    <div class="row">

        @if(!$isBlogsModule)
            @if($actor['type'] === 'Teacher')
                @include('layouts.teacher-sidebar')
            @elseif($actor['type'] === 'Student')
                @include('layouts.student-sidebar')
            @else
                @include('layouts.sidebar')
            @endif
        @endif

        <div class="{{ $isBlogsModule ? 'col-12 blogs-social-shell' : 'col-md-10 col-lg-10 p-4' }}">

            <div class="blogs-social-hero">
                <div>
                    <span class="blogs-social-kicker">InnovatEdge Community</span>
                    <h2>Blogs</h2>
                    <p>Share STEM updates, achievements, project ideas and classroom highlights in one moderated community space.</p>
                </div>
                <div class="blogs-social-user">
                    @if(!empty($actor['profile_image']))
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($actor['profile_image']) }}"
                             alt="{{ $actor['name'] }}"
                             class="blogs-social-user-photo">
                    @else
                        <div class="blogs-social-user-mark">
                            {{ strtoupper(substr($actor['name'] ?? 'U', 0, 1)) }}
                        </div>
                    @endif
                    <div class="blogs-social-user-copy">
                        <span>{{ $actor['name'] }}</span>
                        <strong>{{ $actorLabel }}</strong>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    Please check the highlighted fields and try again.
                </div>
            @endif

            <nav class="community-icon-nav" aria-label="Blogs sections">
                @foreach($navItems as $tab => $item)
                    <a href="{{ route($baseRoute, ['tab' => $tab]) }}"
                       class="community-icon-link {{ $activeTab === $tab ? 'active' : '' }}">
                        <span class="community-icon-orb">
                            <i class="fa {{ $item['icon'] }}"></i>
                            @if($tab === 'approvals' && $pendingCount)
                                <em>{{ $pendingCount }}</em>
                            @endif
                        </span>
                        <span>
                            <strong>{{ $item['label'] }}</strong>
                            <small>{{ $item['description'] }}</small>
                        </span>
                    </a>
                @endforeach
            </nav>

            @if($activeTab === 'post')
                <section class="community-compose-card community-feature-panel">
                    <div class="community-panel-heading">
                        <div class="community-avatar">
                            {{ strtoupper(substr($actor['name'] ?? 'U', 0, 1)) }}
                        </div>
                        <div>
                            <h3>Create a Post</h3>
                            <p>
                                @if($actor['type'] === 'Student')
                                    Student posts are submitted for Admin or STEM Engineer approval before appearing in the feed.
                                @else
                                    Your post will be published immediately.
                                @endif
                            </p>
                        </div>
                    </div>

                    <form method="POST"
                          action="{{ route($routePrefix . '.community-feed.store') }}"
                          enctype="multipart/form-data">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Post Type</label>
                                <select name="post_type" class="form-control" required>
                                    @foreach($postTypes as $postType)
                                        <option value="{{ $postType }}" @selected(old('post_type') === $postType)>
                                            {{ $postType }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-7">
                                <label class="form-label">Title</label>
                                <input type="text"
                                       name="title"
                                       class="form-control"
                                       value="{{ old('title') }}"
                                       maxlength="255"
                                       required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Post</label>
                                <textarea name="body"
                                          class="form-control community-post-textarea"
                                          rows="6"
                                          maxlength="3000"
                                          required>{{ old('body') }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Image</label>
                                <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Attachment</label>
                                <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-paper-plane me-1"></i>
                                {{ $actor['type'] === 'Student' ? 'Submit for Approval' : 'Publish Post' }}
                            </button>
                        </div>
                    </form>
                </section>
            @elseif($activeTab === 'profile')
                <section class="community-profile-panel community-feature-panel">
                    <div class="community-profile-cover"></div>
                    <div class="community-profile-body">
                        @if(!empty($profile['image']))
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($profile['image']) }}"
                                 alt="{{ $profile['name'] }}"
                                 class="community-profile-avatar">
                        @elseif($profile['type'] === 'Student')
                            <div class="community-profile-avatar community-profile-symbol">
                                <i class="fa fa-user-graduate"></i>
                            </div>
                        @else
                            <div class="community-profile-avatar community-profile-symbol">
                                {{ strtoupper(substr($profile['name'] ?? 'U', 0, 1)) }}
                            </div>
                        @endif

                        <div class="community-profile-info">
                            <span class="community-profile-role">{{ $profile['role'] }}</span>
                            <h3>{{ $profile['name'] }}</h3>
                            <p>
                                {{ $profile['institute'] ?: 'InnovatEdge' }}
                                @if($profile['type'] === 'Student' && ($profile['class'] || $profile['section']))
                                    &middot; Class {{ trim(($profile['class'] ?? '') . ' ' . ($profile['section'] ?? '')) }}
                                @endif
                            </p>

                            @if($profile['qualification'])
                                <div class="community-profile-note">
                                    <i class="fa fa-graduation-cap"></i>
                                    {{ $profile['qualification'] }}
                                </div>
                            @elseif($profile['designation'])
                                <div class="community-profile-note">
                                    <i class="fa fa-id-badge"></i>
                                    {{ $profile['designation'] }}
                                </div>
                            @endif
                        </div>

                        <div class="community-profile-stats">
                            <div>
                                <strong>{{ $profile['approved_posts'] }}</strong>
                                <span>Posts</span>
                            </div>
                            <div>
                                <strong>{{ $profile['likes'] }}</strong>
                                <span>Likes</span>
                            </div>
                            @if($profile['pending_posts'])
                                <div>
                                    <strong>{{ $profile['pending_posts'] }}</strong>
                                    <span>Pending</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                @include('community-feed.partials.posts', [
                    'posts' => $posts,
                    'actor' => $actor,
                    'routePrefix' => $routePrefix,
                    'baseRoute' => $baseRoute,
                    'canModerate' => $canModerate,
                    'isAdmin' => $isAdmin,
                    'emptyTitle' => 'No posts from this profile yet',
                    'emptyBody' => 'Approved posts from this user will appear here.'
                ])
            @else
                @if($activeTab === 'feed')
                    <div class="community-filter-card">
                        <div class="community-filter-scroll">
                            <a href="{{ route($baseRoute, ['tab' => 'feed']) }}"
                               class="btn btn-sm {{ !request('type') ? 'btn-primary' : 'btn-outline-primary' }}">
                                All Posts
                            </a>

                            @foreach($postTypes as $postType)
                                <a href="{{ route($baseRoute, ['tab' => 'feed', 'type' => $postType]) }}"
                                   class="btn btn-sm {{ request('type') === $postType ? 'btn-primary' : 'btn-outline-primary' }}">
                                    {{ $postType }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @include('community-feed.partials.posts', [
                    'posts' => $posts,
                    'actor' => $actor,
                    'routePrefix' => $routePrefix,
                    'baseRoute' => $baseRoute,
                    'canModerate' => $canModerate,
                    'isAdmin' => $isAdmin,
                    'emptyTitle' => $activeTab === 'approvals' ? 'No posts awaiting approval' : 'No community posts yet',
                    'emptyBody' => $activeTab === 'approvals' ? 'Pending student posts will appear here for review.' : 'Share the first blog update, achievement or classroom highlight.'
                ])
            @endif

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cards = document.querySelectorAll('[data-community-post-card]');
        const backdrop = document.querySelector('[data-community-post-backdrop]');
        const interactiveSelector = 'a, button, input, textarea, select, label, summary, details, form';

        function clearFocusedPost() {
            document.body.classList.remove('community-post-focus-active');
            cards.forEach(card => card.classList.remove('community-post-card-focused'));
        }

        cards.forEach(card => {
            card.addEventListener('click', function (event) {
                if (event.target.closest(interactiveSelector)) {
                    return;
                }

                const alreadyFocused = card.classList.contains('community-post-card-focused');
                clearFocusedPost();

                if (!alreadyFocused) {
                    document.body.classList.add('community-post-focus-active');
                    card.classList.add('community-post-card-focused');
                }
            });
        });

        if (backdrop) {
            backdrop.addEventListener('click', clearFocusedPost);
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                clearFocusedPost();
            }
        });
    });
</script>

@endsection
