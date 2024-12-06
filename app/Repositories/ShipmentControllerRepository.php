<?php

namespace App\Repositories;

use App\Interfaces\ShipmentRepositoryInterface;
use App\Models\Shipment;

class ShipmentControllerRepository extends BaseRepository implements ShipmentRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(Shipment $model)
    {
        parent::__construct($model);
    }
}
