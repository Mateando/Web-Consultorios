<?php
// Boot Laravel and call AppointmentController@show for debugging
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();


use App\Http\Controllers\AppointmentController;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

$id = $argv[1] ?? null;
if (!$id) {
    echo "Usage: php scripts/debug_fetch_appointment.php {id}\n";
    exit(1);
}

$appointment = Appointment::find($id);
if (!$appointment) {
    echo json_encode(['error' => 'Appointment not found', 'id' => $id]) . PHP_EOL;
    exit(1);
}

// Authenticate as a user so controller authorization checks (Auth::user()) work.
// Prefer a staff user (administrador/medico/recepcionista) to avoid patient-restricted responses
$user = null;
foreach (User::all() as $u) {
    try {
        if ($u->hasRole(['administrador', 'medico', 'recepcionista'])) {
            $user = $u;
            break;
        }
    } catch (\Throwable $e) {
        // ignore users that can't check role
    }
}
if (!$user) {
    $user = User::first();
}
if (!$user) {
    echo json_encode(['error' => 'No users found to authenticate as']) . PHP_EOL;
    exit(1);
}
Auth::setUser($user);

$ctrl = new AppointmentController();
try {
    $response = $ctrl->apiShow($appointment);
} catch (\Exception $e) {
    echo json_encode(['error' => 'Exception', 'message' => $e->getMessage()]) . PHP_EOL;
    exit(1);
}

// If response is a JsonResponse or Response, get content
if (method_exists($response, 'getContent')) {
    echo $response->getContent() . PHP_EOL;
} else {
    // Try to json encode
    echo json_encode($response) . PHP_EOL;
}

exit(0);
