<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditResource extends JsonResource
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
            'auditor' => $this->auditor,
            'status' => $this->status,
            'admissions_number' => $this->admissions_number,
            'invoice_number' => $this->invoice_number,
            'admissions_list_id' => $this->admissions_list_id,
            'admissions_list' => $this->whenLoaded('admissionsList', fn() => new AdmissionsListResource($this->admissionsList)),

        ];
    }
}
