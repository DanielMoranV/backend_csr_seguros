<?php

namespace App\Interfaces;

interface InvoiceRepositoryInterface extends BaseRepositoryInterface
{
    public function updateByNumber($number, $data);
}