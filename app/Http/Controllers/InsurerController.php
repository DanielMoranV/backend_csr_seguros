<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreInsurerRequest;
use App\Http\Requests\StoreInsurersRequest;
use App\Http\Requests\UpdateInsurerRequest;
use App\Http\Requests\UpdateInsurersRequest;
use App\Http\Resources\InsurerResource;
use App\Interfaces\InsurerRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InsurerController extends Controller
{
    protected $insurerRepositoryInterface;
    protected $relations = ['admissions'];

    public function __construct(InsurerRepositoryInterface $insurerRepositoryInterface)
    {
        $this->insurerRepositoryInterface = $insurerRepositoryInterface;
        $this->middleware('compress')->only('index');
    }

    public function index()
    {
        $data = $this->insurerRepositoryInterface->getAll($this->relations);
        return ApiResponseClass::sendResponse(InsurerResource::collection($data), '', 200);
    }

    public function show(string $id)
    {
        $data = $this->insurerRepositoryInterface->getById($id, $this->relations);
        return ApiResponseClass::sendResponse(new InsurerResource($data), '', 200);
    }

    public function store(StoreInsurerRequest $request)
    {
        $data = $request->validated();
        $insurer = $this->insurerRepositoryInterface->store($data);
        return ApiResponseClass::sendResponse(new InsurerResource($insurer), '', 200);
    }

    public function update(UpdateInsurerRequest $request, string $id)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $insurer = $this->insurerRepositoryInterface->update($data, $id);
            DB::commit();
            return ApiResponseClass::sendResponse(new InsurerResource($insurer), '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $this->insurerRepositoryInterface->delete($id);
            DB::commit();
            return ApiResponseClass::sendResponse(null, '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::errorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function storeMultiple(StoreInsurersRequest $request)
    {
        $data = $request->validated();
        $successfulRecords = [];
        $failedRecords = [];

        DB::beginTransaction();
        try {
            foreach ($data as $insurer) {
                try {
                    $insurer = [
                        'name' => $insurer['name'],
                        'shipping_period' => $insurer['shipping_period'] ?? null,
                        'payment_period' => $insurer['payment_period'] ?? null,
                    ];
                    $newInsurer = $this->insurerRepositoryInterface->store($insurer);
                    $successfulRecords[] = $newInsurer;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($insurer, ['error' => $e->getMessage()]);
                }
            }
            DB::commit();
            $response = [
                'success' => $successfulRecords,
                'errors' => $failedRecords,
                'message' => 'Processing complete'
            ];
            return ApiResponseClass::sendResponse($response, '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::errorResponse($e->getMessage(), $e->getCode());
        }
    }
    public function updateMultiple(UpdateInsurersRequest $request)
    {
        $data = $request->validated();
        $successfulRecords = [];
        $failedRecords = [];

        DB::beginTransaction();
        try {
            foreach ($data as $insurer) {
                try {
                    $updatedInsurer = $this->insurerRepositoryInterface->updateByName($insurer['name'], $insurer);
                    $successfulRecords[] = $updatedInsurer;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($insurer, ['error' => $e->getMessage()]);
                }
            }
            DB::commit();
            $response = [
                'success' => $successfulRecords,
                'errors' => $failedRecords,
                'message' => 'Processing complete'
            ];
            return ApiResponseClass::sendResponse($response, '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::errorResponse($e->getMessage(), $e->getCode());
        }
    }
}
