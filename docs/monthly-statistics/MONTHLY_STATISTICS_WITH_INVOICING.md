# Estadísticas Mensuales con Métricas de Facturación y Pago

## 🆕 Nuevas Métricas Agregadas

Se agregaron **6 nuevas métricas** al reporte de estadísticas mensuales para analizar el estado de facturación y pagos:

### Métricas de Facturación y Pago

| Métrica | Descripción | Ejemplo |
|---------|-------------|---------|
| `invoiced_admissions` | Atenciones facturadas (con factura válida en SC0017) | `580` |
| `paid_admissions` | Atenciones pagadas (que además están en SC0022) | `450` |
| `pending_admissions` | Atenciones pendientes de facturar | `32` |
| `unpaid_admissions` | Atenciones facturadas pero no pagadas | `130` |
| `invoiced_percentage` | % de atenciones facturadas del total | `94.77%` |
| `paid_percentage` | % de atenciones pagadas del total facturado | `77.59%` |

---

## 📊 Estructura Completa del Response

```json
{
  "monthly_statistics": [
    {
      "month": 2,
      "month_name": "Feb",

      // Métricas de pacientes y atenciones
      "unique_patients": 531,
      "total_admissions": 612,
      "total_amount": 272105.13,

      // ✨ NUEVAS: Métricas de facturación y pago
      "invoiced_admissions": 580,
      "paid_admissions": 450,
      "pending_admissions": 32,
      "unpaid_admissions": 130,
      "invoiced_percentage": 94.77,
      "paid_percentage": 77.59,

      // Métricas calculadas
      "avg_amount_per_admission": 444.62,
      "avg_admissions_per_patient": 1.15,
      "recurrence_rate": 15.25
    },
    {
      "month": 3,
      "month_name": "Mar",
      "unique_patients": 596,
      "total_admissions": 690,
      "total_amount": 427054.60,
      "invoiced_admissions": 670,
      "paid_admissions": 550,
      "pending_admissions": 20,
      "unpaid_admissions": 120,
      "invoiced_percentage": 97.10,
      "paid_percentage": 82.09,
      "avg_amount_per_admission": 619.21,
      "avg_admissions_per_patient": 1.16,
      "recurrence_rate": 15.77
    }
  ]
}
```

---

## 🔍 Detalle de las Nuevas Métricas

### 1. Atenciones Facturadas (`invoiced_admissions`)

**Definición**: Cantidad de atenciones que tienen al menos una factura válida en SC0017.

**Cálculo SQL**:
```sql
COUNT(DISTINCT CASE
    WHEN SC0017.num_fac IS NOT NULL
        AND SC0017.num_fac NOT LIKE "005-%"
        AND SC0017.num_fac NOT LIKE "006-%"
        AND SC0017.num_fac NOT LIKE "009-%"
    THEN SC0011.num_doc
END) as invoiced_admissions
```

**Criterios**:
- ✅ Debe tener `num_doc` en SC0017
- ✅ `num_fac` debe estar presente
- ❌ Excluye facturas temporales (005-*, 006-*, 009-*)

**Ejemplo**:
- `total_admissions = 612`
- `invoiced_admissions = 580`
- Significa que **580 atenciones ya fueron facturadas**

---

### 2. Atenciones Pagadas (`paid_admissions`)

**Definición**: Cantidad de atenciones facturadas que además tienen registro de pago en SC0022.

**Cálculo SQL**:
```sql
COUNT(DISTINCT CASE
    WHEN SC0017.num_fac IS NOT NULL
        AND SC0017.num_fac NOT LIKE "005-%"
        AND SC0017.num_fac NOT LIKE "006-%"
        AND SC0017.num_fac NOT LIKE "009-%"
        AND SC0022.num_doc IS NOT NULL
    THEN SC0011.num_doc
END) as paid_admissions
```

**Criterios**:
- ✅ Debe cumplir todos los criterios de `invoiced_admissions`
- ✅ Además debe estar en SC0022 (registro de pago)

