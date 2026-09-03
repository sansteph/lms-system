<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MobileStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        // A public contract marker, not a database or infrastructure health probe.
        return response()->json([
            'service' => 'innovatEdge-mobile',
            'api_version' => 1,
            'release' => '2026-09-02',
        ])->header('Cache-Control', 'no-store');
    }
}
