# Reporte de Estadísticas Mensuales - Dashboard

## Resumen

El reporte de **Estadísticas Mensuales** es un nuevo componente agregado al endpoint `/api/dashboard/date-range-analysis` que proporciona un análisis detallado mes a mes de la actividad del periodo solicitado.

**Agregado**: 2025-01-20
**Endpoint**: `POST /api/dashboard/date-range-analysis`
**Campo en response**: `monthly_statistics`

---

## Descripción

Este reporte muestra para cada mes del rango de fechas:
- **Pacientes únicos** atendidos
- **Cantidad total** de atenciones generadas
- **Monto total** facturado
- **Promedio de monto** por atención
- **Promedio de atenciones** por paciente
- **Tasa de reincidencia** (porcentaje de pacientes con múltiples atenciones)

---

## Estructura del Response

### Ubicación en el Response

El reporte se incluye en el campo `monthly_statistics` del response de `/api/dashboard/date-range-analysis`:

```json
{
  "summary": { ... },
  "invoice_status_by_month": { ... },
  "insurers_by_month": { ... },
  "payment_status": { ... },
  "attendance_type_analysis": { ... },
  "unique_patients": { ... },
  "top_companies": { ... },
  "monthly_statistics": [
    {
      "month": 1,
      "month_name": "Ene",
      "unique_patients": 120,
      "total_admissions": 150,
      "total_amount": 45000.50,
      "avg_amount_per_admission": 300.00,
      "avg_admissions_per_patient": 1.25,
      "recurrence_rate": 25.00
    },
    {
      "month": 2,
      "month_name": "Feb",
      ...
    }
  ],
  "admissions": [ ... ]
}
```

### Campos del Reporte

| Campo | Tipo | Descripción | Ejemplo |
|-------|------|-------------|---------|
| `month` | number | Número del mes (1-12) | `1` |
| `month_name` | string | Nombre del mes en español (abreviado) | `"Ene"` |
| `unique_patients` | number | Cantidad de pacientes únicos atendidos en el mes | `120` |
| `total_admissions` | number | Cantidad total de atenciones generadas | `150` |
| `total_amount` | number | Monto total facturado en el mes (2 decimales) | `45000.50` |
| `avg_amount_per_admission` | number | Promedio de monto por atención (2 decimales) | `300.00` |
| `avg_admissions_per_patient` | number | Promedio de atenciones por paciente (2 decimales) | `1.25` |
| `recurrence_rate` | number | Tasa de reincidencia: porcentaje de atenciones de pacientes que regresaron (2 decimales) | `25.00` |

---

## Ejemplo Completo

### Request

```http
POST /api/dashboard/date-range-analysis
Content-Type: application/json
Authorization: Bearer {token}

{
  "start_date": "2025-01-01",
  "end_date": "2025-03-31"
}
```

### Response

```json
{
  "summary": {
    "total_admissions": 450,
    "period": {
      "start": "2025-01-01",
      "end": "2025-03-31"
    }
  },
  "monthly_statistics": [
    {
      "month": 1,
      "month_name": "Ene",
      "unique_patients": 120,
      "total_admissions": 150,
      "total_amount": 45000.50,
      "avg_amount_per_admission": 300.00,
      "avg_admissions_per_patient": 1.25,
      "recurrence_rate": 25.00
    },
    {
      "month": 2,
      "month_name": "Feb",
      "unique_patients": 145,
      "total_admissions": 175,
      "total_amount": 58000.75,
      "avg_amount_per_admission": 331.43,
      "avg_admissions_per_patient": 1.21,
      "recurrence_rate": 20.69
    },
    {
      "month": 3,
      "month_name": "Mar",
      "unique_patients": 100,
      "total_admissions": 125,
      "total_amount": 42000.00,
      "avg_amount_per_admission": 336.00,
      "avg_admissions_per_patient": 1.25,
      "recurrence_rate": 25.00
    }
  ],
  "invoice_status_by_month": { ... },
  "insurers_by_month": { ... },
  "payment_status": { ... },
  "attendance_type_analysis": { ... },
  "unique_patients": { ... },
  "top_companies": { ... },
  "admissions": [ ... ]
}
```

---

## Interpretación de Métricas

