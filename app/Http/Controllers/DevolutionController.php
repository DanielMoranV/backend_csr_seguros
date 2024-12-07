<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreDevolutionRequest;
use App\Http\Requests\StoreDevolutionsRequest;
use App\Http\Requests\UpdateDevolutionRequest;
use App\Http\Requests\UpdateDevolutionsRequest;
use App\Http\Resources\DevolutionResource;
use App\Interfaces\DevolutionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DevolutionController extends Controller
{
    protected $devolutionRepositoryInterface;
    protected $relations = ['invoice', 'admission'];

    public function __construct(DevolutionRepositoryInterface $devolutionRepositoryInterface)
    {
        $this->devolutionRepositoryInterface = $devolutionRepositoryInterface;
    }

    public function index()
    {
        $data = $this->devolutionRepositoryInterface->getAll($this->relations);
        return ApiResponseClass::sendResponse(DevolutionResource::collection($data), '', 200);
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
                        'invoice_id' => $devolution['invoice_id'],
                        'type' => $devolution['type'],
                        'reason' => $devolution['reason'],
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
                    $updatedDevolution = [
                        'date' => $devolution['date'],
                        'invoice_id' => $devolution['invoice_id'],
                        'type' => $devolution['type'],
                        'reason' => $devolution['reason'],
                        'period' => $devolution['period'],
                        'biller' => $devolution['biller'],
                        'admission_id' => $devolution['admission_id'],
                    ];
                    $updatedDevolution = $this->devolutionRepositoryInterface->update($updatedDevolution, $devolution['id']);
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
