<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('contents')
            ->select(['file_path', 'preview_pdf_path'])
            ->orderBy('id')
            ->chunk(100, function ($contents) {
                foreach ($contents as $content) {
                    $this->movePathToPrivate($content->file_path);
                    $this->movePathToPrivate($content->preview_pdf_path);
                }
            });
    }

    public function down(): void
    {
        DB::table('contents')
            ->select(['file_path', 'preview_pdf_path'])
            ->orderBy('id')
            ->chunk(100, function ($contents) {
                foreach ($contents as $content) {
                    $this->movePathToPublic($content->file_path);
                    $this->movePathToPublic($content->preview_pdf_path);
                }
            });
    }

    private function movePathToPrivate($path): void
    {
        if (!$path || !Storage::disk('public')->exists($path)) {
            return;
        }

        if (!Storage::disk('local')->exists($path)) {
            Storage::disk('local')->put($path, Storage::disk('public')->get($path));
        }

        Storage::disk('public')->delete($path);
    }

    private function movePathToPublic($path): void
    {
        if (!$path || !Storage::disk('local')->exists($path)) {
            return;
        }

        if (!Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, Storage::disk('local')->get($path));
        }

        Storage::disk('local')->delete($path);
    }
};
