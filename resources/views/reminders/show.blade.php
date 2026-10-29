@extends('layouts.app', ['pageTitle' => 'Detail Pengingat'])

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
    <a href="{{ route('reminders.index') }}" class="btn btn-secondary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
        <span>Kembali ke Daftar Pengingat</span>
    </a>

    <div style="display: flex; align-items: center; gap: 8px;">
        <!-- Toggle Status -->
        <form method="POST" action="{{ route('reminders.toggle', $reminder) }}" style="display: inline;">
            @csrf
            @if($reminder->isCompleted())
                <button type="submit" class="btn btn-secondary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    <span>Aktifkan Kembali</span>
                </button>
            @else
                <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--color-success-text); border-color: var(--color-success-border);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>Tandai Selesai</span>
                </button>
            @endif
        </form>

        <!-- Edit -->
        <a href="{{ route('reminders.edit', $reminder) }}" class="btn btn-secondary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 20h9"></path>
                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
            </svg>
            <span>Edit</span>
        </a>

        <!-- Hapus -->
        <form method="POST" action="{{ route('reminders.destroy', $reminder) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengingat ini?');" style="display: inline;">
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

<!-- Layout Detail Pengingat (2 Kolom) -->
<div class="doc-detail-grid">
    <!-- Kolom Kiri: Detail & Deskripsi -->
    <div>
        <div class="section-container" style="padding: 28px; margin-bottom: 24px;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
                <div>
                    <span class="badge {{ $reminder->status_badge_class }}" style="margin-bottom: 8px;">
                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                        {{ $reminder->status_label }}
                    </span>
                    <h1 style="font-size: 22px; font-weight: 700; color: var(--color-primary); line-height: 1.3;">
                        {{ $reminder->title }}
                    </h1>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 12px; color: var(--color-text-tertiary); text-transform: uppercase; font-weight: 600;">
                        Jatuh Tempo
                    </div>
                    <div style="font-size: 16px; font-weight: 700; color: var(--color-primary);">
                        {{ $reminder->due_date->format('d M Y') }}
                    </div>
                </div>
            </div>

            <!-- Deskripsi / Keterangan -->
            <div style="background-color: var(--color-surface-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 16px 20px; margin-bottom: 24px;">
                <h3 style="font-size: 13px; font-weight: 600; color: var(--color-text-secondary); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 8px;">
                    Keterangan / Catatan
                </h3>
                <p style="font-size: 14px; color: var(--color-text); line-height: 1.6; white-space: pre-wrap;">{{ $reminder->description ?: 'Tidak ada keterangan tambahan untuk pengingat ini.' }}</p>
            </div>

            <!-- Dokumen Terkait -->
            <div>
                <h3 style="font-size: 14px; font-weight: 700; color: var(--color-primary); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <span>Dokumen Terkait</span>
                </h3>

                @if($reminder->document)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border: 1px solid var(--color-border); border-radius: var(--radius-md); background-color: var(--color-surface);">
                        <div>
                            <a href="{{ route('documents.show', $reminder->document_id) }}" style="font-weight: 600; color: var(--color-primary); font-size: 14px; display: block;">
                                {{ $reminder->document->title }}
                            </a>
                            <span style="font-size: 12px; color: var(--color-text-secondary);">
                                Berkas: {{ $reminder->document->original_filename }} • Kategori: {{ $reminder->document->category ? $reminder->document->category->name : 'Umum' }}
                            </span>
                        </div>
                        <a href="{{ route('documents.show', $reminder->document_id) }}" class="btn btn-secondary btn-sm">
                            <span>Buka Dokumen</span>
                        </a>
                    </div>
                @else
                    <div style="padding: 14px 18px; border: 1px dashed var(--color-border); border-radius: var(--radius-md); color: var(--color-text-secondary); font-size: 13px;">
                        Pengingat ini berdiri sendiri dan belum ditautkan ke dokumen tertentu. Anda dapat menautkannya melalui menu Edit.
                    </div>
                @endif
            </div>
        </div>

        <!-- Tabel Log Pengiriman Notifikasi Otomatis -->
        <div class="section-container" style="padding: 24px;">
            <div style="margin-bottom: 16px;">
                <h2 style="font-size: 16px; font-weight: 700; color: var(--color-primary); margin-bottom: 4px;">
                    Log Notifikasi Terjadwal
                </h2>
                <p style="font-size: 13px; color: var(--color-text-secondary);">
                    Catatan pengiriman pengingat otomatis oleh Laravel Scheduler untuk interval 30 hari, 7 hari, 1 hari, dan hari jatuh tempo.
                </p>
            </div>

            @if($notifications->isEmpty())
                <div style="padding: 20px; border: 1px dashed var(--color-border); border-radius: var(--radius-md); text-align: center; color: var(--color-text-secondary); font-size: 13px;">
                    Belum ada notifikasi yang dipicu untuk pengingat ini. Sistem scheduler Simpera akan mengecek batas waktu setiap hari pada pukul 08:00 pagi.
                </div>
            @else
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Tahap Pengingat</th>
                                <th scope="col">Kanal</th>
                                <th scope="col">Status Pengiriman</th>
                                <th scope="col">Waktu Pencatatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notifications as $notif)
                                <tr>
                                    <td>
                                        <span style="font-weight: 600; color: var(--color-primary);">
                                            {{ $notif->interval_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-category" style="text-transform: capitalize;">
                                            {{ $notif->channel }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($notif->status === 'sent')
                                            <span class="badge badge-status-active">Terkirim</span>
                                        @elseif($notif->status === 'failed')
                                            <span class="badge badge-status-expired">Gagal</span>
                                        @else
                                            <span class="badge badge-status-today">Tercatat (Simulasi)</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span style="font-size: 12.5px; color: var(--color-text-secondary);">
                                            {{ $notif->sent_at ? $notif->sent_at->format('d M Y H:i') : '-' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Kolom Kanan: Ringkasan Status & Parameter -->
    <div>
        <div class="doc-sidebar-card">
            <h2 class="doc-sidebar-title">Status &amp; Informasi Waktu</h2>
            <ul class="doc-info-list">
                <li class="doc-info-row">
                    <span class="doc-info-label">Status Saat Ini</span>
                    <span class="doc-info-value">
                        <span class="badge {{ $reminder->status_badge_class }}">
                            {{ $reminder->status_label }}
                        </span>
                    </span>
                </li>
                <li class="doc-info-row">
                    <span class="doc-info-label">Tanggal Jatuh Tempo</span>
                    <span class="doc-info-value">{{ $reminder->due_date->format('d M Y') }}</span>
                </li>
                <li class="doc-info-row">
                    <span class="doc-info-label">Sisa Waktu</span>
                    <span class="doc-info-value">
                        @if($reminder->isCompleted())
                            <span style="color: var(--color-success-text); font-weight: 600;">Selesai</span>
                        @elseif($reminder->isOverdue())
                            <span style="color: var(--color-danger-text); font-weight: 600;">Lewat {{ abs($reminder->days_remaining) }} hari</span>
                        @elseif($reminder->isDueToday())
                            <span style="color: var(--color-info-text); font-weight: 600;">Hari ini</span>
                        @else
                            <span style="color: var(--color-primary); font-weight: 600;">{{ $reminder->days_remaining }} hari lagi</span>
                        @endif
                    </span>
                </li>
                <li class="doc-info-row">
                    <span class="doc-info-label">Dibuat Pada</span>
                    <span class="doc-info-value">{{ $reminder->created_at->format('d M Y H:i') }}</span>
                </li>
                <li class="doc-info-row">
                    <span class="doc-info-label">Terakhir Diperbarui</span>
                    <span class="doc-info-value">{{ $reminder->updated_at->format('d M Y H:i') }}</span>
                </li>
            </ul>

            <div class="doc-actions-vertical">
                <a href="{{ route('reminders.edit', $reminder) }}" class="btn btn-secondary btn-block">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                    <span>Edit Pengingat</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
