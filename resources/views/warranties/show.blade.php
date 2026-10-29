@extends('layouts.app', ['pageTitle' => 'Detail Garansi Produk'])

@section('content')
<!-- Notifikasi Status Sukses -->
@if(session('status'))
    <div class="info-notice-banner" style="border-left-color: var(--color-success-text); margin-bottom: 20px;">
        <div class="info-notice-content">
            <div class="info-notice-icon" style="color: var(--color-success-text);" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div>
                <div class="info-notice-title" style="color: var(--color-success-text);">Berhasil</div>
                <p class="info-notice-text">{{ session('status') }}</p>
            </div>
        </div>
    </div>
@endif

<!-- Bar Navigasi Atas & Tombol Aksi -->
<div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;">
    <a href="{{ route('warranties.index') }}" class="btn btn-secondary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
        <span>Kembali ke Katalog Garansi</span>
    </a>

    <div style="display: flex; align-items: center; gap: 8px;">
        <a href="{{ route('warranties.edit', $warranty) }}" class="btn btn-secondary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 20h9"></path>
                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
            </svg>
            <span>Edit Garansi</span>
        </a>

        <form method="POST" action="{{ route('warranties.destroy', $warranty) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data garansi produk ini?');" style="display: inline;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                <span>Hapus</span>
            </button>
        </form>
    </div>
</div>

