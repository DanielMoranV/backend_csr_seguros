<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Classes\ApiResponseClass;
use Illuminate\Contracts\Validation\Validator;

class CreateShipmentRequest extends FormRequest
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
            'newShipments' => 'required|array',
            'newShipments.*.verified_shipment_date' => 'nullable|date',
            'newShipments.*.invoice_number' => 'required|string|max:255',
            'newShipments.*.remarks' => 'nullable|string|max:255',
            'newShipments.*.trama_date' => 'nullable|date',
            'newShipments.*.courier_date' => 'nullable|date',
            'newShipments.*.email_verified_date' => 'nullable|date',
            'newShipments.*.url_sustenance' => 'nullable|string|max:255',
            'updatesShipments' => 'required|array',
            'updatesShipments.*.verified_shipment_date' => 'nullable|date',
            'updatesShipments.*.invoice_number' => 'required|string|max:255',
            'updatesShipments.*.remarks' => 'nullable|string|max:255',
            'updatesShipments.*.trama_date' => 'nullable|date',
            'updatesShipments.*.courier_date' => 'nullable|date',
            'updatesShipments.*.email_verified_date' => 'nullable|date',
            'updatesShipments.*.url_sustenance' => 'nullable|string|max:255'
        ];
    }

    public function failedValidation(Validator $validator)
    {
        ApiResponseClass::validationError($validator, $this->response);
    }
}
