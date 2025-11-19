-- ============================================================================
-- SQL OPTIMIZATION EXAMPLES - Dashboard CSR Seguros
-- ============================================================================
-- Este archivo contiene ejemplos de las optimizaciones implementadas
-- y queries para verificar el rendimiento
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. VERIFICAR VERSIÓN DE MYSQL (Debe ser >= 8.0 para Window Functions)
-- ----------------------------------------------------------------------------
SELECT VERSION();
-- Resultado esperado: 8.1.x


-- ----------------------------------------------------------------------------
-- 2. VERIFICAR ÍNDICES CREADOS
-- ----------------------------------------------------------------------------

-- Ver índices en shipments
SHOW INDEX FROM shipments;
-- Buscar: idx_shipments_invoice_verified

-- Ver índices en audits
SHOW INDEX FROM audits;
-- Buscar: idx_audits_admission

-- Ver índices en admissions_lists
SHOW INDEX FROM admissions_lists;
-- Buscar: idx_admissions_lists_period_admission, idx_admissions_lists_admission


-- ----------------------------------------------------------------------------
-- 3. EJEMPLO DE DEDUPLICACIÓN CON WINDOW FUNCTIONS
-- ----------------------------------------------------------------------------

-- ANTES (traía duplicados, se procesaban en PHP):
SELECT
    SC0011.num_doc,
    SC0017.num_fac,
    SC0017.fec_fac
FROM SC0011
LEFT JOIN SC0017 ON SC0011.num_doc = SC0017.num_doc
WHERE SC0011.fec_doc BETWEEN '2024-01-01' AND '2024-12-31'
ORDER BY SC0011.num_doc, SC0017.fec_fac DESC;
-- Resultado: Potencialmente duplicados si una admisión tiene múltiples facturas


-- AHORA (deduplicación en MySQL con ROW_NUMBER()):
SELECT *
FROM (
    SELECT
        SC0011.num_doc,
        SC0017.num_fac,
        SC0017.fec_fac,
        ROW_NUMBER() OVER (
            PARTITION BY SC0011.num_doc
            ORDER BY SC0017.fec_fac DESC
        ) as row_num
    FROM SC0011
    LEFT JOIN SC0017 ON SC0011.num_doc = SC0017.num_doc
    WHERE SC0011.fec_doc BETWEEN '2024-01-01' AND '2024-12-31'
) as deduplicated
WHERE row_num = 1;
-- Resultado: Una sola fila por admisión (la factura más reciente)


-- ----------------------------------------------------------------------------
-- 4. EJEMPLO DE AGREGACIONES EN MYSQL (en lugar de PHP)
-- ----------------------------------------------------------------------------

-- Estado de facturación por mes
SELECT
    MONTH(SC0011.fec_doc) as month,
    SUM(CASE
        WHEN SC0017.num_fac IS NULL
            OR SC0017.num_fac LIKE '005-%'
            OR SC0017.num_fac LIKE '006-%'
        THEN 1 ELSE 0
    END) as pending_count,
    SUM(CASE
        WHEN SC0017.num_fac IS NOT NULL
            AND SC0017.num_fac NOT LIKE '005-%'
            AND SC0017.num_fac NOT LIKE '006-%'
        THEN 1 ELSE 0
    END) as invoiced_count,
    SUM(CASE
        WHEN SC0017.num_fac IS NULL
            OR SC0017.num_fac LIKE '005-%'
            OR SC0017.num_fac LIKE '006-%'
        THEN SC0011.tot_doc ELSE 0
    END) as pending_amount,
    SUM(CASE
        WHEN SC0017.num_fac IS NOT NULL
            AND SC0017.num_fac NOT LIKE '005-%'
            AND SC0017.num_fac NOT LIKE '006-%'
        THEN SC0011.tot_doc ELSE 0
    END) as invoiced_amount
FROM SC0011
LEFT JOIN SC0017 ON SC0011.num_doc = SC0017.num_doc
LEFT JOIN SC0002 ON LEFT(SC0011.cod_emp, 2) = SC0002.cod_cia
WHERE SC0011.fec_doc BETWEEN '2024-01-01' AND '2024-12-31'
    AND SC0011.tot_doc >= 0
    AND SC0011.nom_pac != ''
    AND SC0011.nom_pac != 'No existe...'
    AND SC0002.nom_cia NOT IN ('PARTICULAR', 'PACIENTES PARTICULARES')
GROUP BY MONTH(SC0011.fec_doc)
ORDER BY month;


-- ----------------------------------------------------------------------------
-- 5. COMPARACIÓN DE RENDIMIENTO (ejecutar con EXPLAIN)
-- ----------------------------------------------------------------------------

-- Query NO optimizado (sin índices, con procesamiento PHP):
EXPLAIN
SELECT
    s.invoice_number,
    s.verified_shipment_date
FROM shipments s
WHERE s.invoice_number IN (
    SELECT SC0017.num_fac
    FROM SC0011
    LEFT JOIN SC0017 ON SC0011.num_doc = SC0017.num_doc
    WHERE SC0011.fec_doc BETWEEN '2024-01-01' AND '2024-12-31'
)
AND s.verified_shipment_date IS NOT NULL;
-- Sin índice: Type = ALL (full table scan)


-- Query optimizado (con índice idx_shipments_invoice_verified):
EXPLAIN
SELECT
    s.invoice_number,
    s.verified_shipment_date
FROM shipments s
WHERE s.invoice_number IN ('001-123', '001-124', '001-125')
    AND s.verified_shipment_date IS NOT NULL;
-- Con índice: Type = ref o range (index scan)


-- ----------------------------------------------------------------------------
-- 6. ESTADÍSTICAS DE TABLAS
-- ----------------------------------------------------------------------------

