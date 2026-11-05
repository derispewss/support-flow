@extends('layouts.admin')

@section('title', 'Tim — '.config('app.name'))

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Tim</h1>
            <p class="mt-1 text-sm text-ink-soft">Kelompokkan staf berdasarkan divisi untuk routing dan pembagian beban tiket.</p>
        </div>
        <div class="self-start sm:self-auto">
            <x-ui.stamp tone="ink">{{ $teams->count() }} tim</x-ui.stamp>
        </div>
    </div>

    <div class="mt-6">
        <x-admin.settings-nav active="teams" />
    </div>

    @php
        $staffOptions = $staff->map(fn ($membership) => [
            'id' => $membership->user_id,
            'name' => $membership->user?->name ?? 'Anggota',
            'role' => $membership->role->label(),
        ]);
    @endphp

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_1.6fr]">
        <section class="sheet overflow-hidden self-start">
            <div class="border-b border-rule px-5 py-4">
                <h2 class="font-display text-base font-semibold text-ink">Tim baru</h2>
                <p class="mt-0.5 text-xs text-ink-faint">Pilih ketua tim dan anggota staf yang tergabung.</p>
            </div>

            @if ($staffOptions->isEmpty())
                <p class="p-5 text-sm text-ink-soft">Tambahkan anggota staf terlebih dahulu sebelum membuat tim.</p>
            @else
                <form method="POST" action="{{ route('admin.settings.teams.store') }}" class="space-y-4 p-5">
                    @csrf

                    <div>
                        <label for="team-name" class="field-label">Nama tim</label>
                        <input id="team-name" name="name" type="text" value="{{ old('name') }}" maxlength="120" required class="field mt-1.5" placeholder="Contoh: Layanan Pelanggan">
                        @error('name')
                            <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="team-description" class="field-label">Deskripsi <span class="font-normal text-ink-faint">(opsional)</span></label>
                        <textarea id="team-description" name="description" rows="2" maxlength="500" class="field mt-1.5" placeholder="Fokus penanganan tim ini…">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="team-lead" class="field-label">Ketua tim</label>
                        <select id="team-lead" name="lead_id" class="field mt-1.5">
                            <option value="">Tanpa ketua</option>
                            @foreach ($staffOptions as $option)
                                <option value="{{ $option['id'] }}" @selected((string) old('lead_id') === (string) $option['id'])>{{ $option['name'] }}</option>
                            @endforeach
                        </select>
                        @error('lead_id')
                            <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <p class="field-label">Anggota tim</p>
                        <div class="mt-1.5 grid gap-2 sm:grid-cols-2">
                            @foreach ($staffOptions as $option)
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-rule bg-paper px-3 py-2 text-xs font-medium text-ink transition-colors hover:border-rule-strong">
                                    <input type="checkbox" name="members[]" value="{{ $option['id'] }}" @checked(in_array((string) $option['id'], array_map('strval', old('members', [])), true)) class="size-4 rounded border-rule-strong text-ink focus:ring-0">
                                    <span class="truncate">{{ $option['name'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('members.*')
                            <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-signal w-full">
                        <x-heroicon-o-plus class="size-4" />
                        Tambah tim
                    </button>
                </form>
            @endif
        </section>

        <section class="space-y-3">
            @forelse ($teams as $team)
                @php
                    $memberIds = $team->members->pluck('id')->map(fn ($id) => (int) $id)->all();
                @endphp
                <article class="sheet overflow-hidden" wire:key="team-{{ $team->getKey() }}">
                    <div class="p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="font-display text-base font-semibold text-ink">{{ $team->name }}</h2>
                                <p class="mt-1 text-xs text-ink-faint">
                                    Ketua: <span class="font-medium text-ink-soft">{{ $team->lead?->name ?? '—' }}</span> · {{ $team->members->count() }} anggota · {{ $team->tickets_count }} tiket
                                </p>
                            </div>
                        </div>

                        @if ($team->description)
                            <p class="mt-3 text-sm leading-relaxed text-ink-soft">{{ $team->description }}</p>
                        @endif

                        <ul class="mt-4 flex flex-wrap gap-2">
                            @forelse ($team->members as $member)
                                <li class="inline-flex items-center gap-2 rounded-lg border border-rule bg-paper px-2.5 py-1 text-xs font-medium text-ink">
                                    <span class="grid size-5 place-items-center rounded-full bg-ink font-mono text-[10px] font-semibold text-paper">{{ $member->initials() }}</span>
                                    <span>{{ $member->name }}</span>
                                    @if ((int) $team->lead_id === (int) $member->getKey())
                                        <span class="rounded bg-signal-tint px-1.5 py-0.5 text-[10px] font-semibold text-signal">Ketua</span>
                                    @endif
                                </li>
                            @empty
                                <li class="text-xs text-ink-faint">Belum ada anggota di tim ini.</li>
                            @endforelse
                        </ul>

                        <details class="group mt-4">
                            <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-rule bg-paper px-3 py-1.5 text-xs font-medium text-ink-soft transition-colors hover:border-rule-strong hover:text-ink">
                                <x-heroicon-o-pencil-square class="size-3.5 text-ink-faint" />
                                <span>Edit tim</span>
                                <x-heroicon-o-chevron-down class="size-3 text-ink-faint transition-transform group-open:rotate-180" />
                            </summary>

                            <div class="mt-3 space-y-4 rounded-xl border border-rule bg-paper p-4">
                                <form method="POST" action="{{ route('admin.settings.teams.update', $team) }}" class="space-y-3">
                                    @csrf
                                    @method('PATCH')

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label for="team-name-{{ $team->getKey() }}" class="field-label">Nama tim</label>
                                            <input id="team-name-{{ $team->getKey() }}" name="name" type="text" value="{{ $team->name }}" maxlength="120" required class="field mt-1">
                                        </div>

                                        <div>
                                            <label for="team-lead-{{ $team->getKey() }}" class="field-label">Ketua tim</label>
                                            <select id="team-lead-{{ $team->getKey() }}" name="lead_id" class="field mt-1">
                                                <option value="">Tanpa ketua</option>
                                                @foreach ($staffOptions as $option)
                                                    <option value="{{ $option['id'] }}" @selected((int) $team->lead_id === (int) $option['id'])>{{ $option['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="team-description-{{ $team->getKey() }}" class="field-label">Deskripsi</label>
                                        <textarea id="team-description-{{ $team->getKey() }}" name="description" rows="2" maxlength="500" class="field mt-1">{{ $team->description }}</textarea>
                                    </div>

                                    <div>
                                        <p class="field-label">Anggota tim</p>
                                        <div class="mt-1.5 grid gap-2 sm:grid-cols-2">
                                            @foreach ($staffOptions as $option)
                                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-rule bg-paper-raised px-3 py-2 text-xs text-ink">
                                                    <input type="checkbox" name="members[]" value="{{ $option['id'] }}" @checked(in_array((int) $option['id'], $memberIds, true)) class="size-4 rounded border-rule-strong text-ink focus:ring-0">
                                                    <span class="truncate">{{ $option['name'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="flex justify-end pt-1">
                                        <button type="submit" class="btn btn-ink">Simpan perubahan</button>
                                    </div>
                                </form>

                                <div class="flex items-center justify-between border-t border-rule pt-3">
                                    <span class="text-xs text-ink-faint">Hapus tim ini dari organisasi</span>
                                    <form method="POST" action="{{ route('admin.settings.teams.destroy', $team) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-signal hover:underline">Hapus tim</button>
                                    </form>
                                </div>
                            </div>
                        </details>
                    </div>
                </article>
            @empty
                <div class="sheet px-5 py-12 text-center">
                    <x-heroicon-o-user-group class="mx-auto size-9 text-ink-faint" />
                    <p class="mt-3 font-display text-base font-semibold text-ink">Belum ada tim.</p>
                    <p class="mt-1 text-sm text-ink-soft">Buat tim pertama lewat formulir di samping.</p>
                </div>
            @endforelse
        </section>
    </div>
@endsection
