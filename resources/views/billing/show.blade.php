@extends('layouts.app')
@section('title', 'Bill #' . $billing->id)

@section('content')
<a href="{{ route('appointments.show', $billing->appointment) }}" class="text-slate-500 hover:text-slate-800">← Back to visit</a>
<div class="mt-3 flex flex-wrap items-center gap-3">
    <h1>{{ $billing->appointment->patient->full_name }}</h1><x-badge :value="$billing->payment_status" />
</div>

<div class="mt-8 space-y-6">
    <div class="card">
        <ul class="divide-y divide-slate-100">
            @foreach ($billing->appointment->treatments->where('status', '!=', 'cancelled') as $line)
                <li class="flex justify-between py-3"><span>{{ $line->treatment->treatment_name }}</span><x-money :value="$line->cost_at_time" /></li>
            @endforeach
        </ul>
        <div class="mt-4 space-y-1 border-t border-slate-200 pt-4 text-lg">
            <p class="flex justify-between"><span>Total</span><strong><x-money :value="$billing->total_amount" /></strong></p>
            <p class="flex justify-between text-slate-600"><span>Paid so far</span><span><x-money :value="$billing->amountPaid()" /></span></p>
            <p class="flex justify-between text-xl"><span class="font-semibold">Left to pay</span><strong class="text-blue-700"><x-money :value="$billing->balance()" /></strong></p>
        </div>
    </div>

    {{-- The online-payment switch --}}
    <div class="card">
        <h2>Online payment</h2>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-4">
            <p class="max-w-md text-slate-600">
                @if ($billing->allow_online)
                    <strong class="text-emerald-700">ON</strong> — the patient sees a “Pay online” button in their account (GCash, Maya, card, QR).
                @else
                    <strong class="text-slate-700">OFF</strong> — the patient sees the button greyed out and is asked to pay at the clinic.
                @endif
            </p>
            <form method="POST" action="{{ route('billings.online', $billing) }}">@csrf
                <button class="btn {{ $billing->allow_online ? 'btn-ghost' : 'btn-primary' }}">{{ $billing->allow_online ? 'Turn off' : 'Turn on' }}</button>
            </form>
        </div>
        @if ($billing->allow_online && ! $paymongoReady)
            <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-amber-900">PayMongo isn’t connected yet, so patients still can’t pay online. Add your keys to <code>.env</code> (see the setup guide).</p>
        @endif
    </div>

    @if ($billing->balance() > 0)
        <form method="POST" action="{{ route('payments.store', $billing) }}" class="card space-y-4">
            @csrf
            <h2>Payment received at the clinic</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">Amount (₱)</label><input name="amount_paid" type="number" step="0.01" min="0.01" max="{{ $billing->balance() }}" value="{{ $billing->balance() }}" class="input" required></div>
                <div><label class="label">How did they pay?</label>
                    <select name="payment_method" class="input">@foreach (['cash' => 'Cash', 'card' => 'Card', 'bank_transfer' => 'Bank transfer', 'other' => 'Other'] as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
            </div>
            <button class="btn btn-green">Save payment</button>
        </form>
    @endif

    @if ($billing->payments->isNotEmpty())
        <div class="card">
            <h2>Payments</h2>
            <ul class="mt-3 divide-y divide-slate-100">
                @foreach ($billing->payments as $p)
                    <li class="flex justify-between py-3"><span class="text-slate-600">{{ $p->payment_date->format('M j, Y') }} · {{ $p->payment_method === 'paymongo' ? 'Online (PayMongo)' : ucfirst(str_replace('_', ' ', $p->payment_method)) }}</span><x-money :value="$p->amount_paid" /></li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
