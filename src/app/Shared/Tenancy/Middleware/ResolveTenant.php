<?php

namespace App\Shared\Tenancy\Middleware;

use App\Shared\Enums\MembershipStatus;
use App\Shared\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(protected TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->clear();

        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $membership = null;
        $header = $request->header('X-Organization');
        $hasHeader = is_string($header) && $header !== '';

        if ($hasHeader) {
            $selected = ctype_digit($header) ? (int) $header : -1;
        } else {
            $selected = $request->hasSession()
                ? $request->session()->get('current_organization_id')
                : null;
        }

        if ($selected !== null) {
            $membership = $user->memberships()
                ->with('organization')
                ->where('organization_id', $selected)
                ->where('status', MembershipStatus::Active->value)
                ->first();
        }

        if ($membership === null && ! $hasHeader) {
            $membership = $user->memberships()
                ->with('organization')
                ->where('status', MembershipStatus::Active->value)
                ->orderByRaw("CASE WHEN role = 'customer' THEN 1 ELSE 0 END")
                ->orderBy('id')
                ->first();
        }

        if ($membership !== null && $membership->organization !== null) {
            $this->context->set($membership->organization, $membership);

            if ($request->hasSession()) {
                $request->session()->put('current_organization_id', $membership->organization_id);
            }
        }

        return $next($request);
    }
}
