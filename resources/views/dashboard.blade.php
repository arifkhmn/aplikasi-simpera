@extends('layouts.app')

@section('content')
<!-- Banner Status Autentikasi & Pengalih Tampilan Demo -->
<section class="info-notice-banner" aria-label="Informasi Status Dashboard">
    <div class="info-notice-content">
        <div class="info-notice-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>
        <div>
            @if($isDemoView)
                <div class="info-notice-title">Pratinjau Desain: Mode Data Contoh (Demo)</div>
                <p class="info-notice-text">
                    Anda sedang melihat data contoh berlabel <strong>[Demo]</strong> untuk memvalidasi tata letak tabel dan status masa berlaku dokumen. Data ini <strong>bukan</strong> dokumen asli dari akun <strong>{{ Auth::user()->email }}</strong>.
                </p>
            @else
                <div class="info-notice-title">Selamat Datang, {{ Auth::user()->name }}!</div>
                <p class="info-notice-text">
                    Brankas dokumen digital Anda aktif dan terproteksi. Seluruh berkas disimpan secara privat dan hanya dapat diakses oleh Anda.
                </p>
            @endif
        </div>
    </div>
    <div class="view-state-switcher" role="group" aria-label="Pengalih Pratinjau Tampilan">
        <a href="{{ route('dashboard') }}" class="switcher-btn {{ !$isDemoView ? 'active' : '' }}">
            Data Akun Asli
        </a>
        <a href="{{ route('dashboard', ['view' => 'demo']) }}" class="switcher-btn {{ $isDemoView ? 'active' : '' }}">
            Pratinjau Demo
        </a>
    </div>
</section>

<!-- Kartu Ringkasan (Summary Cards) -->
<section class="summary-cards-grid" aria-label="Ringkasan Statistik Dokumen">
    <!-- Kartu Total Dokumen -->
    <article class="summary-card">
        <div class="card-top">
            <span class="card-label">Total Dokumen</span>
            <div class="card-icon-wrapper" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
            </div>
        </div>
        <div class="card-value">{{ $metrics['total_documents'] }}</div>
        <div class="card-caption">
            <span>Berkas tersimpan di brankas privat Anda</span>
        </div>
    </article>

    <!-- Kartu Pengingat Mendatang -->
    <article class="summary-card">
        <div class="card-top">
            <span class="card-label">Pengingat Mendatang</span>
            <div class="card-icon-wrapper" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
            </div>
        </div>
        <div class="card-value">{{ $metrics['upcoming_reminders'] }}</div>
        <div class="card-caption">
            <span>Kedaluwarsa dalam 30 hari ke depan</span>
        </div>
    </article>

    <!-- Kartu Garansi Aktif -->
    <article class="summary-card">
        <div class="card-top">
            <span class="card-label">Garansi Aktif</span>
            <div class="card-icon-wrapper" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
            </div>
        </div>
        <div class="card-value">{{ $metrics['active_warranties'] }}</div>
        <div class="card-caption">
            @if(isset($warrantyCounts))
                <span>{{ $warrantyCounts['expiring_soon'] }} hampir berakhir • {{ $warrantyCounts['expired'] }} kedaluwarsa</span>
            @else
                <span>Peralatan &amp; barang dengan garansi aktif</span>
            @endif
        </div>
    </article>
</section>

