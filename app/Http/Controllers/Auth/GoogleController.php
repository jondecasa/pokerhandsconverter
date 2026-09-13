<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Throwable;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            /** @var SocialiteUser $googleUser */
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')->withErrors([
                'email' => 'Could not sign in with Google. Please try again.',
            ]);
        }

        // Google always verifies the email behind an account; check the field
        // defensively anyway in case the userinfo payload ever omits it.
        $verified = $googleUser->user['email_verified']
            ?? $googleUser->user['verified_email']
            ?? true;

        if (! $verified) {
            return redirect()->route('login')->withErrors([
                'email' => "Your Google account's email is not verified.",
            ]);
        }

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            if (blank($user->google_id)) {
                $user->forceFill(['google_id' => $googleUser->getId()])->save();
            }
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: Str::before($googleUser->getEmail(), '@'),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                // Unusable random password — the account signs in through Google only.
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user, remember: true);

        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
