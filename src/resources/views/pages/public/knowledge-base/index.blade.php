@extends('layouts.public')

@section('title', 'Pusat Bantuan '.$organization->name)
@section('description', 'Artikel bantuan '.$organization->name)

@section('content')
    <section class="border-b border-rule bg-paper-raised">
        <div class="mx-auto max-w-[1280px] px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <span class="inline-flex items-center gap-2 rounded-full border border-rule bg-paper px-3 py-1 text-xs font-medium text-ink-soft">
                <x-heroicon-o-book-open class="size-3.5 text-signal" />
                {{ $organization->name }} · Pusat Bantuan
            </span>
            <h1 class="mt-3 font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">Bagaimana kami bisa membantu?</h1>
            <p class="mt-2 max-w-xl text-sm text-ink-soft">Cari solusi dan panduan penggunaan resmi dari tim {{ $organization->name }}.</p>

            <form method="GET" action="{{ route('help.organization', $organization) }}" class="mt-6 flex flex-col gap-2 sm:max-w-xl sm:flex-row">
                @if (request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <div class="relative flex-1">
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-ink-faint" />
                    <input name="q" value="{{ request('q') }}" placeholder="Cari judul atau kata kunci artikel…" class="field pl-10">
                </div>
                <button type="submit" class="btn btn-signal">Cari artikel</button>
            </form>
        </div>
    </section>

    <div class="mx-auto max-w-[1280px] px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[240px_1fr]">
            <aside>
                <p class="px-3 text-xs font-semibold text-ink-faint">Kategori</p>
                <nav class="mt-2 flex gap-1.5 overflow-x-auto pb-2 lg:flex-col lg:overflow-visible lg:pb-0">
                    <a
                        href="{{ route('help.organization', $organization) }}"
                        class="flex shrink-0 items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ ! request('category') ? 'bg-ink text-paper' : 'text-ink-soft hover:bg-paper-raised hover:text-ink' }}"
                    >
                        <span>Semua artikel</span>
                    </a>
                    @foreach ($categories as $category)
                        <a
                            href="{{ route('help.organization', $organization).'?category='.$category->slug }}"
                            class="flex shrink-0 items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request('category') === $category->slug ? 'bg-ink text-paper' : 'text-ink-soft hover:bg-paper-raised hover:text-ink' }}"
                        >
                            <span class="truncate">{{ $category->name }}</span>
                            <span class="font-mono text-xs {{ request('category') === $category->slug ? 'text-paper/70' : 'text-ink-faint' }}">{{ $category->articles_count }}</span>
                        </a>
                    @endforeach
                </nav>
            </aside>

            <section>
                <div class="grid gap-4">
                    @forelse ($articles as $article)
                        <a href="{{ route('help.article', [$organization, $article]) }}" class="sheet group block p-5 transition-colors hover:border-rule-strong">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-xs font-medium text-signal">{{ $article->category?->name ?? 'Panduan Umum' }}</span>
                                @if ($article->updated_at)
                                    <span class="font-mono text-xs text-ink-faint">{{ $article->updated_at->format('d M Y') }}</span>
                                @endif
                            </div>
                            <h2 class="mt-1.5 font-display text-lg font-semibold text-ink group-hover:text-signal">{{ $article->title }}</h2>
                            <p class="mt-1.5 line-clamp-2 text-sm leading-6 text-ink-soft">{{ Str::limit(strip_tags($article->body), 180) }}</p>
                        </a>
                    @empty
                        <div class="sheet px-6 py-12 text-center">
                            <x-heroicon-o-document-magnifying-glass class="mx-auto size-9 text-ink-faint" />
                            <p class="mt-3 font-display text-base font-semibold text-ink">Belum ada artikel yang cocok.</p>
                            <p class="mt-1 text-sm text-ink-soft">Coba kata kunci lain atau lihat semua kategori yang tersedia.</p>
                        </div>
                    @endforelse
                </div>

                @if ($articles->hasPages())
                    <div class="mt-6">
                        {{ $articles->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
