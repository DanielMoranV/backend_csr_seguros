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
        // Precalcular datos para evitar múltiples consultas
        $latestInvoice = $this->relationLoaded('invoices') && $this->invoices->isNotEmpty()
            ? $this->invoices->last()
            : null;

        $latestSettlement = $this->relationLoaded('settlements') && $this->settlements->isNotEmpty()
            ? $this->settlements->last()
            : null;

        return [
            'id' => $this->id,
            'number' => $this->number,
            'attendance_date' => $this->attendance_date,
            'attendance_hour' => $this->attendance_hour,
            'type' => $this->type,
            'doctor' => $this->doctor,
            'amount' => $this->amount,
            'insurer_id' => $this->insurer_id,
            'company' => $this->company,
            'status' => $this->status,
            'patient' => $this->patient,
            'medical_record_id' => $this->medical_record_id,

            // Cargar datos de facturas y liquidaciones si existen
            'last_invoice_number' => $latestInvoice?->number,
            'last_invoice_biller' => $latestInvoice?->biller,
            'last_invoice_status' => $latestInvoice?->status,
            'last_settlement_period' => $latestSettlement?->period,
            'last_settlement_biller' => $latestSettlement?->biller,

            // Relación Insurer
            'insurer' => $this->whenLoaded('insurer', fn() => new InsurerResource($this->insurer)),

            // Relación Medical Record
            'medical_record' => $this->whenLoaded('medicalRecord', fn() => new MedicalRecordResource($this->medicalRecord)),

            // Relación Invoices
            'invoices' => $this->whenLoaded('invoices', fn() => InvoiceResource::collection($this->invoices)),

            // Relación Devolutions
            'devolutions' => $this->whenLoaded('devolutions', fn() => $this->devolutions->isEmpty() ? null : DevolutionResource::collection($this->devolutions)),

            // Relación Settlements
            // 'settlements' => $this->whenLoaded('settlements', fn() => $this->settlements->isEmpty() ? null : SettlementResource::collection($this->settlements)),

            // Indicador de devoluciones
            'is_devolution' => $this->whenLoaded('devolutions', fn() => !$this->devolutions->isEmpty()),
        ];
    }
}