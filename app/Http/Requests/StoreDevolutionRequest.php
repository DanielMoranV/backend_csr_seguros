<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDevolutionRequest extends FormRequest
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
            'date' => 'required|date',
            'invoice_id' => 'nullable|exists:invoices,id',
            'type' => 'required|string',
            'reason' => 'required|string',
            'period' => 'required|string',
            'biller' => 'required|string',
            'status' => 'nullable|string',
            'admission_id' => 'required|exists:admissions,id',
        ];
    }
}
