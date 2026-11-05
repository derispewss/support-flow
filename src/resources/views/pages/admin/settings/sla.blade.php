@extends('layouts.admin')

@section('title', 'Kebijakan SLA — '.config('app.name'))

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Kebijakan SLA</h1>
            <p class="mt-1 text-sm text-ink-soft">Target waktu respons pertama dan resolusi yang diterapkan pada tiket masuk.</p>
        </div>
        <div class="self-start sm:self-auto">
            <x-ui.stamp tone="ink">{{ $policies->count() }} kebijakan</x-ui.stamp>
        </div>
    </div>

    <div class="mt-6">
        <x-admin.settings-nav active="sla" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_1.6fr]">
        <section class="sheet overflow-hidden self-start">
            <div class="border-b border-rule px-5 py-4">
                <h2 class="font-display text-base font-semibold text-ink">Kebijakan baru</h2>
                <p class="mt-0.5 text-xs text-ink-faint">Tentukan target waktu dalam satuan menit.</p>
            </div>

            <form method="POST" action="{{ route('admin.settings.sla.store') }}" class="space-y-4 p-5">
                @csrf

                <div>
                    <label for="sla-name" class="field-label">Nama kebijakan</label>
                    <input id="sla-name" name="name" type="text" value="{{ old('name') }}" maxlength="120" required class="field mt-1.5" placeholder="Contoh: Standar / Prioritas Tinggi">
                    @error('name')
                        <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="sla-first-response" class="field-label">Respons (menit)</label>
                        <input id="sla-first-response" name="first_response_minutes" type="number" min="1" max="10080" value="{{ old('first_response_minutes', 60) }}" required class="field mt-1.5 font-mono">
                        @error('first_response_minutes')
                            <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="sla-resolution" class="field-label">Resolusi (menit)</label>
                        <input id="sla-resolution" name="resolution_minutes" type="number" min="1" max="525600" value="{{ old('resolution_minutes', 1440) }}" required class="field mt-1.5 font-mono">
                        @error('resolution_minutes')
                            <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-rule bg-paper px-3.5 py-2.5 text-sm font-medium text-ink">
                    <input type="hidden" name="is_default" value="0">
                    <input type="checkbox" name="is_default" value="1" @checked(old('is_default')) class="size-4 rounded border-rule-strong text-ink focus:ring-0">
                    <span>Jadikan kebijakan default organisasi</span>
                </label>

                <button type="submit" class="btn btn-signal w-full">
                    <x-heroicon-o-plus class="size-4" />
                    Tambah kebijakan
                </button>
            </form>
        </section>

        <section class="space-y-3">
            @forelse ($policies as $policy)
                <article class="sheet overflow-hidden" wire:key="sla-{{ $policy->getKey() }}">
                    <div class="p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-display text-base font-semibold text-ink">{{ $policy->name }}</h2>
                                    @if ($policy->is_default)
                                        <x-ui.stamp tone="solid">Default</x-ui.stamp>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-ink-faint">Digunakan pada {{ $policy->tickets_count }} tiket</p>
                            </div>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-rule bg-paper px-4 py-3">
                                <dt class="text-xs text-ink-soft">Target respons pertama</dt>
                                <dd class="mt-1 font-mono text-lg font-semibold text-ink">{{ $policy->first_response_minutes }} mnt</dd>
                            </div>
                            <div class="rounded-xl border border-rule bg-paper px-4 py-3">
                                <dt class="text-xs text-ink-soft">Target resolusi</dt>
                                <dd class="mt-1 font-mono text-lg font-semibold text-ink">{{ $policy->resolution_minutes }} mnt</dd>
                            </div>
                        </dl>

                        <details class="group mt-4">
                            <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-rule bg-paper px-3 py-1.5 text-xs font-medium text-ink-soft transition-colors hover:border-rule-strong hover:text-ink">
                                <x-heroicon-o-pencil-square class="size-3.5 text-ink-faint" />
                                <span>Edit kebijakan</span>
                                <x-heroicon-o-chevron-down class="size-3 text-ink-faint transition-transform group-open:rotate-180" />
                            </summary>

                            <div class="mt-3 space-y-4 rounded-xl border border-rule bg-paper p-4">
                                <form method="POST" action="{{ route('admin.settings.sla.update', $policy) }}" class="space-y-3">
                                    @csrf
                                    @method('PATCH')

                                    <div>
                                        <label for="sla-name-{{ $policy->getKey() }}" class="field-label">Nama kebijakan</label>
                                        <input id="sla-name-{{ $policy->getKey() }}" name="name" type="text" value="{{ $policy->name }}" maxlength="120" required class="field mt-1">
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label for="sla-first-{{ $policy->getKey() }}" class="field-label">Respons (menit)</label>
                                            <input id="sla-first-{{ $policy->getKey() }}" name="first_response_minutes" type="number" min="1" max="10080" value="{{ $policy->first_response_minutes }}" required class="field mt-1 font-mono">
                                        </div>
                                        <div>
                                            <label for="sla-resolution-{{ $policy->getKey() }}" class="field-label">Resolusi (menit)</label>
                                            <input id="sla-resolution-{{ $policy->getKey() }}" name="resolution_minutes" type="number" min="1" max="525600" value="{{ $policy->resolution_minutes }}" required class="field mt-1 font-mono">
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                                        <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-soft">
                                            <input type="hidden" name="is_default" value="0">
                                            <input type="checkbox" name="is_default" value="1" @checked($policy->is_default) class="size-4 rounded border-rule-strong text-ink focus:ring-0">
                                            <span>Kebijakan default</span>
                                        </label>

                                        <button type="submit" class="btn btn-ink">Simpan perubahan</button>
                                    </div>
                                </form>

                                <div class="flex items-center justify-between border-t border-rule pt-3">
                                    <span class="text-xs text-ink-faint">Hapus kebijakan SLA ini</span>
                                    <form method="POST" action="{{ route('admin.settings.sla.destroy', $policy) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-signal hover:underline">Hapus kebijakan</button>
                                    </form>
                                </div>
                            </div>
                        </details>
                    </div>
                </article>
            @empty
                <div class="sheet px-5 py-12 text-center">
                    <x-heroicon-o-clock class="mx-auto size-9 text-ink-faint" />
                    <p class="mt-3 font-display text-base font-semibold text-ink">Belum ada kebijakan SLA.</p>
                    <p class="mt-1 text-sm text-ink-soft">Tanpa kebijakan default, tiket baru tidak mendapat batas waktu otomatis.</p>
                </div>
            @endforelse
        </section>
    </div>
@endsection
