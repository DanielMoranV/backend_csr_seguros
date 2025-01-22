<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAdmissionsListsRequest extends FormRequest
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
            '*.requester_nick' => 'required|string|max:255',
            '*.admission_number' => 'nullable|string|max:255',
            '*.medical_record_number' => 'nullable|max:255',
            '*.request_date' => 'required|date',
            '*.remarks' => 'nullable|string|max:255',
            '*.admissionList' => 'required|array',
            '*.admissionList.admission_number' => 'required|max:255',
            '*.admissionList.biller' => 'required|string|max:255',
            '*.admissionList.end_date' => 'required|date',
            '*.admissionList.start_date' => 'required|date',
            '*.admissionList.period' => 'required',
        ];
    }
}