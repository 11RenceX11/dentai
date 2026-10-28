<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['billing_id', 'amount_paid', 'payment_date', 'payment_method', 'received_by', 'provider_reference'];

    protected function casts(): array
    {
        return ['amount_paid' => 'decimal:2', 'payment_date' => 'date'];
    }

    public function billing(): BelongsTo
    {
        return $this->belongsTo(Billing::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
