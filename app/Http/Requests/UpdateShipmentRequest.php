<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentRequest extends FormRequest
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
            'verified_shipment_date' => 'nullable|date',
            'reception_date' => 'nullable|date',
            'invoice_number' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:255',
            'trama_date' => 'nullable|date',
            'courier_date' => 'nullable|date',
            'email_verified_date' => 'nullable|date',
            'url_sustenance' => 'nullable|string|max:255'
        ];
    }
}
