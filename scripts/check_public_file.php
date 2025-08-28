<?php
$rel = 'clinic/e3B90NzyHgbS98J39t19hmXE4qiTlI2S23YVUvtg.jpg';
$publicPath = __DIR__ . '/../public/storage/' . $rel;
$storagePath = __DIR__ . '/../storage/app/public/' . $rel;

echo "Checking file paths:\n";
echo "public: $publicPath\n";
echo "storage: $storagePath\n\n";

foreach ([$publicPath, $storagePath] as $p) {
    echo "- $p\n";
    if (file_exists($p)) {
        echo "  exists: yes\n";
        echo "  filesize: " . filesize($p) . " bytes\n";
        echo "  is_readable: " . (is_readable($p) ? 'yes' : 'no') . "\n";
        $mime = @mime_content_type($p) ?: 'unknown';
        echo "  mime_content_type: $mime\n";
        $perms = substr(sprintf('%o', fileperms($p)), -4);
        echo "  perms: $perms\n";
    } else {
        echo "  exists: NO\n";
    }
    echo "\n";
}

// check .htaccess files
$files = [__DIR__ . '/../public/.htaccess', __DIR__ . '/../public/storage/.htaccess', __DIR__ . '/../storage/app/public/.htaccess'];
foreach ($files as $f) {
    echo "Checking $f -> ";
    if (file_exists($f)) {
        echo "FOUND\n";
        echo "--- content start ---\n";
        echo file_get_contents($f);
        echo "\n--- content end ---\n";
    } else {
        echo "missing\n";
    }
}

return 0;
