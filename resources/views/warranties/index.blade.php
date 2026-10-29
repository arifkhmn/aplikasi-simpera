@extends('layouts.app', ['pageTitle' => 'Pelacak Garansi'])

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

<!-- Tab Navigasi Filter Status Garansi -->
<nav class="reminder-tabs-nav" aria-label="Filter Status Garansi">
    <a href="{{ route('warranties.index', array_merge(request()->query(), ['status' => 'all'])) }}" class="reminder-tab-link {{ $currentStatus === 'all' ? 'active' : '' }}">
        <span>Semua Garansi</span>
        <span class="reminder-tab-count">{{ $counts['all'] }}</span>
    </a>
    <a href="{{ route('warranties.index', array_merge(request()->query(), ['status' => 'active'])) }}" class="reminder-tab-link {{ $currentStatus === 'active' ? 'active' : '' }}">
        <span>Aktif (> 30 Hari)</span>
        <span class="reminder-tab-count">{{ $counts['active'] }}</span>
    </a>
    <a href="{{ route('warranties.index', array_merge(request()->query(), ['status' => 'expiring_soon'])) }}" class="reminder-tab-link {{ $currentStatus === 'expiring_soon' ? 'active' : '' }}">
        <span>Hampir Berakhir (&le; 30 Hari)</span>
        <span class="reminder-tab-count">{{ $counts['expiring_soon'] }}</span>
    </a>
    <a href="{{ route('warranties.index', array_merge(request()->query(), ['status' => 'expired'])) }}" class="reminder-tab-link {{ $currentStatus === 'expired' ? 'active' : '' }}">
        <span>Kedaluwarsa</span>
        <span class="reminder-tab-count">{{ $counts['expired'] }}</span>
    </a>
</nav>

<!-- Bar Pencarian & Filter -->
<div class="filter-card">
    <form method="GET" action="{{ route('warranties.index') }}" class="filter-form">
        <input type="hidden" name="status" value="{{ $currentStatus }}">

        <!-- Input Pencarian -->
        <div class="filter-search-group">
            <input 
                type="text" 
                name="q" 
                value="{{ $currentSearch }}" 
                class="form-input" 
                placeholder="Cari berdasarkan nama produk, merek, toko, atau nomor seri..."
                aria-label="Cari produk atau merek garansi"
            >
        </div>

        <!-- Tombol Aksi Filter -->
        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <span>Cari</span>
            </button>
            @if(!empty($currentSearch) || $currentStatus !== 'all')
                <a href="{{ route('warranties.index') }}" class="btn btn-secondary btn-sm" title="Reset filter">
                    <span>Reset</span>
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Daftar Garansi -->
<div class="section-container">
    <div class="section-header">
        <div class="section-title-wrap">
            <div class="section-title">
                <span>Pelacak Garansi Produk</span>
                <span class="badge badge-category">{{ $warranties->total() }} Terdaftar</span>
            </div>
            <p class="section-subtitle">Pantau masa perlindungan purnajual, struk pembelian, dan batas klaim garansi Anda</p>
        </div>
        <div class="section-actions">
            <a href="{{ route('warranties.create') }}" class="btn btn-primary" id="btnTambahGaransi">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Tambah Garansi</span>
            </a>
        </div>
    </div>

    @if($warranties->isEmpty())
        <!-- Empty State Saat Data Kosong -->
        <div class="empty-state-card">
            <div class="empty-state-icon-box" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
            </div>
            @if(!empty($currentSearch) || $currentStatus !== 'all')
                <h2 class="empty-state-title">Garansi Tidak Ditemukan</h2>
                <p class="empty-state-desc">
                    Tidak ada barang atau kartu garansi yang cocok dengan filter atau kata kunci pencarian Anda. Silakan coba kata kunci lain.
                </p>
                <a href="{{ route('warranties.index') }}" class="btn btn-secondary">
                    <span>Lihat Semua Garansi</span>
                </a>
            @else
                <h2 class="empty-state-title">Belum Ada Data Garansi</h2>
                <p class="empty-state-desc">
                    Daftarkan barang berharga Anda seperti gawai, laptop, peralatan rumah tangga, dan kendaraan. Tautkan nota pembelian dari Brankas Dokumen agar klaim servis selalu mudah.
                </p>
                <a href="{{ route('warranties.create') }}" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Daftarkan Garansi Pertama</span>
                </a>
            @endif
        </div>
    @else
        <!-- Tabel Garansi -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Nama Produk &amp; Merek</th>
                        <th scope="col">Nota / Invoice</th>
                        <th scope="col">Tanggal Beli &amp; Kedaluwarsa</th>
                        <th scope="col">Status Garansi</th>
                        <th scope="col" style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($warranties as $warranty)
                        <tr>
                            <!-- Produk & Merek -->
                            <td>
                                <div class="doc-name-cell">
                                    <div class="doc-type-icon" aria-hidden="true">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                        </svg>
                                    </div>
                                    <div class="doc-meta">
                                        <a href="{{ route('warranties.show', $warranty) }}" class="doc-title">
                                            {{ $warranty->product_name }}
                                        </a>
                                        <span class="doc-subinfo">
                                            Merek: <strong>{{ $warranty->brand }}</strong>
                                            @if($warranty->serial_number)
                                                • S/N: {{ $warranty->serial_number }}
                                            @endif
                                            @if($warranty->store_name)
                                                • Toko: {{ $warranty->store_name }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Nota Pembelian -->
                            <td>
                                @if($warranty->document)
                                    <a href="{{ route('documents.show', $warranty->document_id) }}" class="badge badge-category" style="display: inline-flex; align-items: center; gap: 4px; text-decoration: none;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                        </svg>
                                        <span>{{ Str::limit($warranty->document->title, 20) }}</span>
                                    </a>
                                @else
                                    <span class="badge badge-category" style="color: var(--color-text-tertiary);">Tanpa Nota</span>
                                @endif
                            </td>

                            <!-- Tanggal Beli & Berakhir -->
                            <td>
                                <div class="doc-meta">
                                    <span style="font-weight: 600; color: var(--color-primary);">
                                        s/d {{ $warranty->warranty_end_date->format('d M Y') }}
                                    </span>
                                    <span class="doc-subinfo">
                                        Beli: {{ $warranty->purchase_date->format('d M Y') }}
                                    </span>
                                </div>
                            </td>

                            <!-- Status Garansi -->
                            <td>
                                <span class="badge {{ $warranty->status_badge_class }}">
                                    <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                                    {{ $warranty->status_label }}
                                </span>
                                <div class="doc-subinfo" style="margin-top: 4px;">
                                    @if($warranty->isExpired())
                                        Lewat {{ abs($warranty->days_remaining) }} hari
                                    @elseif($warranty->days_remaining === 0)
                                        Berakhir hari ini
                                    @else
                                        Sisa {{ $warranty->days_remaining }} hari
                                    @endif
                                </div>
                            </td>

                            <!-- Aksi -->
                            <td style="text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <a href="{{ route('warranties.show', $warranty) }}" class="btn btn-secondary btn-sm" title="Buka rincian">
                                        <span>Buka</span>
                                    </a>
                                    <a href="{{ route('warranties.edit', $warranty) }}" class="btn btn-secondary btn-sm" title="Edit garansi">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 20h9"></path>
                                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('warranties.destroy', $warranty) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data garansi ini?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus garansi">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($warranties->hasPages())
            <div class="pagination-wrapper">
                <div>
                    Menampilkan {{ $warranties->firstItem() }} - {{ $warranties->lastItem() }} dari {{ $warranties->total() }} barang garansi
                </div>
                <div>
                    {{ $warranties->links() }}
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
