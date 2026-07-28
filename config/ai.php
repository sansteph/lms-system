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
        'auto_generation_start_date' => env('AI_CONTENT_AUTO_GENERATION_START_DATE', '2026-07-31'),
        'auto_generation_limit' => (int) env('AI_CONTENT_AUTO_GENERATION_LIMIT', 2),
        'teacher_passing_percentage' => (float) env('AI_TEACHER_PASSING_PERCENTAGE', 50),
        'student_passing_percentage' => (float) env('AI_STUDENT_PASSING_PERCENTAGE', 60),
    ],
];
