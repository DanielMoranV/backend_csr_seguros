<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            '*.issue_date' => 'date',
            '*.status' => 'string|max:255',
            '*.payment_date' => 'date',
            '*.amount' => 'numeric|min:0',
            '*.admission_id' => 'exists:admissions,id',
        ];
    }
}