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

class AdmissionController extends Controller
{
    protected $admissionRepositoryInterface;
    protected $relations = ['medical_record', 'insurer', 'invoices', 'settlements'];

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
        $data = $request->all();
        $admission = $this->admissionRepositoryInterface->store($data);
        return ApiResponseClass::sendResponse(new AdmissionResource($admission), '', 200);
    }

    public function storeMultiple(StoreAdmissionsRequest $request)
    {
        $validated = $request->validated();

        $successfulRecords = [];
        $failedRecords = [];

        DB::beginTransaction();
        try {
            foreach ($validated as $admission) {
                try {
                    $this->admissionRepositoryInterface->store($admission);
                    $successfulRecords[] = $admission;
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

    public function updateMultiple(UpdateAdmissionRequest $request)
    {
        $validated = $request->validated();

        $successfulRecords = [];
        $failedRecords = [];

        DB::beginTransaction();
        try {
            foreach ($validated as $admission) {
                try {
                    $this->admissionRepositoryInterface->updateByNumber($admission['number'], $admission);
                    $successfulRecords[] = $admission;
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