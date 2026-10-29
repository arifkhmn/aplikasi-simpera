<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class NavigationTransitionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memastikan pengguna belum login diarahkan ke /login saat mengakses modul utama.
     */
    public function test_unauthenticated_user_redirected_to_login_on_protected_routes(): void
    {
        $protectedRoutes = [
            route('dashboard'),
            route('documents.index'),
            route('reminders.index'),
            route('warranties.index'),
        ];

        foreach ($protectedRoutes as $url) {
            $response = $this->get($url);
            $response->assertRedirect(route('login'));
        }
    }

    /**
     * Memastikan halaman guest (login & register) memuat stylesheet dan script navigasi Simpera.
     */
    public function test_guest_pages_load_and_include_navigation_assets(): void
    {
        $guestRoutes = [
            route('login'),
            route('register'),
        ];

        foreach ($guestRoutes as $url) {
            $response = $this->get($url);
            $response->assertOk();
            $response->assertSee('simpera.css');
            $response->assertSee('simpera-nav.js');
            $response->assertSee('auth-container');
        }
    }

    /**
     * Memastikan pengguna login dapat bernavigasi dengan lancar ke seluruh modul utama:
     * Dashboard, Document Vault, Smart Reminders, dan Warranty Tracker.
     */
    public function test_authenticated_user_can_navigate_all_main_modules(): void
    {
        $user = User::factory()->create();

        $routesToTest = [
            route('dashboard') => 'Dashboard Ringkasan',
            route('documents.index') => 'Brankas Dokumen',
            route('reminders.index') => 'Smart Reminders',
            route('warranties.index') => 'Pelacak Garansi',
        ];

        foreach ($routesToTest as $url => $expectedTitle) {
            $response = $this->actingAs($user)->get($url);
            $response->assertOk();
            $response->assertSee('id="main-content"', false);
            $response->assertSee('app-sidebar');
            $response->assertSee('app-header');
            $response->assertSee('simpera-nav.js');
            $response->assertSee($expectedTitle);
        }
    }

    /**
     * Memastikan berkas CSS dan JavaScript transisi navigasi terpasang dengan konfigurasi standar:
     * - View Transitions API (@view-transition)
     * - Durasi transisi visual 200ms
     * - Progress bar non-blocking #simpera-nav-progress
     * - Aksesibilitas prefers-reduced-motion
     */
    public function test_navigation_script_and_transition_css_are_properly_configured(): void
    {
        $cssPath = public_path('css/simpera.css');
        $this->assertFileExists($cssPath);
        $cssContent = File::get($cssPath);

        // Uji aturan CSS
        $this->assertStringContainsString('@view-transition', $cssContent);
        $this->assertStringContainsString('simperaFadeIn', $cssContent);
        $this->assertStringContainsString('200ms', $cssContent);
        $this->assertStringContainsString('#simpera-nav-progress', $cssContent);
        $this->assertStringContainsString('prefers-reduced-motion', $cssContent);
        $this->assertStringContainsString('view-transition-name: app-sidebar', $cssContent);
        $this->assertStringContainsString('view-transition-name: app-header', $cssContent);
        $this->assertStringContainsString('view-transition-name: main-content', $cssContent);

        // Uji berkas JavaScript
        $jsPath = public_path('js/simpera-nav.js');
        $this->assertFileExists($jsPath);
        $jsContent = File::get($jsPath);

        $this->assertStringContainsString('simpera-nav-progress', $jsContent);
        $this->assertStringContainsString('prefers-reduced-motion', $jsContent);
        $this->assertStringContainsString('pageshow', $jsContent);
        $this->assertStringContainsString('beforeunload', $jsContent);
    }

    /**
     * Memastikan validasi formulir dan error handling tetap berfungsi normal saat navigasi/submit.
     */
    public function test_form_validation_and_error_handling_remain_intact(): void
    {
        // Login dengan kredensial kosong
        $response = $this->post(route('login'), []);
        $response->assertSessionHasErrors(['email', 'password']);
        $response->assertRedirect();
    }

    /**
     * Memastikan rute tidak ditemukan (404) ditangani dengan benar tanpa merusak aplikasi.
     */
    public function test_not_found_route_returns_404_cleanly(): void
    {
        $response = $this->get('/halaman-acak-yang-tidak-ada');
        $response->assertNotFound();
    }
}