### 1. Pacientes Únicos (`unique_patients`)

**Definición**: Cantidad de pacientes diferentes atendidos en el mes.

**Cálculo**: Se cuenta cada `cod_pac` (código de paciente) único en el mes.

**Uso**: Permite identificar el alcance real de la clínica en términos de personas atendidas.

**Ejemplo**:
- Si `unique_patients = 120`, significa que 120 personas diferentes recibieron atención en ese mes.

---

### 2. Atenciones Totales (`total_admissions`)

**Definición**: Cantidad total de atenciones/admisiones registradas en el mes.

**Cálculo**: Se cuentan todas las admisiones del mes, incluyendo múltiples atenciones al mismo paciente.

**Uso**: Mide el volumen total de trabajo de la clínica.

**Ejemplo**:
- Si `total_admissions = 150` y `unique_patients = 120`, significa que hubo 150 atenciones para 120 pacientes.

---

### 3. Monto Total (`total_amount`)

**Definición**: Suma de todos los montos facturados en las atenciones del mes.

**Cálculo**: Se suman los valores del campo `tot_doc` (total del documento) de todas las admisiones.

**Uso**: Indica los ingresos generados en el mes.

**Ejemplo**:
- `total_amount = 45000.50` significa que se facturó S/ 45,000.50 en ese mes.

---

### 4. Promedio por Atención (`avg_amount_per_admission`)

**Definición**: Monto promedio facturado por cada atención.

**Cálculo**: `total_amount / total_admissions`

**Uso**: Indica el ticket promedio de las atenciones.

**Ejemplo**:
- `avg_amount_per_admission = 300.00` significa que cada atención generó en promedio S/ 300.

---

### 5. Promedio de Atenciones por Paciente (`avg_admissions_per_patient`)

**Definición**: Cantidad promedio de atenciones que recibe cada paciente.

**Cálculo**: `total_admissions / unique_patients`

**Uso**: Mide la frecuencia con la que los pacientes regresan.

**Interpretación**:
- `1.00` = Cada paciente tuvo exactamente 1 atención (sin reincidencia)
- `1.25` = En promedio, cada paciente tuvo 1.25 atenciones (25% de reincidencia)
- `2.00` = En promedio, cada paciente tuvo 2 atenciones

**Ejemplo**:
- `avg_admissions_per_patient = 1.25` significa que de 120 pacientes, algunos regresaron para una segunda (o más) atención.

---

### 6. Tasa de Reincidencia (`recurrence_rate`)

**Definición**: Porcentaje que representa las atenciones adicionales (más allá de la primera) respecto a los pacientes únicos.

**Cálculo**: `((total_admissions - unique_patients) / unique_patients) * 100`

**Uso**: Indica qué tan frecuente es que los pacientes regresen para atenciones adicionales.

**Interpretación**:
- `0%` = Ningún paciente regresó (todos tuvieron solo 1 atención)
- `25%` = Las atenciones adicionales representan 25% de los pacientes únicos
- `100%` = Las atenciones adicionales equivalen al 100% de pacientes únicos (promedio de 2 atenciones por paciente)

**Ejemplo**:
- Si `unique_patients = 120`, `total_admissions = 150`
- `recurrence_rate = ((150 - 120) / 120) * 100 = 25%`
- Interpretación: De los 120 pacientes, 30 atenciones adicionales (25% de 120) fueron de pacientes que regresaron.

---

## Meses sin Datos

El reporte **incluye todos los meses del rango** especificado, incluso si un mes no tiene datos:

```json
{
  "month": 4,
  "month_name": "Abr",
  "unique_patients": 0,
  "total_admissions": 0,
  "total_amount": 0,
  "avg_amount_per_admission": 0,
  "avg_admissions_per_patient": 0,
  "recurrence_rate": 0
}
```

**Razón**: Permite visualizaciones continuas sin "gaps" en gráficos o tablas.

---

## Implementación en el Frontend

### Opción 1: Tabla Simple

