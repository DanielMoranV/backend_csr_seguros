<?php

namespace App\Repositories;

use App\Interfaces\AuditRepositoryInterface;
use App\Models\Audit;

class AuditRepository extends BaseRepository implements AuditRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(Audit $model)
    {
        parent::__construct($model);
    }

    /**
     * Get audits by admissions.
     */
    public function getAuditsByAdmissions($admissions = [])
    {

        return $this->model->whereIn('admission_number', $admissions)->get();
    }

    /**
     * Get audits by data range.
     */
    public function getAuditsByDateRange($from, $to)
    {
        return $this->model->whereBetween('created_at', [$from, $to])->get();
    }
}