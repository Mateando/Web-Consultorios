<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use App\Models\ClinicSetting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Share clinic settings with all Inertia pages (single-row table)
        try {
            $clinic = ClinicSetting::first();
        } catch (\Throwable $e) {
            $clinic = null;
        }

        $logoUrl = null;
        if ($clinic && $clinic->logo_path) {
            try {
                $logoUrl = Storage::url($clinic->logo_path);
            } catch (\Throwable $e) {
                $logoUrl = null;
            }
        }

        Inertia::share([
            'clinic' => $clinic ? [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'address' => $clinic->address,
                'phone' => $clinic->phone,
                'email' => $clinic->email,
                'tax_id' => $clinic->tax_id,
                'footer_notes' => $clinic->footer_notes,
                'logo_url' => $logoUrl,
            ] : null,
        ]);

        // Also share with Blade views (for favicon in app.blade.php)
        View::share('clinic_logo_url', $logoUrl);
    }
}
