<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Exceptions\GoogleLinkingException;
use App\Modules\Identity\Models\SocialAccount;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GoogleAccountLinker
{
    public function linkOrCreate(SocialiteUser $googleUser): User
    {
        $providerUserId = (string) $googleUser->getId();
        $email = Str::lower(trim((string) $googleUser->getEmail()));
        $raw = $googleUser instanceof AbstractUser && is_array($googleUser->user) ? $googleUser->user : [];

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new GoogleLinkingException('Email dari Google tidak valid.');
        }

        if (($raw['email_verified'] ?? false) !== true) {
            throw GoogleLinkingException::emailUnverified();
        }

        $account = SocialAccount::query()
            ->with('user')
            ->where('provider', 'google')
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($account !== null) {
            $user = $account->user;

            if ($user === null) {
                throw new GoogleLinkingException('Akun Google ini tidak terhubung dengan user yang valid.');
            }

            if (! $user->is_active) {
                throw GoogleLinkingException::accountInactive();
            }

            $account->forceFill([
                'email' => $email,
                'avatar_url' => $googleUser->getAvatar(),
            ])->save();

            return $user;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            if (! $user->hasVerifiedEmail()) {
                throw GoogleLinkingException::emailAlreadyRegisteredUnverified();
            }

            if (! $user->is_active) {
                throw GoogleLinkingException::accountInactive();
            }
        } else {
            $user = DB::transaction(function () use ($googleUser, $email): User {
                $user = User::create([
                    'name' => $googleUser->getName() ?: $email,
                    'email' => $email,
                    'password' => null,
                    'timezone' => 'UTC',
                    'locale' => 'id',
                    'avatar_path' => null,
                    'is_active' => true,
                ]);

                // Google has already verified ownership of this address.
                $user->forceFill(['email_verified_at' => now()])->save();

                return $user;
            });
        }

        SocialAccount::create([
            'user_id' => $user->getKey(),
            'provider' => 'google',
            'provider_user_id' => $providerUserId,
            'email' => $email,
            'avatar_url' => $googleUser->getAvatar(),
        ]);

        return $user;
    }
}
