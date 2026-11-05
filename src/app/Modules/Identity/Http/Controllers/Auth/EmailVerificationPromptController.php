<?php

namespace App\Modules\Identity\Http\Controllers\Auth;

use App\Modules\Identity\Support\AuthenticationRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController
{
    public function __invoke(Request $request, AuthenticationRedirector $redirector): View|RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return $redirector->for($request->user());
        }

        return view('pages.auth.verify-email', [
            'email' => $request->user()?->email,
        ]);
    }
}
