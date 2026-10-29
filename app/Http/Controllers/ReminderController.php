<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReminderController extends Controller
{
    /**
     * Menampilkan daftar pengingat milik pengguna yang sedang login.
     * Mendukung filter status (Semua, Jatuh Tempo Hari Ini, Terlambat, Mendatang, Selesai) dan pencarian.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Reminder::class);

        $user = $request->user();
        $statusFilter = $request->query('status', 'all');
        $search = trim($request->query('q', ''));

        $query = $user->reminders()->with('document');

        // Pencarian berdasarkan judul pengingat
        if (! empty($search)) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        // Filter berdasarkan status & kategori tanggal
        match ($statusFilter) {
            'pending' => $query->pending(),
            'today' => $query->dueToday(),
            'overdue' => $query->overdue(),
            'upcoming' => $query->upcoming(),
            'completed' => $query->completed(),
            default => $query,
        };

        // Urutan: pengingat tertunda diurutkan berdasarkan tanggal jatuh tempo terdekat
        $reminders = $query->orderBy('status', 'desc')
            ->orderBy('due_date', 'asc')
            ->paginate(10)
            ->withQueryString();

        // Penghitungan metrik tab untuk navigasi cepat (satukan dalam 1 kueri agregasi tunggal)
        $todayDate = today()->toDateString();

        $stats = $user->reminders()
            ->selectRaw("
                COUNT(*) as total_all,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as total_pending,
                COUNT(CASE WHEN status = 'pending' AND date(due_date) = ? THEN 1 END) as total_today,
                COUNT(CASE WHEN status = 'pending' AND date(due_date) < ? THEN 1 END) as total_overdue,
                COUNT(CASE WHEN status = 'pending' AND date(due_date) > ? THEN 1 END) as total_upcoming,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as total_completed
            ", [$todayDate, $todayDate, $todayDate])
            ->first();

        $counts = [
            'all' => (int) ($stats->total_all ?? 0),
            'pending' => (int) ($stats->total_pending ?? 0),
            'today' => (int) ($stats->total_today ?? 0),
            'overdue' => (int) ($stats->total_overdue ?? 0),
            'upcoming' => (int) ($stats->total_upcoming ?? 0),
            'completed' => (int) ($stats->total_completed ?? 0),
        ];

        return view('reminders.index', [
            'reminders' => $reminders,
            'counts' => $counts,
            'currentStatus' => $statusFilter,
            'currentSearch' => $search,
        ]);
    }

    /**
     * Menampilkan formulir pembuatan pengingat baru.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Reminder::class);

        $user = $request->user();
        $selectedDocumentId = $request->query('document_id');
        $preselectedDocId = null;
        $prefillDocument = null;

        if ($selectedDocumentId) {
            // Hanya terima jika dokumen tersebut milik pengguna yang sedang login
            $prefillDocument = $user->documents()->find($selectedDocumentId);
            if ($prefillDocument) {
                $preselectedDocId = $prefillDocument->id;
            }
        }

        $documents = $user->documents()->orderBy('title')->get();

        return view('reminders.create', [
            'documents' => $documents,
            'preselectedDocId' => $preselectedDocId,
            'prefillDocument' => $prefillDocument,
        ]);
    }

    /**
     * Menyimpan pengingat baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Reminder::class);

        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['required', 'date'],
            'document_id' => [
                'nullable',
                Rule::exists('documents', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }),
            ],
        ], [
            'title.required' => 'Judul pengingat wajib diisi.',
            'due_date.required' => 'Tanggal jatuh tempo wajib diisi.',
            'due_date.date' => 'Format tanggal jatuh tempo tidak valid.',
            'document_id.exists' => 'Dokumen yang dipilih tidak valid atau bukan milik Anda.',
        ]);

        $user->reminders()->create([
            'document_id' => $validated['document_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'],
            'status' => 'pending',
        ]);

        return redirect()->route('reminders.index')
            ->with('status', 'Pengingat berhasil ditambahkan ke jadwal Anda.');
    }

    /**
     * Menampilkan detail lengkap suatu pengingat.
     */
    public function show(Reminder $reminder): View
    {
        Gate::authorize('view', $reminder);

        $reminder->load(['document', 'notifications']);

        return view('reminders.show', [
            'reminder' => $reminder,
            'notifications' => $reminder->notifications,
        ]);
    }

    /**
     * Menampilkan formulir edit pengingat.
     */
    public function edit(Request $request, Reminder $reminder): View
    {
        Gate::authorize('update', $reminder);

        $documents = $request->user()->documents()->orderBy('title')->get();

        return view('reminders.edit', [
            'reminder' => $reminder,
            'documents' => $documents,
        ]);
    }

    /**
     * Memperbarui data pengingat.
     */
    public function update(Request $request, Reminder $reminder): RedirectResponse
    {
        Gate::authorize('update', $reminder);
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['required', 'date'],
            'status' => ['required', 'string', Rule::in(['pending', 'completed'])],
            'document_id' => [
                'nullable',
                Rule::exists('documents', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }),
            ],
        ], [
            'title.required' => 'Judul pengingat wajib diisi.',
            'due_date.required' => 'Tanggal jatuh tempo wajib diisi.',
            'status.in' => 'Status pengingat tidak valid.',
            'document_id.exists' => 'Dokumen yang dipilih tidak valid atau bukan milik Anda.',
        ]);

        $reminder->update([
            'document_id' => $validated['document_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('reminders.show', $reminder)
            ->with('status', 'Pengingat berhasil diperbarui.');
    }

    /**
     * Menghapus pengingat secara permanen.
     */
    public function destroy(Reminder $reminder): RedirectResponse
    {
        Gate::authorize('delete', $reminder);

        $reminder->delete();

        return redirect()->route('reminders.index')
            ->with('status', 'Pengingat berhasil dihapus.');
    }

    /**
     * Mengubah status pengingat antara selesai dan aktif kembali (Toggle Status).
     */
    public function toggleStatus(Reminder $reminder): RedirectResponse
    {
        Gate::authorize('update', $reminder);

        $newStatus = ($reminder->status === 'completed') ? 'pending' : 'completed';
        $reminder->update(['status' => $newStatus]);

        $message = ($newStatus === 'completed')
            ? 'Pengingat berhasil ditandai selesai.'
            : 'Pengingat diaktifkan kembali ke daftar aktif.';

        return back()->with('status', $message);
    }
}
