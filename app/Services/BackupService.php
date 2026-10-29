<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Throwable;

class BackupService
{
    /**
     * Membuat paket cadangan (database dan file dokumen privat) secara aman dan konsisten.
     *
     * @param string|null $customDestination Direktori tujuan kustom (opsional)
     * @param string|null $notes Catatan administratif cadangan (opsional)
     * @param int|null $customKeep Jumlah retensi kustom (opsional)
     * @return array Ringkasan hasil backup
     * @throws RuntimeException Jika terjadi kesalahan validasi atau kegagalan cadangan
     */
    public function createBackup(?string $customDestination = null, ?string $notes = null, ?int $customKeep = null): array
    {
        $backupRoot = $this->resolveAndValidateDestination($customDestination);
        $keepBackups = $customKeep !== null ? $customKeep : (int) Config::get('backup.keep_backups', 0);

        // Buat identitas backup unik bertanggal
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $randomSuffix = Str::lower(Str::random(8));
        $backupId = "simpera_backup_{$timestamp}_{$randomSuffix}";

        // Gunakan direktori sementara selama proses pembuatan agar tidak ada backup parsial/rusak
        $tempDir = $backupRoot . DIRECTORY_SEPARATOR . ".tmp_{$backupId}";
        $finalDir = $backupRoot . DIRECTORY_SEPARATOR . $backupId;

        if (File::exists($tempDir)) {
            File::deleteDirectory($tempDir);
        }
        File::makeDirectory($tempDir, 0750, true, true);

        try {
            // 1. Cadangkan Database SQLite secara konsisten
            $dbMetadata = $this->backupSqliteDatabase($tempDir);

            // 2. Cadangkan Berkas Dokumen Privat
            $documentsMetadata = $this->backupPrivateDocuments($tempDir);

            // 3. Verifikasi korelasi antara database hasil cadangan dan berkas fisik
            $crossCheck = $this->crossCheckDatabaseAndFiles($tempDir, $documentsMetadata['files']);

            // 4. Susun Manifest Metadata Cadangan
            $manifest = [
                'backup_id' => $backupId,
                'version' => '1.0',
                'created_at' => Carbon::now()->format('Y-m-d\TH:i:s.uP'),
                'notes' => $notes,
                'database' => $dbMetadata,
                'documents' => [
                    'count' => count($documentsMetadata['files']),
                    'total_size' => $documentsMetadata['total_size'],
                    'files' => $documentsMetadata['files'],
                    'missing_in_storage' => $crossCheck['missing_in_storage'],
                ],
                'environment' => [
                    'php_version' => PHP_VERSION,
                    'sqlite_version' => $this->getSqliteVersion(),
                    'laravel_version' => app()->version(),
                ],
                'status' => 'verified',
            ];

            // Tulis manifest ke file JSON di direktori cadangan
            $manifestPath = $tempDir . DIRECTORY_SEPARATOR . 'manifest.json';
            File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // Verifikasi integritas manifest yang baru ditulis
            if (! File::exists($manifestPath) || json_decode(File::get($manifestPath), true) === null) {
                throw new RuntimeException('Gagal menulis atau memverifikasi berkas manifest cadangan.');
            }

            // 5. Finalisasi: Ganti nama direktori dari sementara (.tmp_*) menjadi final
            if (! @rename($tempDir, $finalDir)) {
                // Fallback copy & delete jika rename direct gagal
                File::copyDirectory($tempDir, $finalDir);
                File::deleteDirectory($tempDir);
            }

            // 6. Jalankan pembersihan retensi jika dikonfigurasi
            $purgedCount = 0;
            if ($keepBackups > 0) {
                $purgedCount = $this->applyRetentionPolicy($backupRoot, $keepBackups);
            }

            return [
                'success' => true,
                'backup_id' => $backupId,
                'backup_path' => $finalDir,
                'database_size' => $dbMetadata['size'],
                'database_sha256' => $dbMetadata['sha256'],
                'documents_count' => count($documentsMetadata['files']),
                'documents_total_size' => $documentsMetadata['total_size'],
                'created_at' => $manifest['created_at'],
                'purged_old_backups' => $purgedCount,
            ];
        } catch (Throwable $e) {
            // Bersihkan direktori sementara jika terjadi kesalahan
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }

            Log::error('Gagal menjalankan proses cadangan Simpera: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            throw new RuntimeException("Proses cadangan Simpera gagal: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Memvalidasi dan memastikan lokasi tujuan backup aman dan valid.
     */
    public function resolveAndValidateDestination(?string $customDestination = null): string
    {
        $destination = $customDestination ?: Config::get('backup.path', 'D:\Backup_Simpera');
        $destination = rtrim($destination, '\\/');

        if (empty($destination)) {
            throw new RuntimeException('Lokasi tujuan cadangan belum ditentukan.');
        }

        // Cegah penyimpanan di dalam web root (public)
        $publicPath = rtrim(public_path(), '\\/');
        $normalizedDest = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $destination);
        $normalizedPublic = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicPath);

        if (str_starts_with($normalizedDest, $normalizedPublic)) {
            throw new RuntimeException('Lokasi cadangan DITOLAK: Tidak boleh berada di dalam folder public web root.');
        }

        // Periksa keberadaan atau buat folder tujuan
        if (! File::exists($destination)) {
            try {
                File::makeDirectory($destination, 0750, true, true);
            } catch (Throwable $e) {
                throw new RuntimeException("Gagal membuat direktori cadangan di '{$destination}': " . $e->getMessage());
            }
        }

        if (! $this->isDirectoryWritable($destination)) {
            throw new RuntimeException("Direktori cadangan '{$destination}' tidak memiliki izin tulis (permission denied).");
        }

        return $destination;
    }

