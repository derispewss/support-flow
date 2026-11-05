<?php

namespace App\Modules\Support\Http\Controllers\Admin;

use App\Modules\Support\Models\Webhook;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebhookController
{
    public function index(TenantContext $context): View
    {
        $organization = $this->organization($context);

        return view('pages.admin.webhooks', ['webhooks' => $organization->webhooks()->withCount('deliveries')->latest()->get()]);
    }

    public function store(Request $request, TenantContext $context): RedirectResponse
    {
        $organization = $this->organization($context);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'url' => ['required', 'url', 'max:2048'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'max:100'],
        ]);
        $organization->webhooks()->create([
            'name' => $data['name'],
            'url' => $data['url'],
            'secret' => Str::random(48),
            'events' => array_values($data['events']),
        ]);

        return back()->with('status', 'Webhook ditambahkan.');
    }

    public function destroy(Webhook $webhook, TenantContext $context): RedirectResponse
    {
        abort_unless($webhook->organization_id === $this->organization($context)->id, 404);
        $webhook->delete();

        return back()->with('status', 'Webhook dihapus.');
    }

    private function organization(TenantContext $context): Organization
    {
        $organization = $context->organization();
        abort_unless($organization instanceof Organization, 403);

        return $organization;
    }
}
