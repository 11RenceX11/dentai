<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Treatment extends Model
{
    protected $fillable = ['treatment_name', 'description', 'base_cost', 'est_duration_minutes'];

    protected function casts(): array
    {
        return ['base_cost' => 'decimal:2'];
    }

    public function dentists(): BelongsToMany
    {
        return $this->belongsToMany(Dentist::class, 'dentist_treatment')
            ->withPivot(['certified_date', 'proficiency_level', 'is_active']);
    }
}
