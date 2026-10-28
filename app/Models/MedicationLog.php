<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationLog extends Model
{
    protected $fillable = ['prescription_id', 'dose_date', 'dose_time', 'taken_at'];

    protected function casts(): array
    {
        return ['dose_date' => 'date', 'taken_at' => 'datetime'];
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }
}
