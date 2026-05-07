<?php

namespace App\Repositories;

use App\Interfaces\DevolutionRepositoryInterface;
use App\Models\Devolution;

class DevolutionRepository extends BaseRepository implements DevolutionRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(Devolution $model)
    {
        parent::__construct($model);
    }

    public function updateByInvoiceId(array $data, $invoiceId)
    {
        return $this->model->where('invoice_id', $invoiceId)->update($data);
    }

    // Actualiza is_paid para todas las devoluciones de una misma atención
    public function updateIsPaidByAdmissionNumber(string $admissionNumber, bool $isPaid): void
    {
        $this->model
            ->where('admission_number', $admissionNumber)
            ->update(['is_paid' => $isPaid]);
    }
}