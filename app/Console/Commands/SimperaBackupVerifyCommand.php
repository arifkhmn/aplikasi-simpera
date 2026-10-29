<?php

namespace App\Console\Commands;

use App\Services\RestoreService;
use Illuminate\Console\Command;

class SimperaBackupVerifyCommand extends Command
{
    /**
     * Nama dan tanda tangan perintah konsol.
     *
     * @var string
     */
    protected $signature = 'simpera:backup-verify
                            {backup_id? : ID paket cadangan yang ingin diperiksa integritasnya}
                            {--destination= : Lokasi folder cadangan kustom}';

    /**
     * Deskripsi perintah konsol.
     *
     * @var string
     */
    protected $description = 'Memeriksa keaslian dan integritas SQLite serta checksum seluruh berkas dokumen pada paket cadangan';

    /**
     * Eksekusi perintah konsol.
     */
    public function handle(RestoreService $restoreService): int
    {
        $backupId = $this->argument('backup_id');
        $destination = $this->option('destination');

        if (empty($backupId)) {
            $backups = $restoreService->listBackups($destination);
            if (empty($backups)) {
                $this->error('Tidak ada paket cadangan yang ditemukan untuk diverifikasi.');
                return Command::FAILURE;
            }
            $backupId = $backups[0]['backup_id'];
            $this->comment("Memilih paket cadangan terbaru secara otomatis: {$backupId}");
        }

        $this->info("Memeriksa integritas cadangan [{$backupId}]...");

        $res = $restoreService->verifyBackup($backupId, $destination);

        if ($res['valid']) {
            $this->newLine();
            $this->info("✓ Paket cadangan [{$backupId}] VALID dan UTUH!");
            $this->line("- SQLite PRAGMA integrity_check: OK");
            $this->line("- Database SHA-256 Checksum: Cocok");
            $this->line("- Seluruh berkas dokumen SHA-256: Cocok dan lengkap");
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->error("✗ Paket cadangan [{$backupId}] TIDAK VALID atau RUSAK!");
        foreach ($res['errors'] as $err) {
            $this->line("  [GAGAL] {$err}");
        }

        return Command::FAILURE;
    }
}

