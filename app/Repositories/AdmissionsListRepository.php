<?php

namespace App\repositories;

use App\Interfaces\AdmissionsListRepositoryInterface;
use App\Models\AdmissionsList;

class AdmissionsListRepository extends BaseRepository implements AdmissionsListRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(AdmissionsList $model)
    {
        parent::__construct($model);
    }

    /**
     * Check if exists by admission_number
     */
    public function exists(string $column, string $value): bool
    {
        return $this->model->where($column, $value)->exists();
    }
}
