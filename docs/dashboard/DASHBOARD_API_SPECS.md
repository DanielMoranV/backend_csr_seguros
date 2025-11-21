# Especificaciones API Dashboard - Backend Laravel 12

## Resumen Ejecutivo

Este documento detalla las especificaciones para implementar los endpoints del Dashboard de Admisiones en **Laravel 12 con MySQL**. El objetivo es mover toda la lógica de procesamiento de datos desde el frontend al backend para optimizar el rendimiento y simplificar el mantenimiento.

**Stack Tecnológico**:

-   **Framework**: Laravel 12
-   **Base de Datos**: 2 conexiones MySQL 8.0+
    -   **MySQL Legado**: Queries SQL directas (sin modelos Eloquent) - Tablas SC00XX
    -   **MySQL Aplicación**: Modelos Eloquent - Tablas modernas (admissions_lists, audits, shipments)
-   **ORM**: Query Builder (DB legado) + Eloquent (DB aplicación)
-   **Caché**: Redis (recomendado)
-   **Validación**: Form Requests de Laravel

---

## Índice

1. [Contexto Actual](#contexto-actual)
2. [Arquitectura Laravel](#arquitectura-laravel)
3. [Endpoints Propuestos](#endpoints-propuestos)
4. [Implementación Detallada](#implementación-detallada)
5. [Testing](#testing)
6. [Optimizaciones](#optimizaciones)

---

## Contexto Actual

### Endpoints Actuales del Backend

#### 🔵 Endpoint 1: Análisis por Rango de Fechas

**URL Actual**: `POST /excequte_query` (via `FastApiService.admisionsByRangeDashboard`)

**Flujo Actual**:

1. Frontend envía: `{ start_date: 'MM-DD-YYYY', end_date: 'MM-DD-YYYY' }`
2. Backend ejecuta query SQL directa en MySQL
3. Frontend recibe array de admisiones raw
4. **Frontend procesa todo**: deduplicación, enriquecimiento, agregaciones

**Query SQL Actual (Rango de Fechas)**:

```sql
SELECT
    SC0011.num_doc AS number,
    SC0011.fec_doc AS attendance_date,
    SC0011.ta_doc AS type,
    SC0011.tot_doc AS amount,
    SC0006.nom_ser AS doctor,
    SC0011.clos_doc AS is_closed,
    SC0017.num_fac AS invoice_number,
    SC0017.fec_fac AS invoice_date,
    SC0017.uc_sis AS biller,
    SC0033.fh_dev AS devolution_date,
    SC0002.nom_cia AS insurer_name,
    SC0022.num_fac AS paid_invoice_number
    SC0003.nom_emp as company,
FROM SC0011
LEFT JOIN SC0006 ON SC0011.cod_ser = SC0006.cod_ser
LEFT JOIN SC0002 ON LEFT(SC0011.cod_emp, 2) = SC0002.cod_cia
LEFT JOIN SC0033 ON SC0011.num_doc = SC0033.num_doc
LEFT JOIN SC0003 ON SC0011.cod_emp = SC0003.cod_emp
LEFT JOIN SC0017 ON SC0011.num_doc = SC0017.num_doc
LEFT JOIN SC0022 ON SC0017.num_doc = SC0022.num_doc
WHERE SC0011.fec_doc BETWEEN ? AND ?
    AND SC0011.tot_doc >= 0
    AND SC0011.nom_pac <> ''
    AND SC0011.nom_pac <> 'No existe...'
    AND SC0002.nom_cia <> 'PARTICULAR'
    AND SC0002.nom_cia <> 'PACIENTES PARTICULARES'
ORDER BY SC0011.num_doc DESC;
```

---

#### 🟢 Endpoint 2: Análisis por Periodo (Proceso de 2 pasos)

**PASO 1 - Obtener lista de números de admisión**

**URL Actual**: `GET /admissions-lists/by-period/{period}`

**Flujo**:

1. Frontend envía periodo (ej: "202501")
2. Backend retorna lista de registros desde tabla `admissions_lists` (MySQL con modelos Eloquent):

```json
[
  {
    "id": 1,
    "admission_number": "2025010001",
    "period": "202501",
    "start_date": "2025-01-15",
    "end_date": "2025-01-15",
    "biller": "Juan Perez",
    "auditor": "Dr. Smith",
    "created_at": "2025-01-16T10:30:00Z"
  },
  ...
]
```

**PASO 2 - Obtener datos completos de admisiones de MySQL**

**URL Actual**: `POST /excequte_query` (via `FastApiService.admisionsByNumbers`)

**Flujo**:

1. Frontend extrae `admission_number` de los resultados del PASO 1
2. Envía array de números: `['2025010001', '2025010002', ...]`
3. Backend ejecuta query SQL con `IN` clause en MySQL:

```sql
SELECT
    SC0011.num_doc as number,
    SC0011.fec_doc as attendance_date,
    SC0011.nom_pac as patient,
    SC0011.hi_doc as attendance_hour,
    SC0011.ta_doc as type,
    SC0011.tot_doc as amount,
    SC0017.num_fac as invoice_number,
    SC0017.fec_fac as invoice_date,
    SC0017.uc_sis as biller,
    SC0033.fh_dev as devolution_date,
    SC0033.num_fac as devolution_invoice_number,
    SC0002.nom_cia as insurer_name,
    SC0022.num_fac as paid_invoice_number,
    SC0006.nom_ser as doctor,
    SC0004.nh_pac as medical_record_number,
    SC0003.nom_emp as company,
    SC0011.clos_doc as is_closed
FROM SC0011
LEFT JOIN SC0006 ON SC0011.cod_ser = SC0006.cod_ser
LEFT JOIN SC0002 ON LEFT(SC0011.cod_emp, 2) = SC0002.cod_cia
LEFT JOIN SC0003 ON SC0011.cod_emp = SC0003.cod_emp
LEFT JOIN SC0004 ON SC0011.cod_pac = SC0004.cod_pac
LEFT JOIN SC0033 ON SC0011.num_doc = SC0033.num_doc
LEFT JOIN SC0017 ON SC0011.num_doc = SC0017.num_doc
LEFT JOIN SC0022 ON SC0017.num_doc = SC0022.num_doc
WHERE SC0011.num_doc IN ('2025010001', '2025010002', ...)
ORDER BY SC0011.num_doc DESC;
```

**PASO 3 - Frontend mezcla los datos**

```javascript
// admissionsListsStore.js líneas 79-88
const combinedArray = results.map((admission) => {
    const match = admissionsLists.find((list) => list.admission_number === admission.number);

    return {
        ...admission, // Datos de MySQL Legado (SC0011, SC0017, etc.) - Query directa
        ...match // Datos de MySQL Aplicación (admissions_lists) - Eloquent
    };
});
```

**Resultado combinado**:

```json
{
    "number": "2025010001",
    "attendance_date": "2025-01-15",
    "patient": "Pedro Garcia",
    "type": "EMERGENCIA",
    "amount": 450.5,
    "invoice_number": "001-00123",
    "biller": "Maria Lopez",
    "insurer_name": "MAPFRE",
    // ... datos de MySQL Legado (query directa)

    "admission_number": "2025010001", // Duplicado del number
    "period": "202501", // De MySQL Aplicación (Eloquent)
    "auditor": "Dr. Smith", // De MySQL Aplicación (Eloquent)
    "start_date": "2025-01-15", // De MySQL Aplicación (Eloquent)
    "end_date": "2025-01-15" // De MySQL Aplicación (Eloquent)
}
```

---

### Problema: Procesamiento Masivo en el Frontend

Después de obtener los datos raw, el frontend debe:

1. **Deduplicación**: Agrupa admisiones por `num_doc` y selecciona la factura más reciente
2. **Enriquecimiento**: Calcula días transcurridos, mes, estado de factura
3. **Agregaciones**: Genera estadísticas para gráficos (6 tipos diferentes)
4. **Análisis de Auditores/Facturadores**: Procesa rendimiento por periodo
5. **Join con Shipments**: Busca datos de envíos en MySQL Aplicación (modelos Eloquent) para determinar estado "Enviado"
6. **Join con Audits**: Busca datos de auditorías en MySQL Aplicación (modelos Eloquent) para análisis por periodo

---

### Diagrama de Flujo: Arquitectura Actual vs Propuesta

#### 📊 Flujo Actual - Análisis por Rango de Fechas

```
┌─────────────┐
│  Frontend   │
│   (Vue 3)   │
└──────┬──────┘
       │
       │ 1. POST /excequte_query
       │    { query: "SELECT...", params: [start_date, end_date] }
       ▼
┌──────────────────┐
│    Backend       │
│   (FastAPI)      │
│                  │
│  executeQuery()  │
└────────┬─────────┘
         │
         │ 2. Query MySQL raw
         ▼
    ┌────────────┐
    │   MySQL    │
    │  (SC0011)  │
    └────┬───────┘
         │
         │ 3. Retorna array raw [1000+ registros]
         ▼
┌──────────────────┐
│    Frontend      │
│                  │
│ ❌ Procesa TODO: │
│ • Deduplicación  │
│ • Enriquecimiento│
│ • Agregaciones   │
│ • Join Shipments │
│ • 6 gráficos     │
└──────────────────┘
```

**Problemas**:

-   ⚠️ 1000+ registros transferidos sin procesar
-   ⚠️ Frontend procesa datos masivamente (lento)
-   ⚠️ Lógica de negocio duplicada
-   ⚠️ Difícil de mantener

---

#### 📊 Flujo Actual - Análisis por Periodo (2 requests)

```
┌─────────────┐
│  Frontend   │
└──────┬──────┘
       │
       │ 1. GET /admissions-lists/by-period/202501
       ▼
┌──────────────────┐
│    Backend       │
│   (Laravel)      │
└────────┬─────────┘
         │
         │ 2. Query MySQL App
         ▼
    ┌────────────────┐
    │  MySQL App     │
    │ admissions_    │
    │     lists      │
    └────┬───────────┘
         │
         │ 3. Retorna [{ admission_number, period, biller, auditor }]
         ▼
┌──────────────────┐
│    Frontend      │
│                  │
│ Extrae números:  │
│ [2025010001,     │
│  2025010002...]  │
└────────┬─────────┘
         │
         │ 4. POST /excequte_query
         │    { query: "SELECT ... WHERE num_doc IN (...)" }
         ▼
┌──────────────────┐
│    Backend       │
│   (FastAPI)      │
└────────┬─────────┘
         │
         │ 5. Query MySQL Legado (IN clause)
         ▼
    ┌────────────┐
    │ MySQL Leg. │
    │  (SC0011)  │
    └────┬───────┘
         │
         │ 6. Retorna array completo
         ▼
┌──────────────────┐
│    Frontend      │
│                  │
│ ❌ Mezcla datos: │
│ { ...mysqlLegacy,│
│   ...mysqlApp }  │
│                  │
│ ❌ Procesa TODO: │
│ • Deduplicación  │
│ • Join Audits    │
│ • Join Shipments │
│ • Agregaciones   │
│ • 4 gráficos     │
└──────────────────┘
```

**Problemas**:

-   ⚠️ **2 requests** separados al backend
-   ⚠️ Frontend hace el JOIN manualmente
-   ⚠️ Lógica compleja en el cliente
-   ⚠️ Rendimiento degradado

---

#### ✅ Flujo Propuesto - Un Solo Endpoint por Análisis

```
┌─────────────┐
│  Frontend   │
│   (Vue 3)   │
└──────┬──────┘
       │
       │ 1. POST /api/dashboard/date-range-analysis
       │    { start_date: "2025-01-01", end_date: "2025-01-31" }
       │
       │ O
       │
       │    POST /api/dashboard/period-analysis
       │    { period: "202501" }
       ▼
┌────────────────────────────────────────┐
│          Backend Laravel 12            │
│                                        │
│  DashboardController                   │
│     ↓                                  │
│  DashboardService                      │
│     ↓                                  │
│  AdmissionRepository                   │
│     ↓                                  │
│  ✅ Procesa TODO:                      │
│  • Query optimizado con ROW_NUMBER()  │
│  • Deduplicación en SQL                │
│  • Join MySQL Legado + MySQL App       │
│  • Campos calculados (month, days)     │
│  • Estado de factura (CASE WHEN)       │
│     ↓                                  │
│  AggregationService                    │
│  • Genera 6 agregaciones               │
│  • Datos listos para gráficos          │
└────────┬───────────────────────────────┘
         │
         │ 2. Retorna JSON estructurado
         │    { summary, charts_data, admissions }
         ▼
┌──────────────────┐
│    Frontend      │
│                  │
│ ✅ Solo mapea:   │
│ chartData.value  │
│   = response.data│
└──────────────────┘
```

**Beneficios**:

-   ✅ **1 request** único
-   ✅ Backend procesa todo
-   ✅ Frontend solo renderiza
-   ✅ Performance optimizado
-   ✅ Mantenimiento centralizado

---

### Comparación de Tablas Utilizadas

| Tabla                       | Base de Datos       | Acceso Actual      | Acceso Propuesto | Propósito                       |
| --------------------------- | ------------------- | ------------------ | ---------------- | ------------------------------- |
| `SC0011` (admisiones)       | MySQL Legado        | Query directa      | Query directa    | Datos principales de admisiones |
| `SC0017` (facturas)         | MySQL Legado        | Query directa      | Query directa    | Información de facturación      |
| `SC0022` (facturas pagadas) | MySQL Legado        | Query directa      | Query directa    | Estado de pago                  |
| `SC0033` (devoluciones)     | MySQL Legado        | Query directa      | Query directa    | Devoluciones                    |
| `SC0002` (aseguradoras)     | MySQL Legado        | Query directa      | Query directa    | Información de aseguradoras     |
| `SC0006` (servicios)        | MySQL Legado        | Query directa      | Query directa    | Médicos/doctores                |
| `SC0003` (empresas)         | MySQL Legado        | Query directa      | Query directa    | Nombre de empresa               |
| `SC0004` (pacientes)        | MySQL Legado        | Query directa      | Query directa    | Número de historia clínica      |
| `admissions_lists`          | MySQL Aplicación    | Eloquent (paso 1)  | Eloquent         | Lista de admisiones por periodo |
| `shipments`                 | MySQL Aplicación    | ❌ Frontend        | ✅ Eloquent      | Envíos verificados              |
| `audits`                    | MySQL Aplicación    | ❌ Frontend        | ✅ Eloquent      | Auditorías médicas              |

**Notas importantes**:
- **MySQL Legado**: Base de datos con tablas `SC00XX` sin modelos Eloquent - Se usa Query Builder directo
- **MySQL Aplicación**: Base de datos con modelos Eloquent implementados
- En el flujo actual, las tablas `shipments` y `audits` se consultan desde el **frontend** mediante llamadas adicionales, aumentando la complejidad

---

## Arquitectura Laravel

### Estructura de Archivos

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── DashboardController.php
│   ├── Requests/
│   │   ├── DateRangeAnalysisRequest.php
│   │   └── PeriodAnalysisRequest.php
│   └── Resources/
│       ├── DashboardDateRangeResource.php
│       └── DashboardPeriodResource.php
├── Models/
│   ├── AdmissionsList.php      # MySQL Aplicación (Eloquent)
│   ├── Shipment.php            # MySQL Aplicación (Eloquent)
│   └── Audit.php               # MySQL Aplicación (Eloquent)
├── Services/
│   ├── DashboardService.php
│   ├── AdmissionProcessingService.php
│   └── AggregationService.php
└── Repositories/
    └── AdmissionRepository.php  # Query Builder para MySQL Legado (SC00XX)

routes/
└── api.php

config/
└── database.php  # Configuración de 2 conexiones MySQL
```

---

## Endpoints Propuestos

### 🎯 Endpoint 1: Análisis por Rango de Fechas

**Ruta Laravel**:

```php
// routes/api.php
Route::post('/dashboard/date-range-analysis', [DashboardController::class, 'dateRangeAnalysis'])
    ->middleware(['auth:sanctum'])
    ->name('dashboard.date-range');
```

**URL**: `POST /api/dashboard/date-range-analysis`

**Request**:

```json
{
    "start_date": "2025-01-01",
    "end_date": "2025-01-31"
}
```

**Response**:

```json
{
    "summary": {
        "total_admissions": 450,
        "period": {
            "start": "2025-01-01",
            "end": "2025-01-31"
        }
    },
    "invoice_status_by_month": {
        "view_by_quantity": {
            "months": ["Ene", "Feb", "Mar"],
            "invoiced": [120, 145, 180],
            "pending": [30, 25, 15]
        },
        "view_by_amount": {
            "months": ["Ene", "Feb", "Mar"],
            "invoiced": [45000.50, 58000.75, 72000.00],
            "pending": [12000.00, 9800.50, 6500.25]
        }
    },
    "insurers_by_month": {
        "view_by_quantity": [...],
        "view_by_amount": [...]
    },
    "payment_status": {
        "view_by_quantity": { "paid": 380, "pending": 70 },
        "view_by_amount": { "paid": 152000.50, "pending": 28300.75 }
    },
    "admissions": [...],
    "attendance_type_analysis": {
        "view_by_quantity": [
            {
                "type": "EMERGENCIA",
                "count": 150,
                "percentage": 33.33
            },
            {
                "type": "CONSULTA",
                "count": 200,
                "percentage": 44.44
            },
            {
                "type": "CIRUGIA",
                "count": 100,
                "percentage": 22.22
            }
        ],
        "view_by_amount": [
            {
                "type": "EMERGENCIA",
                "amount": 45000.50,
                "average": 300.00,
                "percentage": 35.00
            },
            {
                "type": "CONSULTA",
                "amount": 50000.75,
                "average": 250.00,
                "percentage": 38.89
            },
            {
                "type": "CIRUGIA",
                "amount": 33500.25,
                "average": 335.00,
                "percentage": 26.11
            }
        ]
    },
    "unique_patients": {
        "total": 380,
        "percentage_of_admissions": 84.44
    },
    "top_companies": {
        "view_by_quantity": [
            {
                "company": "EPS MAPFRE SALUD",
                "count": 85,
                "percentage": 18.89
            },
            {
                "company": "PACIFICO SALUD EPS",
                "count": 67,
                "percentage": 14.89
            }
        ],
        "view_by_amount": [
            {
                "company": "EPS MAPFRE SALUD",
                "amount": 28500.75,
                "percentage": 22.18
            },
            {
                "company": "PACIFICO SALUD EPS",
                "amount": 25300.50,
                "percentage": 19.69
            }
        ]
    }
}
```

### 🎯 Endpoint 2: Análisis por Periodo

**Ruta Laravel**:

```php
// routes/api.php
Route::post('/dashboard/period-analysis', [DashboardController::class, 'periodAnalysis'])
    ->middleware(['auth:sanctum'])
    ->name('dashboard.period');
```

**URL**: `POST /api/dashboard/period-analysis`

**Request**:

```json
{
    "period": "202501"
}
```

**Response**: Ver estructura completa en sección anterior.

---

## Implementación Detallada

### 1. Form Requests (Validación)

#### DateRangeAnalysisRequest.php

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class DateRangeAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajustar según permisos
    }

    public function rules(): array
    {
        return [
            'start_date' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:end_date'
            ],
            'end_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                'before_or_equal:today'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required' => 'La fecha de inicio es requerida',
            'start_date.date_format' => 'La fecha de inicio debe tener formato YYYY-MM-DD',
            'start_date.before_or_equal' => 'La fecha de inicio debe ser anterior o igual a la fecha fin',
            'end_date.required' => 'La fecha fin es requerida',
            'end_date.after_or_equal' => 'La fecha fin debe ser posterior o igual a la fecha inicio',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $start = $this->start_date;
            $end = $this->end_date;

            if ($start && $end) {
                $diff = \Carbon\Carbon::parse($start)->diffInDays(\Carbon\Carbon::parse($end));

                // Validar rango máximo de 1 año
                if ($diff > 365) {
                    $validator->errors()->add(
                        'date_range',
                        'El rango de fechas no puede ser mayor a 1 año'
                    );
                }
            }
        });
    }
}
```

#### PeriodAnalysisRequest.php

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PeriodAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => [
                'required',
                'string',
                'size:6',
                'regex:/^(202[0-9]|203[0-9])(0[1-9]|1[0-2])$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'period.required' => 'El periodo es requerido',
            'period.size' => 'El periodo debe tener 6 dígitos (formato YYYYMM)',
            'period.regex' => 'El periodo debe estar en formato YYYYMM (ej: 202501)',
        ];
    }
}
```

---

### 2. Modelos Eloquent (Solo para MySQL Aplicación)

**IMPORTANTE**: Las tablas `SC00XX` del MySQL Legado **NO tienen modelos Eloquent**. Se acceden mediante Query Builder directo en el Repository.

#### AdmissionsList.php (MySQL Aplicación)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdmissionsList extends Model
{
    protected $connection = 'mysql_app'; // Conexión a MySQL Aplicación
    protected $table = 'admissions_lists';

    protected $fillable = [
        'admission_number',
        'period',
        'start_date',
        'end_date',
        'biller',
        'auditor',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Relaciones
    public function audit(): HasOne
    {
        return $this->hasOne(Audit::class, 'admission_number', 'admission_number');
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class, 'admission_number', 'admission_number');
    }

    // Scopes
    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }
}
```

#### Shipment.php (MySQL Aplicación)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $connection = 'mysql_app'; // Conexión a MySQL Aplicación
    protected $table = 'shipments';

    protected $fillable = [
        'invoice_number',
        'admission_number',
        'verified_shipment_date',
    ];

    protected $casts = [
        'verified_shipment_date' => 'datetime',
    ];
}
```

#### Audit.php (MySQL Aplicación)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $connection = 'mysql_app'; // Conexión a MySQL Aplicación
    protected $table = 'audits';

    protected $fillable = [
        'admission_number',
        'auditor',
        'audit_date',
        'status',
    ];

    protected $casts = [
        'audit_date' => 'datetime',
    ];
}
```

---

### 3. Repository Pattern

#### AdmissionRepository.php

```php
<?php

namespace App\Repositories;

use App\Models\Admission;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdmissionRepository
{
    /**
     * Obtener admisiones deduplicadas con factura más reciente
     */
    public function getUniqueAdmissionsByDateRange(string $startDate, string $endDate): array
    {
        $query = $this->buildBaseQuery()
            ->whereBetween('SC0011.fec_doc', [$startDate, $endDate])
            ->where('SC0011.tot_doc', '>=', 0)
            ->where('SC0011.nom_pac', '!=', '')
            ->where('SC0011.nom_pac', '!=', 'No existe...')
            ->whereNotIn('SC0002.nom_cia', ['PARTICULAR', 'PACIENTES PARTICULARES']);

        // Deduplicación con ROW_NUMBER (MySQL 8.0+)
        $deduplicatedQuery = DB::connection('mysql_legacy')->table(DB::raw("({$query->toSql()}) as base_query"))
            ->mergeBindings($query)
            ->selectRaw('
                *,
                ROW_NUMBER() OVER (
                    PARTITION BY num_doc
                    ORDER BY
                        fec_fac DESC,
                        CASE
                            WHEN num_fac NOT LIKE "005-%" AND num_fac NOT LIKE "006-%" THEN 0
                            ELSE 1
                        END
                ) as rn
            ');

        $finalQuery = DB::connection('mysql_legacy')->table(DB::raw("({$deduplicatedQuery->toSql()}) as deduplicated"))
            ->mergeBindings($deduplicatedQuery)
            ->where('rn', 1)
            ->select('*')
            ->get();

        return $finalQuery->toArray();
    }

    /**
     * Query base con todos los joins necesarios
     */
    protected function buildBaseQuery()
    {
        return DB::connection('mysql_legacy')
            ->table('SC0011')
            ->leftJoin('SC0017', 'SC0011.num_doc', '=', 'SC0017.num_doc')
            ->leftJoin('SC0022', 'SC0017.num_doc', '=', 'SC0022.num_doc')
            ->leftJoin('SC0033', 'SC0011.num_doc', '=', 'SC0033.num_doc')
            ->leftJoin('SC0006', 'SC0011.cod_ser', '=', 'SC0006.cod_ser')
            ->leftJoinSub(
                'SELECT cod_cia, nom_cia, shipping_period FROM SC0002',
                'SC0002',
                function ($join) {
                    $join->on(DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia');
                }
            )
            ->leftJoinSub(
                'SELECT cod_emp, nom_emp FROM SC0003',
                'SC0003',
                function ($join) {
                    $join->on('SC0011.cod_emp', '=', 'SC0003.cod_emp');
                }
            )
            ->select([
                'SC0011.num_doc as number',
                'SC0011.fec_doc as attendance_date',
                'SC0011.ta_doc as type',
                'SC0011.tot_doc as amount',
                'SC0011.cod_pac as patient_code',
                'SC0011.clos_doc as is_closed',
                'SC0003.nom_emp as company',
                'SC0006.nom_ser as doctor',
                'SC0017.num_fac as invoice_number',
                'SC0017.fec_fac as invoice_date',
                'SC0017.uc_sis as biller',
                'SC0033.fh_dev as devolution_date',
                'SC0002.nom_cia as insurer_name',
                'SC0002.shipping_period',
                'SC0022.num_fac as paid_invoice_number',
                // Campos calculados
                DB::raw('MONTH(SC0011.fec_doc) as month'),
                DB::raw('DATEDIFF(CURDATE(), SC0011.fec_doc) as days_passed'),
                $this->buildStatusCase()
            ]);
    }

    /**
     * CASE para determinar el estado de facturación
     */
    protected function buildStatusCase(): string
    {
        return DB::raw('
            CASE
                WHEN SC0017.num_fac IS NULL
                    OR SC0017.num_fac LIKE "005-%"
                    OR SC0017.num_fac LIKE "006-%"
                THEN "Pendiente"

                WHEN SC0033.fh_dev IS NOT NULL
                    AND SC0022.num_fac IS NULL
                THEN "Devolución"

                WHEN SC0022.num_fac IS NOT NULL
                THEN "Pagado"

                ELSE "Liquidado"
            END as status
        ')->getValue(DB::connection('mysql_legacy')->getQueryGrammar());
    }

    /**
     * Enriquecer admisiones con datos de envíos (MySQL Aplicación)
     */
    public function enrichWithShipments(array $admissions): array
    {
        $invoiceNumbers = collect($admissions)
            ->pluck('invoice_number')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($invoiceNumbers)) {
            return $admissions;
        }

        $shipments = DB::connection('mysql_app')
            ->table('shipments')
            ->whereIn('invoice_number', $invoiceNumbers)
            ->whereNotNull('verified_shipment_date')
            ->get()
            ->keyBy('invoice_number');

        foreach ($admissions as &$admission) {
            if (isset($shipments[$admission->invoice_number])) {
                // Si tiene envío verificado y aún no está clasificado como Pagado/Devolución
                if ($admission->status === 'Liquidado') {
                    $admission->status = 'Enviado';
                }
            }
        }

        return $admissions;
    }

    /**
     * Enriquecer admisiones con datos de auditorías (MySQL Aplicación)
     */
    public function enrichWithAudits(array $admissions): array
    {
        $admissionNumbers = collect($admissions)
            ->pluck('number')
            ->unique()
            ->values()
            ->toArray();

        if (empty($admissionNumbers)) {
            return $admissions;
        }

        $audits = DB::connection('mysql_app')
            ->table('audits')
            ->whereIn('admission_number', $admissionNumbers)
            ->get()
            ->keyBy('admission_number');

        foreach ($admissions as &$admission) {
            $admission->audit = $audits[$admission->number] ?? null;
        }

        return $admissions;
    }
}
```

---

### 4. Service Layer

#### DashboardService.php

```php
<?php

namespace App\Services;

use App\Repositories\AdmissionRepository;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        protected AdmissionRepository $admissionRepository,
        protected AggregationService $aggregationService
    ) {}

    /**
     * Análisis por rango de fechas
     */
    public function getDateRangeAnalysis(string $startDate, string $endDate): array
    {
        // 1. Obtener admisiones deduplicadas
        $admissions = $this->admissionRepository->getUniqueAdmissionsByDateRange(
            $startDate,
            $endDate
        );

        // 2. Enriquecer con datos de envíos (MySQL Aplicación)
        $admissions = $this->admissionRepository->enrichWithShipments($admissions);

        // 3. Generar agregaciones
        $invoiceStatusByMonth = $this->aggregationService->calculateInvoiceStatusByMonth($admissions);
        $insurersByMonth = $this->aggregationService->calculateInsurersByMonth($admissions);
        $paymentStatus = $this->aggregationService->calculatePaymentStatus($admissions);
        $attendanceTypeAnalysis = $this->aggregationService->calculateAttendanceTypeAnalysis($admissions);
        $uniquePatients = $this->aggregationService->calculateUniquePatients($admissions);
        $topCompanies = $this->aggregationService->calculateTopCompanies($admissions);

        return [
            'summary' => [
                'total_admissions' => count($admissions),
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ],
            'invoice_status_by_month' => $invoiceStatusByMonth,
            'insurers_by_month' => $insurersByMonth,
            'payment_status' => $paymentStatus,
            'attendance_type_analysis' => $attendanceTypeAnalysis,
            'unique_patients' => $uniquePatients,
            'top_companies' => $topCompanies,
            'admissions' => $admissions,
        ];
    }

    /**
     * Análisis por periodo
     */
    public function getPeriodAnalysis(string $period): array
    {
        // Convertir periodo YYYYMM a rango de fechas
        $year = substr($period, 0, 4);
        $month = substr($period, 4, 2);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');

        // 1. Obtener admisiones
        $admissions = $this->admissionRepository->getUniqueAdmissionsByDateRange(
            $startDate,
            $endDate
        );

        // 2. Enriquecer con auditorías y envíos
        $admissions = $this->admissionRepository->enrichWithAudits($admissions);
        $admissions = $this->admissionRepository->enrichWithShipments($admissions);

        // 3. Procesar estados de auditores y facturadores
        $admissions = $this->processAuditorsAndBillers($admissions);

        // 4. Generar agregaciones
        $auditorsPerformance = $this->aggregationService->calculateAuditorsPerformance($admissions);
        $billersPerformance = $this->aggregationService->calculateBillersPerformance($admissions);

        return [
            'summary' => [
                'total_admissions' => count($admissions),
                'period' => $period,
                'period_label' => $this->getPeriodLabel($period),
            ],
            'auditors_performance' => $auditorsPerformance,
            'billers_performance' => $billersPerformance,
            'admissions' => $admissions,
        ];
    }

    /**
     * Procesar estados de auditores y facturadores
     */
    protected function processAuditorsAndBillers(array $admissions): array
    {
        foreach ($admissions as &$admission) {
            // Normalizar facturas temporales
            if (preg_match('/^00[56]-/', $admission->invoice_number)) {
                $admission->invoice_number = '';
            }

            // Determinar si es devolución
            $admission->is_devolution = $admission->devolution_date
                && $admission->devolution_invoice_number === $admission->invoice_number;

            // Estado del auditor
            if ($admission->audit) {
                $admission->status_auditor = $admission->paid_invoice_number
                    ? 'PAGADO'
                    : ($admission->is_devolution ? 'DEVOLUCION' : 'AUDITADO');
            }

            // Estado del facturador
            if ($admission->biller && $admission->invoice_number) {
                if ($admission->paid_invoice_number) {
                    $admission->status_biller = 'PAGADO';
                } elseif ($admission->is_devolution) {
                    $admission->status_biller = 'DEVOLUCION';
                } elseif ($admission->verified_shipment_date) {
                    $admission->status_biller = 'ENVIADO';
                } else {
                    $admission->status_biller = 'FACTURADO';
                }
            }
        }

        return $admissions;
    }

    /**
     * Obtener etiqueta del periodo en español
     */
    protected function getPeriodLabel(string $period): string
    {
        $year = substr($period, 0, 4);
        $month = (int)substr($period, 4, 2);

        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return $months[$month] . ' ' . $year;
    }
}
```

#### AggregationService.php

```php
<?php

namespace App\Services;

use Illuminate\Support\Collection;

class AggregationService
{
    protected array $monthsEs = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
    ];

    /**
     * Calcular estado de facturación por mes
     */
    public function calculateInvoiceStatusByMonth(array $admissions): array
    {
        $collection = collect($admissions);

        // Por cantidad
        $byQuantity = $collection->groupBy('month')->map(function ($items, $month) {
            return [
                'invoiced' => $items->where('status', '!=', 'Pendiente')->count(),
                'pending' => $items->where('status', 'Pendiente')->count(),
            ];
        });

        // Por monto
        $byAmount = $collection->groupBy('month')->map(function ($items, $month) {
            return [
                'invoiced' => $items->where('status', '!=', 'Pendiente')->sum('amount'),
                'pending' => $items->where('status', 'Pendiente')->sum('amount'),
            ];
        });

        $months = $collection->pluck('month')->unique()->sort()->map(fn($m) => $this->monthsEs[$m])->values();

        return [
            'view_by_quantity' => [
                'months' => $months->toArray(),
                'invoiced' => $byQuantity->pluck('invoiced')->values()->toArray(),
                'pending' => $byQuantity->pluck('pending')->values()->toArray(),
            ],
            'view_by_amount' => [
                'months' => $months->toArray(),
                'invoiced' => $byAmount->pluck('invoiced')->values()->toArray(),
                'pending' => $byAmount->pluck('pending')->values()->toArray(),
            ],
        ];
    }

    /**
     * Calcular aseguradoras por mes
     */
    public function calculateInsurersByMonth(array $admissions): array
    {
        $collection = collect($admissions);

        // Por cantidad
        $byQuantity = $collection
            ->groupBy(fn($item) => $item->insurer_name . '|' . $item->month)
            ->map(function ($items, $key) {
                [$insurer, $month] = explode('|', $key);
                return [
                    'insurance' => $insurer,
                    'month' => (int)$month,
                    'count' => $items->count(),
                ];
            })
            ->sortBy([['month', 'asc'], ['count', 'desc']])
            ->values();

        // Por monto
        $byAmount = $collection
            ->groupBy(fn($item) => $item->insurer_name . '|' . $item->month)
            ->map(function ($items, $key) {
                [$insurer, $month] = explode('|', $key);
                return [
                    'insurance' => $insurer,
                    'month' => (int)$month,
                    'count' => $items->sum('amount'),
                ];
            })
            ->sortBy([['month', 'asc'], ['count', 'desc']])
            ->values();

        return [
            'view_by_quantity' => $byQuantity->toArray(),
            'view_by_amount' => $byAmount->toArray(),
        ];
    }

    /**
     * Calcular estado de pagos
     */
    public function calculatePaymentStatus(array $admissions): array
    {
        $collection = collect($admissions);

        $validInvoices = $collection->filter(function ($item) {
            return $item->invoice_number
                && !str_starts_with($item->invoice_number, '005-')
                && !str_starts_with($item->invoice_number, '006-');
        });

        return [
            'view_by_quantity' => [
                'paid' => $validInvoices->whereNotNull('paid_invoice_number')->count(),
                'pending' => $validInvoices->whereNull('paid_invoice_number')->count(),
            ],
            'view_by_amount' => [
                'paid' => $validInvoices->whereNotNull('paid_invoice_number')->sum('amount'),
                'pending' => $validInvoices->whereNull('paid_invoice_number')->sum('amount'),
            ],
        ];
    }

    /**
     * Calcular rendimiento de auditores
     */
    public function calculateAuditorsPerformance(array $admissions): array
    {
        $collection = collect($admissions)->whereNotNull('audit');

        $auditors = $collection->pluck('audit.auditor')->unique()->sort()->values();

        $byQuantity = $collection
            ->groupBy(fn($item) => $item->audit->auditor . '|' . $item->status_auditor)
            ->map(function ($items, $key) {
                [$auditor, $status] = explode('|', $key);
                return [
                    'auditor' => $auditor,
                    'status' => $status,
                    'count' => $items->count(),
                ];
            })
            ->sortBy('auditor')
            ->values();

        $byAmount = $collection
            ->groupBy(fn($item) => $item->audit->auditor . '|' . $item->status_auditor)
            ->map(function ($items, $key) {
                [$auditor, $status] = explode('|', $key);
                return [
                    'auditor' => $auditor,
                    'status' => $status,
                    'count' => $items->sum('amount'),
                ];
            })
            ->sortBy('auditor')
            ->values();

        return [
            'auditors_list' => $auditors->toArray(),
            'view_by_quantity' => $byQuantity->toArray(),
            'view_by_amount' => $byAmount->toArray(),
        ];
    }

    /**
     * Calcular rendimiento de facturadores
     */
    public function calculateBillersPerformance(array $admissions): array
    {
        $collection = collect($admissions)
            ->whereNotNull('biller')
            ->filter(fn($item) =>
                $item->invoice_number
                && !str_starts_with($item->invoice_number, '005-')
                && !str_starts_with($item->invoice_number, '006-')
            );

        $billers = $collection->pluck('biller')->unique()->sort()->values();

        $byQuantity = $collection
            ->groupBy(fn($item) => $item->biller . '|' . $item->status_biller)
            ->map(function ($items, $key) {
                [$biller, $status] = explode('|', $key);
                return [
                    'biller' => $biller,
                    'status' => $status,
                    'count' => $items->count(),
                ];
            })
            ->sortBy('biller')
            ->values();

        $byAmount = $collection
            ->groupBy(fn($item) => $item->biller . '|' . $item->status_biller)
            ->map(function ($items, $key) {
                [$biller, $status] = explode('|', $key);
                return [
                    'biller' => $biller,
                    'status' => $status,
                    'count' => $items->sum('amount'),
                ];
            })
            ->sortBy('biller')
            ->values();

        return [
            'billers_list' => $billers->toArray(),
            'view_by_quantity' => $byQuantity->toArray(),
            'view_by_amount' => $byAmount->toArray(),
        ];
    }

    /**
     * Calcular análisis por tipo de atención
     */
    public function calculateAttendanceTypeAnalysis(array $admissions): array
    {
        $collection = collect($admissions);
        $totalAdmissions = $collection->count();
        $totalAmount = $collection->sum('amount');

        // Por cantidad
        $byQuantity = $collection
            ->groupBy('type')
            ->map(function ($items, $type) use ($totalAdmissions) {
                $count = $items->count();
                return [
                    'type' => $type,
                    'count' => $count,
                    'percentage' => round(($count * 100) / $totalAdmissions, 2),
                ];
            })
            ->sortByDesc('count')
            ->values();

        // Por monto con promedio
        $byAmount = $collection
            ->groupBy('type')
            ->map(function ($items, $type) use ($totalAmount) {
                $amount = $items->sum('amount');
                return [
                    'type' => $type,
                    'amount' => round($amount, 2),
                    'average' => round($items->avg('amount'), 2),
                    'percentage' => round(($amount * 100) / $totalAmount, 2),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        return [
            'view_by_quantity' => $byQuantity->toArray(),
            'view_by_amount' => $byAmount->toArray(),
        ];
    }

    /**
     * Calcular pacientes únicos
     */
    public function calculateUniquePatients(array $admissions): array
    {
        $collection = collect($admissions);
        $totalAdmissions = $collection->count();
        $uniquePatients = $collection->pluck('patient_code')->unique()->count();

        return [
            'total' => $uniquePatients,
            'percentage_of_admissions' => round(($uniquePatients * 100) / $totalAdmissions, 2),
        ];
    }

    /**
     * Calcular top 10 empresas
     */
    public function calculateTopCompanies(array $admissions, int $limit = 10): array
    {
        $collection = collect($admissions);
        $totalAdmissions = $collection->count();
        $totalAmount = $collection->sum('amount');

        // Top por cantidad
        $byQuantity = $collection
            ->groupBy('company')
            ->map(function ($items, $company) use ($totalAdmissions) {
                $count = $items->count();
                return [
                    'company' => $company,
                    'count' => $count,
                    'percentage' => round(($count * 100) / $totalAdmissions, 2),
                ];
            })
            ->sortByDesc('count')
            ->take($limit)
            ->values();

        // Top por monto
        $byAmount = $collection
            ->groupBy('company')
            ->map(function ($items, $company) use ($totalAmount) {
                $amount = $items->sum('amount');
                return [
                    'company' => $company,
                    'amount' => round($amount, 2),
                    'percentage' => round(($amount * 100) / $totalAmount, 2),
                ];
            })
            ->sortByDesc('amount')
            ->take($limit)
            ->values();

        return [
            'view_by_quantity' => $byQuantity->toArray(),
            'view_by_amount' => $byAmount->toArray(),
        ];
    }
}
```

---

### 5. Controller

#### DashboardController.php

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DateRangeAnalysisRequest;
use App\Http\Requests\PeriodAnalysisRequest;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Análisis por rango de fechas
     */
    public function dateRangeAnalysis(DateRangeAnalysisRequest $request): JsonResponse
    {
        try {
            $startDate = $request->validated('start_date');
            $endDate = $request->validated('end_date');

            // Cachear por 10 minutos
            $cacheKey = "dashboard:date_range:{$startDate}:{$endDate}";

            $data = Cache::remember($cacheKey, 600, function () use ($startDate, $endDate) {
                return $this->dashboardService->getDateRangeAnalysis($startDate, $endDate);
            });

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Error al procesar la solicitud',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ]
            ], 500);
        }
    }

    /**
     * Análisis por periodo
     */
    public function periodAnalysis(PeriodAnalysisRequest $request): JsonResponse
    {
        try {
            $period = $request->validated('period');

            $cacheKey = "dashboard:period:{$period}";

            $data = Cache::remember($cacheKey, 600, function () use ($period) {
                return $this->dashboardService->getPeriodAnalysis($period);
            });

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Error al procesar la solicitud',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ]
            ], 500);
        }
    }
}
```

---

### 6. Configuración de Base de Datos

**Arquitectura de Dos Bases de Datos MySQL:**

1. **`mysql_legacy`**: Base de datos legada con tablas SC00XX. Sin modelos Eloquent, acceso directo via Query Builder.
2. **`mysql_app`**: Base de datos de la aplicación moderna con tablas `admissions_lists`, `audits`, `shipments`. Usa modelos Eloquent.

#### config/database.php

```php
'connections' => [
    // Base de datos legada - Tablas SC00XX (sin modelos Eloquent)
    'mysql_legacy' => [
        'driver' => 'mysql',
        'host' => env('DB_LEGACY_HOST', '127.0.0.1'),
        'port' => env('DB_LEGACY_PORT', '3306'),
        'database' => env('DB_LEGACY_DATABASE', 'forge'),
        'username' => env('DB_LEGACY_USERNAME', 'forge'),
        'password' => env('DB_LEGACY_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
        'options' => [
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ],
    ],

    // Base de datos aplicación - Tablas modernas (con modelos Eloquent)
    'mysql_app' => [
        'driver' => 'mysql',
        'host' => env('DB_APP_HOST', '127.0.0.1'),
        'port' => env('DB_APP_PORT', '3306'),
        'database' => env('DB_APP_DATABASE', 'forge'),
        'username' => env('DB_APP_USERNAME', 'forge'),
        'password' => env('DB_APP_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ],
],
```

#### .env

```env
# MySQL Legado (Tablas SC00XX)
DB_CONNECTION=mysql_legacy
DB_LEGACY_HOST=127.0.0.1
DB_LEGACY_PORT=3306
DB_LEGACY_DATABASE=clinica_legacy
DB_LEGACY_USERNAME=root
DB_LEGACY_PASSWORD=secret

# MySQL Aplicación (Tablas modernas: admissions_lists, audits, shipments)
DB_APP_HOST=127.0.0.1
DB_APP_PORT=3306
DB_APP_DATABASE=clinica_app
DB_APP_USERNAME=root
DB_APP_PASSWORD=secret

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## Testing

### Feature Test

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    /**
     * Test análisis por rango de fechas
     */
    public function test_date_range_analysis_returns_valid_structure(): void
    {
        $response = $this->postJson('/api/dashboard/date-range-analysis', [
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'summary' => [
                    'total_admissions',
                    'period' => ['start', 'end'],
                ],
                'invoice_status_by_month' => [
                    'view_by_quantity' => ['months', 'invoiced', 'pending'],
                    'view_by_amount' => ['months', 'invoiced', 'pending'],
                ],
                'insurers_by_month',
                'payment_status',
                'admissions',
            ]);
    }

    /**
     * Test validación de fechas inválidas
     */
    public function test_date_range_validation_fails_with_invalid_dates(): void
    {
        $response = $this->postJson('/api/dashboard/date-range-analysis', [
            'start_date' => '2025-01-31',
            'end_date' => '2025-01-01', // Fecha fin antes que inicio
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    /**
     * Test análisis por periodo
     */
    public function test_period_analysis_returns_valid_structure(): void
    {
        $response = $this->postJson('/api/dashboard/period-analysis', [
            'period' => '202501',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'summary' => [
                    'total_admissions',
                    'period',
                    'period_label',
                ],
                'auditors_performance',
                'billers_performance',
                'admissions',
            ]);
    }

    /**
     * Test validación de periodo inválido
     */
    public function test_period_validation_fails_with_invalid_format(): void
    {
        $response = $this->postJson('/api/dashboard/period-analysis', [
            'period' => '202513', // Mes inválido
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }
}
```

### Unit Test

```php
<?php

namespace Tests\Unit;

use App\Services\AggregationService;
use PHPUnit\Framework\TestCase;

class AggregationServiceTest extends TestCase
{
    protected AggregationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AggregationService();
    }

    /**
     * Test cálculo de estado de facturación
     */
    public function test_calculate_invoice_status_by_month(): void
    {
        $admissions = [
            (object)['month' => 1, 'status' => 'Pagado', 'amount' => 100.00],
            (object)['month' => 1, 'status' => 'Pendiente', 'amount' => 50.00],
            (object)['month' => 2, 'status' => 'Pagado', 'amount' => 200.00],
        ];

        $result = $this->service->calculateInvoiceStatusByMonth($admissions);

        $this->assertArrayHasKey('view_by_quantity', $result);
        $this->assertArrayHasKey('view_by_amount', $result);
        $this->assertEquals(['Ene', 'Feb'], $result['view_by_quantity']['months']);
    }
}
```

---

## Optimizaciones

### 1. Índices de Base de Datos

```sql
-- MySQL
CREATE INDEX idx_admissions_date ON SC0011(fec_doc);
CREATE INDEX idx_admissions_number ON SC0011(num_doc);
CREATE INDEX idx_invoices_composite ON SC0017(num_doc, fec_fac DESC);
CREATE INDEX idx_invoices_paid_number ON SC0022(num_doc);
CREATE INDEX idx_devolutions_number ON SC0033(num_doc);

-- MySQL Aplicación
CREATE INDEX idx_shipments_invoice ON shipments(invoice_number);
CREATE INDEX idx_shipments_verified ON shipments(verified_shipment_date);
CREATE INDEX idx_audits_admission ON audits(admission_number);
```

### 2. Caché de Redis

```php
// Cachear periodos de envío de aseguradoras (cambian raramente)
Cache::remember('insurers:shipping_periods', 3600, function () {
    return DB::connection('mysql_legacy')
        ->table('SC0002')
        ->pluck('shipping_period', 'nom_cia');
});
```

### 3. Query Optimization

-   Usar `SELECT` específicos en lugar de `SELECT *`
-   Limitar joins solo a columnas necesarias
-   Usar `EXPLAIN` para analizar queries lentas
-   Considerar vistas materializadas para agregaciones frecuentes

### 4. Paginación para Admissions

```php
// En DashboardService.php
public function getDateRangeAnalysis(string $startDate, string $endDate, bool $includeAdmissions = false): array
{
    // ... código anterior ...

    return [
        'summary' => [...],
        'invoice_status_by_month' => [...],
        'admissions' => $includeAdmissions ? array_slice($admissions, 0, 1000) : [],
    ];
}
```

---

## Comandos Artisan Útiles

```bash
# Generar clases
php artisan make:controller Api/DashboardController --api
php artisan make:request DateRangeAnalysisRequest
php artisan make:model Admission
php artisan make:test DashboardTest

# Cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# Testing
php artisan test --filter DashboardTest
php artisan test --coverage
```

---

## Migración Frontend

Una vez implementados los endpoints, el frontend eliminará:

1. ✅ `useDashboardDataProcessing.js`
2. ✅ Lógica de procesamiento en `useDashboardAdmissions.js`
3. ✅ Lógica de procesamiento en `useDashboardPeriod.js`

**Llamadas simplificadas**:

```javascript
// Antes (frontend procesaba todo)
const data = await fetchAdmissionsDateRangeApiDashboard(payload);
processAdmissions(data);
updateDateRangeCharts();

// Después (backend retorna todo procesado)
const { data } = await axios.post('/api/dashboard/date-range-analysis', payload);
chartData.value = data.invoice_status_by_month;
```

---

---

## Resumen de Endpoints: Actual vs Propuesto

### 📌 Tabla Comparativa

| Aspecto                          | Endpoints Actuales                                                  | Endpoints Propuestos                      |
| -------------------------------- | ------------------------------------------------------------------- | ----------------------------------------- |
| **Cantidad de requests**         | 2-3 requests por vista                                              | 1 request por vista                       |
| **Análisis por rango de fechas** | `POST /excequte_query`                                              | `POST /api/dashboard/date-range-analysis` |
| **Análisis por periodo**         | `GET /admissions-lists/by-period/{period}` + `POST /excequte_query` | `POST /api/dashboard/period-analysis`     |
| **Procesamiento**                | Frontend (JavaScript)                                               | Backend (Laravel)                         |
| **Deduplicación**                | Frontend con Map/Array                                              | Backend con `ROW_NUMBER()`                |
| **Agregaciones**                 | 6 cálculos en frontend                                              | 6 cálculos en backend                     |
| **Join cross-database**          | Frontend mezcla datos                                               | Backend con Eloquent multi-connection     |
| **Cálculos de estado**           | Frontend con if/else                                                | Backend con `CASE WHEN`                   |
| **Caché**                        | No implementado                                                     | Redis 10 minutos                          |
| **Validación**                   | Frontend manual                                                     | Form Requests Laravel                     |
| **Testing**                      | No estructurado                                                     | PHPUnit feature/unit tests                |

---

### 🔄 Mapa de Migración

#### Endpoint 1: Rango de Fechas

**ANTES**:

```javascript
// Frontend: src/composables/useDashboardAdmissions.js
const response = await FastApiService.admisionsByRangeDashboard({
    start_date: '01-15-2025',
    end_date: '01-31-2025'
});

// Backend: POST /excequte_query
// Retorna: Array de admisiones raw (1000+ registros)

// Frontend procesa:
const uniqueAdmissions = getUniqueAdmissions(response.data);
const enrichedAdmissions = enrichAdmissions(uniqueAdmissions);
const chartData = calculateAggregations(enrichedAdmissions);
```

**DESPUÉS**:

```javascript
// Frontend: src/composables/useDashboardAdmissions.js
const { data } = await axios.post('/api/dashboard/date-range-analysis', {
    start_date: '2025-01-15',
    end_date: '2025-01-31'
});

// Backend: POST /api/dashboard/date-range-analysis
// Retorna: { summary, invoice_status_by_month, insurers_by_month, payment_status, admissions }

// Frontend solo mapea:
chartData.value = data.invoice_status_by_month;
```

---

#### Endpoint 2: Análisis por Periodo

**ANTES**:

```javascript
// Frontend: src/stores/admissionsListsStore.js

// PASO 1: Obtener lista de admisiones
const { data: admissionsList } = await fetchAdmissionsListsByPeriod('202501');
// Backend: GET /admissions-lists/by-period/202501
// Retorna: [{ admission_number, period, biller, auditor }]

// PASO 2: Extraer números
const numbers = admissionsList.map((item) => item.admission_number);

// PASO 3: Obtener datos completos de MySQL
const { results } = await FastApiService.admisionsByNumbers(numbers);
// Backend: POST /excequte_query con IN clause
// Retorna: Array de admisiones completas

// PASO 4: Mezclar datos manualmente
const combined = results.map((admission) => ({
    ...admission,
    ...admissionsList.find((list) => list.admission_number === admission.number)
}));

// PASO 5: Procesar y agregar
const processed = processAuditorsAndBillers(combined);
const auditorsData = calculateAuditorsPerformance(processed);
```

**DESPUÉS**:

```javascript
// Frontend: src/composables/useDashboardPeriod.js

const { data } = await axios.post('/api/dashboard/period-analysis', {
    period: '202501'
});

// Backend: POST /api/dashboard/period-analysis
// Retorna: {
//   summary,
//   auditors_performance: { auditors_list, view_by_quantity, view_by_amount },
//   billers_performance: { billers_list, view_by_quantity, view_by_amount },
//   admissions
// }

// Frontend solo mapea:
auditorsData.value = data.auditors_performance;
billersData.value = data.billers_performance;
```

---

### 📊 Métricas de Mejora Esperadas

| Métrica                       | Actual      | Propuesto       | Mejora        |
| ----------------------------- | ----------- | --------------- | ------------- |
| **Requests HTTP**             | 2-3         | 1               | -66%          |
| **Tiempo de carga**           | ~3-5s       | ~1-2s           | -60%          |
| **Tamaño de respuesta**       | 500KB+ raw  | 200KB procesado | -60%          |
| **Procesamiento frontend**    | ~2000ms     | ~50ms           | -97%          |
| **Líneas de código frontend** | ~800 líneas | ~100 líneas     | -87%          |
| **Complejidad ciclomática**   | Alto        | Bajo            | Significativa |
| **Mantenibilidad**            | Difícil     | Fácil           | Muy mejorada  |

---

### 📊 Reportes Adicionales Incluidos

#### 1. **Análisis por Tipo de Atención**

**Descripción**: Muestra la distribución de atenciones médicas por tipo (EMERGENCIA, CONSULTA, CIRUGÍA, etc.)

**Campos incluidos**:
- **view_by_quantity**: Cantidad de atenciones por tipo con porcentaje
- **view_by_amount**: Monto total y promedio por tipo con porcentaje

**Casos de uso**:
- Identificar qué tipos de atención son más frecuentes
- Analizar el costo promedio por tipo de atención
- Planificar recursos según demanda por tipo

**Ejemplo de salida**:
```json
{
  "attendance_type_analysis": {
    "view_by_quantity": [
      {
        "type": "CONSULTA",
        "count": 200,
        "percentage": 44.44
      },
      {
        "type": "EMERGENCIA",
        "count": 150,
        "percentage": 33.33
      }
    ],
    "view_by_amount": [
      {
        "type": "EMERGENCIA",
        "amount": 45000.50,
        "average": 300.00,
        "percentage": 35.00
      }
    ]
  }
}
```

---

#### 2. **Pacientes Únicos**

**Descripción**: Calcula cuántos pacientes diferentes fueron atendidos en el periodo

**Campos incluidos**:
- **total**: Número de pacientes únicos (basado en `cod_pac`)
- **percentage_of_admissions**: Porcentaje de pacientes únicos vs total de atenciones

**Casos de uso**:
- Identificar pacientes recurrentes
- Calcular tasa de reincidencia
- Análisis de fidelización de pacientes

**Ejemplo de salida**:
```json
{
  "unique_patients": {
    "total": 380,
    "percentage_of_admissions": 84.44
  }
}
```

**Interpretación**: Si hay 450 admisiones pero solo 380 pacientes únicos, significa que algunos pacientes tuvieron múltiples atenciones (18.44% de reincidencia).

---

#### 3. **Top 10 Empresas**

**Descripción**: Ranking de las 10 empresas con mayor volumen de atenciones y montos

**Campos incluidos**:
- **view_by_quantity**: Top 10 empresas por cantidad de atenciones
- **view_by_amount**: Top 10 empresas por monto total facturado

**Casos de uso**:
- Identificar principales clientes corporativos
- Priorizar relaciones con empresas de mayor volumen
- Análisis de concentración de ingresos

**Ejemplo de salida**:
```json
{
  "top_companies": {
    "view_by_quantity": [
      {
        "company": "EPS MAPFRE SALUD",
        "count": 85,
        "percentage": 18.89
      },
      {
        "company": "PACIFICO SALUD EPS",
        "count": 67,
        "percentage": 14.89
      }
    ],
    "view_by_amount": [
      {
        "company": "EPS MAPFRE SALUD",
        "amount": 28500.75,
        "percentage": 22.18
      }
    ]
  }
}
```

---

### 🔍 Campos Adicionales Requeridos

Para soportar estos nuevos reportes, se agregaron los siguientes campos al query base:

| Campo | Tabla | Alias | Propósito |
|-------|-------|-------|-----------|
| `cod_pac` | SC0011 | `patient_code` | Identificar pacientes únicos |
| `nom_emp` | SC0003 | `company` | Nombre de la empresa/EPS |
| `ta_doc` | SC0011 | `type` | Tipo de atención (ya existía) |

**Join adicional**:
```php
->leftJoinSub(
    'SELECT cod_emp, nom_emp FROM SC0003',
    'SC0003',
    function ($join) {
        $join->on('SC0011.cod_emp', '=', 'SC0003.cod_emp');
    }
)
```

### 📈 Resumen de Todos los Reportes del Endpoint

**Endpoint**: `POST /api/dashboard/date-range-analysis`

| # | Reporte | Descripción | Vistas |
|---|---------|-------------|--------|
| 1 | **Summary** | Totales generales del periodo | 1 objeto |
| 2 | **Invoice Status by Month** | Estado de facturación por mes | 2 vistas (cantidad/monto) |
| 3 | **Insurers by Month** | Distribución por aseguradora y mes | 2 vistas (cantidad/monto) |
| 4 | **Payment Status** | Estado de pago de facturas | 2 vistas (cantidad/monto) |
| 5 | **Attendance Type Analysis** ⭐ NUEVO | Análisis por tipo de atención | 2 vistas (cantidad/monto+promedio) |
| 6 | **Unique Patients** ⭐ NUEVO | Pacientes únicos atendidos | 1 objeto |
| 7 | **Top Companies** ⭐ NUEVO | Top 10 empresas | 2 vistas (cantidad/monto) |
| 8 | **Admissions** | Lista completa de admisiones | Array de objetos |

**Total**: 8 reportes en un solo endpoint

---

### 📊 Visualización de Datos Recomendada

Para el frontend, se recomienda visualizar estos datos de la siguiente manera:

#### Gráfico 1: Estado de Facturación por Mes
- **Tipo**: Gráfico de barras apiladas
- **Eje X**: Meses
- **Eje Y**: Cantidad o Monto
- **Series**: Facturado, Pendiente

#### Gráfico 2: Aseguradoras por Mes
- **Tipo**: Gráfico de barras agrupadas
- **Eje X**: Meses
- **Eje Y**: Cantidad o Monto
- **Series**: Una por cada aseguradora

#### Gráfico 3: Estado de Pago
- **Tipo**: Gráfico de pastel (pie chart)
- **Segmentos**: Pagado, Pendiente de pago

#### Gráfico 4: Análisis por Tipo de Atención ⭐ NUEVO
- **Tipo**: Gráfico de barras horizontales con promedio
- **Eje X**: Cantidad/Monto
- **Eje Y**: Tipos de atención
- **Adicional**: Mostrar promedio por tipo

#### Gráfico 5: Top 10 Empresas ⭐ NUEVO
- **Tipo**: Gráfico de barras horizontales
- **Eje X**: Cantidad/Monto
- **Eje Y**: Nombres de empresas
- **Orden**: De mayor a menor

#### Card/Badge 6: Pacientes Únicos ⭐ NUEVO
- **Tipo**: Card con icono
- **Mostrar**: Total de pacientes únicos
- **Subtítulo**: Porcentaje respecto a admisiones

---

---

### 🎯 Beneficios Clave de la Migración

1. **Performance**

    - Reducción del 60% en tiempo de carga
    - Menos datos transferidos por la red
    - Queries optimizados con índices

2. **Mantenimiento**

    - Lógica centralizada en el backend
    - Testing estructurado (PHPUnit)
    - Menos duplicación de código

3. **Escalabilidad**

    - Caché de Redis implementado
    - Queries optimizados con window functions
    - Preparado para futuros reportes

4. **Seguridad**

    - Validación robusta con Form Requests
    - No se expone estructura de BD al frontend
    - Sanitización de inputs automática

5. **Developer Experience**
    - Código frontend más simple
    - Debugging más fácil
    - Documentación clara de contratos API

---

## Documentación Adicional

**Versión**: 2.0 - Laravel 12 Implementation
**Autor**: Backend Team
**Fecha**: 2025-01-19

**Referencias**:

-   [Laravel 12 Docs](https://laravel.com/docs/12.x)
-   [MySQL 8.0 Window Functions](https://dev.mysql.com/doc/refman/8.0/en/window-functions.html)
-   [Eloquent Multi-Database](https://laravel.com/docs/12.x/database#using-multiple-database-connections)

**Contacto**:

-   Para dudas de implementación: Backend Team
-   Para dudas de integración: Frontend Team
-   Repositorio del proyecto: [GitHub/csr_frontend_seguros](./)
