<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_id',
        'title',
        'description',
        'due_date',
        'status',
    ];

    /**
     * Tipe casting atribut Eloquent.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    /**
     * Relasi ke pengguna pemilik pengingat.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke dokumen terkait (opsional/nullable).
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Relasi ke riwayat notifikasi yang telah terkirim.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(ReminderNotification::class);
    }

    /**
     * Relasi ke data garansi produk (jika pengingat ini dibuat dari modul garansi).
     */
    public function warranty(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Warranty::class);
    }

    /**
     * Cek apakah pengingat sudah ditandai selesai.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Cek apakah pengingat jatuh tempo hari ini.
     */
    public function isDueToday(): bool
    {
        if ($this->isCompleted()) {
            return false;
        }

        return $this->due_date->isToday();
    }

    /**
     * Cek apakah pengingat sudah melewati tanggal jatuh tempo (terlambat).
     */
    public function isOverdue(): bool
    {
        if ($this->isCompleted()) {
            return false;
        }

        return $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    /**
     * Cek apakah pengingat jatuh tempo di masa depan (mendatang).
     */
    public function isUpcoming(): bool
    {
        if ($this->isCompleted()) {
            return false;
        }

        return $this->due_date->isFuture();
    }

    /**
     * Menghitung sisa hari hingga jatuh tempo (negatif jika terlambat).
     */
    public function getDaysRemainingAttribute(): int
    {
        return (int) today()->diffInDays($this->due_date, false);
    }

    /**
     * Label status ramah pengguna dalam Bahasa Indonesia.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->isCompleted()) {
            return 'Selesai';
        }

        if ($this->isDueToday()) {
            return 'Jatuh Tempo Hari Ini';
        }

        if ($this->isOverdue()) {
            $days = abs($this->days_remaining);
            return "Terlambat {$days} hari";
        }

        $days = $this->days_remaining;
        return "{$days} hari lagi";
    }

    /**
     * Kelas badge status CSS.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        if ($this->isCompleted()) {
            return 'badge-status-active';
        }

        if ($this->isDueToday() || $this->isOverdue()) {
            return 'badge-status-expired';
        }

        if ($this->days_remaining <= 7) {
            return 'badge-status-warning';
        }

        return 'badge-category';
    }

    // ==========================================
    // Query Scopes
    // ==========================================

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'pending')->whereDate('due_date', '<', today());
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->where('status', 'pending')->whereDate('due_date', today());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'pending')->whereDate('due_date', '>', today());
    }
}
