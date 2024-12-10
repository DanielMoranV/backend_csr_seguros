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
            'verified_shipment' => $this->verified_shipment,
            'shipment_date' => $this->shipment_date,
            'reception_date' => $this->reception_date,
            'remarks' => $this->remarks,
            'trama_verified' => $this->trama_verified,
            'trama_date' => $this->trama_date,
            'courier_verified' => $this->courier_verified,
            'courier_date' => $this->courier_date,
            'email_verified' => $this->email_verified,
            'email_verified_date' => $this->email_verified_date,
            'invoice' => $this->whenLoaded('invoice', fn() => new InvoiceResource($this->invoice)),

        ];
    }
}