```vue
<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const monthlyStats = ref([]);

const fetchData = async () => {
  const { data } = await axios.post('/api/dashboard/date-range-analysis', {
    start_date: '2025-01-01',
    end_date: '2025-03-31'
  });

  monthlyStats.value = data.monthly_statistics;
};

// Formatear números con separadores de miles
const formatNumber = (value) => {
  return new Intl.NumberFormat('es-PE').format(value);
};

// Formatear moneda
const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-PE', {
    style: 'currency',
    currency: 'PEN'
  }).format(value);
};
</script>

<template>
  <div class="monthly-statistics">
    <h2>Estadísticas Mensuales</h2>

    <table class="stats-table">
      <thead>
        <tr>
          <th>Mes</th>
          <th>Pacientes Únicos</th>
          <th>Atenciones</th>
          <th>Monto Total</th>
          <th>Promedio/Atención</th>
          <th>Atenciones/Paciente</th>
          <th>Reincidencia</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="stat in monthlyStats" :key="stat.month">
          <td>{{ stat.month_name }}</td>
          <td>{{ formatNumber(stat.unique_patients) }}</td>
          <td>{{ formatNumber(stat.total_admissions) }}</td>
          <td>{{ formatCurrency(stat.total_amount) }}</td>
          <td>{{ formatCurrency(stat.avg_amount_per_admission) }}</td>
          <td>{{ stat.avg_admissions_per_patient.toFixed(2) }}</td>
          <td>{{ stat.recurrence_rate.toFixed(2) }}%</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.stats-table {
  width: 100%;
  border-collapse: collapse;
}

.stats-table th,
.stats-table td {
  padding: 12px;
  text-align: right;
  border-bottom: 1px solid #e0e0e0;
}

.stats-table th {
  background-color: #f5f5f5;
  font-weight: 600;
}

.stats-table th:first-child,
.stats-table td:first-child {
  text-align: left;
}
</style>
```

---

### Opción 2: Gráfico de Líneas (Chart.js)

```vue
<script setup>
import { ref, computed, onMounted } from 'vue';
import { Line } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
);

const monthlyStats = ref([]);

const chartData = computed(() => ({
  labels: monthlyStats.value.map(s => s.month_name),
  datasets: [
    {
      label: 'Pacientes Únicos',
      data: monthlyStats.value.map(s => s.unique_patients),
      borderColor: 'rgb(75, 192, 192)',
      backgroundColor: 'rgba(75, 192, 192, 0.2)',
      yAxisID: 'y'
    },
    {
      label: 'Atenciones Totales',
      data: monthlyStats.value.map(s => s.total_admissions),
      borderColor: 'rgb(255, 99, 132)',
      backgroundColor: 'rgba(255, 99, 132, 0.2)',
      yAxisID: 'y'
    }
  ]
}));

const chartOptions = {
  responsive: true,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  scales: {
    y: {
      type: 'linear',
      display: true,
      position: 'left',
      title: {
        display: true,
        text: 'Cantidad'
      }
    }
  },
  plugins: {
    legend: {
      position: 'top',
    },
    title: {
      display: true,
      text: 'Evolución Mensual de Atenciones'
    }
  }
};

onMounted(async () => {
  const { data } = await axios.post('/api/dashboard/date-range-analysis', {
    start_date: '2025-01-01',
    end_date: '2025-12-31'
  });

  monthlyStats.value = data.monthly_statistics;
});
</script>

<template>
  <div class="chart-container">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>
```

---

## Casos de Uso

### 1. Análisis de Tendencias

**Pregunta**: ¿Cómo ha evolucionado la cantidad de pacientes mes a mes?

**Uso**: Graficar `unique_patients` por `month_name` para identificar tendencias estacionales o crecimiento.

---

### 2. Detección de Meses Críticos

**Pregunta**: ¿En qué meses hubo menor/mayor actividad?

**Uso**: Ordenar por `total_admissions` o `total_amount` para identificar meses con baja/alta actividad.

---

### 3. Análisis de Fidelización

**Pregunta**: ¿Qué tan frecuente es que los pacientes regresen?

**Uso**: Analizar `recurrence_rate` y `avg_admissions_per_patient` para medir la fidelización de pacientes.

---

### 4. Proyección de Ingresos

**Pregunta**: ¿Cuál es el ingreso promedio mensual?

**Uso**: Calcular el promedio de `total_amount` para proyectar ingresos futuros.

---

### 5. Eficiencia Operativa

