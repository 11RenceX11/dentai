<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedicalHistory extends Model
{
    public const TYPES = ['allergy', 'chronic_condition', 'past_surgery', 'current_medication', 'family_history', 'other'];

    protected $fillable = ['patient_id', 'history_type', 'description', 'recorded_date', 'recorded_by'];

    protected function casts(): array
    {
        return ['recorded_date' => 'date'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
