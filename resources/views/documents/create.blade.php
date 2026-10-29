@extends('layouts.app', ['pageTitle' => 'Tambah Dokumen'])

@section('content')
<div class="vault-form-card">
    <div style="margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--color-border-subtle); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 class="page-title" style="font-size: 20px;">Tambah Dokumen ke Brankas</h1>
            <p class="page-description">Unggah berkas digital Anda secara aman ke penyimpanan privat terenkripsi.</p>
        </div>
        <a href="{{ route('documents.index') }}" class="btn btn-secondary btn-sm">
            <span>Kembali ke Daftar</span>
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

    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
        @csrf

        <!-- Judul Dokumen -->
        <div class="form-group">
            <label for="title" class="form-label">Judul Dokumen <span style="color: var(--color-danger-text);">*</span></label>
            <input 
                type="text" 
                id="title" 
                name="title" 
                value="{{ old('title') }}" 
                class="form-input @error('title') is-invalid @enderror" 
                placeholder="Contoh: KTP Digital, Polis Asuransi Kesehatan, Kartu Garansi TV" 
                required 
                autofocus
            >
            @error('title')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <!-- Unggah Berkas -->
        <div class="form-group">
            <label for="file" class="form-label">Berkas Dokumen <span style="color: var(--color-danger-text);">*</span></label>
            <div class="file-dropzone-box" onclick="document.getElementById('file').click();">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 10px; color: var(--color-primary);" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <div style="font-size: 14px; font-weight: 600; color: var(--color-primary);">Pilih atau seret berkas ke sini</div>
                <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;" id="file-chosen-text">
                    Mendukung format: PDF, JPG, JPEG, PNG (Maksimal 10 MB)
                </div>
            </div>
            <input 
                type="file" 
                id="file" 
                name="file" 
                accept=".pdf,.jpg,.jpeg,.png"
                style="display: none;" 
                required
                onchange="if(this.files[0]) { document.getElementById('file-chosen-text').innerText = 'Berkas terpilih: ' + this.files[0].name + ' (' + (this.files[0].size/1048576).toFixed(2) + ' MB)'; }"
            >
            @error('file')
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
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="new_category" class="form-label">Atau Buat Kategori Baru</label>
                <input 
                    type="text" 
                    id="new_category" 
                    name="new_category" 
                    value="{{ old('new_category') }}" 
                    class="form-input" 
                    placeholder="Misal: Kendaraan, Asuransi, Pajak"
                >
            </div>
        </div>

        <!-- Tanggal Terbit & Tanggal Kedaluwarsa -->
        <div class="form-grid-2">
            <div class="form-group">
                <label for="document_date" class="form-label">Tanggal Dokumen / Terbit</label>
                <input 
                    type="date" 
                    id="document_date" 
                    name="document_date" 
                    value="{{ old('document_date') }}" 
                    class="form-input"
                >
            </div>
            <div class="form-group">
                <label for="expiry_date" class="form-label">Tanggal Kedaluwarsa (Jika ada)</label>
                <input 
                    type="date" 
                    id="expiry_date" 
                    name="expiry_date" 
                    value="{{ old('expiry_date') }}" 
                    class="form-input"
                >
                <div class="form-help-text">Kosongkan jika berlaku seumur hidup.</div>
            </div>
        </div>

        <!-- Catatan / Deskripsi -->
        <div class="form-group">
            <label for="description" class="form-label">Catatan Tambahan (Opsional)</label>
            <textarea 
                id="description" 
                name="description" 
                rows="3" 
                class="form-input" 
                placeholder="Tambahkan catatan nomor referensi, lokasi fisik dokumen asli, atau info penting lainnya..."
            >{{ old('description') }}</textarea>
        </div>

        <!-- Favorit -->
        <div class="form-checkbox-row">
            <input 
                type="checkbox" 
                id="is_favorite" 
                name="is_favorite" 
                value="1" 
                class="form-checkbox"
                {{ old('is_favorite') ? 'checked' : '' }}
            >
            <label for="is_favorite" class="form-checkbox-label">Tandai sebagai dokumen favorit</label>
        </div>

        <!-- Tombol Simpan -->
        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>Simpan Dokumen</span>
            </button>
            <a href="{{ route('documents.index') }}" class="btn btn-secondary">
                <span>Batal</span>
            </a>
        </div>
    </form>
</div>
@endsection
