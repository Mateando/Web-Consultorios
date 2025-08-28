<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ClinicSetting;
use Illuminate\Support\Facades\Storage;

// Update this filename to the real uploaded file present in storage/app/public/clinic
$realFilename = 'e3B90NzyHgbS98J39t19hmXE4qiTlI2S23YVUvtg.jpg';

$c = ClinicSetting::first();
if (! $c) {
    $c = ClinicSetting::create([]);
}

$c->logo_path = 'clinic/' . $realFilename;
$c->save();
$c->refresh();

echo "Set clinic.logo_path = " . $c->logo_path . PHP_EOL;
echo "Computed logo_url: " . Storage::url($c->logo_path) . PHP_EOL;
