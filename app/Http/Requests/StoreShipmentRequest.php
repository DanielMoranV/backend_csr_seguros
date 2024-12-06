<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
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
            'verified_shipment' => 'nullable|boolean',
            'shipment_date' => 'nullable|date',
            'reception_date' => 'nullable|date',
            'invoice_id' => 'required|exists:invoices,id',
            'remarks' => 'nullable|string|max:255',
            'trama_verified' => 'nullable|boolean',
            'trama_date' => 'nullable|date',
            'courier_verified' => 'nullable|boolean',
            'courier_date' => 'nullable|date',
            'email_verified' => 'nullable|boolean',
            'email_verified_date' => 'nullable|date',
        ];
    }
}
