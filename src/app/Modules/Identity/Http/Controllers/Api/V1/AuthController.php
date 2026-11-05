<?php

namespace App\Modules\Identity\Http\Controllers\Api\V1;

use App\Modules\Identity\Http\Requests\Api\LoginRequest;
use App\Modules\Identity\Http\Requests\Api\RegisterRequest;
use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Services\OrganizationProvisioner;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController
{
    public function register(RegisterRequest $request, OrganizationProvisioner $provisioner): JsonResponse
    {
        $user = DB::transaction(function () use ($request, $provisioner): User {
            $user = User::create([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'password' => $request->string('password')->toString(),
                'email_verified_at' => null,
                'timezone' => 'UTC',
                'locale' => 'id',
                'is_active' => true,
            ]);

            $provisioner->provision($user, $request->string('organization_name')->toString() ?: null);

            return $user;
        });

        event(new Registered($user));

        $token = $user->createToken($request->string('device_name')->toString() ?: 'api')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $this->userPayload($user),
                'token' => $token,
                'token_type' => 'Bearer',
                'memberships' => $this->membershipPayload($user),
            ],
            'message' => 'Registered',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if (
            $user === null
            || ! $user->is_active
            || $user->password === null
            || ! Hash::check($request->string('password')->toString(), $user->password)
        ) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai.',
            ]);
        }

        $token = $user->createToken($request->string('device_name')->toString() ?: 'api')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $this->userPayload($user),
                'token' => $token,
                'token_type' => 'Bearer',
                'memberships' => $this->membershipPayload($user),
            ],
            'message' => 'Logged in',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'data' => [
                'user' => $this->userPayload($user),
                'memberships' => $this->membershipPayload($user),
            ],
            'message' => 'OK',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:191'],
        ]);

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Jika email terdaftar, tautan reset password sudah dikirim.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:191'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return response()->json(['message' => 'Password berhasil diperbarui.']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function userPayload(User $user): array
    {
        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'locale' => $user->locale,
            'timezone' => $user->timezone,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function membershipPayload(User $user): array
    {
        return $user->memberships()
            ->with('organization')
            ->where('status', 'active')
            ->orderBy('id')
            ->get()
            ->map(fn ($membership): array => [
                'organization_id' => $membership->organization_id,
                'organization_name' => $membership->organization?->name,
                'organization_slug' => $membership->organization?->slug,
                'role' => $membership->role->value,
                'status' => $membership->status->value,
            ])
            ->all();
    }
}
