<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuditRequest extends FormRequest
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
            'auditor' => 'string',
            'description' => 'nullable|string',
            'admission_number' => 'required|string',
            'invoice_number' => 'nullable|string',
            'type' => 'required|in:Regular,Devolucion',
            'status' => 'required|in:Aprobado,Con Observaciones,Rechazado,Pendiente',
        ];
    }
}
