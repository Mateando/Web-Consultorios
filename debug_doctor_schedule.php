<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$s = App\Models\DoctorSchedule::find(1);
if (!$s) {
    echo "NO_SCHEDULE\n";
    exit;
}
echo "SCHEDULE_ID:" . $s->id . " DOCTOR_ID:" . $s->doctor_id . "\n";
$d = App\Models\Doctor::with('specialties')->find($s->doctor_id);
if (!$d) {
    echo "DOCTOR_NULL\n";
    exit;
}
echo "DOCTOR_ID:" . $d->id . " SPECIALTIES:" . json_encode($d->specialties->pluck('name')) . "\n";
