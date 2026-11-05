<?php

namespace App\Modules\Identity\Http\Controllers\Auth;

use App\Modules\Identity\Http\Requests\LoginRequest;
use App\Modules\Identity\Support\AuthenticationRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController
{
    public function create(Request $request): View
    {
        return view('pages.auth.login', [
            'email' => $request->string('email')->toString(),
            'redirect' => $this->safeRedirect($request->string('redirect')->toString()),
        ]);
    }

    public function store(LoginRequest $request, AuthenticationRedirector $redirector): RedirectResponse
    {
        $credentials = [
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'is_active' => true,
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user !== null && $user->hasVerifiedEmail()) {
            $redirect = $this->safeRedirect($request->string('redirect')->toString());

            if ($redirect !== null) {
                return redirect($redirect)->with('status', 'Selamat datang kembali.');
            }
        }

        return $redirector->for($request->user())
            ->with('status', 'Selamat datang kembali.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Anda telah keluar.');
    }

    protected function safeRedirect(string $redirect): ?string
    {
        if ($redirect === '' || ! Str::startsWith($redirect, '/') || Str::startsWith($redirect, '//')) {
            return null;
        }

        return $redirect;
    }
}
