<?php

namespace App\Repositories;

use App\Interfaces\MedicalRecordRepositoryInterface;
use App\Models\MedicalRecord;

class MedicalRecordRepository extends BaseRepository implements MedicalRecordRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(MedicalRecord $model)
    {
        parent::__construct($model);
    }

    public function updateByNumber($number, $data)
    {
        return $this->model->where('number', $number)->update($data);
    }
}