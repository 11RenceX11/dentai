@extends('layouts.marketing')
@section('title', 'Request received · DentAI')

@section('content')
<section class="mx-auto max-w-xl px-6 py-20 text-center">
    <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-100 text-3xl text-emerald-600">✓</div>
    <h1 class="mt-6">Thank you, {{ $booking['name'] }}!</h1>
    <p class="mt-3 text-lg text-slate-600">We received your request. Our team will contact you to confirm your visit.</p>

    <div class="card mt-8">
        <p class="hint">Your reference number</p>
        <p class="mt-1 text-3xl font-bold tracking-wide">{{ $booking['reference'] }}</p>
    </div>

    <div class="card mt-4 text-left">
        <h2>Your account</h2>
        <p class="mt-2 text-slate-600">We’ve created an account for you. When your visit is arranged, our dentist or assistant will give you your <strong>login details</strong> in person. You can then see your medicine schedule, set reminders, and pay online.</p>
    </div>

    @if ($booking['priority'])
        <p class="mt-4 rounded-xl bg-orange-50 px-5 py-4 text-left text-orange-900">Because of what you described, we’ve marked your request as a priority. If things get worse, or you have swelling that affects breathing or swallowing, please go to an emergency room.</p>
    @endif

    <a href="{{ route('home') }}" class="btn btn-ghost mt-8">Back to home</a>
</section>
@endsection
