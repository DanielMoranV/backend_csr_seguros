<?php

namespace App\Http\Controllers;

use App\Interfaces\MedicalRecordRequestRepositoryInterface;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\MedicalRecordRequestResource;
use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreMedicalRecordRequestsRequest;
use App\Http\Requests\UpdateMedicalRecordRequestsRequest;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\Request;

class MedicalRecordRequestController extends Controller
{
    protected $medicalRecordRequestRepositoryInterface;
    protected $relations = ['shipment', 'audit',];

    public function __construct(MedicalRecordRequestRepositoryInterface $medicalRecordRequestRepositoryInterface)
    {
        $this->medicalRecordRequestRepositoryInterface = $medicalRecordRequestRepositoryInterface;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = $this->medicalRecordRequestRepositoryInterface->getAll($this->relations);
        return ApiResponseClass::sendResponse(MedicalRecordRequestResource::collection($data), '', 200);
    }

    public function show($id)
    {
        $data = $this->medicalRecordRequestRepositoryInterface->getById($id, $this->relations);
        return ApiResponseClass::sendResponse(new MedicalRecordRequestResource($data), '', 200);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $medicalRecordRequest = $this->medicalRecordRequestRepositoryInterface->store($data);
        return ApiResponseClass::sendResponse(new MedicalRecordRequestResource($medicalRecordRequest), '', 200);
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $medicalRecordRequest = $this->medicalRecordRequestRepositoryInterface->update($id, $data);
        return ApiResponseClass::sendResponse(new MedicalRecordRequestResource($medicalRecordRequest), '', 200);
    }

    public function destroy($id)
    {
        $this->medicalRecordRequestRepositoryInterface->delete($id);
        return ApiResponseClass::sendResponse([], '', 200);
    }

    public function storeMultiple(StoreMedicalRecordRequestsRequest $request)
    {
        $data = $request->all();
        $successfulRecords = [];
        $failedRecords = [];
        try {
            DB::beginTransaction();
            foreach ($data as $medicalRecordRequest) {
                try {
                    $medicalRecordRequest = [
                        'requester_nick' => $medicalRecordRequest['requester_nick'],
                        'requested_nick' => $medicalRecordRequest['requested_nick'],
                        'admission_number' => $medicalRecordRequest['admission_number'],
                        'medical_record_number' => $medicalRecordRequest['medical_record_number'],
                        'request_date' => $medicalRecordRequest['request_date'],
                        'response_date' => $medicalRecordRequest['response_date'],
                        'remarks' => $medicalRecordRequest['remarks'],
                    ];
                    $newMedicalRecordRequest = $this->medicalRecordRequestRepositoryInterface->store($medicalRecordRequest);
                    $successfulRecords[] = $newMedicalRecordRequest;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($medicalRecordRequest, ['error' => $e->getMessage()]);
                }
            }
            DB::commit();
            return ApiResponseClass::sendResponse([
                'successfulRecords' => MedicalRecordRequestResource::collection($successfulRecords),
                'failedRecords' => $failedRecords
            ], '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::sendResponse([], $e->getMessage(), 500);
        }
    }

    public function updateMultiple(UpdateMedicalRecordRequestsRequest $request)
    {
        $data = $request->all();
        $successfulRecords = [];
        $failedRecords = [];
        try {
            DB::beginTransaction();
            foreach ($data as $medicalRecordRequest) {
                try {
                    $fields = ['id', 'requester_nick', 'requested_nick', 'admission_number', 'medical_record_number', 'request_date', 'response_date', 'remarks'];

                    $medicalRecordRequest =
                        array_filter(
                            array_intersect_key($medicalRecordRequest, array_flip($fields)),
                            fn($value) => $value !== null
                        );

                    $newMedicalRecordRequest = $this->medicalRecordRequestRepositoryInterface->update($medicalRecordRequest['id'], $medicalRecordRequest);
                    $successfulRecords[] = $newMedicalRecordRequest;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($medicalRecordRequest, ['error' => $e->getMessage()]);
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
