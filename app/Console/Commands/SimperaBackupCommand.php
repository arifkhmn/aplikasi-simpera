<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Throwable;

class SimperaBackupCommand extends Command
{
    /**
     * Nama dan tanda tangan perintah konsol.
     *
     * @var string
     */
    protected $signature = 'simpera:backup
                            {--destination= : Lokasi folder cadangan kustom}
                            {--notes= : Catatan tambahan untuk berkas manifest cadangan}
                            {--keep= : Batas jumlah cadangan yang dipertahankan (retensi)}';

    /**
     * Deskripsi perintah konsol.
     *
     * @var string
     */
    protected $description = 'Membuat cadangan aman dan konsisten untuk database SQLite dan seluruh dokumen privat Simpera';

    /**
     * Eksekusi perintah konsol.
     */
    public function handle(BackupService $backupService): int
    {
        $this->info('====================================================');
        $this->info('   SIMPERA: PEMBUATAN CADANGAN AMAN (BACKUP)        ');
        $this->info('====================================================');

        $destination = $this->option('destination');
        $notes = $this->option('notes');
        $keep = $this->option('keep') !== null ? (int) $this->option('keep') : null;

        $this->line('1. Memeriksa lokasi penyimpanan dan izin akses...');
        $this->line('2. Menjalankan VACUUM INTO untuk snapshot SQLite atomik...');
        $this->line('3. Menyalin dan menghitung checksum SHA-256 berkas dokumen...');

        try {
            $result = $backupService->createBackup($destination, $notes, $keep);

            $this->newLine();
            $this->info('✓ Cadangan berhasil dibuat dan diverifikasi 100%!');
            $this->table(
                ['Parameter', 'Keterangan'],
                [
                    ['ID Cadangan', $result['backup_id']],
                    ['Lokasi Folder', $result['backup_path']],
                    ['Ukuran Database', number_format($result['database_size']) . ' bytes'],
                    ['SHA-256 Database', substr($result['database_sha256'], 0, 16) . '... (terverifikasi)'],
                    ['Jumlah Dokumen', $result['documents_count'] . ' berkas'],
                    ['Total Ukuran Dokumen', number_format($result['documents_total_size']) . ' bytes'],
                    ['Waktu Pembuatan', $result['created_at']],
                    ['Backup Lama Dibersihkan', $result['purged_old_backups'] . ' paket'],
                ]
            );

            $this->comment('Petunjuk: Jalankan "php artisan simpera:backup-verify ' . $result['backup_id'] . '" untuk memeriksa integritas kapan pun.');
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('✗ Gagal membuat cadangan: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

