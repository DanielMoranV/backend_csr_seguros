<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdmissionsRequest extends FormRequest
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
            '*.attendance_date' => 'date',
            '*.type' => 'string|max:255',
            '*.doctor' => 'string|max:255',
            '*.insurer_id' => 'exists:insurers,id',
            '*.company' => 'string|max:255',
            '*.amount' => 'numeric|min:0',
            '*.patient' => 'string|max:255',
            '*.medical_record_id' => 'exists:medical_records,id',
        ];
    }
}