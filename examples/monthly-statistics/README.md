# 📊 Ejemplos de Monthly Statistics

Ejemplos de respuestas JSON del endpoint `monthly_statistics` en sus diferentes versiones.

## 📄 Archivos

### Versiones Históricas

1. **MONTHLY_STATISTICS_EXAMPLE_RESPONSE.json**
   - Respuesta de ejemplo básica inicial
   - Sin desglose por tipo de atención

2. **MONTHLY_STATISTICS_COMPLETE_EXAMPLE.json**
   - Ejemplo completo con múltiples meses
   - Versión intermedia del endpoint

3. **REFACTORED_RESPONSE_EXAMPLE.json**
   - Ejemplo de respuesta refactorizada
   - Incluye mejoras en la estructura

### Versión Actual

4. **MONTHLY_STATISTICS_NEW_STRUCTURE.json** ✅
   - Estructura actual sin `view_unique_patients`
   - Incluye `view_by_quantity` y `view_by_amount`
   - Campos obsoletos eliminados

5. **MONTHLY_STATISTICS_WITH_UNIQUE_PATIENTS.json** ✅ **ÚLTIMA VERSIÓN**
   - Estructura completa y actualizada
   - Incluye las 3 vistas:
     - `view_unique_patients` (pacientes únicos por tipo)
     - `view_by_quantity` (admisiones por tipo)
     - `view_by_amount` (montos por tipo)

## 🎯 Uso Recomendado

Para implementaciones nuevas, usar:
- **MONTHLY_STATISTICS_WITH_UNIQUE_PATIENTS.json** como referencia principal

## 📊 Estructura Principal

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
        "view_unique_patients": [...],
        "view_by_quantity": [...],
        "view_by_amount": [...]
      }
    }
  ]
}
```

## 🔗 Ver también

- `/docs/monthly-statistics` - Documentación técnica completa
- `/docs/monthly-statistics/MONTHLY_STATISTICS_UNIQUE_PATIENTS_FEATURE.md` - Explicación detallada de la última funcionalidad