**Ejemplo**:
- `invoiced_admissions = 580`
- `paid_admissions = 450`
- Significa que **de las 580 facturadas, 450 ya están pagadas**

---

### 3. Atenciones Pendientes (`pending_admissions`)

**Definición**: Atenciones que aún NO han sido facturadas.

**Cálculo**:
```
pending_admissions = total_admissions - invoiced_admissions
```

**Ejemplo**:
- `total_admissions = 612`
- `invoiced_admissions = 580`
- `pending_admissions = 32`
- Significa que **32 atenciones están pendientes de facturar**

---

### 4. Atenciones Sin Pagar (`unpaid_admissions`)

**Definición**: Atenciones que fueron facturadas pero aún NO han sido pagadas.

**Cálculo**:
```
unpaid_admissions = invoiced_admissions - paid_admissions
```

**Ejemplo**:
- `invoiced_admissions = 580`
- `paid_admissions = 450`
- `unpaid_admissions = 130`
- Significa que **130 facturas están pendientes de pago**

---

### 5. Porcentaje de Facturación (`invoiced_percentage`)

**Definición**: Porcentaje de atenciones que han sido facturadas respecto al total.

**Cálculo**:
```
invoiced_percentage = (invoiced_admissions / total_admissions) * 100
```

**Interpretación**:
- `94.77%` = Alta eficiencia de facturación
- `< 80%` = Podría indicar retrasos en facturación
- `100%` = Todas las atenciones están facturadas

**Ejemplo**:
- `total_admissions = 612`
- `invoiced_admissions = 580`
- `invoiced_percentage = (580 / 612) * 100 = 94.77%`

---

### 6. Porcentaje de Pago (`paid_percentage`)

**Definición**: Porcentaje de facturas que han sido pagadas respecto al total facturado.

**Cálculo**:
```
paid_percentage = (paid_admissions / invoiced_admissions) * 100
```

**Interpretación**:
- `> 80%` = Buena tasa de cobro
- `50-80%` = Tasa regular de cobro
- `< 50%` = Problemas de cobro

**Ejemplo**:
- `invoiced_admissions = 580`
- `paid_admissions = 450`
- `paid_percentage = (450 / 580) * 100 = 77.59%`

---

## 📈 Embudo de Conversión (Funnel)

El reporte permite visualizar el embudo completo del proceso de atención → facturación → pago:

```
Total Atenciones (612)
    ↓ 94.77%
Facturadas (580)
    ↓ 77.59%
Pagadas (450)
```

### Cálculo de Pérdidas por Etapa

```javascript
const calculateFunnel = (stats) => {
  return {
    stage1_loss: stats.pending_admissions,           // 32 (sin facturar)
    stage2_loss: stats.unpaid_admissions,            // 130 (sin pagar)
    total_conversion: (stats.paid_admissions / stats.total_admissions) * 100  // 73.53%
  };
};
```

---

## 💡 Casos de Uso

### 1. Dashboard de Eficiencia de Facturación

```vue
<template>
  <div class="efficiency-dashboard">
    <div class="metric-card">
      <h3>Tasa de Facturación</h3>
      <div class="value" :class="getColorClass(stat.invoiced_percentage)">
        {{ stat.invoiced_percentage }}%
      </div>
      <div class="details">
        {{ stat.invoiced_admissions }} de {{ stat.total_admissions }}
      </div>
    </div>

    <div class="metric-card">
      <h3>Tasa de Cobro</h3>
      <div class="value" :class="getColorClass(stat.paid_percentage)">
        {{ stat.paid_percentage }}%
      </div>
      <div class="details">
        {{ stat.paid_admissions }} de {{ stat.invoiced_admissions }}
      </div>
    </div>

    <div class="metric-card alert" v-if="stat.unpaid_admissions > 0">
      <h3>⚠️ Facturas Pendientes</h3>
      <div class="value">{{ stat.unpaid_admissions }}</div>
      <div class="details">Requieren seguimiento</div>
    </div>
  </div>
</template>

<script setup>
const getColorClass = (percentage) => {
  if (percentage >= 90) return 'success';
  if (percentage >= 70) return 'warning';
  return 'danger';
};
</script>
```

