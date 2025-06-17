@echo off
REM Script de despliegue para Laravel en PRODUCCIÓN (Windows)

REM 1. Instalar dependencias de Composer (sin paquetes de desarrollo)
echo Instalando dependencias de Composer...
composer install --optimize-autoloader --no-dev

REM 2. Generar clave de aplicación (solo si no existe APP_KEY)
echo Generando clave de aplicación si es necesario...
php artisan key:generate

REM 3. Ejecutar migraciones (forzadas)
echo Ejecutando migraciones...
php artisan migrate --force

REM 4. Limpiar y cachear configuración, rutas y vistas
echo Cacheando configuración...
php artisan config:cache
echo Cacheando rutas...
php artisan route:cache
echo Cacheando vistas...
php artisan view:cache

REM 5. Limpiar cachés antiguos
echo Limpiando cachés antiguos...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

REM 6. Compilar assets front-end (si aplica)
if exist package.json (
    echo Instalando dependencias de NPM...
    call npm install
    echo Compilando assets front-end...
    call npm run build
) else (
    echo No se encontró package.json, omitiendo compilación de assets front-end.
)

echo --------------------------------------------
echo Despliegue completado. Revisa la salida por si hay errores.
echo --------------------------------------------
pause
