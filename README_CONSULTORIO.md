# Sistema de Gestión de Consultorio Médico

## Descripción
Sistema completo de gestión para consultorios médicos desarrollado con Laravel 12, Vue.js 3, Livewire y Tailwind CSS.

## Características Principales

### Roles y Permisos
- **Administrador**: Acceso completo al sistema
- **Médico**: Gestión de citas, pacientes y historiales médicos
- **Recepcionista**: Gestión de citas y pacientes
- **Paciente**: Visualización de citas e historial médico

### Funcionalidades

#### 1. Autenticación y Autorización
- Sistema de login/logout seguro con Laravel Breeze
- Gestión de roles y permisos con Spatie Laravel Permission
- Middleware personalizado para control de acceso

#### 2. Dashboard Personalizado
- Dashboard diferente según el rol del usuario
- Estadísticas relevantes para cada tipo de usuario
- Vista de citas del día y próximas actividades

#### 3. Gestión de Citas
- Crear, editar, cancelar y completar citas
- Filtros por fecha, doctor, estado y paciente
- Estados: programada, confirmada, completada, cancelada, no asistió
- Interfaz dinámica con Livewire

#### 4. Gestión de Pacientes
- Registro completo de pacientes
- Información médica: alergias, condiciones, medicamentos
- Datos de contacto de emergencia
- Información de seguro médico

#### 5. Gestión de Doctores
- Perfiles de doctores con especialidades
- Horarios de trabajo configurables
- Tarifas de consulta
- Años de experiencia y biografía

#### 6. Especialidades Médicas
- Catálogo de especialidades médicas
- Asignación de doctores a especialidades

#### 7. Historiales Médicos
- Registro detallado de consultas
- Diagnósticos y tratamientos
- Prescripciones médicas
- Signos vitales

## Tecnologías Utilizadas

### Backend
- **Laravel 12**: Framework PHP
- **MySQL/SQLite**: Base de datos
- **Spatie Laravel Permission**: Gestión de roles y permisos
- **Livewire 3**: Componentes dinámicos del lado del servidor

### Frontend
- **Vue.js 3**: Framework JavaScript
- **Inertia.js**: Comunicación SPA entre Laravel y Vue
- **Tailwind CSS**: Framework CSS
- **Vite**: Herramienta de build

## Estructura de la Base de Datos

### Tablas Principales
- `users`: Usuarios del sistema
- `doctors`: Información específica de doctores
- `patients`: Información específica de pacientes
- `specialties`: Especialidades médicas
- `appointments`: Citas médicas
- `medical_records`: Historiales médicos
- `roles` y `permissions`: Gestión de roles (Spatie)

### Relaciones
- Un usuario puede ser doctor, paciente, o ambos
- Los doctores tienen una especialidad
- Las citas relacionan pacientes con doctores
- Los historiales médicos se vinculan a citas específicas

## Instalación y Configuración

### Requisitos
- PHP 8.2+
- Composer
- Node.js y npm
- MySQL o SQLite

### Pasos de Instalación
1. Clonar el repositorio
2. Instalar dependencias PHP: `composer install`
3. Instalar dependencias JS: `npm install`
4. Configurar base de datos en `.env`
5. Ejecutar migraciones y seeders: `php artisan migrate:fresh --seed`
6. Compilar assets: `npm run build`
7. Iniciar servidor: `php artisan serve`

## Usuarios de Prueba

### Administrador
- Email: admin@consultorio.com
- Password: password

### Recepcionista
- Email: recepcion@consultorio.com
- Password: password

### Doctores
- Email: juan.perez@consultorio.com (Cardiólogo)
- Email: ana.lopez@consultorio.com (Pediatra)
- Email: carlos.rodriguez@consultorio.com (Medicina General)
- Email: laura.martinez@consultorio.com (Ginecóloga)
- Password: password (para todos)

### Pacientes
- Email: pedro.jimenez@email.com
- Email: maria.fernandez@email.com
- Email: jose.garcia@email.com
- Email: carmen.silva@email.com
- Email: luis.morales@email.com
- Password: password (para todos)

## Funcionalidades Implementadas

### ✅ Completadas
- Sistema de autenticación con roles
- Dashboard personalizado por rol
- Gestión básica de citas (CRUD)
- Modelos y migraciones completas
- Seeders con datos de prueba
- Navegación dinámica por roles
- Componente Livewire para citas
- Estructura de base de datos completa

### 🚧 En Desarrollo
- Gestión completa de pacientes
- Gestión de historiales médicos
- Componentes adicionales de Livewire
- Validaciones avanzadas
- Reportes y estadísticas
- Notificaciones
- Exportación de datos

## Arquitectura del Sistema

### Patrón MVC
- **Modelos**: Eloquent ORM para interacción con base de datos
- **Vistas**: Vue.js con Inertia.js para SPA
- **Controladores**: Lógica de negocio y API endpoints

### Componentes Livewire
- Interactividad del lado del servidor
- Actualizaciones en tiempo real
- Validación dinámica de formularios

### Middleware de Seguridad
- Verificación de roles y permisos
- Protección de rutas sensibles
- Manejo de errores 403

## Contribución
Este proyecto es un sistema base que puede ser extendido según las necesidades específicas del consultorio médico.

## Licencia
MIT License
