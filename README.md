# Backend Seguros Clínica Santa Rosa

## Descripción

Backend Seguros Clínica Santa Rosa es un sistema de gestión para el procesamiento y seguimiento de seguros médicos de la Clínica Santa Rosa. Esta API RESTful está desarrollada con Laravel y proporciona una interfaz robusta para la gestión de admisiones, historias médicas, facturación, liquidaciones y seguimiento de envíos relacionados con seguros médicos.

## Características Principales

- **Autenticación segura**: Implementación de JWT (JSON Web Tokens) para la autenticación de usuarios.
- **Gestión de usuarios**: Sistema de roles y permisos.
- **Gestión de aseguradoras**: Registro y administración de compañías aseguradoras.
- **Historias médicas**: Gestión completa de registros médicos y solicitudes.
- **Admisiones**: Control de admisiones de pacientes con seguros.
- **Facturación**: Sistema de facturación para servicios médicos cubiertos por seguros.
- **Liquidaciones**: Procesamiento de liquidaciones con aseguradoras.
- **Devoluciones**: Gestión de devoluciones y notas de crédito.
- **Auditoría**: Sistema de registro y seguimiento de auditorías.
- **Envíos**: Control de envíos de documentación a aseguradoras.

## Requisitos Técnicos

- PHP 8.2 o superior
- Composer
- MySQL 5.7 o superior
- Laravel 11.x
- Node.js y NPM (para la compilación de assets)

## Instalación

### 1. Clonar el repositorio

```bash
git clone [URL_DEL_REPOSITORIO]
cd backend_csr_seguros
```

### 2. Instalar dependencias de PHP

```bash
composer install
```

### 3. Configurar el entorno

Copiar el archivo de entorno de ejemplo y configurarlo:

```bash
cp .env.example .env
```

Editar el archivo `.env` con la configuración de tu base de datos y otras variables de entorno necesarias:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_base_datos
DB_USERNAME=usuario
DB_PASSWORD=contraseña

JWT_SECRET=tu_clave_secreta_jwt
```

### 4. Generar clave de aplicación y configurar JWT

```bash
php artisan key:generate
php artisan jwt:secret
```

### 5. Ejecutar migraciones y seeders

```bash
php artisan migrate
php artisan db:seed
```

### 6. Instalar dependencias de Node.js (opcional, si se requiere compilar assets)

```bash
npm install
npm run dev
```

### 7. Iniciar el servidor de desarrollo

```bash
php artisan serve
```

El servidor estará disponible en `http://localhost:8000`.

## Documentación de la API

La API sigue una estructura RESTful con los siguientes endpoints principales. Todos los endpoints requieren autenticación JWT excepto los de login y registro.

### Autenticación

| Método | Ruta | Descripción | Parámetros | Respuesta |
|--------|------|-------------|------------|----------|
| POST | `/api/auth/register` | Registrar nuevo usuario | `dni`, `name`, `email`, `password` | Token JWT |
| POST | `/api/auth/login` | Iniciar sesión | `dni`, `password` | Token JWT |
| POST | `/api/auth/logout` | Cerrar sesión | Token JWT | Mensaje de confirmación |
| POST | `/api/auth/refresh` | Renovar token | Token JWT | Nuevo token JWT |
| POST | `/api/auth/me` | Obtener información del usuario actual | Token JWT | Datos del usuario |

### Gestión de Usuarios

| Método | Ruta | Descripción | Parámetros | Respuesta |
|--------|------|-------------|------------|----------|
| GET | `/api/users` | Listar todos los usuarios | - | Colección de usuarios |
| GET | `/api/users/{id}` | Obtener un usuario específico | `id` | Datos del usuario |
| POST | `/api/users` | Crear un usuario | `dni`, `name`, `email`, `password`, `roles` | Usuario creado |
| PUT/PATCH | `/api/users/{id}` | Actualizar un usuario | `dni`, `name`, `email`, `password`, `roles` | Usuario actualizado |
| DELETE | `/api/users/{id}` | Eliminar un usuario | `id` | - |
| POST | `/api/users/store` | Crear múltiples usuarios | Array de usuarios | Usuarios creados |
| PATCH | `/api/users/{id}/restore` | Restaurar un usuario eliminado | `id` | Usuario restaurado |
| POST | `/api/users/{id}/photoprofile` | Subir foto de perfil | `photo` | URL de la foto |

