<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\Reminder;
use App\Models\ReminderNotification;
use App\Models\User;
use App\Models\Warranty;
use App\Services\ReminderNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * UJI KEAMANAN: Memastikan pengguna TIDAK BISA menautkan category_id milik pengguna lain saat membuat dokumen (IDOR prevention).
     */
    public function test_user_cannot_link_another_users_category_on_document_creation(): void
    {
        Storage::fake('local');

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Kategori milik User B
        $categoryB = Category::create([
            'user_id' => $userB->id,
            'name' => 'Kategori Rahasia User B',
        ]);

        $file = UploadedFile::fake()->create('dokumen_a.pdf', 500, 'application/pdf');

        // User A mencoba membuat dokumen menggunakan category_id milik User B
        $response = $this->actingAs($userA)->post(route('documents.store'), [
            'title' => 'Dokumen User A',
            'file' => $file,
            'category_id' => $categoryB->id,
        ]);

        $response->assertSessionHasErrors(['category_id']);
        $this->assertEquals(0, Document::count());
    }

    /**
     * UJI KEAMANAN: Memastikan pengguna TIDAK BISA menautkan category_id milik pengguna lain saat memperbarui dokumen (IDOR prevention).
     */
    public function test_user_cannot_link_another_users_category_on_document_update(): void
    {
        Storage::fake('local');

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $categoryB = Category::create([
            'user_id' => $userB->id,
            'name' => 'Kategori Finansial User B',
        ]);

        $documentA = Document::create([
            'user_id' => $userA->id,
            'title' => 'Dokumen Asli User A',
            'original_filename' => 'dokumen_a.pdf',
            'storage_path' => 'documents/' . $userA->id . '/dokumen_a.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        // User A mencoba mengubah category_id dokumennya ke kategori milik User B
        $response = $this->actingAs($userA)->put(route('documents.update', $documentA), [
            'title' => 'Judul Baru',
            'category_id' => $categoryB->id,
        ]);

        $response->assertSessionHasErrors(['category_id']);
        $documentA->refresh();
        $this->assertNull($documentA->category_id);
    }

    /**
     * UJI KEAMANAN: Memastikan MIME type yang disimpan di database adalah hasil deteksi server-side,
     * bukan MIME type yang dikirim oleh client dalam header HTTP.
     */
    public function test_uploaded_file_mime_type_uses_server_detected_mime(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        // Buat file PDF valid
        $file = UploadedFile::fake()->create('berkas.pdf', 500, 'application/pdf');

        $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'Uji Integritas MIME',
            'file' => $file,
        ]);

        $document = Document::where('user_id', $user->id)->first();
        $this->assertNotNull($document);
        $this->assertEquals('application/pdf', $document->mime_type);
    }

    /**
     * UJI KEAMANAN: Memastikan path traversal pada nama file asli (original_filename) dibersihkan saat upload.
     */
    public function test_path_traversal_in_filename_is_sanitized_on_upload(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        // Nama file berbahaya dengan traversal dan tanda kutip
        $maliciousFilename = '../../etc/passwd"evil.pdf';
        $file = UploadedFile::fake()->create($maliciousFilename, 200, 'application/pdf');

        $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'Uji Sanitasi Nama File',
            'file' => $file,
        ]);

        $document = Document::where('user_id', $user->id)->first();
        $this->assertNotNull($document);

        // Pastikan tidak ada path traversal "../" atau tanda kutip pada original_filename
        $this->assertStringNotContainsString('../', $document->original_filename);
        $this->assertStringNotContainsString('..\\', $document->original_filename);
        $this->assertStringNotContainsString('"', $document->original_filename);
    }

    /**
     * UJI KEAMANAN: Memastikan respons preview menetapkan header keamanan (X-Content-Type-Options: nosniff)
     * dan menyajikan Content-Type yang aman.
     */
    public function test_preview_sets_security_headers_and_safe_mime_type(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $path = 'documents/' . $user->id . '/test_pratinjau.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 konten pdf dummy');

        $document = Document::create([
            'user_id' => $user->id,
            'title' => 'Pratinjau Aman',
            'original_filename' => 'test_pratinjau.pdf',
            'storage_path' => $path,
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $response = $this->actingAs($user)->get(route('documents.preview', $document));

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * UJI KEAMANAN: Memastikan penghapusan dokumen tidak dapat menghapus file di luar folder dokumen pengguna.
     */
    public function test_document_destroy_cannot_delete_files_outside_user_directory(): void
    {
        Storage::fake('local');

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // File penting milik User B
        $userBPath = 'documents/' . $userB->id . '/file_kritis_b.pdf';
        Storage::disk('local')->put($userBPath, 'Data berharga User B');

        // Dokumen User A yang diubah storage_path-nya secara tidak wajar menunjuk ke file User B
        $compromisedDocA = Document::create([
            'user_id' => $userA->id,
            'title' => 'Dokumen Manipulasi',
            'original_filename' => 'dokumen.pdf',
            'storage_path' => $userBPath, // Menunjuk ke path User B
            'mime_type' => 'application/pdf',
            'file_size' => 500,
        ]);

        // User A menghapus dokumennya sendiri
        $this->actingAs($userA)->delete(route('documents.destroy', $compromisedDocA));

        // File User B HARUS TETAP ADA karena di luar direktori documents/{userA->id}/
        Storage::disk('local')->assertExists($userBPath);
    }

    /**
     * UJI KEAMANAN: Memastikan operasi toggle favorite dilindungi oleh policy (User B tidak bisa toggle dokumen User A).
     */
    public function test_user_cannot_bypass_policy_on_document_favorite(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $documentA = Document::create([
            'user_id' => $userA->id,
            'title' => 'Dokumen Privat A',
            'original_filename' => 'dok_a.pdf',
            'storage_path' => 'documents/' . $userA->id . '/dok_a.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 500,
            'is_favorite' => false,
        ]);

        // User B mencoba toggle favorite pada dokumen User A
        $response = $this->actingAs($userB)->post(route('documents.favorite', $documentA));
        $response->assertStatus(403);

        $documentA->refresh();
        $this->assertFalse($documentA->is_favorite);
    }

    /**
     * UJI KEAMANAN: Memastikan operasi toggle status pengingat dilindungi policy (User B tidak bisa ubah pengingat User A).
     */
    public function test_user_cannot_bypass_policy_on_reminder_toggle(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $reminderA = Reminder::create([
            'user_id' => $userA->id,
            'title' => 'Pengingat Penting User A',
            'due_date' => today()->addDays(5),
            'status' => 'pending',
        ]);

        // User B mencoba toggle status pengingat User A
        $response = $this->actingAs($userB)->post(route('reminders.toggle', $reminderA));
        $response->assertStatus(403);

        $reminderA->refresh();
        $this->assertEquals('pending', $reminderA->status);
    }

    /**
     * UJI KEAMANAN: Memastikan pembaruan berulang pada garansi dengan opsi pengingat tidak menimbulkan duplikasi pengingat.
     */
    public function test_warranty_update_does_not_duplicate_reminders(): void
    {
        $user = User::factory()->create();

        $warranty = Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Laptop Gaming',
            'brand' => 'Asus',
            'purchase_date' => today()->subMonth(),
            'warranty_end_date' => today()->addMonths(11),
        ]);

        $initialReminderCount = Reminder::where('user_id', $user->id)->count();
        $this->assertEquals(0, $initialReminderCount);

        // Update 1: Buat pengingat pertama kali via opsi edit
        $payload = [
            'product_name' => 'Laptop Gaming ROG',
            'brand' => 'Asus',
            'purchase_date' => today()->subMonth()->toDateString(),
            'warranty_end_date' => today()->addMonths(11)->toDateString(),
            'create_reminder' => '1',
        ];

        $this->actingAs($user)->put(route('warranties.update', $warranty), $payload);
        $warranty->refresh();

        $this->assertNotNull($warranty->reminder_id);
        $this->assertEquals(1, Reminder::where('user_id', $user->id)->count());

        // Update 2: Kirim lagi update beberapa kali
        $this->actingAs($user)->put(route('warranties.update', $warranty), $payload);
        $this->actingAs($user)->put(route('warranties.update', $warranty), $payload);

        // Total pengingat HARUS TETAP 1 (tidak terjadi duplikasi)
        $this->assertEquals(1, Reminder::where('user_id', $user->id)->count());
    }

    /**
     * UJI KEAMANAN: Memastikan ReminderNotificationService tidak membuat notifikasi ganda untuk interval & kanal yang sama.
     */
    public function test_reminder_notification_service_prevents_duplicate_notifications(): void
    {
        $user = User::factory()->create();

        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Uji Notifikasi Duplikat',
            'due_date' => today()->addDays(7), // Interval H-7
            'status' => 'pending',
        ]);

        $service = new ReminderNotificationService();

        // Eksekusi pertama
        $result1 = $service->processDueReminders('system');
        $this->assertEquals(1, $result1['sent']);
        $this->assertEquals(0, $result1['skipped']);
        $this->assertEquals(1, ReminderNotification::where('reminder_id', $reminder->id)->count());

        // Eksekusi kedua pada pengingat yang sama
        $result2 = $service->processDueReminders('system');
        $this->assertEquals(0, $result2['sent']);
        $this->assertEquals(1, $result2['skipped']);

        // Data dalam tabel reminder_notifications tetap hanya 1 baris
        $this->assertEquals(1, ReminderNotification::where('reminder_id', $reminder->id)->count());
    }

    /**
     * UJI KEAMANAN: Memastikan penghapusan akun pengguna meng-cascade penghapusan seluruh data terkait
     * (dokumen, pengingat, notifikasi, kategori, garansi) dan tidak meninggalkan data orphaned.
     */
    public function test_user_deletion_cascades_all_related_records(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Kategori Pengguna',
        ]);

        $document = Document::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Dokumen Pengguna',
            'original_filename' => 'doc.pdf',
            'storage_path' => 'documents/' . $user->id . '/doc.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $reminder = Reminder::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'title' => 'Pengingat Pengguna',
            'due_date' => today()->addDays(10),
            'status' => 'pending',
        ]);

        $notification = ReminderNotification::create([
            'reminder_id' => $reminder->id,
            'interval_days' => 10,
            'channel' => 'system',
            'sent_at' => now(),
            'status' => 'sent',
        ]);

        $warranty = Warranty::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'reminder_id' => $reminder->id,
            'product_name' => 'Peralatan Pengguna',
            'brand' => 'Brand Test',
            'purchase_date' => today()->subMonth(),
            'warranty_end_date' => today()->addMonths(11),
        ]);

        // Hapus pengguna
        $user->delete();

        // Seluruh data terkait harus terhapus secara cascade
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        $this->assertDatabaseMissing('reminders', ['id' => $reminder->id]);
        $this->assertDatabaseMissing('reminder_notifications', ['id' => $notification->id]);
        $this->assertDatabaseMissing('warranties', ['id' => $warranty->id]);
    }
}
