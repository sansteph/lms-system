<?php

namespace App\Services;

use App\Models\LmsNotification;
use App\Models\MobilePushToken;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirebasePushService
{
    public function sendLmsNotification(LmsNotification $notification): void
    {
        try {
            $this->deliverLmsNotification($notification);
        } catch (Throwable $error) {
            Log::warning('Firebase push delivery skipped after an unexpected error.', [
                'notification_id' => $notification->id,
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function deliverLmsNotification(LmsNotification $notification): void
    {
        if (!$this->shouldSend($notification)) {
            return;
        }

        $credentials = $this->credentials();
        if (!$credentials) {
            Log::info('Firebase push skipped: credentials are not configured.');
            return;
        }

        $accessToken = $this->accessToken($credentials);
        if (!$accessToken) {
            Log::warning('Firebase push skipped: access token unavailable.');
            return;
        }

        $projectId = $credentials['project_id'] ?? config('services.firebase.project_id');
        if (!$projectId) {
            Log::warning('Firebase push skipped: project_id unavailable.');
            return;
        }

        $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $tokens = $this->tokensFor($notification);

        foreach ($tokens as $token) {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post($endpoint, [
                    'message' => [
                        'token' => $token->fcm_token,
                        'notification' => [
                            'title' => $notification->title,
                            'body' => $notification->message,
                        ],
                        'data' => [
                            'type' => 'lms_notification',
                            'notification_id' => (string) $notification->id,
                            'target' => (string) $notification->target,
                        ],
                        'android' => [
                            'priority' => 'HIGH',
                        ],
                    ],
                ]);

            if ($response->failed()) {
                $error = $response->json('error.status');
                if (in_array($error, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
                    $token->delete();
                    continue;
                }
                Log::warning('Firebase push send failed.', [
                    'notification_id' => $notification->id,
                    'token_id' => $token->id,
                    'status' => $response->status(),
                    'error' => $error,
                ]);
            }
        }
    }

    private function shouldSend(LmsNotification $notification): bool
    {
        if ($notification->status !== 'active') {
            return false;
        }

        $today = now()->toDateString();
        if ($notification->starts_at && $notification->starts_at->toDateString() > $today) {
            return false;
        }
        if ($notification->expires_at && $notification->expires_at->toDateString() < $today) {
            return false;
        }

        return true;
    }

    private function tokensFor(LmsNotification $notification)
    {
        return MobilePushToken::query()
            ->when($notification->target === 'teachers', fn ($query) => $query->where('role', 'STEM Engineer'))
            ->when($notification->target === 'students', fn ($query) => $query->whereIn('role', ['Student', 'Hybrid Learner']))
            ->when($notification->institute, function ($query) use ($notification) {
                $query->where(function ($scope) use ($notification) {
                    $scope
                        ->where(function ($users) use ($notification) {
                            $users->where('pushable_type', User::class)
                                ->whereExists(function ($exists) use ($notification) {
                                    $exists->selectRaw('1')
                                        ->from('users')
                                        ->whereColumn('users.id', 'mobile_push_tokens.pushable_id')
                                        ->where('users.institute', $notification->institute);
                                });
                        })
                        ->orWhere(function ($students) use ($notification) {
                            $students->where('pushable_type', Student::class)
                                ->whereExists(function ($exists) use ($notification) {
                                    $exists->selectRaw('1')
                                        ->from('students')
                                        ->whereColumn('students.id', 'mobile_push_tokens.pushable_id')
                                        ->where('students.institute', $notification->institute);
                                });
                        });
                });
            })
            ->latest('last_seen_at')
            ->get();
    }

    private function credentials(): ?array
    {
        $raw = config('services.firebase.service_account_json');
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $path = config('services.firebase.credentials');
        if (is_string($path) && is_file($path) && is_readable($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function accessToken(array $credentials): ?string
    {
        $clientEmail = $credentials['client_email'] ?? null;
        $privateKey = $credentials['private_key'] ?? null;
        if (!$clientEmail || !$privateKey) {
            return null;
        }

        return Cache::remember('firebase_access_token_' . sha1($clientEmail), 3300, function () use ($clientEmail, $privateKey) {
            $now = time();
            $claim = [
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            $jwt = $this->jwt($claim, $privateKey);
            if (!$jwt) {
                return null;
            }

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            return $response->successful() ? $response->json('access_token') : null;
        });
    }

    private function jwt(array $claim, string $privateKey): ?string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64Url(json_encode($header)),
            $this->base64Url(json_encode($claim)),
        ];
        $unsigned = implode('.', $segments);

        $ok = openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            return null;
        }

        $segments[] = $this->base64Url($signature);
        return implode('.', $segments);
    }

    private function base64Url(string|false $value): string
    {
        return rtrim(strtr(base64_encode((string) $value), '+/', '-_'), '=');
    }
}
