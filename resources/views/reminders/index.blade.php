@extends('layouts.app', ['pageTitle' => 'Smart Reminders'])

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

<!-- Tab Navigasi Status Pengingat -->
<nav class="reminder-tabs-nav" aria-label="Filter Status Pengingat">
    <a href="{{ route('reminders.index', array_merge(request()->query(), ['status' => 'all'])) }}" class="reminder-tab-link {{ $currentStatus === 'all' ? 'active' : '' }}">
        <span>Semua</span>
        <span class="reminder-tab-count">{{ $counts['all'] }}</span>
    </a>
    <a href="{{ route('reminders.index', array_merge(request()->query(), ['status' => 'today'])) }}" class="reminder-tab-link {{ $currentStatus === 'today' ? 'active' : '' }}">
        <span>Hari Ini</span>
        <span class="reminder-tab-count">{{ $counts['today'] }}</span>
    </a>
    <a href="{{ route('reminders.index', array_merge(request()->query(), ['status' => 'overdue'])) }}" class="reminder-tab-link {{ $currentStatus === 'overdue' ? 'active' : '' }}">
        <span>Terlambat</span>
        <span class="reminder-tab-count">{{ $counts['overdue'] }}</span>
    </a>
    <a href="{{ route('reminders.index', array_merge(request()->query(), ['status' => 'upcoming'])) }}" class="reminder-tab-link {{ $currentStatus === 'upcoming' ? 'active' : '' }}">
        <span>Mendatang (30 Hari)</span>
        <span class="reminder-tab-count">{{ $counts['upcoming'] }}</span>
    </a>
    <a href="{{ route('reminders.index', array_merge(request()->query(), ['status' => 'completed'])) }}" class="reminder-tab-link {{ $currentStatus === 'completed' ? 'active' : '' }}">
        <span>Selesai</span>
        <span class="reminder-tab-count">{{ $counts['completed'] }}</span>
    </a>
</nav>

