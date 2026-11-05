<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.tickets.index') }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-ink-soft transition hover:bg-paper-raised hover:text-ink">
            <x-heroicon-o-arrow-left class="size-4" />
            Kembali ke antrean
        </a>

        <span class="font-mono text-[11px] text-ink-faint">Detail tiket</span>
    </div>

    <div class="mt-4 sheet overflow-hidden">
        <div class="flex flex-col gap-4 border-b border-rule px-5 py-5 sm:px-7 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
            <span class="font-mono text-xs font-semibold text-ink-faint">{{ $ticket->ticket_number }}</span>
            <x-ui.stamp :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-ui.stamp>
            <x-ui.stamp :tone="$ticket->priority->tone()">Prioritas {{ $ticket->priority->label() }}</x-ui.stamp>
            @if ($hasInternalNotes)
                <x-ui.stamp tone="pending">Memiliki Catatan Internal</x-ui.stamp>
            @endif
                </div>
                <h1 class="mt-3 max-w-4xl font-display text-2xl font-bold leading-tight tracking-tight text-ink sm:text-3xl">
                {{ $ticket->subject }}
                </h1>
                <p class="mt-2 text-xs text-ink-faint">
                    <span class="font-semibold text-ink">{{ $ticket->requester?->name ?? 'Customer' }}</span>
                    <span class="mx-1">·</span>{{ $ticket->requester?->email }}
                </p>
            </div>
            <div class="grid shrink-0 grid-cols-2 gap-x-8 gap-y-2 border-l border-rule pl-4 text-xs sm:grid-cols-3 lg:min-w-[390px]">
                <div>
                    <p class="text-ink-faint">Kanal</p>
                    <p class="mt-0.5 font-semibold text-ink">{{ $ticket->channel->label() }}</p>
                </div>
                <div>
                    <p class="text-ink-faint">Dibuat</p>
                    <p class="mt-0.5 font-semibold text-ink">{{ $ticket->created_at?->translatedFormat('d M Y, H:i') }}</p>
                </div>
                <div>
                    <p class="text-ink-faint">Aktivitas terakhir</p>
                    <p class="mt-0.5 font-semibold text-ink">{{ $ticket->last_activity_at?->diffForHumans() ?? '—' }}</p>
                </div>
            </div>
        </div>
        <div class="table-scroll">
            <table class="min-w-full text-left text-xs">
                <thead class="border-b border-rule bg-paper/50 text-[10px] font-semibold uppercase tracking-wide text-ink-faint">
                    <tr>
                        <th class="px-5 py-2.5 sm:px-7">Kategori</th>
                        <th class="px-5 py-2.5 sm:px-7">Tim support</th>
                        <th class="px-5 py-2.5 sm:px-7">Penanggung jawab</th>
                        <th class="px-5 py-2.5 sm:px-7">SLA</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="font-semibold text-ink">
                        <td class="whitespace-nowrap px-5 py-3 sm:px-7">{{ $ticket->category?->name ?? 'Tanpa kategori' }}</td>
                        <td class="whitespace-nowrap px-5 py-3 sm:px-7">{{ $ticket->team?->name ?? 'Tanpa tim' }}</td>
                        <td class="whitespace-nowrap px-5 py-3 sm:px-7">{{ $ticket->assignee?->name ?? 'Belum ditugaskan' }}</td>
                        <td class="whitespace-nowrap px-5 py-3 sm:px-7">{{ $ticket->slaPolicy?->name ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if ($notice !== '')
        <div class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-pine/30 bg-pine-soft px-4 py-3 text-sm font-medium text-pine" role="status" aria-live="polite">
            <div class="flex items-center gap-2.5">
                <x-heroicon-o-check-circle class="size-5 shrink-0" />
                <p>{{ $notice }}</p>
            </div>
            <button type="button" wire:click="$set('notice', '')" class="rounded-md p-1 text-pine/70 hover:text-pine" aria-label="Tutup notifikasi">
                <x-heroicon-o-x-mark class="size-4" />
            </button>
        </div>
    @endif

    <div class="mt-8">
        <div class="min-w-0">
            <section class="sheet overflow-hidden">
                <div class="flex items-center justify-between border-b border-rule px-5 py-3.5">
                    <h2 class="font-display text-base font-semibold text-ink">Riwayat Percakapan &amp; Aktivitas</h2>
                    <span class="rounded-full bg-paper px-2.5 py-0.5 text-xs font-medium text-ink-faint">{{ $messages->count() }} entri</span>
                </div>

                <ol class="divide-y divide-rule">
                    @forelse ($messages as $message)
                        @if ($message->type === \App\Shared\Enums\MessageType::Event)
                            <li wire:key="message-{{ $message->getKey() }}" class="flex items-center gap-3 bg-paper/50 px-5 py-2.5 text-xs text-ink-faint">
                                <span class="size-2 shrink-0 rounded-full bg-rule-strong" aria-hidden="true"></span>
                                <p class="flex-1">
                                    <span class="font-medium text-ink-soft">{{ $message->body }}</span>
                                    <span class="ml-1.5 text-ink-faint">· {{ $message->created_at?->translatedFormat('d M Y, H:i') }}</span>
                                </p>
                            </li>
                        @else
                            <li wire:key="message-{{ $message->getKey() }}" @class(['grid gap-4 border-b border-rule px-5 py-5 sm:grid-cols-[145px_minmax(0,1fr)]', 'bg-ochre-soft/45' => $message->is_internal])>
                                <div class="flex items-start gap-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <span class="grid size-8 place-items-center rounded-lg border border-rule-strong bg-paper text-xs font-semibold text-ink">
                                            {{ $message->user?->initials() ?? 'SF' }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-xs font-semibold text-ink">{{ $message->user?->name ?? 'Tim Support' }}</p>
                                            <p class="mt-0.5 text-[11px] text-ink-faint">{{ $message->created_at?->translatedFormat('d M Y, H:i') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    @if ($message->is_internal)
                                        <x-ui.stamp tone="pending">Catatan Internal</x-ui.stamp>
                                    @endif
                                    <div class="mt-2 whitespace-pre-line text-sm leading-relaxed text-ink">{{ $message->body }}</div>

                                @if ($message->attachments->isNotEmpty())
                                    <ul class="mt-4 flex flex-wrap gap-2">
                                        @foreach ($message->attachments as $attachment)
                                            <li wire:key="attachment-{{ $attachment->getKey() }}">
                                                <a href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-rule bg-paper px-3 py-1.5 text-xs font-medium text-ink-soft transition hover:border-ink hover:text-ink">
                                                    <x-heroicon-o-paper-clip class="size-3.5 text-ink-faint" />
                                                    <span>{{ $attachment->original_name }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endif
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-ink-faint">Belum ada pesan pada tiket ini.</li>
                    @endforelse
                </ol>

                <div class="border-t border-rule bg-paper/30" x-data="{ tab: 'reply' }">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-rule bg-paper/70 px-5 py-3">
                        <div class="inline-flex rounded-lg border border-rule bg-paper-raised p-0.5">
                            <button
                                type="button"
                                @click="tab = 'reply'; $wire.set('internal', false)"
                                :class="tab === 'reply' ? 'bg-ink text-white shadow-2xs' : 'text-ink-soft hover:text-ink'"
                                class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
                            >
                                Balasan Customer
                            </button>
                            <button
                                type="button"
                                @click="tab = 'note'; $wire.set('internal', true)"
                                :class="tab === 'note' ? 'bg-ochre text-white shadow-2xs' : 'text-ink-soft hover:text-ink'"
                                class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
                            >
                                Catatan Internal
                            </button>
                        </div>
                        <span
                            class="text-xs font-medium"
                            :class="tab === 'note' ? 'text-ochre' : 'text-ink-faint'"
                            x-text="tab === 'note' ? 'Hanya terlihat oleh staf internal' : 'Akan dikirimkan ke customer'"
                        ></span>
                    </div>

                    <form wire:submit="postReply" class="space-y-4 p-5">
                        @if ($cannedResponses->isNotEmpty())
                            <div>
                                <label for="canned-response" class="label-mono">Template Respons Cepat</label>
                                <select id="canned-response" wire:model="cannedResponseId" wire:change="applyCanned($event.target.value)" class="field mt-1">
                                    <option value="">Pilih template balasan…</option>
                                    @foreach ($cannedResponses as $canned)
                                        <option value="{{ $canned->getKey() }}">{{ $canned->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label for="ticket-reply" class="label-mono">Isi Pesan</label>
                            <textarea
                                id="ticket-reply"
                                wire:model="reply"
                                rows="5"
                                maxlength="20000"
                                placeholder="Tulis solusi untuk customer atau diskusikan catatan internal dengan tim…"
                                class="field mt-1"
                            ></textarea>
                            @error('reply')
                                <p class="mt-1.5 text-xs font-medium text-signal-dark">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="ticket-files" class="label-mono">Lampiran Berkas <span class="font-normal text-ink-faint">(opsional, maks. 5 × 10 MB)</span></label>
                            <input
                                id="ticket-files"
                                type="file"
                                wire:model="files"
                                multiple
                                class="mt-1 block w-full rounded-lg border border-rule-strong bg-paper-raised px-3 py-2 text-sm text-ink-soft file:mr-3 file:rounded-md file:border-0 file:bg-ink file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-ink-soft"
                            >
                            <div wire:loading wire:target="files" class="mt-1.5 text-xs text-ink-faint">Mengunggah lampiran…</div>
                            @error('files.*')
                                <p class="mt-1.5 text-xs font-medium text-signal-dark">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-rule pt-4">
                            <p class="text-xs text-ink-faint">
                                {{ $internal ? 'Catatan internal disimpan untuk staf.' : 'Customer akan menerima notifikasi balasan ini.' }}
                            </p>
                            <button type="submit" class="btn" :class="tab === 'note' ? 'btn-outline !border-ochre !text-ochre hover:!bg-ochre-soft' : 'btn-signal'" wire:loading.attr="disabled" wire:target="postReply,files">
                                <span wire:loading.remove wire:target="postReply">Kirim {{ $internal ? 'catatan' : 'balasan' }}</span>
                                <span wire:loading wire:target="postReply">Menyimpan…</span>
                                <x-heroicon-o-paper-airplane class="size-4" />
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <aside class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.6fr)]">
            {{-- Tabel Manajemen & Properti Tiket --}}
            <div class="sheet overflow-hidden xl:col-span-2">
                <div class="flex items-center justify-between border-b border-rule bg-paper/60 px-5 py-3.5">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-table-cells class="size-4 text-ink-faint" />
                        <h2 class="font-display text-sm font-semibold text-ink">Manajemen &amp; Atribut Tiket</h2>
                    </div>
                    <span class="font-mono text-[11px] text-ink-faint">Properti</span>
                </div>

                <div class="table-scroll">
                    <table class="min-w-full text-left text-xs divide-y divide-rule">
                        <tbody class="divide-y divide-rule">
                            {{-- Status & Transisi --}}
                            <tr>
                                <th class="w-1/3 whitespace-nowrap bg-paper/30 px-5 py-3.5 font-medium text-ink-faint align-top">Status</th>
                                <td class="px-5 py-3.5 space-y-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-ui.stamp :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-ui.stamp>
                                        @if ($ticket->resolved_at)
                                            <span class="text-[11px] text-ink-faint">Selesai: {{ $ticket->resolved_at->translatedFormat('d M Y, H:i') }}</span>
                                        @endif
                                        @if ($ticket->closed_at)
                                            <span class="text-[11px] text-ink-faint">Tutup: {{ $ticket->closed_at->translatedFormat('d M Y, H:i') }}</span>
                                        @endif
                                    </div>

                                    @if ($allowedTransitions === [])
                                        <p class="text-[11px] text-ink-faint">Status final tidak dapat diubah lagi.</p>
                                    @else
                                        <form wire:submit="changeStatus" class="space-y-2 pt-1 border-t border-rule/60">
                                            <div>
                                                <label for="ticket-status" class="sr-only">Ubah status</label>
                                                <select id="ticket-status" wire:model="status" class="field text-xs !py-1.5 !min-h-8">
                                                    <option value="">Pilih status transisi…</option>
                                                    @foreach ($allowedTransitions as $transition)
                                                        <option value="{{ $transition->value }}">{{ $transition->label() }}</option>
                                                    @endforeach
                                                </select>
                                                @error('status')
                                                    <p class="mt-1 text-[11px] font-medium text-signal-dark">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div>
                                                <input id="ticket-status-note" type="text" wire:model="statusNote" maxlength="500" class="field text-xs !py-1.5 !min-h-8" placeholder="Catatan transisi (opsional)…">
                                            </div>

                                            <button type="submit" class="btn btn-ink btn-sm w-full" wire:loading.attr="disabled" wire:target="changeStatus">
                                                Terapkan status
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>

                            {{-- Penugasan (Assignee) --}}
                            <tr>
                                <th class="whitespace-nowrap bg-paper/30 px-5 py-3.5 font-medium text-ink-faint align-top">Penanggung Jawab</th>
                                <td class="px-5 py-3.5">
                                    @if ($canManageTicket)
                                        <form wire:submit="saveAssignment" class="space-y-2">
                                            <div>
                                                <select id="ticket-assignee" wire:model="assigneeId" class="field text-xs !py-1.5 !min-h-8">
                                                    <option value="">Belum ditugaskan</option>
                                                    @foreach ($agents as $membership)
                                                        <option value="{{ $membership->user_id }}">{{ $membership->user?->name ?? 'Anggota' }} · {{ $membership->role->label() }}</option>
                                                    @endforeach
                                                </select>
                                                @error('assigneeId')
                                                    <p class="mt-1 text-[11px] font-medium text-signal-dark">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <button type="submit" class="btn btn-outline btn-sm w-full" wire:loading.attr="disabled" wire:target="saveAssignment">
                                                Simpan penugasan
                                            </button>
                                        </form>
                                    @else
                                        <span class="font-semibold text-ink">{{ $ticket->assignee?->name ?? 'Belum ditugaskan' }}</span>
                                    @endif
                                </td>
                            </tr>

                            {{-- Klasifikasi: Prioritas & Kategori --}}
                            <tr>
                                <th class="whitespace-nowrap bg-paper/30 px-5 py-3.5 font-medium text-ink-faint align-top">Klasifikasi</th>
                                <td class="px-5 py-3.5">
                                    @if ($canManageTicket)
                                        <form wire:submit="saveClassification" class="space-y-2">
                                            <div>
                                                <label for="ticket-priority" class="text-[11px] text-ink-faint font-medium">Prioritas</label>
                                                <select id="ticket-priority" wire:model="priority" class="field text-xs mt-0.5 !py-1.5 !min-h-8">
                                                    @foreach (\App\Shared\Enums\TicketPriority::cases() as $option)
                                                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                                    @endforeach
                                                </select>
                                                @error('priority')
                                                    <p class="mt-1 text-[11px] font-medium text-signal-dark">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div>
                                                <label for="ticket-category" class="text-[11px] text-ink-faint font-medium">Kategori</label>
                                                <select id="ticket-category" wire:model="categoryId" class="field text-xs mt-0.5 !py-1.5 !min-h-8">
                                                    <option value="">Tanpa kategori</option>
                                                    @foreach ($categories as $option)
                                                        <option value="{{ $option->getKey() }}">{{ $option->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('categoryId')
                                                    <p class="mt-1 text-[11px] font-medium text-signal-dark">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <button type="submit" class="btn btn-outline btn-sm w-full" wire:loading.attr="disabled" wire:target="saveClassification">
                                                Simpan klasifikasi
                                            </button>
                                        </form>
                                    @else
                                        <div class="space-y-1">
                                            <p><span class="text-ink-faint">Prioritas:</span> <x-ui.stamp :tone="$ticket->priority->tone()">{{ $ticket->priority->label() }}</x-ui.stamp></p>
                                            <p><span class="text-ink-faint">Kategori:</span> <strong class="text-ink">{{ $ticket->category?->name ?? 'Tanpa kategori' }}</strong></p>
                                        </div>
                                    @endif
                                </td>
                            </tr>

                            {{-- Tim & Kanal --}}
                            <tr>
                                <th class="whitespace-nowrap bg-paper/30 px-5 py-3 font-medium text-ink-faint">Tim Support</th>
                                <td class="px-5 py-3 font-medium text-ink">{{ $ticket->team?->name ?? 'Tanpa tim' }}</td>
                            </tr>
                            <tr>
                                <th class="whitespace-nowrap bg-paper/30 px-5 py-3 font-medium text-ink-faint">Kanal Masuk</th>
                                <td class="px-5 py-3 font-medium text-ink">{{ $ticket->channel->label() }}</td>
                            </tr>

                            {{-- Pengamat --}}
                            <tr>
                                <th class="whitespace-nowrap bg-paper/30 px-5 py-3.5 font-medium text-ink-faint align-top">
                                    Pengamat
                                    <span class="ml-1 rounded-full bg-paper px-1.5 py-0.5 text-[10px]">{{ $watchers->count() }}</span>
                                </th>
                                <td class="px-5 py-3.5">
                                    @php
                                        $watchingIds = $watchers->pluck('id')->all();
                                        $watcherOptions = $agents->filter(fn ($membership) => ! in_array($membership->user_id, $watchingIds, true));
                                    @endphp

                                    <div class="flex flex-wrap gap-2">
                                        @forelse ($watchers as $watcher)
                                            <span wire:key="watcher-{{ $watcher->getKey() }}" class="inline-flex items-center gap-1.5 rounded-md border border-rule bg-paper px-2 py-1 text-[11px] font-medium text-ink">
                                                <span class="grid size-5 place-items-center rounded border border-rule-strong text-[9px]">{{ $watcher->initials() }}</span>
                                                <span>{{ $watcher->name }}</span>
                                                @if ($canManageTicket)
                                                    <button type="button" wire:click="removeWatcher({{ $watcher->getKey() }})" wire:confirm="Hapus {{ $watcher->name }} dari watcher?" class="ml-0.5 text-ink-faint hover:text-signal-dark" aria-label="Hapus watcher {{ $watcher->name }}">
                                                        <x-heroicon-o-x-mark class="size-3.5" />
                                                    </button>
                                                @endif
                                            </span>
                                        @empty
                                            <span class="text-xs text-ink-faint">Belum ada pengamat.</span>
                                        @endforelse
                                    </div>

                                    @if ($canManageTicket && $watcherOptions->isNotEmpty())
                                        <form wire:submit="addWatcher" class="mt-3 flex max-w-md items-center gap-2">
                                            <label for="ticket-watcher" class="sr-only">Tambah Pengamat</label>
                                            <select id="ticket-watcher" wire:model="watcherId" class="field text-xs !min-h-8 !py-1.5">
                                                <option value="">Tambah pengamat…</option>
                                                @foreach ($watcherOptions as $membership)
                                                    <option value="{{ $membership->user_id }}">{{ $membership->user?->name ?? 'Anggota' }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-outline btn-sm !px-3" aria-label="Tambah watcher">
                                                <x-heroicon-o-plus class="size-4" />
                                            </button>
                                        </form>
                                    @endif

                                    @error('watcherId')
                                        <p class="mt-1.5 text-[11px] font-medium text-signal-dark">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>

                            {{-- Kebijakan SLA & Target --}}
                            <tr>
                                <th class="whitespace-nowrap bg-paper/30 px-5 py-3.5 font-medium text-ink-faint align-top">SLA &amp; Batas Waktu</th>
                                <td class="px-5 py-3.5 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-ink-faint">Kebijakan:</span>
                                        <span class="font-semibold text-ink">{{ $ticket->slaPolicy?->name ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-ink-faint">Target Respons:</span>
                                        <span class="font-medium text-ink">{{ $ticket->first_response_due_at?->translatedFormat('d M Y, H:i') ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-ink-faint">Target Resolusi:</span>
                                        <span class="font-medium text-ink">{{ $ticket->due_at?->translatedFormat('d M Y, H:i') ?? '—' }}</span>
                                    </div>
                                    @if ($ticket->first_response_at)
                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="text-ink-faint">Respons Pertama:</span>
                                            <span class="font-medium text-ink">{{ $ticket->first_response_at->translatedFormat('d M Y, H:i') }}</span>
                                        </div>
                                    @endif
                                </td>
                            </tr>

                            {{-- Waktu & Aktivitas --}}
                            <tr>
                                <th class="whitespace-nowrap bg-paper/30 px-5 py-3.5 font-medium text-ink-faint align-top">Timeline</th>
                                <td class="px-5 py-3.5 space-y-1 text-[11px]">
                                    <div class="flex items-center justify-between">
                                        <span class="text-ink-faint">Dibuat:</span>
                                        <span class="font-medium text-ink">{{ $ticket->created_at?->translatedFormat('d M Y, H:i') }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-ink-faint">Aktivitas Terakhir:</span>
                                        <span class="font-medium text-ink">{{ $ticket->last_activity_at?->diffForHumans() ?? '—' }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Card Tertentu: Label & Tag --}}
            @if ($canManageTicket)
                <div class="sheet overflow-hidden">
                    <div class="flex items-center justify-between border-b border-rule bg-paper/60 px-5 py-3.5">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-tag class="size-4 text-ink-faint" />
                            <h2 class="font-display text-sm font-semibold text-ink">Label &amp; Tag</h2>
                        </div>
                        <span class="rounded-full bg-paper px-2 py-0.5 text-xs font-medium text-ink-faint">{{ count($tagIds) }} terpilih</span>
                    </div>

                    <div class="p-5">
                        @if ($tags->isEmpty())
                            <p class="text-xs text-ink-faint">Belum ada tag yang dikonfigurasi di organisasi ini.</p>
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach ($tags as $tag)
                                    <label wire:key="tag-option-{{ $tag->getKey() }}" class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-rule bg-paper/60 px-2.5 py-1.5 text-xs font-medium text-ink-soft transition hover:border-rule-strong hover:text-ink">
                                        <input type="checkbox" wire:model="tagIds" value="{{ $tag->getKey() }}" class="size-3.5 rounded-xs border-rule-strong text-ink focus:ring-0">
                                        <span class="size-2 shrink-0 rounded-full" style="background-color: {{ $tag->color }}"></span>
                                        <span class="truncate">{{ $tag->name }}</span>
                                    </label>
                                @endforeach
                            </div>

                            @error('tagIds')
                                <p class="mt-2 text-xs font-medium text-signal-dark">{{ $message }}</p>
                            @enderror

                            <button type="button" wire:click="saveTags" class="btn btn-outline btn-sm mt-3.5 w-full" wire:loading.attr="disabled" wire:target="saveTags">
                                Simpan tag
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Card Tertentu: Lampiran Berkas Tiket --}}
            @if ($ticket->attachments->isNotEmpty())
                <div class="sheet overflow-hidden xl:col-span-2">
                    <div class="flex items-center justify-between border-b border-rule bg-paper/60 px-5 py-3.5">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-paper-clip class="size-4 text-ink-faint" />
                            <h2 class="font-display text-sm font-semibold text-ink">Lampiran Tiket</h2>
                        </div>
                        <span class="rounded-full bg-paper px-2 py-0.5 text-xs font-medium text-ink-faint">{{ $ticket->attachments->count() }}</span>
                    </div>

                    <div class="p-5">
                        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($ticket->attachments as $attachment)
                                <li wire:key="ticket-attachment-{{ $attachment->getKey() }}">
                                    <div class="overflow-hidden border border-rule bg-paper/50">
                                        @if (str_starts_with($attachment->mime_type, 'image/'))
                                            <a href="{{ route('admin.tickets.attachments.preview', [$ticket, $attachment]) }}" target="_blank" rel="noopener" class="block aspect-[4/3] bg-paper transition hover:opacity-80">
                                                <img src="{{ route('admin.tickets.attachments.preview', [$ticket, $attachment]) }}" alt="{{ $attachment->original_name }}" class="size-full object-cover">
                                            </a>
                                        @elseif ($attachment->mime_type === 'application/pdf')
                                            <iframe src="{{ route('admin.tickets.attachments.preview', [$ticket, $attachment]) }}" title="Preview {{ $attachment->original_name }}" class="h-52 w-full bg-white"></iframe>
                                        @else
                                            <div class="grid aspect-[4/3] place-items-center bg-paper">
                                                <x-heroicon-o-document-text class="size-10 text-ink-faint" />
                                            </div>
                                        @endif
                                        <div class="flex items-center gap-3 border-t border-rule px-3 py-2.5">
                                            <x-heroicon-o-paper-clip class="size-4 shrink-0 text-ink-faint" />
                                            <span class="min-w-0 flex-1 truncate text-xs font-medium text-ink-soft">{{ $attachment->original_name }}</span>
                                            <a href="{{ route('admin.tickets.attachments.download', [$ticket, $attachment]) }}" class="shrink-0 rounded p-1 text-ink-faint transition hover:bg-paper hover:text-ink" aria-label="Unduh {{ $attachment->original_name }}">
                                                <x-heroicon-o-arrow-down-tray class="size-4" />
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Zona Bahaya: Hapus Tiket --}}
            @if ($canDelete)
                <div class="rounded-xl border border-rule bg-paper/40 p-4 text-center xl:col-span-2">
                    <button
                        type="button"
                        wire:click="deleteTicket"
                        wire:confirm="Hapus tiket {{ $ticket->ticket_number }}? Tindakan ini tidak bisa dibatalkan."
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-signal-dark hover:underline"
                    >
                        <x-heroicon-o-trash class="size-4" />
                        Hapus tiket ini secara permanen
                    </button>
                </div>
            @endif
        </aside>
    </div>
</div>
