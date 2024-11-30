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

    public function updateByNumber($number, $data)
    {
        return $this->model->where('number', $number)->update($data);
    }
}