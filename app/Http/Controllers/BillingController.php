<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Billing;
use App\Services\PaymongoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $show = $request->query('show') === 'paid' ? 'paid' : 'unpaid';

        $billings = Billing::with(['appointment.patient', 'payments'])
            ->when($show === 'paid', fn ($q) => $q->where('payment_status', 'paid'), fn ($q) => $q->where('payment_status', '!=', 'paid'))
            ->latest()->paginate(12)->withQueryString();

        return view('billing.index', compact('billings', 'show'));
    }

    public function store(Request $request, Appointment $appointment)
    {
        if ($appointment->billing) {
            return redirect()->route('billings.show', $appointment->billing);
        }

        $total = $appointment->treatments()->where('status', '!=', 'cancelled')->sum('cost_at_time');

        if ($total <= 0) {
            return back()->withErrors(['billing' => 'Add at least one treatment first, so there is something to bill.']);
        }

        $billing = Billing::create([
            'appointment_id' => $appointment->id,
            'total_amount' => $total,
            'issued_date' => today(),
            'due_date' => today()->addDays(14),
            'created_by' => $request->user()->id,
            'allow_online' => $request->boolean('allow_online'),
        ]);

        return redirect()->route('billings.show', $billing)->with('status', 'Bill created.');
    }

    public function show(Billing $billing, PaymongoService $paymongo)
    {
        $billing->load(['appointment.patient', 'appointment.treatments.treatment', 'payments']);

        return view('billing.show', ['billing' => $billing, 'paymongoReady' => $paymongo->enabled()]);
    }

    /** The online-payment switch: ON shows "Pay online" to the patient, OFF keeps it disabled. */
    public function toggleOnline(Billing $billing)
    {
        $billing->update(['allow_online' => ! $billing->allow_online]);

        return back()->with('status', $billing->allow_online
            ? 'Online payment is ON. The patient can now pay from their account.'
            : 'Online payment is OFF. The patient will be asked to pay at the clinic.');
    }

    /** Money received at the clinic. */
    public function storePayment(Request $request, Billing $billing)
    {
        $data = $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:0.01', 'max:' . $billing->balance()],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'bank_transfer', 'other'])],
        ], ['amount_paid.max' => 'That is more than the remaining balance.']);

        $billing->payments()->create($data + ['payment_date' => today(), 'received_by' => $request->user()->id]);
        $billing->refreshStatus();

        return back()->with('status', 'Payment saved.');
    }
}
