<?php
// Script que arranca Laravel para inspeccionar doctor schedules y slots
// Uso: php scripts/debug_slots_laravel.php [doctor_id] [specialty_id] [date]

chdir(__DIR__ . '/..');
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DoctorSchedule;
use App\Models\Doctor;
use Carbon\Carbon;

$doctorId = $argv[1] ?? null;
$specialtyId = $argv[2] ?? null;
$dateArg = $argv[3] ?? null;

if (!$doctorId || !$specialtyId || !$dateArg) {
    // Mostrar algunas filas de doctor_schedules para inspección
    $schedules = DoctorSchedule::limit(20)->get();
    $out = [];
    foreach ($schedules as $s) {
        $out[] = [
            'id' => $s->id,
            'doctor_id' => $s->doctor_id,
            'specialty_id' => $s->specialty_id,
            'day_of_week' => $s->day_of_week,
            'start_time' => (string)$s->start_time,
            'end_time' => (string)$s->end_time,
            'appointment_duration' => $s->appointment_duration,
            'is_active' => (bool)$s->is_active,
        ];
    }
    echo json_encode(['sample_schedules' => $out], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit(0);
}

try {
    $date = Carbon::parse($dateArg);
} catch (Exception $e) {
    echo json_encode(['error' => 'Fecha inválida: ' . $e->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit(1);
}

$dayOfWeek = strtolower($date->format('l'));

$doctor = Doctor::find($doctorId);
if (!$doctor) {
    echo json_encode(['error' => 'Doctor no encontrado'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit(1);
}

$schedules = $doctor->schedules()->where('day_of_week', $dayOfWeek)->where('specialty_id', $specialtyId)->where('is_active', true)->get();

$allSlots = [];
$schedulesOut = [];
foreach ($schedules as $sch) {
    $slots = $sch->getAvailableTimeSlots();
    $allSlots = array_merge($allSlots, $slots);
    $schedulesOut[] = [
        'id' => $sch->id,
        'start_time' => (string)$sch->start_time,
        'end_time' => (string)$sch->end_time,
        'appointment_duration' => $sch->appointment_duration,
        'slots' => $slots,
    ];
}

$allSlots = array_values(array_unique($allSlots));
sort($allSlots);

$appts = $doctor->appointments()->whereDate('appointment_date', $date->toDateString())->where('specialty_id', $specialtyId)->whereNotIn('status', ['cancelada'])->get();
$booked = $appts->map(function($a) { return Carbon::parse($a->appointment_date)->format('H:i'); })->toArray();

$available = array_values(array_diff($allSlots, $booked));

if ($date->isToday()) {
    $nowTime = Carbon::now()->format('H:i');
    $available = array_values(array_filter($available, function($s) use ($nowTime) { return $s > $nowTime; }));
}

echo json_encode([
    'date' => $date->toDateString(),
    'day_of_week' => $dayOfWeek,
    'schedules' => $schedulesOut,
    'all_slots' => $allSlots,
    'booked_slots' => $booked,
    'available_slots' => $available,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
