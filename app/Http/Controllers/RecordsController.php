<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class RecordsController extends Controller
{
    public function index(Request $request)
    {
        $records = Appointment::with(['patient', 'dentist', 'problemType'])
            ->where('status', 'completed')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->whereHas('patient', fn ($p) => $p->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)->orWhere('phone_number', 'like', $term));
            })
            ->latest('completed_at')
            ->paginate(15)
            ->withQueryString();

        return view('records.index', compact('records'));
    }
}
