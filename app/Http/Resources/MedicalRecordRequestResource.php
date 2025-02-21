<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalRecordRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medical_record_number' => $this->medical_record_number,
            'requester_nick' => $this->requester_nick,
            'requested_nick' => $this->requested_nick,
            'admission_number' => $this->admission_number,
            'request_date' => $this->request_date,
            'response_date' => $this->response_date,
            'confirmed_receipt_date' => $this->confirmed_receipt_date,
            'confirmed_return_date' => $this->confirmed_return_date,
            'remarks' => $this->remarks,
            'status' => $this->status,
        ];
    }
}