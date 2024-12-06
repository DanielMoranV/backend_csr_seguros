<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Requests\UpdateShipmentRequest;
use App\Http\Resources\ShipmentResource;
use App\Interfaces\ShipmentRepositoryInterface;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    protected $shipmentRepositoryInterface;
    protected $relations = ['invoice'];

    public function __construct(ShipmentRepositoryInterface $shipmentRepositoryInterface)
    {
        $this->shipmentRepositoryInterface = $shipmentRepositoryInterface;
    }

    public function index()
    {
        $data = $this->shipmentRepositoryInterface->getAll($this->relations);
        return ApiResponseClass::sendResponse(ShipmentResource::collection($data), '', 200);
    }

    public function show(string $id)
    {
        $data = $this->shipmentRepositoryInterface->getById($id, $this->relations);
        return ApiResponseClass::sendResponse(new ShipmentResource($data), '', 200);
    }

    public function store(StoreShipmentRequest $request)
    {
        $data = $request->validated();
        $shipment = $this->shipmentRepositoryInterface->store($data);
        return ApiResponseClass::sendResponse(new ShipmentResource($shipment), '', 200);
    }

    public function update(UpdateShipmentRequest $request, string $id)
    {
        $data = $request->validated();
        $shipment = $this->shipmentRepositoryInterface->update($data, $id);
        return ApiResponseClass::sendResponse(new ShipmentResource($shipment), '', 200);
    }

    public function destroy(string $id)
    {
        $this->shipmentRepositoryInterface->delete($id);
        return ApiResponseClass::sendResponse([], 'Shipment deleted successfully', 200);
    }
}
