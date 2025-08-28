<?php
require __DIR__ . '/../vendor/autoload.php';
// Bootstrap the framework
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ClinicSetting;
use Illuminate\Support\Facades\Storage;

$c = ClinicSetting::first();
if (! $c) {
    $c = ClinicSetting::create([]);
}
$c->logo_path = 'clinic/logo.png';
$c->save();

echo "updated clinic_settings.logo_path to {$c->logo_path}\n";
echo "public url: " . Storage::url($c->logo_path) . "\n";
