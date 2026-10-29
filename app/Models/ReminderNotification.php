<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'reminder_id',
        'interval_days',
        'channel',
        'sent_at',
        'status',
    ];

    /**
     * Tipe casting atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interval_days' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke pengingat induk.
     */
    public function reminder(): BelongsTo
    {
        return $this->belongsTo(Reminder::class);
    }
}
