<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreAuditRequest;
use App\Http\Requests\UpdateAuditRequest;
use App\Http\Resources\AuditResource;
use App\Interfaces\AuditRepositoryInterface;
use App\Models\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditController extends Controller
{
    protected $auditRepositoryInterface;

    public function __construct(AuditRepositoryInterface $auditRepositoryInterface)
    {
        $this->auditRepositoryInterface = $auditRepositoryInterface;
        $this->middleware('compress')->only('index');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $data = $this->auditRepositoryInterface->getAll();
            return ApiResponseClass::sendResponse(AuditResource::collection($data), '', 200);
        } catch (\Exception $e) {
            return ApiResponseClass::sendResponse([], $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAuditRequest $request)
    {
        try {
            $data = $request->validated();
            $audit = $this->auditRepositoryInterface->store($data);
            return ApiResponseClass::sendResponse(new AuditResource($audit), '', 200);
        } catch (\Exception $e) {
            return ApiResponseClass::sendResponse([], $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Audit $audit)
    {
        try {
            $audit = $this->auditRepositoryInterface->getById($audit->id);
            return ApiResponseClass::sendResponse(new AuditResource($audit), '', 200);
        } catch (\Exception $e) {
            return ApiResponseClass::sendResponse([], $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAuditRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $audit = $this->auditRepositoryInterface->update($data, $id);
            return ApiResponseClass::sendResponse(new AuditResource($audit), '', 200);
        } catch (\Exception $e) {
            return ApiResponseClass::sendResponse([], $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Audit $audit)
    {
        try {
            $this->auditRepositoryInterface->delete($audit->id);
            return ApiResponseClass::sendResponse([], '', 200);
        } catch (\Exception $e) {
            return ApiResponseClass::sendResponse([], $e->getMessage(), 500);
        }
    }

    public function getAuditsByAdmissions(Request $request)
    {
        try {
            $admissions = $request->input('admissions', []);
            if (empty($admissions)) {
                return ApiResponseClass::sendResponse([], 'Admissions are required.', 400);
            }
            $data = $this->auditRepositoryInterface->getAuditsByAdmissions($admissions);
            return ApiResponseClass::sendResponse(AuditResource::collection($data), '', 200);
        } catch (\Exception $e) {
            return ApiResponseClass::sendResponse([], $e->getMessage(), 500);
        }
    }

    public function getAuditsByDateRange(Request $request)
    {
        try {
            $from = $request->input('from', '');
            $to = $request->input('to', '');
            if (empty($from) || empty($to)) {
                return ApiResponseClass::sendResponse([], 'From and To dates are required.', 400);
            }
            $data = $this->auditRepositoryInterface->getAuditsByDateRange($from, $to);
            return ApiResponseClass::sendResponse(AuditResource::collection($data), '', 200);
        } catch (\Exception $e) {
            return ApiResponseClass::sendResponse([], $e->getMessage(), 500);
        }
    }
}