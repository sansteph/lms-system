<?php

namespace App\Services;

use App\Models\MfaAuditLog;
use App\Models\MfaChallenge;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MfaChallengeService
{
    public function issue(object $account, string $channel, ?string $ip = null, ?string $userAgent = null): string
    {
        $this->invalidateExisting($account);

        $plainToken = Str::random(64);
        $code = str_pad((string) random_int(0, 999999), (int) config('mfa.code_length', 6), '0', STR_PAD_LEFT);
        $challenge = MfaChallenge::create([
            'challenge_token_hash' => hash('sha256', $plainToken),
            'account_type' => get_class($account),
            'account_id' => $account->getKey(),
            'channel' => $channel,
            'code_hash' => hash('sha256', $code),
            'expires_at' => now()->addMinutes((int) config('mfa.challenge_minutes', 5)),
            'resend_window_started_at' => now(),
            'last_sent_at' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        $this->send($account, $channel, $code);
        $this->audit($challenge, 'issued', true);

        return $plainToken;
    }

    public function issueFirebasePhoneChallenge(object $account, ?string $ip = null, ?string $userAgent = null): string
    {
        $this->invalidateExisting($account);

        $plainToken = Str::random(64);
        $challenge = MfaChallenge::create([
            'challenge_token_hash' => hash('sha256', $plainToken),
            'account_type' => get_class($account),
            'account_id' => $account->getKey(),
            'channel' => 'firebase_phone',
            'code_hash' => hash('sha256', Str::random(32)),
            'expires_at' => now()->addMinutes((int) config('mfa.challenge_minutes', 5)),
            'resend_window_started_at' => now(),
            'last_sent_at' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        $this->audit($challenge, 'issued', true);

        return $plainToken;
    }

    public function verifyFirebasePhone(string $plainToken, string $firebaseIdToken, string $expectedPhone, ?string $ip = null, ?string $userAgent = null): MfaChallenge
    {
        $challenge = MfaChallenge::where('challenge_token_hash', hash('sha256', $plainToken))->first();

        if (!$challenge || $challenge->channel !== 'firebase_phone' || $challenge->verified_at || $challenge->expires_at->isPast() || ($challenge->locked_until && $challenge->locked_until->isFuture())) {
            throw ValidationException::withMessages(['code' => 'The verification code is invalid or expired.']);
        }

        try {
            $firebaseUser = app(FirebasePhoneAuthService::class)->verifyIdToken($firebaseIdToken);
            $phone = (string) ($firebaseUser['phoneNumber'] ?? '');
            if (!app(FirebasePhoneAuthService::class)->phoneMatches($phone, $expectedPhone)) {
                throw new \RuntimeException('Phone number mismatch.');
            }
        } catch (\Throwable $exception) {
            $challenge->increment('attempts');
            if ($challenge->attempts >= (int) config('mfa.max_attempts', 5)) {
                $challenge->update(['locked_until' => now()->addMinutes((int) config('mfa.lockout_minutes', 15))]);
            }
            $this->audit($challenge, 'verification_failed', false, ['attempts' => $challenge->attempts]);
            throw ValidationException::withMessages(['code' => 'The verification code is invalid or expired.']);
        }

        $challenge->update(['verified_at' => now(), 'ip_address' => $ip, 'user_agent' => $userAgent]);
        $this->audit($challenge, 'verified', true);

        return $challenge;
    }

    public function verify(string $plainToken, string $code, ?string $ip = null, ?string $userAgent = null): MfaChallenge
    {
        $challenge = MfaChallenge::where('challenge_token_hash', hash('sha256', $plainToken))->first();

        if (!$challenge || $challenge->verified_at || $challenge->expires_at->isPast() || ($challenge->locked_until && $challenge->locked_until->isFuture())) {
            throw ValidationException::withMessages(['code' => 'The verification code is invalid or expired.']);
        }

        if (!hash_equals($challenge->code_hash, hash('sha256', trim($code)))) {
            $challenge->increment('attempts');
            if ($challenge->attempts >= (int) config('mfa.max_attempts', 5)) {
                $challenge->update(['locked_until' => now()->addMinutes((int) config('mfa.lockout_minutes', 15))]);
            }
            $this->audit($challenge, 'verification_failed', false, ['attempts' => $challenge->attempts]);
            throw ValidationException::withMessages(['code' => 'The verification code is invalid or expired.']);
        }

        $challenge->update(['verified_at' => now(), 'ip_address' => $ip, 'user_agent' => $userAgent]);
        $this->audit($challenge, 'verified', true);

        return $challenge;
    }

    public function resend(string $plainToken, ?string $ip = null, ?string $userAgent = null): void
    {
        $challenge = MfaChallenge::where('challenge_token_hash', hash('sha256', $plainToken))->first();

        if (!$challenge || $challenge->verified_at || $challenge->expires_at->isPast() || ($challenge->locked_until && $challenge->locked_until->isFuture())) {
            throw ValidationException::withMessages(['code' => 'The verification code is invalid or expired.']);
        }

        if ($challenge->last_sent_at && $challenge->last_sent_at->diffInSeconds(now()) < (int) config('mfa.resend_cooldown_seconds', 60)) {
            throw ValidationException::withMessages(['code' => 'Please wait before requesting another code.']);
        }

        if ($challenge->resend_window_started_at?->isBefore(now()->subHour())) {
            $challenge->update(['resend_count' => 0, 'resend_window_started_at' => now()]);
        }

        if ($challenge->resend_count >= (int) config('mfa.max_resends_per_hour', 5)) {
            $challenge->update(['locked_until' => now()->addMinutes((int) config('mfa.lockout_minutes', 15))]);
            throw ValidationException::withMessages(['code' => 'Please try again later.']);
        }

        $code = str_pad((string) random_int(0, 999999), (int) config('mfa.code_length', 6), '0', STR_PAD_LEFT);
        $challenge->update([
            'code_hash' => hash('sha256', $code),
            'resend_count' => $challenge->resend_count + 1,
            'last_sent_at' => now(),
            'expires_at' => now()->addMinutes((int) config('mfa.challenge_minutes', 5)),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        $this->send($this->resolveAccount($challenge), $challenge->channel, $code);
        $this->audit($challenge, 'resent', true);
    }

    private function send(object $account, string $channel, string $code): void
    {
        if ($channel !== 'email' || blank($account->email)) {
            throw ValidationException::withMessages(['code' => 'A verification code could not be delivered.']);
        }

        try {
            Mail::raw('Your InnovatEdge verification code is ' . $code . '. It expires in 5 minutes. If you did not request this code, ignore this message.', function ($message) use ($account) {
                $message->to($account->email)->subject('InnovatEdge verification code');
            });
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['code' => 'We could not send a verification code right now. Please try again later.']);
        }
    }

    private function resolveAccount(MfaChallenge $challenge): object
    {
        return $challenge->account_type::findOrFail($challenge->account_id);
    }

    private function invalidateExisting(object $account): void
    {
        MfaChallenge::where('account_type', get_class($account))
            ->where('account_id', $account->getKey())
            ->whereNull('verified_at')
            ->update(['expires_at' => now()]);
    }

    private function audit(MfaChallenge $challenge, string $event, bool $successful, array $metadata = []): void
    {
        MfaAuditLog::create([
            'account_type' => $challenge->account_type,
            'account_id' => $challenge->account_id,
            'event' => $event,
            'channel' => $challenge->channel,
            'successful' => $successful,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
