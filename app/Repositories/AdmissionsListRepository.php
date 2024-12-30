<?php

namespace App\repositories;

use App\Interfaces\AdmissionsListRepositoryInterface;
use App\Models\AdmissionsList;
use Illuminate\Support\Facades\Log;

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

    /**
     * Get by period
     */
    public function getByPeriod(string $period, array $relations = [])
    {
        if (!empty($relations)) {
            return $this->model->with($relations)->where('period', 'like', '%' . $period . '%')->get();
        }
        return $this->model->where('period', 'like', '%' . $period . '%')->get();
    }

    /**
     * Get all periods
     */

    public function getAllPeriods()
    {
        // ORDENAR DE MAYOR A MENOR SEGUN PERIOD
        return $this->model->select('period')->distinct()->orderBy('period', 'desc')->get()->toArray();
    }
}