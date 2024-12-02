<?php

namespace App\Http\Requests;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class StoreAdmissionsRequest extends FormRequest
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
            '*.number' => 'required|string|max:10',
            '*.attendance_date' => 'required|date',
            '*.type' => 'max:255',
            '*.doctor' => 'max:255',
            '*.insurer_id' => 'exists:insurers,id',
            '*.company' => 'max:255',
            '*.amount' => 'numeric',
            '*.patient' => 'max:255',
            '*.medical_record_id' => 'exists:medical_records,id',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        ApiResponseClass::validationError($validator, $this->all());
    }
}