    /**
     * Melakukan backup SQLite menggunakan VACUUM INTO untuk konsistensi transaksi (termasuk WAL).
     */
    protected function backupSqliteDatabase(string $tempDir): array
    {
        $connectionName = Config::get('backup.database_connection', 'sqlite');
        $targetDbPath = $tempDir . DIRECTORY_SEPARATOR . 'database.sqlite';

        $connection = DB::connection($connectionName);
        $driver = $connection->getDriverName();
        if ($driver !== 'sqlite') {
            throw new RuntimeException("Driver database saat ini ('{$driver}') bukan SQLite. Cadangan otomatis ini khusus SQLite.");
        }

        $dbPath = Config::get("database.connections.{$connectionName}.database");

        // Jika database adalah berkas fisik di disk, gunakan koneksi terdedikasi agar tidak bertabrakan dengan transaksi aktif
        if (! empty($dbPath) && $dbPath !== ':memory:' && File::exists($dbPath)) {
            try {
                $dedicatedPdo = new PDO('sqlite:' . $dbPath);
                $dedicatedPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $quotedPath = $dedicatedPdo->quote($targetDbPath);
                $dedicatedPdo->exec("VACUUM INTO {$quotedPath}");
            } catch (Throwable $e) {
                throw new RuntimeException("Gagal membuat salinan database SQLite menggunakan VACUUM INTO: " . $e->getMessage(), 0, $e);
            }
        } else {
            // Jika in-memory database atau koneksi aktif
            $pdo = $connection->getPdo();
            if (! $pdo instanceof PDO) {
                throw new RuntimeException("Koneksi database '{$connectionName}' tidak valid atau bukan instans PDO.");
            }

            try {
                $quotedPath = $pdo->quote($targetDbPath);
                $pdo->exec("VACUUM INTO {$quotedPath}");
            } catch (Throwable $e) {
                throw new RuntimeException("Gagal membuat salinan database SQLite menggunakan VACUUM INTO: " . $e->getMessage(), 0, $e);
            }
        }

        if (! File::exists($targetDbPath) || File::size($targetDbPath) === 0) {
            throw new RuntimeException("Berkas database hasil cadangan tidak ditemukan atau kosong.");
        }

        // Verifikasi integritas database hasil cadangan dengan PRAGMA integrity_check
        $verifyPdo = new PDO('sqlite:' . $targetDbPath);
        $verifyPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $integrityResult = $verifyPdo->query('PRAGMA integrity_check;')->fetchColumn();
        if ($integrityResult !== 'ok') {
            throw new RuntimeException("Database hasil cadangan gagal uji integritas: {$integrityResult}");
        }

        $journalMode = (string) $verifyPdo->query('PRAGMA journal_mode;')->fetchColumn();

        $dbSize = File::size($targetDbPath);
        $dbSha256 = hash_file('sha256', $targetDbPath);

        return [
            'filename' => 'database.sqlite',
            'size' => $dbSize,
            'sha256' => $dbSha256,
            'journal_mode' => $journalMode,
            'integrity_check' => $integrityResult,
        ];
    }

