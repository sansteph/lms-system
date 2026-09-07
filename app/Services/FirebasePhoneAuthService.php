<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class FirebasePhoneAuthService
{
    public function normalizePhoneNumber(string $phone): string
    {
        $value = trim($phone);
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (str_starts_with($value, '+')) {
            return '+' . $digits;
        }

        $countryCode = (string) config('services.firebase.default_country_code', '+91');
        $countryDigits = preg_replace('/\D+/', '', $countryCode) ?? '';
        $digits = ltrim($digits, '0');

        if ($countryDigits !== '' && strlen($digits) === 10) {
            return '+' . $countryDigits . $digits;
        }

        return '+' . $digits;
    }

    public function verifyIdToken(string $idToken): array
    {
        $apiKey = (string) config('services.firebase.web_api_key');
        if ($apiKey === '') {
            throw ValidationException::withMessages(['code' => 'Phone verification is not configured.']);
        }

        $response = Http::asJson()
            ->timeout(10)
            ->post('https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . urlencode($apiKey), [
                'idToken' => $idToken,
            ]);

        $firebaseUser = $response->json('users.0');
        if (!$response->successful() || !is_array($firebaseUser) || blank($firebaseUser['phoneNumber'] ?? null)) {
            throw ValidationException::withMessages(['code' => 'The verification code is invalid or expired.']);
        }

        return $firebaseUser;
    }

    public function phoneMatches(string $verifiedPhone, ?string $storedPhone): bool
    {
        $verifiedDigits = preg_replace('/\D+/', '', $verifiedPhone);
        $storedDigits = preg_replace('/\D+/', '', $this->normalizePhoneNumber((string) $storedPhone));

        if ($verifiedDigits === '' || $storedDigits === '') {
            return false;
        }

        return $verifiedDigits === $storedDigits
            || str_ends_with($verifiedDigits, $storedDigits)
            || str_ends_with($storedDigits, $verifiedDigits);
    }
}
