<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\{DB, Route, Schema, Storage};

class MobilePreflight extends Command
{
    protected $signature = 'mobile:preflight {--url=https://tinkedge.tech : Expected public HTTPS origin}';
    protected $description = 'Read-only mobile deployment checks; never runs migrations or changes data';

    public function handle(): int
    {
        $failures = 0;
        $check = function (bool $ok, string $message) use (&$failures): void {
            $ok ? $this->info('PASS '.$message) : $this->error('FAIL '.$message);
            if (!$ok) {
                $failures++;
            }
        };

        $expected = rtrim((string) $this->option('url'), '/');
        $check(parse_url($expected, PHP_URL_SCHEME) === 'https'
            && rtrim((string) config('app.url'), '/') === $expected, 'APP_URL matches the expected HTTPS origin');
        $check(app()->environment('production'), 'APP_ENV is production');
        $check(config('app.debug') === false, 'APP_DEBUG is false');
        $check(filled(config('app.key')), 'Existing APP_KEY is configured (do not regenerate it)');
        $check(class_exists(\Laravel\Sanctum\Sanctum::class), 'Sanctum is installed');

        $routes = collect(Route::getRoutes()->getRoutes());
        foreach (['api/login', 'api/profile', 'api/workflows/{area}', 'api/engineer/sessions/state', 'api/mobile-status'] as $uri) {
            $check($routes->contains(fn ($route) => $route->uri() === $uri), 'Registered route /'.$uri);
        }
        $profile = $routes->first(fn ($route) => $route->uri() === 'api/profile');
        $check($profile !== null
            && in_array('auth:sanctum', $profile->gatherMiddleware(), true)
            && in_array(\App\Http\Middleware\MobileRoleAccess::class, $profile->gatherMiddleware(), true),
            'Profile route enforces token and role access');
        foreach (['mobile.engineer.content-preview', 'mobile.student.content-preview', 'mobile.hybrid.content-preview'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $check($route !== null && in_array('signed', $route->gatherMiddleware(), true), 'Signed protection: '.$name);
        }

        try {
            DB::connection()->getPdo();
            $check(true, 'Database connection available');
            foreach ([
                'personal_access_tokens' => ['tokenable_type', 'tokenable_id', 'token', 'abilities'],
                'students' => ['student_id', 'password', 'institute', 'class', 'section', 'status'],
                'users' => ['role', 'password', 'institute', 'status'],
                'teaching_plans' => ['release_policy', 'start_date', 'ai_training_start_date'],
                'teaching_plan_weeks' => ['release_date', 'status'],
                'teaching_plan_items' => ['completed_by', 'completed_by_role', 'status'],
                'contents' => ['file_path', 'preview_pdf_path', 'student_file_path', 'student_preview_pdf_path'],
                'assessment_sessions' => ['started_at', 'submitted_at', 'violation_count'],
                'pending_password_changes' => ['purpose'],
                'lms_notification_login_views' => ['display_count'],
                'mobile_push_tokens' => ['fcm_token', 'pushable_type', 'pushable_id', 'last_seen_at'],
            ] as $table => $columns) {
                $check(Schema::hasTable($table) && Schema::hasColumns($table, $columns), 'Required schema: '.$table);
            }
            if (Schema::hasTable('migrations')) {
                $ran = DB::table('migrations')->pluck('migration')->all();
                $pending = array_values(array_diff(array_map(
                    fn ($file) => basename($file, '.php'), glob(database_path('migrations/*.php')) ?: []
                ), $ran));
                $check($pending === [], 'No unreviewed pending migrations');
                foreach ($pending as $migration) {
                    $this->warn('REVIEW '.$migration);
                }
            } else {
                $check(false, 'Migration history exists; inspect the live database before proceeding');
            }
        } catch (\Throwable) {
            // Connection exception text can contain credentials or host information.
            $check(false, 'Database/schema check failed; inspect server logs privately');
        }

        try {
            $root = realpath(Storage::disk('local')->path(''));
            $public = realpath(public_path());
            $outsidePublic = $root && $public && $root !== $public
                && !str_starts_with($root, $public.DIRECTORY_SEPARATOR);
            $check((bool) $outsidePublic, 'Private lesson storage is outside the public web directory');
            foreach ([storage_path(), bootstrap_path('cache'), $root] as $path) {
                $check(is_string($path) && is_dir($path) && is_writable($path), 'Writable application storage/cache directory');
            }
        } catch (\Throwable) {
            $check(false, 'Private lesson storage configured');
        }

        $check(filled(config('ai.gemini.api_key')), 'Gemini API key is configured (value hidden)');
        $check(filled(config('ai.gemini.model')), 'Gemini model configured');
        $check(!in_array(config('mail.default'), [null, '', 'log', 'array'], true), 'Production mail transport configured');
        $events = collect(app(Schedule::class)->events())->pluck('command')->filter()->implode("\n");
        $check(str_contains($events, 'teaching-plans:release-weekly'), 'Teaching-plan release task registered');
        $check(str_contains($events, 'ai-content:generate-upcoming'), 'AI preparation task registered');
        $this->line('Application timezone: '.config('app.timezone'));
        $this->warn('MANUAL: Confirm cron/worker operation, mail delivery, Gemini response, document conversion and actual device workflows.');
        $this->warn('This command does not run jobs, migrations, send mail, call Gemini or modify database records.');

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
