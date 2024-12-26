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
            'admissions_number' => $this->admissions_number,
            'period' => $this->period,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'biller' => $this->biller,
            'shipment_id' => $this->shipment_id,
            'audit_id' => $this->audit_id,
            'medical_record_request_id' => $this->medical_record_request_id,

            'audits' => $this->whenLoaded('audits', fn() => $this->audits->isEmpty() ? null : AuditResource::collection($this->audits)),

            'medical_record_requests' => $this->whenLoaded('audits', fn() => $this->medical_record_requests->isEmpty() ? null : MedicalRecordRequestResource::collection($this->medical_record_requests)),
        ];
    }
}
