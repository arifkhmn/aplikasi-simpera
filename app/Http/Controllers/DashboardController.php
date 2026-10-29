<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard utama Simpera untuk pengguna yang terautentikasi.
     * Menggunakan data dokumen dan pengingat riil milik pengguna dari database.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $isDemoView = ($request->query('view') === 'demo');

        if ($isDemoView) {
            // Tampilan pratinjau data contoh (Demo)
            $metrics = [
                'total_documents' => 8,
                'upcoming_reminders' => 2,
                'active_warranties' => 4,
            ];

            $documents = collect([
                (object) [
                    'id' => 991,
                    'title' => 'Kartu Tanda Penduduk (KTP Digital)',
                    'original_filename' => 'ktp_digital_demo.pdf',
                    'category' => (object) ['name' => 'Identitas Pribadi'],
                    'created_at' => now()->subDays(5),
                    'document_date' => now()->subDays(5),
                    'expiry_date' => null,
                    'is_pdf' => true,
                    'is_image' => false,
                    'is_expired' => false,
                    'is_expiring_soon' => false,
                    'status_label' => 'Aktif',
                    'status_type' => 'active',
                    'is_demo' => true,
                ],
                (object) [
                    'id' => 992,
                    'title' => 'Polis Asuransi Kesehatan Keluarga',
                    'original_filename' => 'asuransi_kesehatan_2026.pdf',
                    'category' => (object) ['name' => 'Asuransi & Finansial'],
                    'created_at' => now()->subDays(12),
                    'document_date' => now()->subDays(12),
                    'expiry_date' => now()->addDays(60),
                    'is_pdf' => true,
                    'is_image' => false,
                    'is_expired' => false,
                    'is_expiring_soon' => false,
                    'status_label' => 'Aktif',
                    'status_type' => 'active',
                    'is_demo' => true,
                ],
                (object) [
                    'id' => 993,
                    'title' => 'Garansi Resmi Laptop ThinkPad X1',
                    'original_filename' => 'invoice_garansi_laptop.pdf',
                    'category' => (object) ['name' => 'Elektronik & Garansi'],
                    'created_at' => now()->subMonths(10),
                    'document_date' => now()->subMonths(10),
                    'expiry_date' => now()->addDays(14),
                    'is_pdf' => true,
                    'is_image' => false,
                    'is_expired' => false,
                    'is_expiring_soon' => true,
                    'status_label' => 'Segera Berakhir',
                    'status_type' => 'warning',
                    'is_demo' => true,
                ],
                (object) [
                    'id' => 994,
                    'title' => 'Surat Izin Mengemudi (SIM A)',
                    'original_filename' => 'sim_a_demo.jpg',
                    'category' => (object) ['name' => 'Identitas Pribadi'],
                    'created_at' => now()->subMonths(6),
                    'document_date' => now()->subMonths(6),
                    'expiry_date' => now()->addDays(20),
                    'is_pdf' => false,
                    'is_image' => true,
                    'is_expired' => false,
                    'is_expiring_soon' => true,
                    'status_label' => 'Segera Berakhir',
                    'status_type' => 'warning',
                    'is_demo' => true,
                ],
            ]);

            $nearestReminders = collect([
                (object) [
                    'id' => 801,
                    'title' => 'Perpanjangan Masa Berlaku SIM A',
                    'due_date' => today()->addDays(20),
                    'status' => 'pending',
                    'days_remaining' => 20,
                    'status_label' => '20 hari lagi',
                    'status_badge_class' => 'badge-category',
                    'document' => (object) ['title' => 'Surat Izin Mengemudi (SIM A)'],
                    'is_demo' => true,
                ],
                (object) [
                    'id' => 802,
                    'title' => 'Klaim Servis Berkala Garansi Laptop',
                    'due_date' => today()->addDays(14),
                    'status' => 'pending',
                    'days_remaining' => 14,
                    'status_label' => '14 hari lagi',
                    'status_badge_class' => 'badge-status-warning',
                    'document' => (object) ['title' => 'Garansi Resmi Laptop ThinkPad X1'],
                    'is_demo' => true,
                ],
            ]);

            $warrantyCounts = [
                'total' => 5,
                'active' => 3,
                'expiring_soon' => 1,
                'expired' => 1,
            ];

            $isEmptyState = false;
        } else {
            // Data Riil Akun Pengguna yang Sedang Login
            $totalDocs = $user->documents()->count();

            // Pengingat: pengingat aktif yang jatuh tempo hari ini, terlambat, atau dalam 30 hari ke depan
            $upcomingReminders = $user->reminders()
                ->pending()
                ->whereDate('due_date', '<=', today()->addDays(30))
                ->count();

            // Pengingat terdekat untuk ringkasan di dashboard
            $nearestReminders = $user->reminders()
                ->with('document')
                ->pending()
                ->orderBy('due_date', 'asc')
                ->take(4)
                ->get();

            // Garansi: hitung total dan status garansi dalam 1 kueri agregasi kondisional
            $todayDate = today()->toDateString();
            $expiringThreshold = today()->addDays(30)->toDateString();

            $warrantyStats = $user->warranties()
                ->selectRaw('
                    COUNT(*) as total,
                    COUNT(CASE WHEN date(warranty_end_date) >= ? THEN 1 END) as active_warranties,
                    COUNT(CASE WHEN date(warranty_end_date) > ? THEN 1 END) as active,
                    COUNT(CASE WHEN date(warranty_end_date) >= ? AND date(warranty_end_date) <= ? THEN 1 END) as expiring_soon,
                    COUNT(CASE WHEN date(warranty_end_date) < ? THEN 1 END) as expired
                ', [$todayDate, $expiringThreshold, $todayDate, $expiringThreshold, $todayDate])
                ->first();

            $totalWarranties = (int) ($warrantyStats->total ?? 0);
            $activeWarranties = (int) ($warrantyStats->active_warranties ?? 0);

            $warrantyCounts = [
                'total' => $totalWarranties,
                'active' => (int) ($warrantyStats->active ?? 0),
                'expiring_soon' => (int) ($warrantyStats->expiring_soon ?? 0),
                'expired' => (int) ($warrantyStats->expired ?? 0),
            ];

            $metrics = [
                'total_documents' => $totalDocs,
                'upcoming_reminders' => $upcomingReminders,
                'active_warranties' => $activeWarranties,
            ];

            // 5 Dokumen terbaru milik pengguna
            $documents = $user->documents()
                ->with('category')
                ->latest()
                ->take(5)
                ->get();

            // Gunakan kembali nilai yang sudah dihitung untuk menentukan status kosong tanpa kueri duplikat
            $isEmptyState = ($totalDocs === 0 && $totalWarranties === 0 && $user->reminders()->count() === 0);
        }

        return view('dashboard', [
            'metrics' => $metrics,
            'documents' => $documents,
            'nearestReminders' => $nearestReminders,
            'warrantyCounts' => $warrantyCounts,
            'isDemoView' => $isDemoView,
            'isEmptyState' => $isEmptyState,
        ]);
    }
}
