@extends('layouts.portal')

@section('title', 'Buat Tiket — '.config('app.name'))

@section('content')
    <div>

        <div class="mt-3">
            <h1 class="font-display text-xl font-semibold tracking-tight text-ink sm:text-2xl">Buat tiket baru</h1>
            <p class="mt-0.5 text-sm text-ink-faint">Jelaskan kendala atau permintaan Anda secara rinci agar tim support dapat segera menindaklanjuti.</p>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-xl border border-signal/30 bg-signal-soft px-4 py-3.5" role="alert" aria-live="polite">
                <p class="text-xs font-semibold text-signal-dark">Mohon periksa kembali formulir Anda:</p>
                <ul class="mt-1.5 list-inside list-disc space-y-0.5 text-xs text-signal-dark">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="sheet mt-5 overflow-hidden">
            @csrf

            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label for="subject" class="block text-xs font-medium text-ink">Subjek kendala</label>
                    <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required maxlength="150" placeholder="Contoh: Tidak bisa login setelah reset password" class="field mt-1.5 @error('subject') border-signal @enderror">
                    @error('subject')
                        <p class="mt-1.5 text-xs font-medium text-signal-dark">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="category" class="block text-xs font-medium text-ink">Kategori</label>
                        <select id="category" name="category_id" class="field mt-1.5 @error('category_id') border-signal @enderror">
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->getKey() }}" @selected((string) old('category_id') === (string) $category->getKey())>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1.5 text-xs font-medium text-signal-dark">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="priority" class="block text-xs font-medium text-ink">Tingkat prioritas</label>
                        <select id="priority" name="priority" class="field mt-1.5 @error('priority') border-signal @enderror">
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}" @selected(old('priority', 'normal') === $priority->value)>{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                        @error('priority')
                            <p class="mt-1.5 text-xs font-medium text-signal-dark">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-xs font-medium text-ink">Deskripsi lengkap</label>
                    <textarea id="description" name="description" rows="6" required maxlength="20000" placeholder="Ceritakan kronologi masalah, langkah yang sudah dicoba, dan pesan error yang muncul…" class="field mt-1.5 @error('description') border-signal @enderror">{{ old('description') }}</textarea>
                    <p class="mt-1.5 text-xs text-ink-faint">Semakin lengkap informasi yang diberikan, semakin cepat kendala dapat diselesaikan.</p>
                    @error('description')
                        <p class="mt-1.5 text-xs font-medium text-signal-dark">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="attachments" class="block text-xs font-medium text-ink">Lampiran berkas <span class="font-normal text-ink-faint">(opsional)</span></label>
                    <label for="attachments" class="mt-1.5 flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-rule-strong bg-paper/50 px-4 py-6 text-center transition-colors hover:border-ink hover:bg-paper">
                        <span class="grid size-9 place-items-center rounded-lg border border-rule bg-paper-raised text-ink-faint shadow-2xs">
                            <x-heroicon-o-cloud-arrow-up class="size-5" />
                        </span>
                        <span class="mt-2.5 text-xs font-medium text-ink">Klik untuk memilih berkas atau tarik file ke area ini</span>
                        <span class="mt-1 text-xs text-ink-faint">PNG, JPG, PDF, ZIP · maksimal 10 MB per file</span>
                        <input id="attachments" name="attachments[]" type="file" multiple class="sr-only">
                    </label>
                    @error('attachments')
                        <p class="mt-1.5 text-xs font-medium text-signal-dark">{{ $message }}</p>
                    @enderror
                    @error('attachments.*')
                        <p class="mt-1.5 text-xs font-medium text-signal-dark">{{ $message }}</p>
                    @enderror
                </div>

                @if ($sla !== null)
                    <div class="flex items-start gap-3 rounded-xl border border-rule bg-paper/60 px-4 py-3.5">
                        <x-heroicon-o-clock class="mt-0.5 size-4 shrink-0 text-ink-soft" />
                        <div>
                            <p class="text-xs font-semibold text-ink">Target layanan ({{ $sla->name }})</p>
                            <p class="mt-0.5 text-xs text-ink-faint">
                                Respons pertama dalam {{ $sla->first_response_minutes }} menit · target penyelesaian {{ max(1, intdiv($sla->resolution_minutes, 60)) }} jam.
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-rule bg-paper/40 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="text-xs text-ink-faint">Tiket akan langsung diteruskan ke antrean tim support.</p>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <a href="{{ route('tickets.index') }}" class="btn btn-outline btn-sm">Batal</a>
                    <button type="submit" class="btn btn-ink btn-sm">
                        <span>Kirim tiket</span>
                        <x-heroicon-o-paper-airplane class="size-3.5" />
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
