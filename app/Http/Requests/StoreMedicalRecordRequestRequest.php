<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalRecordRequestRequest extends FormRequest
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
            'requester_nick' => 'required|string|max:255',
            'requested_nick' => 'nullable|string|max:255',
            'admission_number' => 'nullable|string|max:255',
            'medical_record_number' => 'required|max:255',
            'request_date' => 'required|date',
            'response_date' => 'nullable|date',
            'confirmed_receipt_date' => 'nullable|date',
            'confirmed_return_date' => 'nullable|date',
            'status' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:255',
        ];
    }
}