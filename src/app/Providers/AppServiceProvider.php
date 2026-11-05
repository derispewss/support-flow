<?php

namespace App\Providers;

use App\Modules\Support\Models\Attachment;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketMessage;
use App\Modules\Support\Policies\AttachmentPolicy;
use App\Modules\Support\Policies\TicketMessagePolicy;
use App\Modules\Support\Policies\TicketPolicy;
use App\Modules\Tenancy\Models\Invitation;
use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Models\Organization;
use App\Modules\Tenancy\Policies\InvitationPolicy;
use App\Modules\Tenancy\Policies\MembershipPolicy;
use App\Modules\Tenancy\Policies\OrganizationPolicy;
use App\Shared\Enums\OrganizationPlan;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerRateLimiters();
        $this->registerPasswordRules();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Membership::class, MembershipPolicy::class);
        Gate::policy(Invitation::class, InvitationPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(TicketMessage::class, TicketMessagePolicy::class);
        Gate::policy(Attachment::class, AttachmentPolicy::class);
    }

    protected function registerRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by($this->emailThrottleKey($request));
        });

        RateLimiter::for('register', function (Request $request): Limit {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('password', function (Request $request): Limit {
            return Limit::perMinute(3)->by($this->emailThrottleKey($request));
        });

        RateLimiter::for('oauth', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('verification', function (Request $request): Limit {
            return Limit::perMinute(3)->by($request->user()?->getAuthIdentifier() ?: $request->ip());
        });

        RateLimiter::for('api', function (Request $request): Limit {
            $organizationId = $request->header('X-Organization');
            $membership = $request->user()?->memberships()
                ->with('organization')
                ->when(is_string($organizationId) && ctype_digit($organizationId), fn ($query) => $query->where('organization_id', (int) $organizationId))
                ->where('status', 'active')
                ->first();
            $plan = $membership?->organization?->plan;
            $limit = match ($plan) {
                OrganizationPlan::Pro => 300,
                OrganizationPlan::Enterprise => 1200,
                default => 60,
            };

            return Limit::perMinute($limit)->by(($request->user()?->getAuthIdentifier() ?? 'guest').'|'.($organizationId ?: 'default'));
        });

        RateLimiter::for('upload', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->user()?->getAuthIdentifier() ?: $request->ip());
        });
    }

    protected function registerPasswordRules(): void
    {
        Password::defaults(function (): Password {
            return $this->app->isProduction()
                ? Password::min(10)->letters()->mixedCase()->numbers()->symbols()->uncompromised()
                : Password::min(8)->letters()->mixedCase()->numbers();
        });
    }

    protected function emailThrottleKey(Request $request): string
    {
        $email = $request->input('email');
        $email = is_string($email) ? Str::lower(trim($email)) : '';

        return Str::transliterate($email.'|'.$request->ip());
    }
}
