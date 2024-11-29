<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Interfaces\InvoiceRepositoryInterface;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    protected $invoiceRepositoryInterface;
    protected $relations = ['admission'];

    public function __construct(InvoiceRepositoryInterface $invoiceRepositoryInterface)
    {
        $this->invoiceRepositoryInterface = $invoiceRepositoryInterface;
    }

    public function index()
    {
        $data = $this->invoiceRepositoryInterface->getAll($this->relations);
        return ApiResponseClass::sendResponse(InvoiceResource::collection($data), '', 200);
    }

    public function show(string $id)
    {
        $data = $this->invoiceRepositoryInterface->getById($id, $this->relations);
        return ApiResponseClass::sendResponse(new InvoiceResource($data), '', 200);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $data = $request->all();
        $invoice = $this->invoiceRepositoryInterface->store($data);
        return ApiResponseClass::sendResponse(new InvoiceResource($invoice), '', 200);
    }
}