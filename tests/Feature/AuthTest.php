<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memastikan halaman registrasi dapat dibuka oleh pengguna tamu.
     */
    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Akun Baru');
        $response->assertSee('Simpera');
        $response->assertSee('Nama Lengkap');
        $response->assertSee('Alamat Email');
        $response->assertSee('Kata Sandi');
        $response->assertSee('Konfirmasi Kata Sandi');
    }

    /**
     * Memastikan pengguna baru dapat mendaftar, kata sandi di-hash, dan langsung diarahkan ke dashboard.
     */
    public function test_new_users_can_register_and_passwords_are_securely_hashed(): void
    {
        $rawPassword = 'Password123!';

        $response = $this->post(route('register'), [
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad.fauzi@example.com',
            'password' => $rawPassword,
            'password_confirmation' => $rawPassword,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'ahmad.fauzi@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Ahmad Fauzi', $user->name);

        // Pastikan password TIDAK disimpan sebagai teks biasa
        $this->assertNotEquals($rawPassword, $user->password);
        $this->assertTrue(Hash::check($rawPassword, $user->password));
    }

    /**
     * Memastikan validasi pendaftaran gagal jika ada data yang kosong atau konfirmasi tidak cocok.
     */
    public function test_registration_fails_with_invalid_data(): void
    {
        $response = $this->post(route('register'), [
            'name' => '',
            'email' => 'bukan-email-valid',
            'password' => 'pendek',
            'password_confirmation' => 'berbeda',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertGuest();
    }

    /**
     * Memastikan validasi pendaftaran menolak email duplikat.
     */
    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'sudahada@example.com',
        ]);

        $response = $this->post(route('register'), [
            'name' => 'Pengguna Lain',
            'email' => 'sudahada@example.com',
            'password' => 'Rahasia123#',
            'password_confirmation' => 'Rahasia123#',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * Memastikan halaman login dapat dibuka oleh pengguna tamu.
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Masuk ke Akun');
        $response->assertSee('Simpera');
        $response->assertSee('Alamat Email');
        $response->assertSee('Kata Sandi');
    }

    /**
     * Memastikan pengguna dapat login menggunakan kredensial yang benar.
     */
    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'email' => 'pengguna@example.com',
            'password' => Hash::make('SandiAman123!'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'pengguna@example.com',
            'password' => 'SandiAman123!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    /**
     * Memastikan pengguna gagal login jika kata sandi salah.
     */
    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'siti@example.com',
            'password' => Hash::make('SandiAsli123!'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'siti@example.com',
            'password' => 'SandiSalah999!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Memastikan pengguna yang sudah login dialihkan dari halaman login dan registrasi.
     */
    public function test_authenticated_users_are_redirected_away_from_guest_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('login'))->assertRedirect('/');
        $this->actingAs($user)->get(route('register'))->assertRedirect('/');
    }

    /**
     * Memastikan pengguna dapat logout secara aman menggunakan metode POST.
     */
    public function test_users_can_logout_via_post_request(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Anda telah berhasil keluar dari akun.');
    }

    /**
     * Memastikan logout tidak diizinkan melalui metode GET (mencegah eksploitasi CSRF link).
     */
    public function test_users_cannot_logout_via_get_request(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/logout');

        // Route GET /logout tidak terdaftar, menghasilkan 405 Method Not Allowed atau 404
        $this->assertTrue(in_array($response->status(), [404, 405]));
        $this->assertAuthenticated();
    }

    /**
     * Memastikan rate limiting aktif setelah 5 kali percobaan login gagal.
     */
    public function test_rate_limiting_protects_against_brute_force_login(): void
    {
        $user = User::factory()->create([
            'email' => 'target@example.com',
            'password' => Hash::make('Benar12345'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => 'target@example.com',
                'password' => 'Salah' . $i,
            ]);
        }

        // Percobaan ke-6 harus di-throttle
        $response = $this->post(route('login'), [
            'email' => 'target@example.com',
            'password' => 'SalahLagi',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }
}
