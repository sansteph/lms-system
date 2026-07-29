<div class="community-post-list">
    @forelse($posts as $post)
        @php
            $liked = $post->isLikedBy($actor['type'], $actor['id']);
            $isOwner = $post->author_type === $actor['type'] && (int) $post->author_id === (int) $actor['id'];
            $authorImage = $post->authorProfileImage();
            $profileUrl = route($baseRoute, [
                'tab' => 'profile',
                'profile_type' => $post->author_type,
                'profile_id' => $post->author_id,
            ]);
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
                <a href="{{ $profileUrl }}" class="community-profile-trigger" title="View profile">
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
                </a>

                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <a href="{{ $profileUrl }}" class="community-author-name">{{ $post->authorName() }}</a>
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
                    <span class="community-comment-count">
                        <i class="fa-regular fa-comment"></i>
                        {{ $post->comments->count() }}
                    </span>
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

            @if($post->status === 'Approved')
                <div class="community-comments">
                    <form method="POST"
                          action="{{ route($routePrefix . '.community-feed.comments.store', $post->id) }}"
                          class="community-comment-form">
                        @csrf
                        <div class="community-comment-input">
                            <textarea name="body"
                                      rows="2"
                                      maxlength="1200"
                                      placeholder="Write a comment..."
                                      required></textarea>
                            <button type="submit" title="Post comment">
                                <i class="fa fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>

                    @if($post->comments->isNotEmpty())
                        <div class="community-comment-list">
                            @foreach($post->comments->sortByDesc('created_at') as $comment)
                                @php
                                    $ownsComment = $comment->commenter_type === $actor['type']
                                        && (int) $comment->commenter_id === (int) $actor['id'];
                                @endphp

                                <div class="community-comment">
                                    <div class="community-comment-avatar">
                                        {{ strtoupper(substr($comment->commenterName(), 0, 1)) }}
                                    </div>
                                    <div class="community-comment-bubble">
                                        <div class="community-comment-meta">
                                            <strong>{{ $comment->commenterName() }}</strong>
                                            <span>{{ $comment->roleLabel() }} | {{ optional($comment->created_at)->format('d M Y, h:i A') }}</span>
                                        </div>
                                        <p>{{ $comment->body }}</p>

                                        @if($ownsComment)
                                            <div class="community-comment-tools">
                                                <details>
                                                    <summary>Edit</summary>
                                                    <form method="POST" action="{{ route($routePrefix . '.community-feed.comments.update', $comment->id) }}">
                                                        @csrf
                                                        <textarea name="body" rows="2" maxlength="1200" required>{{ $comment->body }}</textarea>
                                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                                    </form>
                                                </details>

                                                <form method="POST"
                                                      action="{{ route($routePrefix . '.community-feed.comments.delete', $comment->id) }}"
                                                      onsubmit="return confirm('Delete this comment?');">
                                                    @csrf
                                                    <button type="submit" class="community-comment-delete">Delete</button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </article>
    @empty
        <div class="community-empty-state">
            <i class="fa fa-comments"></i>
            <h4>{{ $emptyTitle }}</h4>
            <p>{{ $emptyBody }}</p>
        </div>
    @endforelse
</div>

@if($posts->hasPages())
    <div class="mt-4">
        {{ $posts->links() }}
    </div>
@endif
