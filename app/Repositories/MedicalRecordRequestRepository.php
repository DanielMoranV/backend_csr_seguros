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
     * Override getAll to order by request_date descending.
     *
     * @param array $relations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(array $relations = [])
    {
        if (!empty($relations)) {
            return $this->model::with($relations)->orderBy('request_date', 'desc')->get();
        }
        return $this->model::orderBy('request_date', 'desc')->get();
    }

    /**
     * Override getDateRange to use request_date instead of created_at.
     *
     * @param string $from
     * @param string $to
     * @param array $relations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDateRange($from = null, $to = null, $relations = [])
    {
        if (!empty($relations)) {
            return $this->model::with($relations)
                ->whereBetween('request_date', [$from, $to])
                ->orderBy('request_date', 'desc')
                ->get();
        }
        return $this->model->whereBetween('request_date', [$from, $to])
            ->orderBy('request_date', 'desc')
            ->get();
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
            ->orderBy('request_date', 'desc')
            ->get();
    }
}
