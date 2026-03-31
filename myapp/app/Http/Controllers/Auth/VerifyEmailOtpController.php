<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailOtpController extends Controller
{
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended('/dashboard');
        }

        if (
            ! $user->email_verification_code ||
            ! $user->email_verification_code_expires_at ||
            $user->email_verification_code_expires_at->isPast()
        ) {
            return back()->withErrors(['code' => 'The verification code has expired. Please request a new one.']);
        }

        if ($user->email_verification_code !== $request->code) {
            return back()->withErrors(['code' => 'The verification code is invalid.']);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
        ])->save();

        return redirect()->intended('/dashboard')->with('status', 'Email verified successfully!');
    }
}