<!-- Bar Pencarian & Filter -->
<div class="filter-card">
    <form method="GET" action="{{ route('reminders.index') }}" class="filter-form">
        <input type="hidden" name="status" value="{{ $currentStatus }}">

        <!-- Input Pencarian Judul -->
        <div class="filter-search-group">
            <input 
                type="text" 
                name="q" 
                value="{{ $currentSearch }}" 
                class="form-input" 
                placeholder="Cari berdasarkan judul pengingat..."
                aria-label="Cari judul pengingat"
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
                <a href="{{ route('reminders.index') }}" class="btn btn-secondary btn-sm" title="Reset semua filter">
                    <span>Reset</span>
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Daftar Pengingat -->
<div class="section-container">
    <div class="section-header">
        <div class="section-title-wrap">
            <div class="section-title">
                <span>Jadwal Pengingat Terjadwal</span>
                <span class="badge badge-category">{{ $reminders->total() }} Pengingat</span>
            </div>
            <p class="section-subtitle">Notifikasi otomatis dirancang untuk 30, 7, dan 1 hari sebelum batas jatuh tempo</p>
        </div>
        <div class="section-actions">
            <a href="{{ route('reminders.create') }}" class="btn btn-primary" id="btnTambahPengingat">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Tambah Pengingat</span>
            </a>
        </div>
    </div>

    @if($reminders->isEmpty())
        <!-- Empty State Saat Data Kosong -->
        <div class="empty-state-card">
            <div class="empty-state-icon-box" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
            </div>
            @if(!empty($currentSearch) || $currentStatus !== 'all')
                <h2 class="empty-state-title">Pengingat Tidak Ditemukan</h2>
                <p class="empty-state-desc">
                    Tidak ada pengingat yang cocok dengan filter atau kata kunci pencarian Anda. Silakan bersihkan pencarian atau ubah tab status.
                </p>
                <a href="{{ route('reminders.index') }}" class="btn btn-secondary">
                    <span>Lihat Semua Pengingat</span>
                </a>
            @else
                <h2 class="empty-state-title">Belum Ada Pengingat</h2>
                <p class="empty-state-desc">
                    Jadwalkan pengingat masa berlaku dokumen seperti perpanjangan SIM, paspor, pembayaran pajak STNK, atau garansi barang agar Anda selalu siap tepat waktu.
                </p>
                <a href="{{ route('reminders.create') }}" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Buat Pengingat Pertama</span>
                </a>
            @endif
        </div>
    @else
        <!-- Tabel Pengingat -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col" style="width: 44px; text-align: center;">Status</th>
                        <th scope="col">Judul Pengingat &amp; Dokumen</th>
                        <th scope="col">Batas Jatuh Tempo</th>
                        <th scope="col">Kondisi Waktu</th>
                        <th scope="col" style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reminders as $reminder)
                        @php
                            $isCompleted = $reminder->isCompleted();
                        @endphp
                        <tr>
                            <!-- Tombol Cepat Tandai Selesai / Aktifkan Kembali -->
                            <td style="text-align: center;">
                                <form method="POST" action="{{ route('reminders.toggle', $reminder) }}">
                                    @csrf
                                    <button 
                                        type="submit" 
                                        class="btn-toggle-check {{ $isCompleted ? 'is-completed' : '' }}" 
                                        title="{{ $isCompleted ? 'Klik untuk aktifkan kembali pengingat' : 'Klik untuk tandai selesai' }}"
                                        aria-label="{{ $isCompleted ? 'Aktifkan kembali' : 'Tandai selesai' }}"
                                    >
                                        @if($isCompleted)
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        @else
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="9"></circle>
                                            </svg>
                                        @endif
                                    </button>
                                </form>
                            </td>

                            <!-- Judul & Dokumen Terkait -->
                            <td>
                                <div class="doc-name-cell">
                                    <div class="doc-meta">
                                        <a href="{{ route('reminders.show', $reminder) }}" class="doc-title {{ $isCompleted ? 'reminder-title-completed' : '' }}">
                                            {{ $reminder->title }}
                                        </a>
                                        @if($reminder->document)
                                            <span class="doc-subinfo" style="display: flex; align-items: center; gap: 4px; margin-top: 2px;">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                    <polyline points="14 2 14 8 20 8"></polyline>
                                                </svg>
                                                <a href="{{ route('documents.show', $reminder->document_id) }}" style="color: var(--color-text-secondary); text-decoration: underline;">
                                                    {{ $reminder->document->title }}
                                                </a>
                                            </span>
                                        @else
                                            <span class="doc-subinfo">Tanpa dokumen terkait</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Tanggal Jatuh Tempo -->
                            <td>
                                <div class="doc-meta">
                                    <span style="font-weight: 600; color: var(--color-primary);">
                                        {{ $reminder->due_date->format('d M Y') }}
                                    </span>
                                    <span class="doc-subinfo">
                                        {{ $reminder->due_date->translatedFormat('l') }}
                                    </span>
                                </div>
                            </td>

                            <!-- Label Kondisi Waktu & Status -->
                            <td>
                                <span class="badge {{ $reminder->status_badge_class }}">
                                    <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                                    {{ $reminder->status_label }}
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td style="text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <a href="{{ route('reminders.show', $reminder) }}" class="btn btn-secondary btn-sm" title="Lihat detail">
                                        <span>Buka</span>
                                    </a>
                                    <a href="{{ route('reminders.edit', $reminder) }}" class="btn btn-secondary btn-sm" title="Edit pengingat">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 20h9"></path>
                                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('reminders.destroy', $reminder) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengingat ini?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus pengingat">
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
        @if($reminders->hasPages())
            <div class="pagination-wrapper">
                <div>
                    Menampilkan {{ $reminders->firstItem() }} - {{ $reminders->lastItem() }} dari {{ $reminders->total() }} pengingat
                </div>
                <div>
                    {{ $reminders->links() }}
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
