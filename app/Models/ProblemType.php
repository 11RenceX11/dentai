<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProblemType extends Model
{
    protected $fillable = ['name', 'description', 'default_urgency', 'suggested_treatment_id'];

    public function suggestedTreatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class, 'suggested_treatment_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
