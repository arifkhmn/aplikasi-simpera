<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarLogoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memastikan sidebar aplikasi menggunakan logo Simpera baru.
     */
    public function test_sidebar_renders_with_new_simpera_logo(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);

        // Memastikan aset logo Simpera baru terpasang di sidebar
        $response->assertSee('sidebar-brand-logo');
        $response->assertSee('images/simpera-logo.png');

        // Memastikan teks brand & subtitle tetap utuh
        $response->assertSee('Simpera');
        $response->assertSee('Dokumen Digital');

        // Memastikan kotak brand-icon lama tidak ditampilkan ketika file logo ada
        $response->assertDontSee('class="brand-icon"');
    }

    /**
     * Memastikan logo yang sama digunakan secara konsisten pada halaman autentikasi/login.
     */
    public function test_consistent_logo_asset_between_sidebar_and_login_page(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('images/simpera-logo.png');
        $response->assertSee('Simpera');
    }
}

