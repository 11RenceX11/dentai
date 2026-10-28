@extends('layouts.app')
@section('title', 'Prices')

@section('content')
<h1>Treatments &amp; prices</h1>
<p class="mt-1 text-slate-600">Used for suggestions, visits and bills.</p>

<details class="card mt-6" @if($errors->any()) open @endif>
    <summary class="cursor-pointer text-lg font-semibold text-blue-700">+ Add a treatment</summary>
    <form method="POST" action="{{ route('treatments.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
        @csrf
        <div class="sm:col-span-2"><label class="label">Name</label><input name="treatment_name" class="input" required></div>
        <div class="sm:col-span-2"><label class="label">Description</label><input name="description" class="input"></div>
        <div><label class="label">Price (₱)</label><input name="base_cost" type="number" step="0.01" min="0" class="input" required></div>
        <div><label class="label">Minutes</label><input name="est_duration_minutes" type="number" min="5" class="input" required></div>
        <div class="sm:col-span-2"><button class="btn btn-primary">Add</button></div>
    </form>
</details>

<div class="mt-6 space-y-3">
    @foreach ($treatments as $t)
        <details class="card p-5">
            <summary class="flex cursor-pointer items-center justify-between gap-3">
                <span class="text-lg font-semibold text-slate-900">{{ $t->treatment_name }}</span>
                <span class="text-slate-600"><x-money :value="$t->base_cost" /> · {{ $t->est_duration_minutes }} min</span>
            </summary>
            <form method="POST" action="{{ route('treatments.update', $t) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                @csrf @method('PUT')
                <div class="sm:col-span-2"><label class="label">Name</label><input name="treatment_name" value="{{ $t->treatment_name }}" class="input"></div>
                <div class="sm:col-span-2"><label class="label">Description</label><input name="description" value="{{ $t->description }}" class="input"></div>
                <div><label class="label">Price (₱)</label><input name="base_cost" type="number" step="0.01" value="{{ $t->base_cost }}" class="input"></div>
                <div><label class="label">Minutes</label><input name="est_duration_minutes" type="number" value="{{ $t->est_duration_minutes }}" class="input"></div>
                <div class="flex gap-3 sm:col-span-2"><button class="btn btn-primary btn-sm">Save</button></div>
            </form>
            <form method="POST" action="{{ route('treatments.destroy', $t) }}" class="mt-3" onsubmit="return confirm('Delete this treatment?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button></form>
        </details>
    @endforeach
</div>
@endsection
