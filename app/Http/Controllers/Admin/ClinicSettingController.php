<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ClinicSetting;
use Illuminate\Support\Facades\Storage;

class ClinicSettingController extends Controller
{
    public function edit()
    {
        $clinic = ClinicSetting::first();
        return Inertia::render('Admin/ClinicSettings', [
            'clinic' => $clinic,
        ]);
    }

    public function update(Request $request)
    {
        $clinic = ClinicSetting::first();

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1024',
            'phone' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
            'tax_id' => 'nullable|string|max:64',
            'footer_notes' => 'nullable|string|max:2000',
            'logo' => 'nullable|image|max:5120', // max 5MB
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->store('clinic', 'public');
            $data['logo_path'] = $path;

            // Try to resize/normalize the stored image to avoid 1x1 or huge uploads
            try {
                $fullPath = storage_path('app/public/' . $path);
                $this->resizeImageFile($fullPath, 1200, 600);
            } catch (\Throwable $e) {
                // ignore resize errors
            }
        }

        if ($request->input('remove_logo')) {
            if ($clinic && $clinic->logo_path) {
                try { Storage::disk('public')->delete($clinic->logo_path); } catch (\Throwable $e) {}
            }
            $data['logo_path'] = null;
        }

        if (! $clinic) {
            $clinic = ClinicSetting::create($data);
        } else {
            $clinic->update($data);
            $clinic->refresh();
        }

        // Compute public logo URL to include in AJAX responses
        $logoUrl = null;
        if ($clinic && $clinic->logo_path) {
            try {
                $logoUrl = Storage::url($clinic->logo_path);
            } catch (\Throwable $e) {
                $logoUrl = null;
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            $clinicArray = $clinic->toArray();
            $clinicArray['logo_url'] = $logoUrl;
            return response()->json(['clinic' => $clinicArray], 200);
        }

        return back();
    }

    /**
     * Resize an image file in place using GD if available.
     * Keeps aspect ratio, fits within maxWidth/maxHeight.
     */
    protected function resizeImageFile(string $filePath, int $maxWidth = 1200, int $maxHeight = 600): void
    {
        if (! file_exists($filePath)) return;
        if (! function_exists('getimagesize')) return;

        $info = @getimagesize($filePath);
        if (! $info) return;

        [$src_w, $src_h, $type] = [$info[0], $info[1], $info[2]];

        // If already within bounds, nothing to do
        if ($src_w <= $maxWidth && $src_h <= $maxHeight) return;

        $ratio = min($maxWidth / $src_w, $maxHeight / $src_h);
        $new_w = max(1, (int) round($src_w * $ratio));
        $new_h = max(1, (int) round($src_h * $ratio));

        switch ($type) {
            case IMAGETYPE_JPEG:
                $src = @imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $src = @imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $src = @imagecreatefromgif($filePath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $src = @imagecreatefromwebp($filePath);
                } else {
                    return;
                }
                break;
            default:
                return;
        }

        if (! $src) return;

        $dst = imagecreatetruecolor($new_w, $new_h);

        // Preserve transparency for PNG and GIF
        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $new_w, $new_h, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_w, $new_h, $src_w, $src_h);

        try {
            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($dst, $filePath, 85);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($dst, $filePath, 6);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($dst, $filePath);
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagewebp')) imagewebp($dst, $filePath, 85);
                    break;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        imagedestroy($src);
        imagedestroy($dst);
    }
}

