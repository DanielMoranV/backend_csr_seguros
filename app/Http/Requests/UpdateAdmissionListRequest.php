<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdmissionListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:admissions_lists,id',
            'admission_number' => 'nullable|string|max:10',
            'period' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'biller' => 'nullable|string',
            'shipment_id' => 'nullable',
            'audit_id' => 'nullable',
            'medical_record_request_id' => 'nullable',
            'observations' => 'nullable|string'
        ];
    }
}