---

### 2. Tabla Comparativa Mensual

```vue
<template>
  <table class="monthly-comparison">
    <thead>
      <tr>
        <th>Mes</th>
        <th>Atenciones</th>
        <th>Facturadas</th>
        <th>Pagadas</th>
        <th>% Facturación</th>
        <th>% Cobro</th>
        <th>Pendientes</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="stat in monthlyStats" :key="stat.month">
        <td>{{ stat.month_name }}</td>
        <td>{{ stat.total_admissions }}</td>
        <td>
          <span class="badge">{{ stat.invoiced_admissions }}</span>
        </td>
        <td>
          <span class="badge success">{{ stat.paid_admissions }}</span>
        </td>
        <td>
          <ProgressBar :value="stat.invoiced_percentage" />
        </td>
        <td>
          <ProgressBar :value="stat.paid_percentage" />
        </td>
        <td>
          <span class="badge alert" v-if="stat.unpaid_admissions > 0">
            {{ stat.unpaid_admissions }}
          </span>
          <span v-else>-</span>
        </td>
      </tr>
    </tbody>
  </table>
</template>
```

---

### 3. Gráfico de Embudo (Funnel Chart)

```vue
<script setup>
import { computed } from 'vue';

const funnelData = computed(() => {
  return monthlyStats.value.map(stat => ({
    month: stat.month_name,
    stages: [
      {
        label: 'Total',
        value: stat.total_admissions,
        percentage: 100
      },
      {
        label: 'Facturadas',
        value: stat.invoiced_admissions,
        percentage: stat.invoiced_percentage
      },
      {
        label: 'Pagadas',
        value: stat.paid_admissions,
        percentage: (stat.paid_admissions / stat.total_admissions) * 100
      }
    ]
  }));
});
</script>

<template>
  <div class="funnel-chart">
    <div v-for="month in funnelData" :key="month.month" class="month-funnel">
      <h4>{{ month.month }}</h4>
      <div class="funnel-stages">
        <div
          v-for="stage in month.stages"
          :key="stage.label"
          class="stage"
          :style="{ width: `${stage.percentage}%` }"
        >
          <span>{{ stage.label }}: {{ stage.value }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
```

---

### 4. Alertas Automáticas

```javascript
const analyzeMonth = (stat) => {
  const alerts = [];

  // Alerta de baja facturación
  if (stat.invoiced_percentage < 80) {
    alerts.push({
      type: 'warning',
      title: 'Facturación Baja',
      message: `Solo ${stat.invoiced_percentage}% de las atenciones están facturadas en ${stat.month_name}`,
      action: 'Revisar atenciones pendientes de facturar',
      count: stat.pending_admissions
    });
  }

  // Alerta de bajo cobro
  if (stat.paid_percentage < 70) {
    alerts.push({
      type: 'danger',
      title: 'Bajo Índice de Cobro',
      message: `Solo ${stat.paid_percentage}% de las facturas están pagadas en ${stat.month_name}`,
      action: 'Contactar con cuentas por cobrar',
      count: stat.unpaid_admissions
    });
  }

  // Alerta de facturas pendientes elevadas
  if (stat.unpaid_admissions > 100) {
    alerts.push({
      type: 'warning',
      title: 'Muchas Facturas Pendientes',
      message: `${stat.unpaid_admissions} facturas sin pagar en ${stat.month_name}`,
      action: 'Priorizar seguimiento de cobros',
      count: stat.unpaid_admissions
    });
  }

  return alerts;
};
```

---

## 🔄 Comparación: Todas las Métricas

### Vista Completa por Mes

