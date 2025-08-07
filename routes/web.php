<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DoctorScheduleController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Ruta de prueba para verificar CSRF
Route::get('/csrf-test', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
        'session_id' => session()->getId(),
        'app_url' => config('app.url'),
        'session_config' => [
            'driver' => config('session.driver'),
            'domain' => config('session.domain'),
            'path' => config('session.path'),
        ]
    ]);
})->name('csrf.test');

// Página HTML para testing
Route::get('/test-login', function () {
    return view('csrf-test');
})->name('test.login');

// Página de login simple sin Inertia
Route::get('/simple-login', function () {
    return view('simple-login');
})->name('simple.login');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Rutas de citas - todos los usuarios autenticados pueden ver/gestionar citas
    Route::resource('appointments', AppointmentController::class)->middleware('validate.active');
    
    // Ruta para obtener doctores por especialidad
    Route::get('/api/doctors-by-specialty', [AppointmentController::class, 'getDoctorsBySpecialty'])->name('doctors.by-specialty');
    
    // Ruta para obtener slots disponibles de un doctor
    Route::get('/api/appointments/available-slots', [AppointmentController::class, 'getAvailableTimeSlots'])->name('appointments.available-slots');
    
    // Ruta para obtener días disponibles por especialidad
    Route::get('/api/specialty-available-days', [AppointmentController::class, 'getSpecialtyAvailableDays'])->name('specialty.available-days');
    
    // Ruta de debug temporal (SIN autenticación)
    Route::get('/debug/specialty-days/{specialtyId}', function($specialtyId) {
        $controller = new \App\Http\Controllers\AppointmentController();
        $request = new \Illuminate\Http\Request();
        $request->merge(['specialty_id' => $specialtyId]);
        return $controller->getSpecialtyAvailableDays($request);
    });
    
    // Ruta para que pacientes cancelen sus citas
    Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    
    // Rutas para horarios de doctores (Admin y doctores)
    Route::middleware('role:administrador|medico')->group(function () {
        Route::resource('doctor-schedules', DoctorScheduleController::class)->except(['show', 'create', 'edit']);
        Route::get('/api/doctor-schedules/available-slots', [DoctorScheduleController::class, 'getAvailableSlots'])->name('doctor-schedules.available-slots');
    });
    
    // Rutas para pacientes (Solo admin, doctores y recepcionistas)
    Route::middleware('role:administrador|medico|recepcionista')->group(function () {
        Route::resource('patients', PatientController::class);
        // Ruta para activar/desactivar paciente
        Route::patch('patients/{patient}/toggle-status', [PatientController::class, 'toggleStatus'])
            ->name('patients.toggle-status');
    });
    
    // Rutas para doctores (Solo admin y recepcionistas)
    Route::middleware('role:administrador|recepcionista')->group(function () {
        Route::resource('doctors', DoctorController::class);
        // Ruta para activar/desactivar doctor
        Route::patch('doctors/{doctor}/toggle-status', [DoctorController::class, 'toggleStatus'])
            ->name('doctors.toggle-status');
    });
    
    // Rutas para administración (Solo admin)
    Route::middleware('role:administrador')->group(function () {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
        Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
        Route::patch('/admin/users/{user}/roles', [AdminController::class, 'updateUserRoles'])->name('admin.users.updateRoles');
        Route::patch('/admin/users/{user}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('admin.users.toggleStatus');
        Route::get('/admin/specialties', [AdminController::class, 'specialties'])->name('admin.specialties');
        Route::post('/admin/specialties', [AdminController::class, 'storeSpecialty'])->name('admin.specialties.store');
        Route::put('/admin/specialties/{specialty}', [AdminController::class, 'updateSpecialty'])->name('admin.specialties.update');
        Route::patch('/admin/specialties/{specialty}/toggle-status', [AdminController::class, 'toggleSpecialtyStatus'])->name('admin.specialties.toggleStatus');
        Route::delete('/admin/specialties/{specialty}', [AdminController::class, 'destroySpecialty'])->name('admin.specialties.destroy');
        Route::get('/admin/reports', [AdminController::class, 'reports'])->name('admin.reports');
    });
    
    // Rutas del perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


require __DIR__.'/auth.php';
