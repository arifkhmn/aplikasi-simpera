<?php

namespace App\Http\Controllers;

use App\Models\Warranty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WarrantyController extends Controller
{
    /**
     * Menampilkan katalog daftar garansi produk milik pengguna yang sedang login.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Warranty::class);

        $user = $request->user();
        $currentStatus = $request->query('status', 'all');
        $currentSearch = trim((string) $request->query('q', ''));

        // Query dasar terikat pengguna yang terautentikasi
        $query = $user->warranties()->with('document');

        // Filter berdasarkan pencarian nama produk, merek, toko, atau nomor seri
        if ($currentSearch !== '') {
            $query->where(function ($q) use ($currentSearch) {
                $q->where('product_name', 'like', "%{$currentSearch}%")
                    ->orWhere('brand', 'like', "%{$currentSearch}%")
                    ->orWhere('store_name', 'like', "%{$currentSearch}%")
                    ->orWhere('serial_number', 'like', "%{$currentSearch}%");
            });
        }

        // Filter berdasarkan status masa garansi
        $today = today();
        match ($currentStatus) {
            'active' => $query->whereDate('warranty_end_date', '>', $today->copy()->addDays(30)),
            'expiring_soon' => $query->whereDate('warranty_end_date', '>=', $today)
                ->whereDate('warranty_end_date', '<=', $today->copy()->addDays(30)),
            'expired' => $query->whereDate('warranty_end_date', '<', $today),
            default => null,
        };

        // Urutkan garansi yang paling mendekati batas akhir garansi terlebih dahulu
        $warranties = $query->orderBy('warranty_end_date', 'asc')
            ->paginate(10)
            ->withQueryString();

        // Hitung total untuk counter pada masing-masing tab (satukan dalam 1 kueri agregasi tunggal)
        $todayDate = $today->toDateString();
        $expiringThreshold = $today->copy()->addDays(30)->toDateString();

        $stats = $user->warranties()
            ->selectRaw('
                COUNT(*) as total_all,
                COUNT(CASE WHEN date(warranty_end_date) > ? THEN 1 END) as total_active,
                COUNT(CASE WHEN date(warranty_end_date) >= ? AND date(warranty_end_date) <= ? THEN 1 END) as total_expiring_soon,
                COUNT(CASE WHEN date(warranty_end_date) < ? THEN 1 END) as total_expired
            ', [$expiringThreshold, $todayDate, $expiringThreshold, $todayDate])
            ->first();

        $counts = [
            'all' => (int) ($stats->total_all ?? 0),
            'active' => (int) ($stats->total_active ?? 0),
            'expiring_soon' => (int) ($stats->total_expiring_soon ?? 0),
            'expired' => (int) ($stats->total_expired ?? 0),
        ];

        return view('warranties.index', [
            'warranties' => $warranties,
            'counts' => $counts,
            'currentStatus' => $currentStatus,
            'currentSearch' => $currentSearch,
        ]);
    }

    /**
     * Menampilkan formulir pendaftaran garansi produk baru.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Warranty::class);

        $user = $request->user();
        $selectedDocumentId = $request->query('document_id');
        $preselectedDocId = null;
        $prefillDocument = null;

        if ($selectedDocumentId) {
            // Pastikan dokumen yang dipilih hanya diterima jika milik pengguna yang sedang login
            $prefillDocument = $user->documents()->find($selectedDocumentId);
            if ($prefillDocument) {
                $preselectedDocId = $prefillDocument->id;
            }
        }

        $documents = $user->documents()->orderBy('title')->get();

        return view('warranties.create', [
            'documents' => $documents,
            'preselectedDocId' => $preselectedDocId,
            'prefillDocument' => $prefillDocument,
        ]);
    }

    /**
     * Menyimpan data garansi baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Warranty::class);

        $user = $request->user();

        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'warranty_end_date' => ['required', 'date', 'after_or_equal:purchase_date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document_id' => [
                'nullable',
                Rule::exists('documents', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }),
            ],
            'create_reminder' => ['nullable', 'boolean'],
        ], [
            'product_name.required' => 'Nama produk wajib diisi.',
            'brand.required' => 'Merek produk wajib diisi.',
            'purchase_date.required' => 'Tanggal pembelian wajib diisi.',
            'warranty_end_date.required' => 'Tanggal garansi berakhir wajib diisi.',
            'warranty_end_date.after_or_equal' => 'Tanggal garansi berakhir harus sama atau setelah tanggal pembelian.',
            'purchase_price.numeric' => 'Harga pembelian harus berupa angka yang valid.',
            'document_id.exists' => 'Nota pembelian yang dipilih tidak valid atau bukan milik Anda.',
        ]);

        // Buat data garansi
        $warranty = $user->warranties()->create([
            'document_id' => $validated['document_id'] ?? null,
            'product_name' => $validated['product_name'],
            'brand' => $validated['brand'],
            'serial_number' => $validated['serial_number'] ?? null,
            'purchase_date' => $validated['purchase_date'],
            'warranty_end_date' => $validated['warranty_end_date'],
            'purchase_price' => $validated['purchase_price'] ?? null,
            'store_name' => $validated['store_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Integrasi aman dengan Smart Reminders (jika opsi dipilih/aktif)
        if ($request->boolean('create_reminder')) {
            $reminder = $user->reminders()->create([
                'document_id' => $warranty->document_id,
                'title' => "Batas Akhir Garansi: {$warranty->brand} {$warranty->product_name}",
                'due_date' => $warranty->warranty_end_date,
                'description' => "Pengingat otomatis masa berlaku garansi produk {$warranty->product_name} ({$warranty->brand})"
                    . ($warranty->serial_number ? " • S/N: {$warranty->serial_number}" : '')
                    . ($warranty->store_name ? " • Toko: {$warranty->store_name}" : ''),
                'status' => 'pending',
            ]);

            $warranty->update(['reminder_id' => $reminder->id]);
        }

        return redirect()->route('warranties.show', $warranty)
            ->with('status', 'Data garansi produk berhasil ditambahkan ke katalog.');
    }

    /**
     * Menampilkan detail lengkap suatu garansi produk.
     */
    public function show(Warranty $warranty): View
    {
        Gate::authorize('view', $warranty);

        $warranty->load(['document', 'reminder']);

        return view('warranties.show', [
            'warranty' => $warranty,
        ]);
    }

    /**
     * Menampilkan formulir edit garansi produk.
     */
    public function edit(Request $request, Warranty $warranty): View
    {
        Gate::authorize('update', $warranty);

        $user = $request->user();
        $documents = $user->documents()->orderBy('title')->get();

        return view('warranties.edit', [
            'warranty' => $warranty,
            'documents' => $documents,
        ]);
    }

    /**
     * Memperbarui data garansi di database.
     */
    public function update(Request $request, Warranty $warranty): RedirectResponse
    {
        Gate::authorize('update', $warranty);

        $user = $request->user();

        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'warranty_end_date' => ['required', 'date', 'after_or_equal:purchase_date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document_id' => [
                'nullable',
                Rule::exists('documents', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }),
            ],
            'create_reminder' => ['nullable', 'boolean'],
        ], [
            'product_name.required' => 'Nama produk wajib diisi.',
            'brand.required' => 'Merek produk wajib diisi.',
            'purchase_date.required' => 'Tanggal pembelian wajib diisi.',
            'warranty_end_date.required' => 'Tanggal garansi berakhir wajib diisi.',
            'warranty_end_date.after_or_equal' => 'Tanggal garansi berakhir harus sama atau setelah tanggal pembelian.',
            'purchase_price.numeric' => 'Harga pembelian harus berupa angka yang valid.',
            'document_id.exists' => 'Nota pembelian yang dipilih tidak valid atau bukan milik Anda.',
        ]);

        $warranty->update([
            'document_id' => $validated['document_id'] ?? null,
            'product_name' => $validated['product_name'],
            'brand' => $validated['brand'],
            'serial_number' => $validated['serial_number'] ?? null,
            'purchase_date' => $validated['purchase_date'],
            'warranty_end_date' => $validated['warranty_end_date'],
            'purchase_price' => $validated['purchase_price'] ?? null,
            'store_name' => $validated['store_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Tangani sinkronisasi pengingat secara aman
        if ($warranty->reminder_id && $warranty->reminder) {
            // Perbarui tanggal jatuh tempo dan judul pengingat yang terhubung
            $warranty->reminder->update([
                'title' => "Batas Akhir Garansi: {$warranty->brand} {$warranty->product_name}",
                'due_date' => $warranty->warranty_end_date,
                'document_id' => $warranty->document_id,
            ]);
        } elseif ($request->boolean('create_reminder')) {
            // Buat pengingat baru jika sebelumnya belum ada
            $reminder = $user->reminders()->create([
                'document_id' => $warranty->document_id,
                'title' => "Batas Akhir Garansi: {$warranty->brand} {$warranty->product_name}",
                'due_date' => $warranty->warranty_end_date,
                'description' => "Pengingat otomatis masa berlaku garansi produk {$warranty->product_name} ({$warranty->brand})"
                    . ($warranty->serial_number ? " • S/N: {$warranty->serial_number}" : '')
                    . ($warranty->store_name ? " • Toko: {$warranty->store_name}" : ''),
                'status' => 'pending',
            ]);

            $warranty->update(['reminder_id' => $reminder->id]);
        }

        return redirect()->route('warranties.show', $warranty)
            ->with('status', 'Data garansi produk berhasil diperbarui.');
    }

    /**
     * Menghapus data garansi dari database.
     * Pengingat yang terkait tidak ikut dihapus agar jadwal pengingat pengguna tetap terjaga.
     */
    public function destroy(Warranty $warranty): RedirectResponse
    {
        Gate::authorize('delete', $warranty);

        $warranty->delete();

        return redirect()->route('warranties.index')
            ->with('status', 'Data garansi produk berhasil dihapus.');
    }
}
