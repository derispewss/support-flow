@extends('layouts.public')

@section('title', config('app.name').' — Meja support untuk tiket, SLA, dan knowledge base')
@section('description', 'SupportFlow menyatukan tiket, SLA, portal customer, dan knowledge base dalam satu meja kerja yang rapi.')

@section('content')
    {{-- Hero --}}
    <section class="border-b border-rule">
        <div class="mx-auto grid max-w-[1280px] items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:gap-14 lg:px-8 lg:py-20">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-rule bg-paper-raised px-3 py-1 text-xs font-medium text-ink-soft">
                    <span class="size-2 rounded-full bg-signal" aria-hidden="true"></span>
                    Meja operasional support multi-tenant
                </div>

                <h1 class="mt-5 font-display text-4xl font-bold leading-[1.08] tracking-tight text-ink sm:text-5xl lg:text-6xl">
                    Tiket masuk.<br>
                    <span class="text-signal">Jejak tetap utuh.</span>
                </h1>

                <p class="mt-5 max-w-xl text-base leading-7 text-ink-soft">
                    SupportFlow menyatukan tiket, SLA, portal customer, dan knowledge base. Satu antrean untuk pertanyaan yang masuk, satu riwayat yang jelas saat detailnya penting.
                </p>

                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ url('/register') }}" class="btn btn-signal px-5 py-2.5">
                        Buka meja support
                        <x-heroicon-o-arrow-right class="size-4" />
                    </a>
                    <a href="{{ url('/help') }}" class="btn btn-outline px-5 py-2.5">Lihat pusat bantuan</a>
                </div>

                <dl class="mt-10 grid max-w-md grid-cols-3 gap-4 border-t border-rule pt-6">
                    @foreach ([
                        ['128', 'Tiket aktif'],
                        ['42 mnt', 'Respons pertama'],
                        ['94%', 'SLA terpenuhi'],
                    ] as [$value, $label])
                        <div>
                            <dt class="text-xs font-medium text-ink-faint">{{ $label }}</dt>
                            <dd class="mt-1 font-display text-2xl font-bold tracking-tight text-ink">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Live queue preview --}}
            <div>
                <div class="sheet overflow-hidden">
                    <div class="flex items-center justify-between border-b border-rule bg-ink px-5 py-3.5 text-paper">
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-emerald-400"></span>
                            <span class="text-xs font-semibold">Antrean hari ini</span>
                        </div>
                        <span class="font-mono text-xs text-paper/60">09:41 WIB</span>
                    </div>

                    <div class="divide-y divide-rule">
                        @foreach ([
                            ['#SF-1042', 'Tidak bisa login setelah reset password', 'Budi Santoso', 'Masuk', 'open', '12 mnt'],
                            ['#SF-1039', 'Invoice Oktober belum diterima', 'Sari Dewi', 'Tertunda', 'pending', '48 mnt'],
                            ['#SF-1031', 'Permintaan upgrade paket tim', 'Andi Pratama', 'Selesai', 'resolved', '—'],
                            ['#SF-1024', 'Lampiran gagal diunggah di mobile', 'Maya Putri', 'Masuk', 'open', '1 j 20 m'],
                        ] as [$id, $subject, $customer, $status, $tone, $remaining])
                            <div class="ticket-stub flex items-center justify-between gap-3 px-5 py-3.5">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-ink">{{ $subject }}</p>
                                    <p class="mt-0.5 text-xs text-ink-faint">
                                        <span class="font-mono">{{ $id }}</span> · {{ $customer }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <x-ui.stamp :tone="$tone">{{ $status }}</x-ui.stamp>
                                    <span class="hidden w-14 text-right font-mono text-xs sm:inline {{ $remaining === '—' ? 'text-ink-faint' : 'text-ink-soft' }}">{{ $remaining }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-3 divide-x divide-rule border-t border-rule bg-paper/60">
                        @foreach ([['24', 'Belum dijawab'], ['31', 'Dikerjakan'], ['73', 'Selesai hari ini']] as [$value, $label])
                            <div class="px-4 py-3 text-center sm:text-left">
                                <p class="font-display text-lg font-bold text-ink">{{ $value }}</p>
                                <p class="text-xs text-ink-faint">{{ $label }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between px-1 text-xs text-ink-faint">
                    <span>Simulasi antrean operasional real-time</span>
                    <a href="{{ url('/admin') }}" class="font-medium text-signal hover:underline">Buka konsol agent →</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="fitur" class="border-b border-rule bg-paper-raised">
        <div class="mx-auto max-w-[1280px] px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">
                        Enam bagian, satu meja kerja.
                    </h2>
                </div>
                <p class="max-w-md text-sm leading-6 text-ink-soft">
                    Tidak perlu membuka empat aplikasi untuk menjawab satu pertanyaan. Semua yang tim support butuhkan ada di sini.
                </p>
            </div>

            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['icon' => 'heroicon-o-ticket', 'title' => 'Tiket terpusat', 'body' => 'Buat, balas, assign, dan ubah status. Catatan internal dipisah dari balasan customer.'],
                    ['icon' => 'heroicon-o-clock', 'title' => 'SLA & eskalasi', 'body' => 'Target respons per prioritas. Scheduler menandai tiket yang mendekati batas waktu.'],
                    ['icon' => 'heroicon-o-book-open', 'title' => 'Knowledge base', 'body' => 'Artikel bantuan dengan kategori dan pencarian agar customer bisa menyelesaikan masalah mandiri.'],
                    ['icon' => 'heroicon-o-users', 'title' => 'Multi-tenant', 'body' => 'Satu atau banyak organisasi dengan isolasi data penuh dan pengaturan role yang jelas.'],
                    ['icon' => 'heroicon-o-chart-bar', 'title' => 'Laporan & metrik', 'body' => 'Tren tiket, beban agent, dan SLA compliance. Ekspor CSV untuk analisis lanjutan.'],
                    ['icon' => 'heroicon-o-bolt', 'title' => 'Otomasi & webhook', 'body' => 'Queue, notifikasi real-time, dan webhook bertanda tangan untuk integrasi ke sistem lain.'],
                ] as $feature)
                    <div class="rounded-xl border border-rule bg-paper p-6 transition-colors hover:border-rule-strong">
                        <div class="inline-flex size-10 items-center justify-center rounded-lg border border-rule bg-paper-raised text-signal">
                            <x-dynamic-component :component="$feature['icon']" class="size-5" />
                        </div>
                        <h3 class="mt-4 font-display text-lg font-semibold text-ink">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-ink-soft">{{ $feature['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Workflow --}}
    <section id="alur" class="border-b border-rule">
        <div class="mx-auto max-w-[1280px] px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
            <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:gap-16">
                <div>
                    <h2 class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">
                        Dari tiket ke pengetahuan.
                    </h2>
                    <p class="mt-3 max-w-md text-sm leading-6 text-ink-soft">
                        Setiap tiket punya jejak yang jelas: siapa menangani, apa yang terjadi, dan kapan selesai.
                    </p>

                    <ol class="mt-8 space-y-4">
                        @foreach ([
                            ['Customer membuat tiket', 'Lewat portal atau REST API, lengkap dengan lampiran berkas.'],
                            ['Agent mengambil dan membalas', 'Antrean terfilter, penugasan tim, dan catatan internal.'],
                            ['SLA dipantau otomatis', 'Scheduler menandai potensi pelanggaran batas waktu dan mengirim pengingat.'],
                            ['Solusi menjadi artikel', 'Jawaban yang sering berulang disimpan ke knowledge base publik.'],
                        ] as $index => $step)
                            <li class="flex gap-4 rounded-xl border border-rule bg-paper-raised p-4">
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-signal-tint font-mono text-xs font-semibold text-signal">
                                    {{ $index + 1 }}
                                </span>
                                <div>
                                    <h3 class="font-display text-base font-semibold text-ink">{{ $step[0] }}</h3>
                                    <p class="mt-0.5 text-sm text-ink-soft">{{ $step[1] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                {{-- Case file preview --}}
                <div class="sheet overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-rule px-5 py-4">
                        <div>
                            <p class="font-mono text-xs text-ink-faint">#SF-1042</p>
                            <p class="mt-0.5 font-display text-base font-semibold text-ink">Tidak bisa login setelah reset password</p>
                        </div>
                        <x-ui.stamp tone="resolved">Selesai</x-ui.stamp>
                    </div>

                    <div class="divide-y divide-rule p-5">
                        @foreach ([
                            ['Customer · Budi', '09:12', 'Saya sudah reset password, tapi tetap tidak bisa masuk. Muncul pesan “kredensial tidak valid”.', false],
                            ['Agent · Rina', '09:20', 'Halo, kemungkinan akun Anda terdaftar lewat Google. Coba masuk dengan tombol “Lanjutkan dengan Google”, ya.', true],
                            ['Customer · Budi', '09:24', 'Berhasil. Terima kasih, cepat sekali.', false],
                        ] as [$author, $time, $message, $fromAgent])
                            <div class="py-3.5 first:pt-0 last:pb-0">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-semibold {{ $fromAgent ? 'text-signal' : 'text-ink' }}">{{ $author }}</span>
                                    <span class="font-mono text-ink-faint">{{ $time }}</span>
                                </div>
                                <div class="mt-2 rounded-lg p-3 text-sm leading-6 {{ $fromAgent ? 'border border-signal/20 bg-signal-tint/50 text-ink' : 'bg-paper text-ink-soft' }}">
                                    {{ $message }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-rule bg-paper/60 px-5 py-3 text-xs">
                        <span class="text-ink-soft">Diselesaikan dalam 12 menit</span>
                        <span class="inline-flex items-center gap-1.5 font-medium text-pine">
                            <span class="size-2 rounded-full bg-pine" aria-hidden="true"></span>
                            SLA terpenuhi
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="harga" class="border-b border-rule bg-paper-raised">
        <div class="mx-auto max-w-[1280px] px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">Mulai kecil, tumbuh sesuai kebutuhan.</h2>
                </div>
                <p class="max-w-xs text-sm text-ink-soft">Tanpa kontrak tahunan. Mulai langsung dari tiket pertama Anda.</p>
            </div>

            <div class="mt-10 grid gap-6 lg:grid-cols-3">
                @foreach ([
                    [
                        'name' => 'Starter',
                        'price' => 'Gratis',
                        'note' => 'Untuk tim kecil yang baru mulai',
                        'highlight' => false,
                        'features' => ['Hingga 3 agent aktif', 'Tiket & lampiran terpusat', 'Knowledge base dasar', 'REST API standar'],
                    ],
                    [
                        'name' => 'Growth',
                        'price' => 'Rp299rb',
                        'note' => 'Per organisasi / bulan',
                        'highlight' => true,
                        'features' => ['Hingga 10 agent aktif', 'Target SLA & pengingat otomatis', 'Knowledge base penuh', 'Integrasi webhook keluar'],
                    ],
                    [
                        'name' => 'Scale',
                        'price' => 'Rp899rb',
                        'note' => 'Per organisasi / bulan',
                        'highlight' => false,
                        'features' => ['Agent tanpa batas', 'SLA kustom & multi-tim', 'Audit log operasional lengkap', 'Batas rate limit API tertinggi'],
                    ],
                ] as $plan)
                    <div class="flex flex-col justify-between rounded-xl border p-6 {{ $plan['highlight'] ? 'border-signal bg-paper shadow-sm ring-1 ring-signal/20' : 'border-rule bg-paper' }}">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-display text-lg font-semibold text-ink">{{ $plan['name'] }}</h3>
                                @if ($plan['highlight'])
                                    <x-ui.stamp tone="open">Pilihan tim</x-ui.stamp>
                                @endif
                            </div>
                            <p class="mt-4 font-display text-4xl font-bold tracking-tight text-ink">{{ $plan['price'] }}</p>
                            <p class="mt-1 text-xs text-ink-faint">{{ $plan['note'] }}</p>

                            <ul class="mt-6 space-y-2.5 border-t border-rule pt-5 text-sm text-ink-soft">
                                @foreach ($plan['features'] as $item)
                                    <li class="flex items-center gap-2.5">
                                        <x-heroicon-o-check-circle class="size-4 shrink-0 text-pine" />
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <a href="{{ url('/register') }}" class="btn mt-8 w-full {{ $plan['highlight'] ? 'btn-signal' : 'btn-outline' }}">
                            Mulai dengan {{ $plan['name'] }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="border-b border-rule">
        <div class="mx-auto grid max-w-[1280px] gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:gap-16 lg:px-8 lg:py-20">
            <div>
                <h2 class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">Pertanyaan yang sering muncul.</h2>
                <p class="mt-3 max-w-sm text-sm leading-6 text-ink-soft">
                    Belum menemukan jawaban? <a href="{{ url('/help') }}" class="font-semibold text-signal hover:underline">Buka pusat bantuan</a> atau buat tiket baru.
                </p>
            </div>

            <div class="divide-y divide-rule rounded-xl border border-rule bg-paper-raised px-5">
                @foreach ([
                    ['q' => 'Apakah bisa login dengan Google?', 'a' => 'Bisa. SupportFlow mendukung email/password dan Google OAuth. Akun dengan email yang sama akan ditautkan sesuai aturan keamanan.'],
                    ['q' => 'Apakah data antar organisasi terisolasi?', 'a' => 'Ya. Setiap query operasional dibatasi organization_id dan diuji dengan test isolasi tenant.'],
                    ['q' => 'Apakah tersedia API?', 'a' => 'Tersedia REST API /api/v1 dengan token Sanctum, pagination, filter, dan dokumentasi OpenAPI.'],
                    ['q' => 'Apakah bisa dipakai di mobile?', 'a' => 'Seluruh halaman dirancang responsif dari ponsel hingga layar lebar, ditambah REST API untuk aplikasi mobile.'],
                ] as $faq)
                    <details class="group py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                            <span class="text-sm font-semibold text-ink">{{ $faq['q'] }}</span>
                            <x-heroicon-o-chevron-down class="size-4 shrink-0 text-ink-faint transition-transform group-open:rotate-180" />
                        </summary>
                        <p class="mt-2.5 text-sm leading-6 text-ink-soft">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Closing CTA --}}
    <section class="bg-ink text-paper">
        <div class="mx-auto flex max-w-[1280px] flex-col justify-between gap-8 px-4 py-14 sm:px-6 lg:flex-row lg:items-center lg:px-8 lg:py-16">
            <div>
                <h2 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">
                    Rapikan meja support Anda hari ini.
                </h2>
                <p class="mt-2 max-w-lg text-sm leading-6 text-paper/70">
                    Gratis untuk tim kecil. Tanpa kartu kredit. Cukup beberapa menit untuk mulai mengelola tiket pertama.
                </p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ url('/register') }}" class="btn btn-signal px-5 py-2.5">Mulai gratis</a>
                <a href="{{ url('/login') }}" class="btn btn-outline-light px-5 py-2.5">Masuk ke akun</a>
            </div>
        </div>
    </section>
@endsection
