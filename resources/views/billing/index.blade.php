@extends('layouts.app')
@section('title', 'Billing')

@section('content')
<h1>Billing</h1>

<div class="mt-6 flex gap-2">
    <a href="{{ route('billings.index') }}" class="rounded-full px-5 py-2 text-base font-semibold {{ $show === 'unpaid' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-300' }}">To collect</a>
    <a href="{{ route('billings.index', ['show' => 'paid']) }}" class="rounded-full px-5 py-2 text-base font-semibold {{ $show === 'paid' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-300' }}">Paid</a>
</div>

<div class="mt-6 space-y-3">
    @forelse ($billings as $b)
        <a href="{{ route('billings.show', $b) }}" class="card flex flex-wrap items-center justify-between gap-3 p-5 transition hover:border-blue-300">
            <div>
                <p class="text-lg font-semibold text-slate-900">{{ $b->appointment->patient->full_name }}</p>
                <p class="text-slate-600">Total <x-money :value="$b->total_amount" />@if ($b->balance() > 0) · Left to pay <strong><x-money :value="$b->balance()" /></strong>@endif</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($b->allow_online)<span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">Online ON</span>@endif
                <x-badge :value="$b->payment_status" />
            </div>
        </a>
    @empty
        <div class="card text-center text-slate-500">Nothing here.</div>
    @endforelse
</div>
<div class="mt-6 empty:hidden">{{ $billings->links() }}</div>
@endsection
