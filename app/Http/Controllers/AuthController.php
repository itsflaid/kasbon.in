<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class AuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors([
                'google' => 'Gagal masuk dengan Google. Silakan coba lagi.',
            ]);
        }

        $email = $googleUser->getEmail();

        if (! $email) {
            return redirect()->route('login')->withErrors([
                'google' => 'Akun Google tidak memiliki alamat email.',
            ]);
        }

        $googleId = $googleUser->getId();

        if (! $googleId) {
            return redirect()->route('login')->withErrors([
                'google' => 'Akun Google tidak memiliki identitas yang valid.',
            ]);
        }

        $user = User::where('google_id', $googleId)->first();

        if ($user === null) {
            $userByEmail = User::where('email', $email)->first();

            if ($userByEmail !== null && $userByEmail->google_id !== null && $userByEmail->google_id !== $googleId) {
                return redirect()->route('login')->withErrors([
                    'google' => 'Email ini sudah terhubung ke akun Google lain.',
                ]);
            }

            $user = $userByEmail;
        }

        if ($user) {
            $user->forceFill([
                'name' => $googleUser->getName() ?? $email,
                'email' => $email,
                'google_id' => $googleId,
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ])->save();
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?? $email,
                'email' => $email,
                'google_id' => $googleId,
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
