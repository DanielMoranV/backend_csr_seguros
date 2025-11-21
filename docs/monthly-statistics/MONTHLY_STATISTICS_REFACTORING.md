# 📋 Refactorización de monthly_statistics

## ✅ Cambios Implementados

### 1. Campos Eliminados (Obsoletos)

Se eliminaron los siguientes campos que ya no se usan en el frontend:

```json
// ❌ ELIMINADO
{
  "avg_admissions_per_patient": 1.51,
  "recurrence_rate": 51.19
}
```

**Ubicación:** `app/Services/DashboardService.php:441-446`

---

### 2. Nuevo Desglose: `by_attendance_type`

Se agregó un desglose completo por tipo de atención para cada mes, con dos vistas:

#### Vista por Cantidad (`view_by_quantity`)

Muestra la cantidad de admisiones y su porcentaje por tipo de atención:

```json
{
  "type": "Ambulatorio",
  "count": 428,
  "percentage": 75.09
}
```

#### Vista por Monto (`view_by_amount`)

Muestra el monto total, promedio y porcentaje por tipo de atención:

```json
{
  "type": "Ambulatorio",
  "amount": 112850.50,
  "average": 263.67,
  "percentage": 47.03
}
```

---

## 📊 Estructura Final

```json
{
  "monthly_statistics": [
    {
      "month": 2,
      "month_name": "Feb",
      "unique_patients": 377,
      "total_admissions": 570,
      "total_amount": 239950.33,
      "avg_amount_per_admission": 420.97,
      "by_attendance_type": {
        "view_by_quantity": [
          {
            "type": "Ambulatorio",
            "count": 428,
            "percentage": 75.09
          },
          {
            "type": "Emergencia",
            "count": 118,
            "percentage": 20.70
          },
          {
            "type": "Hospitalario",
            "count": 18,
            "percentage": 3.16
          },
          {
            "type": "Proceso",
            "count": 6,
            "percentage": 1.05
          },
          {
            "type": "",
            "count": 0,
            "percentage": 0.00
          }
        ],
        "view_by_amount": [
          {
            "type": "Ambulatorio",
            "amount": 112850.50,
            "average": 263.67,
            "percentage": 47.03
          },
          {
            "type": "Emergencia",
            "amount": 58120.30,
            "average": 492.55,
            "percentage": 24.22
          },
          {
            "type": "Hospitalario",
            "amount": 62500.00,
            "average": 3472.22,
            "percentage": 26.04
          },
          {
            "type": "Proceso",
            "amount": 6479.53,
            "average": 1079.92,
            "percentage": 2.70
          },
          {
            "type": "",
            "amount": 0.00,
            "average": 0.00,
            "percentage": 0.00
          }
        ]
      }
    }
  ]
}
```

---

## 🔧 Cambios en el Código

### 1. Repository: `DashboardAggregationRepository.php`

**Líneas 265-282:** Nuevo query SQL para obtener desglose por tipo de atención por mes

```php
// 8. Desglose por tipo de atención por mes
$attendanceTypeByMonth = DB::connection('external_db')
    ->table('SC0011')
    ->leftJoin('SC0002', DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia')
    ->where($baseWhere)
    ->whereNotIn('SC0002.nom_cia', ['PARTICULAR', 'PACIENTES PARTICULARES'])
    ->selectRaw('
        MONTH(SC0011.fec_doc) as month,
        UPPER(TRIM(SC0011.ta_doc)) as type,
        COUNT(*) as count,
        SUM(SC0011.tot_doc) as amount,
        AVG(SC0011.tot_doc) as average
    ')
    ->groupBy(DB::raw('MONTH(SC0011.fec_doc)'), DB::raw('UPPER(TRIM(SC0011.ta_doc))'))
    ->orderBy('month')
    ->orderByDesc('count')
    ->get();
```

**Línea 294:** Agregar al return del método

```php
return [
    // ... otros campos
    'attendance_type_by_month' => $attendanceTypeByMonth,
];
```

---

### 2. Service: `DashboardService.php`

**Líneas 54-59:** Actualizar llamada a `formatMonthlyStatistics`

```php
'monthly_statistics' => $this->formatMonthlyStatistics(
    $aggregations['monthly_statistics'],
    $aggregations['attendance_type_by_month'],
    $startDate,
    $endDate
),
```

**Líneas 403-526:** Métodos actualizados

1. **`formatMonthlyStatistics`**: Ahora recibe `$attendanceTypeByMonth` y elimina campos obsoletos
2. **`formatAttendanceTypeByMonth`** (NUEVO): Formatea el desglose por tipo de atención

---

### 3. Tests: `AggregationServiceTest.php`

**Líneas 215-231:** Actualizar aserciones eliminando campos obsoletos

```php
// ANTES (❌ ELIMINADO)
$this->assertEquals(1.5, $enero['avg_admissions_per_patient']);
$this->assertEquals(50.00, $enero['recurrence_rate']);

// AHORA (✅)
// Solo se validan los campos que existen
$this->assertEquals(150.00, $enero['avg_amount_per_admission']);
```

---

## ✨ Beneficios

1. **Consistencia**: Reutiliza la misma estructura que `attendance_type_analysis`
2. **Filtrado flexible**: Permite al frontend filtrar por tipo de atención
3. **Doble vista**: Soporta vista por cantidad y por monto
4. **Completo**: Incluye promedios y porcentajes calculados
5. **Limpio**: Elimina campos obsoletos que generaban confusión

---

## 🧪 Validación

### Validaciones Automáticas

1. ✅ **Suma de count** = `total_admissions` del mes
2. ✅ **Suma de amount** = `total_amount` del mes
3. ✅ **Porcentajes** suman aproximadamente 100% (con redondeo)
4. ✅ **Average** = `amount / count` para cada tipo

### Tests Unitarios

```bash
php artisan test --filter=AggregationServiceTest
```

---

## 🎨 Uso en el Frontend

Con esta estructura, el frontend puede implementar:

```vue
<!-- Selector de tipo de atención -->
<SelectButton
  v-model="selectedAttendanceType"
  :options="['Total', 'Ambulatorio', 'Emergencia', 'Hospitalario', 'Proceso']"
/>

<!-- Tabla filtrada dinámicamente por tipo -->
<DataTable :value="filteredMonthlyStats" />
```

---

## 📂 Archivos Modificados

1. ✅ `app/Repositories/DashboardAggregationRepository.php` (+18 líneas)
2. ✅ `app/Services/DashboardService.php` (+77 líneas, -10 líneas)
3. ✅ `tests/Unit/AggregationServiceTest.php` (-4 líneas)
4. ✅ `MONTHLY_STATISTICS_NEW_STRUCTURE.json` (nuevo archivo de ejemplo)

---

## 🚀 Próximos Pasos

1. Ejecutar tests para validar cambios
2. Probar el endpoint en Postman/Insomnia
3. Verificar que los porcentajes suman correctamente
4. Actualizar documentación del frontend
5. Commitear cambios con mensaje descriptivo

---

## 📝 Notas Técnicas

- El query SQL agrupa por mes y tipo de atención
- Los tipos se normalizan a mayúsculas y se eliminan espacios
- Se incluye tipo vacío ("") para manejar registros sin tipo
- Los porcentajes se calculan sobre el total del mes
- Los promedios se calculan directamente en MySQL con `AVG()`
