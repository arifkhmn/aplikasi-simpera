<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Simpera - Autentikasi Pengguna">
    <title>Simpera - {{ $pageTitle ?? 'Autentikasi' }}</title>
    
    <!-- Stylesheet Utama Simpera (Plain CSS, Navy #172B4D, Tanpa Gradien) -->
    <link rel="stylesheet" href="{{ asset('css/simpera.css') }}">
</head>
<body class="auth-page-body">
    <div class="auth-page-bg">
        <!-- Dekorasi Geometris Sudut Kiri Atas (Clean Corporate SaaS) -->
        <div class="auth-decoration auth-decoration-tl" aria-hidden="true">
            <span class="auth-decoration-accent"></span>
            <span class="auth-decoration-navy"></span>
        </div>

        <!-- Dekorasi Geometris Sudut Kanan Bawah (Clean Corporate SaaS) -->
        <div class="auth-decoration auth-decoration-br" aria-hidden="true">
            <span class="auth-decoration-accent"></span>
            <span class="auth-decoration-navy"></span>
        </div>

        <div class="auth-wrapper">
            <div class="auth-container">
                <!-- Brand Logo & Header -->
                <div class="auth-brand-header">
                    <a href="{{ url('/') }}" class="auth-brand-link" title="Simpera">
                        @if (file_exists(public_path('images/simpera-logo.png')))
                            <img src="{{ asset('images/simpera-logo.png') }}" alt="Logo Simpera" class="auth-brand-logo" width="46" height="52">
                        @else
                            <div class="auth-brand-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <line x1="10" y1="9" x2="8" y2="9"></line>
                                </svg>
                            </div>
                        @endif
                    </a>
                    <div class="auth-brand-title">Simpera</div>
                    <div class="auth-brand-desc">Manajemen Dokumen Digital Pribadi</div>
                </div>

                <!-- Konten Halaman Autentikasi -->
                @yield('content')

                <div class="auth-footer-text">
                    Simpera &copy; {{ date('Y') }} &bull; Aman &amp; Terenkripsi
                </div>
            </div>
        </div>
    </div>
    <!-- Script Optimasi Navigasi & Transisi Simpera -->
    <script src="{{ asset('js/simpera-nav.js') }}" defer></script>
</body>
</html>
