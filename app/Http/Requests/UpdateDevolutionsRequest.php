<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDevolutionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            '*.date' => 'nullable|date',
            '*.invoice_id' => 'nullable|exists:invoices,id',
            '*.type' => 'nullable|string',
            '*.reason' => 'nullable|string',
            '*.period' => 'nullable|string',
            '*.biller' => 'nullable|string',
            '*.status' => 'nullable|string',
            '*.admission_id' => 'required|exists:admissions,id',
        ];
    }
}
