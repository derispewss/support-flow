@extends('layouts.admin')

@section('title', 'Webhooks — '.config('app.name'))

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Webhooks</h1>
            <p class="mt-1 text-sm text-ink-soft">Kirim notifikasi HTTP otomatis ke layanan eksternal saat peristiwa tiket terjadi.</p>
        </div>
        <div class="self-start sm:self-auto">
            <x-ui.stamp tone="ink">{{ $webhooks->count() }} endpoint</x-ui.stamp>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_1.5fr]">
        <section class="sheet overflow-hidden self-start">
            <div class="border-b border-rule px-5 py-4">
                <h2 class="font-display text-base font-semibold text-ink">Tambah endpoint webhook</h2>
                <p class="mt-0.5 text-xs text-ink-faint">Payload dikirim via HTTP POST dalam format JSON.</p>
            </div>

            <form method="POST" action="{{ route('admin.webhooks.store') }}" class="space-y-4 p-5">
                @csrf

                <div>
                    <label for="webhook-name" class="field-label">Nama integrasi</label>
                    <input id="webhook-name" name="name" value="{{ old('name') }}" required placeholder="Contoh: Slack Notifikasi" class="field mt-1.5">
                    @error('name')
                        <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="webhook-url" class="field-label">URL tujuan</label>
                    <input id="webhook-url" name="url" type="url" value="{{ old('url') }}" required placeholder="https://example.com/webhook" class="field mt-1.5 font-mono text-xs">
                    @error('url')
                        <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="webhook-event" class="field-label">Peristiwa pemicu</label>
                    <input id="webhook-event" name="events[]" value="ticket.created" required placeholder="Contoh: ticket.created" class="field mt-1.5 font-mono text-xs">
                    <p class="mt-1 text-xs text-ink-faint">Mendukung <code class="font-mono">ticket.created</code>, <code class="font-mono">ticket.replied</code>, <code class="font-mono">ticket.resolved</code>, atau <code class="font-mono">*</code>.</p>
                    @error('events')
                        <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-signal w-full">
                    <x-heroicon-o-plus class="size-4" />
                    Tambah webhook
                </button>
            </form>
        </section>

        <section class="sheet overflow-hidden self-start">
            <div class="border-b border-rule px-5 py-4">
                <h2 class="font-display text-base font-semibold text-ink">Endpoint terdaftar</h2>
                <p class="mt-0.5 text-xs text-ink-faint">Daftar endpoint aktif beserta total pengiriman keluar.</p>
            </div>

            <div class="divide-y divide-rule">
                @forelse ($webhooks as $webhook)
                    <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-display text-base font-semibold text-ink">{{ $webhook->name }}</p>
                                <x-ui.stamp :tone="$webhook->is_active ? 'resolved' : 'faint'">
                                    {{ $webhook->is_active ? 'Aktif' : 'Nonaktif' }}
                                </x-ui.stamp>
                                <span class="rounded-md bg-paper px-2 py-0.5 font-mono text-xs text-ink-soft">
                                    {{ $webhook->deliveries_count }} delivery
                                </span>
                            </div>
                            <p class="mt-1 break-all font-mono text-xs text-ink-soft">{{ $webhook->url }}</p>
                            @if (! empty($webhook->events))
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ((array) $webhook->events as $evt)
                                        <span class="rounded border border-rule bg-paper px-2 py-0.5 font-mono text-[11px] text-ink-soft">{{ $evt }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.webhooks.destroy', $webhook) }}" class="self-start sm:self-center">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn border-signal/30 bg-signal-tint text-signal hover:border-signal">
                                Hapus
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="px-5 py-12 text-center">
                        <x-heroicon-o-bolt class="mx-auto size-9 text-ink-faint" />
                        <p class="mt-3 font-display text-base font-semibold text-ink">Belum ada webhook.</p>
                        <p class="mt-1 text-sm text-ink-soft">Tambahkan endpoint pertama untuk mengintegrasikan event tiket ke sistem Anda.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
