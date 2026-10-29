@extends('layouts.app', ['pageTitle' => 'Edit Pengingat'])

@section('content')
<div style="max-width: 760px; margin: 0 auto 24px auto;">
    <a href="{{ route('reminders.show', $reminder) }}" class="btn btn-secondary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
        <span>Kembali ke Detail Pengingat</span>
    </a>
</div>

<div class="vault-form-card">
    <div style="margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--color-border-subtle);">
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-primary); margin-bottom: 6px;">
            Perbarui Pengingat
        </h1>
        <p style="font-size: 13.5px; color: var(--color-text-secondary); line-height: 1.4;">
            Sesuaikan jadwal, dokumen rujukan, atau status penyelesaian pengingat ini.
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

    <form method="POST" action="{{ route('reminders.update', $reminder) }}">
        @csrf
        @method('PUT')

        <!-- Judul Pengingat -->
        <div class="form-group">
            <label for="title" class="form-label">
                Judul Pengingat <span style="color: var(--color-danger-text);">*</span>
            </label>
            <input 
                type="text" 
                id="title" 
                name="title" 
                value="{{ old('title', $reminder->title) }}" 
                class="form-input @error('title') is-invalid @enderror" 
                required
                maxlength="255"
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
                    <option value="">-- Tanpa Dokumen Terkait --</option>
                    @foreach($documents as $doc)
                        <option 
                            value="{{ $doc->id }}" 
                            {{ old('document_id', $reminder->document_id) == $doc->id ? 'selected' : '' }}
                        >
                            {{ $doc->title }} {{ $doc->expiry_date ? '• Exp: ' . $doc->expiry_date->format('d/m/Y') : '' }}
                        </option>
                    @endforeach
                </select>
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
                    value="{{ old('due_date', $reminder->due_date ? $reminder->due_date->format('Y-m-d') : '') }}" 
                    class="form-input @error('due_date') is-invalid @enderror" 
                    required
                >
                @error('due_date')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Status Pengingat -->
        <div class="form-group">
            <label for="status" class="form-label">
                Status Pengingat <span style="color: var(--color-danger-text);">*</span>
            </label>
            <select 
                id="status" 
                name="status" 
                class="form-input @error('status') is-invalid @enderror"
                required
            >
                <option value="pending" {{ old('status', $reminder->status) === 'pending' ? 'selected' : '' }}>
                    Aktif / Menunggu (Pending)
                </option>
                <option value="completed" {{ old('status', $reminder->status) === 'completed' ? 'selected' : '' }}>
                    Selesai (Completed)
                </option>
            </select>
            <div class="form-help-text">
                Pilih "Selesai" jika pengurusan atau perpanjangan dokumen ini telah berhasil dilakukan.
            </div>
            @error('status')
                <div class="form-error">{{ $message }}</div>
            @enderror
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
                maxlength="1000"
            >{{ old('description', $reminder->description) }}</textarea>
            @error('description')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <!-- Tombol Simpan & Batal -->
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--color-border-subtle);">
            <a href="{{ route('reminders.show', $reminder) }}" class="btn btn-secondary">
                <span>Batal</span>
            </a>
            <button type="submit" class="btn btn-primary" id="btnUpdateReminder">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>Simpan Perubahan</span>
            </button>
        </div>
    </form>
</div>
@endsection