<!-- Layout Detail Garansi (2 Kolom) -->
<div class="doc-detail-grid">
    <!-- Kolom Kiri: Rincian Produk, Dokumen Nota & Pengingat -->
    <div>
        <div class="section-container" style="padding: 28px; margin-bottom: 24px;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
                <div>
                    <span class="badge {{ $warranty->status_badge_class }}" style="margin-bottom: 8px;">
                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                        {{ $warranty->status_label }}
                    </span>
                    <h1 style="font-size: 22px; font-weight: 700; color: var(--color-primary); line-height: 1.3;">
                        {{ $warranty->product_name }}
                    </h1>
                    <div style="font-size: 14px; color: var(--color-text-secondary); margin-top: 4px;">
                        Merek: <strong style="color: var(--color-primary);">{{ $warranty->brand }}</strong>
                        @if($warranty->serial_number)
                            • No. Seri: <code>{{ $warranty->serial_number }}</code>
                        @endif
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 12px; color: var(--color-text-tertiary); text-transform: uppercase; font-weight: 600;">
                        Masa Garansi Berakhir
                    </div>
                    <div style="font-size: 18px; font-weight: 700; color: var(--color-primary);">
                        {{ $warranty->warranty_end_date->format('d M Y') }}
                    </div>
                </div>
            </div>

            <!-- Ringkasan Pembelian Produk -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; background-color: var(--color-surface-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 18px; margin-bottom: 24px;">
                <div>
                    <div style="font-size: 12px; color: var(--color-text-tertiary); text-transform: uppercase; font-weight: 600;">
                        Tanggal Pembelian
                    </div>
                    <div style="font-size: 14px; font-weight: 600; color: var(--color-primary); margin-top: 2px;">
                        {{ $warranty->purchase_date->format('d M Y') }}
                    </div>
                </div>
                <div>
                    <div style="font-size: 12px; color: var(--color-text-tertiary); text-transform: uppercase; font-weight: 600;">
                        Harga Pembelian
                    </div>
                    <div style="font-size: 14px; font-weight: 600; color: var(--color-primary); margin-top: 2px;">
                        {{ $warranty->formatted_price }}
                    </div>
                </div>
                <div>
                    <div style="font-size: 12px; color: var(--color-text-tertiary); text-transform: uppercase; font-weight: 600;">
                        Tempat Pembelian / Toko
                    </div>
                    <div style="font-size: 14px; font-weight: 600; color: var(--color-primary); margin-top: 2px;">
                        {{ $warranty->store_name ?: '-' }}
                    </div>
                </div>
            </div>

            <!-- Catatan / Ketentuan Klaim -->
            <div style="margin-bottom: 24px;">
                <h3 style="font-size: 13.5px; font-weight: 600; color: var(--color-text-secondary); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 8px;">
                    Catatan Garansi &amp; Layanan Servis
                </h3>
                <div style="background-color: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 16px; font-size: 14px; color: var(--color-text); line-height: 1.6; white-space: pre-wrap;">{{ $warranty->notes ?: 'Tidak ada catatan atau instruksi khusus untuk garansi ini.' }}</div>
            </div>

            <!-- Nota Pembelian di Document Vault -->
            <div style="margin-bottom: 24px;">
                <h3 style="font-size: 14px; font-weight: 700; color: var(--color-primary); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <span>Nota / Bukti Pembelian Terkait</span>
                </h3>

                @if($warranty->document)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border: 1px solid var(--color-border); border-radius: var(--radius-md); background-color: var(--color-surface); gap: 16px; flex-wrap: wrap;">
                        <div>
                            <a href="{{ route('documents.show', $warranty->document_id) }}" style="font-weight: 600; color: var(--color-primary); font-size: 14px; display: block;">
                                {{ $warranty->document->title }}
                            </a>
                            <span style="font-size: 12px; color: var(--color-text-secondary); margin-top: 2px; display: block;">
                                Berkas: {{ $warranty->document->original_filename }} • Ukuran: {{ $warranty->document->formatted_file_size }}
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <a href="{{ route('documents.download', $warranty->document_id) }}" class="btn btn-secondary btn-sm">
                                <span>Unduh Nota</span>
                            </a>
                            <a href="{{ route('documents.show', $warranty->document_id) }}" class="btn btn-primary btn-sm">
                                <span>Buka di Vault</span>
                            </a>
                        </div>
                    </div>
                @else
                    <div style="padding: 16px 20px; border: 1px dashed var(--color-border); border-radius: var(--radius-md); color: var(--color-text-secondary); font-size: 13px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                        <span>Garansi ini belum ditautkan dengan berkas nota atau struk pembelian digital.</span>
                        <a href="{{ route('warranties.edit', $warranty) }}" class="btn btn-secondary btn-sm">
                            <span>Tautkan Nota</span>
                        </a>
                    </div>
                @endif
            </div>

            <!-- Integrasi Smart Reminders -->
            <div>
                <h3 style="font-size: 14px; font-weight: 700; color: var(--color-primary); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span>Pengingat Smart Reminders Terjadwal</span>
                </h3>

                @if($warranty->reminder)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border: 1px solid var(--color-border); border-radius: var(--radius-md); background-color: var(--color-surface); gap: 16px; flex-wrap: wrap;">
                        <div>
                            <div style="font-weight: 600; color: var(--color-primary); font-size: 14px;">
                                {{ $warranty->reminder->title }}
                            </div>
                            <span style="font-size: 12px; color: var(--color-text-secondary); margin-top: 2px; display: block;">
                                Jadwal jatuh tempo: {{ $warranty->reminder->due_date->format('d M Y') }} • Status: 
                                <span class="badge {{ $warranty->reminder->status_badge_class }}" style="padding: 2px 6px; font-size: 11px;">
                                    {{ $warranty->reminder->status_label }}
                                </span>
                            </span>
                        </div>
                        <a href="{{ route('reminders.show', $warranty->reminder_id) }}" class="btn btn-secondary btn-sm">
                            <span>Lihat Jadwal Notifikasi</span>
                        </a>
                    </div>
                @else
                    <div style="padding: 16px 20px; border: 1px dashed var(--color-border); border-radius: var(--radius-md); color: var(--color-text-secondary); font-size: 13px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                        <span>Belum ada jadwal pengingat otomatis di Smart Reminders untuk garansi ini.</span>
                        <a href="{{ route('warranties.edit', $warranty) }}" class="btn btn-secondary btn-sm">
                            <span>Aktifkan Pengingat</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Status & Ringkasan Waktu -->
    <div>
        <div class="doc-sidebar-card">
            <h2 class="doc-sidebar-title">Status Masa Garansi</h2>
            <ul class="doc-info-list">
                <li class="doc-info-row">
                    <span class="doc-info-label">Kondisi Saat Ini</span>
                    <span class="doc-info-value">
                        <span class="badge {{ $warranty->status_badge_class }}">
                            {{ $warranty->status_label }}
                        </span>
                    </span>
                </li>
                <li class="doc-info-row">
                    <span class="doc-info-label">Sisa Waktu Garansi</span>
                    <span class="doc-info-value">
                        @if($warranty->isExpired())
                            <span style="color: var(--color-danger-text); font-weight: 600;">Lewat {{ abs($warranty->days_remaining) }} hari</span>
                        @elseif($warranty->days_remaining === 0)
                            <span style="color: var(--color-warning-text); font-weight: 600;">Berakhir Hari Ini</span>
                        @else
                            <span style="color: var(--color-primary); font-weight: 600;">{{ $warranty->days_remaining }} hari lagi</span>
                        @endif
                    </span>
                </li>
                <li class="doc-info-row">
                    <span class="doc-info-label">Tanggal Mulai</span>
                    <span class="doc-info-value">{{ $warranty->purchase_date->format('d M Y') }}</span>
                </li>
                <li class="doc-info-row">
                    <span class="doc-info-label">Tanggal Berakhir</span>
                    <span class="doc-info-value">{{ $warranty->warranty_end_date->format('d M Y') }}</span>
                </li>
                <li class="doc-info-row">
                    <span class="doc-info-label">Didaftarkan Pada</span>
                    <span class="doc-info-value">{{ $warranty->created_at->format('d M Y H:i') }}</span>
                </li>
                <li class="doc-info-row">
                    <span class="doc-info-label">Terakhir Diperbarui</span>
                    <span class="doc-info-value">{{ $warranty->updated_at->format('d M Y H:i') }}</span>
                </li>
            </ul>

            <div class="doc-actions-vertical">
                <a href="{{ route('warranties.edit', $warranty) }}" class="btn btn-secondary btn-block">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                    <span>Edit Data Garansi</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
