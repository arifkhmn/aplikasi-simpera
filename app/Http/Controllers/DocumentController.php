<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Menampilkan daftar dokumen milik pengguna yang sedang login.
     * Mendukung pencarian judul, filter kategori, dan filter favorit.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Document::class);

        $user = $request->user();

        $query = $user->documents()->with('category');

        // Pencarian berdasarkan judul
        if ($request->filled('q')) {
            $searchTerm = '%' . trim($request->query('q')) . '%';
            $query->where('title', 'like', $searchTerm);
        }

        // Filter berdasarkan kategori
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        // Filter dokumen favorit
        if ($request->filled('favorite') && $request->query('favorite') === '1') {
            $query->where('is_favorite', true);
        }

        // Pengurutan dokumen terbaru
        $documents = $query->latest()->paginate(10)->withQueryString();

        // Kategori yang tersedia untuk dropdown filter
        $categories = $user->categories()->orderBy('name')->get();

        return view('documents.index', [
            'documents' => $documents,
            'categories' => $categories,
            'currentSearch' => $request->query('q', ''),
            'selectedCategory' => $request->query('category_id', ''),
            'isFavoriteFilter' => $request->query('favorite') === '1',
        ]);
    }

    /**
     * Menampilkan formulir penambahan dokumen baru.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Document::class);

        $categories = $request->user()->categories()->orderBy('name')->get();

        return view('documents.create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Menyimpan dokumen baru dan mengunggah berkas ke penyimpanan privat.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Document::class);

        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }),
            ],
            'new_category' => ['nullable', 'string', 'max:50'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:10240'],
            'document_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'is_favorite' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'Judul dokumen wajib diisi.',
            'title.max' => 'Judul dokumen maksimal 255 karakter.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid atau bukan milik Anda.',
            'file.required' => 'Berkas dokumen wajib diunggah.',
            'file.mimes' => 'Format berkas hanya boleh berupa PDF, JPG, JPEG, atau PNG.',
            'file.mimetypes' => 'Format konten berkas hanya boleh berupa PDF, JPG, JPEG, atau PNG.',
            'file.max' => 'Ukuran berkas maksimal adalah 10 MB.',
            'document_date.date' => 'Format tanggal dokumen tidak valid.',
            'expiry_date.date' => 'Format tanggal kedaluwarsa tidak valid.',
        ]);

        $file = $request->file('file');
        $storedPath = null;

        try {
            $document = DB::transaction(function () use ($user, $validated, $file, &$storedPath) {
                // Pembuatan kategori baru on-the-fly jika diisi
                $categoryId = $validated['category_id'] ?? null;
                if (! empty($validated['new_category'])) {
                    $categoryName = trim($validated['new_category']);
                    if ($categoryName !== '') {
                        $category = Category::firstOrCreate([
                            'user_id' => $user->id,
                            'name' => $categoryName,
                        ]);
                        $categoryId = $category->id;
                    }
                }

                // Simpan berkas dengan ekstensi yang diverifikasi server dan hash acak yang aman di penyimpanan privat
                $extension = $file->guessExtension() ?: 'bin';
                $storedName = Str::random(40) . '.' . strtolower($extension);
                $directory = 'documents/' . $user->id;
                $storedPath = $directory . '/' . $storedName;

                Storage::disk('local')->putFileAs($directory, $file, $storedName);

                // Sanitasi nama berkas asli dari path traversal atau karakter berbahaya
                $safeOriginalName = basename($file->getClientOriginalName());
                $safeOriginalName = str_replace(['"', "'", "\r", "\n", "\0"], '', $safeOriginalName);
                if ($safeOriginalName === '') {
                    $safeOriginalName = 'dokumen.' . $extension;
                }

                // Selalu gunakan MIME type yang diverifikasi secara server-side (finfo), bukan dari header client
                $verifiedMime = $file->getMimeType() ?: 'application/octet-stream';

                return $user->documents()->create([
                    'category_id' => $categoryId,
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'original_filename' => $safeOriginalName,
                    'storage_path' => $storedPath,
                    'mime_type' => $verifiedMime,
                    'file_size' => $file->getSize(),
                    'document_date' => $validated['document_date'] ?? null,
                    'expiry_date' => $validated['expiry_date'] ?? null,
                    'is_favorite' => $validated['is_favorite'] ?? false,
                ]);
            });

            return redirect()->route('documents.show', $document)
                ->with('status', 'Dokumen berhasil diunggah dan disimpan secara privat di brankas digital.');
        } catch (\Throwable $e) {
            // Hapus file fisik jika transaksi database gagal
            if ($storedPath && Storage::disk('local')->exists($storedPath)) {
                Storage::disk('local')->delete($storedPath);
            }
            report($e);

            return back()->withInput()->withErrors([
                'file' => 'Gagal menyimpan dokumen ke brankas. Terjadi kesalahan pada penyimpanan berkas.',
            ]);
        }
    }

    /**
     * Menampilkan detail lengkap suatu dokumen beserta pratinjau.
     */
    public function show(Document $document): View
    {
        Gate::authorize('view', $document);

        $document->load('category');

        return view('documents.show', [
            'document' => $document,
        ]);
    }

    /**
     * Menampilkan formulir untuk mengedit metadata dokumen.
     */
    public function edit(Request $request, Document $document): View
    {
        Gate::authorize('update', $document);

        $categories = $request->user()->categories()->orderBy('name')->get();

        return view('documents.edit', [
            'document' => $document,
            'categories' => $categories,
        ]);
    }

    /**
     * Memperbarui metadata dokumen di database.
     */
    public function update(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('update', $document);

        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }),
            ],
            'new_category' => ['nullable', 'string', 'max:50'],
            'document_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'is_favorite' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'Judul dokumen wajib diisi.',
            'title.max' => 'Judul dokumen maksimal 255 karakter.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid atau bukan milik Anda.',
        ]);

        $categoryId = $validated['category_id'] ?? null;

        if (! empty($validated['new_category'])) {
            $categoryName = trim($validated['new_category']);
            if ($categoryName !== '') {
                $category = Category::firstOrCreate([
                    'user_id' => $user->id,
                    'name' => $categoryName,
                ]);
                $categoryId = $category->id;
            }
        }

        $document->update([
            'category_id' => $categoryId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'document_date' => $validated['document_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'is_favorite' => $request->boolean('is_favorite'),
        ]);

        return redirect()->route('documents.show', $document)
            ->with('status', 'Metadata dokumen berhasil diperbarui.');
    }

    /**
     * Menghapus dokumen beserta berkas fisik dari private storage.
     */
    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $filePath = $document->storage_path;
        $expectedPrefix = 'documents/' . $document->user_id . '/';

        DB::transaction(function () use ($document, $filePath, $expectedPrefix) {
            $document->delete();

            // Pastikan hanya file dalam direktori dokumen pengguna yang dihapus (mencegah path traversal)
            if (Str::startsWith($filePath, $expectedPrefix) && Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }
        });

        return redirect()->route('documents.index')
            ->with('status', 'Dokumen dan berkas fisik berhasil dihapus dari brankas.');
    }

    /**
     * Mengunduh berkas fisik dokumen secara aman setelah pemeriksaan otorisasi.
     */
    public function download(Document $document): StreamedResponse
    {
        Gate::authorize('download', $document);

        $expectedPrefix = 'documents/' . $document->user_id . '/';
        if (! Str::startsWith($document->storage_path, $expectedPrefix) || ! Storage::disk('local')->exists($document->storage_path)) {
            abort(404, 'Berkas fisik tidak ditemukan pada sistem penyimpanan privat.');
        }

        $safeFilename = str_replace(['"', "\r", "\n"], '', basename($document->original_filename));

        return Storage::disk('local')->download(
            $document->storage_path,
            $safeFilename,
            [
                'Content-Type' => $document->mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Menyajikan berkas secara inline untuk pratinjau PDF atau Gambar di browser.
     */
    public function preview(Document $document): mixed
    {
        Gate::authorize('preview', $document);

        $expectedPrefix = 'documents/' . $document->user_id . '/';
        if (! Str::startsWith($document->storage_path, $expectedPrefix) || ! Storage::disk('local')->exists($document->storage_path)) {
            abort(404, 'Berkas fisik tidak ditemukan pada sistem penyimpanan privat.');
        }

        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $mimeType = in_array($document->mime_type, $allowedMimeTypes, true)
            ? $document->mime_type
            : 'application/octet-stream';

        $safeFilename = str_replace(['"', "\r", "\n"], '', basename($document->original_filename));

        return Storage::disk('local')->response(
            $document->storage_path,
            $safeFilename,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $safeFilename . '"',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Mengubah status favorit dokumen (Toggle Favorite).
     */
    public function toggleFavorite(Document $document): RedirectResponse
    {
        Gate::authorize('update', $document);

        $document->update([
            'is_favorite' => ! $document->is_favorite,
        ]);

        $pesan = $document->is_favorite ? 'Dokumen ditambahkan ke favorit.' : 'Dokumen dihapus dari favorit.';

        return back()->with('status', $pesan);
    }
}
