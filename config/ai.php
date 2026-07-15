<?php

return [
    'provider' => env('AI_PROVIDER', 'gemini'),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.1-flash-lite'),
        'endpoint' => env('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 60),
    ],

    'content' => [
        'max_summary_input_chars' => (int) env('AI_MAX_SUMMARY_INPUT_CHARS', 24000),
        'pdftotext_path' => env('AI_PDFTOTEXT_PATH', 'pdftotext'),
    ],
];
