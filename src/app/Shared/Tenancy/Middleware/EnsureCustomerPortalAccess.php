<?php

namespace App\Shared\Tenancy\Middleware;

use App\Shared\Enums\UserRole;
use App\Shared\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerPortalAccess
{
    public function __construct(protected TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $role = $this->context->role() ?? UserRole::tryFrom($request->user()->onboarding_role ?? '');

        if ($role === UserRole::Owner) {
            return $this->context->check()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('onboarding.create');
        }

        return $next($request);
    }
}
