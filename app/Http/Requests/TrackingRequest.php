<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tracking_number' => ['required', 'string', 'max:50'],
            'reference_id' => ['nullable', 'string', 'max:50'],
            'reference_name' => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['nullable', 'string', 'max:200'],
            'recipient_email' => ['nullable', 'email', 'max:200'],
        ];
    }
}
