<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreDevolutionRequest;
use App\Http\Requests\StoreDevolutionsRequest;
use App\Http\Requests\UpdateDevolutionRequest;
use App\Http\Requests\UpdateDevolutionsRequest;
use App\Http\Resources\DevolutionResource;
use App\Interfaces\DevolutionRepositoryInterface;
use App\Services\ApiSisclinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DevolutionController extends Controller
{
    protected $devolutionRepositoryInterface;
    protected $relations = ['invoice', 'admission.insurer'];
    protected $apiSisclinService;

    public function __construct(DevolutionRepositoryInterface $devolutionRepositoryInterface, ApiSisclinService $apiSisclinService)
    {
        $this->devolutionRepositoryInterface = $devolutionRepositoryInterface;
        $this->apiSisclinService = $apiSisclinService;
        $this->middleware('compress')->only('index');
    }

    public function index()
    {
        try {
            $data = $this->devolutionRepositoryInterface->getAll($this->relations);
            return ApiResponseClass::sendResponse(DevolutionResource::collection($data), '', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return ApiResponseClass::rollback($e);
        }
    }

    public function store(StoreDevolutionRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $devolution = $this->devolutionRepositoryInterface->store($data);
            DB::commit();
            return ApiResponseClass::sendResponse(new DevolutionResource($devolution), '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function update(UpdateDevolutionRequest $request, $id)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $devolution = $this->devolutionRepositoryInterface->update($data, $id);
            DB::commit();
            return ApiResponseClass::sendResponse(new DevolutionResource($devolution), '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $this->devolutionRepositoryInterface->delete($id);
            DB::commit();
            return ApiResponseClass::sendResponse([], '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function storeMultiple(StoreDevolutionsRequest $request)
    {
        $data = $request->all();
        $successfulRecords = [];
        $failedRecords = [];
        DB::beginTransaction();
        try {
            foreach ($data as $devolution) {
                try {
                    $devolution = [
                        'date' => $devolution['date'],
                        'invoice_id' => $devolution['invoice_id'] ?? null,
                        'type' => $devolution['type'],
                        'reason' => $devolution['reason'],
                        'status' => $devolution['status'],
                        'period' => $devolution['period'],
                        'biller' => $devolution['biller'],
                        'admission_id' => $devolution['admission_id'],
                    ];
                    $newDevolution = $this->devolutionRepositoryInterface->store($devolution);
                    $successfulRecords[] = $newDevolution;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($devolution, ['error' => $e->getMessage()]);
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

    public function updateMultiple(UpdateDevolutionsRequest $request)
    {
        $data = $request->all();
        $successfulRecords = [];
        $failedRecords = [];
        DB::beginTransaction();
        try {
            foreach ($data as $devolution) {
                try {
                    $fields = ['date', 'invoice_id', 'type', 'reason', 'period', 'biller', 'status', 'admission_id'];

                    $updatedDevolution = array_filter(
                        array_intersect_key($devolution, array_flip($fields)),
                        fn($value) => $value !== null
                    );
                    $updatedDevolution = $this->devolutionRepositoryInterface->updateByInvoiceId($updatedDevolution, $devolution['invoice_id']);
                    $successfulRecords[] = $updatedDevolution;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($devolution, ['error' => $e->getMessage()]);
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