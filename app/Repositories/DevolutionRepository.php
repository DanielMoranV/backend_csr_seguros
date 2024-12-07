<?php

namespace App\Repositories;

use App\Interfaces\DevolutionRepositoryInterface;
use App\Models\Devolution;
class DevolutionRepository extends BaseRepository implements DevolutionRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(Devolution $model)
    {
        parent::__construct($model);
    }
}