### Gestión de Aseguradoras

| Método | Ruta | Descripción | Parámetros | Respuesta |
|--------|------|-------------|------------|----------|
| GET | `/api/insurers` | Listar todas las aseguradoras | - | Colección de aseguradoras |
| GET | `/api/insurers/{id}` | Obtener una aseguradora específica | `id` | Datos de la aseguradora |
| POST | `/api/insurers` | Crear una aseguradora | `name`, `ruc`, `address`, `phone`, `email` | Aseguradora creada |
| PUT/PATCH | `/api/insurers/{id}` | Actualizar una aseguradora | `name`, `ruc`, `address`, `phone`, `email` | Aseguradora actualizada |
| DELETE | `/api/insurers/{id}` | Eliminar una aseguradora | `id` | - |
| POST | `/api/insurers/store` | Crear múltiples aseguradoras | Array de aseguradoras | Aseguradoras creadas |
| PATCH | `/api/insurers/update` | Actualizar múltiples aseguradoras | Array de aseguradoras | Aseguradoras actualizadas |

### Gestión de Historias Médicas

| Método | Ruta | Descripción | Parámetros | Respuesta |
|--------|------|-------------|------------|----------|
| GET | `/api/medical-records` | Listar todas las historias médicas | - | Colección de historias médicas |
| GET | `/api/medical-records/{id}` | Obtener una historia médica específica | `id` | Datos de la historia médica |
| POST | `/api/medical-records` | Crear una historia médica | `number`, `patient_name`, `patient_dni` | Historia médica creada |
| PUT/PATCH | `/api/medical-records/{id}` | Actualizar una historia médica | `number`, `patient_name`, `patient_dni` | Historia médica actualizada |
| DELETE | `/api/medical-records/{id}` | Eliminar una historia médica | `id` | - |
| POST | `/api/medical-records/store` | Crear múltiples historias médicas | Array de historias médicas | Historias médicas creadas |
| PATCH | `/api/medical-records/update` | Actualizar múltiples historias médicas | Array de historias médicas | Historias médicas actualizadas |

### Gestión de Admisiones

| Método | Ruta | Descripción | Parámetros | Respuesta |
|--------|------|-------------|------------|----------|
| GET | `/api/admissions` | Listar todas las admisiones | - | Colección de admisiones |
| GET | `/api/admissions/{id}` | Obtener una admisión específica | `id` | Datos de la admisión |
| POST | `/api/admissions` | Crear una admisión | `number`, `attendance_date`, `attendance_hour`, `type`, `doctor`, `insurer_id`, `company`, `amount`, `patient`, `medical_record_id` | Admisión creada |
| PUT/PATCH | `/api/admissions/{id}` | Actualizar una admisión | `number`, `attendance_date`, `attendance_hour`, `type`, `doctor`, `insurer_id`, `company`, `amount`, `patient`, `medical_record_id` | Admisión actualizada |
| DELETE | `/api/admissions/{id}` | Eliminar una admisión | `id` | - |
| POST | `/api/admissions/store` | Crear múltiples admisiones | Array de admisiones | Admisiones creadas |
| PATCH | `/api/admissions/update` | Actualizar múltiples admisiones | Array de admisiones | Admisiones actualizadas |
| POST | `/api/admissions/date-range` | Obtener admisiones por rango de fechas | `start_date`, `end_date` | Colección de admisiones |
| GET | `/api/admissions/by-number/{number}` | Buscar admisión por número | `number` | Datos de la admisión |

### Gestión de Facturas

| Método | Ruta | Descripción | Parámetros | Respuesta |
|--------|------|-------------|------------|----------|
| GET | `/api/invoices` | Listar todas las facturas | - | Colección de facturas |
| GET | `/api/invoices/{id}` | Obtener una factura específica | `id` | Datos de la factura |
| POST | `/api/invoices` | Crear una factura | `number`, `date`, `biller`, `status`, `admission_id` | Factura creada |
| PUT/PATCH | `/api/invoices/{id}` | Actualizar una factura | `number`, `date`, `biller`, `status`, `admission_id` | Factura actualizada |
| DELETE | `/api/invoices/{id}` | Eliminar una factura | `id` | - |
| POST | `/api/invoices/store` | Crear múltiples facturas | Array de facturas | Facturas creadas |
| PATCH | `/api/invoices/update` | Actualizar múltiples facturas | Array de facturas | Facturas actualizadas |

