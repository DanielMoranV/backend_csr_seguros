<?php

namespace App\Http\Requests;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class StoreAdmissionRequest extends FormRequest
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
            'number' => 'required|string|max:10',
            'attendance_date' => 'required|date',
            'attendance_hour' => 'required|date',
            'type' => 'required|string|max:255',
            'doctor' => 'string|max:255',
            'insurer_id' => 'exists:insurers,id',
            'company' => 'string|max:255',
            'amount' => 'required|numeric',
            'patient' => 'required|string|max:255',
            'medical_record_id' => 'exists:medical_records,id',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        ApiResponseClass::validationError($validator, $this->all());
    }
}
