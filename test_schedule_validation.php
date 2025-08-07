<?php

/*
 * Script de prueba para demostrar el sistema de validación de horarios
 * Ejecutar con: php test_schedule_validation.php
 */

require_once 'vendor/autoload.php';

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\DoctorSchedule;

echo "=== SISTEMA DE VALIDACIÓN DE HORARIOS POR ESPECIALIDAD ===\n\n";

// 1. Mostrar doctores y sus especialidades
echo "1. DOCTORES Y SUS ESPECIALIDADES:\n";
echo str_repeat("-", 50) . "\n";

$doctors = Doctor::with('specialties', 'user')->get();
foreach ($doctors as $doctor) {
    $specialties = $doctor->specialties->pluck('name')->implode(', ');
    echo "• {$doctor->user->name}: {$specialties}\n";
}

echo "\n";

// 2. Mostrar horarios existentes
echo "2. HORARIOS EXISTENTES:\n";
echo str_repeat("-", 50) . "\n";

$schedules = DoctorSchedule::with(['doctor.user', 'specialty'])->get();
foreach ($schedules as $schedule) {
    $day = [
        'monday' => 'Lunes',
        'tuesday' => 'Martes',
        'wednesday' => 'Miércoles',
        'thursday' => 'Jueves',
        'friday' => 'Viernes',
        'saturday' => 'Sábado',
        'sunday' => 'Domingo'
    ][$schedule->day_of_week];
    
    echo "• {$schedule->doctor->user->name} - {$schedule->specialty->name}: ";
    echo "{$day} {$schedule->start_time} - {$schedule->end_time}\n";
}

echo "\n";

// 3. Probar validación de solapamiento
echo "3. PRUEBAS DE VALIDACIÓN:\n";
echo str_repeat("-", 50) . "\n";

$doctor = Doctor::whereHas('user', function($q) {
    $q->where('name', 'Dr. Juan Pérez');
})->first();

if ($doctor) {
    $pediatria = Specialty::where('name', 'Pediatría')->first();
    
    echo "Intentando crear horario CONFLICTIVO:\n";
    echo "Dr. Juan Pérez - Pediatría: Lunes 10:00-13:00\n";
    echo "(debería fallar porque se solapa con Cardiología 09:00-12:00)\n\n";
    
    $hasConflict = DoctorSchedule::hasScheduleConflict(
        $doctor->id,
        'monday',
        '10:00:00',
        '13:00:00'
    );
    
    if ($hasConflict) {
        echo "✅ VALIDACIÓN CORRECTA: Se detectó conflicto\n";
        
        $conflicts = DoctorSchedule::getConflictingSchedules(
            $doctor->id,
            'monday',
            '10:00:00',
            '13:00:00'
        );
        
        echo "Horarios en conflicto:\n";
        foreach ($conflicts as $conflict) {
            echo "  → {$conflict->specialty->name} ({$conflict->start_time} - {$conflict->end_time})\n";
        }
    } else {
        echo "❌ ERROR: No se detectó conflicto cuando debería haberlo\n";
    }
    
    echo "\n";
    
    echo "Intentando crear horario VÁLIDO:\n";
    echo "Dr. Juan Pérez - Pediatría: Martes 09:00-12:00\n";
    echo "(debería ser permitido)\n\n";
    
    $hasConflict2 = DoctorSchedule::hasScheduleConflict(
        $doctor->id,
        'tuesday',
        '09:00:00',
        '12:00:00'
    );
    
    if (!$hasConflict2) {
        echo "✅ VALIDACIÓN CORRECTA: No hay conflicto\n";
    } else {
        echo "❌ ERROR: Se detectó conflicto cuando no debería haberlo\n";
    }
}

echo "\n";

// 4. Estadísticas del sistema
echo "4. ESTADÍSTICAS DEL SISTEMA:\n";
echo str_repeat("-", 50) . "\n";

$totalSchedules = DoctorSchedule::count();
$activeSchedules = DoctorSchedule::where('is_active', true)->count();
$specialtiesWithSchedules = DoctorSchedule::distinct('specialty_id')->count();

echo "• Total de horarios: {$totalSchedules}\n";
echo "• Horarios activos: {$activeSchedules}\n";
echo "• Especialidades con horarios: {$specialtiesWithSchedules}\n";

echo "\n=== FIN DE PRUEBAS ===\n";
