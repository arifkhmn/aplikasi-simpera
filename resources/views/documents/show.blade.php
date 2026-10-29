@extends('layouts.app', ['pageTitle' => $document->title])

@section('content')
<!-- Notifikasi Status -->
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

<div class="doc-detail-grid">
    <!-- Area Pratinjau Berkas (Kiri) -->
    <div class="doc-preview-wrapper">
        <div class="doc-preview-header">
            <div class="doc-preview-title">
                @if($document->isPdf())
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <span>Pratinjau PDF Dokumen</span>
                @else
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <span>Pratinjau Gambar Dokumen</span>
                @endif
            </div>
            <a href="{{ route('documents.download', $document) }}" class="btn btn-secondary btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                <span>Unduh Berkas</span>
            </a>
        </div>

        <div class="doc-preview-body">
            @if($document->isPdf())
                <iframe 
                    src="{{ route('documents.preview', $document) }}" 
                    class="doc-preview-iframe" 
                    title="Pratinjau PDF {{ $document->title }}"
                ></iframe>
            @elseif($document->isImage())
                <img 
                    src="{{ route('documents.preview', $document) }}" 
                    alt="Pratinjau {{ $document->title }}" 
                    class="doc-preview-img"
                >
            @else
                <div style="padding: 48px; text-align: center; color: var(--color-surface);">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 12px;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <p style="font-size: 14px;">Pratinjau langsung tidak tersedia untuk format berkas ini.</p>
                    <a href="{{ route('documents.download', $document) }}" class="btn btn-primary" style="margin-top: 16px;">
                        <span>Unduh Berkas untuk Membuka</span>
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Informasi Metadata & Aksi (Kanan) -->
    <div>
        <div class="doc-sidebar-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                <h2 style="font-size: 18px; font-weight: 700; color: var(--color-primary); line-height: 1.3;">
                    {{ $document->title }}
                </h2>
                <form method="POST" action="{{ route('documents.favorite', $document) }}">
                    @csrf
                    <button type="submit" class="btn-icon-only" title="{{ $document->is_favorite ? 'Hapus dari favorit' : 'Tandai favorit' }}">
                        @if($document->is_favorite)
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="#FFAB00" stroke="#FFAB00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                        @else
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="star-inactive" aria-hidden="true">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                        @endif
                    </button>
                </form>
            </div>

            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
                <span class="badge badge-category">
                    {{ $document->category ? $document->category->name : 'Tanpa Kategori' }}
                </span>
                @if($document->isExpired())
                    <span class="badge badge-status-expired">Kedaluwarsa</span>
                @elseif($document->isExpiringSoon())
                    <span class="badge badge-status-warning">Segera Berakhir</span>
                @else
                    <span class="badge badge-status-active">Aktif</span>
                @endif
            </div>

            <ul class="doc-info-list">
                <li class="doc-info-row">
                    <span class="doc-info-label">Nama Berkas:</span>
                    <span class="doc-info-value">{{ $document->original_filename }}</span>
                </li>
                <li class="doc-info-row">
                    <span class="doc-info-label">Ukuran Berkas:</span>
                    <span class="doc-info-value">{{ $document->formatted_file_size }}</span>
                </li>
                <li class="doc-info-row">
                    <span class="doc-info-label">Tipe Berkas:</span>
                    <span class="doc-info-value">{{ $document->mime_type }}</span>
                </li>
                <li class="doc-info-row">
                    <span class="doc-info-label">Tanggal Terbit:</span>
                    <span class="doc-info-value">{{ $document->document_date ? $document->document_date->format('d M Y') : '-' }}</span>
                </li>
                <li class="doc-info-row">
                    <span class="doc-info-label">Masa Berlaku:</span>
                    <span class="doc-info-value">{{ $document->expiry_date ? $document->expiry_date->format('d M Y') : 'Seumur Hidup' }}</span>
                </li>
                <li class="doc-info-row">
                    <span class="doc-info-label">Diunggah Pada:</span>
                    <span class="doc-info-value">{{ $document->created_at->format('d M Y, H:i') }}</span>
                </li>
            </ul>

            @if($document->description)
                <div style="margin-bottom: 20px;">
                    <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--color-text-secondary); margin-bottom: 6px;">
                        Catatan Dokumen:
                    </div>
                    <div style="font-size: 13.5px; color: var(--color-text); line-height: 1.5; background-color: var(--color-surface-subtle); padding: 10px 12px; border-radius: var(--radius-sm); border: 1px solid var(--color-border-subtle);">
                        {{ $document->description }}
                    </div>
                </div>
            @endif

            <!-- Tombol Aksi -->
            <div class="doc-actions-vertical">
                <a href="{{ route('documents.download', $document) }}" class="btn btn-primary" style="width: 100%;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Unduh Berkas</span>
                </a>

                <a href="{{ route('documents.edit', $document) }}" class="btn btn-secondary" style="width: 100%;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                    <span>Edit Metadata</span>
                </a>

                <a href="{{ route('reminders.create', ['document_id' => $document->id]) }}" class="btn btn-secondary" style="width: 100%;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span>Pasang Pengingat</span>
                </a>

                <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumen ini beserta berkas fisiknya dari brankas secara permanen?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" style="width: 100%;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        <span>Hapus Dokumen</span>
                    </button>
                </form>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="{{ route('documents.index') }}" class="btn btn-secondary btn-sm">
                <span>&larr; Kembali ke Daftar Dokumen</span>
            </a>
        </div>
    </div>
</div>
@endsection
