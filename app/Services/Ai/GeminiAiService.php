<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiAiService
{
    public function generateContentSummary(string $title, string $contentText): array
    {
        $apiKey = config('ai.gemini.api_key');
        $model = config('ai.gemini.model');

        if (blank($apiKey)) {
            throw new RuntimeException('Gemini API key is missing. Add GEMINI_API_KEY to the .env file.');
        }

        $prompt = $this->summaryPrompt($title, $contentText);
        $responseText = $this->generateText($model, $prompt);
        $payload = $this->decodeJsonResponse($responseText);

        return [
            'summary' => $payload['summary'] ?? $responseText,
            'key_points' => $payload['key_points'] ?? [],
            'quiz_seed' => $payload['quiz_seed'] ?? [],
            'model' => $model,
        ];
    }

    public function evaluateQuizAnswers(string $title, array $questions, array $answers): array
    {
        $apiKey = config('ai.gemini.api_key');
        $model = config('ai.gemini.model');

        if (blank($apiKey)) {
            throw new RuntimeException('Gemini API key is missing. Add GEMINI_API_KEY to the .env file.');
        }

        $prompt = $this->evaluationPrompt($title, $questions, $answers);
        $responseText = $this->generateText($model, $prompt);
        $payload = $this->decodeJsonResponse($responseText);

        return [
            'score' => $payload['score'] ?? 0,
            'total_marks' => $payload['total_marks'] ?? collect($questions)->sum('marks'),
            'percentage' => $payload['percentage'] ?? 0,
            'feedback' => $payload['feedback'] ?? null,
            'answer_feedback' => $payload['answer_feedback'] ?? [],
            'model' => $model,
        ];
    }

    public function generateReportInsights(string $title, array $metrics): array
    {
        $apiKey = config('ai.gemini.api_key');
        $model = config('ai.gemini.model');

        if (blank($apiKey)) {
            throw new RuntimeException('Gemini API key is missing. Add GEMINI_API_KEY to the .env file.');
        }

        $prompt = $this->reportInsightsPrompt($title, $metrics);
        $responseText = $this->generateText($model, $prompt);
        $payload = $this->decodeJsonResponse($responseText);

        return [
            'summary' => $payload['summary'] ?? 'AI summary was generated, but no summary text was returned.',
            'highlights' => $payload['highlights'] ?? [],
            'risks' => $payload['risks'] ?? [],
            'recommendations' => $payload['recommendations'] ?? [],
            'model' => $model,
            'generated_at' => now()->format('d M Y h:i A'),
        ];
    }

    private function generateText(string $model, string $prompt): string
    {
        $endpoint = rtrim(config('ai.gemini.endpoint'), '/');
        $timeout = (int) config('ai.gemini.timeout', 60);

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->post($endpoint . '/models/' . $model . ':generateContent?key=' . config('ai.gemini.api_key'), [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 1400,
                    'responseMimeType' => 'application/json',
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini request failed: ' . $response->body());
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (blank($text)) {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        return trim($text);
    }

    private function summaryPrompt(string $title, string $contentText): string
    {
        $maxChars = (int) config('ai.content.max_summary_input_chars', 24000);
        $limitedText = mb_substr($contentText, 0, $maxChars);

        return <<<PROMPT
You are generating a learning support summary for InnovatEdge LMS.

Content title: {$title}

Return only valid JSON with this exact structure:
{
  "summary": "A student-friendly summary in 2 to 4 short paragraphs.",
  "key_points": ["5 to 8 important learning points"],
  "quiz_seed": [
    {
      "question": "A short assessment question based only on the content",
      "expected_answer": "The expected answer",
      "marks": 2
    }
  ]
}

Rules:
- Use only the supplied content.
- Do not invent facts.
- Keep language clear for school students.
- Create exactly 5 quiz_seed questions.

Content:
{$limitedText}
PROMPT;
    }

    private function evaluationPrompt(string $title, array $questions, array $answers): string
    {
        $questionPayload = json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $answerPayload = json_encode($answers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
You are evaluating a student AI review quiz for InnovatEdge LMS.

Content title: {$title}

Questions:
{$questionPayload}

Student answers are keyed by question id:
{$answerPayload}

Return only valid JSON with this exact structure:
{
  "score": 0,
  "total_marks": 10,
  "percentage": 0,
  "feedback": "Short student-friendly feedback.",
  "answer_feedback": [
    {
      "question_id": 1,
      "question_order": 1,
      "score": 0,
      "feedback": "Short feedback for this answer."
    }
  ]
}

Rules:
- Use the expected_answer and marks for each question.
- Award partial marks for partially correct answers.
- Do not award marks for blank or unrelated answers.
- percentage must be score / total_marks * 100 rounded to 2 decimals.
- Keep feedback concise and constructive.
PROMPT;
    }

    private function reportInsightsPrompt(string $title, array $metrics): string
    {
        $metricPayload = json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
You are generating admin-only operational insights for InnovatEdge LMS.

Report title: {$title}

Live LMS metrics:
{$metricPayload}

Return only valid JSON with this exact structure:
{
  "summary": "A concise 3 to 5 sentence executive summary.",
  "highlights": ["3 to 5 positive findings from the data"],
  "risks": ["2 to 4 risks, gaps, or areas needing attention"],
  "recommendations": ["3 to 5 practical next actions for admins"]
}

Rules:
- Use only the supplied metrics.
- Do not invent missing data.
- Keep language clear, professional, and suitable for school LMS administrators.
- If a metric is zero or missing, mention it only when it is operationally important.
PROMPT;
    }

    private function decodeJsonResponse(string $responseText): array
    {
        $cleaned = trim($responseText);
        $cleaned = preg_replace('/^```json\s*/i', '', $cleaned);
        $cleaned = preg_replace('/^```\s*/', '', $cleaned);
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);

        $decoded = json_decode($cleaned, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Gemini returned a response that could not be parsed as JSON.');
        }

        return $decoded;
    }
}
