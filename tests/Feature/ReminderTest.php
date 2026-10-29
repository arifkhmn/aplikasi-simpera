<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\Reminder;
use App\Models\ReminderNotification;
use App\Models\User;
use App\Services\ReminderNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memastikan pengguna tanpa login diarahkan ke halaman login saat mengakses modul pengingat.
     */
    public function test_unauthenticated_user_cannot_access_reminders(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Pengingat Tamu',
            'due_date' => now()->addDays(5),
            'status' => 'pending',
        ]);

        $this->get(route('reminders.index'))->assertRedirect(route('login'));
        $this->get(route('reminders.create'))->assertRedirect(route('login'));
        $this->post(route('reminders.store'), [])->assertRedirect(route('login'));
        $this->get(route('reminders.show', $reminder))->assertRedirect(route('login'));
        $this->get(route('reminders.edit', $reminder))->assertRedirect(route('login'));
        $this->put(route('reminders.update', $reminder), [])->assertRedirect(route('login'));
        $this->delete(route('reminders.destroy', $reminder))->assertRedirect(route('login'));
        $this->post(route('reminders.toggle', $reminder))->assertRedirect(route('login'));
    }

    /**
     * Memastikan pengguna login dapat melihat halaman daftar pengingat.
     */
    public function test_authenticated_user_can_view_reminders_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reminders.index'));

        $response->assertStatus(200);
        $response->assertSee('Jadwal Pengingat');
        $response->assertSee('Belum Ada Pengingat');
    }

    /**
     * Memastikan halaman tambah pengingat dapat diakses tanpa query parameter (preselectedDocId bernilai null).
     */
    public function test_authenticated_user_can_view_reminders_create_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reminders.create'));

        $response->assertStatus(200);
        $response->assertSee('Jadwalkan Pengingat Baru');
        $response->assertSee('-- Tanpa Dokumen Terkait --');
    }

    /**
     * Memastikan form tambah pengingat dapat melakukan pre-select dokumen jika document_id valid milik pengguna diberikan di query.
     */
    public function test_authenticated_user_can_view_create_form_with_preselected_document(): void
    {
        $user = User::factory()->create();
        $document = Document::create([
            'user_id' => $user->id,
            'title' => 'Paspor Republik Indonesia',
            'original_filename' => 'paspor.pdf',
            'storage_path' => 'documents/' . $user->id . '/paspor.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 150000,
            'expiry_date' => now()->addDays(60),
        ]);

        $response = $this->actingAs($user)->get(route('reminders.create', ['document_id' => $document->id]));

        $response->assertStatus(200);
        $response->assertSee('Paspor Republik Indonesia');
        $response->assertSee('selected');
        $response->assertSee('Perpanjang Paspor Republik Indonesia');
    }

    /**
     * Memastikan jika document_id milik pengguna lain diberikan pada query URL create, dokumen tersebut diabaikan secara aman.
     */
    public function test_foreign_document_id_in_create_query_is_safely_ignored(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $docA = Document::create([
            'user_id' => $userA->id,
            'title' => 'Dokumen Privat User Lain',
            'original_filename' => 'rahasia.pdf',
            'storage_path' => 'documents/' . $userA->id . '/rahasia.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100000,
        ]);

        $response = $this->actingAs($userB)->get(route('reminders.create', ['document_id' => $docA->id]));

        $response->assertStatus(200);
        $response->assertDontSee('Dokumen Privat User Lain');
    }

    /**
     * Memastikan pengguna login dapat membuat pengingat mandiri (tanpa dokumen).
     */
    public function test_authenticated_user_can_create_standalone_reminder(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reminders.store'), [
            'title' => 'Bayar Pajak Kendaraan Bermotor',
            'due_date' => now()->addDays(14)->format('Y-m-d'),
            'description' => 'Siapkan KTP asli dan STNK',
        ]);

        $reminder = Reminder::where('user_id', $user->id)->first();
        $this->assertNotNull($reminder);
        $this->assertEquals('Bayar Pajak Kendaraan Bermotor', $reminder->title);
        $this->assertNull($reminder->document_id);
        $this->assertEquals('pending', $reminder->status);
        $this->assertEquals('Siapkan KTP asli dan STNK', $reminder->description);

        $response->assertRedirect(route('reminders.index'));
    }

    /**
     * Memastikan pengguna login dapat membuat pengingat yang ditautkan ke dokumen miliknya.
     */
    public function test_authenticated_user_can_create_reminder_linked_to_their_document(): void
    {
        $user = User::factory()->create();
        $document = Document::create([
            'user_id' => $user->id,
            'title' => 'SIM A Nasional',
            'original_filename' => 'sim_a.pdf',
            'storage_path' => 'documents/' . $user->id . '/sim_a.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 120000,
            'document_date' => now()->subYear(),
            'expiry_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($user)->post(route('reminders.store'), [
            'title' => 'Perpanjang SIM A',
            'document_id' => $document->id,
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'description' => 'Perpanjang di gerai SIM keliling',
        ]);

        $reminder = Reminder::where('user_id', $user->id)->first();
        $this->assertNotNull($reminder);
        $this->assertEquals('Perpanjang SIM A', $reminder->title);
        $this->assertEquals($document->id, $reminder->document_id);

        $response->assertRedirect(route('reminders.index'));
    }

    /**
     * Memastikan pengguna tidak dapat menautkan pengingat ke dokumen milik pengguna lain.
     */
    public function test_user_cannot_link_reminder_to_another_users_document(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $docA = Document::create([
            'user_id' => $userA->id,
            'title' => 'Dokumen Privat User A',
            'original_filename' => 'privat_a.pdf',
            'storage_path' => 'documents/' . $userA->id . '/privat_a.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 50000,
        ]);

        $response = $this->actingAs($userB)->post(route('reminders.store'), [
            'title' => 'Upaya Tautan Ilegal',
            'document_id' => $docA->id,
            'due_date' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('document_id');
        $this->assertDatabaseMissing('reminders', [
            'user_id' => $userB->id,
            'title' => 'Upaya Tautan Ilegal',
        ]);
    }

    /**
     * Memastikan pengguna dapat melihat detail pengingat miliknya.
     */
    public function test_authenticated_user_can_view_their_reminder(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Perpanjang Asuransi Rumah',
            'due_date' => now()->addDays(15),
            'status' => 'pending',
            'description' => 'Hubungi agen polis',
        ]);

        $response = $this->actingAs($user)->get(route('reminders.show', $reminder));

        $response->assertStatus(200);
        $response->assertSee('Perpanjang Asuransi Rumah');
        $response->assertSee('Hubungi agen polis');
    }

    /**
     * Memastikan pengguna dapat memperbarui pengingat miliknya.
     */
    public function test_authenticated_user_can_update_their_reminder(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Judul Lama',
            'due_date' => now()->addDays(5),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->put(route('reminders.update', $reminder), [
            'title' => 'Judul Baru Diperbarui',
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'completed',
            'description' => 'Catatan penyelesaian',
        ]);

        $reminder->refresh();
        $this->assertEquals('Judul Baru Diperbarui', $reminder->title);
        $this->assertEquals('completed', $reminder->status);
        $this->assertEquals('Catatan penyelesaian', $reminder->description);

        $response->assertRedirect(route('reminders.show', $reminder));
    }

    /**
     * Memastikan pengguna dapat menghapus pengingat miliknya.
     */
    public function test_authenticated_user_can_delete_their_reminder(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Pengingat untuk Dihapus',
            'due_date' => now()->addDays(10),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->delete(route('reminders.destroy', $reminder));

        $this->assertDatabaseMissing('reminders', [
            'id' => $reminder->id,
        ]);

        $response->assertRedirect(route('reminders.index'));
    }

    /**
     * Memastikan pengguna lain tidak dapat melihat, mengubah, atau menghapus pengingat orang lain (Policy Protection).
     */
    public function test_user_cannot_access_or_modify_another_users_reminder(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $reminderA = Reminder::create([
            'user_id' => $userA->id,
            'title' => 'Pengingat Rahasia User A',
            'due_date' => now()->addDays(10),
            'status' => 'pending',
        ]);

        // User B coba melihat pengingat User A
        $this->actingAs($userB)->get(route('reminders.show', $reminderA))->assertStatus(403);

        // User B coba mengakses form edit
        $this->actingAs($userB)->get(route('reminders.edit', $reminderA))->assertStatus(403);

        // User B coba mengupdate data
        $this->actingAs($userB)->put(route('reminders.update', $reminderA), [
            'title' => 'Diretas oleh User B',
            'due_date' => now()->addDays(2)->format('Y-m-d'),
            'status' => 'completed',
        ])->assertStatus(403);

        // User B coba toggle status
        $this->actingAs($userB)->post(route('reminders.toggle', $reminderA))->assertStatus(403);

        // User B coba menghapus
        $this->actingAs($userB)->delete(route('reminders.destroy', $reminderA))->assertStatus(403);

        // Pastikan data asli milik User A tidak tersentuh
        $reminderA->refresh();
        $this->assertEquals('Pengingat Rahasia User A', $reminderA->title);
        $this->assertEquals('pending', $reminderA->status);
    }

    /**
     * Memastikan aksi toggle mengubah status antara selesai (completed) dan aktif (pending).
     */
    public function test_user_can_toggle_reminder_status(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Pengingat Toggle',
            'due_date' => now()->addDays(3),
            'status' => 'pending',
        ]);

        // Toggle pertama: pending -> completed
        $response1 = $this->actingAs($user)->post(route('reminders.toggle', $reminder));
        $reminder->refresh();
        $this->assertEquals('completed', $reminder->status);
        $response1->assertRedirect();

        // Toggle kedua: completed -> pending
        $response2 = $this->actingAs($user)->post(route('reminders.toggle', $reminder));
        $reminder->refresh();
        $this->assertEquals('pending', $reminder->status);
        $response2->assertRedirect();
    }

    /**
     * Memastikan filter status dan pencarian judul berfungsi dengan akurat.
     */
    public function test_reminders_status_filtering_and_search(): void
    {
        $user = User::factory()->create();

        // 1. Jatuh tempo hari ini
        Reminder::create([
            'user_id' => $user->id,
            'title' => 'Pengingat Hari Ini',
            'due_date' => today(),
            'status' => 'pending',
        ]);

        // 2. Terlambat (kemarin)
        Reminder::create([
            'user_id' => $user->id,
            'title' => 'Pengingat Terlambat',
            'due_date' => today()->subDays(2),
            'status' => 'pending',
        ]);

        // 3. Mendatang (10 hari lagi)
        Reminder::create([
            'user_id' => $user->id,
            'title' => 'Pengingat Mendatang KTP',
            'due_date' => today()->addDays(10),
            'status' => 'pending',
        ]);

        // 4. Selesai
        Reminder::create([
            'user_id' => $user->id,
            'title' => 'Pengingat Sudah Selesai',
            'due_date' => today()->addDays(5),
            'status' => 'completed',
        ]);

        // Uji filter hari ini
        $respToday = $this->actingAs($user)->get(route('reminders.index', ['status' => 'today']));
        $respToday->assertSee('Pengingat Hari Ini');
        $respToday->assertDontSee('Pengingat Mendatang KTP');

        // Uji filter terlambat
        $respOverdue = $this->actingAs($user)->get(route('reminders.index', ['status' => 'overdue']));
        $respOverdue->assertSee('Pengingat Terlambat');
        $respOverdue->assertDontSee('Pengingat Hari Ini');

        // Uji filter selesai
        $respCompleted = $this->actingAs($user)->get(route('reminders.index', ['status' => 'completed']));
        $respCompleted->assertSee('Pengingat Sudah Selesai');
        $respCompleted->assertDontSee('Pengingat Hari Ini');

        // Uji pencarian kata kunci
        $respSearch = $this->actingAs($user)->get(route('reminders.index', ['q' => 'KTP']));
        $respSearch->assertSee('Pengingat Mendatang KTP');
        $respSearch->assertDontSee('Pengingat Terlambat');
    }

    /**
     * Memastikan penghapusan dokumen tidak menghapus pengingat, melainkan mengubah document_id menjadi null.
     */
    public function test_document_deletion_safely_nullifies_document_id_on_reminder(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $document = Document::create([
            'user_id' => $user->id,
            'title' => 'Polis Mobil',
            'original_filename' => 'polis_mobil.pdf',
            'storage_path' => 'documents/' . $user->id . '/polis_mobil.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 80000,
            'expiry_date' => now()->addMonths(6),
        ]);

        $reminder = Reminder::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'title' => 'Klaim Asuransi Mobil',
            'due_date' => now()->addMonths(6),
            'status' => 'pending',
        ]);

        $this->assertEquals($document->id, $reminder->document_id);

        // Hapus dokumen via DocumentController
        $this->actingAs($user)->delete(route('documents.destroy', $document));

        // Pastikan dokumen telah terhapus
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);

        // Pastikan pengingat tetap ada dan document_id berubah jadi null
        $reminder->refresh();
        $this->assertNotNull($reminder);
        $this->assertNull($reminder->document_id);
        $this->assertEquals('Klaim Asuransi Mobil', $reminder->title);
    }

    /**
     * Memastikan automated reminder dispatch mencatat notifikasi dan tidak pernah mengirim notifikasi duplikat.
     */
    public function test_scheduler_sends_notifications_without_duplicates(): void
    {
        $user = User::factory()->create();

        // Buat pengingat dengan batas jatuh tempo tepat 7 hari ke depan
        $reminder7 = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Pengingat H-7',
            'due_date' => today()->addDays(7),
            'status' => 'pending',
        ]);

        // Buat pengingat dengan batas jatuh tempo tepat 30 hari ke depan
        $reminder30 = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Pengingat H-30',
            'due_date' => today()->addDays(30),
            'status' => 'pending',
        ]);

        // Pengingat yang sudah selesai (tidak boleh dikirimi notifikasi)
        $reminderCompleted = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Pengingat Selesai',
            'due_date' => today()->addDays(7),
            'status' => 'completed',
        ]);

        $service = app(ReminderNotificationService::class);

        // Eksekusi pertama: harus memproses 2 notifikasi (H-7 dan H-30)
        $firstResult = $service->processDueReminders();
        $this->assertEquals(2, $firstResult['sent']);

        $this->assertDatabaseHas('reminder_notifications', [
            'reminder_id' => $reminder7->id,
            'interval_days' => 7,
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('reminder_notifications', [
            'reminder_id' => $reminder30->id,
            'interval_days' => 30,
            'status' => 'sent',
        ]);

        $this->assertDatabaseMissing('reminder_notifications', [
            'reminder_id' => $reminderCompleted->id,
        ]);

        // Eksekusi kedua pada hari yang sama: TIDAK BOLEH ada notifikasi baru (0 duplikat)
        $secondResult = $service->processDueReminders();
        $this->assertEquals(0, $secondResult['sent']);
        $this->assertEquals(2, $secondResult['skipped']);

        // Total catatan di tabel reminder_notifications tetap tepat 2
        $this->assertEquals(2, ReminderNotification::count());

        // Uji juga via Artisan console command `simpera:send-reminders`
        $this->artisan('simpera:send-reminders')
            ->expectsOutputToContain('pemeriksaan pengingat jatuh tempo')
            ->assertExitCode(0);

        // Catatan tetap tidak bertambah
        $this->assertEquals(2, ReminderNotification::count());
    }
}
