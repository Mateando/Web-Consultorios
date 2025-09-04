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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Quitar después de validar que la URL es alcanzable tras los cambios.
    // Ruta para imprimir LISTADO de citas con filtros
    Route::get('/appointments/print-list', [AppointmentController::class, 'printList'])->name('appointments.printList');

    // API endpoint para obtener una cita como JSON (para prefill en modales)
    // Restringir {appointment} a números para que rutas como /api/appointments/available-slots no
    // sean capturadas por esta ruta paramétrica (causaba 404 al intentar resolver el model binding).
    Route::get('/api/appointments/{appointment}', [AppointmentController::class, 'apiShow'])
        ->whereNumber('appointment')
        ->name('api.appointments.show');

Route::middleware(['auth', 'verified'])->group(function () {
    // 2FA Google Authenticator
    Route::get('/profile/2fa', [\App\Http\Controllers\TwoFactorController::class, 'showSetup'])->name('profile.2fa');
    Route::post('/profile/2fa/enable', [\App\Http\Controllers\TwoFactorController::class, 'enable'])->name('profile.2fa.enable');
    Route::post('/profile/2fa/disable', [\App\Http\Controllers\TwoFactorController::class, 'disable'])->name('profile.2fa.disable');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    
    // Rutas de citas - todos los usuarios autenticados pueden ver/gestionar citas
    // Forzamos que el parámetro {appointment} solo acepte IDs numéricos para evitar
    // que rutas literales como /appointments/stats sean capturadas por la ruta paramétrica
    // del resource (causaba 404 al intentar resolver el model binding).
    Route::resource('appointments', AppointmentController::class)
        ->where(['appointment' => '[0-9]+'])
        ->middleware('validate.active');

    // Página de estadísticas de atención (Inertia) — ruta dedicada
    Route::get('/appointments/stats', [\App\Http\Controllers\AppointmentController::class, 'stats'])
        ->name('appointments.stats');
    
    // Ruta para obtener doctores por especialidad
    Route::get('/api/doctors-by-specialty', [AppointmentController::class, 'getDoctorsBySpecialty'])->name('doctors.by-specialty');
    
    // Ruta para obtener slots disponibles de un doctor
    Route::get('/api/appointments/available-slots', [AppointmentController::class, 'getAvailableTimeSlots'])->name('appointments.available-slots');
    
    // Ruta para obtener días disponibles por especialidad
    Route::get('/api/specialty-available-days', [AppointmentController::class, 'getSpecialtyAvailableDays'])->name('specialty.available-days');
    
    // ...existing code...
    
    // Ruta para que pacientes cancelen sus citas
    Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');

    // Ruta para imprimir una cita (vista imprimible)
    Route::get('/appointments/{appointment}/print', [AppointmentController::class, 'print'])->name('appointments.print');
    
    // Ruta para imprimir LISTADO de citas con filtros
    Route::get('/appointments/print-list', [AppointmentController::class, 'printList'])->name('appointments.printList');
    
    // Rutas para horarios de doctores (Admin y doctores)
    Route::middleware('role:administrador|medico')->group(function () {
        Route::resource('doctor-schedules', DoctorScheduleController::class)->except(['show', 'create', 'edit']);
        Route::get('/api/doctor-schedules/available-slots', [DoctorScheduleController::class, 'getAvailableSlots'])->name('doctor-schedules.available-slots');
        Route::get('/api/doctor-specialties', [DoctorScheduleController::class, 'getDoctorSpecialties'])->name('doctor.specialties');
        
        // Ruta para el componente Livewire de gestión de horarios
        Route::get('/doctor-schedules-management', function () {
            return view('doctor-schedules-management');
        })->name('doctor-schedules.management');
    });
    
    // Rutas para pacientes (Solo admin, doctores y recepcionistas)
    Route::middleware('role:administrador|medico|recepcionista')->group(function () {
    // Usar el controlador para la ruta principal de pacientes
    Route::get('patients', [PatientController::class, 'index'])->name('patients.index');
    // Ver citas de un paciente
    Route::get('patients/{patient}/appointments', [PatientController::class, 'appointments'])->name('patients.appointments');
    // Ruta para activar/desactivar paciente
    Route::patch('patients/{patient}/toggle-status', [PatientController::class, 'toggleStatus'])
        ->name('patients.toggle-status');
    });
    
    // Rutas para doctores (Solo admin y recepcionistas)
    Route::middleware('role:administrador|recepcionista')->group(function () {
        // Forzamos que el parámetro {doctor} solo acepte IDs numéricos para evitar
        // que rutas literales como /doctors/insurance-providers sean capturadas
        // por la ruta paramétrica del resource.
        Route::resource('doctors', DoctorController::class)->where(['doctor' => '[0-9]+']);
        // Ruta para activar/desactivar doctor
        Route::patch('doctors/{doctor}/toggle-status', [DoctorController::class, 'toggleStatus'])
            ->name('doctors.toggle-status');
    // CRUD para asignar obras sociales a doctores
    Route::get('/doctors/insurance-providers', [\App\Http\Controllers\DoctorInsuranceProviderController::class, 'index'])->name('doctors.insurance-providers.index');
    Route::put('/doctors/{doctor}/insurance-providers', [\App\Http\Controllers\DoctorInsuranceProviderController::class, 'update'])->name('doctors.insurance-providers.update');
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

    // Ruta para imprimir plantillas desde Reportes
    Route::get('/admin/reports/medical-order-templates/{medicalOrderTemplate}/print', [\App\Http\Controllers\AdminConfigController::class, 'printMedicalOrderTemplate'])->name('admin.reports.medical-order-templates.print');

    // Clínica - configuración general
    Route::get('/admin/config/clinic', [\App\Http\Controllers\Admin\ClinicSettingController::class, 'edit'])->name('admin.config.clinic');
    Route::post('/admin/config/clinic', [\App\Http\Controllers\Admin\ClinicSettingController::class, 'update'])->name('admin.config.clinic.update');
    // Página Generales dentro del menú Configuración
    Route::get('/admin/config/generales', [\App\Http\Controllers\AdminConfigController::class,'generales'])->name('admin.config.generales');

    // Configuración catálogos
    Route::get('/admin/config/patient-types', [\App\Http\Controllers\AdminConfigController::class,'patientTypes'])->name('admin.config.patient-types');
    Route::post('/admin/config/patient-types', [\App\Http\Controllers\AdminConfigController::class,'storePatientType'])->name('admin.config.patient-types.store');
    Route::put('/admin/config/patient-types/{patientType}', [\App\Http\Controllers\AdminConfigController::class,'updatePatientType'])->name('admin.config.patient-types.update');
    Route::patch('/admin/config/patient-types/{patientType}/toggle', [\App\Http\Controllers\AdminConfigController::class,'togglePatientType'])->name('admin.config.patient-types.toggle');

    Route::get('/admin/config/insurance-providers', [\App\Http\Controllers\AdminConfigController::class,'insuranceProviders'])->name('admin.config.insurance-providers');
    Route::post('/admin/config/insurance-providers', [\App\Http\Controllers\AdminConfigController::class,'storeInsuranceProvider'])->name('admin.config.insurance-providers.store');
    Route::put('/admin/config/insurance-providers/{insuranceProvider}', [\App\Http\Controllers\AdminConfigController::class,'updateInsuranceProvider'])->name('admin.config.insurance-providers.update');
    Route::patch('/admin/config/insurance-providers/{insuranceProvider}/toggle', [\App\Http\Controllers\AdminConfigController::class,'toggleInsuranceProvider'])->name('admin.config.insurance-providers.toggle');

    // Feriados / Días no laborables
    Route::get('/admin/config/holidays', [\App\Http\Controllers\AdminConfigController::class,'holidays'])->name('admin.config.holidays');
    Route::post('/admin/config/holidays', [\App\Http\Controllers\AdminConfigController::class,'storeHoliday'])->name('admin.config.holidays.store');
    Route::put('/admin/config/holidays/{holiday}', [\App\Http\Controllers\AdminConfigController::class,'updateHoliday'])->name('admin.config.holidays.update');
    Route::patch('/admin/config/holidays/{holiday}/toggle', [\App\Http\Controllers\AdminConfigController::class,'toggleHoliday'])->name('admin.config.holidays.toggle');

    // Motivos de Turnos
    Route::get('/admin/config/appointment-reasons', [\App\Http\Controllers\AdminConfigController::class,'appointmentReasons'])->name('admin.config.appointment-reasons');
    Route::post('/admin/config/appointment-reasons', [\App\Http\Controllers\AdminConfigController::class,'storeAppointmentReason'])->name('admin.config.appointment-reasons.store');
    Route::put('/admin/config/appointment-reasons/{appointmentReason}', [\App\Http\Controllers\AdminConfigController::class,'updateAppointmentReason'])->name('admin.config.appointment-reasons.update');
    Route::patch('/admin/config/appointment-reasons/{appointmentReason}/toggle', [\App\Http\Controllers\AdminConfigController::class,'toggleAppointmentReason'])->name('admin.config.appointment-reasons.toggle');

    // Tipos de Estudios
    Route::get('/admin/config/study-types', [\App\Http\Controllers\AdminConfigController::class,'studyTypes'])->name('admin.config.study-types');
    Route::post('/admin/config/study-types', [\App\Http\Controllers\AdminConfigController::class,'storeStudyType'])->name('admin.config.study-types.store');
    Route::put('/admin/config/study-types/{studyType}', [\App\Http\Controllers\AdminConfigController::class,'updateStudyType'])->name('admin.config.study-types.update');
    Route::patch('/admin/config/study-types/{studyType}/toggle', [\App\Http\Controllers\AdminConfigController::class,'toggleStudyType'])->name('admin.config.study-types.toggle');
    
    // Plantillas de Ordenes medicas
    Route::get('/admin/config/medical-order-templates', [\App\Http\Controllers\AdminConfigController::class,'medicalOrderTemplates'])->name('admin.config.medical-order-templates');
    Route::post('/admin/config/medical-order-templates', [\App\Http\Controllers\AdminConfigController::class,'storeMedicalOrderTemplate'])->name('admin.config.medical-order-templates.store');
    Route::put('/admin/config/medical-order-templates/{medicalOrderTemplate}', [\App\Http\Controllers\AdminConfigController::class,'updateMedicalOrderTemplate'])->name('admin.config.medical-order-templates.update');
    Route::patch('/admin/config/medical-order-templates/{medicalOrderTemplate}/toggle', [\App\Http\Controllers\AdminConfigController::class,'toggleMedicalOrderTemplate'])->name('admin.config.medical-order-templates.toggle');

    Route::get('/admin/config/countries', [\App\Http\Controllers\AdminConfigController::class,'countries'])->name('admin.config.countries');
    Route::post('/admin/config/countries', [\App\Http\Controllers\AdminConfigController::class,'storeCountry'])->name('admin.config.countries.store');
    Route::put('/admin/config/countries/{country}', [\App\Http\Controllers\AdminConfigController::class,'updateCountry'])->name('admin.config.countries.update');
    Route::patch('/admin/config/countries/{country}/toggle', [\App\Http\Controllers\AdminConfigController::class,'toggleCountry'])->name('admin.config.countries.toggle');

    Route::get('/admin/config/countries/{country}/provinces', [\App\Http\Controllers\AdminConfigController::class,'provinces'])->name('admin.config.provinces');
    Route::post('/admin/config/countries/{country}/provinces', [\App\Http\Controllers\AdminConfigController::class,'storeProvince'])->name('admin.config.provinces.store');
    Route::put('/admin/config/provinces/{province}', [\App\Http\Controllers\AdminConfigController::class,'updateProvince'])->name('admin.config.provinces.update');
    Route::patch('/admin/config/provinces/{province}/toggle', [\App\Http\Controllers\AdminConfigController::class,'toggleProvince'])->name('admin.config.provinces.toggle');

    Route::get('/admin/config/provinces/{province}/cities', [\App\Http\Controllers\AdminConfigController::class,'cities'])->name('admin.config.cities');
    Route::post('/admin/config/provinces/{province}/cities', [\App\Http\Controllers\AdminConfigController::class,'storeCity'])->name('admin.config.cities.store');
    Route::put('/admin/config/cities/{city}', [\App\Http\Controllers\AdminConfigController::class,'updateCity'])->name('admin.config.cities.update');
    Route::patch('/admin/config/cities/{city}/toggle', [\App\Http\Controllers\AdminConfigController::class,'toggleCity'])->name('admin.config.cities.toggle');

    // API selects dependientes
    Route::get('/api/config/countries', [\App\Http\Controllers\AdminConfigController::class,'apiCountries'])->name('api.config.countries');
    // Endpoint API para búsqueda de pacientes (JSON)
    Route::get('/api/patients', [\App\Http\Controllers\PatientController::class, 'apiSearch'])->name('api.patients.search');
    Route::get('/api/config/countries/{country}/provinces', [\App\Http\Controllers\AdminConfigController::class,'apiProvinces'])->name('api.config.provinces');
    Route::get('/api/config/provinces/{province}/cities', [\App\Http\Controllers\AdminConfigController::class,'apiCities'])->name('api.config.cities');
    Route::get('/api/config/patient-types', [\App\Http\Controllers\AdminConfigController::class,'apiPatientTypes'])->name('api.config.patient-types');
    Route::get('/api/config/insurance-providers', [\App\Http\Controllers\AdminConfigController::class,'apiInsuranceProviders'])->name('api.config.insurance-providers');
        Route::get('/api/config/holidays', [\App\Http\Controllers\AdminConfigController::class,'apiHolidays'])->name('api.config.holidays');
    Route::get('/api/config/appointment-reasons', [\App\Http\Controllers\AdminConfigController::class,'apiAppointmentReasons'])->name('api.config.appointment-reasons');
    Route::get('/api/config/study-types', [\App\Http\Controllers\AdminConfigController::class,'apiStudyTypes'])->name('api.config.study-types');
        Route::get('/api/config/medical-order-templates/active', [\App\Http\Controllers\AdminConfigController::class,'apiActiveMedicalOrderTemplates'])->name('api.config.medical-order-templates.active');

    // Endpoint API para estadísticas de citas (dashboard)
    Route::get('/api/appointments/stats', [\App\Http\Controllers\Api\AppointmentStatsController::class, 'index'])
        ->name('api.appointments.stats');
    
        // Endpoint para auditar envíos de WhatsApp (solo roles autorizados)
        Route::post('/api/whatsapp-audits', [\App\Http\Controllers\WhatsappAuditController::class, 'store'])
            ->name('api.whatsapp.audits.store')
            ->middleware('role:administrador|recepcionista|medico');
    });
    
    // Rutas del perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile', [ProfileController::class, 'update']); // Para formularios con archivos
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


require __DIR__.'/auth.php';
