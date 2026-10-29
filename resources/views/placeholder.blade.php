@extends('layouts.app')

@section('content')
<div class="placeholder-page-wrapper">
    <div class="placeholder-card">
        <div class="placeholder-header">
            <div class="placeholder-icon" aria-hidden="true">
                @if(($iconType ?? '') === 'upload')
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                @elseif(($iconType ?? '') === 'bell')
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                @elseif(($iconType ?? '') === 'shield')
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                @else
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <line x1="10" y1="9" x2="8" y2="9"></line>
                    </svg>
                @endif
            </div>
            <div>
                <h2 class="placeholder-title">{{ $sectionTitle ?? 'Modul Dalam Pengembangan' }}</h2>
                <p class="placeholder-desc">{{ $explanation ?? 'Modul ini disiapkan untuk dihubungkan pada tahap pengembangan berikutnya.' }}</p>
            </div>
        </div>

        @if(!empty($roadmap))
            <div class="roadmap-checklist">
                <div class="roadmap-title">Rencana Implementasi Teknis (Tahap Berikutnya):</div>
                <ul class="roadmap-list">
                    @foreach($roadmap as $index => $item)
                        <li class="roadmap-item">
                            <span class="roadmap-step-badge">Tahap {{ $index + 1 }}</span>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="placeholder-actions">
            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Kembali ke Dashboard</span>
            </a>
            @if(request()->routeIs('documents.create'))
                <a href="{{ route('documents.index') }}" class="btn btn-secondary">
                    <span>Lihat Rencana Dokumen</span>
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
