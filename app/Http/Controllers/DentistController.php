<?php

namespace App\Http\Controllers;

use App\Models\Dentist;
use App\Models\DentistTimeOff;
use Illuminate\Http\Request;

/** "Doctors" page — where staff record that a doctor is not working on certain days. */
class DentistController extends Controller
{
    public function index()
    {
        $dentists = Dentist::where('is_active', true)
            ->with(['timeOffs' => fn ($q) => $q->whereDate('end_date', '>=', today())->orderBy('start_date')])
            ->orderBy('last_name')->get();

        return view('dentists.index', compact('dentists'));
    }

    public function storeTimeOff(Request $request, Dentist $dentist)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:120'],
        ]);

        $dentist->timeOffs()->create([
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? $data['start_date'],
            'reason' => $data['reason'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', "{$dentist->full_name} will not appear as an option on those days.");
    }

    public function destroyTimeOff(Dentist $dentist, DentistTimeOff $timeOff)
    {
        abort_unless($timeOff->dentist_id === $dentist->id, 404);
        $timeOff->delete();

        return back()->with('status', 'Day off removed.');
    }
}
