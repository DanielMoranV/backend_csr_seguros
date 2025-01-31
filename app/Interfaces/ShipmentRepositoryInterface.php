<?php

namespace App\Interfaces;

interface ShipmentRepositoryInterface extends BaseRepositoryInterface
{
    // actualizar por invoice_number
    public function updateByInvoiceNumber(string $invoiceNumber, array $data): bool;
}