```json
{
  "month": 2,
  "month_name": "Feb",

  // 📊 Grupo 1: Volumen
  "unique_patients": 531,          // Pacientes únicos
  "total_admissions": 612,         // Total de atenciones
  "total_amount": 272105.13,       // Monto total

  // 💰 Grupo 2: Facturación
  "invoiced_admissions": 580,      // Atenciones facturadas
  "pending_admissions": 32,        // Pendientes de facturar
  "invoiced_percentage": 94.77,    // % facturado

  // 💵 Grupo 3: Cobro
  "paid_admissions": 450,          // Atenciones pagadas
  "unpaid_admissions": 130,        // Facturas sin pagar
  "paid_percentage": 77.59,        // % pagado (del facturado)

  // 📈 Grupo 4: Promedios
  "avg_amount_per_admission": 444.62,      // Ticket promedio
  "avg_admissions_per_patient": 1.15,      // Atenciones por paciente
  "recurrence_rate": 15.25                  // % reincidencia
}
```

---

## 🎯 KPIs Recomendados

### KPI 1: Índice de Eficiencia Operativa (IEO)

```javascript
const IEO = (stat) => {
  return (stat.paid_admissions / stat.total_admissions) * 100;
};

// Interpretación:
// > 80% = Excelente
// 60-80% = Bueno
// < 60% = Necesita mejora
```

### KPI 2: Tiempo Promedio de Ciclo (estimado)

Si tienes las fechas, puedes estimar:
```javascript
const cycleTime = {
  admission_to_invoice: 'X días',
  invoice_to_payment: 'Y días',
  total_cycle: 'X + Y días'
};
```

### KPI 3: Índice de Salud Financiera (ISF)

```javascript
const ISF = (stat) => {
  const w1 = 0.4; // Peso facturación
  const w2 = 0.6; // Peso cobro

  return (stat.invoiced_percentage * w1) + (stat.paid_percentage * w2);
};

// Interpretación:
// > 85 = Excelente salud financiera
// 70-85 = Buena salud financiera
// < 70 = Requiere atención
```

---

## 📝 Ejemplo Completo de Análisis

```javascript
const monthlyAnalysis = (stats) => {
  const analysis = stats.map(stat => ({
    month: stat.month_name,

    // Volumen
    volume: {
      patients: stat.unique_patients,
      admissions: stat.total_admissions,
      revenue: stat.total_amount
    },

    // Eficiencia de facturación
    invoicing: {
      invoiced: stat.invoiced_admissions,
      pending: stat.pending_admissions,
      efficiency: stat.invoiced_percentage,
      status: stat.invoiced_percentage >= 90 ? 'Excelente' :
              stat.invoiced_percentage >= 80 ? 'Bueno' : 'Requiere mejora'
    },

    // Eficiencia de cobro
    collection: {
      paid: stat.paid_admissions,
      unpaid: stat.unpaid_admissions,
      efficiency: stat.paid_percentage,
      status: stat.paid_percentage >= 80 ? 'Excelente' :
              stat.paid_percentage >= 70 ? 'Bueno' : 'Requiere mejora'
    },

    // KPIs
    kpis: {
      operational_efficiency: (stat.paid_admissions / stat.total_admissions) * 100,
      financial_health: (stat.invoiced_percentage * 0.4) + (stat.paid_percentage * 0.6)
    }
  }));

  return analysis;
};
```

---

## 🚀 Cómo Obtener el Reporte

El reporte con las nuevas métricas está disponible inmediatamente con `aggregations_only: true`:

```javascript
const { data } = await axios.post('/api/dashboard/date-range-analysis', {
  start_date: '2025-02-01',
  end_date: '2025-11-20',
  aggregations_only: true
});

// ✅ Ahora incluye las 6 nuevas métricas de facturación/pago
console.log(data.monthly_statistics);
```

---

## 📚 Documentos Relacionados

1. `DASHBOARD_MONTHLY_STATISTICS_REPORT.md` - Documentación original
2. `MONTHLY_STATISTICS_OPTIMIZATION.md` - Detalles de optimización
3. `MONTHLY_STATISTICS_FINAL_SUMMARY.md` - Resumen completo

---

**Versión**: 3.0 (Con métricas de facturación y pago)
**Fecha**: 2025-01-20
**Estado**: ✅ Listo para usar
