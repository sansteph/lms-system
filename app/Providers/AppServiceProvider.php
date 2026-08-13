<?php

namespace App\Providers;

use App\Models\LmsNotification;
use App\Models\Student;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $popupNotifications = collect();

            if (!Schema::hasTable('lms_notifications')) {
                $view->with('popupNotifications', $popupNotifications);
                return;
            }

            $audience = null;
            $institute = null;
            $viewerKey = null;

            if (session('user_role') == 'Teacher' && session('user_id')) {
                $audience = 'teachers';
                $institute = session('user_institute');
                $viewerKey = 'teacher-' . session('user_id');
            } elseif (session('student_id')) {
                $student = Student::find(session('student_id'));
                $audience = 'students';
                $institute = $student?->institute;
                $viewerKey = 'student-' . session('student_id');
            }

            if (!$audience || !$viewerKey) {
                $view->with('popupNotifications', $popupNotifications);
                return;
            }

            $today = now()->toDateString();
            $loginDeliveries = session('teacher_login_notification_deliveries', []);

            $popupNotifications = LmsNotification::query()
                ->where('status', 'active')
                ->whereIn('target', ['all', $audience])
                ->where(function ($query) use ($institute) {
                    $query->whereNull('institute')
                        ->when($institute, fn ($scope) => $scope->orWhere('institute', $institute));
                })
                ->where(function ($query) use ($today) {
                    $query->whereNull('starts_at')
                        ->orWhereDate('starts_at', '<=', $today);
                })
                ->where(function ($query) use ($today) {
                    $query->whereNull('expires_at')
                        ->orWhereDate('expires_at', '>=', $today);
                })
                ->when($audience === 'teachers', function ($query) use ($loginDeliveries) {
                    $query->where(function ($scope) use ($loginDeliveries) {
                        $scope->whereNull('notification_type')
                            ->orWhere('notification_type', '!=', 'admin_topic_complete')
                            ->orWhereIn('id', array_keys($loginDeliveries));
                    });
                })
                ->latest()
                ->get()
                ->map(function (LmsNotification $notification) use ($loginDeliveries) {
                    $deliveryNumber = $loginDeliveries[$notification->id] ?? null;

                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'institute' => $notification->institute ?: 'All Institutes',
                        'created_at' => $notification->created_at?->format('d M Y'),
                        'signature' => $notification->notification_type === 'admin_topic_complete' && $deliveryNumber
                            ? 'admin-topic-complete-' . $notification->id . '-' . $deliveryNumber
                            : $notification->id . '-' . optional($notification->updated_at)->timestamp,
                    ];
                })
                ->values();

            $view->with('popupNotifications', $popupNotifications);
            $view->with('notificationPopupViewerKey', $viewerKey);
        });
    }
}
