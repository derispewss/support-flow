<?php

use App\Modules\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;

if (! function_exists('tenant_context')) {
    function tenant_context(): TenantContext
    {
        return app(TenantContext::class);
    }
}

if (! function_exists('tenant')) {
    function tenant(): ?Organization
    {
        return tenant_context()->organization();
    }
}
