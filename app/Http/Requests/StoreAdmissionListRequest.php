<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdmissionListRequest extends FormRequest
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
            'admission_number' => 'required|string|max:10',
            'period' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'biller' => 'required|string',
            'shipment_id' => 'nullable',
            'audit_id' => 'nullable',
            'medical_record_request_id' => 'nullable',
            'observations' => 'nullable|string'
        ];
    }
}