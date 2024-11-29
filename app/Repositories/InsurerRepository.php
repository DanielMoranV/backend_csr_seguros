<?php

namespace App\Repositories;

use App\Interfaces\InsurerRepositoryInterface;
use App\Models\Insurer;

class InsurerRepository extends BaseRepository implements InsurerRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(Insurer $model)
    {
        parent::__construct($model);
    }
}