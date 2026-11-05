<?php

namespace App\Shared\Tenancy\Middleware;

use App\Shared\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function __construct(protected TenantContext $context) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $this->context->check()) {
            return redirect()->route('onboarding.create');
        }

        $role = $this->context->role();

        if ($role === null || ! in_array($role->value, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
