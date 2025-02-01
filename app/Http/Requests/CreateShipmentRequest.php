<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Classes\ApiResponseClass;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Log;

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
            'newShipments' => 'nullable|array',
            'newShipments.*.verified_shipment_date' => 'nullable|date',
            'newShipments.*.invoice_number' => 'nullable|string|max:255',
            'newShipments.*.remarks' => 'nullable|string|max:255',
            'newShipments.*.trama_date' => 'nullable|date',
            'newShipments.*.courier_date' => 'nullable|date',
            'newShipments.*.email_verified_date' => 'nullable|date',
            'newShipments.*.url_sustenance' => 'nullable|string|max:255',
            'updatedShipments' => 'nullable|array',
            'updatedShipments.*.verified_shipment_date' => 'nullable|date',
            'updatedShipments.*.invoice_number' => 'nullable|string|max:255',
            'updatedShipments.*.remarks' => 'nullable|string|max:255',
            'updatedShipments.*.trama_date' => 'nullable|date',
            'updatedShipments.*.courier_date' => 'nullable|date',
            'updatedShipments.*.email_verified_date' => 'nullable|date',
            'updatedShipments.*.url_sustenance' => 'nullable|string|max:255'
        ];
    }

    public function failedValidation(Validator $validator)
    {
        ApiResponseClass::validationError($validator, $this->response);
    }
}
