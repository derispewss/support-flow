@extends('layouts.admin')

@section('title', 'Knowledge Base — '.config('app.name'))

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Artikel bantuan</h1>
            <p class="mt-1 text-sm text-ink-soft">Tulis dan kelola dokumentasi mandiri untuk pelanggan di pusat bantuan publik.</p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <a href="{{ route('help.organization', $organization) }}" target="_blank" rel="noopener" class="btn btn-outline">
                Lihat halaman publik
                <x-heroicon-o-arrow-top-right-on-square class="size-3.5" />
            </a>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.05fr_1.45fr]">
        <section class="sheet overflow-hidden self-start">
            <div class="border-b border-rule px-5 py-4">
                <h2 class="font-display text-base font-semibold text-ink">Tulis artikel baru</h2>
                <p class="mt-0.5 text-xs text-ink-faint">Artikel dengan status Published langsung tampil di Knowledge Base.</p>
            </div>

            <form method="POST" action="{{ route('admin.knowledge-base.articles.store') }}" class="space-y-4 p-5">
                @csrf

                <div>
                    <label for="kb-title" class="field-label">Judul artikel</label>
                    <input id="kb-title" name="title" value="{{ old('title') }}" required placeholder="Contoh: Cara mengatur ulang password akun" class="field mt-1.5">
                    @error('title')
                        <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="kb-category" class="field-label">Kategori</label>
                        <select id="kb-category" name="kb_category_id" class="field mt-1.5">
                            <option value="">Tanpa kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('kb_category_id') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="kb-status" class="field-label">Status publikasi</label>
                        <select id="kb-status" name="status" class="field mt-1.5">
                            <option value="draft" @selected(old('status') === 'draft')>Draft</option>
                            <option value="published" @selected(old('status') === 'published')>Published</option>
                            <option value="archived" @selected(old('status') === 'archived')>Archived</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="kb-body" class="field-label">Isi artikel</label>
                    <textarea id="kb-body" name="body" required rows="7" placeholder="Tuliskan langkah-langkah panduan secara jelas…" class="field mt-1.5">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-signal w-full">
                    <x-heroicon-o-plus class="size-4" />
                    Tambah artikel
                </button>
            </form>
        </section>

        <section class="sheet overflow-hidden self-start">
            <div class="flex items-center justify-between gap-2 border-b border-rule px-5 py-4">
                <h2 class="font-display text-base font-semibold text-ink">Daftar artikel</h2>
                <span class="font-mono text-xs text-ink-faint">{{ $articles->total() }} artikel</span>
            </div>

            <div class="divide-y divide-rule">
                @forelse ($articles as $article)
                    <div class="p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-display text-base font-semibold text-ink">{{ $article->title }}</p>
                                    <x-ui.stamp :tone="$article->status === 'published' ? 'resolved' : ($article->status === 'draft' ? 'pending' : 'faint')">
                                        {{ ucfirst($article->status) }}
                                    </x-ui.stamp>
                                </div>
                                <p class="mt-1 text-xs text-ink-soft">
                                    {{ $article->category?->name ?? 'Tanpa kategori' }}
                                    @if ($article->updated_at)
                                        · diperbarui {{ $article->updated_at->diffForHumans() }}
                                    @endif
                                    · {{ number_format($article->views ?? 0) }} dibaca
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 self-start">
                                @if ($article->status === 'published')
                                    <a href="{{ route('help.article', [$organization, $article]) }}" target="_blank" rel="noopener" class="btn btn-outline py-1.5 text-xs">
                                        Baca
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('admin.knowledge-base.articles.destroy', $article) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn border-signal/30 bg-signal-tint py-1.5 text-xs text-signal hover:border-signal">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </div>

                        <details class="group mt-3">
                            <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 text-xs font-medium text-ink-soft hover:text-ink">
                                <x-heroicon-o-pencil-square class="size-3.5" />
                                <span>Sunting isi & status</span>
                            </summary>

                            <form method="POST" action="{{ route('admin.knowledge-base.articles.update', $article) }}" class="mt-3 space-y-3 rounded-lg border border-rule bg-paper p-4">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label class="field-label">Judul</label>
                                    <input name="title" value="{{ $article->title }}" required class="field mt-1">
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="field-label">Kategori</label>
                                        <select name="kb_category_id" class="field mt-1">
                                            <option value="">Tanpa kategori</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" @selected($article->kb_category_id === $category->id)>{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="field-label">Status</label>
                                        <select name="status" class="field mt-1">
                                            <option value="draft" @selected($article->status === 'draft')>Draft</option>
                                            <option value="published" @selected($article->status === 'published')>Published</option>
                                            <option value="archived" @selected($article->status === 'archived')>Archived</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label">Isi artikel</label>
                                    <textarea name="body" rows="5" required class="field mt-1">{{ $article->body }}</textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="btn btn-ink">Simpan perubahan</button>
                                </div>
                            </form>
                        </details>
                    </div>
                @empty
                    <div class="px-5 py-12 text-center">
                        <x-heroicon-o-book-open class="mx-auto size-9 text-ink-faint" />
                        <p class="mt-3 font-display text-base font-semibold text-ink">Belum ada artikel bantuan.</p>
                        <p class="mt-1 text-sm text-ink-soft">Tulis artikel pertama melalui formulir di samping.</p>
                    </div>
                @endforelse
            </div>

            @if ($articles->hasPages())
                <div class="border-t border-rule bg-paper/50 px-5 py-3.5">
                    {{ $articles->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
