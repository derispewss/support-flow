<?php

namespace App\Modules\Identity\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoleSelectionController extends Controller
{
    /**
     * Handle role selection after registration.
     * Expected POST field "role" with value "customer" or "owner".
     */
    public function store(Request $request): RedirectResponse
    {
        $role = $request->input('role');

        if ($role === 'owner') {
            // Owner must go through onboarding to create organization
            return redirect()->route('onboarding.create');
        }

        // Default / any other value is treated as customer
        return redirect()->route('dashboard');
    }
}
