<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\BackupService;
use App\Services\RestoreService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;
use Tests\TestCase;

class BackupRestoreTest extends TestCase
{
    protected string $testBackupDir;
    protected string $testDbPath;
    protected string $originalDbPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat folder backup dan database SQLite sementara khusus pengujian (isolasi penuh dari produksi)
        $this->testBackupDir = storage_path('framework/testing/test_backups_' . uniqid());
        File::makeDirectory($this->testBackupDir, 0750, true, true);

        $this->originalDbPath = Config::get('database.connections.sqlite.database', '');

        $this->testDbPath = $this->testBackupDir . DIRECTORY_SEPARATOR . 'test_database.sqlite';
        touch($this->testDbPath);

        Config::set('database.connections.sqlite.database', $this->testDbPath);
        Config::set('backup.path', $this->testBackupDir);

        DB::purge('sqlite');
        $this->artisan('migrate', ['--database' => 'sqlite']);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        Config::set('database.connections.sqlite.database', $this->originalDbPath);
        DB::purge('sqlite');

        if (File::exists($this->testBackupDir)) {
            File::deleteDirectory($this->testBackupDir);
        }

        parent::tearDown();
    }

    /**
     * Memastikan backup database SQLite berhasil dan menghasilkan file yang valid serta lulus uji integritas.
     */
    public function test_backup_database_succeeds_and_passes_integrity_check(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        Document::create([
            'user_id' => $user->id,
            'title' => 'Dokumen Tes Integritas',
            'original_filename' => 'surat.pdf',
            'storage_path' => 'documents/1/surat.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        Storage::disk('local')->put('documents/1/surat.pdf', 'Isi berkas dokumen tes');

        $backupService = app(BackupService::class);
        $result = $backupService->createBackup($this->testBackupDir, 'Tes cadangan database');

        $this->assertTrue($result['success']);
        $this->assertDirectoryExists($result['backup_path']);

        $dbFile = $result['backup_path'] . DIRECTORY_SEPARATOR . 'database.sqlite';
        $this->assertFileExists($dbFile);
        $this->assertGreaterThan(0, filesize($dbFile));

        // Uji PRAGMA integrity_check pada database hasil backup
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $integrity = $pdo->query('PRAGMA integrity_check;')->fetchColumn();
        $this->assertEquals('ok', $integrity);

        // Verifikasi manifest
        $manifestFile = $result['backup_path'] . DIRECTORY_SEPARATOR . 'manifest.json';
        $this->assertFileExists($manifestFile);
        $manifest = json_decode(File::get($manifestFile), true);
        $this->assertEquals('verified', $manifest['status']);
        $this->assertEquals('ok', $manifest['database']['integrity_check']);
    }

    /**
     * Memastikan backup file privat dokumen mempertahankan struktur direktori dan isi berkas.
     */
    public function test_backup_private_files_succeeds_and_preserves_relative_structure(): void
    {
        Storage::fake('local');

        $relPath = 'documents/99/surat_rahasia.pdf';
        $content = 'Konten dokumen privat yang sangat rahasia untuk Document Vault';
        Storage::disk('local')->put($relPath, $content);

        $backupService = app(BackupService::class);
        $result = $backupService->createBackup($this->testBackupDir);

        $targetFile = $result['backup_path'] . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
        $this->assertFileExists($targetFile);
        $this->assertEquals($content, file_get_contents($targetFile));

        // Pastikan berkas sumber asli tidak dipindahkan atau dihapus
        $this->assertTrue(Storage::disk('local')->exists($relPath));
    }

    /**
     * Memastikan nama backup selalu unik dan bertanggal.
     */
    public function test_backup_names_are_unique_and_timestamped(): void
    {
        Storage::fake('local');

        $backupService = app(BackupService::class);

        $backup1 = $backupService->createBackup($this->testBackupDir);
        $backup2 = $backupService->createBackup($this->testBackupDir);

        $this->assertNotEquals($backup1['backup_id'], $backup2['backup_id']);
        $this->assertNotEquals($backup1['backup_path'], $backup2['backup_path']);
        $this->assertStringStartsWith('simpera_backup_', $backup1['backup_id']);
        $this->assertStringStartsWith('simpera_backup_', $backup2['backup_id']);
    }

    /**
     * Memastikan backup yang rusak ditolak oleh verifikator dan restore.
     */
    public function test_corrupted_backup_is_rejected(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('documents/1/foto.png', 'konten gambar');

        $backupService = app(BackupService::class);
        $result = $backupService->createBackup($this->testBackupDir);

        $restoreService = app(RestoreService::class);

        // Verifikasi awal harus valid
        $verifyBefore = $restoreService->verifyBackup($result['backup_id'], $this->testBackupDir);
        $this->assertTrue($verifyBefore['valid']);

        // Rusak file dokumen hasil backup
        $backedFile = $result['backup_path'] . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . '1' . DIRECTORY_SEPARATOR . 'foto.png';
        File::put($backedFile, 'konten yang sudah diubah secara ilegal / korup');

        $verifyAfter = $restoreService->verifyBackup($result['backup_id'], $this->testBackupDir);
        $this->assertFalse($verifyAfter['valid']);
        $this->assertNotEmpty($verifyAfter['errors']);

        // Restore ke staging harus gagal dan melemparkan exception
        $this->expectException(RuntimeException::class);
        $restoreService->restoreToStaging($result['backup_id'], null, $this->testBackupDir);
    }

    /**
     * Memastikan pemulihan ke direktori staging tidak mengubah database aktif atau berkas aktif.
     */
    public function test_restore_to_staging_does_not_modify_active_database_or_files(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['name' => 'Nama Sebelum Backup']);
        Document::create([
            'user_id' => $user->id,
            'title' => 'Judul Awal Dokumen',
            'original_filename' => 'awal.pdf',
            'storage_path' => 'documents/1/awal.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
        ]);
        Storage::disk('local')->put('documents/1/awal.pdf', 'Dokumen Awal Asli');

        // Lakukan backup data awal
        $backupService = app(BackupService::class);
        $backupResult = $backupService->createBackup($this->testBackupDir);

        // Modifikasi data aktif produksi setelah backup dibuat
        $user->update(['name' => 'Nama Setelah Diubah Aktif']);
        Storage::disk('local')->put('documents/1/awal.pdf', 'Dokumen Aktif yang Sudah Dimutakhirkan');

        // Jalankan restore ke staging
        $restoreService = app(RestoreService::class);
        $stagingResult = $restoreService->restoreToStaging($backupResult['backup_id'], null, $this->testBackupDir);

        $this->assertTrue($stagingResult['success']);
        $this->assertDirectoryExists($stagingResult['staging_path']);

        // Pastikan data aktif produksi TIDAK BERUBAH sama sekali
        $this->assertEquals('Nama Setelah Diubah Aktif', $user->fresh()->name);
        $this->assertEquals('Dokumen Aktif yang Sudah Dimutakhirkan', Storage::disk('local')->get('documents/1/awal.pdf'));

        // Dan pastikan berkas di staging memuat data sebelum diubah
        $stagingDoc = $stagingResult['staging_path'] . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . '1' . DIRECTORY_SEPARATOR . 'awal.pdf';
        $this->assertFileExists($stagingDoc);
        $this->assertEquals('Dokumen Awal Asli', file_get_contents($stagingDoc));

        // Bersihkan folder staging pengujian
        File::deleteDirectory($stagingResult['staging_path']);
    }

    /**
     * Memastikan direktori backup di dalam public web root ditolak demi keamanan.
     */
    public function test_backup_in_public_web_root_is_strictly_rejected(): void
    {
        $backupService = app(BackupService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Lokasi cadangan DITOLAK: Tidak boleh berada di dalam folder public web root.');

        $backupService->createBackup(public_path('insecure_backups'));
    }

    /**
     * Memastikan kegagalan backup membersihkan folder sementara (.tmp_*) dan ditangani dengan rapi.
     */
    public function test_backup_failure_cleans_up_temporary_folders(): void
    {
        $backupService = app(BackupService::class);

        // Gunakan path file sebagai folder tujuan agar memicu error pembuatan direktori
        $dummyFile = $this->testBackupDir . DIRECTORY_SEPARATOR . 'blocker.txt';
        File::put($dummyFile, 'bukan direktori');

        $invalidTarget = $dummyFile . DIRECTORY_SEPARATOR . 'subfolder';

        try {
            $backupService->createBackup($invalidTarget);
            $this->fail('Harus gagal karena path tujuan tidak valid.');
        } catch (RuntimeException $e) {
            $this->assertTrue(
                str_contains($e->getMessage(), 'Gagal membuat direktori cadangan') ||
                str_contains($e->getMessage(), 'tidak memiliki izin tulis')
            );
        }

        // Pastikan tidak ada berkas .tmp_* tertinggal di folder induk
        $tempDirs = File::glob($this->testBackupDir . DIRECTORY_SEPARATOR . '.tmp_*');
        $this->assertEmpty($tempDirs);
    }

    /**
     * Memastikan kebijakan retensi hanya menghapus backup lama setelah backup baru sukses terverifikasi.
     */
    public function test_retention_policy_maintains_configured_limit(): void
    {
        Storage::fake('local');

        $backupService = app(BackupService::class);

        // Buat 3 backup berturut-turut dengan keep = 2
        $backupService->createBackup($this->testBackupDir, 'B1', 2);
        usleep(100000); // jeda 100ms
        $backupService->createBackup($this->testBackupDir, 'B2', 2);
        usleep(100000);
        $b3 = $backupService->createBackup($this->testBackupDir, 'B3', 2);

        $restoreService = app(RestoreService::class);
        $list = $restoreService->listBackups($this->testBackupDir);

        $this->assertCount(2, $list);
        $this->assertEquals($b3['backup_id'], $list[0]['backup_id']);
    }

    /**
     * Memastikan seluruh Artisan command (backup, list, verify, restore staging) berjalan dengan benar.
     */
    public function test_artisan_commands_run_successfully(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/1/nota.pdf', 'konten nota');

        // 1. simpera:backup
        $this->artisan('simpera:backup', [
            '--destination' => $this->testBackupDir,
            '--notes' => 'Backup via Artisan Test',
        ])
            ->expectsOutputToContain('Cadangan berhasil dibuat dan diverifikasi 100%!')
            ->assertSuccessful();

        $restoreService = app(RestoreService::class);
        $list = $restoreService->listBackups($this->testBackupDir);
        $this->assertCount(1, $list);
        $backupId = $list[0]['backup_id'];

        // 2. simpera:backup-list
        $this->artisan('simpera:backup-list', [
            '--destination' => $this->testBackupDir,
        ])
            ->expectsOutputToContain($backupId)
            ->assertSuccessful();

        // 3. simpera:backup-verify
        $this->artisan('simpera:backup-verify', [
            'backup_id' => $backupId,
            '--destination' => $this->testBackupDir,
        ])
            ->expectsOutputToContain('VALID dan UTUH')
            ->assertSuccessful();

        // 4. simpera:restore (staging mode default)
        $this->artisan('simpera:restore', [
            'backup_id' => $backupId,
            '--destination' => $this->testBackupDir,
        ])
            ->expectsOutputToContain('Pemulihan ke direktori staging berhasil 100%!')
            ->assertSuccessful();
    }
}
