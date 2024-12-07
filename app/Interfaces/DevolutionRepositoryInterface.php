<?php

namespace App\Interfaces;

interface DevolutionRepositoryInterface extends BaseRepositoryInterface
{
    public function updateByInvoiceId(array $data, $invoiceId);
}