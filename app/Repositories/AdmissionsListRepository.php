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
}
