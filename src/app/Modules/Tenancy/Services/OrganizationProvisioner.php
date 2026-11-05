<?php

namespace App\Modules\Tenancy\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\Category;
use App\Modules\Support\Models\SlaPolicy;
use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\MembershipStatus;
use App\Shared\Enums\OrganizationPlan;
use App\Shared\Enums\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationProvisioner
{
    public function provision(User $user, ?string $organizationName = null): Organization
    {
        $name = trim($organizationName ?: 'Ruang Kerja '.$user->name);
        $name = $name === '' ? 'Ruang Kerja' : $name;

        return DB::transaction(function () use ($user, $name): Organization {
            $organization = Organization::create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'plan' => OrganizationPlan::Free,
            ]);

            Membership::create([
                'organization_id' => $organization->getKey(),
                'user_id' => $user->getKey(),
                'role' => UserRole::Owner,
                'status' => MembershipStatus::Active,
                'joined_at' => now(),
            ]);

            // Seed default ticket categories
            foreach ([
                ['Akun', 'Akses, password, dan profil.'],
                ['Billing', 'Invoice, pembayaran, dan langganan.'],
                ['Teknis', 'Bug, error, dan integrasi.'],
                ['Pengiriman', 'Status dan kendala pengiriman.'],
                ['Lainnya', 'Pertanyaan umum di luar kategori lain.'],
            ] as $index => [$name, $description]) {
                Category::query()->firstOrCreate(
                    [
                        'organization_id' => $organization->getKey(),
                        'slug' => \Str::slug($name),
                    ],
                    [
                        'name' => $name,
                        'description' => $description,
                        'is_active' => true,
                        'sort_order' => $index,
                    ]
                );
            }

            // Seed default Knowledge Base category
            \App\Modules\KnowledgeBase\Models\Category::query()->firstOrCreate(
                [
                    'organization_id' => $organization->getKey(),
                    'slug' => 'akun-dan-login',
                ],
                [
                    'name' => 'Akun dan Login',
                    'description' => 'Panduan akses akun dan pemulihan password.',
                    'sort_order' => 0,
                ]
            );

            // Seed default SLA policies
            SlaPolicy::query()->firstOrCreate(
                [
                    'organization_id' => $organization->getKey(),
                    'name' => 'Standar',
                ],
                [
                    'first_response_minutes' => 60,
                    'resolution_minutes' => 1440,
                    'business_hours' => null,
                    'is_default' => true,
                ]
            );

            SlaPolicy::query()->firstOrCreate(
                [
                    'organization_id' => $organization->getKey(),
                    'name' => 'Prioritas',
                ],
                [
                    'first_response_minutes' => 15,
                    'resolution_minutes' => 240,
                    'business_hours' => null,
                    'is_default' => false,
                ]
            );

            return $organization;
        });
    }

    public function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $base = $base === '' ? 'organisasi' : Str::limit($base, 100, '');

        $slug = $base;
        $suffix = 2;

        while (Organization::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
