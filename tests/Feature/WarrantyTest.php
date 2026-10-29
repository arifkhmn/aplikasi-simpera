<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Warranty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarrantyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memastikan tamu (unauthenticated) tidak dapat mengakses rute-rute garansi.
     */
    public function test_unauthenticated_user_cannot_access_warranties(): void
    {
        $user = User::factory()->create();
        $warranty = Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Laptop ThinkPad X1',
            'brand' => 'Lenovo',
            'purchase_date' => now()->subMonths(6)->toDateString(),
            'warranty_end_date' => now()->addMonths(18)->toDateString(),
        ]);

        $this->get(route('warranties.index'))->assertRedirect(route('login'));
        $this->get(route('warranties.create'))->assertRedirect(route('login'));
        $this->post(route('warranties.store'), [])->assertRedirect(route('login'));
        $this->get(route('warranties.show', $warranty))->assertRedirect(route('login'));
        $this->get(route('warranties.edit', $warranty))->assertRedirect(route('login'));
        $this->put(route('warranties.update', $warranty), [])->assertRedirect(route('login'));
        $this->delete(route('warranties.destroy', $warranty))->assertRedirect(route('login'));
    }

    /**
     * Memastikan pengguna terautentikasi dapat melihat daftar garansi dan empty state jika belum ada data.
     */
    public function test_authenticated_user_can_view_warranties_index_with_empty_state(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('warranties.index'));

        $response->assertStatus(200);
        $response->assertSee('Pelacak Garansi');
        $response->assertSee('Belum Ada Data Garansi');
        $response->assertSee('Daftarkan Garansi Pertama');
        $response->assertSee(route('warranties.create'));
    }

    /**
     * Memastikan pengguna dapat membuka formulir tambah garansi.
     */
    public function test_authenticated_user_can_view_create_warranty_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('warranties.create'));

        $response->assertStatus(200);
        $response->assertSee('Daftarkan Garansi Produk');
        $response->assertSee('Nama Produk');
        $response->assertSee('Merek / Brand');
        $response->assertSee('Tanggal Pembelian');
        $response->assertSee('Garansi Berakhir');
    }

    /**
     * Memastikan pembuatan data garansi baru berhasil dengan validasi data yang benar.
     */
    public function test_authenticated_user_can_create_basic_warranty(): void
    {
        $user = User::factory()->create();

        $payload = [
            'product_name' => 'MacBook Pro M3',
            'brand' => 'Apple',
            'serial_number' => 'C02D12345678',
            'purchase_date' => now()->subMonths(2)->toDateString(),
            'warranty_end_date' => now()->addMonths(10)->toDateString(),
            'purchase_price' => '24500000',
            'store_name' => 'Apple Authorized Store',
            'notes' => 'Garansi resmi Apple Indonesia 1 tahun.',
        ];

        $response = $this->actingAs($user)->post(route('warranties.store'), $payload);

        $this->assertDatabaseHas('warranties', [
            'user_id' => $user->id,
            'product_name' => 'MacBook Pro M3',
            'brand' => 'Apple',
            'serial_number' => 'C02D12345678',
            'store_name' => 'Apple Authorized Store',
        ]);

        $warranty = Warranty::where('user_id', $user->id)->first();
        $this->assertNotNull($warranty);

        $response->assertRedirect(route('warranties.show', $warranty));
        $response->assertSessionHas('status', 'Data garansi produk berhasil ditambahkan ke katalog.');
    }

    /**
     * Memastikan pengguna dapat menautkan dokumen dari Document Vault miliknya sebagai nota pembelian.
     */
    public function test_authenticated_user_can_create_warranty_with_document_receipt(): void
    {
        $user = User::factory()->create();
        $document = Document::create([
            'user_id' => $user->id,
            'title' => 'Nota Pembelian MacBook',
            'original_filename' => 'nota_macbook.pdf',
            'storage_path' => 'documents/' . $user->id . '/nota_macbook.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 102400,
            'document_date' => now()->subDays(10)->toDateString(),
        ]);

        $payload = [
            'product_name' => 'MacBook Air M2',
            'brand' => 'Apple',
            'purchase_date' => now()->subDays(10)->toDateString(),
            'warranty_end_date' => now()->addDays(355)->toDateString(),
            'document_id' => $document->id,
        ];

        $response = $this->actingAs($user)->post(route('warranties.store'), $payload);

        $warranty = Warranty::where('user_id', $user->id)->first();
        $this->assertNotNull($warranty);
        $this->assertEquals($document->id, $warranty->document_id);

        $response->assertRedirect(route('warranties.show', $warranty));
    }

    /**
     * Memastikan pengguna TIDAK DAPAT menggunakan dokumen milik pengguna lain sebagai nota pembelian.
     */
    public function test_user_cannot_link_another_users_document_as_receipt(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $otherUserDoc = Document::create([
            'user_id' => $user2->id,
            'title' => 'Nota Rahasia User 2',
            'original_filename' => 'nota_user2.pdf',
            'storage_path' => 'documents/' . $user2->id . '/nota_user2.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 50000,
            'document_date' => now()->subDays(5)->toDateString(),
        ]);

        $payload = [
            'product_name' => 'Smart TV 55 Inch',
            'brand' => 'Samsung',
            'purchase_date' => now()->subMonths(1)->toDateString(),
            'warranty_end_date' => now()->addMonths(11)->toDateString(),
            'document_id' => $otherUserDoc->id, // Dokumen milik user 2
        ];

        $response = $this->actingAs($user1)->post(route('warranties.store'), $payload);

        $response->assertSessionHasErrors(['document_id']);
        $this->assertDatabaseMissing('warranties', [
            'user_id' => $user1->id,
            'product_name' => 'Smart TV 55 Inch',
        ]);
    }

    /**
     * Memastikan aturan validasi berjalan dengan tepat (nama, tanggal berakhir >= pembelian, harga valid).
     */
    public function test_warranty_validation_rules(): void
    {
        $user = User::factory()->create();

        // 1. Data kosong
        $response = $this->actingAs($user)->post(route('warranties.store'), []);
        $response->assertSessionHasErrors(['product_name', 'brand', 'purchase_date', 'warranty_end_date']);

        // 2. Tanggal garansi berakhir sebelum tanggal pembelian
        $invalidDates = [
            'product_name' => 'Mouse Wireless',
            'brand' => 'Logitech',
            'purchase_date' => now()->toDateString(),
            'warranty_end_date' => now()->subDays(5)->toDateString(), // Tidak valid
        ];
        $response2 = $this->actingAs($user)->post(route('warranties.store'), $invalidDates);
        $response2->assertSessionHasErrors(['warranty_end_date']);

        // 3. Format harga tidak valid (angka negatif)
        $invalidPrice = [
            'product_name' => 'Mouse Wireless',
            'brand' => 'Logitech',
            'purchase_date' => now()->subMonths(1)->toDateString(),
            'warranty_end_date' => now()->addMonths(11)->toDateString(),
            'purchase_price' => '-50000',
        ];
        $response3 = $this->actingAs($user)->post(route('warranties.store'), $invalidPrice);
        $response3->assertSessionHasErrors(['purchase_price']);
    }

    /**
     * Memastikan opsi integrasi pengingat otomatis membuat pengingat di modul Smart Reminders secara aman.
     */
    public function test_authenticated_user_can_create_warranty_with_automated_smart_reminder(): void
    {
        $user = User::factory()->create();

        $payload = [
            'product_name' => 'Kulkas 2 Pintu',
            'brand' => 'Sharp',
            'serial_number' => 'SHP-99281',
            'purchase_date' => now()->subDays(5)->toDateString(),
            'warranty_end_date' => now()->addYears(2)->toDateString(),
            'create_reminder' => '1',
        ];

        $response = $this->actingAs($user)->post(route('warranties.store'), $payload);

        $warranty = Warranty::where('user_id', $user->id)->first();
        $this->assertNotNull($warranty);
        $this->assertNotNull($warranty->reminder_id);

        $reminder = Reminder::find($warranty->reminder_id);
        $this->assertNotNull($reminder);
        $this->assertEquals($user->id, $reminder->user_id);
        $this->assertEquals($warranty->warranty_end_date->toDateString(), $reminder->due_date->toDateString());
        $this->assertStringContainsString('Sharp', $reminder->title);
        $this->assertStringContainsString('Kulkas 2 Pintu', $reminder->title);
        $this->assertEquals('pending', $reminder->status);

        $response->assertRedirect(route('warranties.show', $warranty));
    }

    /**
     * Memastikan penghitungan status garansi otomatis berdasarkan tanggal:
     * - Aktif (> 30 hari ke depan)
     * - Hampir berakhir (0 s/d 30 hari ke depan)
     * - Kedaluwarsa (< hari ini)
     */
    public function test_automatic_status_calculation_based_on_end_date(): void
    {
        $user = User::factory()->create();

        // 1. Aktif (berakhir 60 hari lagi)
        $activeWarranty = Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Mesin Cuci',
            'brand' => 'LG',
            'purchase_date' => today()->subMonths(6),
            'warranty_end_date' => today()->addDays(60),
        ]);
        $this->assertEquals('active', $activeWarranty->status);
        $this->assertEquals('Aktif', $activeWarranty->status_label);
        $this->assertEquals('badge-status-active', $activeWarranty->status_badge_class);
        $this->assertTrue($activeWarranty->isActive());
        $this->assertFalse($activeWarranty->isExpiringSoon());
        $this->assertFalse($activeWarranty->isExpired());
        $this->assertGreaterThan(30, $activeWarranty->days_remaining);

        // 2. Hampir berakhir (berakhir 15 hari lagi)
        $expiringSoonWarranty = Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Headphone Bluetooth',
            'brand' => 'Sony',
            'purchase_date' => today()->subMonths(11),
            'warranty_end_date' => today()->addDays(15),
        ]);
        $this->assertEquals('expiring_soon', $expiringSoonWarranty->status);
        $this->assertEquals('Hampir Berakhir', $expiringSoonWarranty->status_label);
        $this->assertEquals('badge-status-warning', $expiringSoonWarranty->status_badge_class);
        $this->assertTrue($expiringSoonWarranty->isExpiringSoon());
        $this->assertFalse($expiringSoonWarranty->isActive());
        $this->assertFalse($expiringSoonWarranty->isExpired());
        $this->assertEquals(15, $expiringSoonWarranty->days_remaining);

        // 3. Hampir berakhir batas hari ini (0 hari)
        $todayWarranty = Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Kipas Angin',
            'brand' => 'Miyako',
            'purchase_date' => today()->subYear(),
            'warranty_end_date' => today(),
        ]);
        $this->assertEquals('expiring_soon', $todayWarranty->status);
        $this->assertEquals(0, $todayWarranty->days_remaining);

        // 4. Kedaluwarsa (berakhir 10 hari lalu)
        $expiredWarranty = Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Blender',
            'brand' => 'Philips',
            'purchase_date' => today()->subMonths(13),
            'warranty_end_date' => today()->subDays(10),
        ]);
        $this->assertEquals('expired', $expiredWarranty->status);
        $this->assertEquals('Kedaluwarsa', $expiredWarranty->status_label);
        $this->assertEquals('badge-status-expired', $expiredWarranty->status_badge_class);
        $this->assertTrue($expiredWarranty->isExpired());
        $this->assertFalse($expiredWarranty->isActive());
        $this->assertFalse($expiredWarranty->isExpiringSoon());
        $this->assertLessThan(0, $expiredWarranty->days_remaining);
    }

    /**
     * Memastikan filter status dan pencarian pada halaman index berjalan sesuai kriteria.
     */
    public function test_warranties_status_filtering_and_search(): void
    {
        $user = User::factory()->create();

        $active = Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Televisi OLED',
            'brand' => 'Samsung',
            'purchase_date' => today()->subMonths(2),
            'warranty_end_date' => today()->addDays(90),
        ]);

        $expiring = Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Smartphone Galaxy',
            'brand' => 'Samsung',
            'purchase_date' => today()->subMonths(11),
            'warranty_end_date' => today()->addDays(10),
        ]);

        $expired = Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Microwave Digital',
            'brand' => 'Panasonic',
            'purchase_date' => today()->subMonths(14),
            'warranty_end_date' => today()->subDays(30),
        ]);

        // Filter tab: aktif
        $respActive = $this->actingAs($user)->get(route('warranties.index', ['status' => 'active']));
        $respActive->assertSee('Televisi OLED');
        $respActive->assertDontSee('Smartphone Galaxy');
        $respActive->assertDontSee('Microwave Digital');

        // Filter tab: hampir berakhir
        $respExpiring = $this->actingAs($user)->get(route('warranties.index', ['status' => 'expiring_soon']));
        $respExpiring->assertSee('Smartphone Galaxy');
        $respExpiring->assertDontSee('Televisi OLED');
        $respExpiring->assertDontSee('Microwave Digital');

        // Filter tab: kedaluwarsa
        $respExpired = $this->actingAs($user)->get(route('warranties.index', ['status' => 'expired']));
        $respExpired->assertSee('Microwave Digital');
        $respExpired->assertDontSee('Televisi OLED');
        $respExpired->assertDontSee('Smartphone Galaxy');

        // Pencarian kata kunci (brand Samsung)
        $respSearch = $this->actingAs($user)->get(route('warranties.index', ['q' => 'Samsung']));
        $respSearch->assertSee('Televisi OLED');
        $respSearch->assertSee('Smartphone Galaxy');
        $respSearch->assertDontSee('Microwave Digital');
    }

    /**
     * Memastikan isolasi data antar pengguna: pengguna tidak dapat melihat detail garansi milik pengguna lain.
     */
    public function test_user_cannot_view_another_users_warranty(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $warrantyUser2 = Warranty::create([
            'user_id' => $user2->id,
            'product_name' => 'iPad Pro M4 Milik User 2',
            'brand' => 'Apple',
            'purchase_date' => today()->subMonth(),
            'warranty_end_date' => today()->addMonths(11),
        ]);

        $response = $this->actingAs($user1)->get(route('warranties.show', $warrantyUser2));
        $response->assertStatus(403);
    }

    /**
     * Memastikan isolasi data antar pengguna: pengguna tidak dapat mengedit atau memperbarui garansi milik pengguna lain.
     */
    public function test_user_cannot_edit_or_update_another_users_warranty(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $warrantyUser2 = Warranty::create([
            'user_id' => $user2->id,
            'product_name' => 'Drone DJI Milik User 2',
            'brand' => 'DJI',
            'purchase_date' => today()->subMonth(),
            'warranty_end_date' => today()->addMonths(11),
        ]);

        // Coba buka form edit
        $this->actingAs($user1)
            ->get(route('warranties.edit', $warrantyUser2))
            ->assertStatus(403);

        // Coba kirim request update
        $this->actingAs($user1)
            ->put(route('warranties.update', $warrantyUser2), [
                'product_name' => 'Perubahan Ilegal',
                'brand' => 'Hacker',
                'purchase_date' => today()->subMonth()->toDateString(),
                'warranty_end_date' => today()->addMonths(5)->toDateString(),
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('warranties', [
            'id' => $warrantyUser2->id,
            'product_name' => 'Perubahan Ilegal',
        ]);
    }

    /**
     * Memastikan isolasi data antar pengguna: pengguna tidak dapat menghapus garansi milik pengguna lain.
     */
    public function test_user_cannot_delete_another_users_warranty(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $warrantyUser2 = Warranty::create([
            'user_id' => $user2->id,
            'product_name' => 'Kamera Canon Milik User 2',
            'brand' => 'Canon',
            'purchase_date' => today()->subMonth(),
            'warranty_end_date' => today()->addMonths(11),
        ]);

        $response = $this->actingAs($user1)->delete(route('warranties.destroy', $warrantyUser2));
        $response->assertStatus(403);

        $this->assertDatabaseHas('warranties', [
            'id' => $warrantyUser2->id,
            'product_name' => 'Kamera Canon Milik User 2',
        ]);
    }

    /**
     * Memastikan pemilik data dapat memperbarui data garansi dan sinkronisasi ke pengingat yang terhubung.
     */
    public function test_authenticated_user_can_update_warranty_and_sync_linked_reminder(): void
    {
        $user = User::factory()->create();

        $warranty = Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Monitor 27 Inch',
            'brand' => 'Dell',
            'purchase_date' => today()->subMonths(3),
            'warranty_end_date' => today()->addMonths(9),
        ]);

        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => "Batas Akhir Garansi: Dell Monitor 27 Inch",
            'due_date' => $warranty->warranty_end_date,
            'status' => 'pending',
        ]);
        $warranty->update(['reminder_id' => $reminder->id]);

        $newEndDate = today()->addMonths(21)->toDateString();

        $updatePayload = [
            'product_name' => 'Monitor 27 Inch UltraSharp',
            'brand' => 'Dell Official',
            'serial_number' => 'DEL-112233',
            'purchase_date' => today()->subMonths(3)->toDateString(),
            'warranty_end_date' => $newEndDate,
            'purchase_price' => '8500000',
            'store_name' => 'Dell Store Mall',
            'notes' => 'Diperpanjang garansi resmi.',
        ];

        $response = $this->actingAs($user)->put(route('warranties.update', $warranty), $updatePayload);

        $response->assertRedirect(route('warranties.show', $warranty));
        $response->assertSessionHas('status', 'Data garansi produk berhasil diperbarui.');

        // Cek update pada data garansi
        $warranty->refresh();
        $this->assertEquals('Monitor 27 Inch UltraSharp', $warranty->product_name);
        $this->assertEquals('Dell Official', $warranty->brand);
        $this->assertEquals('DEL-112233', $warranty->serial_number);
        $this->assertEquals($newEndDate, $warranty->warranty_end_date->toDateString());

        // Cek sinkronisasi ke pengingat yang terhubung
        $reminder->refresh();
        $this->assertEquals($newEndDate, $reminder->due_date->toDateString());
        $this->assertStringContainsString('Monitor 27 Inch UltraSharp', $reminder->title);
    }

    /**
     * Memastikan menghapus garansi TIDAK menghapus pengingat secara sembarangan,
     * sehingga jadwal pengingat pengguna di Smart Reminders tetap terjaga.
     */
    public function test_deleting_warranty_does_not_delete_reminder(): void
    {
        $user = User::factory()->create();

        $warranty = Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Printer Inkjet',
            'brand' => 'Epson',
            'purchase_date' => today()->subMonth(),
            'warranty_end_date' => today()->addMonths(11),
        ]);

        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => "Batas Akhir Garansi: Epson Printer Inkjet",
            'due_date' => $warranty->warranty_end_date,
            'status' => 'pending',
        ]);
        $warranty->update(['reminder_id' => $reminder->id]);

        $response = $this->actingAs($user)->delete(route('warranties.destroy', $warranty));

        $response->assertRedirect(route('warranties.index'));
        $response->assertSessionHas('status', 'Data garansi produk berhasil dihapus.');

        // Data garansi terhapus
        $this->assertDatabaseMissing('warranties', ['id' => $warranty->id]);

        // Pengingat di Smart Reminders TETAP ADA dan TIDAK ikut terhapus
        $this->assertDatabaseHas('reminders', ['id' => $reminder->id]);
    }

    /**
     * Memastikan jika dokumen nota dihapus dari Document Vault,
     * foreign key document_id pada garansi otomatis menjadi NULL (nullOnDelete) tanpa merusak data garansi.
     */
    public function test_deleting_receipt_document_nullifies_document_id_on_warranty(): void
    {
        $user = User::factory()->create();
        $document = Document::create([
            'user_id' => $user->id,
            'title' => 'Nota Pembelian Scanner',
            'original_filename' => 'nota_scanner.pdf',
            'storage_path' => 'documents/' . $user->id . '/nota_scanner.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 45000,
            'document_date' => now()->toDateString(),
        ]);

        $warranty = Warranty::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'product_name' => 'Document Scanner Pro',
            'brand' => 'Canon',
            'purchase_date' => today()->subMonth(),
            'warranty_end_date' => today()->addMonths(11),
        ]);

        $this->assertEquals($document->id, $warranty->document_id);

        // Hapus dokumen
        $document->delete();

        // Garansi tetap ada dengan document_id bernilai null
        $warranty->refresh();
        $this->assertNull($warranty->document_id);
        $this->assertDatabaseHas('warranties', [
            'id' => $warranty->id,
            'product_name' => 'Document Scanner Pro',
        ]);
    }

    /**
     * Memastikan ringkasan garansi aktif ditampilkan di dashboard.
     */
    public function test_dashboard_displays_warranty_summary_counts(): void
    {
        $user = User::factory()->create();

        // 2 garansi aktif
        Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'AC Inverter',
            'brand' => 'Daikin',
            'purchase_date' => today()->subMonth(),
            'warranty_end_date' => today()->addMonths(24),
        ]);
        Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'TV 43 Inch',
            'brand' => 'LG',
            'purchase_date' => today()->subMonth(),
            'warranty_end_date' => today()->addMonths(12),
        ]);

        // 1 garansi hampir berakhir
        Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Speaker Portable',
            'brand' => 'JBL',
            'purchase_date' => today()->subMonths(11),
            'warranty_end_date' => today()->addDays(10),
        ]);

        // 1 garansi kedaluwarsa
        Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Kipas Dinding',
            'brand' => 'Cosmos',
            'purchase_date' => today()->subMonths(14),
            'warranty_end_date' => today()->subDays(20),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Garansi Aktif');
        // Total valid (aktif + hampir berakhir = 3)
        $response->assertSee('3');
        $response->assertSee('1 hampir berakhir');
        $response->assertSee('1 kedaluwarsa');
    }
}
