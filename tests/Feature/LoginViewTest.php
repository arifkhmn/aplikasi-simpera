<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginViewTest extends TestCase
{
    /**
     * Memastikan halaman login memiliki elemen identitas brand Simpera dan ornamen geometris.
     */
    public function test_login_page_renders_with_corporate_decorations_and_logo(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);

        // Identitas Brand
        $response->assertSee('Simpera');
        $response->assertSee('Manajemen Dokumen Digital Pribadi');
        $response->assertSee('images/simpera-logo.png');

        // Ornamen Dekorasi Geometris di latar belakang
        $response->assertSee('auth-decoration-tl');
        $response->assertSee('auth-decoration-br');
        $response->assertSee('auth-decoration-accent');
        $response->assertSee('auth-decoration-navy');

        // Form Login & Keamanan
        $response->assertSee('Masuk ke Akun');
        $response->assertSee('name="_token"', false); // CSRF token
        $response->assertSee('type="email"', false);
        $response->assertSee('type="password"', false);
        $response->assertSee('remember');
        $response->assertSee('Ingat sesi saya di perangkat ini');
        $response->assertSee('Daftar sekarang');
        $response->assertSee(route('register'));
    }

    /**
     * Memastikan notifikasi status flash (misal setelah logout) tampil dengan format alert sukses.
     */
    public function test_login_page_displays_session_status_notification(): void
    {
        $response = $this->withSession(['status' => 'Anda telah berhasil keluar dari akun.'])
            ->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('auth-alert-success');
        $response->assertSee('Anda telah berhasil keluar dari akun.');
    }
}

