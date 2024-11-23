<?php

namespace App\Http\Requests;

use App\Classes\ApiResponseClass;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'name' => 'required',
            'dni' => 'required|unique:users',
            'email' => 'required|email',
            'cargo' => 'required',
            'phone' => 'required',
            'photo' => 'required',
            'password' => 'required|confirmed|min:8',
        ];
    }
    public function failedValidation(Validator $validator)
    {
        ApiResponseClass::validationError($validator);
    }
}