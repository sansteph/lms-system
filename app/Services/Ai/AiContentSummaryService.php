<?php

namespace App\Services\Ai;

use App\Models\AiContentSummary;
use App\Models\Content;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AiContentSummaryService
{
    public function __construct(
        private PdfTextExtractionService $pdfTextExtractionService,
        private GeminiAiService $geminiAiService
    ) {
    }

    public function generate(Content $content, ?int $generatedBy = null): AiContentSummary
    {
        $sourcePath = $this->preferredPdfPath($content);

        if (!$sourcePath) {
            throw new RuntimeException('No PDF material is available for this content.');
        }

        $absolutePath = Storage::disk('local')->path($sourcePath);

        if (!Storage::disk('local')->exists($sourcePath)) {
            throw new RuntimeException('The selected content file is missing from private storage.');
        }

        $sourceHash = hash_file('sha256', $absolutePath);
        $existing = AiContentSummary::where('content_id', $content->id)->first();

        if ($existing && $existing->status == 'generated' && $existing->source_hash == $sourceHash) {
            return $existing;
        }

        $summary = AiContentSummary::updateOrCreate(
            ['content_id' => $content->id],
            [
                'provider' => config('ai.provider', 'gemini'),
                'model' => config('ai.gemini.model'),
                'source_file_path' => $sourcePath,
                'source_hash' => $sourceHash,
                'status' => 'processing',
                'error_message' => null,
                'generated_by' => $generatedBy,
            ]
        );

        try {
            $extractedText = $this->pdfTextExtractionService->extract($absolutePath);
            $aiResult = $this->geminiAiService->generateContentSummary($content->content_title, $extractedText);

            $summary->update([
                'extracted_text' => $extractedText,
                'summary' => $aiResult['summary'],
                'key_points' => $aiResult['key_points'],
                'quiz_seed' => $aiResult['quiz_seed'],
                'model' => $aiResult['model'],
                'status' => 'generated',
                'error_message' => null,
                'generated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $summary->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $summary->fresh();
    }

    private function preferredPdfPath(Content $content): ?string
    {
        foreach ([
            $content->student_preview_pdf_path,
            $content->preview_pdf_path,
            $content->student_file_path,
            $content->file_path,
        ] as $path) {
            if ($path && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
                return $path;
            }
        }

        return null;
    }
}
