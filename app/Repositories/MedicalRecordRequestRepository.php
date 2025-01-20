<?php

namespace App\Repositories;

use App\Interfaces\MedicalRecordRequestRepositoryInterface;
use App\Models\MedicalRecordRequest;

class MedicalRecordRequestRepository extends BaseRepository implements MedicalRecordRequestRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(MedicalRecordRequest $model)
    {
        parent::__construct($model);
    }

    /**
     * Search records by medical record number using LIKE.
     *
     * @param string $medicalRecordNumber
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchByMedicalRecordNumber($medicalRecordNumber)
    {
        return $this->model->where('medical_record_number', 'LIKE', '%' . $medicalRecordNumber . '%')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}