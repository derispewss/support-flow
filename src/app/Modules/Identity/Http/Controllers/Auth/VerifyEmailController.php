<?php

namespace App\Modules\Identity\Http\Controllers\Auth;

use App\Modules\Identity\Support\AuthenticationRedirector;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController
{
    public function __invoke(EmailVerificationRequest $request, AuthenticationRedirector $redirector): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $redirector->for($user)->with('status', 'Email berhasil diverifikasi.');
    }
}
