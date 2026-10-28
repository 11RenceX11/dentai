<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Billing extends Model
{
    protected $fillable = [
        'appointment_id', 'total_amount', 'payment_status', 'issued_date', 'due_date',
        'created_by', 'allow_online', 'paymongo_checkout_id',
    ];

    protected function casts(): array
    {
        return ['total_amount' => 'decimal:2', 'issued_date' => 'date', 'due_date' => 'date', 'allow_online' => 'boolean'];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function amountPaid(): float
    {
        return (float) $this->payments->sum('amount_paid');
    }

    public function balance(): float
    {
        return max(0, (float) $this->total_amount - $this->amountPaid());
    }

    /** Keep payment_status in sync with the payments recorded. */
    public function refreshStatus(): void
    {
        $this->load('payments');
        $paid = $this->amountPaid();

        $status = match (true) {
            $paid >= (float) $this->total_amount && $paid > 0 => 'paid',
            $paid > 0 => 'partial',
            $this->due_date && $this->due_date->isPast() => 'overdue',
            default => 'unpaid',
        };

        $this->update(['payment_status' => $status]);
    }
}
