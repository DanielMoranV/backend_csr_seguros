# 📋 Nueva Funcionalidad: Pacientes Únicos por Tipo de Atención

## ✅ Cambios Implementados

### 1. Query SQL Actualizado

**Archivo:** `app/Repositories/DashboardAggregationRepository.php:276`

Se agregó `COUNT(DISTINCT SC0011.cod_pac)` al query SQL para obtener pacientes únicos por tipo:

```php
->selectRaw('
    MONTH(SC0011.fec_doc) as month,
    UPPER(TRIM(SC0011.ta_doc)) as type,
    COUNT(*) as count,
    COUNT(DISTINCT SC0011.cod_pac) as unique_patients,  // 🆕 NUEVO
    SUM(SC0011.tot_doc) as amount,
    AVG(SC0011.tot_doc) as average
')
```

---

### 2. Nueva Vista: `view_unique_patients`

**Archivo:** `app/Services/DashboardService.php:537-540`

Se agregó un nuevo array `view_unique_patients` en el retorno de `by_attendance_type`:

```php
return [
    'view_unique_patients' => $viewUniquePatients,  // 🆕 NUEVO
    'view_by_quantity' => $viewByQuantity,
    'view_by_amount' => $viewByAmount,
];
```

---

## 📊 Estructura Final

```json
{
  "month": 2,
  "month_name": "Feb",
  "unique_patients": 377,
  "total_admissions": 570,
  "total_amount": 239950.33,
  "avg_amount_per_admission": 420.97,
  "by_attendance_type": {
    "view_unique_patients": [
      {
        "type": "Ambulatorio",
        "unique_patients": 305,
        "percentage": 80.90
      },
      {
        "type": "Emergencia",
        "unique_patients": 110,
        "percentage": 29.18
      },
      {
        "type": "Hospitalario",
        "unique_patients": 14,
        "percentage": 3.71
      },
      {
        "type": "Proceso",
        "unique_patients": 3,
        "percentage": 0.80
      },
      {
        "type": "",
        "unique_patients": 0,
        "percentage": 0.00
      }
    ],
    "view_by_quantity": [...],
    "view_by_amount": [...]
  }
}
```

---

## 🔍 Cómo Funciona

### Cálculo de Pacientes Únicos

1. **Por Tipo**: `COUNT(DISTINCT SC0011.cod_pac)` agrupa por tipo de atención
2. **Total del Mes**: Se obtiene del campo `unique_patients` del mes

### Cálculo de Porcentajes

```php
percentage = (unique_patients_del_tipo / unique_patients_total_mes) * 100
```

**Ejemplo:**
- Total pacientes únicos del mes: **377**
- Pacientes con atención Ambulatoria: **305**
- Porcentaje: `(305 / 377) * 100 = 80.90%`

---

## ⚠️ Consideración Importante: Superposición de Pacientes

### Un paciente puede tener múltiples tipos de atención en el mismo mes

**Ejemplo Real:**

```
Total pacientes únicos del mes: 377

Desglose por tipo:
- Ambulatorio: 305 pacientes (80.90%)
- Emergencia: 110 pacientes (29.18%)
- Hospitalario: 14 pacientes (3.71%)
- Proceso: 3 pacientes (0.80%)

Suma: 305 + 110 + 14 + 3 = 432 (mayor que 377)
Suma de porcentajes: 80.90 + 29.18 + 3.71 + 0.80 = 114.59% (mayor que 100%)
```

### ¿Por qué la suma supera 100%?

Porque un paciente puede aparecer en múltiples categorías:

```
Paciente P001:
- 3 atenciones Ambulatorias
- 1 atención de Emergencia
- 1 atención Hospitalaria

Este paciente cuenta en 3 tipos diferentes
```

### Validaciones

✅ **Cada tipo ≤ Total**: `unique_patients_tipo ≤ unique_patients_total`
✅ **Cada porcentaje ≤ 100%**: `percentage_tipo ≤ 100%`
⚠️ **Suma puede superar 100%**: Es normal por la superposición

---

## 🔧 Cambios en el Código

### 1. Repository: `DashboardAggregationRepository.php`

**Línea 276:** Agregar `COUNT(DISTINCT SC0011.cod_pac) as unique_patients`

```diff
  ->selectRaw('
      MONTH(SC0011.fec_doc) as month,
      UPPER(TRIM(SC0011.ta_doc)) as type,
      COUNT(*) as count,
+     COUNT(DISTINCT SC0011.cod_pac) as unique_patients,
      SUM(SC0011.tot_doc) as amount,
      AVG(SC0011.tot_doc) as average
  ')
```

---

### 2. Service: `DashboardService.php`

**Líneas 458-463:** Pasar `$uniquePatients` al método

```diff
  $monthData['by_attendance_type'] = $this->formatAttendanceTypeByMonth(
      $attendanceByMonth[$currentMonth] ?? [],
+     $uniquePatients,
      $totalAdmissions,
      $totalAmount
  );
```

**Líneas 510-516:** Crear array `view_unique_patients`

