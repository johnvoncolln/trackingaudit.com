<?php

namespace App\Http\Requests;

use App\Enums\Carrier;
use App\Rules\UpsTrackingNumber;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

class TrackingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tracking_number' => ['required', 'string', 'max:50'],
            'carrier' => ['required', 'string', new Enum(Carrier::class)],
            'reference_id' => ['nullable', 'string', 'max:50'],
            'reference_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function after()
    {
        return [
            function (Validator $validator) {
                if ($this->carrier === Carrier::UPS->value) {
                    $validatorTwo = Validator::make(
                        ["tracking_number" => $this->tracking_number], // Data to validate
                        ["tracking_number" => [new UpsTrackingNumber()]] // Validation rules
                      );
                    if ($validatorTwo->fails()) {
                        $validator->errors()->add(
                            'tracking_number',
                            'This is not a valid UPS tracking number.'
                        );
                    }
                }
            }
        ];
    }
}
