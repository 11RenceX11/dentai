@extends('layouts.app')
@section('title', 'Patients')

@section('content')
<h1>Patients</h1>
<form method="GET" class="mt-6 flex gap-3"><input name="q" value="{{ request('q') }}" placeholder="Search by name or phone…" class="input"><button class="btn btn-ghost">Search</button></form>

<div class="mt-6 space-y-3">
    @forelse ($patients as $p)
        <a href="{{ route('patients.show', $p) }}" class="card flex items-center justify-between gap-3 p-5 transition hover:border-blue-300">
            <div>
                <p class="text-lg font-semibold text-slate-900">{{ $p->full_name }}</p>
                <p class="text-slate-600">{{ $p->phone_number }} · {{ ucfirst(str_replace('_', ' ', $p->gender)) }}</p>
            </div>
            <span class="text-sm text-slate-500">{{ $p->appointments_count }} {{ \Illuminate\Support\Str::plural('visit', $p->appointments_count) }}</span>
        </a>
    @empty
        <div class="card text-center text-slate-500">No patients found.</div>
    @endforelse
</div>
<div class="mt-6 empty:hidden">{{ $patients->links() }}</div>
@endsection
