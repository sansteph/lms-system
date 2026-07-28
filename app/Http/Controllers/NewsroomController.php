<?php

namespace App\Http\Controllers;

use App\Services\Newsroom\NewsroomFeedService;
use Illuminate\Http\Request;

class NewsroomController extends Controller
{
    public function index(Request $request, NewsroomFeedService $feedService)
    {
        $feed = $feedService->feed($request->boolean('refresh'));

        return view('newsroom.index', [
            'articles' => $feed['items'] ?? [],
            'digest' => $feed['digest'] ?? null,
            'keywords' => $feed['keywords'] ?? [],
            'generatedAt' => $feed['generated_at'] ?? null,
            'aiModel' => $feed['ai_model'] ?? null,
            'aiAvailable' => $feed['ai_available'] ?? false,
        ]);
    }
}
