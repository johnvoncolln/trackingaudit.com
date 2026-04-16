<?php

namespace App\Http\Requests;

use App\Enums\Carrier;
use App\Rules\FedexTrackingNumber;
use App\Rules\UpsTrackingNumber;
use App\Rules\UspsTrackingNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
            'carrier' => ['required', 'string', new Enum(Carrier::class)],
            'reference_id' => ['nullable', 'string', 'max:50'],
            'reference_name' => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['nullable', 'string', 'max:200'],
            'recipient_email' => ['nullable', 'email', 'max:200'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $carrier = Carrier::tryFrom($this->carrier);

            if ($carrier === null) {
                return;
            }

            [$rule, $label] = match ($carrier) {
                Carrier::UPS => [new UpsTrackingNumber, 'UPS'],
                Carrier::USPS => [new UspsTrackingNumber, 'USPS'],
                Carrier::FEDEX => [new FedexTrackingNumber, 'FedEx'],
            };

            $rule->validate('tracking_number', $this->tracking_number, function ($message) use ($validator, $label) {
                $validator->errors()->add('tracking_number', "This is not a valid {$label} tracking number.");
            });
        });
    }
}
