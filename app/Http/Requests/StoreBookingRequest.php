<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone_number' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,}$/'],
            'gender' => ['required', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            'email' => ['nullable', 'email', 'max:120'],
            'problem_type_id' => ['required', 'exists:problem_types,id'],
            'problem_title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'pain_level' => ['required', 'integer', 'between:0,10'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Please enter a valid phone number.',
            'description.min' => 'Please tell us a little more about how it feels (at least 10 characters).',
        ];
    }
}
