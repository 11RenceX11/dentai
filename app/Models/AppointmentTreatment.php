<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentTreatment extends Model
{
    protected $table = 'appointment_treatments';

    protected $fillable = [
        'appointment_id', 'treatment_id', 'performed_by', 'tooth_number',
        'cost_at_time', 'status', 'clinical_notes', 'recommendations',
    ];

    protected function casts(): array
    {
        return ['cost_at_time' => 'decimal:2'];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(Dentist::class, 'performed_by');
    }

}
