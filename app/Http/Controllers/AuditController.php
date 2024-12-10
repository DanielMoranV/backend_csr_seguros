<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreAuditRequest;
use App\Http\Resources\AuditResource;
use App\Interfaces\AuditRepositoryInterface;
use App\Models\Audit;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    protected $auditRepositoryInterface;

    public function __construct(AuditRepositoryInterface $auditRepositoryInterface)
    {
        $this->auditRepositoryInterface = $auditRepositoryInterface;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = $this->auditRepositoryInterface->getAll();
        return ApiResponseClass::sendResponse(AuditResource::collection($data), '', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAuditRequest $request)
    {
        $data = $request->validated();
        $audit = $this->auditRepositoryInterface->store($data);
        return ApiResponseClass::sendResponse(new AuditResource($audit), '', 200);
    }


    /**
     * Display the specified resource.
     */
    public function show(Audit $audit)
    {
        $audit = $this->auditRepositoryInterface->getById($audit->id);
        return ApiResponseClass::sendResponse(new AuditResource($audit), '', 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Audit $audit)
    {
        $audit = $this->auditRepositoryInterface->getById($audit->id);
        return ApiResponseClass::sendResponse(new AuditResource($audit), '', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Audit $audit)
    {
        $data = $request->validated();
        $audit = $this->auditRepositoryInterface->update($audit->id, $data);
        return ApiResponseClass::sendResponse(new AuditResource($audit), '', 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Audit $audit)
    {
        $this->auditRepositoryInterface->delete($audit->id);
        return ApiResponseClass::sendResponse([], '', 200);
    }
}
