@extends('layouts.app')

@section('content')

<div class="newsroom-page">
    <section class="newsroom-hero">
        <div class="container">
            <div class="newsroom-hero-inner">
                <a href="{{ route('home') }}" class="newsroom-back">
                    <i class="fa fa-arrow-left"></i>
                    Back to Home
                </a>

                <div class="newsroom-kicker">
                    <i class="fa fa-newspaper"></i>
                    AI Curated Newsroom
                </div>

                <h1>STEM, ATL and innovation updates for learning teams</h1>

                <p>
                    Fresh education and technology updates are collected from news sources,
                    filtered for classroom relevance, and summarized for InnovatEdge users.
                </p>

                <div class="newsroom-actions">
                    <a href="{{ route('newsroom', ['refresh' => 1]) }}" class="btn newsroom-refresh-btn">
                        <i class="fa fa-rotate"></i>
                        Refresh News
                    </a>

                    @if($generatedAt)
                        <span class="newsroom-updated">
                            Updated {{ $generatedAt }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="newsroom-content">
        <div class="container">
            <div class="newsroom-dashboard">
                <aside class="newsroom-side-panel">
                    <div class="newsroom-panel-card">
                        <span class="newsroom-panel-label">Focus Topics</span>
                        <div class="newsroom-topic-list">
                            @foreach($keywords as $keyword)
                                <span>{{ $keyword }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="newsroom-panel-card">
                        <span class="newsroom-panel-label">AI Status</span>
                        <strong>{{ $aiAvailable ? 'Gemini curation active' : 'Feed fallback active' }}</strong>
                        <p>
                            {{ $aiAvailable
                                ? 'Articles are ranked and summarized using the configured AI model.'
                                : 'Articles are visible from the feed, but AI curation was unavailable.' }}
                        </p>
                        @if($aiModel)
                            <small>{{ $aiModel }}</small>
                        @endif
                    </div>
                </aside>

                <main class="newsroom-main">
                    @if($digest)
                        <div class="newsroom-digest">
                            <span>Today’s Digest</span>
                            <p>{{ $digest }}</p>
                        </div>
                    @endif

                    @if(count($articles))
                        <div class="newsroom-grid">
                            @foreach($articles as $article)
                                <article class="newsroom-card">
                                    <div class="newsroom-card-top">
                                        <span class="newsroom-category">{{ $article['category'] ?? 'STEM' }}</span>
                                        @if(isset($article['relevance_score']) && $article['relevance_score'] !== null)
                                            <span class="newsroom-score">{{ $article['relevance_score'] }}%</span>
                                        @endif
                                    </div>

                                    <h2>{{ $article['title'] }}</h2>

                                    <p class="newsroom-summary">
                                        {{ $article['summary'] ?? $article['snippet'] ?? 'Open the source article for full details.' }}
                                    </p>

                                    @if(!empty($article['learning_angle']))
                                        <div class="newsroom-learning-angle">
                                            <i class="fa fa-lightbulb"></i>
                                            <span>{{ $article['learning_angle'] }}</span>
                                        </div>
                                    @endif

                                    <div class="newsroom-card-footer">
                                        <div>
                                            <strong>{{ $article['source'] ?? 'News source' }}</strong>
                                            @if(!empty($article['published_at']))
                                                <span>{{ $article['published_at'] }}</span>
                                            @endif
                                        </div>

                                        <a href="{{ $article['url'] }}"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           aria-label="Open article">
                                            <i class="fa fa-arrow-up-right-from-square"></i>
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="newsroom-empty-state">
                            <i class="fa fa-satellite-dish"></i>
                            <h2>No news could be loaded right now</h2>
                            <p>
                                Please try refreshing again later. If this continues on VPS,
                                verify outbound network access and Gemini configuration.
                            </p>
                            <a href="{{ route('newsroom', ['refresh' => 1]) }}" class="btn newsroom-refresh-btn">
                                Try Again
                            </a>
                        </div>
                    @endif
                </main>
            </div>
        </div>
    </section>
</div>

@endsection
