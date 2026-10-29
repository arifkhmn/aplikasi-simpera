@extends('layouts.guest', ['pageTitle' => 'Masuk ke Akun'])

@section('content')
<div class="auth-card">
    <h1 class="auth-title">Masuk ke Akun</h1>
    <p class="auth-subtitle">Masukkan alamat email dan kata sandi Anda untuk mengelola dokumen pribadi.</p>

    <!-- Notifikasi Status Sesi (Misal: Logout Berhasil) -->
    @if (session('status'))
        <div class="auth-alert-success" role="alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <!-- Input Email -->
        <div class="form-group">
            <label for="email" class="form-label">Alamat Email</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                value="{{ old('email') }}" 
                class="form-input @error('email') is-invalid @enderror" 
                placeholder="nama@email.com" 
                required 
                autofocus 
                autocomplete="username"
            >
            @error('email')
                <div class="form-error" role="alert">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        <!-- Input Kata Sandi -->
        <div class="form-group">
            <label for="password" class="form-label">Kata Sandi</label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                class="form-input @error('password') is-invalid @enderror" 
                placeholder="Masukkan kata sandi akun Anda" 
                required 
                autocomplete="current-password"
            >
            @error('password')
                <div class="form-error" role="alert">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        <!-- Ingat Saya (Remember Me) -->
        <div class="form-checkbox-row">
            <input 
                type="checkbox" 
                id="remember" 
                name="remember" 
                class="form-checkbox"
                {{ old('remember') ? 'checked' : '' }}
            >
            <label for="remember" class="form-checkbox-label">Ingat sesi saya di perangkat ini</label>
        </div>

        <!-- Tombol Masuk -->
        <button type="submit" class="btn btn-primary btn-block">
            <span>Masuk ke Akun</span>
        </button>
    </form>

    <div class="auth-card-footer">
        Belum memiliki akun Simpera? 
        <a href="{{ route('register') }}" class="auth-link">Daftar sekarang</a>
    </div>
</div>
@endsection
