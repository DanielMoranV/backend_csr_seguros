<?php

namespace App\Repositories;

use App\Interfaces\InvoiceRepositoryInterface;
use App\Models\Invoice;

class InvoiceRepository extends BaseRepository implements InvoiceRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(Invoice $model)
    {
        parent::__construct($model);
    }
}