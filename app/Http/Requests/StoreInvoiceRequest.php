<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Classes\ApiResponseClass;

class StoreInvoiceRequest extends FormRequest
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
            'issue_date' => 'required|date',
            'biller' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'admission_id' => 'required|exists:admissions,id',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        ApiResponseClass::validationError($validator, $this->all());
    }
}
