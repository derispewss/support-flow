<?php

use App\Modules\Identity\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Modules\Identity\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Modules\Identity\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Modules\Identity\Http\Controllers\Auth\GoogleAuthController;
use App\Modules\Identity\Http\Controllers\Auth\NewPasswordController;
use App\Modules\Identity\Http\Controllers\Auth\PasswordResetLinkController;
use App\Modules\Identity\Http\Controllers\Auth\RegisteredUserController;
use App\Modules\Identity\Http\Controllers\Auth\RoleSelectionController;
use App\Modules\Identity\Http\Controllers\Auth\VerifyEmailController;
use App\Modules\KnowledgeBase\Http\Controllers\AdminKnowledgeBaseController;
use App\Modules\KnowledgeBase\Http\Controllers\PublicKnowledgeBaseController;
use App\Modules\Support\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Modules\Support\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Modules\Support\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Modules\Support\Http\Controllers\Admin\ReportController;
use App\Modules\Support\Http\Controllers\Admin\SlaPolicyController as AdminSlaPolicyController;
use App\Modules\Support\Http\Controllers\Admin\TagController as AdminTagController;
use App\Modules\Support\Http\Controllers\Admin\TeamController as AdminTeamController;
use App\Modules\Support\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Modules\Support\Http\Controllers\Admin\WebhookController as AdminWebhookController;
use App\Modules\Support\Http\Controllers\Portal\DashboardController;
use App\Modules\Support\Http\Controllers\Portal\NotificationController;
use App\Modules\Support\Http\Controllers\Portal\TicketController as PortalTicketController;
use App\Modules\Support\Http\Controllers\Portal\TicketMessageController as PortalTicketMessageController;
use App\Modules\Tenancy\Http\Controllers\InvitationController;
use App\Modules\Tenancy\Http\Controllers\MembersController;
use App\Modules\Tenancy\Http\Controllers\OnboardingController;
use App\Modules\Tenancy\Http\Controllers\OrganizationSwitchController;
use App\Modules\Tenancy\Models\Organization;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::view('/', 'pages.public.landing')->name('home');
Route::view('/help', 'pages.public.help')->name('help.index');
Route::get('/help/{organization:slug}', [PublicKnowledgeBaseController::class, 'index'])->name('help.organization');
Route::get('/help/{organization:slug}/articles/{article:slug}', [PublicKnowledgeBaseController::class, 'show'])->name('help.article');
Route::post('/help/{organization:slug}/articles/{article:slug}/vote', [PublicKnowledgeBaseController::class, 'vote'])
    ->middleware('throttle:30,1')->name('help.article.vote');
Route::get('/robots.txt', fn () => response("User-agent: *\nAllow: /\nSitemap: ".url('/sitemap.xml')."\n", 200, ['Content-Type' => 'text/plain']));
Route::get('/sitemap.xml', function () {
    $urls = [url('/'), url('/help')];
    foreach (Organization::query()->with('articles')->get() as $organization) {
        $urls[] = route('help.organization', $organization);
        foreach ($organization->articles->where('status', 'published') as $article) {
            $urls[] = route('help.article', [$organization, $article]);
        }
    }

    return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
})->name('sitemap');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:register');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password')
        ->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password')
        ->name('password.store');

    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
        ->middleware('throttle:oauth')
        ->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->middleware('throttle:oauth')
        ->name('auth.google.callback');
});

// Role selection after registration (must be authenticated)
Route::middleware('auth')->group(function (): void {
    Route::get('/register/role', function () {
        return view('pages.auth.choose-role');
    })->name('register.role');
    Route::post('/register/role', [RoleSelectionController::class, 'store'])
        ->name('register.role.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Email verification
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', EmailVerificationNotificationController::class)
        ->middleware('throttle:verification')
        ->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Onboarding & tenant switching
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function (): void {
    Route::get('/onboarding', [OnboardingController::class, 'create'])->name('onboarding.create');
    Route::post('/onboarding/role', [OnboardingController::class, 'selectRole'])
        ->middleware('verified')
        ->name('onboarding.role');
    Route::get('/onboarding/customer', [OnboardingController::class, 'customer'])->name('onboarding.customer');
    Route::post('/onboarding', [OnboardingController::class, 'store'])
        ->middleware('verified')
        ->name('onboarding.store');

    Route::post('/organizations/{organization}/switch', OrganizationSwitchController::class)
        ->name('organizations.switch');
});

/*
|--------------------------------------------------------------------------
| Invitations
|--------------------------------------------------------------------------
*/
Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])
    ->middleware(['auth', 'verified'])
    ->name('invitations.accept');
