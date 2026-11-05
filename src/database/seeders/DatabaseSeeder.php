<?php

namespace Database\Seeders;

use App\Modules\Identity\Models\User;
use App\Modules\KnowledgeBase\Models\Article;
use App\Modules\KnowledgeBase\Models\Category as KnowledgeBaseCategory;
use App\Modules\Support\Models\CannedResponse;
use App\Modules\Support\Models\Category;
use App\Modules\Support\Models\SlaPolicy;
use App\Modules\Support\Models\Tag;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketMessage;
use App\Modules\Support\Models\Webhook;
use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Models\Organization;
use App\Modules\Tenancy\Models\Team;
use App\Shared\Enums\MembershipStatus;
use App\Shared\Enums\MessageType;
use App\Shared\Enums\OrganizationPlan;
use App\Shared\Enums\TicketPriority;
use App\Shared\Enums\TicketStatus;
use App\Shared\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $organization = Organization::query()->firstOrCreate(
            ['slug' => 'contoh-nusantara'],
            [
                'name' => 'PT Contoh Nusantara',
                'plan' => OrganizationPlan::Pro,
                'settings' => [
                    'brand_color' => '#cf4a1b',
                ],
            ],
        );

        $people = [
            ['Superadmin SupportFlow', 'superadmin@supportflow.test', UserRole::Superadmin],
            ['Rina Wulandari', 'owner@supportflow.test', UserRole::Owner],
            ['Dimas Prakoso', 'agent@supportflow.test', UserRole::Agent],
            ['Budi Santoso', 'customer@supportflow.test', UserRole::Customer],
        ];

        foreach ($people as [$name, $email, $role]) {
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('Password123'),
                    'timezone' => 'Asia/Jakarta',
                    'locale' => 'id',
                    'is_active' => true,
                ],
            );

            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            Membership::query()->updateOrCreate(
                [
                    'organization_id' => $organization->getKey(),
                    'user_id' => $user->getKey(),
                ],
                [
                    'role' => $role,
                    'status' => MembershipStatus::Active,
                    'joined_at' => now(),
                ],
            );
        }

        $owner = User::query()->where('email', 'owner@supportflow.test')->firstOrFail();
        $agent = User::query()->where('email', 'agent@supportflow.test')->firstOrFail();
        $customer = User::query()->where('email', 'customer@supportflow.test')->firstOrFail();

        $team = Team::query()->firstOrCreate(
            [
                'organization_id' => $organization->getKey(),
                'name' => 'Lini Depan',
            ],
            [
                'description' => 'Menangani tiket masuk dan triase awal.',
                'lead_id' => $agent->getKey(),
            ],
        );

        $categories = [];

        foreach ([
            ['Akun', 'Akses, password, dan profil.'],
            ['Billing', 'Invoice, pembayaran, dan langganan.'],
            ['Teknis', 'Bug, error, dan integrasi.'],
            ['Pengiriman', 'Status dan kendala pengiriman.'],
            ['Lainnya', 'Pertanyaan umum di luar kategori lain.'],
        ] as $index => [$name, $description]) {
            $categories[$name] = Category::query()->firstOrCreate(
                [
                    'organization_id' => $organization->getKey(),
                    'slug' => Str::slug($name),
                ],
                [
                    'name' => $name,
                    'description' => $description,
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );
        }

        $tags = [];

        foreach (['login', 'invoice', 'bug', 'refund', 'akun', 'integrasi', 'mobile', 'email', 'urgent', 'pengiriman'] as $name) {
            $tags[$name] = Tag::query()->firstOrCreate(
                [
                    'organization_id' => $organization->getKey(),
                    'name' => $name,
                ],
                [
                    'color' => '#'.substr(md5($name), 0, 6),
                ],
            );
        }

        $standardSla = SlaPolicy::query()->firstOrCreate(
            [
                'organization_id' => $organization->getKey(),
                'name' => 'Standar',
            ],
            [
                'first_response_minutes' => 60,
                'resolution_minutes' => 1440,
                'business_hours' => null,
                'is_default' => true,
            ],
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
            ],
        );

        CannedResponse::query()->firstOrCreate(
            [
                'organization_id' => $organization->getKey(),
                'title' => 'Sapaan awal',
            ],
            [
                'body' => 'Halo, terima kasih sudah menghubungi tim support. Kami sedang meninjau laporan Anda.',
                'created_by' => $agent->getKey(),
            ],
        );

        $kbCategory = KnowledgeBaseCategory::query()->firstOrCreate(
            [
                'organization_id' => $organization->getKey(),
                'slug' => 'akun-dan-login',
            ],
            [
                'name' => 'Akun dan Login',
                'description' => 'Panduan akses akun dan pemulihan password.',
                'sort_order' => 0,
            ],
        );

        Article::query()->firstOrCreate(
            [
                'organization_id' => $organization->getKey(),
                'slug' => 'cara-reset-password',
            ],
            [
                'kb_category_id' => $kbCategory->getKey(),
                'author_id' => $agent->getKey(),
                'title' => 'Cara reset password',
                'body' => 'Gunakan menu Lupa password pada halaman login, lalu ikuti tautan yang dikirim ke email Anda.',
                'status' => 'published',
                'published_at' => now()->subDay(),
            ],
        );

        Webhook::query()->firstOrCreate(
            [
                'organization_id' => $organization->getKey(),
                'name' => 'Demo webhook',
            ],
            [
                'url' => 'https://example.test/supportflow/webhook',
                'secret' => 'demo-secret-change-me',
                'events' => ['ticket.created', 'ticket.sla_breached'],
                'is_active' => true,
            ],
        );

        if ($organization->tickets()->count() > 0) {
            return;
        }

        $subjects = [
            'Tidak bisa login setelah reset password',
            'Invoice bulan ini belum diterima',
            'Lampiran gagal diunggah di mobile',
            'Permintaan upgrade paket tim',
            'Integrasi email berhenti mengirim',
            'Status pengiriman tidak berubah',
            'Error 500 saat membuka dashboard',
            'Refund untuk pesanan yang dibatalkan',
            'Butuh akses tambahan untuk anggota baru',
            'Notifikasi tiket tidak masuk',
        ];

        $statuses = [
            TicketStatus::New,
            TicketStatus::Open,
            TicketStatus::Pending,
            TicketStatus::Resolved,
            TicketStatus::Closed,
        ];

        $priorities = [
            TicketPriority::Low,
            TicketPriority::Normal,
            TicketPriority::High,
            TicketPriority::Urgent,
        ];

        $categoryNames = array_keys($categories);
        $tagNames = array_keys($tags);

        for ($index = 0; $index < 30; $index++) {
            $status = $statuses[$index % count($statuses)];
            $priority = $priorities[$index % count($priorities)];
            $categoryName = $categoryNames[$index % count($categoryNames)];

            $ticket = Ticket::query()->create([
                'organization_id' => $organization->getKey(),
                'subject' => $subjects[$index % count($subjects)].($index >= count($subjects) ? ' #'.($index + 1) : ''),
                'description' => 'Laporan demo nomor '.($index + 1).'. Mohon bantu tindak lanjut dan informasikan perkembangan berikutnya.',
                'status' => $status,
                'priority' => $priority,
                'channel' => $index % 3 === 0 ? 'web' : 'portal',
                'category_id' => $categories[$categoryName]->getKey(),
                'requester_id' => $customer->getKey(),
                'assignee_id' => $status === TicketStatus::New ? null : $agent->getKey(),
                'team_id' => $status === TicketStatus::New ? null : $team->getKey(),
                'sla_policy_id' => $standardSla->getKey(),
                'first_response_due_at' => now()->addHour(),
                'due_at' => now()->addDay(),
                'resolved_at' => in_array($status, [TicketStatus::Resolved, TicketStatus::Closed], true) ? now()->subDay() : null,
                'closed_at' => $status === TicketStatus::Closed ? now() : null,
                'last_activity_at' => now()->subMinutes(($index + 1) * 17),
            ]);

            TicketMessage::query()->create([
                'ticket_id' => $ticket->getKey(),
                'user_id' => $customer->getKey(),
                'type' => MessageType::Reply,
                'body' => $ticket->description,
                'is_internal' => false,
            ]);

            if ($status !== TicketStatus::New) {
                TicketMessage::query()->create([
                    'ticket_id' => $ticket->getKey(),
                    'user_id' => $agent->getKey(),
                    'type' => MessageType::Reply,
                    'body' => 'Halo, terima kasih atas laporannya. Kami sudah mencatat detailnya dan sedang menindaklanjuti.',
                    'is_internal' => false,
                ]);
            }

            if ($index % 4 === 0) {
                TicketMessage::query()->create([
                    'ticket_id' => $ticket->getKey(),
                    'user_id' => $agent->getKey(),
                    'type' => MessageType::Note,
                    'body' => 'Catatan internal: cek log server sebelum menghubungi customer.',
                    'is_internal' => true,
                ]);
            }

            $ticket->tags()->sync([
                $tags[$tagNames[$index % count($tagNames)]]->getKey(),
                $tags[$tagNames[($index + 3) % count($tagNames)]]->getKey(),
            ]);

            $ticket->watchers()->syncWithoutDetaching([$customer->getKey()]);
        }

        CannedResponse::query()->firstOrCreate(
            [
                'organization_id' => $organization->getKey(),
                'title' => 'Permintaan informasi tambahan',
            ],
            [
                'body' => 'Boleh kirimkan tangkapan layar dan waktu kejadian agar kami bisa menelusuri lebih cepat?',
                'created_by' => $owner->getKey(),
            ],
        );
    }
}
