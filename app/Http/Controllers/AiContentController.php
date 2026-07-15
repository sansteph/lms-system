<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Services\Ai\AiContentSummaryService;
use Illuminate\Http\Request;

class AiContentController extends Controller
{
    public function generateSummary(Request $request, Content $content, AiContentSummaryService $summaryService)
    {
        $this->authorizeContentManagement($content);

        try {
            $summaryService->generate($content, session('user_id'));
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->back()
                ->with('error', $exception->getMessage());
        }

        return redirect()->back()
            ->with('success', 'AI summary generated successfully.');
    }

    private function authorizeContentManagement(Content $content): void
    {
        if (session('user_role') == 'Admin') {
            return;
        }

        if (
            session('user_role') == 'InstituteAdmin' &&
            $content->institute &&
            $content->institute == session('user_institute')
        ) {
            return;
        }

        abort(403, 'You are not authorized to manage AI summaries for this content.');
    }
}