-- Contar admisiones por año
SELECT
    YEAR(fec_doc) as year,
    COUNT(*) as total_admissions
FROM SC0011
WHERE tot_doc >= 0
    AND nom_pac != ''
    AND nom_pac != 'No existe...'
GROUP BY YEAR(fec_doc)
ORDER BY year DESC;


-- Contar facturas por año
SELECT
    YEAR(fec_fac) as year,
    COUNT(*) as total_invoices
FROM SC0017
WHERE num_fac IS NOT NULL
GROUP BY YEAR(fec_fac)
ORDER BY year DESC;


-- Verificar duplicados por admisión
SELECT
    num_doc,
    COUNT(*) as invoice_count
FROM SC0017
GROUP BY num_doc
HAVING COUNT(*) > 1
ORDER BY invoice_count DESC
LIMIT 10;


-- ----------------------------------------------------------------------------
-- 7. QUERIES DE DIAGNÓSTICO
-- ----------------------------------------------------------------------------

-- Verificar tamaño de tablas
SELECT
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)",
    table_rows
FROM information_schema.TABLES
WHERE table_schema = DATABASE()
    AND table_name IN ('SC0011', 'SC0017', 'SC0022', 'SC0033', 'SC0002', 'SC0003')
ORDER BY (data_length + index_length) DESC;


-- Verificar uso de índices (ejecutar después de usar la aplicación)
SHOW STATUS LIKE 'Handler_read%';
-- Handler_read_rnd_next alto = muchos full table scans


-- ----------------------------------------------------------------------------
-- 8. BENCHMARK MANUAL
-- ----------------------------------------------------------------------------

-- Test 1: Agregaciones directas en MySQL (debería ser muy rápido)
SET @start_time = NOW(6);

SELECT
    COUNT(*) as total,
    SUM(tot_doc) as total_amount,
    COUNT(DISTINCT cod_pac) as unique_patients,
    AVG(tot_doc) as avg_amount
FROM SC0011
LEFT JOIN SC0002 ON LEFT(SC0011.cod_emp, 2) = SC0002.cod_cia
WHERE SC0011.fec_doc BETWEEN '2024-01-01' AND '2024-12-31'
    AND SC0011.tot_doc >= 0
    AND SC0011.nom_pac != ''
    AND SC0002.nom_cia NOT IN ('PARTICULAR', 'PACIENTES PARTICULARES');

SET @end_time = NOW(6);
SELECT TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000 as execution_time_ms;


-- Test 2: Deduplicación con Window Functions
SET @start_time = NOW(6);

SELECT COUNT(*) FROM (
    SELECT *
    FROM (
        SELECT
            SC0011.num_doc,
            ROW_NUMBER() OVER (
                PARTITION BY SC0011.num_doc
                ORDER BY SC0017.fec_fac DESC
            ) as row_num
        FROM SC0011
        LEFT JOIN SC0017 ON SC0011.num_doc = SC0017.num_doc
        WHERE SC0011.fec_doc BETWEEN '2024-01-01' AND '2024-12-31'
    ) as deduplicated
    WHERE row_num = 1
) as final_count;

SET @end_time = NOW(6);
SELECT TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000 as execution_time_ms;


-- ----------------------------------------------------------------------------
-- 9. ÍNDICES ADICIONALES RECOMENDADOS (SI TIENES ACCESO A LA DB LEGACY)
-- ----------------------------------------------------------------------------

-- ⚠️ ADVERTENCIA: Solo ejecutar si tienes permisos en la base de datos legacy
-- Estos índices NO están incluidos en la migración porque no podemos modificar
-- las tablas legacy, pero si tuvieras acceso, estos mejorarían aún más:

/*
-- Para filtros de fecha
CREATE INDEX idx_sc0011_fec_doc ON SC0011(fec_doc, tot_doc);

-- Para JOINs más rápidos
CREATE INDEX idx_sc0017_num_doc_fac ON SC0017(num_doc, num_fac, fec_fac);
CREATE INDEX idx_sc0022_num_fac ON SC0022(num_fac);
CREATE INDEX idx_sc0033_num_doc ON SC0033(num_doc);
CREATE INDEX idx_sc0003_cod_emp ON SC0003(cod_emp);
CREATE INDEX idx_sc0002_cod_cia ON SC0002(cod_cia, nom_cia);

-- Para filtrar empresas
CREATE INDEX idx_sc0011_cod_emp ON SC0011(cod_emp);
*/


-- ----------------------------------------------------------------------------
-- 10. VERIFICAR PLAN DE EJECUCIÓN DE QUERIES PRINCIPALES
-- ----------------------------------------------------------------------------

-- Verificar plan de ejecución para aggregations
EXPLAIN FORMAT=JSON
SELECT
    MONTH(SC0011.fec_doc) as month,
    COUNT(*) as total
FROM SC0011
LEFT JOIN SC0002 ON LEFT(SC0011.cod_emp, 2) = SC0002.cod_cia
WHERE SC0011.fec_doc BETWEEN '2024-01-01' AND '2024-12-31'
    AND SC0011.tot_doc >= 0
    AND SC0002.nom_cia NOT IN ('PARTICULAR', 'PACIENTES PARTICULARES')
GROUP BY MONTH(SC0011.fec_doc);

-- Buscar en el resultado:
-- - "access_type": Debería ser "range" o mejor
-- - "rows_examined": Debería ser lo más bajo posible
-- - "filtered": Debería ser cercano a 100


-- ----------------------------------------------------------------------------
-- FIN DEL ARCHIVO
-- ============================================================================
-- Para más información, consultar: DASHBOARD_OPTIMIZATION_GUIDE.md
-- ============================================================================
