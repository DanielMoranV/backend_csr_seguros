<?php

namespace App\Http\Controllers;

use App\Interfaces\SettlementRepositoryInterface;
use App\Http\Resources\SettlementResource;
use App\Http\Requests\StoreSettlementRequest;
use App\Http\Requests\UpdateSettlementRequest;
use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreSettlementsRequest;
use App\Http\Requests\UpdateSettlementsRequest;
use Illuminate\Support\Facades\DB;

class SettlementController extends Controller
{
    protected $settlementRepositoryInterface;
    protected $relations = ['invoice'];

    public function __construct(SettlementRepositoryInterface $settlementRepositoryInterface)
    {
        $this->settlementRepositoryInterface = $settlementRepositoryInterface;
    }

    public function index()
    {
        $data = $this->settlementRepositoryInterface->getAll($this->relations);
        return ApiResponseClass::sendResponse(SettlementResource::collection($data), '', 200);
    }

    public function store(StoreSettlementRequest $request)
    {
        $data = $request->validated();
        $settlement = $this->settlementRepositoryInterface->store($data);
        return ApiResponseClass::sendResponse(new SettlementResource($settlement), '', 200);
    }

    public function update(UpdateSettlementRequest $request, string $id)
    {
        $data = $request->validated();
        $settlement = $this->settlementRepositoryInterface->update($data, $id);
        return ApiResponseClass::sendResponse(new SettlementResource($settlement), '', 200);
    }

    public function destroy(string $id)
    {
        $this->settlementRepositoryInterface->delete($id);
        return ApiResponseClass::sendResponse([], 'Settlement deleted successfully', 200);
    }

    public function storeMultiple(StoreSettlementsRequest $request)
    {
        $data = $request->all();
        $successfulRecords = [];
        $failedRecords = [];
        DB::beginTransaction();
        try {
            foreach ($data as $settlement) {
                try {
                    $settlement = [
                        'admission_id' => $settlement['admission_id'],
                        'period' => $settlement['period'],
                        'biller' => $settlement['biller'],
                    ];
                    $newSettlement = $this->settlementRepositoryInterface->store($settlement);
                    $successfulRecords[] = $newSettlement;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($settlement, ['error' => $e->getMessage()]);
                }
            }
            DB::commit();
            $response = [
                'success' => $successfulRecords,
                'errors' => $failedRecords,
                'message' => 'Processing complete'
            ];
            return ApiResponseClass::sendResponse($response, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::sendResponse([], $e->getMessage(), 500);
        }
    }

    public function updateMultiple(UpdateSettlementsRequest $request)
    {
        $data = $request->all();
        $successfulRecords = [];
        $failedRecords = [];
        DB::beginTransaction();
        try {
            foreach ($data as $settlement) {
                try {
                    $fields = ['id', 'admission_id', 'period', 'biller'];
                    $settlement = array_filter(
                        array_intersect_key($settlement, array_flip($fields)),
                        fn($value) => $value !== null
                    );
                    $updatedSettlement = $this->settlementRepositoryInterface->update($settlement, $settlement['id']);
                    $successfulRecords[] = $updatedSettlement;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($settlement, ['error' => $e->getMessage()]);
                }
            }
            DB::commit();
            $response = [
                'success' => $successfulRecords,
                'errors' => $failedRecords,
                'message' => 'Processing complete'
            ];
            return ApiResponseClass::sendResponse($response, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::sendResponse([], $e->getMessage(), 500);
        }
    }
}