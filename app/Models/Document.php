<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'original_filename',
        'storage_path',
        'mime_type',
        'file_size',
        'document_date',
        'expiry_date',
        'is_favorite',
    ];

    /**
     * Tipe casting atribut Eloquent.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'expiry_date' => 'date',
            'is_favorite' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    /**
     * Relasi ke pemilik dokumen (User).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke kategori dokumen (Category).
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi ke pengingat yang terkait dengan dokumen ini.
     */
    public function reminders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Relasi ke data garansi yang menggunakan dokumen ini sebagai nota pembelian.
     */
    public function warranties(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Warranty::class);
    }

    /**
     * Format ukuran berkas ramah pengguna (misal: 2.4 MB, 512 KB).
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Mengetahui apakah dokumen bertipe gambar (JPG, PNG).
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Mengetahui apakah dokumen bertipe PDF.
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Mengetahui apakah masa berlaku dokumen sudah kedaluwarsa.
     */
    public function isExpired(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    /**
     * Mengetahui apakah masa berlaku dokumen segera berakhir dalam 30 hari ke depan.
     */
    public function isExpiringSoon(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return ! $this->isExpired() && $this->expiry_date->diffInDays(now()) <= 30;
    }
}
