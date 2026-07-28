<?php

namespace App\Services\Newsroom;

use App\Services\Ai\GeminiAiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class NewsroomFeedService
{
    public function __construct(
        private GeminiAiService $geminiAiService
    ) {
    }

    public function feed(bool $refresh = false): array
    {
        $cacheKey = 'newsroom.curated-feed.v1';

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(config('newsroom.cache_minutes', 120)), function () {
            $rawItems = $this->fetchItems();
            $curated = $this->curate($rawItems);

            return [
                'items' => $curated['items'],
                'digest' => $curated['digest'],
                'keywords' => config('newsroom.keywords', []),
                'generated_at' => now()->format('d M Y h:i A'),
                'ai_model' => $curated['model'],
                'ai_available' => $curated['ai_available'],
            ];
        });
    }

    private function fetchItems(): array
    {
        $items = [];
        $keywords = config('newsroom.keywords', []);
        $limit = (int) config('newsroom.per_keyword_limit', 6);

        foreach ($keywords as $keyword) {
            foreach ($this->fetchKeywordItems($keyword) as $item) {
                $items[] = $item;
            }
        }

        return collect($items)
            ->filter(fn ($item) => filled($item['title'] ?? null) && filled($item['url'] ?? null))
            ->unique(fn ($item) => $this->canonicalTitle($item['title']))
            ->sortByDesc(fn ($item) => $item['published_timestamp'] ?? 0)
            ->take(max($limit * max(count($keywords), 1), (int) config('newsroom.display_limit', 12)))
            ->values()
            ->map(function ($item, $index) {
                $item['index'] = $index;
                return $item;
            })
            ->all();
    }

    private function fetchKeywordItems(string $keyword): array
    {
        try {
            $response = Http::timeout(12)
                ->retry(1, 300)
                ->get(config('newsroom.google_news.base_url'), [
                    'q' => $keyword,
                    'hl' => config('newsroom.google_news.locale'),
                    'gl' => config('newsroom.google_news.country'),
                    'ceid' => config('newsroom.google_news.ceid'),
                ]);
        } catch (Throwable) {
            return [];
        }

        if ($response->failed()) {
            return [];
        }

        return $this->parseRss($response->body(), $keyword);
    }

    private function parseRss(string $xml, string $keyword): array
    {
        $previous = libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$feed || !isset($feed->channel->item)) {
            return [];
        }

        $items = [];

        foreach ($feed->channel->item as $item) {
            $publishedAt = (string) ($item->pubDate ?? '');
            $timestamp = $publishedAt ? strtotime($publishedAt) : null;
            $source = isset($item->source) ? (string) $item->source : null;

            $items[] = [
                'title' => trim((string) $item->title),
                'url' => trim((string) $item->link),
                'source' => $source ?: 'Google News',
                'published_at' => $timestamp ? date('d M Y', $timestamp) : null,
                'published_timestamp' => $timestamp ?: 0,
                'snippet' => $this->cleanSnippet((string) ($item->description ?? '')),
                'keyword' => $keyword,
            ];
        }

        return array_slice($items, 0, (int) config('newsroom.per_keyword_limit', 6));
    }

    private function curate(array $rawItems): array
    {
        if (empty($rawItems)) {
            return [
                'items' => [],
                'digest' => null,
                'model' => null,
                'ai_available' => false,
            ];
        }

        try {
            $aiResult = $this->geminiAiService->curateNewsItems($rawItems);
            $selected = collect($aiResult['items'] ?? [])
                ->map(function ($curation) use ($rawItems) {
                    $index = (int) ($curation['index'] ?? -1);
                    $item = $rawItems[$index] ?? null;

                    if (!$item) {
                        return null;
                    }

                    return array_merge($item, [
                        'category' => $curation['category'] ?? 'STEM',
                        'summary' => $curation['summary'] ?? $item['snippet'],
                        'learning_angle' => $curation['learning_angle'] ?? null,
                        'relevance_score' => (int) ($curation['relevance_score'] ?? 70),
                    ]);
                })
                ->filter()
                ->sortByDesc('relevance_score')
                ->take((int) config('newsroom.display_limit', 12))
                ->values()
                ->all();

            if (!empty($selected)) {
                return [
                    'items' => $selected,
                    'digest' => $aiResult['digest'] ?? null,
                    'model' => $aiResult['model'] ?? null,
                    'ai_available' => true,
                ];
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return [
            'items' => collect($rawItems)
                ->take((int) config('newsroom.display_limit', 12))
                ->map(function ($item) {
                    return array_merge($item, [
                        'category' => $this->categoryFromKeyword($item['keyword'] ?? ''),
                        'summary' => $item['snippet'] ?: 'Open the source article for full details.',
                        'learning_angle' => 'Review this update for possible classroom discussion or project inspiration.',
                        'relevance_score' => null,
                    ]);
                })
                ->values()
                ->all(),
            'digest' => 'Latest STEM, ATL and education technology updates from configured news sources.',
            'model' => null,
            'ai_available' => false,
        ];
    }

    private function cleanSnippet(string $snippet): string
    {
        $snippet = html_entity_decode(strip_tags($snippet));
        $snippet = preg_replace('/\s+/', ' ', $snippet);

        return trim(mb_substr($snippet, 0, 260));
    }

    private function canonicalTitle(string $title): string
    {
        $title = mb_strtolower($title);
        $title = preg_replace('/\s+-\s+.+$/', '', $title);
        $title = preg_replace('/[^a-z0-9]+/i', ' ', $title);

        return trim($title);
    }

    private function categoryFromKeyword(string $keyword): string
    {
        $keyword = mb_strtolower($keyword);

        return match (true) {
            str_contains($keyword, 'robot') => 'Robotics',
            str_contains($keyword, 'atl') => 'ATL',
            str_contains($keyword, 'iot') => 'IoT',
            str_contains($keyword, 'arduino') => 'Electronics',
            str_contains($keyword, 'ai') => 'AI',
            str_contains($keyword, 'coding') => 'Coding',
            default => 'STEM',
        };
    }
}