    /**
     * Menyalin seluruh berkas dokumen privat dengan mempertahankan struktur folder relatif.
     */
    protected function backupPrivateDocuments(string $tempDir): array
    {
        $diskName = Config::get('backup.documents_disk', 'local');
        $docsDir = Config::get('backup.documents_directory', 'documents');

        $disk = Storage::disk($diskName);
        $allFiles = $disk->allFiles($docsDir);

        $targetDocsDir = $tempDir . DIRECTORY_SEPARATOR . 'documents';
        File::makeDirectory($targetDocsDir, 0750, true, true);

        $filesMetadata = [];
        $totalSize = 0;

        foreach ($allFiles as $relativeFilePath) {
            // Abaikan file .gitignore atau berkas tersembunyi sistem jika ada
            if (basename($relativeFilePath) === '.gitignore') {
                continue;
            }

            // Normalisasi path relatif
            $cleanRelPath = str_replace('\\', '/', $relativeFilePath);

            // Tentukan lokasi target di dalam folder backup
            $destPath = $tempDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanRelPath);
            $destDir = dirname($destPath);

            if (! File::exists($destDir)) {
                File::makeDirectory($destDir, 0750, true, true);
            }

            // Salin berkas menggunakan stream agar hemat memori
            $stream = $disk->readStream($cleanRelPath);
            if (! is_resource($stream)) {
                throw new RuntimeException("Gagal membuka stream berkas sumber: {$cleanRelPath}");
            }

            $destStream = fopen($destPath, 'wb');
            if (! is_resource($destStream)) {
                fclose($stream);
                throw new RuntimeException("Gagal membuka tujuan penulisan berkas: {$destPath}");
            }

            stream_copy_to_stream($stream, $destStream);
            fclose($stream);
            fclose($destStream);

            // Verifikasi ukuran dan hash berkas hasil salinan
            $fileSize = File::size($destPath);
            $sha256 = hash_file('sha256', $destPath);
            $totalSize += $fileSize;

            $filesMetadata[] = [
                'relative_path' => $cleanRelPath,
                'size' => $fileSize,
                'sha256' => $sha256,
            ];
        }

        return [
            'files' => $filesMetadata,
            'total_size' => $totalSize,
        ];
    }

    /**
     * Memeriksa kesesuaian antara metadata dokumen di SQLite hasil cadangan dengan berkas fisik yang disalin.
     */
    protected function crossCheckDatabaseAndFiles(string $tempDir, array $copiedFiles): array
    {
        $targetDbPath = $tempDir . DIRECTORY_SEPARATOR . 'database.sqlite';
        $verifyPdo = new PDO('sqlite:' . $targetDbPath);
        $verifyPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $copiedPaths = array_column($copiedFiles, 'relative_path');
        $copiedLookup = array_flip($copiedPaths);

        $missingInStorage = [];

        // Periksa apakah tabel 'documents' ada di database
        $tableExists = $verifyPdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='documents';")->fetchColumn();
        if ($tableExists) {
            $stmt = $verifyPdo->query("SELECT id, title, storage_path FROM documents WHERE storage_path IS NOT NULL;");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($records as $row) {
                $path = str_replace('\\', '/', $row['storage_path']);
                if (! isset($copiedLookup[$path])) {
                    $missingInStorage[] = [
                        'document_id' => $row['id'],
                        'title' => $row['title'],
                        'expected_path' => $path,
                    ];
                }
            }
        }

        return [
            'missing_in_storage' => $missingInStorage,
        ];
    }

    /**
     * Menerapkan kebijakan retensi: menghapus backup lama hanya setelah backup baru sukses dan terverifikasi.
     */
    protected function applyRetentionPolicy(string $backupRoot, int $keepCount): int
    {
        if ($keepCount <= 0) {
            return 0;
        }

        $directories = File::directories($backupRoot);
        $validBackups = [];

        foreach ($directories as $dir) {
            $manifestPath = $dir . DIRECTORY_SEPARATOR . 'manifest.json';
            if (File::exists($manifestPath)) {
                $content = @json_decode(File::get($manifestPath), true);
                if (is_array($content) && ($content['status'] ?? '') === 'verified') {
                    $validBackups[] = [
                        'path' => $dir,
                        'created_at' => $content['created_at'] ?? '1970-01-01',
                    ];
                }
            }
        }

        // Urutkan dari yang terbaru ke terlama (gunakan path sebagai pemutus seri)
        usort($validBackups, function ($a, $b) {
            $cmp = strcmp($b['created_at'], $a['created_at']);
            return $cmp !== 0 ? $cmp : strcmp($b['path'], $a['path']);
        });

        $purged = 0;
        if (count($validBackups) > $keepCount) {
            $toDelete = array_slice($validBackups, $keepCount);
            foreach ($toDelete as $item) {
                File::deleteDirectory($item['path']);
                $purged++;
            }
        }

        return $purged;
    }

    /**
     * Memeriksa apakah direktori dapat ditulisi secara aman pada lingkungan Windows dan Linux.
     */
    protected function isDirectoryWritable(string $dir): bool
    {
        $testFile = $dir . DIRECTORY_SEPARATOR . '.simpera_write_test_' . uniqid();
        $written = @file_put_contents($testFile, 'test');
        if ($written !== false) {
            @unlink($testFile);
            return true;
        }

        return false;
    }

    /**
     * Dapatkan versi SQLite saat ini.
     */
    protected function getSqliteVersion(): string
    {
        if (class_exists(\SQLite3::class)) {
            $v = \SQLite3::version();
            return $v['versionString'] ?? 'Unknown';
        }

        return 'Unknown';
    }
}
