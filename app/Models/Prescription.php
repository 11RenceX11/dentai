<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Prescription extends Model
{
    /** The times of day a dentist can pick from. */
    public const TIME_SLOTS = [
        '08:00' => 'Morning (8:00 AM)',
        '12:00' => 'Noon (12:00 PM)',
        '18:00' => 'Evening (6:00 PM)',
        '21:00' => 'Bedtime (9:00 PM)',
    ];

    protected $fillable = [
        'appointment_id', 'medication_name', 'dosage', 'times', 'start_date',
        'duration_days', 'instructions', 'prescribed_by', 'prescribed_date',
    ];

    protected function casts(): array
    {
        return ['times' => 'array', 'start_date' => 'date', 'prescribed_date' => 'date'];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function prescriber(): BelongsTo
    {
        return $this->belongsTo(Dentist::class, 'prescribed_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MedicationLog::class);
    }

    public function endDate(): Carbon
    {
        return $this->start_date->copy()->addDays(max(1, $this->duration_days) - 1);
    }

    public function activeOn(Carbon $day): bool
    {
        $day = $day->copy()->startOfDay();

        return $day->greaterThanOrEqualTo($this->start_date->copy()->startOfDay())
            && $day->lessThanOrEqualTo($this->endDate()->startOfDay());
    }

    /** "1 pill · Morning, Bedtime · for 5 days" — plain-language summary for slips and screens. */
    public function summary(): string
    {
        $times = collect($this->times ?? [])->map(fn ($t) => self::TIME_SLOTS[$t] ?? $t)->implode(', ');

        return "{$this->dosage} · {$times} · for {$this->duration_days} " . ($this->duration_days === 1 ? 'day' : 'days');
    }
}
