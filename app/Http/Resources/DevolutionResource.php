<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevolutionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'sisclin_id'        => $this->sisclin_id,
            'date'              => $this->date,
            'invoice_id'        => $this->invoice_id,
            'type'              => $this->type,
            'reason'            => $this->reason,
            'period'            => $this->period,
            'biller'            => $this->biller,
            'status'            => $this->status,
            'is_paid'           => $this->is_paid,
            'is_uncollectible'  => $this->is_uncollectible,
            'admission_id'      => $this->admission_id,
            'admission_number'       => $this->admission_number,
            'medical_record_number'  => $this->medical_record_number,
            'patient_name'           => $this->patient_name,
            'insurer_name'           => $this->insurer_name,
            'attendance_date'        => $this->attendance_date,
            'doctor'                 => $this->doctor,
            'invoice_date'           => $this->invoice_date,
            'invoice_amount'         => $this->invoice_amount,
            'audit_id'               => $this->audit_id,
            'invoice'           => new InvoiceResource($this->whenLoaded('invoice')),
            'admission'         => new AdmissionResource($this->whenLoaded('admission')),
        ];
    }
}