<?php

namespace App\Console\Commands;

use App\Services\RestoreService;
use Illuminate\Console\Command;

class SimperaBackupListCommand extends Command
{
    /**
     * Nama dan tanda tangan perintah konsol.
     *
     * @var string
     */
    protected $signature = 'simpera:backup-list
                            {--destination= : Lokasi folder cadangan kustom}';

    /**
     * Deskripsi perintah konsol.
     *
     * @var string
     */
    protected $description = 'Menampilkan seluruh daftar paket cadangan Simpera yang tersedia';

    /**
     * Eksekusi perintah konsol.
     */
    public function handle(RestoreService $restoreService): int
    {
        $destination = $this->option('destination');
        $backups = $restoreService->listBackups($destination);

        $this->info('====================================================');
        $this->info('   SIMPERA: DAFTAR PAKET CADANGAN TERSEDIA         ');
        $this->info('====================================================');

        if (empty($backups)) {
            $this->warn('Belum ada paket cadangan yang ditemukan di lokasi tersebut.');
            $this->comment('Gunakan "php artisan simpera:backup" untuk membuat cadangan baru.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($backups as $index => $b) {
            $rows[] = [
                $index + 1,
                $b['backup_id'],
                $b['created_at'],
                number_format($b['database_size'] / 1024, 1) . ' KB',
                $b['documents_count'] . ' berkas (' . number_format($b['documents_total_size'] / 1024, 1) . ' KB)',
                $b['status'],
                $b['notes'] ?: '-',
            ];
        }

        $this->table(
            ['No', 'ID Cadangan', 'Tanggal Pembuatan', 'Ukuran DB', 'Dokumen', 'Status', 'Catatan'],
            $rows
        );

        $this->info('Total paket cadangan ditemukan: ' . count($backups));
        return Command::SUCCESS;
    }
}

