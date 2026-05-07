<?php

namespace App\Interfaces;

interface DevolutionRepositoryInterface extends BaseRepositoryInterface
{
    public function updateByInvoiceId(array $data, $invoiceId);
    public function updateIsPaidByAdmissionNumber(string $admissionNumber, bool $isPaid): void;
}