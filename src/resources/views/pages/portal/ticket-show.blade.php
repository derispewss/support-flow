@extends('layouts.portal')

@section('title', $ticket->ticket_number.' — '.config('app.name'))

@section('content')

    <div class="mt-3 flex flex-col gap-4 border-b border-rule pb-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded border border-rule bg-paper/60 px-2 py-0.5 font-mono text-xs font-semibold text-ink-soft">{{ $ticket->ticket_number }}</span>
                <x-ui.stamp :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-ui.stamp>
                <x-ui.stamp :tone="$ticket->priority->tone()">Prioritas {{ $ticket->priority->label() }}</x-ui.stamp>
            </div>
            <h1 class="mt-2 font-display text-xl font-semibold leading-snug tracking-tight text-ink sm:text-2xl">{{ $ticket->subject }}</h1>
            <p class="mt-1 text-xs text-ink-faint">
                Dibuat pada {{ $ticket->created_at?->format('d M Y, H:i') }} · melalui {{ $ticket->channel->label() }}
            </p>
        </div>

        @if ($isStaff)
            <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-outline btn-sm self-start">
                <span>Buka di konsol admin</span>
                <x-heroicon-o-arrow-top-right-on-square class="size-3.5" />
            </a>
        @endif
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <section class="space-y-5 lg:col-span-2">
            <div class="sheet overflow-hidden">
                <div class="flex items-center justify-between border-b border-rule px-5 py-3.5">
                    <h2 class="text-sm font-semibold text-ink">Riwayat percakapan</h2>
                    <span class="rounded-md border border-rule bg-paper px-1.5 py-0.5 font-mono text-[11px] font-medium text-ink-faint">{{ $messages->count() }} pesan</span>
                </div>

                <ol class="divide-y divide-rule">
                    @foreach ($messages as $message)
                        @if ($message->type === \App\Shared\Enums\MessageType::Event)
                            <li class="flex items-center gap-2.5 bg-paper/40 px-5 py-2.5 text-xs text-ink-faint">
                                <span class="size-1.5 shrink-0 rounded-full bg-rule-strong" aria-hidden="true"></span>
                                <span>{{ $message->body }}</span>
                            </li>
                        @else
                            <li class="p-5 {{ $message->is_internal ? 'bg-ochre-soft/50' : '' }}">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <span class="grid size-8 place-items-center rounded-lg border border-rule bg-paper font-mono text-xs font-semibold text-ink shadow-2xs">
                                        {{ $message->user?->initials() ?? 'SF' }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-semibold text-ink">{{ $message->user?->name ?? 'Tim support' }}</span>
                                        <span class="ml-2 text-xs text-ink-faint">{{ $message->created_at?->format('d M Y, H:i') }}</span>
                                    </div>
                                    @if ($message->is_internal)
                                        <x-ui.stamp tone="pending">Catatan Internal</x-ui.stamp>
                                    @endif
                                </div>

                                <div class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ink">{{ $message->body }}</div>

                                @if ($message->attachments->isNotEmpty())
                                    <ul class="mt-3.5 flex flex-wrap gap-2">
                                        @foreach ($message->attachments as $attachment)
                                            <li>
                                                <a href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-rule bg-paper px-2.5 py-1.5 text-xs font-medium text-ink-soft transition-colors hover:border-rule-strong hover:text-ink">
                                                    <x-heroicon-o-paper-clip class="size-3.5 text-ink-faint" />
                                                    <span>{{ $attachment->original_name }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endif
                    @endforeach
                </ol>

                @if ($isStaff)
                    <div class="border-t border-rule bg-paper/40 px-5 py-2.5 text-xs text-ink-faint">
                        Anda dapat melihat catatan internal pada tiket ini karena masuk sebagai staf organisasi.
                    </div>
                @endif
            </div>

            <div class="sheet overflow-hidden">
                <div class="border-b border-rule px-5 py-3.5">
                    <h2 class="text-sm font-semibold text-ink">Kirim balasan</h2>
                </div>

                <form method="POST" action="{{ route('tickets.messages.store', $ticket) }}" enctype="multipart/form-data" class="space-y-4 p-5">
                    @csrf

                    @if ($errors->any())
                        <div class="rounded-xl border border-signal/30 bg-signal-soft px-4 py-3" role="alert">
                            <ul class="list-inside list-disc space-y-0.5 text-xs text-signal-dark">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <label for="body" class="block text-xs font-medium text-ink">Pesan balasan</label>
                        <textarea id="body" name="body" rows="4" required maxlength="20000" placeholder="Tuliskan balasan atau informasi tambahan…" class="field mt-1.5">{{ old('body') }}</textarea>
                    </div>

                    <div>
                        <label for="reply-attachments" class="block text-xs font-medium text-ink">Lampiran tambahan <span class="font-normal text-ink-faint">(opsional)</span></label>
                        <input id="reply-attachments" name="attachments[]" type="file" multiple class="mt-1.5 block w-full rounded-lg border border-rule-strong bg-paper-raised px-3 py-2 text-xs text-ink-soft file:mr-3 file:rounded-md file:border-0 file:bg-ink file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-white">
                    </div>

                    <div class="flex flex-col gap-3 border-t border-rule pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-ink-faint">Balasan Anda akan langsung diberitahukan ke agent yang bertugas.</p>
                        <button type="submit" class="btn btn-ink btn-sm self-end sm:self-auto">
                            <span>Kirim balasan</span>
                            <x-heroicon-o-paper-airplane class="size-3.5" />
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <aside class="space-y-4">
            <div class="sheet overflow-hidden">
                <div class="border-b border-rule px-5 py-3.5">
                    <h2 class="text-sm font-semibold text-ink">Informasi tiket</h2>
                </div>
                <dl class="divide-y divide-rule text-sm">
                    @foreach ([
                        ['Status', $ticket->status->label()],
                        ['Prioritas', $ticket->priority->label()],
                        ['Kategori', $ticket->category?->name ?? 'Tanpa kategori'],
                        ['Kanal', $ticket->channel->label()],
                        ['Ditangani oleh', $ticket->assignee?->name ?? 'Belum ditugaskan'],
                        ['Aktivitas terakhir', $ticket->last_activity_at?->diffForHumans() ?? '—'],
                    ] as [$label, $value])
                        <div class="flex items-center justify-between gap-4 px-5 py-3">
                            <dt class="text-xs text-ink-faint">{{ $label }}</dt>
                            <dd class="text-right text-xs font-semibold text-ink">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            @if ($ticket->tags->isNotEmpty())
                <div class="sheet overflow-hidden">
                    <div class="border-b border-rule px-5 py-3.5">
                        <h2 class="text-sm font-semibold text-ink">Tag</h2>
                    </div>
                    <div class="flex flex-wrap gap-2 p-5">
                        @foreach ($ticket->tags as $tag)
                            <x-ui.stamp tone="faint">{{ $tag->name }}</x-ui.stamp>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($ticket->attachments->isNotEmpty())
                <div class="sheet overflow-hidden">
                    <div class="border-b border-rule px-5 py-3.5">
                        <h2 class="text-sm font-semibold text-ink">Semua lampiran</h2>
                    </div>
                    <ul class="divide-y divide-rule">
                        @foreach ($ticket->attachments as $attachment)
                            <li>
                                <a href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}" class="flex items-center gap-2.5 px-5 py-3 text-xs font-medium text-ink-soft transition-colors hover:bg-paper/60 hover:text-ink">
                                    <x-heroicon-o-paper-clip class="size-4 shrink-0 text-ink-faint" />
                                    <span class="min-w-0 flex-1 truncate">{{ $attachment->original_name }}</span>
                                    <x-heroicon-o-arrow-down-tray class="size-4 shrink-0 text-ink-faint" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-xl border border-rule bg-paper/60 p-4">
                <p class="text-xs font-semibold text-ink">Tips keamanan</p>
                <p class="mt-1 text-xs leading-relaxed text-ink-faint">
                    Tim support tidak pernah meminta password atau kode OTP Anda. Hindari mencantumkan kredensial rahasia di kolom percakapan.
                </p>
            </div>
        </aside>
    </div>
@endsection
