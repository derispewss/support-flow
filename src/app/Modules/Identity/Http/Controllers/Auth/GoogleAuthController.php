<?php

namespace App\Modules\Identity\Http\Controllers\Auth;

use App\Modules\Identity\Exceptions\GoogleLinkingException;
use App\Modules\Identity\Services\GoogleAccountLinker;
use App\Modules\Identity\Support\AuthenticationRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController
{
    public function redirect(Request $request): SymfonyRedirectResponse
    {
        if (blank(config('services.google.client_id')) || blank(config('services.google.client_secret'))) {
            return redirect()
                ->route('login')
                ->with('error', 'Login Google belum dikonfigurasi. Hubungi administrator.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, GoogleAccountLinker $linker, AuthenticationRedirector $redirector): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()
                ->route('login')
                ->with('error', 'Login Google dibatalkan.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
            $user = $linker->linkOrCreate($googleUser);
        } catch (GoogleLinkingException $exception) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => $exception->getMessage()]);
        } catch (InvalidStateException) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Sesi login Google tidak valid. Silakan coba lagi.']);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Login Google gagal. Silakan coba lagi.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return $redirector->for($user)->with('status', 'Berhasil masuk dengan Google.');
    }
}
