<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PeriodAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => [
                'required',
                'string',
                'between:4,6',
                // Regex para validar YYYY o YYYYMM
                // YYYY: 2020-2039
                // YYYYMM: 2020-2039 con meses 01-12
                'regex:/^((202[0-9]|203[0-9])|(202[0-9]|203[0-9])(0[1-9]|1[0-2]))$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'period.required' => 'El periodo es requerido.',
            'period.between' => 'El periodo debe tener 4 o 6 dígitos (formato YYYY o YYYYMM).',
            'period.regex' => 'El formato del periodo es inválido. Use YYYY o YYYYMM.',
        ];
    }
}
