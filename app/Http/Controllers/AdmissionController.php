<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreAdmissionRequest;
use App\Http\Resources\AdmissionResource;
use App\Interfaces\AdmissionRepositoryInterface;
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
}