<?php

namespace App\Http\Requests;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class StoreInsurerRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'shipping_period' => 'string|max:255',
            'payment_period' => 'string|max:255',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        ApiResponseClass::validationError($validator, []);
    }
}
