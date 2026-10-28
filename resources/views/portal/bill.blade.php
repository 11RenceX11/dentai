@extends('layouts.app')
@section('title', 'My bill')

@section('content')
<a href="{{ route('portal.home') }}" class="text-slate-500 hover:text-slate-800">← Back</a>
<h1 class="mt-3">Your bill</h1>

@php $waiting = request()->boolean('paid') && $billing->balance() > 0; @endphp
@if ($waiting)
    <meta http-equiv="refresh" content="6">
    <div class="mt-6 rounded-xl bg-blue-50 px-5 py-4 text-blue-900">⏳ Thanks! We’re confirming your payment. This page will update by itself.</div>
@endif

<div class="card mt-6">
    <ul class="divide-y divide-slate-100">
        @foreach ($billing->appointment->treatments->where('status', '!=', 'cancelled') as $line)
            <li class="flex justify-between py-3"><span>{{ $line->treatment->treatment_name }}</span><x-money :value="$line->cost_at_time" /></li>
        @endforeach
    </ul>
    <div class="mt-4 space-y-1 border-t border-slate-200 pt-4 text-lg">
        <p class="flex justify-between"><span>Total</span><strong><x-money :value="$billing->total_amount" /></strong></p>
        <p class="flex justify-between text-slate-600"><span>Paid</span><span><x-money :value="$billing->amountPaid()" /></span></p>
        <p class="flex justify-between text-xl"><span class="font-semibold">Left to pay</span><strong class="text-blue-700"><x-money :value="$billing->balance()" /></strong></p>
    </div>
</div>

@if ($billing->balance() <= 0)
    <div class="mt-6 rounded-xl bg-emerald-50 px-5 py-4 text-lg font-semibold text-emerald-800">✓ Paid in full. Thank you!</div>
@else
    <div class="card mt-6">
        <h2>How to pay</h2>
        @php $canPay = $billing->allow_online && $paymongoReady; @endphp

        <form method="POST" action="{{ route('portal.bills.pay', $billing) }}" class="mt-4">
            @csrf
            <button class="btn btn-primary w-full py-4 text-lg" @disabled(! $canPay)>Pay <x-money :value="$billing->balance()" /> online</button>
        </form>

        @if ($canPay)
            <p class="hint mt-3">You’ll choose GCash, Maya, card or QR on PayMongo’s secure page.</p>
        @elseif (! $billing->allow_online)
            <p class="mt-3 text-slate-600">Online payment isn’t available for this bill. Please pay at the clinic.</p>
        @else
            <p class="mt-3 text-slate-600">Online payment isn’t set up yet. Please pay at the clinic.</p>
        @endif
    </div>
@endif
@endsection
