<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Interfaces\MedicalRecordRepositoryInterface;
use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreMedicalRecordRequest;
use App\Http\Requests\StoreMedicalRecordsRequest;
use App\Http\Requests\UpdateMedicalRecordRequest;
use App\Http\Requests\UpdateMedicalRecordsRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\MedicalRecordResource;
use Illuminate\Support\Facades\Log;

class MedicalRecordController extends Controller
{
    protected $medicalRecordRepositoryInterface;
    protected $relations = ['admissions', 'invoices', 'settlements'];

    public function __construct(MedicalRecordRepositoryInterface $medicalRecordRepositoryInterface)
    {
        $this->medicalRecordRepositoryInterface = $medicalRecordRepositoryInterface;
    }

    public function index()
    {
        $data = $this->medicalRecordRepositoryInterface->getAll($this->relations);
        return ApiResponseClass::sendResponse(MedicalRecordResource::collection($data), '', 200);
    }

    public function show(string $id)
    {
        $data = $this->medicalRecordRepositoryInterface->getById($id, $this->relations);
        return ApiResponseClass::sendResponse(new MedicalRecordResource($data), '', 200);
    }

    public function store(StoreMedicalRecordRequest $request)
    {
        $validated = $request->validated();
        DB::beginTransaction();
        try {
            $medicalRecord = $this->medicalRecordRepositoryInterface->store($validated);
            DB::commit();
            return ApiResponseClass::sendResponse(new MedicalRecordResource($medicalRecord), '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function update(UpdateMedicalRecordRequest $request, string $id)
    {
        $validated = $request->validated();
        DB::beginTransaction();
        try {
            $medicalRecord = $this->medicalRecordRepositoryInterface->update($validated, $id);
            DB::commit();
            return ApiResponseClass::sendResponse(new MedicalRecordResource($medicalRecord), '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $this->medicalRecordRepositoryInterface->delete($id);
            DB::commit();
            return ApiResponseClass::sendResponse(null, '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function storeMultiple(StoreMedicalRecordsRequest $request)
    {
        $validated = $request->validated();
        $successfulRecords = [];
        $failedRecords = [];

        DB::beginTransaction();
        try {
            foreach ($validated as $medicalRecord) {
                try {
                    $this->medicalRecordRepositoryInterface->store($medicalRecord);
                    $successfulRecords[] = $medicalRecord;
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                    $failedRecords[] = array_merge($medicalRecord, ['error' => $e->getMessage()]);
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
            Log::error($e->getMessage());
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function updateMultiple(UpdateMedicalRecordsRequest $request)
    {
        $validated = $request->validated();
        $successfulRecords = [];
        $failedRecords = [];

        DB::beginTransaction();
        try {
            foreach ($validated as $medicalRecord) {
                try {
                    $this->medicalRecordRepositoryInterface->updateByNumber($medicalRecord['number'], $medicalRecord);
                    $successfulRecords[] = $medicalRecord;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($medicalRecord, ['error' => $e->getMessage()]);
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