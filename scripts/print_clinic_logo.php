<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ClinicSetting;
use Illuminate\Support\Facades\Storage;

$c = ClinicSetting::first();
if (! $c) {
    echo "No clinic settings found\n";
    exit(0);
}

echo "DB logo_path: " . ($c->logo_path ?: 'NULL') . PHP_EOL;
try {
    echo "Computed logo_url: " . (Storage::url($c->logo_path) ?: 'NULL') . PHP_EOL;
} catch (Throwable $e) {
    echo "Computed logo_url: ERROR: " . $e->getMessage() . PHP_EOL;
}
