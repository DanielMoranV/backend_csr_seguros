<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreAdmissionRequest;
use App\Http\Requests\StoreAdmissionsRequest;
use App\Http\Requests\UpdateAdmissionRequest;
use App\Http\Resources\AdmissionResource;
use App\Interfaces\AdmissionRepositoryInterface;
use App\Services\ApiSisclinService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdmissionController extends Controller
{
    protected $admissionRepositoryInterface;
    protected $relations = ['insurer', 'invoices', 'settlements', 'devolutions'];
    protected $apiSisclinService;


    public function __construct(AdmissionRepositoryInterface $admissionRepositoryInterface, ApiSisclinService $apiSisclinService)
    {
        $this->admissionRepositoryInterface = $admissionRepositoryInterface;
        $this->apiSisclinService = $apiSisclinService;
    }

    public function executeQuery(Request $request)
    {
        $data = "SELECT SC0011.num_doc as number, SC0011.fec_doc as attendance_date, SC0011.nom_pac as patient, SC0011.hi_doc as attendance_hour, SC0011.ta_doc as type, SC0011.tot_doc as amount, SC0003.nom_emp as company, SC0006.nom_ser as doctor, SC0004.nh_pac medical_record_number, SC0011.clos_doc as is_closed, SC0017.num_fac as invoice_number, SC0017.fec_fac as invoice_date, SC0017.uc_sis as biller, SC0033.fh_dev, SC0002.nom_cia as insurer_name FROM SC0011 LEFT JOIN SC0006 ON SC0011.cod_ser = SC0006.cod_ser LEFT JOIN SC0002 ON LEFT(SC0011.cod_emp, 2) = SC0002.cod_cia LEFT JOIN SC0003 ON SC0011.cod_emp = SC0003.cod_emp LEFT JOIN SC0004 ON SC0011.cod_pac = SC0004.cod_pac LEFT JOIN SC0033 ON SC0011.num_doc = SC0033.num_doc LEFT JOIN SC0017 ON SC0011.num_doc = SC0017.num_doc WHERE SC0011.fec_doc BETWEEN ctod('12-12-2024') AND ctod('12-12-2024') AND SC0002.nom_cia <> 'PARTICULAR' AND SC0004.nh_pac IS NOT NULL AND SC0004.nh_pac <> '' AND SC0011.nom_pac IS NOT NULL AND SC0011.nom_pac <> '' ORDER BY SC0011.cod_ser ASC;";
        $response = $this->apiSisclinService->executeQuery($data);
        Log::info('Response from FastAPI: ' . $response->status());
        return ApiResponseClass::sendResponse($response->json()['data'], '', $response->status());
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
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $admission = $this->admissionRepositoryInterface->store($data);
            DB::commit();
            return ApiResponseClass::sendResponse(new AdmissionResource($admission), '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function update(UpdateAdmissionRequest $request, string $id)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $admission = $this->admissionRepositoryInterface->update($data, $id);
            DB::commit();
            return ApiResponseClass::sendResponse(new AdmissionResource($admission), '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $this->admissionRepositoryInterface->delete($id);
            DB::commit();
            return ApiResponseClass::sendResponse(null, '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function storeMultiple(StoreAdmissionsRequest $request)
    {
        $data = $request->all();
        $batchSize = 500; // Tamaño del bloque

        $successfulRecords = [];
        $failedRecords = [];
        $dataChunks = array_chunk($data, $batchSize);

        DB::beginTransaction();
        try {
            foreach ($dataChunks as $chunk) {
                $validAdmissions = $this->prepareAdmissions($chunk, $failedRecords);

                // Inserción masiva utilizando Query Builder
                if (!empty($validAdmissions)) {
                    DB::table('admissions')->insert($validAdmissions); // Asume que la tabla se llama 'admissions'
                    $successfulRecords = [...$successfulRecords, ...$validAdmissions];
                }
            }
            DB::commit();

            return ApiResponseClass::sendResponse(
                [
                    'success' => $successfulRecords,
                    'errors' => $failedRecords,
                    'message' => 'Processing complete',
                ],
                'Records processed successfully',
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * Prepara los datos para ser insertados y captura errores.
     *
     * @param array $chunk
     * @param array &$failedRecords
     * @return array
     */
    private function prepareAdmissions(array $chunk, array &$failedRecords): array
    {
        $validAdmissions = [];
        foreach ($chunk as $admission) {
            try {
                $validatedAdmission = [
                    'number' => $admission['number'],
                    'attendance_date' => $admission['attendance_date'] ?? null,
                    'attendance_hour' => $admission['attendance_hour'] ?? null,
                    'type' => $admission['type'] ?? null,
                    'doctor' => $admission['doctor'] ?? null,
                    'status' => $admission['status'] ?? null,
                    'insurer_id' => $admission['insurer_id'] ?? null,
                    'company' => $admission['company'] ?? null,
                    'amount' => $admission['amount'] ?? null,
                    'patient' => $admission['patient'] ?? null,
                    'medical_record_id' => $admission['medical_record_id'] ?? null,
                    'created_at' => now(), // Asegúrate de manejar timestamps si es necesario
                    'updated_at' => now(),
                ];
                $validAdmissions[] = $validatedAdmission;
            } catch (\Exception $e) {
                $failedRecords[] = array_merge($admission, ['error' => $e->getMessage()]);
            }
        }
        return $validAdmissions;
    }


    public function updateMultiple(UpdateAdmissionRequest $request)
    {
        $data = $request->all();

        $successfulRecords = [];
        $failedRecords = [];
        $batchSize = 500; // Tamaño del bloque

        DB::beginTransaction();
        try {
            // Obtener todos los números existentes en una sola consulta
            $numbers = collect($data)->pluck('number')->toArray();
            $existingNumbers = $this->admissionRepositoryInterface->getExistingNumbers($numbers);
            $existingNumbers = collect($existingNumbers)->flip(); // Para búsquedas rápidas

            // Dividir los datos en bloques para mayor eficiencia
            $dataChunks = array_chunk($data, $batchSize);

            foreach ($dataChunks as $chunk) {
                foreach ($chunk as $admission) {
                    try {
                        // Validar que el número exista antes de actualizar
                        if (!isset($existingNumbers[$admission['number']])) {
                            throw new \Exception("Número {$admission['number']} no encontrado");
                        }

                        // Preparar los datos para actualizar
                        $fields = ['number', 'attendance_date', 'attendance_hour', 'type', 'doctor', 'status', 'insurer_id', 'company', 'amount', 'patient', 'medical_record_id'];
                        $updateData = array_filter(
                            array_intersect_key($admission, array_flip($fields)),
                            fn($value) => $value !== null
                        );

                        // Actualización directa usando Query Builder
                        DB::table('admissions')
                            ->where('number', $admission['number'])
                            ->update($updateData);

                        $successfulRecords[] = [
                            'number' => $admission['number'],
                            'updated_data' => $updateData,
                        ];
                    } catch (\Exception $e) {
                        $failedRecords[] = array_merge($admission, ['error' => $e->getMessage()]);
                    }
                }
            }

            DB::commit();

            return ApiResponseClass::sendResponse(
                [
                    'success' => $successfulRecords,
                    'errors' => $failedRecords,
                    'message' => 'Processing complete',
                ],
                'Records processed successfully',
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseClass::rollback($e);
        }
    }
}