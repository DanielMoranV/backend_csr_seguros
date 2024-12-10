<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'issue_date' => $this->issue_date,
            'biller' => $this->biller,
            'status' => $this->status,
            'payment_date' => $this->payment_date,
            'admission_id' => $this->admission_id,
            'amount' => $this->amount,
            'admission' => $this->whenLoaded('admission', fn() => new AdmissionResource($this->admission)),
        ];
    }
}