<!-- Bagian Dokumen Terbaru -->
<section class="section-container" aria-label="Daftar Dokumen Terbaru">
    <div class="section-header">
        <div class="section-title-wrap">
            <div class="section-title">
                <span>Dokumen Terbaru</span>
                @if($isDemoView)
                    <span class="badge badge-demo">Data Contoh Demo</span>
                @endif
            </div>
            <p class="section-subtitle">Daftar arsip dokumen yang baru saja dicatat atau diperbarui</p>
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

    @if($isEmptyState || count($documents) === 0)
        <!-- Empty State Informatif Saat Belum Ada Dokumen -->
        <div class="empty-state-card" id="emptyStateBox">
            <div class="empty-state-icon-box" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                    <polyline points="13 2 13 9 20 9"></polyline>
                </svg>
            </div>
            <h2 class="empty-state-title">Belum Ada Dokumen Tersimpan</h2>
            <p class="empty-state-desc">
                Brankas dokumen pribadi Anda siap digunakan. Unggah dan kelompokkan berkas penting Anda seperti KTP, SIM, polis asuransi, dan kartu garansi di satu tempat aman.
            </p>
            <a href="{{ route('documents.create') }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Mulai Tambah Dokumen</span>
            </a>
        </div>
    @else
        <!-- Tabel Dokumen Terbaru -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Nama Dokumen</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Tanggal &amp; Masa Berlaku</th>
                        <th scope="col">Status</th>
                        <th scope="col" style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $doc)
                        @php
                            $isPdf = method_exists($doc, 'isPdf') ? $doc->isPdf() : ($doc->is_pdf ?? false);
                            $isExpired = method_exists($doc, 'isExpired') ? $doc->isExpired() : ($doc->is_expired ?? false);
                            $isExpiringSoon = method_exists($doc, 'isExpiringSoon') ? $doc->isExpiringSoon() : ($doc->is_expiring_soon ?? false);
                            $docDate = $doc->document_date ? \Carbon\Carbon::parse($doc->document_date)->format('d M Y') : \Carbon\Carbon::parse($doc->created_at)->format('d M Y');
                            $expiryDate = $doc->expiry_date ? \Carbon\Carbon::parse($doc->expiry_date)->format('d M Y') : 'Seumur Hidup';
                            $categoryName = $doc->category ? $doc->category->name : 'Umum';
                            $showUrl = $isDemoView ? '#' : route('documents.show', $doc->id);
                        @endphp
                        <tr>
                            <td>
                                <div class="doc-name-cell">
                                    <div class="doc-type-icon" aria-hidden="true">
                                        @if($isPdf)
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
                                        @if(!$isDemoView)
                                            <a href="{{ $showUrl }}" class="doc-title" style="color: var(--color-primary); font-weight: 600;">
                                                {{ $doc->title }}
                                            </a>
                                        @else
                                            <span class="doc-title">{{ $doc->title }}</span>
                                        @endif
                                        <span class="doc-subinfo">{{ $doc->original_filename }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-category">{{ $categoryName }}</span>
                            </td>
                            <td>
                                <div class="doc-meta">
                                    <span style="font-weight: 500; color: var(--color-text);">{{ $docDate }}</span>
                                    <span class="doc-subinfo">Berlaku s/d: {{ $expiryDate }}</span>
                                </div>
                            </td>
                            <td>
                                @if($isExpired)
                                    <span class="badge badge-status-expired">
                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                                        Kedaluwarsa
                                    </span>
                                @elseif($isExpiringSoon)
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
                            <td style="text-align: right;">
                                @if(!$isDemoView)
                                    <a href="{{ route('documents.show', $doc->id) }}" class="btn btn-secondary" style="padding: 4px 10px; font-size: 12px;">
                                        <span>Buka</span>
                                    </a>
                                @else
                                    <span class="badge badge-demo">Demo</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

<!-- Bagian Pengingat Terdekat -->
@if(!$isEmptyState && $nearestReminders->isNotEmpty())
<section class="section-container" style="margin-top: 28px;" aria-label="Pengingat Terdekat">
    <div class="section-header">
        <div class="section-title-wrap">
            <div class="section-title">
                <span>Pengingat Terdekat</span>
                <span class="badge badge-category">{{ $nearestReminders->count() }} Terjadwal</span>
            </div>
            <p class="section-subtitle">Tenggat waktu penting dokumen dan perpanjangan yang memerlukan perhatian Anda</p>
        </div>
        <div class="section-actions">
            <a href="{{ route('reminders.index') }}" class="btn btn-secondary btn-sm">
                <span>Lihat Semua</span>
            </a>
            <a href="{{ route('reminders.create') }}" class="btn btn-primary btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Tambah Pengingat</span>
            </a>
        </div>
    </div>

    <div class="dashboard-reminders-grid">
        @foreach($nearestReminders as $reminder)
            @php
                $dueDate = is_string($reminder->due_date) ? \Carbon\Carbon::parse($reminder->due_date) : $reminder->due_date;
                $showReminderUrl = $isDemoView ? '#' : route('reminders.show', $reminder->id);
                $statusClass = $reminder->status_badge_class ?? 'badge-status-warning';
                $statusLabel = $reminder->status_label ?? 'Mendatang';
            @endphp
            <div class="dashboard-reminder-card">
                <div class="dashboard-reminder-header">
                    <div>
                        <a href="{{ $showReminderUrl }}" class="dashboard-reminder-title">
                            {{ $reminder->title }}
                        </a>
                        @if($reminder->document)
                            <div class="dashboard-reminder-doc">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                                <span>{{ $reminder->document->title }}</span>
                            </div>
                        @endif
                    </div>
                    <span class="badge {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>
                <div class="dashboard-reminder-footer">
                    <span>Jatuh Tempo: <strong>{{ $dueDate->format('d M Y') }}</strong></span>
                    @if(!$isDemoView)
                        <a href="{{ route('reminders.show', $reminder->id) }}" style="color: var(--color-primary); font-weight: 600;">
                            Buka &rarr;
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif
@endsection
