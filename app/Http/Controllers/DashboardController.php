<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $with = ['patient', 'dentist', 'problemType'];

        $today = Appointment::with($with)
            ->whereDate('appointment_date', today())
            ->open()
            ->when($user->hasRole('dentist'), fn ($q) => $q->where('dentist_id', $user->dentist_id))
            ->orderBy('start_time')
            ->get();

        $requests = Appointment::with($with)
            ->withCount(['suggestions as pending_suggestions_count' => fn ($q) => $q->where('status', 'pending')])
            ->where('status', 'requested')
            ->orderByRaw(Appointment::urgencyOrderSql())
            ->orderBy('created_at')
            ->limit(8)
            ->get();

        return view('dashboard', [
            'today' => $today,
            'requests' => $requests,
            'newCount' => Appointment::where('status', 'requested')->count(),
            'urgentCount' => Appointment::open()->whereIn('urgency', ['emergency', 'high'])->count(),
        ]);
    }
}
