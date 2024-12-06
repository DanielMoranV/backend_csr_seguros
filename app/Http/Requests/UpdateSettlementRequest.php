<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettlementRequest extends FormRequest
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
            'biller' => 'nullable|string|max:255',
            'received_file' => 'nullable|boolean',
            'reception_date' => 'nullable|date',
            'settled' => 'nullable|boolean',
            'settled_date' => 'nullable|date',
            'audited' => 'nullable|boolean',
            'audited_date' => 'nullable|date',
            'billed' => 'nullable|boolean',
            'invoice_id' => 'nullable|exists:invoices,id',
            'shipped' => 'nullable|boolean',
            'shipment_date' => 'nullable|date',
            'paid' => 'nullable|boolean',
            'payment_date' => 'nullable|date',
            'status' => 'nullable|string|max:255',
            'period' => 'nullable|string|max:255',
        ];
    }
}
