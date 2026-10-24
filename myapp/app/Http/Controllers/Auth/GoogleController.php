<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect to Google OAuth consent screen.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the Google OAuth callback.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            logger()->error('Google OAuth failed', ['error' => $e->getMessage()]);

            return redirect('/login')->with('status', 'Google authentication failed. Please try again.');
        }

        // Only trust an email Google itself reports as verified.
        $raw = $googleUser->user ?? [];
        if (! ($raw['email_verified'] ?? $raw['verified_email'] ?? false) || ! $googleUser->getEmail()) {
            return redirect('/login')->with('status', 'Your Google email is not verified. Please verify it with Google and try again.');
        }

        // Check if a user with this email already exists
        $existingUser = User::where('email', $googleUser->getEmail())->first();

        if ($existingUser) {
            // Link google_id if not already set
            if (! $existingUser->google_id) {
                $existingUser->forceFill(['google_id' => $googleUser->getId()])->save();
            }

            // Linking verifies the email. If the account was never verified, someone else may have
            // registered it with a password of their choosing, so that password is dropped.
            if (! $existingUser->hasVerifiedEmail()) {
                $existingUser->forceFill(['email_verified_at' => now(), 'password' => null])->save();
            }

            // If 2FA is enabled, redirect to challenge
            if ($existingUser->hasEnabledTwoFactorAuthentication()) {
                session(['login' => ['id' => $existingUser->id, 'remember' => false]]);

                return redirect('/two-factor-challenge');
            }

            Auth::login($existingUser, remember: true);

            return redirect()->intended('/dashboard');
        }

        // New user — store Google data in session and redirect to registration
        session(['google_user' => [
            'id' => $googleUser->getId(),
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
        ]]);

        return redirect('/register?via=google');
    }
}
