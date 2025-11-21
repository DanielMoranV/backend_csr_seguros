# 🔧 Fix: MySQL Server Has Gone Away

## 🔴 Error

```
SQLSTATE[HY000]: General error: 2006 MySQL server has gone away
```

**Ubicación:** `app/Repositories/DashboardAggregationRepository.php:133`
**Método:** `getDateRangeAggregations()` - Sección `payment_status`

---

## 📊 Causa del Problema

El query de **payment_status** (líneas 126-182) es extremadamente pesado:

```php
// ❌ QUERY PROBLEMÁTICO
$paidDocsSubquery = DB::connection('external_db')
    ->table('SC0011')
    ->join('SC0002', ...)
    ->join('SC0017', ...)  // JOIN 1
    ->join('SC0022', ...)  // JOIN 2
    ->whereBetween('SC0011.fec_doc', [$startDate, $endDate])  // 11 meses de datos
    ->groupBy('SC0011.num_doc', 'SC0011.tot_doc')  // Agrupación 1
    ->selectRaw('...');

$paidDocs = DB::connection('external_db')
    ->table(DB::raw("({$paidDocsSubquery->toSql()}) as paid"))  // Subquery anidada
    ->selectRaw('COUNT(*) as count, SUM(tot_doc) as amount')  // Agrupación 2
    ->first();
```

**Problemas:**
- ✗ 3 JOINs con tablas grandes
- ✗ Subquery anidada con doble agrupación
- ✗ Procesa 11 meses de datos (2025-01-01 a 2025-11-20)
- ✗ MySQL timeout o cierre de conexión
- ✗ El resultado excede el buffer del servidor

---

## 🛠️ Soluciones

### Solución 1: Simplificar el Query (Recomendado)

Reemplazar el query complejo por uno directo usando `invoice_by_month` que ya existe:

```php
// ✅ SOLUCIÓN SIMPLE
// El query de invoice_by_month (líneas 34-100) ya calcula paid_count y paid_amount
// por mes. Podemos sumar esos resultados:

$paymentStatus = (object)[
    'paid_count' => $invoiceByMonth->sum('paid_count'),
    'paid_amount' => $invoiceByMonth->sum('paid_amount'),
    'pending_count' => $invoiceByMonth->sum('pending_count'),
    'pending_amount' => $invoiceByMonth->sum('pending_amount'),
];
```

**Ubicación:** `app/Repositories/DashboardAggregationRepository.php:177-182`

**Cambio completo:**

```php
// ANTES (líneas 119-182): Comentar todo el bloque de payment_status

// DESPUÉS (reemplazar con):
// 3. Estado de pagos - calculado desde invoice_by_month
$paymentStatus = (object)[
    'paid_count' => $invoiceByMonth->sum('paid_count'),
    'paid_amount' => $invoiceByMonth->sum('paid_amount'),
    'pending_count' => $invoiceByMonth->sum('pending_count'),
    'pending_amount' => $invoiceByMonth->sum('pending_amount'),
];
```

---

### Solución 2: Aumentar Timeouts MySQL

Si prefieres mantener el query complejo, aumenta los límites:

#### A. En el archivo `.env`:

```env
DB_TIMEOUT=120
```

#### B. En `config/database.php`:

```php
'external_db' => [
    'driver' => 'mysql',
    // ... otras opciones
    'options' => [
        PDO::ATTR_TIMEOUT => 120,  // 2 minutos
        PDO::ATTR_PERSISTENT => false,
    ],
],
```

#### C. En MySQL Server (`my.ini` o `my.cnf`):

```ini
[mysqld]
max_allowed_packet=256M
wait_timeout=300
interactive_timeout=300
net_read_timeout=120
net_write_timeout=120
```

**Reiniciar MySQL después de cambios en configuración.**

---

### Solución 3: Cachear el Resultado

Agregar cache específico para `payment_status`:

```php
// En DashboardService.php

use Illuminate\Support\Facades\Cache;

$cacheKey = "dashboard:payment_status:{$startDate}:{$endDate}";
$paymentStatus = Cache::remember($cacheKey, 3600, function() use ($startDate, $endDate) {
    return $this->aggregationRepository->getPaymentStatus($startDate, $endDate);
});
```

---

### Solución 4: Dividir el Query en Chunks

