# Sistema de Gestión de Horarios por Especialidad

## Características Implementadas

### 1. **Horarios por Especialidad**
- Cada horario debe estar asociado a una especialidad específica
- Un doctor puede tener múltiples horarios para diferentes especialidades
- La tabla `doctor_schedules` ahora incluye `specialty_id` como campo obligatorio

### 2. **Validación de Solapamientos**
- **Prevención de conflictos**: No se permite que un doctor tenga horarios solapados en el mismo día, independientemente de la especialidad
- **Validación en tiempo real**: El sistema verifica automáticamente los conflictos al crear o editar horarios
- **Mensajes informativos**: Muestra qué especialidades y horarios están en conflicto

### 3. **Componente Livewire Mejorado**
- Interfaz completa para gestión de horarios
- Filtros por doctor
- Formulario modal para crear/editar horarios
- Validación en tiempo real
- Gestión de estados (activo/inactivo)

## Estructura de Base de Datos

### Tabla `doctor_schedules`
```sql
- id (PRIMARY KEY)
- doctor_id (FOREIGN KEY -> doctors.id)
- specialty_id (FOREIGN KEY -> specialties.id) 
- day_of_week (ENUM: monday, tuesday, wednesday, thursday, friday, saturday, sunday)
- start_time (TIME)
- end_time (TIME)
- appointment_duration (INTEGER, default: 30 minutos)
- is_active (BOOLEAN, default: true)
- created_at, updated_at (TIMESTAMPS)

ÍNDICES:
- doctor_id, day_of_week, is_active
- doctor_id, specialty_id, day_of_week
- UNIQUE: doctor_id, specialty_id, day_of_week, start_time, end_time
```

## Reglas de Negocio

### 1. **Validación de Especialidades**
- Un doctor solo puede crear horarios para especialidades que tenga asignadas
- Se verifica la relación doctor-especialidad antes de guardar

### 2. **Prevención de Solapamientos**
- **Misma especialidad**: No puede haber dos horarios que se solapen para la misma especialidad en el mismo día
- **Diferentes especialidades**: Un doctor no puede atender dos especialidades al mismo tiempo
- **Ejemplo**: Si Dr. Juan tiene Cardiología de 9:00-12:00, no puede tener Pediatría de 10:00-13:00 el mismo día

### 3. **Validaciones de Horarios**
- Hora de fin debe ser posterior a hora de inicio
- Duración de citas entre 15-120 minutos
- Horarios solo en días válidos de la semana

## Casos de Uso

### Caso 1: Doctor con Multiple Especialidades
```
Dr. Juan Pérez - Cardiología y Pediatría

✅ PERMITIDO:
- Lunes: Cardiología 09:00-12:00
- Lunes: Pediatría 14:00-17:00

❌ NO PERMITIDO:
- Lunes: Cardiología 09:00-12:00
- Lunes: Pediatría 11:00-14:00 (se solapa 11:00-12:00)
```

### Caso 2: Múltiples Doctores, Misma Especialidad
```
✅ PERMITIDO:
- Dr. Juan: Pediatría Lunes 09:00-12:00
- Dra. Ana: Pediatría Lunes 09:00-12:00 (diferentes doctores)
```

## Cómo Probar el Sistema

### 1. Acceder a la Gestión de Horarios
```
URL: http://localhost:8000/doctor-schedules-management
Requisitos: Usuario con rol "administrador" o "medico"
```

### 2. Crear Horario Válido
1. Seleccionar doctor
2. Seleccionar especialidad (solo las asignadas al doctor)
3. Configurar día, horario y duración
4. Guardar

### 3. Probar Validación de Solapamiento
1. Crear un horario para Dr. Juan Pérez - Cardiología - Lunes 09:00-12:00
2. Intentar crear otro horario para Dr. Juan Pérez - Pediatría - Lunes 10:00-13:00
3. El sistema debe mostrar error: "El horario se solapa con: Cardiología (09:00 - 12:00)"

### 4. Probar Validación de Especialidades
1. Intentar asignar una especialidad que el doctor no tiene
2. El sistema debe mostrar error: "El doctor no tiene asignada esta especialidad"

## Datos de Prueba Incluidos

### Doctores con Especialidades:
- **Dr. Juan Pérez**: Cardiología, Pediatría
- **Dra. Ana López**: Pediatría  
- **Dr. Carlos Rodríguez**: Medicina General
- **Dra. Laura Martínez**: Ginecología
- **Dra. María López**: Traumatología, Medicina Interna
- **Dra. Ana García**: Cardiología, Traumatología

### Horarios de Prueba Creados:
- Dr. Juan Pérez - Cardiología: Lunes 09:00-12:00
- Dr. Juan Pérez - Pediatría: Lunes 14:00-17:00

## Archivos Modificados/Creados

### Modelos:
- `app/Models/DoctorSchedule.php` - Actualizado con specialty_id y validaciones
- `app/Models/Doctor.php` - Método para horarios por especialidad

### Componentes Livewire:
- `app/Livewire/DoctorSchedule.php` - Componente completo para gestión
- `resources/views/livewire/doctor-schedule.blade.php` - Vista del componente

### Migraciones:
- `database/migrations/2025_08_07_020121_create_doctor_schedules_table.php` - Tabla con specialty_id

### Vistas:
- `resources/views/doctor-schedules-management.blade.php` - Página principal

### Rutas:
- `routes/web.php` - Ruta para gestión de horarios

## Próximos Pasos Sugeridos

1. **Integración con Sistema de Citas**: Usar los horarios para generar slots disponibles
2. **Notificaciones**: Alertar cuando se intenta crear horarios conflictivos
3. **Reportes**: Dashboard con estadísticas de horarios por especialidad
4. **API REST**: Endpoints para aplicaciones móviles
5. **Calendarios Visuales**: Interfaz tipo calendario para mejor UX
