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
}