```php
$viewUniquePatients[] = [
    'type' => $formattedType,
    'unique_patients' => $uniquePatients,
    'percentage' => $totalUniquePatients > 0
        ? round(($uniquePatients * 100) / $totalUniquePatients, 2)
        : 0,
];
```

**Líneas 537-540:** Retornar nueva vista

```diff
  return [
+     'view_unique_patients' => $viewUniquePatients,
      'view_by_quantity' => $viewByQuantity,
      'view_by_amount' => $viewByAmount,
  ];
```

---

## ✨ Casos de Uso en el Frontend

### 1. Tasa de Utilización por Tipo

```javascript
// ¿Qué porcentaje de pacientes usó cada tipo de servicio?
const ambulatorioRate = (305 / 377) * 100; // 80.90%
const emergenciaRate = (110 / 377) * 100;  // 29.18%
```

### 2. Promedio de Admisiones por Paciente por Tipo

```javascript
// ¿Cuántas veces en promedio un paciente usó cada tipo?
const avgAdmissionsAmbulatorio = 415 / 305; // 1.36 admisiones por paciente
const avgAdmissionsEmergencia = 136 / 110;  // 1.24 admisiones por paciente
```

### 3. Filtrado de Tabla por Tipo

```vue
<SelectButton
  v-model="selectedType"
  :options="['Total', 'Ambulatorio', 'Emergencia', 'Hospitalario']"
/>

<!-- Mostrar pacientes únicos según el filtro -->
<div v-if="selectedType === 'Total'">
  Total Pacientes: {{ month.unique_patients }}
</div>
<div v-else>
  Pacientes con {{ selectedType }}:
  {{ getUniquePatientsByType(selectedType) }}
</div>
```

### 4. Análisis de Comportamiento

```javascript
// Detectar pacientes con múltiples tipos (superposición)
const totalUniqueByType = types.reduce((sum, type) =>
  sum + type.unique_patients, 0
);

const overlapPatients = totalUniqueByType - month.unique_patients;
const overlapPercentage = (overlapPatients / month.unique_patients) * 100;

console.log(`${overlapPercentage}% de pacientes tuvieron múltiples tipos`);
```

---

## 📊 Métricas Calculables

Con esta nueva vista, el frontend puede calcular:

| Métrica | Fórmula | Interpretación |
|---------|---------|----------------|
| Tasa de Utilización | `(unique_patients_tipo / unique_patients_total) * 100` | % de pacientes que usaron ese tipo |
| Admisiones por Paciente | `count_tipo / unique_patients_tipo` | Frecuencia promedio de uso |
| Gasto por Paciente | `amount_tipo / unique_patients_tipo` | Monto promedio gastado por paciente |
| Índice de Superposición | `(sum_unique_patients_tipos - unique_patients_total) / unique_patients_total` | % de pacientes con múltiples tipos |

---

## 📂 Archivos Modificados

1. ✅ `app/Repositories/DashboardAggregationRepository.php` (+1 línea)
2. ✅ `app/Services/DashboardService.php` (+21 líneas)
3. ✅ `MONTHLY_STATISTICS_WITH_UNIQUE_PATIENTS.json` (nuevo archivo de ejemplo)

**Total: 22 inserciones**

---

## 🎯 Beneficios

1. ✅ **Análisis más profundo**: Entender el comportamiento de los pacientes por tipo
2. ✅ **Métricas avanzadas**: Calcular tasas de utilización y frecuencia de uso
3. ✅ **Consistencia**: Mantiene la misma estructura que las otras vistas
4. ✅ **Filtrado dinámico**: Permite al frontend mostrar datos específicos por tipo
5. ✅ **Detección de patrones**: Identificar pacientes con múltiples tipos de atención

---

## 🧪 Validación

### Validaciones Automáticas

1. ✅ `unique_patients_tipo` ≤ `unique_patients_total` del mes
2. ✅ `percentage_tipo` ≤ 100%
3. ⚠️ Suma de `unique_patients` por tipo ≥ total (esperado por superposición)
4. ⚠️ Suma de `percentage` puede ser > 100% (normal)

### Ejemplo de Validación en Frontend

```javascript
// Validar que ningún tipo supere el total
const isValid = types.every(type =>
  type.unique_patients <= month.unique_patients
);

if (!isValid) {
  console.error('Error: Un tipo tiene más pacientes que el total');
}
```

---

## 🚀 Próximos Pasos

1. Probar el endpoint en Postman/Insomnia
2. Verificar que los porcentajes sean coherentes
3. Implementar componentes en el frontend para mostrar la nueva vista
4. Actualizar documentación del API
5. Commitear cambios

---

## 📝 Notas Técnicas

- El query SQL usa `COUNT(DISTINCT cod_pac)` que es eficiente en MySQL
- Los porcentajes se calculan sobre `unique_patients` del mes, no sobre la suma
- La superposición es un comportamiento esperado y correcto
- Todos los tipos (incluyendo "") se incluyen para mantener consistencia
- Los cálculos se redondean a 2 decimales
