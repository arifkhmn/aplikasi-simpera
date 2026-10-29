<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warranty extends Model
{
    use HasFactory;

    /**
     * Kolom-kolom yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'document_id',
        'reminder_id',
        'product_name',
        'brand',
        'serial_number',
        'purchase_date',
        'warranty_end_date',
        'purchase_price',
        'store_name',
        'notes',
    ];

    /**
     * Tipe data bawaan yang di-cast otomatis.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'purchase_date' => 'date',
        'warranty_end_date' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    /**
     * Relasi ke pemilik data garansi (Pengguna).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke dokumen nota/bukti pembelian di Document Vault (opsional).
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Relasi ke pengingat otomatis di Smart Reminders (opsional).
     */
    public function reminder(): BelongsTo
    {
        return $this->belongsTo(Reminder::class);
    }

    /**
     * Menghitung status garansi secara otomatis berdasarkan tanggal berakhir:
     * - 'expired': jika tanggal berakhir sudah lewat (< today())
     * - 'expiring_soon': jika tanggal berakhir dalam 30 hari ke depan, termasuk hari ini (today() s/d today() + 30)
     * - 'active': jika tanggal berakhir masih lebih dari 30 hari lagi (> today() + 30)
     */
    public function getStatusAttribute(): string
    {
        $today = today();
        $endDate = $this->warranty_end_date;

        if ($endDate->lt($today)) {
            return 'expired';
        }

        if ($endDate->lte($today->copy()->addDays(30))) {
            return 'expiring_soon';
        }

        return 'active';
    }

    /**
     * Mendapatkan label teks status berbahasa Indonesia.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Aktif',
            'expiring_soon' => 'Hampir Berakhir',
            'expired' => 'Kedaluwarsa',
            default => 'Tidak Diketahui',
        };
    }

    /**
     * Mendapatkan kelas CSS badge status (solid, tanpa gradien).
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active' => 'badge-status-active',
            'expiring_soon' => 'badge-status-warning',
            'expired' => 'badge-status-expired',
            default => 'badge-category',
        };
    }

    /**
     * Menghitung sisa hari hingga masa garansi berakhir.
     * Bernilai positif jika di masa depan, 0 jika hari ini, dan negatif jika telah lewat.
     */
    public function getDaysRemainingAttribute(): int
    {
        return (int) today()->diffInDays($this->warranty_end_date, false);
    }

    /**
     * Memeriksa apakah masa garansi telah kedaluwarsa.
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Memeriksa apakah masa garansi hampir berakhir (dalam 30 hari ke depan).
     */
    public function isExpiringSoon(): bool
    {
        return $this->status === 'expiring_soon';
    }

    /**
     * Memeriksa apakah masa garansi masih aktif (> 30 hari).
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Format harga pembelian ke Rupiah standar.
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->purchase_price === null) {
            return '-';
        }

        return 'Rp ' . number_format($this->purchase_price, 0, ',', '.');
    }

    /**
     * Scope: Garansi yang masih aktif (> 30 hari ke depan).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereDate('warranty_end_date', '>', today()->addDays(30));
    }

    /**
     * Scope: Garansi yang hampir berakhir (dalam 30 hari ke depan, termasuk hari ini).
     */
    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->whereDate('warranty_end_date', '>=', today())
            ->whereDate('warranty_end_date', '<=', today()->addDays(30));
    }

    /**
     * Scope: Garansi yang sudah kedaluwarsa (sudah lewat dari hari ini).
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereDate('warranty_end_date', '<', today());
    }

    /**
     * Scope: Garansi yang masih berlaku (baik aktif maupun hampir berakhir).
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->whereDate('warranty_end_date', '>=', today());
    }
}
