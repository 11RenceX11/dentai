<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentSuggestion;
use App\Models\Billing;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\ProblemType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DentAiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'phone_number' => '0917 111 2222',
            'gender' => 'female',
            'problem_type_id' => ProblemType::where('name', 'Toothache')->value('id'),
            'problem_title' => 'Molar hurts',
            'description' => 'Throbbing all night and my cheek is swollen.',
            'pain_level' => 9,
        ], $overrides);
    }

    private function staff(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_painful_booking_is_flagged_and_gets_suggestions(): void
    {
        $this->post('/book', $this->payload())->assertRedirect(route('booking.success'));

        $appointment = Appointment::latest('id')->first();
        $this->assertSame('emergency', $appointment->urgency);
        $this->assertSame('requested', $appointment->status);
        $this->assertTrue($appointment->suggestions()->where('type', 'treatment')->exists());
    }

    public function test_gender_and_phone_are_required(): void
    {
        $this->post('/book', $this->payload(['gender' => '', 'phone_number' => '']))
            ->assertSessionHasErrors(['gender', 'phone_number']);
    }

    public function test_booking_creates_a_patient_account_that_can_log_in(): void
    {
        $this->post('/book', $this->payload());

        $patient = Patient::where('phone_number', '0917 111 2222')->firstOrFail();
        $user = $patient->user;

        $this->assertSame('patient', $user->role);
        $this->assertSame('09171112222', $user->username);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $patient->access_code);

        $this->post('/login', ['login' => '0917 111 2222', 'password' => $patient->access_code])
            ->assertRedirect(route('portal.home'));
        $this->assertAuthenticatedAs($user);

        // A patient account can see its own area but not the clinic dashboard.
        $this->actingAs($user)->get('/my')->assertOk();
        $this->actingAs($user)->get('/dashboard')->assertForbidden();
    }

    public function test_assistant_approving_a_suggestion_adds_it_to_the_appointment(): void
    {
        $suggestion = AppointmentSuggestion::where('type', 'treatment')->firstOrFail();

        $this->actingAs($this->staff('assistant@dentai.test'))
            ->post(route('suggestions.approve', $suggestion))->assertRedirect();

        $this->assertDatabaseHas('appointment_treatments', [
            'appointment_id' => $suggestion->appointment_id,
            'treatment_id' => $suggestion->treatment_id,
        ]);
    }

    public function test_a_doctor_who_is_off_cannot_be_booked_that_day(): void
    {
        $john = Dentist::where('last_name', 'Reyes')->firstOrFail();
        $friday = $john->timeOffs()->firstOrFail()->start_date->toDateString();
        $appointment = Appointment::where('status', 'requested')->firstOrFail();

        $this->actingAs($this->staff('assistant@dentai.test'))
            ->put(route('appointments.update', $appointment), [
                'dentist_id' => $john->id, 'appointment_date' => $friday,
                'start_time' => '10:00', 'end_time' => '10:30', 'urgency' => 'normal',
            ])->assertSessionHasErrors('dentist_id');

        $this->assertFalse($john->isAvailableOn($friday));
    }

    public function test_marking_done_moves_the_visit_to_records(): void
    {
        $appointment = Appointment::where('status', 'scheduled')->firstOrFail();
        $user = $this->staff('dentist@dentai.test');

        $this->actingAs($user)->post(route('appointments.done', $appointment))->assertRedirect(route('records.index'));

        $this->assertSame('completed', $appointment->fresh()->status);
        $this->actingAs($user)->get('/records')->assertOk()->assertSee($appointment->patient->first_name);
        $this->actingAs($user)->get('/appointments?tab=all')->assertDontSee($appointment->problem_title);
    }

    public function test_online_payment_is_blocked_unless_staff_turned_it_on(): void
    {
        config(['paymongo.secret_key' => 'sk_test_x']);
        $bill = Billing::firstOrFail();
        $bill->update(['allow_online' => false]);
        $patientUser = $bill->appointment->patient->user;

        $this->actingAs($patientUser)->post(route('portal.bills.pay', $bill))->assertForbidden();
        $this->actingAs($this->staff('assistant@dentai.test'))->post(route('billings.online', $bill));
        $this->assertTrue($bill->fresh()->allow_online);
    }

    public function test_signed_paymongo_webhook_marks_the_bill_paid_once(): void
    {
        config(['paymongo.webhook_secret' => 'whsec_test']);
        $bill = Billing::firstOrFail();

        $raw = json_encode(['data' => ['type' => 'checkout_session.payment.paid', 'data' => [
            'id' => 'cs_1',
            'attributes' => [
                'reference_number' => "BILL-{$bill->id}",
                'metadata' => ['billing_id' => (string) $bill->id],
                'payments' => [['id' => 'pay_1', 'attributes' => ['status' => 'paid', 'amount' => (int) ($bill->total_amount * 100)]]],
            ],
        ]]]);

        $t = time();
        $sig = hash_hmac('sha256', "{$t}." . $raw, 'whsec_test');
        $server = ['HTTP_PAYMONGO_SIGNATURE' => "t={$t},te={$sig},li=", 'CONTENT_TYPE' => 'application/json'];

        $this->call('POST', '/webhooks/paymongo', [], [], [], $server, $raw)->assertOk();
        $this->call('POST', '/webhooks/paymongo', [], [], [], $server, $raw)->assertOk(); // retry

        $this->assertSame('paid', $bill->fresh()->payment_status);
        $this->assertSame(1, $bill->payments()->count());

        $this->call('POST', '/webhooks/paymongo', [], [], [], ['HTTP_PAYMONGO_SIGNATURE' => 't=1,te=bad,li='], $raw)->assertStatus(400);
    }

    public function test_dentist_cannot_open_billing(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->actingAs($this->staff('dentist@dentai.test'))->get('/billings')->assertForbidden();
    }
}
