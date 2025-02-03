<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
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
            'verified_shipment_date' => $this->verified_shipment_date,
            'admission_number' => $this->admission_number,
            'reception_date' => $this->reception_date,
            'remarks' => $this->remarks,
            'trama_date' => $this->trama_date,
            'courier_date' => $this->courier_date,
            'email_verified_date' => $this->email_verified_date,
            'url_sustenance' => $this->url_sustenance,
            'invoice_number' => $this->invoice_number,
        ];
    }
}
