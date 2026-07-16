# Administrador de Tareas — Backend (API REST)

API REST construida con **Laravel 12** para la gestión de tareas por usuario, con autenticación mediante **JWT** (`tymon/jwt-auth`).

Este proyecto es **independiente** del frontend: no renderiza vistas, solo expone endpoints JSON consumidos por el proyecto `frontend/` (Laravel + Inertia + Vue 3) u cualquier otro cliente (Postman, apps móviles, etc.).

---

## Requisitos

- PHP >= 8.2
- Composer >= 2.x
- MySQL >= 8.0
- Extensiones de PHP: `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `ctype`, `json`

---

## Pasos de instalación

```bash
# 1. Clonar el repositorio
git clone https://github.com/juanmendozaca-netizen/admin-tareas-backend.git backend
cd backend

# 2. Instalar dependencias
composer install

# 3. Copiar el archivo de entorno
cp .env.example .env

# 4. Generar la key de la aplicación
php artisan key:generate

# 5. Configurar la base de datos en el archivo .env
#    (ver sección "Variables de entorno necesarias")

# 6. Crear la base de datos en MySQL
mysql -u root -p -e "CREATE DATABASE task_manager_backend;"

# 7. Instalar el paquete de JWT (si no viene ya en composer.json, este paso es informativo)
composer require tymon/jwt-auth

# 8. Publicar la configuración de JWT
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"

# 9. Generar el secret de JWT
php artisan jwt:secret

# 10. Ejecutar las migraciones
php artisan migrate

# 11. (Opcional) Poblar datos de prueba
php artisan tinker
>>> $user = App\Models\User::factory()->create(['email' => 'demo@demo.com', 'password' => 'password']);
>>> $user->tasks()->saveMany(App\Models\Task::factory(10)->make());
>>> exit

# 12. Levantar el servidor
php artisan serve
```

El backend quedará disponible en `http://localhost:8000`.

---

## Variables de entorno necesarias

| Variable | Descripción | Ejemplo |
|---|---|---|
| `APP_NAME` | Nombre de la aplicación | `TaskManagerAPI` |
| `APP_ENV` | Entorno de ejecución | `local` |
| `APP_KEY` | Key generada por `php artisan key:generate` | *(auto-generada)* |
| `APP_DEBUG` | Modo debug | `true` (solo en desarrollo) |
| `APP_URL` | URL base del backend | `http://localhost:8000` |
| `DB_CONNECTION` | Driver de base de datos | `mysql` |
| `DB_HOST` | Host de MySQL | `127.0.0.1` |
| `DB_PORT` | Puerto de MySQL | `3306` |
| `DB_DATABASE` | Nombre de la base de datos | `task_manager_backend` |
| `DB_USERNAME` | Usuario de MySQL | `root` |
| `DB_PASSWORD` | Contraseña de MySQL | *(según tu entorno)* |
| `JWT_SECRET` | Secret usado para firmar los tokens JWT | *(auto-generada por `jwt:secret`)* |
| `JWT_TTL` | Tiempo de vida del token en minutos | `60` |

---

## Cómo ejecutar el servicio

```bash
php artisan serve
```

Para correr las migraciones desde cero (borrando datos existentes):

```bash
php artisan migrate:fresh
```

Para ejecutar en un puerto distinto:

```bash
php artisan serve --port=8001
```

---

## Endpoints disponibles

### Autenticación

| Método | Endpoint | Protegido | Descripción |
|---|---|---|---|
| POST | `/api/auth/register` | No | Registrar un nuevo usuario |
| POST | `/api/auth/login` | No | Iniciar sesión, devuelve token JWT |
| POST | `/api/auth/logout` | Sí | Cerrar sesión (invalida el token) |
| GET | `/api/auth/me` | Sí | Obtener datos del usuario autenticado |

### Tareas

