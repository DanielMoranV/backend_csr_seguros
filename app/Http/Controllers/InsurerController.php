<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreInsurerRequest;
use App\Http\Resources\InsurerResource;
use App\Interfaces\InsurerRepositoryInterface;
use Illuminate\Http\Request;

class InsurerController extends Controller
{
    protected $insurerRepositoryInterface;
    protected $relations = ['invoices', 'settlements', 'medical_record_requests'];

    public function __construct(InsurerRepositoryInterface $insurerRepositoryInterface)
    {
        $this->insurerRepositoryInterface = $insurerRepositoryInterface;
    }

    public function index()
    {
        $data = $this->insurerRepositoryInterface->getAll($this->relations);
        return ApiResponseClass::sendResponse(InsurerResource::collection($data), '', 200);
    }

    public function show(string $id)
    {
        $data = $this->insurerRepositoryInterface->getById($id, $this->relations);
        return ApiResponseClass::sendResponse(new InsurerResource($data), '', 200);
    }

    public function store(StoreInsurerRequest $request)
    {
        $data = $request->all();
        $insurer = $this->insurerRepositoryInterface->store($data);
        return ApiResponseClass::sendResponse(new InsurerResource($insurer), '', 200);
    }
}