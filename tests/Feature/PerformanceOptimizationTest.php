<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\Reminder;
use App\Models\ReminderNotification;
use App\Models\User;
use App\Models\Warranty;
use App\Services\ReminderNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memastikan ReminderNotificationService mengeliminasi N+1 query loop dan mencegah duplikasi notifikasi.
     */
    public function test_reminder_notification_service_eliminates_n_plus_one_and_prevents_duplicates(): void
    {
        $user = User::factory()->create();

        // Buat 10 pengingat berstatus pending yang jatuh tempo H-30
        for ($i = 1; $i <= 10; $i++) {
            Reminder::create([
                'user_id' => $user->id,
                'title' => "Pengingat H-30 #{$i}",
                'due_date' => today()->addDays(30),
                'status' => 'pending',
            ]);
        }

        $service = app(ReminderNotificationService::class);

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Eksekusi pertama
        $result1 = $service->processDueReminders('system');

        $log = DB::getQueryLog();

        // Hitung query SELECT pada tabel reminder_notifications
        $selectHistoryQueries = array_filter($log, function ($q) {
            return str_contains($q['query'], 'reminder_notifications') && str_starts_with(trim($q['query']), 'select');
        });

        // Harus 1 kueri batch (whereIn), BUKAN 10 kueri berulang (N+1)
        $this->assertEquals(1, count($selectHistoryQueries));
        $this->assertEquals(10, $result1['checked']);
        $this->assertEquals(10, $result1['sent']);
        $this->assertEquals(0, $result1['skipped']);
        $this->assertEquals(10, ReminderNotification::count());

        // Eksekusi kedua (harus di-skip seluruhnya dan tidak ada duplikasi data)
        DB::flushQueryLog();
        $result2 = $service->processDueReminders('system');

        $this->assertEquals(10, $result2['checked']);
        $this->assertEquals(0, $result2['sent']);
        $this->assertEquals(10, $result2['skipped']);
        $this->assertEquals(10, ReminderNotification::count()); // Tetap 10, tidak bertambah
    }

    /**
     * Memastikan agregasi tunggal status garansi di DashboardController menghasilkan nilai yang presisi.
     */
    public function test_dashboard_warranty_aggregation_is_accurate(): void
    {
        $user = User::factory()->create();

        // 1 Kedaluwarsa (< today)
        Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Barang Kedaluwarsa',
            'brand' => 'Merk A',
            'purchase_date' => today()->subYears(2),
            'warranty_end_date' => today()->subDays(10),
        ]);

        // 1 Segera berakhir (dalam 30 hari ke depan)
        Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Barang Segera Berakhir',
            'brand' => 'Merk B',
            'purchase_date' => today()->subMonths(11),
            'warranty_end_date' => today()->addDays(15),
        ]);

        // 1 Aktif (> 30 hari ke depan)
        Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Barang Masih Aktif',
            'brand' => 'Merk C',
            'purchase_date' => today()->subMonths(1),
            'warranty_end_date' => today()->addMonths(6),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();

        // Total: 3, Aktif bergaransi (>= today): 2, Aktif (>30 hari): 1, Hampir Berakhir: 1, Kedaluwarsa: 1
        $response->assertViewHas('metrics', function ($metrics) {
            return $metrics['active_warranties'] === 2;
        });

        $response->assertViewHas('warrantyCounts', function ($counts) {
            return $counts['total'] === 3 &&
                   $counts['active'] === 1 &&
                   $counts['expiring_soon'] === 1 &&
                   $counts['expired'] === 1;
        });
    }

    /**
     * Memastikan agregasi metrik tab pengingat di ReminderController presisi dan bebas duplikat.
     */
    public function test_reminder_tab_counts_aggregation_is_accurate(): void
    {
        $user = User::factory()->create();

        // 1 Selesai
        Reminder::create([
            'user_id' => $user->id,
            'title' => 'Pengingat Selesai',
            'due_date' => today(),
            'status' => 'completed',
        ]);

        // 1 Hari ini (pending)
        Reminder::create([
            'user_id' => $user->id,
            'title' => 'Pengingat Hari Ini',
            'due_date' => today(),
            'status' => 'pending',
        ]);

        // 1 Terlambat (pending)
        Reminder::create([
            'user_id' => $user->id,
            'title' => 'Pengingat Terlambat',
            'due_date' => today()->subDays(5),
            'status' => 'pending',
        ]);

        // 1 Mendatang (pending)
        Reminder::create([
            'user_id' => $user->id,
            'title' => 'Pengingat Mendatang',
            'due_date' => today()->addDays(10),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('reminders.index'));
        $response->assertOk();

        $response->assertViewHas('counts', function ($counts) {
            return $counts['all'] === 4 &&
                   $counts['pending'] === 3 &&
                   $counts['today'] === 1 &&
                   $counts['overdue'] === 1 &&
                   $counts['upcoming'] === 1 &&
                   $counts['completed'] === 1;
        });
    }

    /**
     * Memastikan agregasi metrik tab garansi di WarrantyController presisi dan bebas duplikat.
     */
    public function test_warranty_tab_counts_aggregation_is_accurate(): void
    {
        $user = User::factory()->create();

        Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Produk A',
            'brand' => 'Merk',
            'purchase_date' => today()->subMonths(6),
            'warranty_end_date' => today()->addMonths(3), // active
        ]);

        Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Produk B',
            'brand' => 'Merk',
            'purchase_date' => today()->subMonths(6),
            'warranty_end_date' => today()->addDays(10), // expiring_soon
        ]);

        Warranty::create([
            'user_id' => $user->id,
            'product_name' => 'Produk C',
            'brand' => 'Merk',
            'purchase_date' => today()->subMonths(6),
            'warranty_end_date' => today()->subDays(10), // expired
        ]);

        $response = $this->actingAs($user)->get(route('warranties.index'));
        $response->assertOk();

        $response->assertViewHas('counts', function ($counts) {
            return $counts['all'] === 3 &&
                   $counts['active'] === 1 &&
                   $counts['expiring_soon'] === 1 &&
                   $counts['expired'] === 1;
        });
    }
}

