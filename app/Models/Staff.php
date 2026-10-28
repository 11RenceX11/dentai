<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = ['first_name', 'last_name', 'job_title', 'hire_date', 'is_active'];

    protected function casts(): array
    {
        return ['hire_date' => 'date', 'is_active' => 'boolean'];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
