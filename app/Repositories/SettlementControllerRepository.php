<?php

namespace App\repositories;

use App\Interfaces\SettlementRepositoryInterface;
use App\Models\Settlement;

class SettlementControllerRepository extends BaseRepository implements SettlementRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(Settlement $model)
    {
        parent::__construct($model);
    }
}
