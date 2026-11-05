@extends('layouts.public')

@section('title', $article->title.' — '.$organization->name)
@section('description', Str::limit(strip_tags($article->body), 160))

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:py-14">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <a href="{{ route('help.organization', $organization) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-soft hover:text-ink">
                <x-heroicon-o-arrow-left class="size-4" />
                Kembali ke pusat bantuan {{ $organization->name }}
            </a>
            <x-ui.stamp tone="faint">{{ $article->category?->name ?? 'Panduan' }}</x-ui.stamp>
        </div>

        <div class="sheet mt-5 overflow-hidden">
            <header class="border-b border-rule px-6 py-6 sm:px-8">
                <h1 class="font-display text-2xl font-bold leading-tight text-ink sm:text-3xl">{{ $article->title }}</h1>
                <p class="mt-2 text-xs text-ink-faint">
                    Diperbarui {{ $article->updated_at->format('d M Y') }} · {{ number_format($article->views) }} kali dibaca
                </p>
            </header>

            <div class="prose prose-stone max-w-none px-6 py-6 text-sm leading-7 text-ink sm:px-8 sm:text-base">
                {!! nl2br(e($article->body)) !!}
            </div>

            <footer class="flex flex-col gap-3 border-t border-rule bg-paper/50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <p class="text-sm font-medium text-ink">Apakah artikel ini membantu?</p>
                <div class="flex items-center gap-2">
                    @foreach ([1 => 'Ya, membantu', 0 => 'Belum'] as $value => $label)
                        <form method="POST" action="{{ route('help.article.vote', [$organization, $article]) }}">
                            @csrf
                            <input type="hidden" name="helpful" value="{{ $value }}">
                            <button type="submit" class="btn btn-outline py-1.5 text-xs">
                                {{ $label }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </footer>
        </div>
    </article>
@endsection
