@extends('layouts.guest', ['pageTitle' => 'Daftar Akun Baru'])

@section('content')
<div class="auth-card">
    <h1 class="auth-title">Daftar Akun Baru</h1>
    <p class="auth-subtitle">Mulai simpan dan lindungi dokumen digital pribadi Anda di Simpera.</p>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <!-- Input Nama Lengkap -->
        <div class="form-group">
            <label for="name" class="form-label">Nama Lengkap</label>
            <input 
                type="text" 
                id="name" 
                name="name" 
                value="{{ old('name') }}" 
                class="form-input @error('name') is-invalid @enderror" 
                placeholder="Contoh: Budi Santoso" 
                required 
                autofocus 
                autocomplete="name"
            >
            @error('name')
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
                placeholder="Minimal 8 karakter" 
                required 
                autocomplete="new-password"
            >
            <div class="form-help-text">Gunakan kombinasi minimal 8 karakter untuk keamanan optimal.</div>
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

        <!-- Input Konfirmasi Kata Sandi -->
        <div class="form-group">
            <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi</label>
            <input 
                type="password" 
                id="password_confirmation" 
                name="password_confirmation" 
                class="form-input" 
                placeholder="Ulangi kata sandi di atas" 
                required 
                autocomplete="new-password"
            >
        </div>

        <!-- Tombol Daftar -->
        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 8px;">
            <span>Daftar Akun</span>
        </button>
    </form>

    <div class="auth-card-footer">
        Sudah memiliki akun Simpera? 
        <a href="{{ route('login') }}" class="auth-link">Masuk di sini</a>
    </div>
</div>
@endsection
