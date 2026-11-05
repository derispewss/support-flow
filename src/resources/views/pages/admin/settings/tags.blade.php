@extends('layouts.admin')

@section('title', 'Tag — '.config('app.name'))

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Tag</h1>
            <p class="mt-1 text-sm text-ink-soft">Label fleksibel lintas kategori untuk pengelompokan isu dan pencarian cepat.</p>
        </div>
        <div class="self-start sm:self-auto">
            <x-ui.stamp tone="ink">{{ $tags->count() }} tag</x-ui.stamp>
        </div>
    </div>

    <div class="mt-6">
        <x-admin.settings-nav active="tags" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_1.6fr]">
        <section class="sheet overflow-hidden self-start">
            <div class="border-b border-rule px-5 py-4">
                <h2 class="font-display text-base font-semibold text-ink">Tag baru</h2>
                <p class="mt-0.5 text-xs text-ink-faint">Gunakan nama singkat dan warna penanda yang mudah dikenali.</p>
            </div>

            <form method="POST" action="{{ route('admin.settings.tags.store') }}" class="space-y-4 p-5">
                @csrf

                <div>
                    <label for="tag-name" class="field-label">Nama tag</label>
                    <input id="tag-name" name="name" type="text" value="{{ old('name') }}" maxlength="60" required class="field mt-1.5" placeholder="Contoh: refund">
                    @error('name')
                        <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tag-color" class="field-label">Kode warna HEX <span class="font-normal text-ink-faint">(opsional)</span></label>
                    <input id="tag-color" name="color" type="text" value="{{ old('color', '#cf4a1b') }}" maxlength="7" pattern="^#[0-9a-fA-F]{6}$" class="field mt-1.5 font-mono" placeholder="#cf4a1b">
                    @error('color')
                        <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-signal w-full">
                    <x-heroicon-o-plus class="size-4" />
                    Tambah tag
                </button>
            </form>
        </section>

        <section class="sheet overflow-hidden self-start">
            <div class="border-b border-rule px-5 py-4">
                <h2 class="font-display text-base font-semibold text-ink">Daftar tag</h2>
            </div>

            <ul class="divide-y divide-rule">
                @forelse ($tags as $tag)
                    <li class="p-4 sm:px-5" wire:key="tag-{{ $tag->getKey() }}">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="size-4 shrink-0 rounded-full border border-rule-strong" style="background-color: {{ $tag->color ?? '#e2e8f0' }}" aria-hidden="true"></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-ink">{{ $tag->name }}</p>
                                    <p class="mt-0.5 text-xs text-ink-faint">
                                        <span class="font-mono">{{ $tag->color ?? 'tanpa warna' }}</span> · {{ $tag->tickets_count }} tiket
                                    </p>
                                </div>
                            </div>
                        </div>

                        <details class="group mt-3">
                            <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-rule bg-paper px-3 py-1.5 text-xs font-medium text-ink-soft transition-colors hover:border-rule-strong hover:text-ink">
                                <x-heroicon-o-pencil-square class="size-3.5 text-ink-faint" />
                                <span>Edit tag</span>
                                <x-heroicon-o-chevron-down class="size-3 text-ink-faint transition-transform group-open:rotate-180" />
                            </summary>

                            <div class="mt-3 space-y-3 rounded-xl border border-rule bg-paper p-4">
                                <form method="POST" action="{{ route('admin.settings.tags.update', $tag) }}" class="space-y-3">
                                    @csrf
                                    @method('PATCH')

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label for="tag-name-{{ $tag->getKey() }}" class="field-label">Nama</label>
                                            <input id="tag-name-{{ $tag->getKey() }}" name="name" type="text" value="{{ $tag->name }}" maxlength="60" required class="field mt-1">
                                        </div>

                                        <div>
                                            <label for="tag-color-{{ $tag->getKey() }}" class="field-label">Warna HEX</label>
                                            <input id="tag-color-{{ $tag->getKey() }}" name="color" type="text" value="{{ $tag->color }}" maxlength="7" pattern="^#[0-9a-fA-F]{6}$" class="field mt-1 font-mono">
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit" class="btn btn-ink">Simpan perubahan</button>
                                    </div>
                                </form>

                                <div class="flex items-center justify-between border-t border-rule pt-3">
                                    <span class="text-xs text-ink-faint">Hapus tag dari daftar organisasi</span>
                                    <form method="POST" action="{{ route('admin.settings.tags.destroy', $tag) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-signal hover:underline">Hapus tag</button>
                                    </form>
                                </div>
                            </div>
                        </details>
                    </li>
                @empty
                    <li class="px-5 py-12 text-center">
                        <x-heroicon-o-tag class="mx-auto size-9 text-ink-faint" />
                        <p class="mt-3 font-display text-base font-semibold text-ink">Belum ada tag.</p>
                        <p class="mt-1 text-sm text-ink-soft">Tambahkan tag pertama lewat formulir di samping.</p>
                    </li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