**Pregunta**: ¿Cuántos pacientes atiende la clínica por mes?

**Uso**: Comparar `unique_patients` vs `total_admissions` para medir la eficiencia en captación vs retención.

---

## Optimización

El cálculo de este reporte está optimizado para:

1. **Procesamiento en memoria**: Usa un solo recorrido del array de admisiones (O(n))
2. **Uso eficiente de memoria**: Agrupa por mes usando arrays asociativos
3. **Incluye todos los meses**: Completa meses faltantes con valores en cero
4. **Cálculos precisos**: Todos los valores numéricos se redondean a 2 decimales

---

## Notas Técnicas

### Campo Utilizado para Pacientes Únicos

- **Campo**: `patient_code` (equivalente a `SC0011.cod_pac` en la base de datos legacy)
- **Tipo**: String
- **Formato**: Código alfanumérico único por paciente

### Consideraciones

1. **Pacientes sin código**: Si `patient_code` es `null` o vacío, no se cuenta como paciente único.
2. **Meses sin datos**: Se incluyen con valores en `0` para mantener continuidad.
3. **Rangos de varios años**: El reporte puede abarcar múltiples años, respetando el orden cronológico.

---

## Ejemplos de Análisis

### Ejemplo 1: Crecimiento Mensual

```json
[
  { "month": 1, "unique_patients": 100, "total_admissions": 120 },
  { "month": 2, "unique_patients": 120, "total_admissions": 150 },
  { "month": 3, "unique_patients": 145, "total_admissions": 180 }
]
```

**Análisis**: La clínica está creciendo, tanto en pacientes nuevos como en atenciones totales.

---

### Ejemplo 2: Alta Reincidencia

```json
[
  {
    "month": 1,
    "unique_patients": 100,
    "total_admissions": 200,
    "avg_admissions_per_patient": 2.00,
    "recurrence_rate": 100.00
  }
]
```

**Análisis**: Los pacientes están regresando frecuentemente (promedio de 2 atenciones por paciente).

---

### Ejemplo 3: Baja Fidelización

```json
[
  {
    "month": 1,
    "unique_patients": 100,
    "total_admissions": 105,
    "avg_admissions_per_patient": 1.05,
    "recurrence_rate": 5.00
  }
]
```

**Análisis**: Muy pocos pacientes regresan (solo 5% de reincidencia).

---

## Preguntas Frecuentes

### ¿El reporte incluye pacientes particulares?

No, el reporte excluye pacientes con aseguradoras "PARTICULAR" o "PACIENTES PARTICULARES", al igual que todos los demás reportes del dashboard.

### ¿Se incluyen atenciones con monto negativo?

No, se filtran las admisiones con `tot_doc < 0`.

### ¿Qué pasa si un mes no tiene datos?

El mes se incluye con todos los valores en `0`.

### ¿Puedo obtener solo este reporte sin las admisiones completas?

No, actualmente el reporte `monthly_statistics` solo se incluye cuando `includeAdmissions = true`. Si solo necesitas agregaciones, usa `aggregations_only = true`, pero ese modo no incluye este reporte.

---

## Compatibilidad con Otros Reportes

Este reporte complementa los otros reportes del dashboard:

| Reporte | Relación con Monthly Statistics |
|---------|----------------------------------|
| `summary.total_admissions` | Suma de `total_admissions` de todos los meses |
| `unique_patients.total` | Pacientes únicos en TODO el rango (no suma de meses) |
| `invoice_status_by_month` | Mismos meses, pero enfocado en facturación |
| `insurers_by_month` | Mismos meses, pero por aseguradora |

---

## Roadmap Futuro

Posibles mejoras planeadas:

- [ ] Comparación mes a mes (% de crecimiento)
- [ ] Identificación de meses atípicos (outliers)
- [ ] Proyección de próximos meses basado en tendencias
- [ ] Exportación a Excel con gráficos incluidos

---

## Contacto

Para preguntas o sugerencias sobre este reporte:
- **Backend Team**: Para dudas sobre implementación
- **Frontend Team**: Para dudas sobre visualización
- **Repositorio**: [GitHub/csr_frontend_seguros](./)

---

**Versión**: 1.0
**Fecha**: 2025-01-20
**Autor**: Backend Team
