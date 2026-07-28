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
@endphp

<div class="container-fluid {{ $isBlogsModule ? 'blogs-social-page' : '' }}">
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

            @if($isBlogsModule)
                <div class="blogs-social-hero">
                    <div>
                        <span class="blogs-social-kicker">InnovatEdge Community</span>
                        <h2>Blogs</h2>
                        <p>Share relevant STEM updates, achievements, project ideas and classroom highlights.</p>
                    </div>
                    <div class="blogs-social-user">
                        <span>{{ $actor['name'] }}</span>
                        <strong>{{ $actor['type'] === 'Teacher' ? 'STEM Engineer' : $actor['type'] }}</strong>
                    </div>
                </div>
            @else
                <div class="page-header mb-4">
                    <h2>Blogs</h2>
                    <p class="text-muted mb-0">
                        Share relevant STEM updates, achievements, project ideas and classroom highlights.
                    </p>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    Please check the highlighted fields and try again.
                </div>
            @endif

            <div class="community-feed-layout">
                <div class="community-compose-card">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="community-avatar">
                            {{ strtoupper(substr($actor['name'] ?? 'U', 0, 1)) }}
                        </div>
                        <div>
                            <h5 class="mb-1">Create a Post</h5>
                            <p class="text-muted mb-0">
                                @if($actor['type'] === 'Student')
                                    Student posts are sent for Admin or STEM Engineer approval.
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
                                          rows="5"
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
                </div>

                <div class="community-filter-card">
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route($routePrefix . '.community-feed') }}"
                               class="btn btn-sm {{ !request('type') && !request('status') ? 'btn-primary' : 'btn-outline-primary' }}">
                                All Posts
                            </a>

                            @foreach($postTypes as $postType)
                                <a href="{{ route($routePrefix . '.community-feed', ['type' => $postType]) }}"
                                   class="btn btn-sm {{ request('type') === $postType ? 'btn-primary' : 'btn-outline-primary' }}">
                                    {{ $postType }}
                                </a>
                            @endforeach
                        </div>

                        @if($canModerate)
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route($routePrefix . '.community-feed', ['status' => 'Pending']) }}"
                                   class="btn btn-sm {{ request('status') === 'Pending' ? 'btn-warning' : 'btn-outline-warning' }}">
                                    Pending {{ $pendingCount ? '(' . $pendingCount . ')' : '' }}
                                </a>
                                <a href="{{ route($routePrefix . '.community-feed', ['status' => 'Approved']) }}"
                                   class="btn btn-sm {{ request('status') === 'Approved' ? 'btn-success' : 'btn-outline-success' }}">
                                    Approved
                                </a>
                                <a href="{{ route($routePrefix . '.community-feed', ['status' => 'Rejected']) }}"
                                   class="btn btn-sm {{ request('status') === 'Rejected' ? 'btn-danger' : 'btn-outline-danger' }}">
                                    Rejected
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="community-post-list">
                    @forelse($posts as $post)
                        @php
                            $liked = $post->isLikedBy($actor['type'], $actor['id']);
                            $isOwner = $post->author_type === $actor['type'] && (int) $post->author_id === (int) $actor['id'];
                            $authorImage = $post->authorProfileImage();
                            $canModeratePost = $canModerate
                                && $post->status === 'Pending'
                                && (
                                    $isAdmin
                                    || (
                                        $actor['type'] === 'Teacher'
                                        && $post->author_type === 'Student'
                                        && $post->institute === $actor['institute']
                                    )
                                );
                        @endphp

                        <article class="community-post-card community-post-{{ strtolower($post->author_type) }}">
                            <div class="community-post-header">
                                @if($authorImage)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($authorImage) }}"
                                         alt="{{ $post->authorName() }}"
                                         class="community-avatar-img small">
                                @elseif($post->author_type === 'Student')
                                    <div class="community-avatar small community-avatar-icon">
                                        <i class="fa fa-user-graduate"></i>
                                    </div>
                                @else
                                    <div class="community-avatar small">
                                        {{ strtoupper(substr($post->authorName(), 0, 1)) }}
                                    </div>
                                @endif

                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <h5 class="mb-0">{{ $post->authorName() }}</h5>
                                        @if($post->author_type !== 'Student')
                                            <span class="badge bg-light text-dark border">{{ $post->roleLabel() }}</span>
                                        @endif
                                        <span class="badge bg-primary-subtle text-primary">{{ $post->post_type }}</span>
                                        @if($post->isSystemSynced())
                                            <span class="badge bg-success-subtle text-success">Auto Shared</span>
                                        @endif
                                        @if($post->status !== 'Approved')
                                            <span class="badge {{ $post->status === 'Pending' ? 'bg-warning text-dark' : 'bg-danger' }}">
                                                {{ $post->status }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-muted small">
                                        {{ $post->authorMeta() ?: 'InnovatEdge' }}
                                        &middot;
                                        {{ optional($post->published_at ?? $post->created_at)->format('d M Y, h:i A') }}
                                    </div>
                                </div>
                            </div>

                            <div class="community-post-body">
                                <h4>{{ $post->title }}</h4>
                                <p>{{ $post->body }}</p>
                            </div>

                            @if($post->image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($post->image_path) }}"
                                     alt="{{ $post->title }}"
                                     class="community-post-image">
                            @endif

                            @if($post->attachment_path)
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($post->attachment_path) }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="community-attachment">
                                    <i class="fa fa-paperclip"></i>
                                    {{ $post->attachment_original_name ?: 'View Attachment' }}
                                </a>
                            @endif

                            <div class="community-post-actions">
                                @if($post->status === 'Approved')
                                    <form method="POST" action="{{ route($routePrefix . '.community-feed.like', $post->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $liked ? 'btn-primary' : 'btn-outline-primary' }}">
                                            <i class="{{ $liked ? 'fa fa-heart' : 'fa-regular fa-heart' }}"></i>
                                            {{ $post->likes->count() }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">
                                        <i class="fa fa-clock me-1"></i>
                                        Awaiting publication
                                    </span>
                                @endif

                                <div class="ms-auto d-flex flex-wrap gap-2">
                                    @if($canModeratePost)
                                        <form method="POST" action="{{ route($routePrefix . '.community-feed.approve', $post->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                Approve
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route($routePrefix . '.community-feed.reject', $post->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Reject
                                            </button>
                                        </form>
                                    @endif

                                    @if($isAdmin || ($isOwner && $post->status !== 'Approved'))
                                        <form method="POST" action="{{ route($routePrefix . '.community-feed.delete', $post->id) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Delete this community post?')">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="community-empty-state">
                            <i class="fa fa-comments"></i>
                                <h4>No community posts yet</h4>
                            <p>Share the first blog update, achievement or classroom highlight.</p>
                        </div>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $posts->links() }}
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
