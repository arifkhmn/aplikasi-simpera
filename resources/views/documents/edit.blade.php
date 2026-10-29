@extends('layouts.app', ['pageTitle' => 'Edit ' . $document->title])

@section('content')
<div class="vault-form-card">
    <div style="margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--color-border-subtle); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 class="page-title" style="font-size: 20px;">Edit Metadata Dokumen</h1>
            <p class="page-description">Perbarui informasi, kategori, dan tanggal masa berlaku berkas Anda.</p>
        </div>
        <a href="{{ route('documents.show', $document) }}" class="btn btn-secondary btn-sm">
            <span>Kembali ke Detail</span>
        </a>
    </div>

    @if ($errors->any())
        <div class="auth-alert-danger" role="alert" style="margin-bottom: 24px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <div>
                <strong>Terdapat kesalahan pada input Anda:</strong>
                <ul style="margin-top: 6px; padding-left: 18px; font-size: 13px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Informasi Berkas Fisik Terkait -->
    <div style="background-color: var(--color-surface-subtle); border: 1px solid var(--color-border-subtle); border-radius: var(--radius-md); padding: 14px 18px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="doc-type-icon" aria-hidden="true">
                @if($document->isPdf())
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                @else
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    </svg>
                @endif
            </div>
            <div>
                <div style="font-size: 13.5px; font-weight: 600; color: var(--color-primary);">{{ $document->original_filename }}</div>
                <div style="font-size: 12px; color: var(--color-text-tertiary);">{{ $document->formatted_file_size }} &bull; {{ $document->mime_type }} &bull; Tersimpan di Private Storage</div>
            </div>
        </div>
        <a href="{{ route('documents.download', $document) }}" class="btn btn-secondary btn-sm">
            <span>Unduh</span>
        </a>
    </div>

    <form method="POST" action="{{ route('documents.update', $document) }}">
        @csrf
        @method('PUT')

        <!-- Judul Dokumen -->
        <div class="form-group">
            <label for="title" class="form-label">Judul Dokumen <span style="color: var(--color-danger-text);">*</span></label>
            <input 
                type="text" 
                id="title" 
                name="title" 
                value="{{ old('title', $document->title) }}" 
                class="form-input @error('title') is-invalid @enderror" 
                required 
                autofocus
            >
            @error('title')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <!-- Kategori -->
        <div class="form-grid-2">
            <div class="form-group">
                <label for="category_id" class="form-label">Pilih Kategori</label>
                <select id="category_id" name="category_id" class="form-input">
                    <option value="">-- Pilih Kategori Tersedia --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $document->category_id) == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="new_category" class="form-label">Atau Tambah Kategori Baru</label>
                <input 
                    type="text" 
                    id="new_category" 
                    name="new_category" 
                    value="{{ old('new_category') }}" 
                    class="form-input" 
                    placeholder="Nama kategori baru..."
                >
            </div>
        </div>

        <!-- Tanggal Dokumen & Tanggal Kedaluwarsa -->
        <div class="form-grid-2">
            <div class="form-group">
                <label for="document_date" class="form-label">Tanggal Dokumen / Terbit</label>
                <input 
                    type="date" 
                    id="document_date" 
                    name="document_date" 
                    value="{{ old('document_date', $document->document_date ? $document->document_date->format('Y-m-d') : '') }}" 
                    class="form-input"
                >
            </div>
            <div class="form-group">
                <label for="expiry_date" class="form-label">Tanggal Kedaluwarsa</label>
                <input 
                    type="date" 
                    id="expiry_date" 
                    name="expiry_date" 
                    value="{{ old('expiry_date', $document->expiry_date ? $document->expiry_date->format('Y-m-d') : '') }}" 
                    class="form-input"
                >
                <div class="form-help-text">Kosongkan jika berlaku seumur hidup.</div>
            </div>
        </div>

        <!-- Catatan / Deskripsi -->
        <div class="form-group">
            <label for="description" class="form-label">Catatan Tambahan</label>
            <textarea 
                id="description" 
                name="description" 
                rows="3" 
                class="form-input"
            >{{ old('description', $document->description) }}</textarea>
        </div>

        <!-- Favorit -->
        <div class="form-checkbox-row">
            <input 
                type="checkbox" 
                id="is_favorite" 
                name="is_favorite" 
                value="1" 
                class="form-checkbox"
                {{ old('is_favorite', $document->is_favorite) ? 'checked' : '' }}
            >
            <label for="is_favorite" class="form-checkbox-label">Tandai sebagai dokumen favorit</label>
        </div>

        <!-- Tombol Aksi -->
        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>Simpan Perubahan</span>
            </button>
            <a href="{{ route('documents.show', $document) }}" class="btn btn-secondary">
                <span>Batal</span>
            </a>
        </div>
    </form>
</div>
@endsection
