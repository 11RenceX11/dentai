<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    public const STATUSES = ['requested', 'scheduled', 'completed', 'cancelled'];
    public const URGENCIES = ['low', 'normal', 'high', 'emergency'];

    protected $fillable = [
        'patient_id', 'dentist_id', 'problem_type_id', 'problem_title', 'description', 'pain_level',
        'urgency', 'preferred_date', 'appointment_date', 'start_time', 'end_time', 'status', 'notes', 'created_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['preferred_date' => 'date', 'appointment_date' => 'date', 'pain_level' => 'integer', 'completed_at' => 'datetime'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class);
    }

    public function problemType(): BelongsTo
    {
        return $this->belongsTo(ProblemType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(AppointmentTreatment::class);
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(AppointmentSuggestion::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function isDone(): bool
    {
        return $this->status === 'completed';
    }

    /** Open = not done and not cancelled. */
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function billing(): HasOne
    {
        return $this->hasOne(Billing::class);
    }

    public function isScheduled(): bool
    {
        return $this->appointment_date !== null && $this->start_time !== null;
    }

    /** Sort weight so emergencies float to the top of lists. */
    public static function urgencyOrderSql(): string
    {
        return "CASE urgency WHEN 'emergency' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END";
    }
}
