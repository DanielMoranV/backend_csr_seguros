<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Classes\ApiResponseClass;

class UpdateInvoicesRequest extends FormRequest
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
            '*.issue_date' => 'nullable|date',
            '*.status' => 'nullable|string|max:255',
            '*.biller' => 'nullable|string|max:255',
            '*.payment_date' => 'nullable|date',
            '*.amount' => 'nullable|numeric|min:0',
            '*.admission_id' => 'nullable|exists:admissions,id',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        ApiResponseClass::validationError($validator, $this->all());
    }
}