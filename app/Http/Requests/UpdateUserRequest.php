<?php

namespace App\Http\Requests;

use App\Classes\ApiResponseClass;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
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
            'name' => 'nullable',
            'dni' => 'nullable|min:8|max:8',
            'email' => 'nullable|email',
            'password' => 'nullable|min:8',
            'url_photo_profile' => 'nullable|string',
            'position' => 'nullable',
            'is_active' => 'nullable|boolean'
        ];
    }


    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->is_active ? 1 : 0,
            ]);
        }
    }

    public function failedValidation(Validator $validator)
    {
        ApiResponseClass::validationError($validator);
    }
}
