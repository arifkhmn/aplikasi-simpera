<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memastikan pengguna yang belum login dialihkan ke halaman login.
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Memastikan modul dokumen, pengingat, dan garansi dilindungi oleh auth middleware.
     */
    public function test_private_modules_redirect_unauthenticated_users_to_login(): void
    {
        $this->get(route('documents.index'))->assertRedirect(route('login'));
        $this->get(route('documents.create'))->assertRedirect(route('login'));
        $this->get(route('reminders.index'))->assertRedirect(route('login'));
        $this->get(route('warranties.index'))->assertRedirect(route('login'));
    }

    /**
     * Memastikan pengguna yang sudah login dapat mengakses dashboard dengan nama profil asli.
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi.santoso@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Simpera');
        $response->assertSee('Dashboard Ringkasan');
        $response->assertSee('Budi Santoso');
        $response->assertSee('budi.santoso@example.com');
        $response->assertSee('Total Dokumen');
        $response->assertSee('Pengingat Mendatang');
        $response->assertSee('Garansi Aktif');
        $response->assertSee(route('documents.create'));
    }

    /**
     * Memastikan secara bawaan (default), akun pengguna yang masuk menampilkan empty state informatif.
     */
    public function test_authenticated_user_sees_empty_state_by_default(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Belum Ada Dokumen Tersimpan');
        $response->assertSee('Mulai Tambah Dokumen');
    }

    /**
     * Memastikan pengguna dapat beralih ke pratinjau data demo secara eksplisit.
     */
    public function test_authenticated_user_can_view_demo_preview(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard', ['view' => 'demo']));

        $response->assertStatus(200);
        $response->assertSee('Data Contoh Demo');
        $response->assertSee('KTP Digital');
        $response->assertSee('Polis Asuransi');
    }

    /**
     * Memastikan pengguna login dapat mengakses seluruh modul privat.
     */
    public function test_authenticated_user_can_access_private_modules(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('documents.index'))->assertStatus(200)->assertSee('Daftar Dokumen');
        $this->actingAs($user)->get(route('documents.create'))->assertStatus(200)->assertSee('Tambah Dokumen');
        $this->actingAs($user)->get(route('reminders.index'))->assertStatus(200)->assertSee('Jadwal Pengingat');
        $this->actingAs($user)->get(route('warranties.index'))->assertStatus(200)->assertSee('Pelacak Garansi');
    }
}