### Gestión de Devoluciones

| Método | Ruta | Descripción | Parámetros | Respuesta |
|--------|------|-------------|------------|----------|
| GET | `/api/devolutions` | Listar todas las devoluciones | - | Colección de devoluciones |
| GET | `/api/devolutions/{id}` | Obtener una devolución específica | `id` | Datos de la devolución |
| POST | `/api/devolutions` | Crear una devolución | `date`, `reason`, `admission_id` | Devolución creada |
| PUT/PATCH | `/api/devolutions/{id}` | Actualizar una devolución | `date`, `reason`, `admission_id` | Devolución actualizada |
| DELETE | `/api/devolutions/{id}` | Eliminar una devolución | `id` | - |
| POST | `/api/devolutions/store` | Crear múltiples devoluciones | Array de devoluciones | Devoluciones creadas |
| PATCH | `/api/devolutions/update` | Actualizar múltiples devoluciones | Array de devoluciones | Devoluciones actualizadas |

### Gestión de Liquidaciones

| Método | Ruta | Descripción | Parámetros | Respuesta |
|--------|------|-------------|------------|----------|
| GET | `/api/settlements` | Listar todas las liquidaciones | - | Colección de liquidaciones |
| GET | `/api/settlements/{id}` | Obtener una liquidación específica | `id` | Datos de la liquidación |
| POST | `/api/settlements` | Crear una liquidación | `period`, `date`, `biller`, `amount`, `admission_id` | Liquidación creada |
| PUT/PATCH | `/api/settlements/{id}` | Actualizar una liquidación | `period`, `date`, `biller`, `amount`, `admission_id` | Liquidación actualizada |
| DELETE | `/api/settlements/{id}` | Eliminar una liquidación | `id` | - |
| POST | `/api/settlements/store` | Crear múltiples liquidaciones | Array de liquidaciones | Liquidaciones creadas |
| PATCH | `/api/settlements/update` | Actualizar múltiples liquidaciones | Array de liquidaciones | Liquidaciones actualizadas |

### Gestión de Listas de Admisiones

| Método | Ruta | Descripción | Parámetros | Respuesta |
|--------|------|-------------|------------|----------|
| GET | `/api/admissions-lists` | Listar todas las listas de admisiones | - | Colección de listas |
| GET | `/api/admissions-lists/{id}` | Obtener una lista específica | `id` | Datos de la lista |
| POST | `/api/admissions-lists` | Crear una lista | `period`, `admission_id`, `status` | Lista creada |
| PUT/PATCH | `/api/admissions-lists/{id}` | Actualizar una lista | `period`, `admission_id`, `status` | Lista actualizada |
| DELETE | `/api/admissions-lists/{id}` | Eliminar una lista | `id` | - |
| POST | `/api/admissions-lists/store` | Crear múltiples listas | Array de listas | Listas creadas |
| PATCH | `/api/admissions-lists/update` | Actualizar múltiples listas | Array de listas | Listas actualizadas |
| POST | `/api/admissions-lists/create-admission-list-and-request` | Crear lista y solicitud | `period`, `admissions` | Lista y solicitud creadas |
| GET | `/api/admissions-lists/periods` | Obtener todos los periodos | - | Lista de periodos |
| GET | `/api/admissions-lists/by-period/{period}` | Obtener listas por periodo | `period` | Colección de listas |

### Gestión de Auditorías

| Método | Ruta | Descripción | Parámetros | Respuesta |
|--------|------|-------------|------------|----------|
| GET | `/api/audits` | Listar todas las auditorías | - | Colección de auditorías |
| GET | `/api/audits/{id}` | Obtener una auditoría específica | `id` | Datos de la auditoría |
| POST | `/api/audits` | Crear una auditoría | `date`, `observations`, `admission_id` | Auditoría creada |
| PUT/PATCH | `/api/audits/{id}` | Actualizar una auditoría | `date`, `observations`, `admission_id` | Auditoría actualizada |
| DELETE | `/api/audits/{id}` | Eliminar una auditoría | `id` | - |
| POST | `/api/audits/store` | Crear múltiples auditorías | Array de auditorías | Auditorías creadas |
| PATCH | `/api/audits/update` | Actualizar múltiples auditorías | Array de auditorías | Auditorías actualizadas |
| POST | `/api/audits/by-admissions` | Obtener auditorías por admisiones | `admission_ids` | Colección de auditorías |
| POST | `/api/audits/by-date-range` | Obtener auditorías por rango de fechas | `start_date`, `end_date` | Colección de auditorías |

