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
            'created_at' => $this->created_at,
            'auditor' => $this->auditor,
            'status' => $this->status,
            'url' => $this->url,
            'description' => $this->description,
            'admission_number' => $this->admission_number,
            'invoice_number' => $this->invoice_number,
            'type' => $this->type,
            'admissions_list' => $this->whenLoaded('admissionsList', fn() => new AdmissionsListResource($this->admissionsList)),

        ];
    }
}