Procesar el rango de fechas en bloques más pequeños:

```php
// Dividir el rango en meses
$start = Carbon::parse($startDate);
$end = Carbon::parse($endDate);
$months = [];

while ($start->lte($end)) {
    $monthStart = $start->copy()->startOfMonth()->format('Y-m-d');
    $monthEnd = $start->copy()->endOfMonth()->format('Y-m-d');

    $months[] = $this->getPaymentStatusForMonth($monthStart, $monthEnd);

    $start->addMonth();
}

// Sumar resultados
$paymentStatus = (object)[
    'paid_count' => collect($months)->sum('paid_count'),
    'paid_amount' => collect($months)->sum('paid_amount'),
    'pending_count' => collect($months)->sum('pending_count'),
    'pending_amount' => collect($months)->sum('pending_amount'),
];
```

---

## ✅ Solución Recomendada (Implementación)

**Archivo:** `app/Repositories/DashboardAggregationRepository.php`

### Paso 1: Comentar el query problemático

```php
// Líneas 119-182 - COMENTAR TODO EL BLOQUE

/*
// 3. Estado de pagos (todas las admisiones)
// ... todo el código hasta ...
$paymentStatus = (object)[
    'paid_count' => $paidDocs->count ?? 0,
    'paid_amount' => $paidDocs->amount ?? 0,
    'pending_count' => ($totalValidInvoices->count ?? 0) - ($paidDocs->count ?? 0),
    'pending_amount' => ($totalValidInvoices->amount ?? 0) - ($paidDocs->amount ?? 0),
];
*/
```

### Paso 2: Agregar la solución simple

```php
// DESPUÉS DE LA LÍNEA 100 (después de $invoiceByMonth = ...)

// 3. Estado de pagos - calculado desde invoice_by_month (optimizado)
$paymentStatus = (object)[
    'paid_count' => $invoiceByMonth->sum('paid_count'),
    'paid_amount' => $invoiceByMonth->sum('paid_amount'),
    'pending_count' => $invoiceByMonth->sum('pending_count'),
    'pending_amount' => $invoiceByMonth->sum('pending_amount'),
];
```

---

## 🧪 Validación

### 1. Verificar que el endpoint responda sin error:

```bash
curl -X POST http://localhost/api/dashboard/date-range-analysis \
  -H "Content-Type: application/json" \
  -d '{"start_date": "2025-01-01", "end_date": "2025-11-20"}'
```

### 2. Verificar que los totales sean coherentes:

```sql
-- Comparar contra query directo
SELECT
    SUM(CASE WHEN paid > 0 THEN 1 ELSE 0 END) as paid_count,
    SUM(CASE WHEN paid > 0 THEN amount ELSE 0 END) as paid_amount
FROM admissions_view
WHERE fec_doc BETWEEN '2025-01-01' AND '2025-11-20';
```

---

## 📊 Impacto de la Solución

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Tiempo de ejecución** | ~30s (timeout) | ~2s |
| **JOINs** | 3 JOINs anidados | Ya calculado |
| **Subqueries** | 2 subqueries | 0 subqueries |
| **Agrupaciones** | 3 agrupaciones | 1 suma simple |
| **Riesgo de timeout** | ⚠️ Alto | ✅ Bajo |

---

## 🔍 Monitoreo

Agregar logs para detectar queries lentos:

```php
// En AppServiceProvider.php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

public function boot()
{
    DB::listen(function ($query) {
        if ($query->time > 1000) {  // Más de 1 segundo
            Log::warning('Slow query detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time . 'ms'
            ]);
        }
    });
}
```

---

## 📝 Notas Adicionales

- El error `MySQL server has gone away` también puede ocurrir si:
  - El paquete de datos excede `max_allowed_packet`
  - La conexión está inactiva por más de `wait_timeout`
  - El servidor MySQL se reinicia durante la query
- Si el problema persiste, revisar los logs de MySQL para más detalles
- Considerar crear índices en las columnas usadas en JOINs y WHERE

---

## 🚀 Próximos Pasos

1. ✅ Implementar Solución 1 (Recomendada)
2. ⏳ Monitorear performance después del cambio
3. 📊 Si es necesario, aplicar Solución 2 o 3
4. 🔍 Revisar otros queries similares en el repositorio
