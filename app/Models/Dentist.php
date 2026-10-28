<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Dentist extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'specialization', 'license_number',
        'email', 'phone_number', 'hire_date', 'is_active',
    ];

    protected function casts(): array
    {
        return ['hire_date' => 'date', 'is_active' => 'boolean'];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function timeOffs(): HasMany
    {
        return $this->hasMany(DentistTimeOff::class);
    }

    public function isAvailableOn(string|\DateTimeInterface $date): bool
    {
        $day = \Illuminate\Support\Carbon::parse($date)->toDateString();

        return $this->is_active
            && ! $this->timeOffs()->whereDate('start_date', '<=', $day)->whereDate('end_date', '>=', $day)->exists();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** M:M — treatments this dentist is certified for. */
    public function treatments(): BelongsToMany
    {
        return $this->belongsToMany(Treatment::class, 'dentist_treatment')
            ->withPivot(['certified_date', 'proficiency_level', 'is_active']);
    }

    public function getFullNameAttribute(): string
    {
        return "Dr. {$this->first_name} {$this->last_name}";
    }
}
