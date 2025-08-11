<?php

namespace App\Http\Controllers;

use App\Interfaces\MedicalRecordRequestRepositoryInterface;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\MedicalRecordRequestResource;
use App\Classes\ApiResponseClass;
use App\Events\RequestSent;
use App\Http\Requests\StoreMedicalRecordRequestRequest;
use App\Http\Requests\StoreMedicalRecordRequestsRequest;
use App\Http\Requests\UpdateMedicalRecordRequestRequest;
use App\Http\Requests\UpdateMedicalRecordRequestsRequest;
use App\Http\Requests\DeriveMedicalRecordRequest;
use App\Models\MedicalRecordRequest;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class MedicalRecordRequestController extends Controller
{
    protected $medicalRecordRequestRepositoryInterface;
    protected $relations = ['shipment', 'audit',];

    public function __construct(MedicalRecordRequestRepositoryInterface $medicalRecordRequestRepositoryInterface)
    {
        $this->medicalRecordRequestRepositoryInterface = $medicalRecordRequestRepositoryInterface;
        $this->middleware('compress')->only('index');
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
        $data = $this->medicalRecordRequestRepositoryInterface->getById($id);
        return ApiResponseClass::sendResponse(new MedicalRecordRequestResource($data), '', 200);
    }

    public function store(StoreMedicalRecordRequestRequest $request)
    {
        try {
            $data = $request->validated();
            $medicalRecordRequest = $this->medicalRecordRequestRepositoryInterface->store($data);
            DB::commit();

            event(new RequestSent($medicalRecordRequest));
            return ApiResponseClass::sendResponse(new MedicalRecordRequestResource($medicalRecordRequest), '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function update(UpdateMedicalRecordRequestRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $data = array_filter($request->validated(), function($value) {
                return $value !== null;
            });
            
            $medicalRecordRequest = $this->medicalRecordRequestRepositoryInterface->update($data, $id);
            DB::commit();

            event(new RequestSent($medicalRecordRequest));

            return ApiResponseClass::sendResponse(new MedicalRecordRequestResource($medicalRecordRequest), '', 200);
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
            return ApiResponseClass::sendResponse([], 'From and To dates are required.', 400);
        }
        DB::beginTransaction();
        try {
            $data = $this->medicalRecordRequestRepositoryInterface->getDateRange($from, $to);
            DB::commit();
            return ApiResponseClass::sendResponse(MedicalRecordRequestResource::collection($data), '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
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

    public function getByMedicalRecordNumber($number)
    {
        if (empty($number)) {
            return ApiResponseClass::sendResponse([], 'Medical record number is required.', 400);
        }
        try {
            $data = $this->medicalRecordRequestRepositoryInterface->searchByMedicalRecordNumber($number);
            return ApiResponseClass::sendResponse(MedicalRecordRequestResource::collection($data), '', 200);
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    public function deriveMedicalRecord(DeriveMedicalRecordRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            
            // Obtener el usuario autenticado
            $authUser = auth()->user();
            
            // Preparar datos para la derivación
            $medicalRecordData = [
                'requester_nick' => $authUser->nick,
                'requested_nick' => $data['requested_nick'],
                'derived_by' => $authUser->nick,
                'derived_at' => now(),
                'medical_record_number' => $data['medical_record_number'],
                'request_date' => now(),
                'remarks' => $data['remarks'] ?? null,
                'status' => 'Pendiente'
            ];
            
            $medicalRecordRequest = $this->medicalRecordRequestRepositoryInterface->store($medicalRecordData);
            DB::commit();

            event(new RequestSent($medicalRecordRequest));
            
            return ApiResponseClass::sendResponse(
                new MedicalRecordRequestResource($medicalRecordRequest), 
                'Solicitud de historia clínica derivada exitosamente.', 
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }
}
