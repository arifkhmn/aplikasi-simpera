<header class="app-header">
    <div style="display: flex; align-items: center;">
        <!-- Tombol Buka Menu Navigasi (Hanya Mobile) -->
        <button type="button" class="mobile-menu-toggle" id="openMobileMenuBtn" aria-label="Buka menu navigasi">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <div class="header-title-group">
            <h1 class="page-title">{{ $pageTitle ?? 'Dashboard Ringkasan' }}</h1>
            <p class="page-description">{{ $pageDescription ?? 'Kelola dan pantau seluruh dokumen digital, garansi, serta tanggal kedaluwarsa dokumen pribadi Anda.' }}</p>
        </div>
    </div>

    <div class="header-actions">
        @auth
            <!-- Profil Pengguna Terautentikasi & Tombol Logout Aman -->
            <div class="user-profile-widget">
                <div class="user-profile-badge" title="Akun terautentikasi: {{ Auth::user()->name }}">
                    <div class="user-avatar-initials" aria-hidden="true">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <div class="user-info-text">
                        <span class="user-display-name">{{ Auth::user()->name }}</span>
                        <span class="user-email-text">{{ Auth::user()->email }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-logout" id="btnLogout" title="Keluar dari akun secara aman">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        @else
            <!-- Pratinjau untuk Pengguna Tamu -->
            <a href="{{ route('login') }}" class="btn btn-primary">
                <span>Masuk</span>
            </a>
        @endauth
    </div>
</header>
