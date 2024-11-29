<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Interfaces\MedicalRecordRepositoryInterface;
use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreMedicalRecordRequest;
use App\Http\Resources\MedicalRecordResource;

class MedicalRecordController extends Controller
{
    protected $medicalRecordRepositoryInterface;
    protected $relations = ['admissions', 'invoices', 'settlements'];

    public function __construct(MedicalRecordRepositoryInterface $medicalRecordRepositoryInterface)
    {
        $this->medicalRecordRepositoryInterface = $medicalRecordRepositoryInterface;
    }

    public function index()
    {
        $data = $this->medicalRecordRepositoryInterface->getAll($this->relations);
        return ApiResponseClass::sendResponse(MedicalRecordResource::collection($data), '', 200);
    }

    public function show(string $id)
    {
        $data = $this->medicalRecordRepositoryInterface->getById($id, $this->relations);
        return ApiResponseClass::sendResponse(new MedicalRecordResource($data), '', 200);
    }

    public function store(StoreMedicalRecordRequest $request)
    {
        $data = $request->all();
        $medicalRecord = $this->medicalRecordRepositoryInterface->store($data);
        return ApiResponseClass::sendResponse(new MedicalRecordResource($medicalRecord), '', 200);
    }
}