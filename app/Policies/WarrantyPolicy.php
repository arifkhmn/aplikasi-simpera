<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warranty;

class WarrantyPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar garansi.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Tentukan apakah pengguna dapat melihat detail garansi tertentu.
     */
    public function view(User $user, Warranty $warranty): bool
    {
        return $user->id === $warranty->user_id;
    }

    /**
     * Tentukan apakah pengguna dapat membuat data garansi baru.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Tentukan apakah pengguna dapat memperbarui data garansi tertentu.
     */
    public function update(User $user, Warranty $warranty): bool
    {
        return $user->id === $warranty->user_id;
    }

    /**
     * Tentukan apakah pengguna dapat menghapus data garansi tertentu.
     */
    public function delete(User $user, Warranty $warranty): bool
    {
        return $user->id === $warranty->user_id;
    }
}
