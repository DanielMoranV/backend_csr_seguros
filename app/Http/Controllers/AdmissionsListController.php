<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\CreateAdmissionsListsRequest;
use App\Http\Requests\StoreAdmissionListRequest;
use App\Http\Requests\StoreAdmissionsListsRequest;
use App\Http\Requests\UpdateAdmissionListRequest;
use App\Http\Resources\AdmissionsListResource;
use App\Interfaces\AdmissionsListRepositoryInterface;
use Illuminate\Support\Facades\DB;

use App\Models\AdmissionsList;
use App\Services\AdmissionsListsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdmissionsListController extends Controller
{
    protected $admissionsListRepositoryInterface;
    protected $admissionsListsService;

    protected $relations = ['shipment', 'audit', 'medicalRecordRequest'];

    public function __construct(AdmissionsListRepositoryInterface $admissionsListRepositoryInterface, AdmissionsListsService $admissionsListsService)
    {
        $this->admissionsListRepositoryInterface = $admissionsListRepositoryInterface;
        $this->admissionsListsService = $admissionsListsService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = $this->admissionsListRepositoryInterface->getAll($this->relations);
        return ApiResponseClass::sendResponse(AdmissionsListResource::collection($data), '', 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdmissionListRequest $request)
    {
        $data = $request->all();
        $admissionsList = $this->admissionsListRepositoryInterface->store($data);
        return ApiResponseClass::sendResponse(new AdmissionsListResource($admissionsList), '', 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(AdmissionsList $admissionsList)
    {
        Log::debug('SHOW');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AdmissionsList $admissionsList)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdmissionListRequest $request, string $id)
    {
        $data = $request->validate();
        $admissionsList = $this->admissionsListRepositoryInterface->update($data, $id);
        return ApiResponseClass::sendResponse(new AdmissionsListResource($admissionsList), '', 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->admissionsListRepositoryInterface->delete($id);
        return ApiResponseClass::sendResponse([], 'deleted successfully', 200);
    }

    public function storeMultiple(StoreAdmissionsListsRequest $request)
    {
        $data = $request->all();
        $successfulRecords = [];
        $failedRecords = [];
        DB::beginTransaction();
        try {
            foreach ($data as $admissionsList) {
                try {
                    $admissionsList = [
                        'admission_number' => $admissionsList['admission_number'],
                        'period' => $admissionsList['period'],
                        'start_date' => $admissionsList['start_date'],
                        'end_date' => $admissionsList['end_date'],
                        'biller' => $admissionsList['biller'],
                        'shipment_id' => $admissionsList['shipment_id'] ?? null,
                        'audit_id' => $admissionsList['audit_id'] ?? null,
                        'medical_record_request_id' => $admissionsList['medical_record_request_id'] ?? null,
                    ];
                    $newAdmissionsList = $this->admissionsListRepositoryInterface->store($admissionsList);
                    $successfulRecords[] = $newAdmissionsList;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($admissionsList, ['error' => $e->getMessage()]);
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

    public function updateMultiple(Request $request)
    {
        $data = $request->all();
        $successfulRecords = [];
        $failedRecords = [];
        DB::beginTransaction();
        try {
            foreach ($data as $admissionsList) {
                try {
                    $admissionsList = [
                        'admission_number' => $admissionsList['admission_number'],
                        'period' => $admissionsList['period'],
                        'start_date' => $admissionsList['start_date'],
                        'end_date' => $admissionsList['end_date'],
                        'biller' => $admissionsList['biller'],
                        'shipment_id' => $admissionsList['shipment_id'] ?? null,
                        'audit_id' => $admissionsList['audit_id'] ?? null,
                        'medical_record_request_id' => $admissionsList['medical_record_request_id'] ?? null,
                    ];
                    $newAdmissionsList = $this->admissionsListRepositoryInterface->update($admissionsList, $admissionsList['admission_number']);
                    $successfulRecords[] = $newAdmissionsList;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($admissionsList, ['error' => $e->getMessage()]);
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

    public function createAdmissionsLists(CreateAdmissionsListsRequest $request)
    {
        return $this->admissionsListsService->storeAdmissionListAndRequest($request);
    }

    public function getByPeriod($period)
    {
        // convertir a period en string antes de usarlo
        $period = (string) $period;
        $data = $this->admissionsListRepositoryInterface->getByPeriod($period, $this->relations);
        return ApiResponseClass::sendResponse($data, '', 200);
    }

    public function getAllPeriods()
    {
        $periods = $this->admissionsListRepositoryInterface->getAllPeriods();
        return ApiResponseClass::sendResponse($periods, 'Periodos', 200);
    }
}