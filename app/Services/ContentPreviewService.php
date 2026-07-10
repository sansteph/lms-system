<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ContentPreviewService
{
    private const OFFICE_EXTENSIONS = ['ppt', 'pptx', 'doc', 'docx'];

    public function isOfficeFile(?string $path): bool
    {
        return in_array(strtolower(pathinfo((string) $path, PATHINFO_EXTENSION)), self::OFFICE_EXTENSIONS, true);
    }

    public function isPdf(?string $path): bool
    {
        return strtolower(pathinfo((string) $path, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function previewExists(?string $path): bool
    {
        return filled($path) && Storage::disk('local')->exists($path);
    }

    public function generatePreviewPdf(?string $filePath): ?string
    {
        if (!$filePath || !$this->isOfficeFile($filePath)) {
            return null;
        }

        if (!Storage::disk('local')->exists($filePath)) {
            Log::warning('Content preview conversion skipped because source file is missing.', [
                'file_path' => $filePath,
            ]);

            return null;
        }

        $inputPath = Storage::disk('local')->path($filePath);
        $workRoot = Storage::disk('local')->path('content-preview-work/' . uniqid('preview_', true));
        $outputDir = $workRoot . DIRECTORY_SEPARATOR . 'out';
        $profileDir = $workRoot . DIRECTORY_SEPARATOR . 'profile';

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        if (!is_dir($profileDir)) {
            mkdir($profileDir, 0775, true);
        }

        try {
            $process = new Process([
                config('content_preview.libreoffice_path', 'soffice'),
                '--headless',
                '--nologo',
                '--nofirststartwizard',
                '--norestore',
                '-env:UserInstallation=' . $this->fileUri($profileDir),
                '--convert-to',
                'pdf',
                '--outdir',
                $outputDir,
                $inputPath,
            ]);

            $process->setTimeout((int) config('content_preview.timeout', 90));
            $process->run();

            $convertedPath = $outputDir . DIRECTORY_SEPARATOR . pathinfo($inputPath, PATHINFO_FILENAME) . '.pdf';

            if (!$process->isSuccessful() || !is_file($convertedPath)) {
                Log::warning('Content preview conversion failed.', [
                    'file_path' => $filePath,
                    'exit_code' => $process->getExitCode(),
                    'stdout' => $process->getOutput(),
                    'stderr' => $process->getErrorOutput(),
                ]);

                return null;
            }

            $previewPath = 'content-previews/' . uniqid('preview_', true) . '.pdf';
            Storage::disk('local')->put($previewPath, file_get_contents($convertedPath));

            return $previewPath;
        } catch (\Throwable $exception) {
            Log::warning('Content preview conversion threw an exception.', [
                'file_path' => $filePath,
                'message' => $exception->getMessage(),
            ]);

            return null;
        } finally {
            $this->deleteDirectory($workRoot);
        }
    }

    private function fileUri(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return PHP_OS_FAMILY === 'Windows'
            ? 'file:///' . ltrim($path, '/')
            : 'file://' . $path;
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
