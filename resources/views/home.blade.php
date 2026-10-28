@extends('layouts.marketing')

@section('content')
<section class="mx-auto max-w-3xl px-6 py-20 text-center">
    <h1 class="text-4xl font-bold leading-tight md:text-5xl">Dental care, made simple.</h1>
    <p class="mx-auto mt-5 max-w-xl text-lg text-slate-600">Tell us what’s bothering your teeth. We’ll find the right time and the right dentist for you.</p>
    <a href="{{ route('booking.create') }}" class="btn btn-primary mt-8 px-8 py-4 text-lg">Book a visit</a>
    <p class="mt-3 text-sm text-slate-500">It takes about two minutes. No account needed.</p>
</section>

<section class="bg-slate-50 py-16">
    <div class="mx-auto grid max-w-5xl gap-6 px-6 md:grid-cols-3">
        @foreach ([
            ['1', 'Tell us the problem', 'Pick what’s wrong, describe how it feels, and rate the pain.'],
            ['2', 'We arrange your visit', 'Our team picks a date and a dentist. Painful problems are looked at first.'],
            ['3', 'Get your care plan', 'After your visit, see your medicine schedule, set reminders, and pay online if you like.'],
        ] as [$n, $title, $text])
            <div class="card text-center">
                <span class="step mx-auto">{{ $n }}</span>
                <h2 class="mt-4">{{ $title }}</h2>
                <p class="mt-2 text-slate-600">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>
@endsection
