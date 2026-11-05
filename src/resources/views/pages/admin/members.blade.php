@extends('layouts.admin')

@section('title', 'Anggota — '.config('app.name'))

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-mono">Workspace / akses</p>
            <h1 class="mt-2 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Anggota &amp; peran</h1>
            <p class="mt-1 max-w-2xl text-sm text-ink-soft">
                Kelola siapa yang dapat mengakses {{ $organization->name }} dan apa yang bisa mereka lakukan.
            </p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <x-ui.stamp tone="ink">{{ $members->count() }} anggota</x-ui.stamp>
            @if ($invitations->isNotEmpty())
                <x-ui.stamp tone="pending">{{ $invitations->count() }} menunggu</x-ui.stamp>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="mt-5 rounded-lg border border-pine/25 bg-pine-soft px-4 py-3 text-xs font-medium text-pine">{{ session('status') }}</div>
    @endif
    @foreach (['role', 'status', 'membership', 'email'] as $errorKey)
        @error($errorKey)
            <div class="mt-5 rounded-lg border border-signal/20 bg-signal-tint px-4 py-3 text-xs font-medium text-signal">{{ $message }}</div>
        @enderror
    @endforeach

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="sheet overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-rule px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-ink">Semua anggota</h2>
                    <p class="mt-1 text-xs text-ink-faint">Pilih satu anggota untuk mengubah aksesnya.</p>
                </div>
                <span class="text-xs text-ink-faint">Terakhir diperbarui hari ini</span>
            </div>

            <div class="table-scroll">
                <table class="min-w-[700px] w-full text-left text-sm">
                    <thead class="border-b border-rule bg-paper/50 text-[11px] font-semibold text-ink-faint">
                        <tr>
                            <th scope="col" class="px-5 py-3">Anggota</th>
                            <th scope="col" class="px-4 py-3">Peran</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3">Bergabung</th>
                            <th scope="col" class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rule">
                        @foreach ($members as $membership)
                            @php
                                $isSelf = $membership->user_id === auth()->id();
                                $isOwner = $membership->role->value === 'owner';
                                $isSuspended = $membership->status->value === 'suspended';
                                $canManage = ! $isSelf;
                                $canRemove = $canManage && ! $isOwner;
                            @endphp
                            <tr class="transition-colors hover:bg-paper/40" wire:key="member-{{ $membership->getKey() }}">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-9 shrink-0 place-items-center rounded-lg border border-rule bg-paper font-mono text-xs font-semibold text-ink">
                                            {{ $membership->user?->initials() ?? '??' }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="truncate font-medium text-ink">{{ $membership->user?->name ?? 'User dihapus' }}</p>
                                                @if ($isSelf)
                                                    <span class="rounded bg-paper-warm px-1.5 py-0.5 text-[10px] font-medium text-ink-soft">Anda</span>
                                                @endif
                                            </div>
                                            <p class="mt-0.5 truncate text-xs text-ink-faint">{{ $membership->user?->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <x-ui.stamp :tone="$isOwner ? 'solid' : 'ink'">{{ $membership->role->label() }}</x-ui.stamp>
                                </td>
                                <td class="px-4 py-3.5">
                                    <x-ui.stamp :tone="$membership->status->value === 'active' ? 'resolved' : 'pending'">{{ $membership->status->label() }}</x-ui.stamp>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-xs text-ink-faint">
                                    {{ $membership->joined_at?->translatedFormat('d M Y') ?? '—' }}
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @if ($canManage)
                                        <div x-data="{ open: false }">
                                            <button type="button" @click="open = true" class="btn btn-outline btn-sm">
                                                <x-heroicon-o-cog-6-tooth class="size-3.5" />
                                                Kelola
                                            </button>
                                            <div x-show="open" x-cloak x-transition.opacity @keydown.escape.window="open = false" class="fixed inset-0 z-50 flex items-center justify-center p-4 text-left" role="dialog" aria-modal="true" aria-labelledby="member-access-title-{{ $membership->getKey() }}">
                                                <button type="button" @click="open = false" class="absolute inset-0 bg-ink/40 backdrop-blur-xs" aria-label="Tutup dialog"></button>
                                                <div @click.stop class="relative z-10 w-full max-w-xl rounded-xl border border-rule bg-paper-raised p-5 shadow-xl sm:p-6">
                                                    <div class="flex items-start justify-between gap-4">
                                                        <div>
                                                            <p class="label-mono">Kelola akses</p>
                                                            <h3 id="member-access-title-{{ $membership->getKey() }}" class="mt-1 text-lg font-semibold text-ink">{{ $membership->user?->name ?? 'User dihapus' }}</h3>
                                                            <p class="mt-1 text-xs text-ink-faint">{{ $membership->user?->email }}</p>
                                                        </div>
                                                        <button type="button" @click="open = false" class="grid size-8 place-items-center rounded-lg text-ink-faint hover:bg-paper hover:text-ink" aria-label="Tutup dialog">
                                                            <x-heroicon-o-x-mark class="size-4" />
                                                        </button>
                                                    </div>

                                                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                                        <form method="POST" action="{{ route('admin.members.role.update', $membership) }}" class="rounded-lg border border-rule bg-paper/50 p-4">
                                                            @csrf
                                                            @method('PATCH')
                                                            <label for="member-role-{{ $membership->getKey() }}" class="field-label">Peran akses</label>
                                                            <select id="member-role-{{ $membership->getKey() }}" name="role" class="field mt-1.5">
                                                                @foreach ($manageRoles as $role)
                                                                    <option value="{{ $role->value }}" @selected($membership->role === $role)>{{ $role->label() }}</option>
                                                                @endforeach
                                                                @if ($isOwner)
                                                                    <option value="owner" selected>Owner</option>
                                                                @endif
                                                            </select>
                                                            <button type="submit" class="btn btn-ink mt-3 w-full">Simpan peran</button>
                                                        </form>

                                                        <div class="rounded-lg border border-rule bg-paper/50 p-4">
                                                            <p class="field-label">Status akses</p>
                                                            <form method="POST" action="{{ route('admin.members.status.update', $membership) }}" class="mt-1.5">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="status" value="{{ $isSuspended ? 'active' : 'suspended' }}">
                                                                <button type="submit" class="btn w-full {{ $isSuspended ? 'border-pine/30 bg-pine-tint text-pine hover:border-pine' : 'border-ochre/30 bg-ochre-tint text-ochre hover:border-ochre' }}">
                                                                    {{ $isSuspended ? 'Aktifkan kembali' : 'Tangguhkan akses' }}
                                                                </button>
                                                            </form>
                                                            @if ($canRemove)
                                                                <form method="POST" action="{{ route('admin.members.destroy', $membership) }}" class="mt-2">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn w-full border-signal/30 bg-signal-tint text-signal hover:border-signal">Keluarkan anggota</button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-xs text-ink-faint">Akun Anda</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="space-y-6">
            <section class="sheet overflow-hidden">
                <div class="border-b border-rule px-5 py-4">
                    <h2 class="text-sm font-semibold text-ink">Undang anggota</h2>
                    <p class="mt-1 text-xs leading-5 text-ink-faint">Undangan dikirim ke alamat email anggota.</p>
                </div>
                <form method="POST" action="{{ route('admin.members.invitations.store') }}" class="space-y-4 p-5">
                    @csrf
                    <div>
                        <label for="invite-email" class="field-label">Alamat email</label>
                        <input id="invite-email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@perusahaan.com" class="field mt-1.5" required>
                        @error('email') <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="invite-role" class="field-label">Peran akses</label>
                        <select id="invite-role" name="role" class="field mt-1.5">
                            @foreach ($inviteRoles as $role)
                                <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-signal w-full"><x-heroicon-o-paper-airplane class="size-4" />Kirim undangan</button>
                </form>
            </section>

            <section class="sheet overflow-hidden">
                <div class="flex items-center justify-between border-b border-rule px-5 py-4">
                    <h2 class="text-sm font-semibold text-ink">Undangan menunggu</h2>
                    <span class="font-mono text-xs text-ink-faint">{{ $invitations->count() }}</span>
                </div>
                <div class="divide-y divide-rule">
                    @forelse ($invitations as $invitation)
                        <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-ink">{{ $invitation->email }}</p>
                                <p class="mt-0.5 text-xs text-ink-faint">{{ $invitation->role->label() }} · hingga {{ $invitation->expires_at->translatedFormat('d M Y') }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.members.invitations.destroy', $invitation) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg px-2.5 py-1 text-xs font-medium text-signal hover:bg-signal-tint">Batalkan</button>
                            </form>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-ink-soft">Belum ada undangan.</div>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
@endsection