### Gestión de Solicitudes de Historias Médicas

| Método | Ruta | Descripción | Parámetros | Respuesta |
|--------|------|-------------|------------|----------|
| GET | `/api/medical-records-requests` | Listar todas las solicitudes | - | Colección de solicitudes |
| GET | `/api/medical-records-requests/{id}` | Obtener una solicitud específica | `id` | Datos de la solicitud |
| POST | `/api/medical-records-requests` | Crear una solicitud | `date`, `status`, `medical_record_id` | Solicitud creada |
| PUT/PATCH | `/api/medical-records-requests/{id}` | Actualizar una solicitud | `date`, `status`, `medical_record_id` | Solicitud actualizada |
| DELETE | `/api/medical-records-requests/{id}` | Eliminar una solicitud | `id` | - |
| POST | `/api/medical-records-requests/store` | Crear múltiples solicitudes | Array de solicitudes | Solicitudes creadas |
| PATCH | `/api/medical-records-requests/update` | Actualizar múltiples solicitudes | Array de solicitudes | Solicitudes actualizadas |
| POST | `/api/medical-records-requests/by-date-range` | Obtener solicitudes por rango de fechas | `start_date`, `end_date` | Colección de solicitudes |
| GET | `/api/medical-records-requests/by-medical-record-number/{number}` | Buscar solicitudes por número de historia | `number` | Colección de solicitudes |

### Gestión de Envíos

| Método | Ruta | Descripción | Parámetros | Respuesta |
|--------|------|-------------|------------|----------|
| GET | `/api/shipments` | Listar todos los envíos | - | Colección de envíos |
| GET | `/api/shipments/{id}` | Obtener un envío específico | `id` | Datos del envío |
| POST | `/api/shipments` | Crear un envío | `date`, `status`, `admission_id` | Envío creado |
| PUT/PATCH | `/api/shipments/{id}` | Actualizar un envío | `date`, `status`, `admission_id` | Envío actualizado |
| DELETE | `/api/shipments/{id}` | Eliminar un envío | `id` | - |
| POST | `/api/shipments/store` | Crear múltiples envíos | Array de envíos | Envíos creados |
| PATCH | `/api/shipments/update` | Actualizar múltiples envíos | Array de envíos | Envíos actualizados |
| POST | `/api/shipments/create-and-update` | Crear y actualizar envíos | `shipments` | Envíos procesados |
| POST | `/api/shipments/by-date-range` | Obtener envíos por rango de fechas | `start_date`, `end_date` | Colección de envíos |
| GET | `/api/shipments/by-admission-number/{admissionNumber}` | Buscar envíos por número de admisión | `admissionNumber` | Colección de envíos |
| POST | `/api/shipments/by-admissions-list` | Obtener envíos por lista de admisiones | `admissions_list_ids` | Colección de envíos |

### Consultas Personalizadas

| Método | Ruta | Descripción | Parámetros | Respuesta |
|--------|------|-------------|------------|----------|
| POST | `/api/excequte_query` | Ejecutar consulta personalizada | `query` | Resultados de la consulta |
| POST | `/api/admissions_by_date_range` | Obtener admisiones por rango de fechas | `start_date`, `end_date` | Colección de admisiones |

## Autenticación

El sistema utiliza JWT para la autenticación. Para obtener un token:

```
POST /api/auth/login
{
  "dni": "12345678",
  "password": "contraseña"
}
```

El token debe incluirse en las cabeceras de las solicitudes subsiguientes:

```
Authorization: Bearer {token}
```

## Roles y Permisos

El sistema implementa dos roles principales:

- **dev**: Acceso completo a todas las funcionalidades del sistema.
- **admin**: Acceso administrativo con restricciones específicas.

## Mantenimiento

### Actualización del sistema

```bash
git pull
composer install
php artisan migrate
```

### Limpieza de caché

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Soporte

Para soporte técnico, contactar al equipo de desarrollo en [mailto:soportetic.csr@gmail.com].

## Licencia

Este proyecto es propiedad de Clínica Santa Rosa y su uso está restringido según los términos acordados.
