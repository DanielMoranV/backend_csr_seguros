<?php

namespace App\Services;

use App\Classes\ApiResponseClass;
use App\Http\Requests\CreateAdmissionsListsRequest;
use App\Http\Requests\StoreAdmissionListRequest;
use App\Http\Resources\AdmissionsListResource;
use App\Interfaces\AdmissionsListRepositoryInterface;
use App\Interfaces\MedicalRecordRequestRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdmissionsListsService
{
    protected $admissionsListRepositoryInterface;
    protected $medicalRecordRequestRepositoryInterface;

    public function __construct(AdmissionsListRepositoryInterface $admissionsListRepositoryInterface, MedicalRecordRequestRepositoryInterface $medicalRecordRequestRepositoryInterface)
    {
        $this->admissionsListRepositoryInterface = $admissionsListRepositoryInterface;
        $this->medicalRecordRequestRepositoryInterface = $medicalRecordRequestRepositoryInterface;
    }

    public function storeAdmissionListAndRequest(CreateAdmissionsListsRequest $request)
    {
        $data = $request->all();
        $successfulRecords = [];
        $failedRecords = [];
        DB::beginTransaction();
        try {

            foreach ($data as $medicalRecordRequest) {
                try {
                    // si admission_number existe en la tabla admissions_lists no se debe permitir guardar y añadirlo a failedRecords
                    if ($this->admissionsListRepositoryInterface->exists('admission_number', $medicalRecordRequest['admission_number'])) {
                        $failedRecords[] = array_merge($medicalRecordRequest, ['error' => 'The admission_number has already been taken.']);
                        continue;
                    }
                    $medicalRecordRequestData = [
                        'requester_nick' => $medicalRecordRequest['requester_nick'],
                        'admission_number' => $medicalRecordRequest['admission_number'],
                        'medical_record_number' => $medicalRecordRequest['medical_record_number'],
                        'request_date' => $medicalRecordRequest['request_date'],
                        'remarks' => $medicalRecordRequest['remarks'],
                    ];
                    $newMedicalRecordRequest = $this->medicalRecordRequestRepositoryInterface->store($medicalRecordRequestData);
                    $admissionsListData = $medicalRecordRequest['admissionList'];
                    $admissionsListData['medical_record_request_id'] = $newMedicalRecordRequest->id;
                    $admissionsList = $this->admissionsListRepositoryInterface->store($admissionsListData);
                    $successfulRecords[] = $admissionsList;
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
            return ApiResponseClass::sendResponse($response, '', 200);
        } catch (\Exception $e) {
            Log::warning('Error storing medicalRecordRequest ', ['error' => $e->getMessage()]);
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }
}