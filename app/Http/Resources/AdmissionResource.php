<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionResource extends JsonResource
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
            'number' => $this->number,
            'attendance_date' => $this->attendance_date,
            'type' => $this->type,
            'doctor' => $this->doctor,
            'amount' => $this->amount,
            'insurer' => $this->insurer,
            'company' => $this->company,
            'status' => $this->status,
            'patient' => $this->patient,
            'medical_record' => $this->medical_record,
        ];
    }
}