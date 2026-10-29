<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memastikan pengguna tanpa login diarahkan ke halaman login saat mengakses modul dokumen.
     */
    public function test_unauthenticated_user_cannot_access_documents(): void
    {
        $this->get(route('documents.index'))->assertRedirect(route('login'));
        $this->get(route('documents.create'))->assertRedirect(route('login'));
        $this->post(route('documents.store'), [])->assertRedirect(route('login'));
    }

    /**
     * Memastikan pengguna login dapat mengunggah berkas PDF valid dan tersimpan di private storage.
     */
    public function test_authenticated_user_can_upload_valid_pdf_document(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('ktp_digital.pdf', 1500, 'application/pdf');

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'Kartu Tanda Penduduk',
            'file' => $file,
            'new_category' => 'Identitas Pribadi',
            'document_date' => '2026-01-15',
            'expiry_date' => '2030-01-15',
            'is_favorite' => 1,
        ]);

        $document = Document::where('user_id', $user->id)->first();
        $this->assertNotNull($document);
        $this->assertEquals('Kartu Tanda Penduduk', $document->title);
        $this->assertEquals('ktp_digital.pdf', $document->original_filename);
        $this->assertEquals('application/pdf', $document->mime_type);
        $this->assertTrue($document->is_favorite);

        // Pastikan berkas tersimpan pada disk lokal privat
        Storage::disk('local')->assertExists($document->storage_path);

        $response->assertRedirect(route('documents.show', $document));
    }

    /**
     * Memastikan pengguna login dapat mengunggah gambar JPG/PNG valid.
     */
    public function test_authenticated_user_can_upload_valid_image_document(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('kartu_sim.jpg', 800, 600)->size(500);

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'SIM A Aktif',
            'file' => $file,
            'new_category' => 'Kendaraan',
        ]);

        $document = Document::where('user_id', $user->id)->where('title', 'SIM A Aktif')->first();
        $this->assertNotNull($document);
        $this->assertTrue($document->isImage());
        Storage::disk('local')->assertExists($document->storage_path);

        $response->assertRedirect(route('documents.show', $document));
    }

    /**
     * Memastikan upload berkas dengan tipe terlarang (misal: .exe atau .txt) ditolak validasi.
     */
    public function test_uploading_invalid_file_type_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $forbiddenFile = UploadedFile::fake()->create('skrip_berbahaya.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'File Berbahaya',
            'file' => $forbiddenFile,
        ]);

        $response->assertSessionHasErrors(['file']);
        $this->assertEquals(0, Document::count());
    }

    /**
     * Memastikan upload berkas yang melebihi batas 10 MB ditolak validasi.
     */
    public function test_uploading_file_exceeding_10mb_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        // 11 MB = 11264 KB
        $oversizedFile = UploadedFile::fake()->create('file_besar.pdf', 11264, 'application/pdf');

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'File Terlalu Besar',
            'file' => $oversizedFile,
        ]);

        $response->assertSessionHasErrors(['file']);
        $this->assertEquals(0, Document::count());
    }

    /**
     * Memastikan Pengguna A TIDAK BISA melihat detail dokumen Pengguna B (Policy: 403 Forbidden).
     */
    public function test_user_cannot_view_another_users_document(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $documentA = Document::create([
            'user_id' => $userA->id,
            'title' => 'Dokumen Rahasia User A',
            'original_filename' => 'rahasia.pdf',
            'storage_path' => 'documents/' . $userA->id . '/rahasia.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
        ]);

        // User B mencoba membuka dokumen User A
        $response = $this->actingAs($userB)->get(route('documents.show', $documentA));
        $response->assertStatus(403);
    }

    /**
     * Memastikan Pengguna A TIDAK BISA mengunduh berkas Pengguna B (Policy: 403 Forbidden).
     */
    public function test_user_cannot_download_another_users_document(): void
    {
        Storage::fake('local');

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $path = 'documents/' . $userA->id . '/fileA.pdf';
        Storage::disk('local')->put($path, 'Konten Privat User A');

        $documentA = Document::create([
            'user_id' => $userA->id,
            'title' => 'Polis Rahasia User A',
            'original_filename' => 'fileA.pdf',
            'storage_path' => $path,
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        // User B mencoba mengunduh berkas User A
        $response = $this->actingAs($userB)->get(route('documents.download', $documentA));
        $response->assertStatus(403);

        // User A sendiri dapat mengunduh berkasnya
        $responseA = $this->actingAs($userA)->get(route('documents.download', $documentA));
        $responseA->assertStatus(200);
    }

    /**
     * Memastikan Pengguna A TIDAK BISA melihat pratinjau berkas Pengguna B (Policy: 403 Forbidden).
     */
    public function test_user_cannot_preview_another_users_document(): void
    {
        Storage::fake('local');

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $path = 'documents/' . $userA->id . '/fileA.pdf';
        Storage::disk('local')->put($path, 'Konten Privat User A');

        $documentA = Document::create([
            'user_id' => $userA->id,
            'title' => 'Pratinjau Rahasia',
            'original_filename' => 'fileA.pdf',
            'storage_path' => $path,
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        // User B mencoba pratinjau
        $this->actingAs($userB)->get(route('documents.preview', $documentA))->assertStatus(403);

        // User A sukses membuka pratinjau
        $this->actingAs($userA)->get(route('documents.preview', $documentA))->assertStatus(200);
    }

    /**
     * Memastikan Pengguna A TIDAK BISA mengedit atau menghapus dokumen Pengguna B.
     */
    public function test_user_cannot_edit_or_delete_another_users_document(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $documentA = Document::create([
            'user_id' => $userA->id,
            'title' => 'Asuransi Mobil User A',
            'original_filename' => 'mobil.pdf',
            'storage_path' => 'documents/' . $userA->id . '/mobil.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        // User B mencoba akses form edit
        $this->actingAs($userB)->get(route('documents.edit', $documentA))->assertStatus(403);

        // User B mencoba mengirim request update
        $this->actingAs($userB)->put(route('documents.update', $documentA), [
            'title' => 'Judul Dibajak',
        ])->assertStatus(403);

        // User B mencoba menghapus dokumen
        $this->actingAs($userB)->delete(route('documents.destroy', $documentA))->assertStatus(403);

        // Verifikasi dokumen User A tetap utuh
        $this->assertDatabaseHas('documents', ['id' => $documentA->id, 'title' => 'Asuransi Mobil User A']);
    }

    /**
     * Memastikan pencarian dan filter tidak membocorkan dokumen milik pengguna lain.
     */
    public function test_search_and_filters_do_not_leak_other_users_documents(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $commonKeyword = 'Sertifikat Tanah';

        // Dokumen User A
        Document::create([
            'user_id' => $userA->id,
            'title' => $commonKeyword . ' Rumah Jakarta',
            'original_filename' => 'tanah_jkt.pdf',
            'storage_path' => 'path_a',
            'mime_type' => 'application/pdf',
            'file_size' => 1000,
        ]);

        // Dokumen User B dengan kata kunci yang sama persis
        Document::create([
            'user_id' => $userB->id,
            'title' => $commonKeyword . ' Ruko Surabaya',
            'original_filename' => 'tanah_sby.pdf',
            'storage_path' => 'path_b',
            'mime_type' => 'application/pdf',
            'file_size' => 1000,
        ]);

        // User A mencari "Sertifikat Tanah"
        $responseA = $this->actingAs($userA)->get(route('documents.index', ['q' => $commonKeyword]));
        $responseA->assertStatus(200);
        $responseA->assertSee('Rumah Jakarta');
        $responseA->assertDontSee('Ruko Surabaya'); // Milik User B tidak boleh bocor!

        // User B mencari "Sertifikat Tanah"
        $responseB = $this->actingAs($userB)->get(route('documents.index', ['q' => $commonKeyword]));
        $responseB->assertStatus(200);
        $responseB->assertSee('Ruko Surabaya');
        $responseB->assertDontSee('Rumah Jakarta'); // Milik User A tidak boleh bocor!
    }

    /**
     * Memastikan penghapusan dokumen menghapus data di database dan berkas di storage privat.
     */
    public function test_document_deletion_removes_record_and_physical_storage_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $path = 'documents/' . $user->id . '/berkas_dihapus.pdf';
        Storage::disk('local')->put($path, 'Konten yang akan dihapus');

        $document = Document::create([
            'user_id' => $user->id,
            'title' => 'Dokumen Sementara',
            'original_filename' => 'berkas_dihapus.pdf',
            'storage_path' => $path,
            'mime_type' => 'application/pdf',
            'file_size' => 500,
        ]);

        $this->assertDatabaseHas('documents', ['id' => $document->id]);
        Storage::disk('local')->assertExists($path);

        $response = $this->actingAs($user)->delete(route('documents.destroy', $document));

        $response->assertRedirect(route('documents.index'));
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($path);
    }

    /**
     * Memastikan toggle status favorit dokumen berfungsi.
     */
    public function test_user_can_toggle_favorite_status(): void
    {
        $user = User::factory()->create();

        $document = Document::create([
            'user_id' => $user->id,
            'title' => 'Paspor RI',
            'original_filename' => 'paspor.pdf',
            'storage_path' => 'path_paspor',
            'mime_type' => 'application/pdf',
            'file_size' => 1000,
            'is_favorite' => false,
        ]);

        $this->actingAs($user)->post(route('documents.favorite', $document));
        $this->assertTrue($document->fresh()->is_favorite);

        $this->actingAs($user)->post(route('documents.favorite', $document));
        $this->assertFalse($document->fresh()->is_favorite);
    }
}
