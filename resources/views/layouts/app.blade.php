<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Simpera - Aplikasi Manajemen Dokumen Digital Pribadi">
    <title>Simpera - {{ $pageTitle ?? 'Dashboard Dokumen Pribadi' }}</title>
    
    <!-- Stylesheet Utama Simpera (Plain CSS, Navy #172B4D, Tanpa Gradien) -->
    <link rel="stylesheet" href="{{ asset('css/simpera.css') }}">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigasi -->
        @include('partials.sidebar')

        <!-- Area Konten Utama -->
        <div class="app-main">
            <!-- Header Atas -->
            @include('partials.header')

            <!-- Body Halaman -->
            <main class="page-body" id="main-content">
                @yield('content')
            </main>

            <!-- Footer Sederhana -->
            @include('partials.footer')
        </div>
    </div>

    <!-- Overlay Latar Belakang untuk Tampilan Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <!-- Script Minimalis untuk Toggle Navigasi Mobile -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('appSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const openBtn = document.getElementById('openMobileMenuBtn');
            const closeBtn = document.getElementById('closeSidebarBtn');

            function openSidebar() {
                if (sidebar && overlay) {
                    sidebar.classList.add('is-open');
                    overlay.classList.add('is-active');
                    document.body.style.overflow = 'hidden';
                }
            }

            function closeSidebar() {
                if (sidebar && overlay) {
                    sidebar.classList.remove('is-open');
                    overlay.classList.remove('is-active');
                    document.body.style.overflow = '';
                }
            }

            if (openBtn) openBtn.addEventListener('click', openSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            if (overlay) overlay.addEventListener('click', closeSidebar);
        });
    </script>
    <!-- Script Optimasi Navigasi & Transisi Simpera -->
    <script src="{{ asset('js/simpera-nav.js') }}" defer></script>
</body>
</html>
