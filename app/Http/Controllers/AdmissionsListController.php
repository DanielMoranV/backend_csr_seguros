<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\CreateAdmissionsListsRequest;
use App\Http\Requests\StoreAdmissionListRequest;
use App\Http\Requests\StoreAdmissionsListsRequest;
use App\Http\Requests\UpdateAdmissionListRequest;
use App\Http\Resources\AdmissionsListResource;
use App\Interfaces\AdmissionsListRepositoryInterface;
use Illuminate\Support\Facades\DB;

use App\Models\AdmissionsList;
use App\Services\AdmissionsListsService;
use Illuminate\Http\Request;

/**
 * Controlador para gestionar las listas de admisión.
 * Proporciona endpoints para operaciones CRUD y manejo masivo de listas de admisión.
 */
class AdmissionsListController extends Controller
{
    /**
     * Interfaz del repositorio de listas de admisión para acceso a datos.
     */
    protected $admissionsListRepositoryInterface;
    /**
     * Servicio para operaciones avanzadas o personalizadas sobre listas de admisión.
     */
    protected $admissionsListsService;
    /**
     * Relaciones que se cargan junto con la lista de admisión.
     */
    protected $relations = ['shipment', 'audit', 'medicalRecordRequest'];

    /**
     * Constructor: inyecta dependencias del repositorio y servicio.
     */
    public function __construct(AdmissionsListRepositoryInterface $admissionsListRepositoryInterface, AdmissionsListsService $admissionsListsService)
    {
        $this->admissionsListRepositoryInterface = $admissionsListRepositoryInterface;
        $this->admissionsListsService = $admissionsListsService;
    }
    /**
     * Display a listing of the resource.
     */
    /**
     * Muestra una lista de todas las listas de admisión.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $data = $this->admissionsListRepositoryInterface->getAll($this->relations);
        return ApiResponseClass::sendResponse(AdmissionsListResource::collection($data), '', 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    /**
     * Muestra el formulario para crear una nueva lista de admisión (no implementado para API).
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Almacena una nueva lista de admisión en la base de datos.
     * @param StoreAdmissionListRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreAdmissionListRequest $request)
    {
        $data = $request->validated();
        $admissionsList = $this->admissionsListRepositoryInterface->store($data);
        return ApiResponseClass::sendResponse(new AdmissionsListResource($admissionsList), '', 200);
    }

    /**
     * Display the specified resource.
     */
    /**
     * Muestra una lista de admisión específica por su ID.
     * @param AdmissionsList $admissionsList
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(AdmissionsList $admissionsList)
    {
        $data = $this->admissionsListRepositoryInterface->getById($admissionsList->id, $this->relations);
        return ApiResponseClass::sendResponse(new AdmissionsListResource($data), '', 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    /**
     * Muestra el formulario para editar una lista de admisión (no implementado para API).
     */
    public function edit(AdmissionsList $admissionsList)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * Actualiza una lista de admisión existente.
     * @param UpdateAdmissionListRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateAdmissionListRequest $request, string $id)
    {
        $data = $request->validated();
        $admissionsList = $this->admissionsListRepositoryInterface->update($data, $id);
        return ApiResponseClass::sendResponse(new AdmissionsListResource($admissionsList), '', 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * Elimina una lista de admisión por su ID.
     * Utiliza transacciones para asegurar la integridad de los datos.
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $this->admissionsListRepositoryInterface->delete($id);
            DB::commit();
            return ApiResponseClass::sendResponse([], 'deleted successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::errorResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Almacena múltiples listas de admisión en una sola operación.
     * Utiliza transacción y maneja registros exitosos y fallidos.
     * @param StoreAdmissionsListsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeMultiple(StoreAdmissionsListsRequest $request)
    {
        $data = $request->validated();
        $successfulRecords = [];
        $failedRecords = [];
        DB::beginTransaction();
        try {
            foreach ($data as $admissionsList) {
                try {
                    $admissionsList = [
                        'admission_number' => $admissionsList['admission_number'],
                        'period' => $admissionsList['period'],
                        'start_date' => $admissionsList['start_date'],
                        'end_date' => $admissionsList['end_date'],
                        'biller' => $admissionsList['biller'],
                        'observations' => $admissionsList['observations'] ?? null,
                        'audit_requested_at' => $admissionsList['audit_requested_at'] ?? null,
                        'shipment_id' => $admissionsList['shipment_id'] ?? null,
                        'audit_id' => $admissionsList['audit_id'] ?? null,
                        'medical_record_request_id' => $admissionsList['medical_record_request_id'] ?? null,
                    ];
                    $newAdmissionsList = $this->admissionsListRepositoryInterface->store($admissionsList);
                    $successfulRecords[] = $newAdmissionsList;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($admissionsList, ['error' => $e->getMessage()]);
                }
            }
            DB::commit();
            $response = [
                'success' => $successfulRecords,
                'errors' => $failedRecords,
                'message' => 'Processing complete'
            ];
            return ApiResponseClass::sendResponse($response, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::sendResponse([], $e->getMessage(), 500);
        }
    }

    /**
     * Actualiza múltiples listas de admisión en una sola operación.
     * Utiliza transacción y maneja registros exitosos y fallidos.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMultiple(Request $request)
    {
        $data = $request->validated();
        $successfulRecords = [];
        $failedRecords = [];
        DB::beginTransaction();
        try {
            foreach ($data as $admissionsList) {
                try {
                    $admissionsList = [
                        'admission_number' => $admissionsList['admission_number'],
                        'period' => $admissionsList['period'],
                        'start_date' => $admissionsList['start_date'],
                        'end_date' => $admissionsList['end_date'],
                        'biller' => $admissionsList['biller'],
                        'observations' => $admissionsList['observations'] ?? null,
                        'audit_requested_at' => $admissionsList['audit_requested_at'] ?? null,
                        'shipment_id' => $admissionsList['shipment_id'] ?? null,
                        'audit_id' => $admissionsList['audit_id'] ?? null,
                        'medical_record_request_id' => $admissionsList['medical_record_request_id'] ?? null,
                    ];
                    $newAdmissionsList = $this->admissionsListRepositoryInterface->update($admissionsList, $admissionsList['admission_number']);
                    $successfulRecords[] = $newAdmissionsList;
                } catch (\Exception $e) {
                    $failedRecords[] = array_merge($admissionsList, ['error' => $e->getMessage()]);
                }
            }
            DB::commit();
            $response = [
                'success' => $successfulRecords,
                'errors' => $failedRecords,
                'message' => 'Processing complete'
            ];
            return ApiResponseClass::sendResponse($response, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::sendResponse([], $e->getMessage(), 500);
        }
    }

    /**
     * Crea listas de admisión y realiza una solicitud relacionada usando el servicio.
     * @param CreateAdmissionsListsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createAdmissionsLists(CreateAdmissionsListsRequest $request)
    {
        return $this->admissionsListsService->storeAdmissionListAndRequest($request);
    }

    /**
     * Obtiene todas las listas de admisión para un periodo específico.
     * @param string $period
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByPeriod($period)
    {
        // convertir a period en string antes de usarlo
        $period = (string) $period;
        $data = $this->admissionsListRepositoryInterface->getByPeriod($period, $this->relations);
        return ApiResponseClass::sendResponse(AdmissionsListResource::collection($data), '', 200);
    }

    /**
     * Obtiene todos los periodos disponibles de listas de admisión.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllPeriods()
    {
        $periods = $this->admissionsListRepositoryInterface->getAllPeriods();
        return ApiResponseClass::sendResponse($periods, 'Periodos', 200);
    }
}
