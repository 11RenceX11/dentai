<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'phone_number', 'email', 'gender', 'date_of_birth',
        'address_line1', 'city', 'state_province', 'date_registered', 'access_code',
    ];

    protected $hidden = ['access_code'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'date_registered' => 'date', 'access_code' => 'encrypted'];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalHistories(): HasMany
    {
        return $this->hasMany(PatientMedicalHistory::class);
    }

    public function xrayImages(): HasMany
    {
        return $this->hasMany(XrayImage::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
