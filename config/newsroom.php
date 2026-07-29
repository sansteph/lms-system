<?php

return [
    'cache_minutes' => (int) env('NEWSROOM_CACHE_MINUTES', 120),
    'per_keyword_limit' => (int) env('NEWSROOM_PER_KEYWORD_LIMIT', 6),
    'display_limit' => (int) env('NEWSROOM_DISPLAY_LIMIT', 12),

    'keywords' => array_values(array_filter(array_map(
        'trim',
        explode(',', env(
            'NEWSROOM_KEYWORDS',
            'STEM education,ATL lab,robotics for schools,AI in education,IoT projects for students,Arduino robotics,electronics components,sensors actuators microcontrollers,global STEM projects,student robotics competition,STEM innovation competition,science fair projects,hackathons for students,school innovation labs'
        ))
    ))),

    'google_news' => [
        'base_url' => env('NEWSROOM_GOOGLE_NEWS_URL', 'https://news.google.com/rss/search'),
        'locale' => env('NEWSROOM_GOOGLE_NEWS_LOCALE', 'en-IN'),
        'country' => env('NEWSROOM_GOOGLE_NEWS_COUNTRY', 'IN'),
        'ceid' => env('NEWSROOM_GOOGLE_NEWS_CEID', 'IN:en'),
    ],
];
