<?php

namespace App\Modules\Identity\Http\Controllers\Auth;

use App\Modules\Identity\Http\Requests\RegisterRequest;
use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Services\OrganizationProvisioner;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisteredUserController
{
    public function create(): View
    {
        return view('pages.auth.register');
    }

    public function store(RegisterRequest $request, OrganizationProvisioner $provisioner): RedirectResponse
    {
        // 1. Create user with email only (name is derived from email initially)
        $email = $request->string('email')->toString();
        $name = Str::before($email, '@');

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $request->string('password')->toString(),
            'email_verified_at' => null,
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'is_active' => true,
        ]);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        // 2. Redirect straight to onboarding to set up their workspace or customer portal
        return redirect()->route('onboarding.create');
    }
}
