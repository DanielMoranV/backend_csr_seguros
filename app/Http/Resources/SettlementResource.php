<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettlementResource extends JsonResource
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
            'admission_id' => $this->admission_id,
            'biller' => $this->biller,
            'received_file' => $this->received_file,
            'reception_date' => $this->reception_date,
            'settled' => $this->settled,
            'settled_date' => $this->settled_date,
            'audited' => $this->audited,
            'audited_date' => $this->audited_date,
            'billed' => $this->billed,
            'invoice_id' => $this->invoice_id,
            'shipped' => $this->shipped,
            'shipment_date' => $this->shipment_date,
            'paid' => $this->paid,
            'payment_date' => $this->payment_date,
            'status' => $this->status,
            'period' => $this->period,
            'invoice' => $this->whenLoaded('invoice', fn() => new InvoiceResource($this->invoice)),
        ];
    }
}