<?php

namespace App\repositories;

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
}
