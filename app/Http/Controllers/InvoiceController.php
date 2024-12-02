<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\StoreInvoicesRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Requests\UpdateInvoicesRequest;
use App\Http\Resources\InvoiceResource;
use App\Interfaces\InvoiceRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $invoice = $this->invoiceRepositoryInterface->store($data);
            DB::commit();
            return ApiResponseClass::sendResponse(new InvoiceResource($invoice), '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function update(UpdateInvoiceRequest $request, string $id)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $invoice = $this->invoiceRepositoryInterface->update($data, $id);
            DB::commit();
            return ApiResponseClass::sendResponse(new InvoiceResource($invoice), '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $this->invoiceRepositoryInterface->delete($id);
            DB::commit();
            return ApiResponseClass::sendResponse(null, '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function storeMultiple(StoreInvoicesRequest $request)
    {
        $data = $request->all();
        $successfulRecords = [];
        $failedRecords = [];

        DB::beginTransaction();
        try {
            foreach ($data as $invoice) {
                try {
                    $invoice = [
                        'number' => $invoice['number'],
                        'issue_date' => $invoice['issue_date'] ?? null,
                        'status' => $invoice['status'] ?? null,
                        'payment_date' => $invoice['payment_date'] ?? null,
                        'amount' => $invoice['amount'] ?? null,
                        'admission_id' => $invoice['admission_id'] ?? null,
                        'biller' => $invoice['biller'] ?? null,
                    ];
                    $newInvoice = $this->invoiceRepositoryInterface->store($invoice);
                    $successfulRecords[] = $newInvoice;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($invoice, ['error' => $e->getMessage()]);
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
            Log::warning('Error storing invoices', ['error' => $e->getMessage()]);
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function updateMultiple(UpdateInvoicesRequest $request)
    {
        $data = $request->all();
        $successfulRecords = [];
        $failedRecords = [];

        DB::beginTransaction();
        try {
            foreach ($data as $invoice) {
                try {
                    $invoice = [
                        'number' => $invoice['number'],
                        'issue_date' => $invoice['issue_date'] ?? null,
                        'status' => $invoice['status'] ?? null,
                        'payment_date' => $invoice['payment_date'] ?? null,
                        'amount' => $invoice['amount'] ?? null,
                        'biller' => $invoice['biller'] ?? null,
                    ];
                    $updatedInvoice = $this->invoiceRepositoryInterface->updateByNumber($invoice['number'], $invoice);
                    $successfulRecords[] = $updatedInvoice;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($invoice, ['error' => $e->getMessage()]);
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
