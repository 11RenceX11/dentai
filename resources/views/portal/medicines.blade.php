@extends('layouts.app')
@section('title', 'My medicine')

@section('content')
<h1>My medicine</h1>
<p class="mt-1 text-slate-600">Tap a day to see what to take. Tick each dose when you’ve taken it.</p>

<div class="card mt-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('portal.medicines', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-ghost btn-sm">←</a>
        <h2>{{ $month->format('F Y') }}</h2>
        <a href="{{ route('portal.medicines', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-ghost btn-sm">→</a>
    </div>

    <div class="mt-4 grid grid-cols-7 gap-1 text-center text-sm font-semibold text-slate-500">
        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d)<div class="py-1">{{ $d }}</div>@endforeach
    </div>
    <div class="grid grid-cols-7 gap-1">
        @foreach ($days as $day)
            @php
                $isSel = $day['date']->isSameDay($selected);
                $all = $day['total'] > 0 && $day['taken'] === $day['total'];
            @endphp
            <a href="{{ route('portal.medicines', ['month' => $month->format('Y-m'), 'date' => $day['date']->toDateString()]) }}"
               class="flex min-h-16 flex-col items-center justify-start rounded-xl p-1.5 text-base transition
                      {{ $isSel ? 'bg-blue-600 text-white' : ($day['inMonth'] ? 'bg-slate-50 hover:bg-slate-100' : 'text-slate-300 hover:bg-slate-50') }}
                      {{ $day['date']->isToday() && ! $isSel ? 'ring-2 ring-blue-400' : '' }}">
                <span class="font-semibold">{{ $day['date']->day }}</span>
                @if ($day['total'])
                    <span class="mt-1 rounded-full px-1.5 text-xs font-bold {{ $isSel ? 'bg-white/25' : ($all ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800') }}">{{ $all ? '✓' : $day['taken'] . '/' . $day['total'] }}</span>
                @endif
            </a>
        @endforeach
    </div>
</div>

<h2 class="mt-8">{{ $selected->isToday() ? 'Today' : $selected->format('l, F j') }}</h2>
<div class="mt-3 space-y-3">
    @forelse ($selectedDoses as $dose)
        @php $due = $selected->isToday() && ! $dose['taken'] && $dose['time'] <= now()->format('H:i'); @endphp
        <div class="card flex flex-wrap items-center justify-between gap-3 p-5 {{ $due ? 'border-orange-300 bg-orange-50' : '' }}">
            <div>
                <p class="text-lg font-semibold text-slate-900">💊 {{ $dose['prescription']->medication_name }}</p>
                <p class="text-slate-600">{{ $dose['prescription']->dosage }} at <strong>{{ \Illuminate\Support\Carbon::createFromFormat('H:i', $dose['time'])->format('g:i A') }}</strong>@if($dose['prescription']->instructions) · {{ $dose['prescription']->instructions }}@endif</p>
                @if ($due)<p class="mt-1 font-semibold text-orange-700">Time to take this now</p>@endif
            </div>
            <form method="POST" action="{{ route('portal.doses.toggle') }}">
                @csrf
                <input type="hidden" name="prescription_id" value="{{ $dose['prescription']->id }}">
                <input type="hidden" name="date" value="{{ $selected->toDateString() }}">
                <input type="hidden" name="time" value="{{ $dose['time'] }}">
                <button class="btn btn-sm {{ $dose['taken'] ? 'btn-ghost' : 'btn-green' }}">{{ $dose['taken'] ? '✓ Taken (undo)' : 'I took it' }}</button>
            </form>
        </div>
    @empty
        <div class="card text-slate-500">No medicine on this day.</div>
    @endforelse
</div>

@if ($selected->isToday())
    <div class="mt-4">@include('portal._reminders', ['doses' => $selectedDoses])</div>
@endif

@if ($activePrescriptions->isNotEmpty())
    <h2 class="mt-10">Your medicine plan</h2>
    <div class="mt-3 space-y-3">
        @foreach ($activePrescriptions as $rx)
            <div class="card p-5">
                <p class="text-lg font-semibold text-slate-900">💊 {{ $rx->medication_name }}</p>
                <p class="text-slate-600">{{ $rx->summary() }}</p>
                <p class="hint">{{ $rx->start_date->format('M j') }} – {{ $rx->endDate()->format('M j') }}@if($rx->instructions) · {{ $rx->instructions }}@endif</p>
                <a href="{{ route('portal.reminders', $rx) }}" class="btn btn-ghost btn-sm mt-3">📅 Add alarms to my phone calendar</a>
            </div>
        @endforeach
    </div>
    <p class="hint mt-3">The calendar file opens in Google Calendar, Apple Calendar or Outlook and sets a repeating alarm for every dose.</p>
@endif
@endsection
