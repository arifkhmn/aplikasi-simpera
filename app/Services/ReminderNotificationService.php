<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\ReminderNotification;
use Illuminate\Support\Facades\Log;

class ReminderNotificationService
{
    /**
     * Interval hari sebelum jatuh tempo untuk pemeriksaan notifikasi otomatis.
     */
    public const NOTIFICATION_INTERVALS = [30, 7, 1, 0];

    /**
     * Memeriksa seluruh pengingat aktif yang mendekati jatuh tempo dan mencatat notifikasi otomatis.
     * Menjamin tidak ada notifikasi ganda untuk interval dan kanal yang sama.
     *
     * @param string $channel Kanal notifikasi ('system', 'email', 'app')
     * @return array{checked: int, sent: int, skipped: int}
     */
    public function processDueReminders(string $channel = 'system'): array
    {
        $checkedCount = 0;
        $sentCount = 0;
        $skippedCount = 0;

        foreach (self::NOTIFICATION_INTERVALS as $interval) {
            $targetDate = today()->addDays($interval)->toDateString();

            // Ambil pengingat berstatus pending yang jatuh tempo pada tanggal target
            $reminders = Reminder::with('user')
                ->where('status', 'pending')
                ->whereDate('due_date', $targetDate)
                ->get();

            if ($reminders->isEmpty()) {
                continue;
            }

            // Kumpulkan seluruh ID pengingat pada interval ini
            $reminderIds = $reminders->pluck('id')->all();

            // Query tunggal untuk memeriksa riwayat notifikasi yang sudah pernah dikirim (eliminasi N+1)
            $alreadySentMap = ReminderNotification::whereIn('reminder_id', $reminderIds)
                ->where('interval_days', $interval)
                ->where('channel', $channel)
                ->pluck('reminder_id')
                ->flip()
                ->all();

            foreach ($reminders as $reminder) {
                $checkedCount++;

                // Cek secara in-memory apakah notifikasi untuk interval dan kanal ini sudah pernah tercatat
                if (isset($alreadySentMap[$reminder->id])) {
                    $skippedCount++;
                    continue;
                }

                // Simpan catatan pengiriman notifikasi
                ReminderNotification::create([
                    'reminder_id' => $reminder->id,
                    'interval_days' => $interval,
                    'channel' => $channel,
                    'sent_at' => now(),
                    'status' => 'sent',
                ]);

                // Tandai di map in-memory agar aman jika terjadi duplikat instance
                $alreadySentMap[$reminder->id] = true;

                $label = ($interval === 0) ? 'Hari Ini' : "H-{$interval}";
                Log::info("Notifikasi pengingat {$label} diproses untuk: \"{$reminder->title}\" (User: {$reminder->user->email})");

                $sentCount++;
            }
        }

        return [
            'checked' => $checkedCount,
            'sent' => $sentCount,
            'skipped' => $skippedCount,
        ];
    }
}
