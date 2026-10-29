<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;
use Throwable;

class RestoreService
{
    /**
     * Mengambil daftar seluruh paket cadangan yang tersedia di lokasi backup.
     */
    public function listBackups(?string $customBackupRoot = null): array
    {
        $backupRoot = $this->resolveBackupRoot($customBackupRoot);

        if (! File::exists($backupRoot)) {
            return [];
        }

        $directories = File::directories($backupRoot);
        $backups = [];

        foreach ($directories as $dir) {
            $manifestPath = $dir . DIRECTORY_SEPARATOR . 'manifest.json';
            if (File::exists($manifestPath)) {
                $manifest = @json_decode(File::get($manifestPath), true);
                if (is_array($manifest)) {
                    $backups[] = [
                        'backup_id' => $manifest['backup_id'] ?? basename($dir),
                        'path' => $dir,
                        'created_at' => $manifest['created_at'] ?? 'Unknown',
                        'notes' => $manifest['notes'] ?? '-',
                        'status' => $manifest['status'] ?? 'unknown',
                        'database_size' => $manifest['database']['size'] ?? 0,
                        'documents_count' => $manifest['documents']['count'] ?? 0,
                        'documents_total_size' => $manifest['documents']['total_size'] ?? 0,
                    ];
                }
            }
        }

        // Urutkan dari yang terbaru ke terlama
        usort($backups, function ($a, $b) {
            $cmp = strcmp($b['created_at'], $a['created_at']);
            return $cmp !== 0 ? $cmp : strcmp($b['path'], $a['path']);
        });

        return $backups;
    }

    /**
     * Memverifikasi integritas menyeluruh suatu paket cadangan:
     * - Keberadaan dan checksum SHA-256 database.sqlite
     * - Uji integritas SQLite (PRAGMA integrity_check)
     * - Keberadaan, ukuran, dan checksum seluruh berkas dokumen di manifest
     */
    public function verifyBackup(string $backupId, ?string $customBackupRoot = null): array
    {
        $backupDir = $this->findBackupDirectory($backupId, $customBackupRoot);
        $manifestPath = $backupDir . DIRECTORY_SEPARATOR . 'manifest.json';

        if (! File::exists($manifestPath)) {
            return [
                'valid' => false,
                'backup_id' => $backupId,
                'backup_dir' => $backupDir,
                'errors' => ['Berkas manifest.json tidak ditemukan pada paket cadangan.'],
                'warnings' => [],
                'manifest' => null,
            ];
        }

        $manifest = json_decode(File::get($manifestPath), true);
        if (! is_array($manifest)) {
            return [
                'valid' => false,
                'backup_id' => $backupId,
                'backup_dir' => $backupDir,
                'errors' => ['Berkas manifest.json rusak atau format JSON tidak valid.'],
                'warnings' => [],
                'manifest' => null,
            ];
        }

        $errors = [];
        $warnings = [];

        // 1. Verifikasi Database
        $dbFile = $backupDir . DIRECTORY_SEPARATOR . 'database.sqlite';
        if (! File::exists($dbFile)) {
            $errors[] = "Berkas database.sqlite tidak ditemukan di dalam paket cadangan.";
        } else {
            $actualDbSha256 = hash_file('sha256', $dbFile);
            $expectedDbSha256 = $manifest['database']['sha256'] ?? null;

            if ($expectedDbSha256 && $actualDbSha256 !== $expectedDbSha256) {
                $errors[] = "Checksum SHA-256 database.sqlite tidak cocok (kemungkinan file rusak atau termodifikasi).";
            }

            // Uji PRAGMA integrity_check
            try {
                $pdo = new PDO('sqlite:' . $dbFile);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $res = $pdo->query('PRAGMA integrity_check;')->fetchColumn();
                if ($res !== 'ok') {
                    $errors[] = "Database SQLite gagal uji integritas: {$res}";
                }
            } catch (Throwable $e) {
                $errors[] = "Gagal membuka database SQLite untuk uji integritas: " . $e->getMessage();
            }
        }

        // 2. Verifikasi Dokumen Privat
        $documentsList = $manifest['documents']['files'] ?? [];
        foreach ($documentsList as $docMeta) {
            $relPath = $docMeta['relative_path'] ?? '';
            $expectedSize = $docMeta['size'] ?? 0;
            $expectedSha256 = $docMeta['sha256'] ?? '';

            $filePath = $backupDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);

            if (! File::exists($filePath)) {
                $errors[] = "Berkas dokumen hilang dari cadangan: {$relPath}";
                continue;
            }

            if (File::size($filePath) !== $expectedSize) {
                $errors[] = "Ukuran berkas dokumen tidak sesuai: {$relPath} (Aktual: " . File::size($filePath) . ", Harapan: {$expectedSize})";
            }

            $actualSha = hash_file('sha256', $filePath);
            if ($actualSha !== $expectedSha256) {
                $errors[] = "Checksum SHA-256 berkas dokumen tidak cocok: {$relPath}";
            }
        }

