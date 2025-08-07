<?php

require_once 'vendor/autoload.php';

use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== VERIFICACIÓN DE DATOS PARA PEDIATRÍA ===\n\n";

// Buscar la especialidad de Pediatría
$pediatria = Specialty::where('name', 'LIKE', '%pediatr%')->orWhere('name', 'LIKE', '%Pediatr%')->first();

if (!$pediatria) {
    echo "❌ No se encontró la especialidad de Pediatría\n";
    echo "Especialidades disponibles:\n";
    $specialties = Specialty::all();
    foreach ($specialties as $specialty) {
        echo "- ID: {$specialty->id}, Nombre: {$specialty->name}\n";
    }
    exit;
}

echo "✅ Especialidad encontrada: {$pediatria->name} (ID: {$pediatria->id})\n\n";

// Buscar doctores que tengan esta especialidad
$doctors = Doctor::whereHas('specialties', function ($query) use ($pediatria) {
    $query->where('specialties.id', $pediatria->id);
})->with(['specialties', 'schedules' => function ($query) {
    $query->where('is_active', true);
}, 'user'])->get();

echo "🩺 Doctores con especialidad en {$pediatria->name}: {$doctors->count()}\n\n";

foreach ($doctors as $doctor) {
    echo "👨‍⚕️ Doctor: {$doctor->user->name}\n";
    echo "   - ID: {$doctor->id}\n";
    echo "   - Especialidades: " . $doctor->specialties->pluck('name')->join(', ') . "\n";
    echo "   - Horarios activos:\n";
    
    foreach ($doctor->schedules as $schedule) {
        echo "     * {$schedule->day_of_week}: {$schedule->start_time} - {$schedule->end_time} (activo: " . ($schedule->is_active ? 'Sí' : 'No') . ")\n";
    }
    echo "\n";
}

// Simular el algoritmo del controlador
$availableDays = [];
foreach ($doctors as $doctor) {
    foreach ($doctor->schedules as $schedule) {
        if (!in_array($schedule->day_of_week, $availableDays)) {
            $availableDays[] = $schedule->day_of_week;
        }
    }
}

echo "📅 Días disponibles encontrados:\n";
foreach ($availableDays as $day) {
    echo "- {$day}\n";
}

// Convertir a números
$dayNumbers = [];
foreach ($availableDays as $day) {
    $dayNumber = match($day) {
        'sunday' => 0,
        'monday' => 1,
        'tuesday' => 2,
        'wednesday' => 3,
        'thursday' => 4,
        'friday' => 5,
        'saturday' => 6,
        default => null,
    };
    
    if ($dayNumber !== null) {
        $dayNumbers[] = $dayNumber;
    }
}

echo "\n🔢 Números de días para JavaScript: " . implode(', ', $dayNumbers) . "\n";
echo "   (0=Domingo, 1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves, 5=Viernes, 6=Sábado)\n";
