<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Appointment;
use App\Http\Controllers\AppointmentController;

echo "Verificando permisos de edición como usuario 'administrador'...\n";

$admin = User::role('administrador')->first();
if (!$admin) {
    echo "No se encontró ningún usuario con rol 'administrador'.\n";
    exit(1);
}

Auth::loginUsingId($admin->id);
echo "Autenticado como: {$admin->email} (id={$admin->id})\n";

// Buscar una cita válida (futura o próxima)
$appointment = Appointment::whereNotIn('status', ['cancelada'])->orderBy('appointment_date', 'asc')->first();
if (!$appointment) {
    echo "No se encontró ninguna cita para probar.\n";
    exit(1);
}

echo "Cita seleccionada: id={$appointment->id}, fecha={$appointment->appointment_date}, status={$appointment->status}\n";

$controller = new AppointmentController();

// Llamar a apiShow (espera JSON con can_edit)
try {
    $res = $controller->apiShow($appointment);
    $content = null;
    if (method_exists($res, 'getData')) {
        $content = $res->getData(true);
    } elseif (method_exists($res, 'getContent')) {
        $content = json_decode($res->getContent(), true);
    }
    echo "apiShow response:\n";
    print_r($content);
} catch (\Throwable $e) {
    echo "apiShow lanzó excepción: " . $e->getMessage() . "\n";
}

// Intentar actualizar la cita (simular datos válidos)
$newDate = Carbon::parse($appointment->appointment_date)->addDays(3)->format('Y-m-d H:i');
$payload = [
    'patient_id' => $appointment->patient_id,
    'doctor_id' => $appointment->doctor_id,
    'specialty_id' => $appointment->specialty_id,
    'appointment_date' => $newDate,
    'duration' => $appointment->duration ?? 30,
    'reason' => $appointment->reason ?? 'Actualización automática',
    'reason_id' => $appointment->reason_id ?? null,
    'notes' => $appointment->notes ?? '',
    'status' => $appointment->status ?? 'programada',
];

$req = Request::create("/appointments/{$appointment->id}", 'PUT', $payload);

try {
    $updateRes = $controller->update($req, $appointment);
    echo "Update ejecutado. Tipo de respuesta: " . (is_object($updateRes) ? get_class($updateRes) : gettype($updateRes)) . "\n";
    if (is_object($updateRes) && method_exists($updateRes, 'getStatusCode')) {
        echo "Status code: " . $updateRes->getStatusCode() . "\n";
    }
    echo "Si no hubo excepción, la operación probablemente pasó las verificaciones de permisos.\n";
} catch (\Illuminate\Validation\ValidationException $ve) {
    echo "ValidationException: ";
    print_r($ve->errors());
} catch (\Throwable $e) {
    echo "Update lanzó excepción: " . $e->getMessage() . "\n";
}

echo "Verificación completa.\n";
