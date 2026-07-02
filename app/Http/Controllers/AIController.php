<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
    public function testGemini()
    {
        $apiKey = env('GEMINI_API_KEY');

        $url = env('GEMINI_API_URL') . '?key=' . $apiKey;

        $response = Http::post($url, [

            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => 'Draft a question-paper outline about Photosynthesis'
                        ]
                    ]
                ]
            ]

        ]);

        return $response->json();
    }
}