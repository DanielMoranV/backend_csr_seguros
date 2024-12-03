<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreAdmissionRequest;
use App\Http\Requests\StoreAdmissionsRequest;
use App\Http\Requests\UpdateAdmissionRequest;
use App\Http\Resources\AdmissionResource;
use App\Interfaces\AdmissionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdmissionController extends Controller
{
    protected $admissionRepositoryInterface;
    protected $relations = ['insurer', 'invoices', 'settlements'];

    public function __construct(AdmissionRepositoryInterface $admissionRepositoryInterface)
    {
        $this->admissionRepositoryInterface = $admissionRepositoryInterface;
    }

    public function index()
    {
        $data = $this->admissionRepositoryInterface->getAll($this->relations);
        return ApiResponseClass::sendResponse(AdmissionResource::collection($data), '', 200);
    }

    public function show(string $id)
    {
        $data = $this->admissionRepositoryInterface->getById($id, $this->relations);
        return ApiResponseClass::sendResponse(new AdmissionResource($data), '', 200);
    }

    public function store(StoreAdmissionRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $admission = $this->admissionRepositoryInterface->store($data);
            DB::commit();
            return ApiResponseClass::sendResponse(new AdmissionResource($admission), '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function update(UpdateAdmissionRequest $request, string $id)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $admission = $this->admissionRepositoryInterface->update($data, $id);
            DB::commit();
            return ApiResponseClass::sendResponse(new AdmissionResource($admission), '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $this->admissionRepositoryInterface->delete($id);
            DB::commit();
            return ApiResponseClass::sendResponse(null, '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function storeMultiple(StoreAdmissionsRequest $request)
    {
        $data = $request->all();

        $successfulRecords = [];
        $failedRecords = [];
        $batchSize = 500; // Tamaño del bloque
        $dataChunks = array_chunk($data, $batchSize);

        DB::beginTransaction();
        try {
            foreach ($dataChunks as $chunk) {
                $validAdmissions = [];
                foreach ($chunk as $admission) {
                    try {
                        // Validar y preparar los datos
                        $validatedAdmission = [
                            'number' => $admission['number'],
                            'attendance_date' => $admission['attendance_date'] ?? null,
                            'attendance_hour' => $admission['attendance_hour'] ?? null,
                            'type' => $admission['type'] ?? null,
                            'doctor' => $admission['doctor'] ?? null,
                            'status' => $admission['status'] ?? null,
                            'insurer_id' => $admission['insurer_id'] ?? null,
                            'company' => $admission['company'] ?? null,
                            'amount' => $admission['amount'] ?? null,
                            'patient' => $admission['patient'] ?? null,
                            'medical_record_id' => $admission['medical_record_id'] ?? null,
                        ];
                        $validAdmissions[] = $validatedAdmission;
                    } catch (\Exception $e) {
                        $failedRecords[] = array_merge($admission, ['error' => $e->getMessage()]);
                    }
                }

                // Inserción masiva de los registros válidos
                if (!empty($validAdmissions)) {
                    $this->admissionRepositoryInterface->bulkStore($validAdmissions);
                    $successfulRecords = array_merge($successfulRecords, $validAdmissions);
                }
            }
            DB::commit();

            $response = [
                'success' => $successfulRecords,
                'errors' => $failedRecords,
                'message' => 'Processing complete',
            ];
            return ApiResponseClass::sendResponse($response, 'Records processed successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }


    public function updateMultiple(UpdateAdmissionRequest $request)
    {
        $data = $request->all();

        $successfulRecords = [];
        $failedRecords = [];

        DB::beginTransaction();
        try {
            // Validar que todos los números existen en la base de datos
            $numbers = collect($data)->pluck('number')->toArray();
            $existingNumbers = $this->admissionRepositoryInterface->getExistingNumbers($numbers);

            foreach ($data as $admission) {
                try {
                    // Validar que el número exista antes de actualizar
                    if (!in_array($admission['number'], $existingNumbers)) {
                        throw new \Exception("Número {$admission['number']} no encontrado");
                    }

                    // Preparar los datos para actualizar
                    $updateData = [
                        'attendance_date' => $admission['attendance_date'] ?? null,
                        'attendance_hour' => $admission['attendance_hour'] ?? null,
                        'type' => $admission['type'] ?? null,
                        'doctor' => $admission['doctor'] ?? null,
                        'status' => $admission['status'] ?? null,
                        'insurer_id' => $admission['insurer_id'] ?? null,
                        'company' => $admission['company'] ?? null,
                        'amount' => $admission['amount'] ?? null,
                        'patient' => $admission['patient'] ?? null,
                        'medical_record_id' => $admission['medical_record_id'] ?? null,
                    ];

                    $updatedAdmission = $this->admissionRepositoryInterface->updateByNumber($admission['number'], $updateData);
                    $successfulRecords[] = $updatedAdmission;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($admission, ['error' => $e->getMessage()]);
                }
            }

            DB::commit();

            $response = [
                'success' => $successfulRecords,
                'errors' => $failedRecords,
                'message' => 'Processing complete'
            ];
            return ApiResponseClass::sendResponse($response, 'Records processed successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }
}
