@extends('layouts.app', ['pageTitle' => 'Edit Garansi Produk'])

@section('content')
<div style="max-width: 760px; margin: 0 auto 24px auto;">
    <a href="{{ route('warranties.show', $warranty) }}" class="btn btn-secondary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
        <span>Kembali ke Detail Garansi</span>
    </a>
</div>

<div class="vault-form-card">
    <div style="margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--color-border-subtle);">
        <h1 style="font-size: 20px; font-weight: 700; color: var(--color-primary); margin-bottom: 6px;">
            Perbarui Data Garansi
        </h1>
        <p style="font-size: 13.5px; color: var(--color-text-secondary); line-height: 1.4;">
            Perbarui informasi produk, masa berlaku garansi, atau dokumen bukti nota pembelian.
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

    <form method="POST" action="{{ route('warranties.update', $warranty) }}">
        @csrf
        @method('PUT')

        <!-- Nama Produk & Merek (Grid 2 Kolom) -->
        <div class="form-grid-2">
            <div class="form-group">
                <label for="product_name" class="form-label">
                    Nama Produk / Barang <span style="color: var(--color-danger-text);">*</span>
                </label>
                <input 
                    type="text" 
                    id="product_name" 
                    name="product_name" 
                    value="{{ old('product_name', $warranty->product_name) }}" 
                    class="form-input @error('product_name') is-invalid @enderror" 
                    required
                    maxlength="255"
                >
                @error('product_name')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="brand" class="form-label">
                    Merek / Brand <span style="color: var(--color-danger-text);">*</span>
                </label>
                <input 
                    type="text" 
                    id="brand" 
                    name="brand" 
                    value="{{ old('brand', $warranty->brand) }}" 
                    class="form-input @error('brand') is-invalid @enderror" 
                    required
                    maxlength="255"
                >
                @error('brand')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Nomor Seri & Nama Toko (Grid 2 Kolom) -->
        <div class="form-grid-2">
            <div class="form-group">
                <label for="serial_number" class="form-label">
                    Nomor Seri (Serial Number / IMEI)
                </label>
                <input 
                    type="text" 
                    id="serial_number" 
                    name="serial_number" 
                    value="{{ old('serial_number', $warranty->serial_number) }}" 
                    class="form-input @error('serial_number') is-invalid @enderror" 
                    maxlength="255"
                >
                @error('serial_number')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="store_name" class="form-label">
                    Toko / Tempat Pembelian
                </label>
                <input 
                    type="text" 
                    id="store_name" 
                    name="store_name" 
                    value="{{ old('store_name', $warranty->store_name) }}" 
                    class="form-input @error('store_name') is-invalid @enderror" 
                    maxlength="255"
                >
                @error('store_name')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Tanggal Pembelian & Tanggal Akhir Garansi (Grid 2 Kolom) -->
        <div class="form-grid-2">
            <div class="form-group">
                <label for="purchase_date" class="form-label">
                    Tanggal Pembelian <span style="color: var(--color-danger-text);">*</span>
                </label>
                <input 
                    type="date" 
                    id="purchase_date" 
                    name="purchase_date" 
                    value="{{ old('purchase_date', $warranty->purchase_date->format('Y-m-d')) }}" 
                    class="form-input @error('purchase_date') is-invalid @enderror" 
                    required
                >
                @error('purchase_date')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="warranty_end_date" class="form-label">
                    Tanggal Garansi Berakhir <span style="color: var(--color-danger-text);">*</span>
                </label>
                <input 
                    type="date" 
                    id="warranty_end_date" 
                    name="warranty_end_date" 
                    value="{{ old('warranty_end_date', $warranty->warranty_end_date->format('Y-m-d')) }}" 
                    class="form-input @error('warranty_end_date') is-invalid @enderror" 
                    required
                >
                @error('warranty_end_date')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Harga Pembelian & Dokumen Nota di Brankas (Grid 2 Kolom) -->
        <div class="form-grid-2">
            <div class="form-group">
                <label for="purchase_price" class="form-label">
                    Harga Pembelian (Rupiah)
                </label>
                <input 
                    type="number" 
                    id="purchase_price" 
                    name="purchase_price" 
                    value="{{ old('purchase_price', $warranty->purchase_price) }}" 
                    class="form-input @error('purchase_price') is-invalid @enderror" 
                    step="1000"
                    min="0"
                >
                @error('purchase_price')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="document_id" class="form-label">
                    Nota / Bukti Pembelian di Vault
                </label>
                <select 
                    id="document_id" 
                    name="document_id" 
                    class="form-input @error('document_id') is-invalid @enderror"
                >
                    <option value="">-- Tanpa Nota / Unggah Nanti --</option>
                    @foreach($documents as $doc)
                        <option 
                            value="{{ $doc->id }}" 
                            {{ old('document_id', $warranty->document_id) == $doc->id ? 'selected' : '' }}
                        >
                            {{ $doc->title }} ({{ $doc->original_filename }})
                        </option>
                    @endforeach
                </select>
                @error('document_id')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Status Integrasi Smart Reminders -->
        <div style="background-color: var(--color-surface-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 14px 18px; margin-bottom: 20px;">
            @if($warranty->reminder_id)
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="badge badge-status-active">Pengingat Terhubung</span>
                    <span style="font-size: 13px; color: var(--color-text-secondary);">
                        Jadwal Smart Reminders otomatis tersinkronisasi dengan batas tanggal berakhir baru.
                    </span>
                </div>
            @else
                <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                    <input 
                        type="checkbox" 
                        name="create_reminder" 
                        value="1" 
                        class="form-checkbox" 
                        style="margin-top: 3px;"
                        {{ old('create_reminder') ? 'checked' : '' }}
                    >
                    <div>
                        <span style="font-size: 13.5px; font-weight: 600; color: var(--color-primary); display: block;">
                            Buat Pengingat Baru di Smart Reminders
                        </span>
                        <span style="font-size: 12.5px; color: var(--color-text-secondary); line-height: 1.4; display: block; margin-top: 2px;">
                            Aktifkan untuk memantau batas tanggal berakhir garansi ini melalui jadwal notifikasi berkala.
                        </span>
                    </div>
                </label>
            @endif
        </div>

        <!-- Catatan / Keterangan -->
        <div class="form-group">
            <label for="notes" class="form-label">
                Catatan Tambahan (Ketentuan Garansi, Kontak Pusat Servis)
            </label>
            <textarea 
                id="notes" 
                name="notes" 
                rows="4" 
                class="form-input @error('notes') is-invalid @enderror" 
                maxlength="2000"
            >{{ old('notes', $warranty->notes) }}</textarea>
            @error('notes')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <!-- Tombol Aksi Simpan & Batal -->
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--color-border-subtle);">
            <a href="{{ route('warranties.show', $warranty) }}" class="btn btn-secondary">
                <span>Batal</span>
            </a>
            <button type="submit" class="btn btn-primary" id="btnUpdateWarranty">
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
