<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class DateRangeAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajustar según permisos
    }

    public function rules(): array
    {
        return [
            'start_date' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:end_date'
            ],
            'end_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                'before_or_equal:today'
            ],
            'include_admissions' => 'sometimes|boolean',
            'aggregations_only' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required' => 'La fecha de inicio es requerida',
            'start_date.date_format' => 'La fecha de inicio debe tener formato YYYY-MM-DD',
            'start_date.before_or_equal' => 'La fecha de inicio debe ser anterior o igual a la fecha fin',
            'end_date.required' => 'La fecha fin es requerida',
            'end_date.after_or_equal' => 'La fecha fin debe ser posterior o igual a la fecha inicio',
            'end_date.before_or_equal' => 'La fecha fin no puede ser posterior a hoy',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $start = $this->start_date;
            $end = $this->end_date;

            if ($start && $end) {
                $diff = \Carbon\Carbon::parse($start)->diffInDays(\Carbon\Carbon::parse($end));

                // Validar rango máximo de 1 año
                if ($diff > 365) {
                    $validator->errors()->add(
                        'date_range',
                        'El rango de fechas no puede ser mayor a 1 año'
                    );
                }
            }
        });
    }
}
