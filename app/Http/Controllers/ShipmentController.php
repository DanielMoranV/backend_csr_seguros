<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Requests\StoreShipmentsRequest;
use App\Http\Requests\UpdateShipmentRequest;
use App\Http\Resources\ShipmentResource;
use App\Interfaces\ShipmentRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


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

    public function storeMultiple(StoreShipmentsRequest $request)
    {
        $data = $request->validated();
        $successfulRecords = [];
        $failedRecords = [];
        DB::beginTransaction();
        try {
            foreach ($data as $shipment) {
                try {
                    $fields = ['verified_shipment', 'shipment_date', 'invoice_id', 'remarks', 'trama_verified', 'trama_date', 'courier_verified', 'courier_date', 'email_verified', 'email_verified_date'];
                    $shipment = array_filter(
                        array_intersect_key($shipment, array_flip($fields)),
                        fn($value) => $value !== null
                    );
                    $newShipment = $this->shipmentRepositoryInterface->store($shipment);
                    $successfulRecords[] = $newShipment;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($shipment, ['error' => $e->getMessage()]);
                }
            }
            DB::commit();
            return ApiResponseClass::sendResponse(ShipmentResource::collection($successfulRecords), '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function getDateRange(Request $request)
    {
        $from = $request->input('from', '');
        $to = $request->input('to', '');
        if (empty($from) || empty($to)) {
            return ApiResponseClass::sendResponse([], 'Please provide both from and to date', 400);
        }

        DB::beginTransaction();
        try {
            $data = $this->shipmentRepositoryInterface->getDateRange($from, $to);
            DB::commit();
            return ApiResponseClass::sendResponse(ShipmentResource::collection($data), '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }
}
