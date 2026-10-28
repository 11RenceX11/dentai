<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentSuggestion;
use App\Models\ProblemType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTriageTest extends TestCase
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

    public function test_painful_booking_is_flagged_and_gets_suggestions(): void
    {
        $this->post('/book', $this->payload())->assertRedirect(route('booking.success'));

        $appointment = Appointment::latest('id')->first();
        $this->assertSame('emergency', $appointment->urgency);
        $this->assertSame('requested', $appointment->status);
        $this->assertTrue($appointment->suggestions()->where('type', 'treatment')->exists());
        $this->assertTrue($appointment->suggestions()->where('type', 'schedule')->exists());
    }

    public function test_routine_booking_is_low_priority(): void
    {
        $this->post('/book', $this->payload([
            'problem_type_id' => ProblemType::where('name', 'Cleaning / check-up')->value('id'),
            'description' => 'Just a regular cleaning please.',
            'pain_level' => 0,
        ]));

        $this->assertSame('low', Appointment::latest('id')->first()->urgency);
    }

    public function test_gender_and_phone_are_required(): void
    {
        $this->post('/book', $this->payload(['gender' => '', 'phone_number' => '']))
            ->assertSessionHasErrors(['gender', 'phone_number']);
    }

    public function test_assistant_approving_a_suggestion_adds_it_to_the_appointment(): void
    {
        $suggestion = AppointmentSuggestion::where('type', 'treatment')->firstOrFail();
        $assistant = User::where('email', 'assistant@dentai.test')->firstOrFail();

        $this->actingAs($assistant)->post(route('suggestions.approve', $suggestion))->assertRedirect();

        $this->assertDatabaseHas('appointment_treatments', [
            'appointment_id' => $suggestion->appointment_id,
            'treatment_id' => $suggestion->treatment_id,
        ]);
        $this->assertSame('approved', $suggestion->fresh()->status);
    }

    public function test_dashboard_requires_login_and_billing_is_blocked_for_dentists(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');

        $dentist = User::where('email', 'dentist@dentai.test')->firstOrFail();
        $this->actingAs($dentist)->get('/billings')->assertForbidden();
    }
}
