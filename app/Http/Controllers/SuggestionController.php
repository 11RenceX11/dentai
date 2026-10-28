<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentSuggestion;
use App\Services\TriageService;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    public function approve(Request $request, AppointmentSuggestion $suggestion, TriageService $triage)
    {
        abort_unless($suggestion->status === 'pending', 409, 'Already decided.');
        $triage->approve($suggestion, $request->user());

        return back()->with('status', 'Suggestion approved and added to the appointment.');
    }

    public function dismiss(Request $request, AppointmentSuggestion $suggestion, TriageService $triage)
    {
        abort_unless($suggestion->status === 'pending', 409, 'Already decided.');
        $triage->dismiss($suggestion, $request->user());

        return back()->with('status', 'Suggestion dismissed.');
    }

    public function approveAll(Request $request, Appointment $appointment, TriageService $triage)
    {
        $appointment->suggestions()->where('status', 'pending')->get()
            ->each(fn ($s) => $triage->approve($s, $request->user()));

        return back()->with('status', 'All suggestions approved.');
    }
}
