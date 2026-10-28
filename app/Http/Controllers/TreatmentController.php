<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class TreatmentController extends Controller
{
    private function rules(?Treatment $treatment = null): array
    {
        return [
            'treatment_name' => ['required', 'string', 'max:120', 'unique:treatments,treatment_name' . ($treatment ? ",{$treatment->id}" : '')],
            'description' => ['nullable', 'string', 'max:255'],
            'base_cost' => ['required', 'numeric', 'min:0'],
            'est_duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
        ];
    }

    public function index()
    {
        return view('treatments.index', ['treatments' => Treatment::withCount('dentists')->orderBy('treatment_name')->get()]);
    }

    public function store(Request $request)
    {
        Treatment::create($request->validate($this->rules()));

        return back()->with('status', 'Treatment added.');
    }

    public function update(Request $request, Treatment $treatment)
    {
        $treatment->update($request->validate($this->rules($treatment)));

        return back()->with('status', 'Treatment updated.');
    }

    public function destroy(Treatment $treatment)
    {
        try {
            $treatment->delete();
        } catch (QueryException) {
            return back()->withErrors(['treatment' => 'This treatment is used in appointments and cannot be deleted.']);
        }

        return back()->with('status', 'Treatment deleted.');
    }
}
