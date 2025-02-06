<?php

namespace App\Repositories;

use App\Interfaces\ShipmentRepositoryInterface;
use App\Models\Shipment;

class ShipmentControllerRepository extends BaseRepository implements ShipmentRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(Shipment $model)
    {
        parent::__construct($model);
    }

    // actualizar por invoice_number
    public function updateByInvoiceNumber(string $invoiceNumber, array $data): bool
    {
        $shipment = $this->model->where('invoice_number', $invoiceNumber)->first();
        if ($shipment) {
            return $shipment->update($data);
        }
        return false;
    }

    // mostrar por admission_number
    public function getByAdmissionNumber(string $admissionNumber)
    {
        return $this->model->where('admission_number', 'LIKE', "%{$admissionNumber}%")->get();
    }
}
