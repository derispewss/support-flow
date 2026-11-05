@extends('layouts.auth')

@section('title', 'Onboarding Owner — '.config('app.name'))
@section('auth_body_class', 'items-start sm:items-center')
@section('auth_container_class', 'max-w-5xl')
@section('auth_sheet_class', 'overflow-hidden p-0')
@section('auth_footer_class', 'mx-auto max-w-5xl')

@section('content')
<div class="grid md:grid-cols-[minmax(0,1.15fr)_minmax(18rem,0.85fr)]">
    <div class="p-6 sm:p-8 lg:p-10">
        @if ($selectedRole !== 'owner')
            <div class="mb-8">
                <span class="label-mono">Mulai di sini</span>
                <h2 class="mt-3 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Anda bekerja sebagai apa?</h2>
                <p class="mt-2 max-w-lg text-sm leading-6 text-ink-soft">
                    Pilih peran Anda supaya kami menyiapkan langkah berikutnya dengan tepat.
                </p>
            </div>

            <form method="POST" action="{{ route('onboarding.role') }}" class="space-y-3">
                @csrf
                @foreach ([
                    ['owner', 'Owner', 'Buat meja kerja dan kelola workspace support Anda. Saran nama: '.($suggestedName ?? 'Ruang Kerja').'.', 'Buat workspace'],
                    ['customer', 'Customer', 'Gabung ke organisasi untuk mengirim dan mengikuti tiket.', 'Masuk sebagai customer'],
                    ['agent', 'Agent', 'Gabung ke tim support melalui undangan organisasi.', 'Masuk sebagai agent'],
                ] as [$value, $label, $description, $action])
                    <button type="submit" name="role" value="{{ $value }}"
                        class="group flex w-full items-center justify-between gap-4 rounded-xl border border-rule bg-paper-raised p-4 text-left transition hover:border-signal hover:bg-signal-soft focus-visible:border-signal">
                        <span class="flex items-start gap-3">
                            <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg bg-paper text-signal group-hover:bg-white">
                                @if ($value === 'owner')
                                    <x-heroicon-o-building-office-2 class="size-4" />
                                @elseif ($value === 'agent')
                                    <x-heroicon-o-user-group class="size-4" />
                                @else
                                    <x-heroicon-o-user class="size-4" />
                                @endif
                            </span>
                            <span>
                                <span class="block text-sm font-semibold text-ink">{{ $label }}</span>
                                <span class="mt-1 block text-xs leading-5 text-ink-faint">{{ $description }}</span>
                            </span>
                        </span>
                        <span class="shrink-0 text-xs font-semibold text-signal">{{ $action }}</span>
                    </button>
                @endforeach
            </form>
        @else
        <div class="mb-8">
            <div class="flex items-center justify-between gap-4">
                <span class="label-mono">Workspace baru</span>
                <span class="text-xs font-medium text-ink-faint">Langkah 2 dari 2</span>
            </div>
            <div class="mt-3 h-1 overflow-hidden rounded-full bg-rule" aria-hidden="true">
                <div class="h-full w-full rounded-full bg-signal"></div>
            </div>
            <h2 class="mt-7 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Buat meja kerja</h2>
            <p class="mt-2 max-w-lg text-sm leading-6 text-ink-soft">
                Siapkan ruang kerja support Anda. Detail ini bisa diubah kapan saja dari pengaturan organisasi.
            </p>
        </div>

        <x-ui.toast />

        @unless (auth()->user()?->hasVerifiedEmail())
            <div class="mb-6 rounded-lg border border-ochre/25 bg-ochre-soft p-3.5 text-xs leading-5 text-ochre">
                <p class="font-medium">Email Anda belum terverifikasi. Verifikasi email untuk menjaga akses tetap aktif.</p>
            </div>
        @endunless

        <form method="POST" action="{{ route('onboarding.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="name" class="field-label">Nama organisasi</label>
                <input id="name" name="name" type="text" value="{{ old('name', $suggestedName ?? 'Ruang Kerja') }}"
                    placeholder="Contoh: Acme Support" class="field mt-1.5" required autofocus />
                @error('name')
                    <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="agents" class="field-label">Perkiraan agent support <span class="font-normal text-ink-faint">(opsional)</span></label>
                <input id="agents" name="agents" type="number" min="0" max="50" value="{{ old('agents', 2) }}"
                    placeholder="Contoh: 3" class="field mt-1.5" />
                <p class="mt-1.5 text-xs text-ink-faint">Jumlah orang yang akan menangani tiket pelanggan.</p>
                @error('agents')
                    <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                @enderror
            </div>

            <fieldset>
                <legend class="field-label">Pilih paket layanan</legend>
                <div class="mt-2 grid gap-3 sm:grid-cols-3" x-data="{ selectedPlan: '{{ old('plan', 'free') }}' }">
                    <label class="relative flex cursor-pointer flex-col justify-between rounded-lg border p-3.5 text-left transition-all"
                        :class="selectedPlan === 'free' ? 'border-signal bg-signal-soft ring-2 ring-signal/20' : 'border-rule hover:border-rule-strong'">
                        <input type="radio" name="plan" value="free" class="sr-only" x-model="selectedPlan" />
                        <div>
                            <span class="block text-sm font-semibold text-ink">Free</span>
                            <span class="mt-0.5 block text-xs text-ink-faint">Mulai tanpa biaya</span>
                        </div>
                        <div class="mt-3">
                            <span class="text-lg font-bold text-ink">Rp 0</span>
                            <span class="block text-[10px] text-ink-faint">Maks. 30 tiket/bln</span>
                        </div>
                    </label>

                    <label class="relative flex cursor-pointer flex-col justify-between rounded-lg border p-3.5 text-left transition-all"
                        :class="selectedPlan === 'pro' ? 'border-signal bg-signal-soft ring-2 ring-signal/20' : 'border-rule hover:border-rule-strong'">
                        <input type="radio" name="plan" value="pro" class="sr-only" x-model="selectedPlan" />
                        <div>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="block text-sm font-semibold text-ink">Pro</span>
                                <span class="rounded bg-signal px-1.5 py-0.5 text-[9px] font-semibold text-white">Populer</span>
                            </span>
                            <span class="mt-0.5 block text-xs text-ink-faint">SLA & multi-team</span>
                        </div>
                        <div class="mt-3">
                            <span class="text-lg font-bold text-ink">Rp 150rb</span>
                            <span class="block text-[10px] text-ink-faint">/ bulan (Tripay)</span>
                        </div>
                    </label>

                    <label class="relative flex cursor-pointer flex-col justify-between rounded-lg border p-3.5 text-left transition-all"
                        :class="selectedPlan === 'enterprise' ? 'border-signal bg-signal-soft ring-2 ring-signal/20' : 'border-rule hover:border-rule-strong'">
                        <input type="radio" name="plan" value="enterprise" class="sr-only" x-model="selectedPlan" />
                        <div>
                            <span class="block text-sm font-semibold text-ink">Enterprise</span>
                            <span class="mt-0.5 block text-xs text-ink-faint">Dedicated support</span>
                        </div>
                        <div class="mt-3">
                            <span class="text-lg font-bold text-ink">Rp 500rb</span>
                            <span class="block text-[10px] text-ink-faint">/ bulan (Tripay)</span>
                        </div>
                    </label>
                </div>
                @error('plan')
                    <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                @enderror
            </fieldset>

            <button type="submit" class="btn btn-signal w-full sm:w-auto sm:min-w-52">
                Buat workspace
                <x-heroicon-o-arrow-right class="size-4" />
            </button>
        </form>
        @endif
    </div>

    <aside class="hidden flex-col justify-between bg-ink p-8 text-paper-raised md:flex lg:p-10">
        <div>
            <x-ui.logo variant="light" compact />
            <h3 class="mt-16 max-w-xs font-display text-2xl font-bold leading-tight">
                Semua percakapan pelanggan, satu tempat.
            </h3>
            <p class="mt-4 max-w-xs text-sm leading-6 text-paper/65">
                Mulai dari workspace yang rapi, lalu bangun pengalaman support yang konsisten untuk setiap pelanggan.
            </p>

            <div class="mt-10 space-y-4">
                @foreach (['Antrian tiket untuk seluruh agent', 'Target SLA dan eskalasi otomatis', 'Knowledge base yang mudah ditemukan'] as $feature)
                    <div class="flex items-start gap-3 text-sm text-paper/80">
                        <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-pine text-xs text-white">✓</span>
                        <span>{{ $feature }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="border-t border-paper/15 pt-5 text-xs leading-5 text-paper/45">
            SupportFlow &copy; {{ date('Y') }}<br>
            Workspace terisolasi untuk tim Anda.
        </p>
    </aside>
</div>
@endsection