| Método | Endpoint | Protegido | Descripción |
|---|---|---|---|
| GET | `/api/tasks` | Sí | Listar tareas del usuario (filtros: `status`, `priority`, `search`) |
| POST | `/api/tasks` | Sí | Crear una tarea |
| PUT | `/api/tasks/{id}` | Sí | Editar una tarea (bloqueado si está `done`) |
| PATCH | `/api/tasks/{id}/status` | Sí | Cambiar el estado de una tarea |
| DELETE | `/api/tasks/{id}` | Sí | Eliminar una tarea |

Ejemplos de filtros combinables:
```
GET /api/tasks?status=pending
GET /api/tasks?priority=high
GET /api/tasks?search=laravel
GET /api/tasks?status=pending&priority=high&search=laravel
```

### Dashboard

| Método | Endpoint | Protegido | Descripción |
|---|---|---|---|
| GET | `/api/dashboard` | Sí | Métricas: total, pendientes, en progreso, completadas |

**Autenticación:** todos los endpoints protegidos requieren el header:
```
Authorization: Bearer {token}
```

---


## Estructura del proyecto (carpetas relevantes)

```
app/
├── Http/
│   ├── Controllers/    # AuthController, TaskController, DashboardController
│   │                   # (reciben la petición, delegan al Service, devuelven la respuesta)
│   ├── Requests/       # Form Requests: validación de datos de entrada
│   │                   # (RegisterRequest, LoginRequest, StoreTaskRequest,
│   │                   #  UpdateTaskRequest, UpdateTaskStatusRequest)
│   └── Resources/      # UserResource, TaskResource
│                       # (controlan exactamente qué campos se exponen en el JSON)
├── Models/             # User, Task (representan las tablas de la base de datos)
├── Services/           # TaskService (toda la lógica de negocio: filtros,
│                       #  regla de "done no editable", métricas del dashboard)
└── Exceptions/         # TaskAlreadyDoneException (excepción de dominio)

database/
├── migrations/         # create_users_table, create_tasks_table


routes/
└── api.php             # Todos los endpoints de la API, agrupados con auth:api
```






## Decisiones Técnicas

1. **Arquitectura Controller → Service → Model (Service Layer Pattern).**
   Toda la lógica de negocio (filtros de tareas, regla de "tarea completada no editable", cálculo de métricas del dashboard) vive en `App\Services\TaskService`, no en los controladores. Esto mantiene los controladores delgados (solo reciben la petición, delegan y responden), facilita las pruebas unitarias del servicio de forma aislada, y evita duplicar lógica entre `TaskController` y `DashboardController`, que comparten la misma instancia de `TaskService`.

2. **Regla de negocio "una tarea `done` no puede editarse" aplicada solo a edición de contenido, no al cambio de estado.**
   Se interpretó que el endpoint `PUT /api/tasks/{id}` (edición de `title`, `description`, `priority`, `due_date`) debe bloquearse cuando la tarea está completada, lanzando una excepción de dominio (`TaskAlreadyDoneException`) que el controlador traduce a un `422 Unprocessable Entity`. Sin embargo, el endpoint `PATCH /api/tasks/{id}/status` sí permite cambiar el estado de una tarea `done` (por ejemplo, reabrirla a `pending`), ya que de lo contrario una tarea completada quedaría permanentemente congelada sin posibilidad de corrección, lo cual no es realista en un sistema de gestión de tareas.

3. **Dashboard con una única consulta agregada en lugar de múltiples `COUNT()`.**
   El endpoint `/api/dashboard` obtiene las cuatro métricas con una sola consulta SQL (`SELECT status, COUNT(*) ... GROUP BY status`) en vez de ejecutar cuatro consultas separadas (una por cada estado). Esto reduce la carga a la base de datos, especialmente relevante si el número de tareas por usuario crece.

4. **Autenticación stateless con JWT y logout mediante blacklist.**
   Al no usar sesiones de servidor, el "logout" no elimina una sesión sino que invalida el token actual agregándolo a la blacklist de `tymon/jwt-auth` (`JWT_BLACKLIST_ENABLED=true` por defecto). Esto es coherente con el carácter stateless de JWT y evita que un token robado o filtrado después del logout siga siendo válido hasta su expiración natural.




