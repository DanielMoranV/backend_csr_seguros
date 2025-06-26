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
    protected $relations = ['admissions', 'medicalRecordRequests'];

    public function __construct(MedicalRecordRepositoryInterface $medicalRecordRepositoryInterface)
    {
        $this->medicalRecordRepositoryInterface = $medicalRecordRepositoryInterface;
        $this->middleware('compress')->only('index');
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
        $data = $request->all();
        $successfulRecords = [];
        $failedRecords = [];

        DB::beginTransaction();
        try {
            foreach ($data as $medicalRecord) {
                try {
                    $medicalRecord = [
                        'number' => $medicalRecord['number'],
                        'patient' => $medicalRecord['patient'] ?? null,
                        'color' => $medicalRecord['color'] ?? null,
                        'description' => $medicalRecord['description'] ?? null,
                    ];
                    $newMedicalRecord = $this->medicalRecordRepositoryInterface->store($medicalRecord);
                    $successfulRecords[] = $newMedicalRecord;
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
        $data = $request->all();
        $successfulRecords = [];
        $failedRecords = [];

        DB::beginTransaction();
        try {
            foreach ($data as $medicalRecord) {
                try {
                    $fields = ['number', 'patient', 'color', 'description'];
                    $medicalRecord = array_filter(
                        array_intersect_key($medicalRecord, array_flip($fields)),
                        fn($value) => $value !== null
                    );
                    $updatedMedicalRecord = $this->medicalRecordRepositoryInterface->updateByNumber($medicalRecord['number'], $medicalRecord);
                    $successfulRecords[] = $updatedMedicalRecord;
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
