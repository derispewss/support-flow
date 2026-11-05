<?php

namespace App\Shared\Tenancy\Middleware;

use App\Shared\Enums\UserRole;
use App\Shared\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganization
{
    public function __construct(protected TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->context->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Organisasi belum dipilih.'], 409);
            }

            $user = $request->user();

            if ($user?->onboarding_role !== UserRole::Owner->value) {
                return redirect()
                    ->route('dashboard')
                    ->with('status', 'Hubungkan organisasi terlebih dahulu untuk mengakses fitur tiket.');
            }

            return redirect()
                ->route('onboarding.create')
                ->with('status', 'Buat organisasi terlebih dahulu untuk melanjutkan.');
        }

        return $next($request);
    }
}
