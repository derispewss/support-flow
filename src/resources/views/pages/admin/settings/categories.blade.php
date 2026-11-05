@extends('layouts.admin')

@section('title', 'Kategori — '.config('app.name'))

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Kategori</h1>
            <p class="mt-1 text-sm text-ink-soft">Kelompokkan tiket berdasarkan topik agar triase dan pelaporan lebih terstruktur.</p>
        </div>
        <div class="self-start sm:self-auto">
            <x-ui.stamp tone="ink">{{ $categories->count() }} kategori</x-ui.stamp>
        </div>
    </div>

    <div class="mt-6">
        <x-admin.settings-nav active="categories" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_1.6fr]">
        <section class="sheet overflow-hidden self-start">
            <div class="border-b border-rule px-5 py-4">
                <h2 class="font-display text-base font-semibold text-ink">Kategori baru</h2>
                <p class="mt-0.5 text-xs text-ink-faint">Kategori aktif dapat dipilih pelanggan saat membuat tiket.</p>
            </div>

            <form method="POST" action="{{ route('admin.settings.categories.store') }}" class="space-y-4 p-5">
                @csrf

                <div>
                    <label for="category-name" class="field-label">Nama kategori</label>
                    <input id="category-name" name="name" type="text" value="{{ old('name') }}" maxlength="80" required class="field mt-1.5" placeholder="Contoh: Billing & Pembayaran">
                    @error('name')
                        <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category-description" class="field-label">Deskripsi <span class="font-normal text-ink-faint">(opsional)</span></label>
                    <textarea id="category-description" name="description" rows="3" maxlength="500" class="field mt-1.5" placeholder="Jelaskan jenis kendala yang masuk ke kategori ini…">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="category-order" class="field-label">Urutan tampil</label>
                        <input id="category-order" name="sort_order" type="number" min="0" max="999" value="{{ old('sort_order', 0) }}" class="field mt-1.5">
                        @error('sort_order')
                            <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex cursor-pointer items-center gap-2.5 self-end rounded-lg border border-rule bg-paper px-3 py-2.5 text-sm font-medium text-ink">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="size-4 rounded border-rule-strong text-ink focus:ring-0">
                        <span>Status aktif</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-signal w-full">
                    <x-heroicon-o-plus class="size-4" />
                    Tambah kategori
                </button>
            </form>
        </section>

        <section class="space-y-3">
            @forelse ($categories as $category)
                <article class="sheet overflow-hidden" wire:key="category-{{ $category->getKey() }}">
                    <div class="p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-display text-base font-semibold text-ink">{{ $category->name }}</h2>
                                    <x-ui.stamp :tone="$category->is_active ? 'resolved' : 'faint'">
                                        {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </x-ui.stamp>
                                </div>
                                <p class="mt-1 text-xs text-ink-faint">
                                    <span class="font-mono">{{ $category->slug }}</span> · {{ $category->tickets_count }} tiket · urutan {{ $category->sort_order }}
                                </p>
                            </div>
                        </div>

                        @if ($category->description)
                            <p class="mt-3 text-sm leading-relaxed text-ink-soft">{{ $category->description }}</p>
                        @endif

                        <details class="group mt-4">
                            <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-rule bg-paper px-3 py-1.5 text-xs font-medium text-ink-soft transition-colors hover:border-rule-strong hover:text-ink">
                                <x-heroicon-o-pencil-square class="size-3.5 text-ink-faint" />
                                <span>Edit kategori</span>
                                <x-heroicon-o-chevron-down class="size-3 text-ink-faint transition-transform group-open:rotate-180" />
                            </summary>

                            <div class="mt-3 space-y-4 rounded-xl border border-rule bg-paper p-4">
                                <form method="POST" action="{{ route('admin.settings.categories.update', $category) }}" class="space-y-3">
                                    @csrf
                                    @method('PATCH')

                                    <div class="grid gap-3 sm:grid-cols-[1fr_7rem]">
                                        <div>
                                            <label for="category-name-{{ $category->getKey() }}" class="field-label">Nama</label>
                                            <input id="category-name-{{ $category->getKey() }}" name="name" type="text" value="{{ $category->name }}" maxlength="80" required class="field mt-1">
                                        </div>
                                        <div>
                                            <label for="category-order-{{ $category->getKey() }}" class="field-label">Urutan</label>
                                            <input id="category-order-{{ $category->getKey() }}" name="sort_order" type="number" min="0" max="999" value="{{ $category->sort_order }}" class="field mt-1">
                                        </div>
                                    </div>

                                    <div>
                                        <label for="category-description-{{ $category->getKey() }}" class="field-label">Deskripsi</label>
                                        <textarea id="category-description-{{ $category->getKey() }}" name="description" rows="2" maxlength="500" class="field mt-1">{{ $category->description }}</textarea>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                                        <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-soft">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" @checked($category->is_active) class="size-4 rounded border-rule-strong text-ink focus:ring-0">
                                            <span>Kategori aktif</span>
                                        </label>

                                        <button type="submit" class="btn btn-ink">Simpan perubahan</button>
                                    </div>
                                </form>

                                <div class="flex items-center justify-between border-t border-rule pt-3">
                                    <span class="text-xs text-ink-faint">Hapus kategori ini secara permanen</span>
                                    <form method="POST" action="{{ route('admin.settings.categories.destroy', $category) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-signal hover:underline">Hapus kategori</button>
                                    </form>
                                </div>
                            </div>
                        </details>
                    </div>
                </article>
            @empty
                <div class="sheet px-5 py-12 text-center">
                    <x-heroicon-o-tag class="mx-auto size-9 text-ink-faint" />
                    <p class="mt-3 font-display text-base font-semibold text-ink">Belum ada kategori.</p>
                    <p class="mt-1 text-sm text-ink-soft">Tambahkan kategori pertama lewat formulir di samping.</p>
                </div>
            @endforelse
        </section>
    </div>
@endsection
