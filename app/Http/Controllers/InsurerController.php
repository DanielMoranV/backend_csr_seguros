<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreInsurerRequest;
use App\Http\Requests\StoreInsurersRequest;
use App\Http\Requests\UpdateInsurersRequest;
use App\Http\Resources\InsurerResource;
use App\Interfaces\InsurerRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InsurerController extends Controller
{
    protected $insurerRepositoryInterface;
    protected $relations = ['invoices', 'settlements', 'medical_record_requests'];

    public function __construct(InsurerRepositoryInterface $insurerRepositoryInterface)
    {
        $this->insurerRepositoryInterface = $insurerRepositoryInterface;
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
        $data = $request->all();
        $insurer = $this->insurerRepositoryInterface->store($data);
        return ApiResponseClass::sendResponse(new InsurerResource($insurer), '', 200);
    }
    public function storeMultiple(StoreInsurersRequest $request)
    {
        $validated = $request->validated();
        $successfulRecords = [];
        $failedRecords = [];

        DB::beginTransaction();
        try {
            foreach ($validated as $insurer) {
                try {
                    $this->insurerRepositoryInterface->store($insurer);
                    $successfulRecords[] = $insurer;
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
            return ApiResponseClass::rollback($e);
        }
    }
    public function updateMultiple(UpdateInsurersRequest $request)
    {
        $validated = $request->validated();
        $successfulRecords = [];
        $failedRecords = [];

        DB::beginTransaction();
        try {
            foreach ($validated as $insurer) {
                try {
                    $this->insurerRepositoryInterface->updateByName($insurer['name'], $insurer);
                    $successfulRecords[] = $insurer;
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
            return ApiResponseClass::rollback($e);
        }
    }
}