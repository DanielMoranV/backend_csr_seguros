<?php

namespace App\Interfaces;

interface ShipmentRepositoryInterface extends BaseRepositoryInterface
{
    // actualizar por invoice_number
    public function updateByInvoiceNumber(string $invoiceNumber, array $data): bool;

    // mostrar por admission_number
    public function getByAdmissionNumber(string $admissionNumber);

    // mostrar por lista de admisiones
    public function getByAdmissionsList(array $admissionsList);
}
