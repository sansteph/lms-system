<?php

namespace App\Services;

use App\Models\Content;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class MobilePreviewService
{
    private const TYPES = [
        'pdf' => ['document', 'application/pdf'],
        'jpg' => ['image', 'image/jpeg'], 'jpeg' => ['image', 'image/jpeg'],
        'png' => ['image', 'image/png'], 'gif' => ['image', 'image/gif'],
        'webp' => ['image', 'image/webp'],
        'mp4' => ['video', 'video/mp4'], 'webm' => ['video', 'video/webm'],
        'ogg' => ['video', 'video/ogg'], 'mov' => ['video', 'video/quicktime'],
        'mp3' => ['audio', 'audio/mpeg'], 'wav' => ['audio', 'audio/wav'],
    ];

    public function path(Content $content, string $audience): ?string
    {
        if ($audience === 'teacher') {
            return $content->preview_pdf_path ?: $content->file_path;
        }

        // A student-specific material must never be replaced by the teacher's guide.
        return $content->student_preview_pdf_path ?: $content->student_file_path
            ?: $content->preview_pdf_path ?: $content->file_path;
    }

    public function payload(Content $content, string $route, array $parameters, string $audience = 'student'): array
    {
        $path = $this->path($content, $audience);
        abort_unless($path, 404, 'No lesson file is available.');
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $type = self::TYPES[$extension] ?? null;
        if ($audience !== 'teacher' && ($type[0] ?? null) === 'audio') $type = null;

        return [
            'success' => true, 'content_id' => $content->id,
            'title' => $content->content_title, 'content_type' => $content->content_type,
            'extension' => $extension, 'preview_kind' => $type[0] ?? null,
            'mime_type' => $type[1] ?? null,
            'preview_url' => $type ? URL::temporarySignedRoute($route, now()->addMinutes(10), $parameters) : null,
            'preview_message' => $type ? null : 'Inline preview is not available for this file type. Upload a PDF or supported media version.',
        ];
    }

    public function response(Content $content, string $audience = 'student')
    {
        $path = $this->path($content, $audience);
        abort_unless($path, 404, 'No lesson file is available.');
        $type = self::TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;
        abort_unless($type && ($audience === 'teacher' || $type[0] !== 'audio'), 404, 'Unsupported preview format.');
        $disk = Storage::disk('local');
        $root = realpath($disk->path(''));
        $resolved = realpath($disk->path($path));
        abort_unless($root && $resolved && is_file($resolved) &&
            str_starts_with($resolved, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR), 404, 'Preview file is unavailable.');

        // BinaryFileResponse handles byte ranges for seeking without buffering the full video.
        return response()->file($resolved, [
            'Content-Type' => $type[1], 'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache', 'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
