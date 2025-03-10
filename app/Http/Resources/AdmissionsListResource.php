<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionsListResource extends JsonResource
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
            'admission_number' => $this->admission_number,
            'period' => $this->period,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'biller' => $this->biller,
            'observations' => $this->observations,
            'shipment_id' => $this->shipment_id,
            'audit_requested_at' => $this->audit_requested_at,
            'audit_id' => $this->audit_id,
            'medical_record_request_id' => $this->medical_record_request_id,
            'audit' => $this->whenLoaded('audit', fn() => new AuditResource($this->audit)),
            'medical_record_request' => $this->whenLoaded('medicalRecordRequest', fn() => new MedicalRecordRequestResource($this->medicalRecordRequest)),
            'shipment' => $this->whenLoaded('shipment', fn() => new ShipmentResource($this->shipment)),
        ];
    }
}