Route::post('/invitations/{invitation}/accept-from-customer', [InvitationController::class, 'acceptFromCustomer'])
    ->middleware(['auth', 'verified'])
    ->name('invitations.accept-from-customer');

Route::middleware(['auth', 'verified', 'tenant', 'customer.portal'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

/*
|--------------------------------------------------------------------------
| Customer portal
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tenant', 'customer.portal', 'organization'])->group(function (): void {

    Route::get('/tickets', [PortalTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [PortalTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [PortalTicketController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('tickets.store');
    Route::get('/tickets/{ticket}', [PortalTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/messages', [PortalTicketMessageController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('tickets.messages.store');
    Route::get('/tickets/{ticket}/attachments/{attachment}', [PortalTicketController::class, 'download'])
        ->name('tickets.attachments.download');

});

/*
|--------------------------------------------------------------------------
| Agent / admin console
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'tenant', 'organization', 'role:superadmin,owner,admin,agent'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{ticket}/attachments/{attachment}', [PortalTicketController::class, 'download'])
        ->name('tickets.attachments.download');
    Route::get('/tickets/{ticket}/attachments/{attachment}/preview', [PortalTicketController::class, 'preview'])
        ->name('tickets.attachments.preview');
    Route::get('/reports', [ReportController::class, 'index'])->middleware('role:superadmin,owner,admin')->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->middleware('role:superadmin,owner,admin')->name('reports.export');
    Route::get('/knowledge-base', [AdminKnowledgeBaseController::class, 'index'])->middleware('role:superadmin,owner,admin')->name('knowledge-base.index');
    Route::post('/knowledge-base/articles', [AdminKnowledgeBaseController::class, 'store'])->middleware('role:superadmin,owner,admin')->name('knowledge-base.articles.store');
    Route::patch('/knowledge-base/articles/{article}', [AdminKnowledgeBaseController::class, 'update'])->middleware('role:superadmin,owner,admin')->name('knowledge-base.articles.update');
    Route::delete('/knowledge-base/articles/{article}', [AdminKnowledgeBaseController::class, 'destroy'])->middleware('role:superadmin,owner,admin')->name('knowledge-base.articles.destroy');

    Route::middleware('role:superadmin,owner,admin')->group(function (): void {
        Route::get('/members', [MembersController::class, 'index'])->name('members.index');
        Route::post('/members/invitations', [MembersController::class, 'storeInvitation'])
            ->middleware('throttle:10,1')
            ->name('members.invitations.store');
        Route::delete('/members/invitations/{invitation}', [MembersController::class, 'destroyInvitation'])
            ->name('members.invitations.destroy');
        Route::patch('/members/{membership}/role', [MembersController::class, 'updateRole'])->name('members.role.update');
        Route::patch('/members/{membership}/status', [MembersController::class, 'updateStatus'])->name('members.status.update');
        Route::delete('/members/{membership}', [MembersController::class, 'destroy'])->name('members.destroy');
        Route::get('/webhooks', [AdminWebhookController::class, 'index'])->name('webhooks.index');
        Route::post('/webhooks', [AdminWebhookController::class, 'store'])->name('webhooks.store');
        Route::delete('/webhooks/{webhook}', [AdminWebhookController::class, 'destroy'])->name('webhooks.destroy');

        Route::prefix('settings')->name('settings.')->group(function (): void {
            Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
            Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
            Route::patch('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('/tags', [AdminTagController::class, 'index'])->name('tags.index');
            Route::post('/tags', [AdminTagController::class, 'store'])->name('tags.store');
            Route::patch('/tags/{tag}', [AdminTagController::class, 'update'])->name('tags.update');
            Route::delete('/tags/{tag}', [AdminTagController::class, 'destroy'])->name('tags.destroy');

            Route::get('/sla', [AdminSlaPolicyController::class, 'index'])->name('sla.index');
            Route::post('/sla', [AdminSlaPolicyController::class, 'store'])->name('sla.store');
            Route::patch('/sla/{slaPolicy}', [AdminSlaPolicyController::class, 'update'])->name('sla.update');
            Route::delete('/sla/{slaPolicy}', [AdminSlaPolicyController::class, 'destroy'])->name('sla.destroy');

            Route::get('/teams', [AdminTeamController::class, 'index'])->name('teams.index');
            Route::post('/teams', [AdminTeamController::class, 'store'])->name('teams.store');
            Route::patch('/teams/{team}', [AdminTeamController::class, 'update'])->name('teams.update');
            Route::delete('/teams/{team}', [AdminTeamController::class, 'destroy'])->name('teams.destroy');
        });

        Route::get('/audit', [AdminAuditLogController::class, 'index'])->name('audit.index');
    });
});
