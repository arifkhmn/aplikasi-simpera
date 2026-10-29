@extends('layouts.app', ['pageTitle' => 'Tambah Pengingat'])

@section('content')
<div style="max-width: 760px; margin: 0 auto 24px auto;">
    <a href="{{ route('reminders.index') }}" class="btn btn-secondary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
        <span>Kembali ke Daftar Pengingat</span>
    </a>
</div>

<div class="vault-form-card">
    <div style="margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--color-border-subtle);">
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-primary); margin-bottom: 6px;">
            Jadwalkan Pengingat Baru
        </h1>
        <p style="font-size: 13.5px; color: var(--color-text-secondary); line-height: 1.4;">
            Sistem Simpera akan memantau batas waktu ini dan menyiapkan notifikasi otomatis pada 30 hari, 7 hari, dan 1 hari sebelum jatuh tempo.
        </p>
    </div>

    @if($errors->any())
        <div class="auth-alert-danger" style="margin-bottom: 24px;">
            <div>
                <strong>Perhatian:</strong>
                <ul style="margin-top: 6px; padding-left: 18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('reminders.store') }}">
        @csrf

        <!-- Judul Pengingat -->
        <div class="form-group">
            <label for="title" class="form-label">
                Judul Pengingat <span style="color: var(--color-danger-text);">*</span>
            </label>
            <input 
                type="text" 
                id="title" 
                name="title" 
                value="{{ old('title', $prefillDocument ? 'Perpanjang ' . $prefillDocument->title : '') }}" 
                class="form-input @error('title') is-invalid @enderror" 
                placeholder="Contoh: Perpanjang Masa Berlaku SIM A"
                required
                maxlength="255"
                autocomplete="off"
            >
            @error('title')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <!-- Dokumen Terkait & Tanggal Jatuh Tempo (Grid 2 Kolom) -->
        <div class="form-grid-2">
            <!-- Pilihan Dokumen Terkait -->
            <div class="form-group">
                <label for="document_id" class="form-label">
                    Tautkan ke Dokumen (Opsional)
                </label>
                <select 
                    id="document_id" 
                    name="document_id" 
                    class="form-input @error('document_id') is-invalid @enderror"
                >
                    <option value="" data-expiry="" data-title="">-- Tanpa Dokumen Terkait --</option>
                    @foreach($documents as $doc)
                        @php
                            $expiryFormatted = $doc->expiry_date ? $doc->expiry_date->format('Y-m-d') : '';
                        @endphp
                        <option 
                            value="{{ $doc->id }}" 
                            {{ old('document_id', $preselectedDocId) == $doc->id ? 'selected' : '' }}
                            data-expiry="{{ $expiryFormatted }}"
                            data-title="{{ $doc->title }}"
                        >
                            {{ $doc->title }} {{ $expiryFormatted ? '• Exp: ' . $doc->expiry_date->format('d/m/Y') : '' }}
                        </option>
                    @endforeach
                </select>
                <div class="form-help-text">
                    Jika dipilih, tanggal kedaluwarsa dokumen dapat otomatis mengisi tanggal jatuh tempo.
                </div>
                @error('document_id')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <!-- Tanggal Jatuh Tempo -->
            <div class="form-group">
                <label for="due_date" class="form-label">
                    Batas Jatuh Tempo <span style="color: var(--color-danger-text);">*</span>
                </label>
                <input 
                    type="date" 
                    id="due_date" 
                    name="due_date" 
                    value="{{ old('due_date', $prefillDocument && $prefillDocument->expiry_date ? $prefillDocument->expiry_date->format('Y-m-d') : '') }}" 
                    class="form-input @error('due_date') is-invalid @enderror" 
                    required
                >
                <div class="form-help-text">
                    Pilih tanggal target penyelesaian atau kedaluwarsa.
                </div>
                @error('due_date')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Keterangan / Deskripsi -->
        <div class="form-group">
            <label for="description" class="form-label">
                Keterangan Tambahan (Opsional)
            </label>
            <textarea 
                id="description" 
                name="description" 
                rows="4" 
                class="form-input @error('description') is-invalid @enderror" 
                placeholder="Tambahkan catatan khusus, nomor referensi, lokasi pengurusan, atau berkas pendukung yang perlu dibawa..."
                maxlength="1000"
            >{{ old('description') }}</textarea>
            @error('description')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <!-- Tombol Simpan & Batal -->
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--color-border-subtle);">
            <a href="{{ route('reminders.index') }}" class="btn btn-secondary">
                <span>Batal</span>
            </a>
            <button type="submit" class="btn btn-primary" id="btnSubmitReminder">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>Simpan Pengingat</span>
            </button>
        </div>
    </form>
</div>

<!-- Auto-fill Script saat Dokumen Dipilih -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const docSelect = document.getElementById('document_id');
        const titleInput = document.getElementById('title');
        const dueDateInput = document.getElementById('due_date');

        if (docSelect) {
            docSelect.addEventListener('change', function () {
                const selected = docSelect.options[docSelect.selectedIndex];
                const expiry = selected.getAttribute('data-expiry');
                const docTitle = selected.getAttribute('data-title');

                if (docTitle && (!titleInput.value || titleInput.value.trim() === '')) {
                    titleInput.value = 'Perpanjangan ' + docTitle;
                }

                if (expiry && (!dueDateInput.value || dueDateInput.value === '')) {
                    dueDateInput.value = expiry;
                }
            });
        }
    });
</script>
@endsection
