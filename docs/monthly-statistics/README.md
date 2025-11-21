# 📅 Documentación de Estadísticas Mensuales

Documentación específica del endpoint `monthly_statistics` y sus evoluciones.

## 📄 Archivos

### Documentación Principal
- **DASHBOARD_MONTHLY_STATISTICS_REPORT.md** - Reporte completo del endpoint de estadísticas mensuales

### Implementaciones
- **MONTHLY_STATISTICS_IMPLEMENTATION_SUMMARY.md** - Resumen de implementación inicial
- **MONTHLY_STATISTICS_WITH_INVOICING.md** - Implementación con datos de facturación
- **MONTHLY_STATISTICS_FINAL_SUMMARY.md** - Resumen de la versión final

### Refactorizaciones
- **MONTHLY_STATISTICS_REFACTORING.md** - Refactorización para eliminar campos obsoletos y agregar desglose por tipo
- **MONTHLY_STATISTICS_UNIQUE_PATIENTS_FEATURE.md** - Nueva funcionalidad de pacientes únicos por tipo de atención

### Optimizaciones
- **MONTHLY_STATISTICS_OPTIMIZATION.md** - Optimizaciones de performance implementadas
- **MONTHLY_STATISTICS_UPDATE_SUMMARY.md** - Resumen de actualizaciones y mejoras

## 📊 Estructura del Endpoint

El endpoint retorna estadísticas mensuales con:

### Campos Base (por mes)
- `month` - Número del mes
- `month_name` - Nombre del mes (Ene, Feb, etc.)
- `unique_patients` - Pacientes únicos del mes
- `total_admissions` - Total de admisiones del mes
- `total_amount` - Monto total del mes
- `avg_amount_per_admission` - Monto promedio por admisión

### Desglose por Tipo de Atención (`by_attendance_type`)

#### 1. `view_unique_patients` 🆕
- Pacientes únicos por tipo de atención
- Porcentajes sobre el total de pacientes únicos del mes
- Útil para calcular tasas de utilización de servicios

#### 2. `view_by_quantity`
- Cantidad de admisiones por tipo
- Porcentajes sobre el total de admisiones del mes
- Útil para analizar distribución de servicios

#### 3. `view_by_amount`
- Montos totales por tipo
- Promedios por admisión
- Porcentajes sobre el monto total del mes
- Útil para análisis financiero

## 🎯 Métricas Calculables

| Métrica | Fórmula | Vista Requerida |
|---------|---------|-----------------|
| Tasa de Utilización | `(unique_patients_tipo / unique_patients_total) * 100` | view_unique_patients |
| Admisiones por Paciente | `count_tipo / unique_patients_tipo` | ambas |
| Gasto por Paciente | `amount_tipo / unique_patients_tipo` | ambas |
| Índice de Superposición | `(sum_unique_patients_tipos - unique_patients_total) / unique_patients_total` | view_unique_patients |

## ⚠️ Nota Importante: Superposición de Pacientes

Un paciente puede tener múltiples tipos de atención en el mismo mes, por lo tanto:

- ✅ La suma de `unique_patients` por tipo puede ser **mayor** que `unique_patients` total
- ✅ La suma de porcentajes en `view_unique_patients` puede ser **mayor a 100%**
- ✅ Esto es **comportamiento esperado y correcto**

### Ejemplo

```
Total pacientes únicos del mes: 377

Desglose:
- Ambulatorio: 305 pacientes (80.90%)
- Emergencia: 110 pacientes (29.18%)
- Hospitalario: 14 pacientes (3.71%)
- Proceso: 3 pacientes (0.80%)

Suma: 432 pacientes (114.59%)
```

Un paciente puede haber tenido atención Ambulatoria + Emergencia, por eso aparece en ambas.

## 📁 Ejemplos JSON

Ver carpeta `/examples/monthly-statistics` para ejemplos completos de respuestas.

## 🔗 Ver también

- `/examples/monthly-statistics` - Ejemplos de respuestas JSON
- `/docs/dashboard` - Documentación general del dashboard
