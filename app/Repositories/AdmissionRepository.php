<?php

namespace App\Repositories;

use App\Interfaces\AdmissionRepositoryInterface;
use App\Models\Admission;

class AdmissionRepository extends BaseRepository implements AdmissionRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(Admission $model)
    {
        parent::__construct($model);
    }

    public function updateByNumber($number, $data)
    {
        return $this->model->where('number', $number)->update($data);
    }

    public function getExistingNumbers(array $numbers)
    {
        return $this->model::whereIn('number', $numbers)->pluck('number')->toArray();
    }
}