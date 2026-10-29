<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar dokumen.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Tentukan apakah pengguna dapat melihat detail dokumen tertentu.
     */
    public function view(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }

    /**
     * Tentukan apakah pengguna dapat mengunggah / membuat dokumen baru.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Tentukan apakah pengguna dapat memperbarui dokumen tertentu.
     */
    public function update(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }

    /**
     * Tentukan apakah pengguna dapat menghapus dokumen tertentu.
     */
    public function delete(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }

    /**
     * Tentukan apakah pengguna dapat mengunduh berkas dokumen.
     */
    public function download(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }

    /**
     * Tentukan apakah pengguna dapat melihat pratinjau berkas dokumen (PDF/gambar).
     */
    public function preview(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }
}
