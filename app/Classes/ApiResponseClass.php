<?php

namespace App\Classes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApiResponseClass
{
    public static function rollback($e, $message = 'Error en el proceso de inserción de datos')
    {
        DB::rollBack();
        self::throw($e, $message);
    }

    public static function throw($e, $message = 'Failure in the process', $code = 500)
    {
        // Registra el tipo de excepción, mensaje y trazas más detalladas
        Log::error('Exception caught', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Devuelve un mensaje más descriptivo si la aplicación está en modo de depuración
        $errorDetails = config('app.debug') ? [
            'exception' => get_class($e),
            'error_message' => $e->getMessage(),
            // 'trace' => $e->getTrace(),
        ] : [];

        throw new HttpResponseException(response()->json([
            'message' => $message,
            'details' => $errorDetails, // Añadimos detalles si es modo debug
        ], $code));
    }

    public static function sendResponse($result, $message = '', $code = 200)
    {
        if ($code === 204) {
            return response()->noContent();
        }

        $response = [
            'success' => true,
            'data' => $result,
            'status' => $code,
        ];

        if (!empty($message)) {
            $response['message'] = $message;
        }

        return response()->json($response, $code);
    }
    public static function errorResponse($message, $code = 500)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $code);
    }

    public static function validationError($validator, $data)
    {
        // Información adicional de validación fallida
        $errors = $validator->errors();
        Log::warning('Validation errors occurred', ['errors' => $errors, 'data' => $data]);

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $errors,
            'data' => $data,
        ], 422));
    }
}
