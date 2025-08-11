<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeriveMedicalRecordRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'medical_record_number' => 'required|string|max:255',
            'requested_nick' => [
                'required',
                'string',
                'max:255',
                Rule::exists('users', 'nick'),
                'different:' . auth()->user()->nick ?? ''
            ],
            'request_date' => 'required|date|after_or_equal:today',
            'remarks' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'medical_record_number.required' => 'El número de historia clínica es requerido.',
            'requested_nick.required' => 'El usuario solicitado es requerido.',
            'requested_nick.exists' => 'El usuario solicitado no existe.',
            'requested_nick.different' => 'No puedes derivar una solicitud para ti mismo.',
            'request_date.required' => 'La fecha de solicitud es requerida.',
            'request_date.date' => 'La fecha de solicitud debe ser una fecha válida.',
            'request_date.after_or_equal' => 'La fecha de solicitud no puede ser anterior al día de hoy.',
            'remarks.max' => 'Las observaciones no pueden exceder 1000 caracteres.'
        ];
    }
}