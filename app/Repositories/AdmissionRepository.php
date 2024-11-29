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
}