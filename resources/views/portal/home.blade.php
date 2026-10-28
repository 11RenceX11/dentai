@extends('layouts.app')
@section('title', 'My account')

@section('content')
<h1>Hello, {{ $patient->first_name }} 👋</h1>

<div class="mt-8 space-y-8">

    {{-- Next visit --}}
    <section>
        <h2>Your visit</h2>
        <div class="mt-3 space-y-3">
            @forelse ($upcoming as $a)
                <div class="card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-lg font-semibold text-slate-900">{{ $a->problem_title }}</p>
                            @if ($a->appointment_date && $a->start_time)
                                <p class="mt-1 text-xl font-bold text-blue-700">📅 {{ $a->appointment_date->format('l, F j') }} · {{ \Illuminate\Support\Carbon::parse($a->start_time)->format('g:i A') }}</p>
                                <p class="text-slate-600">with {{ $a->dentist?->full_name ?? 'our dentist' }}</p>
                            @else
                                <p class="mt-1 text-slate-600">We’re arranging a time for you. We’ll contact you soon.</p>
                            @endif
                        </div>
                        <x-badge :value="$a->status" />
                    </div>
                    @if ($a->billing && $a->billing->payment_status !== 'paid')
                        <a href="{{ route('portal.bills.show', $a->billing) }}" class="btn btn-ghost btn-sm mt-4">View bill</a>
                    @endif
                </div>
            @empty
                <div class="card text-slate-500">You have no upcoming visit.</div>
            @endforelse
        </div>
    </section>

    {{-- Today's medicine --}}
    <section>
        <div class="flex items-center justify-between">
            <h2>Medicine today</h2>
            <a href="{{ route('portal.medicines') }}" class="font-semibold text-blue-600 hover:underline">Open calendar →</a>
        </div>
        <div class="mt-3 space-y-3">
            @forelse ($todayDoses as $dose)
                @php $due = ! $dose['taken'] && $dose['time'] <= now()->format('H:i'); @endphp
                <div class="card flex flex-wrap items-center justify-between gap-3 p-5 {{ $due ? 'border-orange-300 bg-orange-50' : '' }}">
                    <div>
                        <p class="text-lg font-semibold text-slate-900">💊 {{ $dose['prescription']->medication_name }}</p>
                        <p class="text-slate-600">{{ $dose['prescription']->dosage }} at {{ \Illuminate\Support\Carbon::createFromFormat('H:i', $dose['time'])->format('g:i A') }}@if($dose['prescription']->instructions) · {{ $dose['prescription']->instructions }}@endif</p>
                    </div>
                    <form method="POST" action="{{ route('portal.doses.toggle') }}">
                        @csrf
                        <input type="hidden" name="prescription_id" value="{{ $dose['prescription']->id }}">
                        <input type="hidden" name="date" value="{{ today()->toDateString() }}">
                        <input type="hidden" name="time" value="{{ $dose['time'] }}">
                        <button class="btn btn-sm {{ $dose['taken'] ? 'btn-ghost' : 'btn-green' }}">{{ $dose['taken'] ? '✓ Taken (undo)' : 'I took it' }}</button>
                    </form>
                </div>
            @empty
                <div class="card text-slate-500">No medicine to take today.</div>
            @endforelse
        </div>
        <div class="mt-4">@include('portal._reminders', ['doses' => $todayDoses])</div>
    </section>

    {{-- Bills --}}
    @if ($bills->isNotEmpty())
    <section>
        <h2>Bills</h2>
        <div class="mt-3 space-y-3">
            @foreach ($bills as $b)
                <a href="{{ route('portal.bills.show', $b) }}" class="card flex items-center justify-between gap-3 p-5 transition hover:border-blue-300">
                    <div><p class="text-lg font-semibold">Left to pay: <x-money :value="$b->balance()" /></p><p class="hint">Tap to see details{{ $b->allow_online ? ' and pay online' : '' }}</p></div>
                    <x-badge :value="$b->payment_status" />
                </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- History --}}
    @if ($past->isNotEmpty())
    <section>
        <h2>Past visits</h2>
        <div class="card mt-3 divide-y divide-slate-100 p-0">
            @foreach ($past as $a)
                <div class="flex items-center justify-between gap-3 px-6 py-4"><span class="font-medium">{{ $a->problem_title }}</span><span class="text-sm text-slate-500">{{ $a->completed_at?->format('M j, Y') }}</span></div>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
