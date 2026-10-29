<?php

namespace App\Policies;

use App\Models\Reminder;
use App\Models\User;

class ReminderPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar pengingat.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Tentukan apakah pengguna dapat melihat pengingat tertentu.
     */
    public function view(User $user, Reminder $reminder): bool
    {
        return $user->id === $reminder->user_id;
    }

    /**
     * Tentukan apakah pengguna dapat membuat pengingat baru.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Tentukan apakah pengguna dapat memperbarui pengingat tertentu.
     */
    public function update(User $user, Reminder $reminder): bool
    {
        return $user->id === $reminder->user_id;
    }

    /**
     * Tentukan apakah pengguna dapat menghapus pengingat tertentu.
     */
    public function delete(User $user, Reminder $reminder): bool
    {
        return $user->id === $reminder->user_id;
    }
}
