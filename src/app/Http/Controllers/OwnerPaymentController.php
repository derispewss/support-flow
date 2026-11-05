<?php

namespace App\Http\Controllers;

use App\Modules\Tenancy\Models\Organization;
use App\Modules\Tenancy\Models\TripayTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OwnerPaymentController extends Controller
{
    public function show(Request $request, Organization $organization): View|RedirectResponse
    {
        $user = $request->user();
        $membership = $user?->membershipFor($organization);

        if (! $membership || ! $membership->role->canManageOrganization()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $planPrices = [
            'pro' => 150000,
            'enterprise' => 500000,
        ];

        $planKey = is_string($organization->plan) ? $organization->plan : $organization->plan->value;
        $amount = $planPrices[$planKey] ?? 150000;

        return view('owner.payment', [
            'organization' => $organization,
            'plan' => $planKey,
            'amount' => $amount,
        ]);
    }

    public function process(Request $request, Organization $organization): RedirectResponse
    {
        $user = $request->user();
        $membership = $user?->membershipFor($organization);

        if (! $membership || ! $membership->role->canManageOrganization()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $planKey = is_string($organization->plan) ? $organization->plan : $organization->plan->value;
        $planPrices = [
            'pro' => 150000,
            'enterprise' => 500000,
        ];
        $amount = $planPrices[$planKey] ?? 150000;
        $fee = 2500;
        $totalAmount = $amount + $fee;

        $reference = 'DEV-TRIPAY-'.strtoupper(Str::random(10));
        $merchantRef = 'ORG-'.$organization->getKey().'-'.time();

        TripayTransaction::create([
            'reference' => $reference,
            'merchant_ref' => $merchantRef,
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'organization_name' => $organization->name,
            'plan' => $planKey,
            'payment_method' => $request->input('payment_method', 'QRIS'),
            'payment_name' => 'Tripay Sandbox QRIS / VA',
            'amount' => $amount,
            'fee' => $fee,
            'total_amount' => $totalAmount,
            'status' => 'PAID',
            'paid_at' => now(),
            'payload' => [
                'sandbox' => true,
                'gateway' => 'Tripay Sandbox Simulator',
                'paid_by' => $user->email,
            ],
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Pembayaran paket '.strtoupper($planKey).' berhasil diverifikasi (Tripay Sandbox). Selamat datang di SupportFlow!');
    }
}
