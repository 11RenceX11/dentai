<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XrayImage extends Model
{
    protected $table = 'xray_images';

    protected $fillable = ['patient_id', 'taken_by', 'uploaded_by', 'image_path', 'image_type', 'upload_date'];

    protected function casts(): array
    {
        return ['upload_date' => 'date'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class, 'taken_by');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
