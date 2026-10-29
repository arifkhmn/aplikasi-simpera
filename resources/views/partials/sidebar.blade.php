<aside class="app-sidebar" id="appSidebar" aria-label="Navigasi Utama">
    <!-- Brand Header Sidebar -->
    <div class="sidebar-header">
        <a href="{{ route('dashboard') }}" class="sidebar-brand" title="Simpera Beranda">
            @if (file_exists(public_path('images/simpera-logo.png')))
                <img src="{{ asset('images/simpera-logo.png') }}" alt="Logo Simpera" class="sidebar-brand-logo" width="34" height="40">
            @else
                <div class="brand-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <line x1="10" y1="9" x2="8" y2="9"></line>
                    </svg>
                </div>
            @endif
            <div class="brand-text">
                <span class="brand-title">Simpera</span>
                <span class="brand-subtitle">Dokumen Digital</span>
            </div>
        </a>

        <!-- Tombol Tutup Sidebar untuk Tampilan Mobile -->
        <button type="button" class="sidebar-close-btn" id="closeSidebarBtn" aria-label="Tutup menu navigasi">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <!-- Navigasi Menu -->
    <nav class="sidebar-nav">
        <div class="nav-section-title">Menu Utama</div>
        <ul class="nav-list">
            <!-- Dashboard -->
            <li>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="nav-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="9"></rect>
                            <rect x="14" y="3" width="7" height="5"></rect>
                            <rect x="14" y="12" width="7" height="9"></rect>
                            <rect x="3" y="16" width="7" height="5"></rect>
                        </svg>
                    </span>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Dokumen -->
            <li>
                <a href="{{ route('documents.index') }}" class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}">
                    <span class="nav-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </span>
                    <span>Dokumen</span>
                </a>
            </li>

            <!-- Pengingat -->
            <li>
                <a href="{{ route('reminders.index') }}" class="nav-link {{ request()->routeIs('reminders.*') ? 'active' : '' }}">
                    <span class="nav-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                    </span>
                    <span>Pengingat</span>
                </a>
            </li>

            <!-- Garansi -->
            <li>
                <a href="{{ route('warranties.index') }}" class="nav-link {{ request()->routeIs('warranties.*') ? 'active' : '' }}">
                    <span class="nav-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </span>
                    <span>Garansi</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-system-badge">
            <span class="status-dot-pulse" aria-hidden="true"></span>
            <span>Simpera v1.0 • Pratinjau</span>
        </div>
    </div>
</aside>
