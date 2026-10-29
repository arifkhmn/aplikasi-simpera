<?php

namespace App\Console\Commands;

use App\Services\RestoreService;
use Illuminate\Console\Command;
use Throwable;

class SimperaRestoreCommand extends Command
{
    /**
     * Nama dan tanda tangan perintah konsol.
     *
     * @var string
     */
    protected $signature = 'simpera:restore
                            {backup_id? : ID paket cadangan yang ingin dipulihkan}
                            {--staging-path= : Lokasi folder staging tujuan}
                            {--destination= : Lokasi folder sumber cadangan}
                            {--production : Pulihkan langsung ke database dan berkas aktif (HATI-HATI)}
                            {--confirm : Konfirmasi tanpa prompt interaktif (untuk CI/otomatisasi)}';

    /**
     * Deskripsi perintah konsol.
     *
     * @var string
     */
    protected $description = 'Memulihkan data Simpera (secara default ke direktori staging yang aman tanpa mengganggu produksi)';

    /**
     * Eksekusi perintah konsol.
     */
    public function handle(RestoreService $restoreService): int
    {
        $backupId = $this->argument('backup_id');
        $destination = $this->option('destination');
        $stagingPath = $this->option('staging-path');
        $isProduction = (bool) $this->option('production');
        $isConfirmed = (bool) $this->option('confirm');

        // Jika ID tidak diinput, tampilkan daftar atau pilih yang terbaru
        if (empty($backupId)) {
            $backups = $restoreService->listBackups($destination);
            if (empty($backups)) {
                $this->error('Tidak ada paket cadangan yang ditemukan untuk dipulihkan.');
                return Command::FAILURE;
            }

            $choices = array_map(function ($b) {
                return $b['backup_id'] . " ({$b['created_at']}, DB: " . round($b['database_size'] / 1024) . "KB, Docs: {$b['documents_count']})";
            }, $backups);

            $selected = $this->choice('Pilih paket cadangan yang ingin dipulihkan:', $choices, 0);
            $backupId = explode(' ', $selected)[0];
        }

        // ==========================================
        // MODE 1: DEFAULT AMAN - RESTORE KE STAGING
        // ==========================================
        if (! $isProduction) {
            $this->info('====================================================');
            $this->info('   SIMPERA: PEMULIHAN KE DIREKTORI STAGING (AMAN)   ');
            $this->info('====================================================');
            $this->line('Mode staging memulihkan data ke folder terpisah.');
            $this->line('Database dan dokumen aktif Anda TIDAK AKAN disentuh atau diubah sama sekali.');
            $this->newLine();

            try {
                $res = $restoreService->restoreToStaging($backupId, $stagingPath, $destination);

                $this->info('✓ Pemulihan ke direktori staging berhasil 100%!');
                $this->table(
                    ['Parameter', 'Hasil Staging'],
                    [
                        ['ID Cadangan', $res['backup_id']],
                        ['Folder Hasil Staging', $res['staging_path']],
                        ['File Database Staging', $res['database_path']],
                        ['Folder Dokumen Staging', $res['documents_path']],
                        ['Integritas SQLite Staging', $res['database_integrity']],
                        ['Dokumen Cocok dengan Metadata', $res['documents_matched'] . ' berkas'],
                    ]
                );

                $this->newLine();
                $this->comment('Anda dapat memeriksa hasil di atas di folder staging tersebut.');
                $this->comment('Jika ingin menerapkan hasil cadangan ini ke database produksi aktif, jalankan:');
                $this->line("  php artisan simpera:restore {$backupId} --production");

                return Command::SUCCESS;
            } catch (Throwable $e) {
                $this->error('✗ Gagal melakukan pemulihan staging: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        // ==========================================
        // MODE 2: PEMULIHAN KE PRODUKSI AKTIF
        // ==========================================
        $this->warn('====================================================');
        $this->warn('   PERINGATAN: PEMULIHAN KE DATA PRODUKSI AKTIF     ');
        $this->warn('====================================================');
        $this->line('Operasi ini akan menimpa database aktif dan berkas dokumen Simpera.');
        $this->line('Sistem akan membuat Safety Pre-Restore Snapshot secara otomatis terlebih dahulu.');
        $this->newLine();

        if (! $isConfirmed) {
            $input = $this->ask("Ketik 'RESTORE' (huruf besar) untuk melanjutkan pemulihan ke produksi:");
            if (trim($input) !== 'RESTORE') {
                $this->info('Operasi pemulihan produksi dibatalkan oleh pengguna.');
                return Command::SUCCESS;
            }
        }

        try {
            $this->line('1. Memverifikasi integritas paket cadangan...');
            $this->line('2. Membuat Safety Pre-Restore Snapshot data aktif...');
            $this->line('3. Mengganti file database dan berkas dokumen...');
            $this->line('4. Menjalankan verifikasi akhir...');

            $res = $restoreService->restoreToProduction($backupId, true, $destination);

            $this->newLine();
            $this->info('✓ Pemulihan produksi berhasil diselesaikan!');
            $this->table(
                ['Parameter', 'Keterangan'],
                [
                    ['ID Cadangan Dipulihkan', $res['backup_id']],
                    ['Waktu Pemulihan', $res['restored_at']],
                    ['Integritas SQLite Aktif', $res['database_integrity']],
                    ['Jumlah Dokumen Dipulihkan', $res['documents_restored'] . ' berkas'],
                    ['Safety Pre-Restore Snapshot', $res['safety_snapshot_path']],
                ]
            );

            $this->comment('Catatan: Jika ada kendala, data lama Anda tersimpan aman di folder Safety Pre-Restore Snapshot di atas.');
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('✗ Gagal memulihkan ke produksi: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

