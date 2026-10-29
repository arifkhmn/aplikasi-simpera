<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi ke seluruh dokumen yang dimiliki pengguna.
     */
    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Relasi ke kategori kustom yang dibuat pengguna.
     */
    public function categories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Relasi ke seluruh pengingat yang dibuat pengguna.
     */
    public function reminders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Relasi ke seluruh data garansi produk milik pengguna.
     */
    public function warranties(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Warranty::class);
    }
}
