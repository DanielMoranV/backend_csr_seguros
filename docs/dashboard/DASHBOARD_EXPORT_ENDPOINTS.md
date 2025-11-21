# Guía de Endpoints de Exportación a Excel - Dashboard

## Tabla de Contenidos
- [Resumen Ejecutivo](#resumen-ejecutivo)
- [Endpoints Disponibles](#endpoints-disponibles)
- [Endpoint 1: Exportar Análisis por Rango de Fechas](#endpoint-1-exportar-análisis-por-rango-de-fechas)
- [Endpoint 2: Exportar Análisis por Periodo](#endpoint-2-exportar-análisis-por-periodo)
- [Implementación en el Frontend](#implementación-en-el-frontend)
- [Manejo de Errores](#manejo-de-errores)
- [Estructura del Archivo Excel](#estructura-del-archivo-excel)
- [Ejemplos de Uso](#ejemplos-de-uso)
- [Preguntas Frecuentes](#preguntas-frecuentes)

---

## Resumen Ejecutivo

Los endpoints de exportación permiten descargar los datos del dashboard en formato Excel (.xlsx) para análisis offline, presentaciones o respaldos. Ambos endpoints generan archivos Excel con todas las admisiones del periodo solicitado, incluyendo información detallada de facturación, pagos, envíos y devoluciones.

**Características principales:**
- ✅ Exportación directa a archivo Excel (.xlsx)
- ✅ Datos completamente procesados y enriquecidos
- ✅ Nombres de archivo descriptivos con fechas/periodo
- ✅ Validación de entrada automática
- ✅ Manejo de errores robusto
- ✅ Sin límite de registros (incluye todas las admisiones del periodo)

---

## Endpoints Disponibles

| Endpoint | Método | Descripción | Autenticación |
|----------|--------|-------------|---------------|
| `/api/dashboard/date-range-analysis/export` | POST | Exporta análisis por rango de fechas a Excel | Requerida (`auth:api`) |
| `/api/dashboard/period-analysis/export` | POST | Exporta análisis por periodo (YYYYMM) a Excel | Requerida (`auth:api`) |

**Base URL**: `https://tu-dominio.com/api`

---

## Endpoint 1: Exportar Análisis por Rango de Fechas

### Información General

**URL**: `POST /api/dashboard/date-range-analysis/export`

**Propósito**: Genera y descarga un archivo Excel con todas las admisiones dentro del rango de fechas especificado.

### Request

#### Headers
```http
Authorization: Bearer {access_token}
Content-Type: application/json
Accept: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
```

#### Body Parameters

| Parámetro | Tipo | Requerido | Formato | Validaciones | Descripción |
|-----------|------|-----------|---------|--------------|-------------|
| `start_date` | string | ✅ Sí | `YYYY-MM-DD` | • Formato válido<br>• Anterior o igual a `end_date`<br>• No puede ser futura | Fecha de inicio del periodo |
| `end_date` | string | ✅ Sí | `YYYY-MM-DD` | • Formato válido<br>• Posterior o igual a `start_date`<br>• No puede ser futura<br>• Rango máximo: 1 año | Fecha fin del periodo |

#### Ejemplo de Request

```json
{
  "start_date": "2025-01-01",
  "end_date": "2025-01-31"
}
```

### Response

#### Success Response (200 OK)

**Tipo de respuesta**: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` (archivo binario)

**Headers de respuesta**:
```http
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Disposition: attachment; filename="analisis_rango_2025-01-01_2025-01-31.xlsx"
Content-Length: 245678
```

**Nombre del archivo**: `analisis_rango_{start_date}_{end_date}.xlsx`

**Ejemplo**: `analisis_rango_2025-01-01_2025-01-31.xlsx`

#### Error Responses

##### Validación Fallida (422 Unprocessable Entity)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "start_date": [
      "La fecha de inicio debe ser anterior o igual a la fecha fin"
    ],
    "end_date": [
      "El rango de fechas no puede ser mayor a 1 año"
    ]
  }
}
```

##### Error del Servidor (500 Internal Server Error)
```http
HTTP/1.1 500 Internal Server Error

Error al generar el archivo Excel: [mensaje de error detallado]
```

---

## Endpoint 2: Exportar Análisis por Periodo

### Información General

**URL**: `POST /api/dashboard/period-analysis/export`

**Propósito**: Genera y descarga un archivo Excel con todas las admisiones del periodo especificado (formato YYYYMM), incluyendo información detallada de auditores, facturadores y estados de procesamiento.

### Request

#### Headers
```http
Authorization: Bearer {access_token}
Content-Type: application/json
Accept: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
```

#### Body Parameters

| Parámetro | Tipo | Requerido | Formato | Validaciones | Descripción |
|-----------|------|-----------|---------|--------------|-------------|
| `period` | string | ✅ Sí | `YYYY` o `YYYYMM` | • 4 o 6 dígitos<br>• Año: 2020-2039<br>• Mes: 01-12 (si se especifica) | Periodo a exportar |

#### Ejemplos de Request

**Periodo mensual** (más común):
```json
{
  "period": "202501"
}
```

**Periodo anual**:
```json
{
  "period": "2025"
}
```

### Response

#### Success Response (200 OK)

**Tipo de respuesta**: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` (archivo binario)

**Headers de respuesta**:
```http
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Disposition: attachment; filename="analisis_periodo_202501.xlsx"
Content-Length: 318945
```

**Nombre del archivo**: `analisis_periodo_{period}.xlsx`

**Ejemplos**:
- `analisis_periodo_202501.xlsx` (enero 2025)
- `analisis_periodo_2025.xlsx` (año completo)

#### Error Responses

##### Validación Fallida (422 Unprocessable Entity)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "period": [
      "El formato del periodo es inválido. Use YYYY o YYYYMM."
    ]
  }
}
```

##### Error del Servidor (500 Internal Server Error)
```http
HTTP/1.1 500 Internal Server Error

Error al generar el archivo Excel: [mensaje de error detallado]
```

---

## Implementación en el Frontend

### Opción 1: Usando Axios (Recomendado)

#### Service / Composable

```javascript
// services/dashboardExportService.js
import axios from 'axios';
import { saveAs } from 'file-saver'; // npm install file-saver

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

/**
 * Exportar análisis por rango de fechas a Excel
 * @param {Object} params - Parámetros de exportación
 * @param {string} params.start_date - Fecha inicio (YYYY-MM-DD)
 * @param {string} params.end_date - Fecha fin (YYYY-MM-DD)
 * @returns {Promise<void>}
 */
export const exportDateRangeAnalysis = async ({ start_date, end_date }) => {
  try {
    const response = await axios.post(
      `${API_BASE_URL}/dashboard/date-range-analysis/export`,
      { start_date, end_date },
      {
        responseType: 'blob', // Importante para archivos binarios
        headers: {
          'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        }
      }
    );

    // Extraer nombre del archivo de los headers
    const contentDisposition = response.headers['content-disposition'];
    let filename = `analisis_rango_${start_date}_${end_date}.xlsx`;

    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename="(.+)"/);
      if (filenameMatch?.[1]) {
        filename = filenameMatch[1];
      }
    }

    // Descargar archivo
    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    });
    saveAs(blob, filename);

    return { success: true, filename };

  } catch (error) {
    console.error('Error al exportar análisis por rango de fechas:', error);
    throw error;
  }
};

/**
 * Exportar análisis por periodo a Excel
 * @param {Object} params - Parámetros de exportación
 * @param {string} params.period - Periodo (YYYY o YYYYMM)
 * @returns {Promise<void>}
 */
export const exportPeriodAnalysis = async ({ period }) => {
  try {
    const response = await axios.post(
      `${API_BASE_URL}/dashboard/period-analysis/export`,
      { period },
      {
        responseType: 'blob',
        headers: {
          'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        }
      }
    );

    // Extraer nombre del archivo
    const contentDisposition = response.headers['content-disposition'];
    let filename = `analisis_periodo_${period}.xlsx`;

    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename="(.+)"/);
      if (filenameMatch?.[1]) {
        filename = filenameMatch[1];
      }
    }

    // Descargar archivo
    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    });
    saveAs(blob, filename);

    return { success: true, filename };

  } catch (error) {
    console.error('Error al exportar análisis por periodo:', error);
    throw error;
  }
};
```

#### Uso en Componente Vue 3 (Composition API)

```vue
<script setup>
import { ref } from 'vue';
import { exportDateRangeAnalysis, exportPeriodAnalysis } from '@/services/dashboardExportService';
import { toast } from '@/utils/toast'; // Tu sistema de notificaciones

const isExporting = ref(false);
const dateRange = ref({
  start_date: '2025-01-01',
  end_date: '2025-01-31'
});
const period = ref('202501');

/**
 * Exportar por rango de fechas
 */
const handleExportDateRange = async () => {
  if (!dateRange.value.start_date || !dateRange.value.end_date) {
    toast.error('Por favor selecciona un rango de fechas válido');
    return;
  }

  isExporting.value = true;

  try {
    const { filename } = await exportDateRangeAnalysis(dateRange.value);

    toast.success(`Archivo ${filename} descargado exitosamente`);

  } catch (error) {
    if (error.response?.status === 422) {
      // Error de validación
      const errors = error.response.data.errors;
      const errorMessages = Object.values(errors).flat().join('\n');
      toast.error(`Error de validación:\n${errorMessages}`);
    } else if (error.response?.status === 500) {
      // Error del servidor
      toast.error('Error al generar el archivo. Por favor intenta nuevamente.');
    } else {
      toast.error('Error de conexión. Verifica tu internet.');
    }
  } finally {
    isExporting.value = false;
  }
};

/**
 * Exportar por periodo
 */
const handleExportPeriod = async () => {
  if (!period.value) {
    toast.error('Por favor selecciona un periodo válido');
    return;
  }

  isExporting.value = true;

  try {
    const { filename } = await exportPeriodAnalysis({ period: period.value });

    toast.success(`Archivo ${filename} descargado exitosamente`);

  } catch (error) {
    if (error.response?.status === 422) {
      const errors = error.response.data.errors;
      const errorMessages = Object.values(errors).flat().join('\n');
      toast.error(`Error de validación:\n${errorMessages}`);
    } else if (error.response?.status === 500) {
      toast.error('Error al generar el archivo. Por favor intenta nuevamente.');
    } else {
      toast.error('Error de conexión. Verifica tu internet.');
    }
  } finally {
    isExporting.value = false;
  }
};
</script>

<template>
  <div class="dashboard-export">
    <!-- Exportar por Rango de Fechas -->
    <div class="export-section">
      <h3>Exportar por Rango de Fechas</h3>

      <div class="date-inputs">
        <input
          v-model="dateRange.start_date"
          type="date"
          placeholder="Fecha inicio"
        />
        <input
          v-model="dateRange.end_date"
          type="date"
          placeholder="Fecha fin"
        />
      </div>

      <button
        @click="handleExportDateRange"
        :disabled="isExporting"
        class="btn-export"
      >
        <span v-if="!isExporting">📊 Exportar a Excel</span>
        <span v-else>⏳ Generando archivo...</span>
      </button>
    </div>

    <!-- Exportar por Periodo -->
    <div class="export-section">
      <h3>Exportar por Periodo</h3>

      <input
        v-model="period"
        type="text"
        placeholder="YYYYMM (ej: 202501)"
        maxlength="6"
      />

      <button
        @click="handleExportPeriod"
        :disabled="isExporting"
        class="btn-export"
      >
        <span v-if="!isExporting">📊 Exportar a Excel</span>
        <span v-else>⏳ Generando archivo...</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.dashboard-export {
  display: flex;
  gap: 2rem;
  padding: 1rem;
}

.export-section {
  flex: 1;
  padding: 1rem;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
}

.date-inputs {
  display: flex;
  gap: 1rem;
  margin: 1rem 0;
}

input {
  padding: 0.5rem;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.btn-export {
  padding: 0.75rem 1.5rem;
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  transition: background-color 0.3s;
}

.btn-export:hover:not(:disabled) {
  background-color: #45a049;
}

.btn-export:disabled {
  background-color: #cccccc;
  cursor: not-allowed;
}
</style>
```

---

### Opción 2: Sin file-saver (Usando URL.createObjectURL)

Si no quieres instalar `file-saver`, puedes usar la API nativa del navegador:

```javascript
/**
 * Descargar archivo usando API nativa del navegador
 */
const downloadBlob = (blob, filename) => {
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', filename);
  document.body.appendChild(link);
  link.click();
  link.parentNode.removeChild(link);
  window.URL.revokeObjectURL(url);
};

// Uso en el servicio
export const exportDateRangeAnalysis = async ({ start_date, end_date }) => {
  try {
    const response = await axios.post(
      `${API_BASE_URL}/dashboard/date-range-analysis/export`,
      { start_date, end_date },
      { responseType: 'blob' }
    );

    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    });

    const filename = `analisis_rango_${start_date}_${end_date}.xlsx`;
    downloadBlob(blob, filename);

    return { success: true, filename };
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
};
```

---

### Opción 3: Usando fetch API nativo

```javascript
/**
 * Exportar usando fetch API nativo
 */
export const exportDateRangeAnalysis = async ({ start_date, end_date }) => {
  try {
    const token = localStorage.getItem('access_token'); // Ajusta según tu auth

    const response = await fetch(
      `${API_BASE_URL}/dashboard/date-range-analysis/export`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        },
        body: JSON.stringify({ start_date, end_date })
      }
    );

    if (!response.ok) {
      if (response.status === 422) {
        const errorData = await response.json();
        throw new Error(JSON.stringify(errorData.errors));
      }
      throw new Error(`Error HTTP: ${response.status}`);
    }

    const blob = await response.blob();
    const filename = `analisis_rango_${start_date}_${end_date}.xlsx`;

    // Descargar
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);

    return { success: true, filename };

  } catch (error) {
    console.error('Error al exportar:', error);
    throw error;
  }
};
```

---

## Manejo de Errores

### Estrategia Recomendada

```javascript
import { ref } from 'vue';

const exportError = ref(null);
const isExporting = ref(false);

const handleExport = async (exportFunction, params) => {
  exportError.value = null;
  isExporting.value = true;

  try {
    const result = await exportFunction(params);

    // Mostrar mensaje de éxito
    showSuccessToast(`Archivo ${result.filename} descargado correctamente`);

    return result;

  } catch (error) {
    // Error de validación (422)
    if (error.response?.status === 422) {
      const validationErrors = error.response.data.errors;
      exportError.value = {
        type: 'validation',
        message: 'Error de validación en los datos enviados',
        details: validationErrors
      };

      // Mostrar errores específicos
      Object.entries(validationErrors).forEach(([field, messages]) => {
        messages.forEach(msg => showErrorToast(msg));
      });
    }

    // Error del servidor (500)
    else if (error.response?.status === 500) {
      exportError.value = {
        type: 'server',
        message: 'Error al generar el archivo Excel en el servidor',
        details: error.response.data
      };

      showErrorToast('Error en el servidor. Por favor contacta al administrador.');
    }

    // Error de autenticación (401)
    else if (error.response?.status === 401) {
      exportError.value = {
        type: 'auth',
        message: 'No estás autenticado o tu sesión expiró'
      };

      showErrorToast('Por favor inicia sesión nuevamente');
      // Redirigir al login
      router.push('/login');
    }

    // Error de red
    else if (error.request) {
      exportError.value = {
        type: 'network',
        message: 'Error de conexión. Verifica tu internet.'
      };

      showErrorToast('No se pudo conectar con el servidor');
    }

    // Otro error
    else {
      exportError.value = {
        type: 'unknown',
        message: error.message || 'Error desconocido'
      };

      showErrorToast('Ocurrió un error inesperado');
    }

    throw error;

  } finally {
    isExporting.value = false;
  }
};
```

### Mensajes de Error para el Usuario

| Código | Tipo de Error | Mensaje Sugerido | Acción Recomendada |
|--------|---------------|------------------|-------------------|
| 422 | Validación | "Los datos ingresados no son válidos. Por favor revisa: [detalles]" | Mostrar errores específicos por campo |
| 500 | Servidor | "Error al generar el archivo. Por favor intenta nuevamente en unos minutos." | Ofrecer reintento o contactar soporte |
| 401 | Autenticación | "Tu sesión ha expirado. Por favor inicia sesión nuevamente." | Redirigir al login |
| 403 | Autorización | "No tienes permisos para exportar estos datos." | Contactar administrador |
| 0 | Red | "Error de conexión. Verifica tu internet y vuelve a intentar." | Verificar conexión |

---

## Estructura del Archivo Excel

### Columnas Incluidas

Ambos endpoints generan archivos Excel con las siguientes columnas (puede variar según la implementación específica de las clases Export):

| Columna | Tipo | Descripción | Ejemplo |
|---------|------|-------------|---------|
| Número de Admisión | Texto | Número único de la admisión | "2025010001" |
| Fecha de Atención | Fecha | Fecha en que se realizó la atención | "2025-01-15" |
| Paciente | Texto | Nombre del paciente | "Juan Pérez García" |
| Tipo de Atención | Texto | Tipo de atención médica | "EMERGENCIA" |
| Monto | Número | Monto de la atención | 450.50 |
| Aseguradora | Texto | Nombre de la aseguradora | "MAPFRE" |
| Empresa | Texto | Empresa o EPS del paciente | "EPS MAPFRE SALUD" |
| Doctor | Texto | Médico que atendió | "Dr. Smith" |
| Número de Factura | Texto | Número de la factura generada | "001-00123" |
| Fecha de Factura | Fecha | Fecha de emisión de la factura | "2025-01-16" |
| Facturador | Texto | Usuario que generó la factura | "María López" |
| Fecha de Devolución | Fecha | Fecha de devolución (si aplica) | "2025-01-20" |
| Factura Pagada | Texto | Número de factura de pago | "001-00123" |
| Estado | Texto | Estado actual de la admisión | "Pagado" / "Liquidado" / "Pendiente" |
| Historia Clínica | Texto | Número de historia clínica | "HC-2025-001" |
| Cerrado | Booleano | Si la admisión está cerrada | "Sí" / "No" |

### Columnas Adicionales en Exportación por Periodo

| Columna | Tipo | Descripción | Ejemplo |
|---------|------|-------------|---------|
| Periodo | Texto | Periodo de la admisión | "202501" |
| Auditor | Texto | Auditor asignado | "Dr. García" |
| Estado Auditor | Texto | Estado del proceso de auditoría | "AUDITADO" / "PAGADO" / "DEVOLUCION" |
| Estado Facturador | Texto | Estado del proceso de facturación | "FACTURADO" / "ENVIADO" / "PAGADO" / "DEVOLUCION" |
| Fecha de Envío Verificado | Fecha | Fecha de verificación de envío | "2025-01-18" |

### Formato del Archivo

- **Formato**: Excel 2007+ (.xlsx)
- **Hoja**: 1 hoja con todos los datos
- **Encabezados**: Primera fila con nombres de columnas
- **Formato de fechas**: `YYYY-MM-DD`
- **Formato de montos**: Numérico con 2 decimales
- **Codificación**: UTF-8

---

## Ejemplos de Uso

### Ejemplo 1: Exportar mes completo

```javascript
// Exportar todo el mes de enero 2025
const result = await exportDateRangeAnalysis({
  start_date: '2025-01-01',
  end_date: '2025-01-31'
});

console.log(`Archivo descargado: ${result.filename}`);
// Output: "Archivo descargado: analisis_rango_2025-01-01_2025-01-31.xlsx"
```

### Ejemplo 2: Exportar trimestre

```javascript
// Exportar primer trimestre 2025
const result = await exportDateRangeAnalysis({
  start_date: '2025-01-01',
  end_date: '2025-03-31'
});
```

### Ejemplo 3: Exportar periodo específico

```javascript
// Exportar enero 2025 con datos de auditores y facturadores
const result = await exportPeriodAnalysis({
  period: '202501'
});

console.log(`Archivo descargado: ${result.filename}`);
// Output: "Archivo descargado: analisis_periodo_202501.xlsx"
```

### Ejemplo 4: Exportar con validación previa

```javascript
const validateAndExport = async (startDate, endDate) => {
  // Validaciones del lado del cliente
  if (!startDate || !endDate) {
    throw new Error('Ambas fechas son requeridas');
  }

  const start = new Date(startDate);
  const end = new Date(endDate);

  if (start > end) {
    throw new Error('La fecha de inicio debe ser anterior a la fecha fin');
  }

  const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
  if (diffDays > 365) {
    throw new Error('El rango no puede ser mayor a 1 año');
  }

  // Si pasa las validaciones, exportar
  return await exportDateRangeAnalysis({
    start_date: startDate,
    end_date: endDate
  });
};
```

### Ejemplo 5: Botón con indicador de progreso

```vue
<script setup>
import { ref } from 'vue';
import { exportDateRangeAnalysis } from '@/services/dashboardExportService';

const isExporting = ref(false);
const exportProgress = ref(0);

const handleExport = async () => {
  isExporting.value = true;
  exportProgress.value = 0;

  // Simular progreso (opcional)
  const progressInterval = setInterval(() => {
    if (exportProgress.value < 90) {
      exportProgress.value += 10;
    }
  }, 200);

  try {
    const result = await exportDateRangeAnalysis({
      start_date: '2025-01-01',
      end_date: '2025-01-31'
    });

    exportProgress.value = 100;

    setTimeout(() => {
      alert(`Archivo ${result.filename} descargado exitosamente`);
    }, 500);

  } catch (error) {
    console.error('Error:', error);
    alert('Error al exportar');
  } finally {
    clearInterval(progressInterval);
    isExporting.value = false;
    exportProgress.value = 0;
  }
};
</script>

<template>
  <div>
    <button @click="handleExport" :disabled="isExporting">
      <span v-if="!isExporting">Exportar a Excel</span>
      <span v-else>Exportando... {{ exportProgress }}%</span>
    </button>

    <div v-if="isExporting" class="progress-bar">
      <div
        class="progress-fill"
        :style="{ width: `${exportProgress}%` }"
      ></div>
    </div>
  </div>
</template>
```

---

## Preguntas Frecuentes

### ¿Cuál es el límite de registros que se pueden exportar?

No hay límite establecido. El endpoint exportará todas las admisiones que cumplan con el criterio de búsqueda (rango de fechas o periodo). Sin embargo, para rangos muy grandes (más de 10,000 registros), la generación del archivo puede tomar más tiempo.

### ¿Puedo exportar múltiples periodos a la vez?

No. Cada request solo puede exportar un rango de fechas o un periodo a la vez. Si necesitas exportar múltiples periodos, debes hacer múltiples llamadas al endpoint.

### ¿El archivo se guarda en el servidor?

No. El archivo se genera en memoria y se envía directamente al navegador. No se almacena en el servidor.

### ¿Puedo personalizar las columnas del Excel?

Desde el frontend no es posible personalizar las columnas. La estructura del Excel está definida en las clases `DateRangeAnalysisExport` y `PeriodAnalysisExport` del backend. Si necesitas columnas adicionales, contacta al equipo de backend.

### ¿Qué pasa si el rango de fechas no tiene datos?

El endpoint generará un archivo Excel válido, pero solo contendrá los encabezados sin filas de datos.

### ¿Cuánto tiempo tarda en generar el archivo?

Depende de la cantidad de datos:
- Menos de 1,000 registros: 1-3 segundos
- 1,000 - 5,000 registros: 3-10 segundos
- Más de 5,000 registros: 10-30 segundos

### ¿Puedo cancelar la descarga?

Sí, puedes usar `AbortController` con Axios o fetch API para cancelar la request:

```javascript
const controller = new AbortController();

const exportWithCancel = async () => {
  try {
    const response = await axios.post(
      '/api/dashboard/date-range-analysis/export',
      { start_date: '2025-01-01', end_date: '2025-01-31' },
      {
        responseType: 'blob',
        signal: controller.signal
      }
    );
    // ... resto del código
  } catch (error) {
    if (error.name === 'CanceledError') {
      console.log('Exportación cancelada');
    }
  }
};

// Para cancelar:
controller.abort();
```

### ¿Los datos del Excel son los mismos que muestra el dashboard?

Sí, pero con información más detallada. El dashboard muestra agregaciones y resúmenes, mientras que el Excel contiene todas las admisiones individuales con todos sus campos.

### ¿Puedo abrir el archivo en Google Sheets?

Sí, los archivos .xlsx son compatibles con Google Sheets, Microsoft Excel, LibreOffice Calc y otras aplicaciones de hojas de cálculo.

### ¿El endpoint respeta los permisos de usuario?

Sí, el endpoint requiere autenticación (`auth:api`) y verifica que el usuario tenga los roles necesarios (`dev|admin`) según la configuración en `routes/api.php`.

---

## Referencias Técnicas

### Backend
- **Controlador**: `app/Http/Controllers/DashboardController.php`
- **Request Validation**:
  - `app/Http/Requests/DateRangeAnalysisRequest.php`
  - `app/Http/Requests/PeriodAnalysisRequest.php`
- **Servicios**: `app/Services/DashboardService.php`
- **Exports**:
  - `app/Exports/DateRangeAnalysisExport.php`
  - `app/Exports/PeriodAnalysisExport.php`
- **Rutas**: `routes/api.php` (líneas 181-182)

### Paquetes Utilizados
- **Laravel Excel**: [maatwebsite/excel](https://laravel-excel.com/)
- **PhpSpreadsheet**: Biblioteca subyacente para generación de Excel

### Documentación Relacionada
- [Documentación completa del Dashboard API](./DASHBOARD_API_SPECS.md)
- [Guía de implementación Laravel](./DASHBOARD_API_SPECS.md#implementación-detallada)

---

## Soporte

Para preguntas o problemas con estos endpoints:
- **Backend Team**: Para dudas sobre implementación del servidor
- **Frontend Team**: Para dudas sobre integración en el cliente
- **Repositorio**: [GitHub/csr_frontend_seguros](./)

---

**Versión**: 1.0
**Fecha**: 2025-01-20
**Autor**: Backend Team