        return [
            'valid' => empty($errors),
            'backup_id' => $backupId,
            'backup_dir' => $backupDir,
            'errors' => $errors,
            'warnings' => $warnings,
            'manifest' => $manifest,
        ];
    }

    /**
     * Memulihkan cadangan ke direktori staging terpisah (Default Aman).
     * Tidak mengubah atau mengganggu database dan dokumen produksi yang aktif.
     */
    public function restoreToStaging(string $backupId, ?string $customStagingPath = null, ?string $customBackupRoot = null): array
    {
        // Verifikasi integritas cadangan terlebih dahulu
        $verification = $this->verifyBackup($backupId, $customBackupRoot);
        if (! $verification['valid']) {
            throw new RuntimeException("Pemulihan ke staging dibatalkan. Paket cadangan tidak valid:\n- " . implode("\n- ", $verification['errors']));
        }

        $backupDir = $verification['backup_dir'];
        $backupRoot = $this->resolveBackupRoot($customBackupRoot);

        $stagingBase = $customStagingPath
            ?: Config::get('backup.staging_path')
            ?: ($backupRoot . DIRECTORY_SEPARATOR . 'staging');

        $timestamp = Carbon::now()->format('Y-m-d_His');
        $stagingDir = $stagingBase . DIRECTORY_SEPARATOR . "staging_{$backupId}_{$timestamp}";

        File::makeDirectory($stagingDir, 0750, true, true);

        try {
            // 1. Salin Database ke Staging
            $stagingDbPath = $stagingDir . DIRECTORY_SEPARATOR . 'database.sqlite';
            File::copy($backupDir . DIRECTORY_SEPARATOR . 'database.sqlite', $stagingDbPath);

            // 2. Salin Berkas Dokumen ke Staging
            $sourceDocs = $backupDir . DIRECTORY_SEPARATOR . 'documents';
            $stagingDocs = $stagingDir . DIRECTORY_SEPARATOR . 'documents';
            if (File::exists($sourceDocs)) {
                File::copyDirectory($sourceDocs, $stagingDocs);
            }

            // 3. Uji integritas database di staging
            $stagingPdo = new PDO('sqlite:' . $stagingDbPath);
            $stagingPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $check = $stagingPdo->query('PRAGMA integrity_check;')->fetchColumn();
            if ($check !== 'ok') {
                throw new RuntimeException("Database hasil staging gagal uji integritas: {$check}");
            }

            // 4. Periksa korelasi metadata di staging DB dengan berkas staging
            $matchedCount = 0;
            $tableExists = $stagingPdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='documents';")->fetchColumn();
            if ($tableExists) {
                $stmt = $stagingPdo->query("SELECT id, title, storage_path FROM documents WHERE storage_path IS NOT NULL;");
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($records as $row) {
                    $rel = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $row['storage_path']);
                    $expectedStagingFile = $stagingDir . DIRECTORY_SEPARATOR . $rel;
                    if (File::exists($expectedStagingFile)) {
                        $matchedCount++;
                    }
                }
            }

            // 5. Buat laporan ringkasan staging
            $report = [
                'backup_id' => $backupId,
                'restored_to' => $stagingDir,
                'timestamp' => Carbon::now()->toIso8601String(),
                'database_integrity' => $check,
                'documents_matched_in_staging' => $matchedCount,
                'status' => 'success',
            ];
            File::put($stagingDir . DIRECTORY_SEPARATOR . 'staging_report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return [
                'success' => true,
                'mode' => 'staging',
                'backup_id' => $backupId,
                'staging_path' => $stagingDir,
                'database_path' => $stagingDbPath,
                'documents_path' => $stagingDocs,
                'database_integrity' => $check,
                'documents_matched' => $matchedCount,
            ];
        } catch (Throwable $e) {
            if (File::exists($stagingDir)) {
                File::deleteDirectory($stagingDir);
            }
            throw new RuntimeException("Gagal melakukan pemulihan ke staging: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Memulihkan data langsung ke lingkungan produksi aktif.
     * Mewajibkan konfirmasi eksplisit ($confirmed = true).
     * Otomatis membuat 'Safety Pre-Restore Snapshot' data aktif sebelum menimpa.
     */
    public function restoreToProduction(string $backupId, bool $confirmed = false, ?string $customBackupRoot = null): array
    {
        if (! $confirmed) {
            throw new RuntimeException("Pemulihan produksi DITOLAK: Memerlukan konfirmasi eksplisit (--confirm-production).");
        }

        // Verifikasi integritas cadangan terlebih dahulu
        $verification = $this->verifyBackup($backupId, $customBackupRoot);
        if (! $verification['valid']) {
            throw new RuntimeException("Pemulihan produksi DIBATALKAN. Paket cadangan tidak valid:\n- " . implode("\n- ", $verification['errors']));
        }

        $backupDir = $verification['backup_dir'];
        $backupRoot = $this->resolveBackupRoot($customBackupRoot);

        // Tentukan lokasi aktif database dan dokumen privat
        $activeDbPath = $this->getActiveDatabasePath();
        $diskName = Config::get('backup.documents_disk', 'local');
        $docsDir = Config::get('backup.documents_directory', 'documents');

        // 1. Buat 'Safety Pre-Restore Snapshot' dari data yang sedang aktif
        $safetyTimestamp = Carbon::now()->format('Y-m-d_His');
        $safetyDir = $backupRoot . DIRECTORY_SEPARATOR . "safety_pre_restore_{$safetyTimestamp}";
        File::makeDirectory($safetyDir, 0750, true, true);

        try {
            // Salin database aktif saat ini ke safety snapshot
            if (File::exists($activeDbPath)) {
                File::copy($activeDbPath, $safetyDir . DIRECTORY_SEPARATOR . 'database.sqlite');
            }

            // Salin berkas dokumen aktif saat ini ke safety snapshot
            $disk = Storage::disk($diskName);
            $activeFiles = $disk->allFiles($docsDir);
            foreach ($activeFiles as $f) {
                if (basename($f) === '.gitignore') continue;
                $clean = str_replace('\\', '/', $f);
                $targetSafetyFile = $safetyDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean);
                File::makeDirectory(dirname($targetSafetyFile), 0750, true, true);
                File::copy($disk->path($clean), $targetSafetyFile);
            }

            File::put($safetyDir . DIRECTORY_SEPARATOR . 'safety_info.json', json_encode([
                'reason' => "Snapshot otomatis sebelum memulihkan cadangan {$backupId}",
                'created_at' => Carbon::now()->toIso8601String(),
                'original_backup_restoring' => $backupId,
            ], JSON_PRETTY_PRINT));
        } catch (Throwable $e) {
            Log::warning('Gagal membuat safety pre-restore snapshot: ' . $e->getMessage());
        }

        // 2. Lakukan Pemulihan Database Aktif
        try {
            // Putuskan koneksi database aktif agar berkas tidak terkunci
            DB::purge(Config::get('backup.database_connection', 'sqlite'));

            $sourceDb = $backupDir . DIRECTORY_SEPARATOR . 'database.sqlite';
            File::copy($sourceDb, $activeDbPath);

            // Verifikasi integritas database aktif yang baru dipulihkan
            $activePdo = new PDO('sqlite:' . $activeDbPath);
            $activePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $check = $activePdo->query('PRAGMA integrity_check;')->fetchColumn();
            if ($check !== 'ok') {
                throw new RuntimeException("Database aktif hasil pemulihan gagal uji integritas: {$check}");
            }
        } catch (Throwable $e) {
            throw new RuntimeException("Gagal memulihkan database aktif: " . $e->getMessage(), 0, $e);
        }

        // 3. Lakukan Pemulihan Dokumen Privat Aktif
        $restoredDocsCount = 0;
        $sourceDocsDir = $backupDir . DIRECTORY_SEPARATOR . 'documents';

        if (File::exists($sourceDocsDir)) {
            $backupDocFiles = File::allFiles($sourceDocsDir);
            foreach ($backupDocFiles as $file) {
                $relative = str_replace('\\', '/', $file->getRelativePathname());
                $fullRelPath = 'documents/' . $relative;

                $targetPath = $disk->path($fullRelPath);
                File::makeDirectory(dirname($targetPath), 0750, true, true);
                File::copy($file->getRealPath(), $targetPath);
                $restoredDocsCount++;
            }
        }

        Log::info("Pemulihan produksi Simpera berhasil diselesaikan dari backup '{$backupId}'.", [
            'safety_snapshot' => $safetyDir,
            'documents_restored' => $restoredDocsCount,
        ]);

        return [
            'success' => true,
            'mode' => 'production',
            'backup_id' => $backupId,
            'safety_snapshot_path' => $safetyDir,
            'restored_at' => Carbon::now()->toIso8601String(),
            'database_integrity' => 'ok',
            'documents_restored' => $restoredDocsCount,
        ];
    }

    /**
     * Dapatkan path absolut file database SQLite aktif.
     */
    public function getActiveDatabasePath(): string
    {
        $conn = Config::get('backup.database_connection', 'sqlite');
        $dbPath = Config::get("database.connections.{$conn}.database");

        if (empty($dbPath) || $dbPath === ':memory:') {
            return database_path('database.sqlite');
        }

        return $dbPath;
    }

    /**
     * Dapatkan folder root backup yang telah divalidasi.
     */
    protected function resolveBackupRoot(?string $customBackupRoot = null): string
    {
        $root = $customBackupRoot ?: Config::get('backup.path', 'D:\Backup_Simpera');
        return rtrim($root, '\\/');
    }

    /**
     * Temukan direktori dari suatu backup ID.
     */
    protected function findBackupDirectory(string $backupId, ?string $customBackupRoot = null): string
    {
        $backupRoot = $this->resolveBackupRoot($customBackupRoot);
        $directPath = $backupRoot . DIRECTORY_SEPARATOR . $backupId;

        if (File::exists($directPath)) {
            return $directPath;
        }

        // Cari berdasarkan subfolder yang memuat manifest dengan ID yang cocok
        $directories = File::directories($backupRoot);
        foreach ($directories as $dir) {
            $manifestPath = $dir . DIRECTORY_SEPARATOR . 'manifest.json';
            if (File::exists($manifestPath)) {
                $manifest = @json_decode(File::get($manifestPath), true);
                if (is_array($manifest) && ($manifest['backup_id'] ?? '') === $backupId) {
                    return $dir;
                }
            }
        }

        throw new RuntimeException("Paket cadangan dengan ID '{$backupId}' tidak ditemukan di '{$backupRoot}'.");
    }
}

