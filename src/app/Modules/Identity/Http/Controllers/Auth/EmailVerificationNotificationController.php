<?php

namespace App\Modules\Identity\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard')->with('status', 'Email Anda sudah terverifikasi.');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'Tautan verifikasi baru sudah dikirim ke email Anda.');
    }
}
