<?php

namespace App\Console\Commands;

use App\Services\ReminderNotificationService;
use Illuminate\Console\Command;

class SendDueRemindersCommand extends Command
{
    /**
     * Nama dan tanda tangan perintah konsol.
     *
     * @var string
     */
    protected $signature = 'simpera:send-reminders {--channel=system : Kanal notifikasi (system/email/app)}';

    /**
     * Deskripsi perintah konsol.
     *
     * @var string
     */
    protected $description = 'Memeriksa dan memproses notifikasi otomatis untuk pengingat yang mendekati jatuh tempo (H-30, H-7, H-1, Hari H)';

    /**
     * Eksekusi perintah konsol.
     */
    public function handle(ReminderNotificationService $service): int
    {
        $channel = $this->option('channel') ?: 'system';

        $this->info("Menjalankan pemeriksaan pengingat jatuh tempo (Kanal: {$channel})...");

        $result = $service->processDueReminders($channel);

        $this->info("Pemeriksaan selesai:");
        $this->line("- Pengingat dievaluasi: {$result['checked']}");
        $this->line("- Notifikasi baru diproses: {$result['sent']}");
        $this->line("- Notifikasi dilewati (sudah pernah dikirim): {$result['skipped']}");

        return Command::SUCCESS;
    }
}
