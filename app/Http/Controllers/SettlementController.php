<?php

namespace App\Http\Controllers;

use App\Interfaces\SettlementRepositoryInterface;
use App\Http\Resources\SettlementResource;
use App\Http\Requests\StoreSettlementRequest;
use App\Http\Requests\UpdateSettlementRequest;
use App\Classes\ApiResponseClass;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    protected $settlementRepositoryInterface;
    protected $relations = ['invoice'];

    public function __construct(SettlementRepositoryInterface $settlementRepositoryInterface)
    {
        $this->settlementRepositoryInterface = $settlementRepositoryInterface;
    }

    public function index()
    {
        $data = $this->settlementRepositoryInterface->getAll($this->relations);
        return ApiResponseClass::sendResponse(SettlementResource::collection($data), '', 200);
    }

    public function store(StoreSettlementRequest $request)
    {
        $data = $request->validated();
        $settlement = $this->settlementRepositoryInterface->store($data);
        return ApiResponseClass::sendResponse(new SettlementResource($settlement), '', 200);
    }

    public function update(UpdateSettlementRequest $request, string $id)
    {
        $data = $request->validated();
        $settlement = $this->settlementRepositoryInterface->update($data, $id);
        return ApiResponseClass::sendResponse(new SettlementResource($settlement), '', 200);
    }

    public function destroy(string $id)
    {
        $this->settlementRepositoryInterface->delete($id);
        return ApiResponseClass::sendResponse([], 'Settlement deleted successfully', 200);
    }
}
