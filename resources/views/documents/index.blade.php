@extends('layouts.app', ['pageTitle' => 'Brankas Dokumen'])

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

<!-- Bar Pencarian dan Filter -->
<div class="filter-card">
    <form method="GET" action="{{ route('documents.index') }}" class="filter-form">
        <!-- Pencarian Judul -->
        <div class="filter-search-group">
            <input 
                type="text" 
                name="q" 
                value="{{ $currentSearch }}" 
                class="form-input" 
                placeholder="Cari berdasarkan judul dokumen..."
                aria-label="Cari judul dokumen"
            >
        </div>

        <!-- Filter Kategori -->
        <div>
            <select name="category_id" class="filter-select" aria-label="Filter kategori">
                <option value="">Semua Kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ $selectedCategory == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Filter Favorit -->
        <label class="filter-checkbox-label">
            <input 
                type="checkbox" 
                name="favorite" 
                value="1" 
                class="form-checkbox"
                {{ $isFavoriteFilter ? 'checked' : '' }}
                onchange="this.form.submit()"
            >
            <span>Hanya Favorit</span>
        </label>

        <!-- Aksi Filter -->
        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <span>Cari</span>
            </button>
            @if(!empty($currentSearch) || !empty($selectedCategory) || $isFavoriteFilter)
                <a href="{{ route('documents.index') }}" class="btn btn-secondary btn-sm" title="Reset filter">
                    <span>Reset</span>
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Daftar Dokumen -->
<div class="section-container">
    <div class="section-header">
        <div class="section-title-wrap">
            <div class="section-title">
                <span>Daftar Dokumen Tersimpan</span>
                <span class="badge badge-category">{{ $documents->total() }} Dokumen</span>
            </div>
            <p class="section-subtitle">Seluruh berkas disimpan aman di private local storage server</p>
        </div>
        <div class="section-actions">
            <a href="{{ route('documents.create') }}" class="btn btn-primary" id="btnTambahDokumen">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Tambah Dokumen</span>
            </a>
        </div>
    </div>

    @if($documents->isEmpty())
        <!-- Empty State Saat Belum Ada Dokumen / Hasil Filter Kosong -->
        <div class="empty-state-card">
            <div class="empty-state-icon-box" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
            </div>
            @if(!empty($currentSearch) || !empty($selectedCategory) || $isFavoriteFilter)
                <h2 class="empty-state-title">Dokumen Tidak Ditemukan</h2>
                <p class="empty-state-desc">
                    Tidak ada dokumen yang cocok dengan kata kunci atau filter yang Anda terapkan. Silakan coba kata kunci lain atau bersihkan filter pencarian.
                </p>
                <a href="{{ route('documents.index') }}" class="btn btn-secondary">
                    <span>Bersihkan Filter</span>
                </a>
            @else
                <h2 class="empty-state-title">Brankas Dokumen Masih Kosong</h2>
                <p class="empty-state-desc">
                    Anda belum menyimpan dokumen apapun. Mulai simpan identitas, polis, kartu garansi, atau sertifikat penting Anda sekarang dengan aman.
                </p>
                <a href="{{ route('documents.create') }}" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Unggah Dokumen Pertama</span>
                </a>
            @endif
        </div>
    @else
        <!-- Tabel Dokumen -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col" style="width: 40px;"></th>
                        <th scope="col">Nama Dokumen</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Format &amp; Ukuran</th>
                        <th scope="col">Masa Berlaku</th>
                        <th scope="col">Status</th>
                        <th scope="col" style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $doc)
                        <tr>
                            <!-- Bintang Favorit -->
                            <td style="padding-right: 0;">
                                <form method="POST" action="{{ route('documents.favorite', $doc) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn-icon-only" title="{{ $doc->is_favorite ? 'Hapus dari favorit' : 'Tandai sebagai favorit' }}">
                                        @if($doc->is_favorite)
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#FFAB00" stroke="#FFAB00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                            </svg>
                                        @else
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="star-inactive" aria-hidden="true">
                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                            </svg>
                                        @endif
                                    </button>
                                </form>
                            </td>

                            <!-- Judul & Nama Berkas -->
                            <td>
                                <div class="doc-name-cell">
                                    <div class="doc-type-icon" aria-hidden="true">
                                        @if($doc->isPdf())
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                            </svg>
                                        @else
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                <polyline points="21 15 16 10 5 21"></polyline>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="doc-meta">
                                        <a href="{{ route('documents.show', $doc) }}" class="doc-title" style="color: var(--color-primary); font-weight: 600;">
                                            {{ $doc->title }}
                                        </a>
                                        <span class="doc-subinfo">{{ $doc->original_filename }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Kategori -->
                            <td>
                                <span class="badge badge-category">
                                    {{ $doc->category ? $doc->category->name : 'Tanpa Kategori' }}
                                </span>
                            </td>

                            <!-- Format & Ukuran -->
                            <td>
                                <div class="doc-meta">
                                    <span style="font-weight: 500; font-size: 13px;">{{ strtoupper(pathinfo($doc->original_filename, PATHINFO_EXTENSION)) }}</span>
                                    <span class="doc-subinfo">{{ $doc->formatted_file_size }}</span>
                                </div>
                            </td>

                            <!-- Tanggal & Kedaluwarsa -->
                            <td>
                                <div class="doc-meta">
                                    <span style="font-size: 13px; font-weight: 500;">
                                        {{ $doc->expiry_date ? $doc->expiry_date->format('d M Y') : 'Seumur Hidup' }}
                                    </span>
                                    @if($doc->document_date)
                                        <span class="doc-subinfo">Terbit: {{ $doc->document_date->format('d M Y') }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Status -->
                            <td>
                                @if($doc->isExpired())
                                    <span class="badge badge-status-expired">
                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                                        Kedaluwarsa
                                    </span>
                                @elseif($doc->isExpiringSoon())
                                    <span class="badge badge-status-warning">
                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                                        Segera Berakhir
                                    </span>
                                @else
                                    <span class="badge badge-status-active">
                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                                        Aktif
                                    </span>
                                @endif
                            </td>

                            <!-- Tombol Aksi -->
                            <td style="text-align: right; white-space: nowrap;">
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <a href="{{ route('documents.show', $doc) }}" class="btn btn-secondary btn-sm" title="Lihat pratinjau dan detail">
                                        <span>Buka</span>
                                    </a>
                                    <a href="{{ route('documents.download', $doc) }}" class="btn btn-secondary btn-sm" title="Unduh berkas">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="7 10 12 15 17 10"></polyline>
                                            <line x1="12" y1="15" x2="12" y2="3"></line>
                                        </svg>
                                    </a>
                                    <a href="{{ route('documents.edit', $doc) }}" class="btn btn-secondary btn-sm" title="Edit metadata">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 20h9"></path>
                                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($documents->hasPages())
            <div class="pagination-wrapper">
                <div>
                    Menampilkan {{ $documents->firstItem() }} - {{ $documents->lastItem() }} dari {{ $documents->total() }} dokumen
                </div>
                <div>
                    {{ $documents->links() }}
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
