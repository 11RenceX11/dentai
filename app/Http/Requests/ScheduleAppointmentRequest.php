<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dentist_id' => ['nullable', 'exists:dentists,id'],
            'appointment_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i', 'required_with:appointment_date'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time', 'required_with:start_time'],
            'urgency' => ['required', Rule::in(Appointment::URGENCIES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return ['start_time' => 'start time', 'end_time' => 'end time', 'appointment_date' => 'date'];
    }
}
