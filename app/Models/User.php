<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'username', 'password', 'role',
        'dentist_id', 'staff_id', 'patient_id', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isPatient(): bool
    {
        return $this->role === 'patient';
    }

    /** Where this person lands after logging in. */
    public function homeRoute(): string
    {
        return $this->isPatient() ? 'portal.home' : 'dashboard';
    }

    public function roleLabel(): string
    {
        return ['admin' => 'Admin', 'dentist' => 'Dentist', 'staff' => 'Assistant', 'patient' => 'Patient'][$this->role] ?? $this->role;
    }
}
