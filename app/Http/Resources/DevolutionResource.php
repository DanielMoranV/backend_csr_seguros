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
            'id' => $this->id,
            'date' => $this->date,
            'invoice_id' => $this->invoice_id,
            'type' => $this->type,
            'reason' => $this->reason,
            'period' => $this->period,
            'biller' => $this->biller,
            'status' => $this->status,
            'admission_id' => $this->admission_id,
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'admission' => new AdmissionResource($this->whenLoaded('admission')),
        ];
    }
}
