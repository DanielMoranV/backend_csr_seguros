#!/bin/bash
set -e

PROJECT_DIR="/home/csr/Code/backend_csr_seguros"
cd "$PROJECT_DIR"

echo "=== CSR Seguros - Deploy ==="

# Construir e iniciar contenedores
echo "[1/4] Construyendo e iniciando contenedores..."
docker compose -f docker-compose.production.yml up -d --build

# Esperar a que MySQL este listo
echo "[2/4] Esperando MySQL..."
until docker exec seguros_mysql mysqladmin ping -h localhost --silent 2>/dev/null; do
  sleep 2
done
echo "MySQL listo."

# Esperar a que composer termine de instalar vendor/
echo "[3/4] Esperando que composer instale dependencias..."
until docker exec seguros_app test -f /var/www/html/vendor/autoload.php 2>/dev/null; do
  sleep 3
done
echo "Dependencias listas."

# Ejecutar migraciones
echo "[4/5] Ejecutando migraciones..."
docker exec seguros_app php artisan migrate --force

# Limpiar y optimizar cache
echo "[5/5] Optimizando Laravel..."
docker exec seguros_app php artisan config:cache
docker exec seguros_app php artisan route:cache
docker exec seguros_app php artisan view:cache

echo ""
echo "=== Deploy completado ==="
echo "API disponible en: http://localhost:8082"
echo "MySQL disponible en: localhost:3307"
