@extends('layouts.app')
@section('title', 'Doctors')

@section('content')
<h1>Doctors</h1>
<p class="mt-1 text-slate-600">If a doctor can’t work on a day, add it here. They won’t be offered when you schedule a visit on those days.</p>

<div class="mt-8 space-y-6">
    @foreach ($dentists as $d)
        <div class="card">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2>{{ $d->full_name }}</h2>
                <span class="text-slate-500">{{ $d->specialization }}</span>
            </div>

            <ul class="mt-4 space-y-2">
                @forelse ($d->timeOffs as $off)
                    <li class="flex items-center justify-between rounded-xl bg-amber-50 px-4 py-3">
                        <span class="text-amber-900">
                            🚫 {{ $off->start_date->format('D, M j') }}@if (! $off->start_date->isSameDay($off->end_date)) – {{ $off->end_date->format('D, M j') }}@endif
                            @if ($off->reason)<span class="text-amber-700"> · {{ $off->reason }}</span>@endif
                        </span>
                        <form method="POST" action="{{ route('dentists.time-off.destroy', [$d, $off]) }}">@csrf @method('DELETE')<button class="text-sm font-semibold text-red-600 hover:underline">Remove</button></form>
                    </li>
                @empty
                    <li class="text-slate-500">No days off coming up.</li>
                @endforelse
            </ul>

            <form method="POST" action="{{ route('dentists.time-off.store', $d) }}" class="mt-5 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-4">
                @csrf
                <div><label class="label">Not working on</label><input type="date" name="start_date" min="{{ today()->toDateString() }}" class="input" required></div>
                <div><label class="label">Until <span class="font-normal text-slate-400">(optional)</span></label><input type="date" name="end_date" min="{{ today()->toDateString() }}" class="input"></div>
                <div><label class="label">Reason <span class="font-normal text-slate-400">(optional)</span></label><input name="reason" class="input" placeholder="e.g. Seminar"></div>
                <div class="flex items-end"><button class="btn btn-ghost w-full">Add day off</button></div>
            </form>
        </div>
    @endforeach
</div>
@endsection
