<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $stats = [];

        if ($user->hasRole('administrador')) {
            $stats = $this->getAdminStats();
        } elseif ($user->hasRole('medico')) {
            $stats = $this->getDoctorStats($user);
        } elseif ($user->hasRole('recepcionista')) {
            $stats = $this->getReceptionistStats();
        } elseif ($user->hasRole('paciente')) {
            $stats = $this->getPatientStats($user);
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'userRole' => $user->roles->first()->name ?? 'sin_rol',
        ]);
    }

    private function getAdminStats()
    {
        return [
            'total_patients' => Patient::count(),
            'total_doctors' => Doctor::count(),
            'total_appointments_today' => Appointment::today()->count(),
            'total_appointments_month' => Appointment::whereMonth('appointment_date', Carbon::now()->month)->count(),
            'pending_appointments' => Appointment::where('status', 'programada')->count(),
            'completed_appointments_today' => Appointment::today()->where('status', 'completada')->count(),
            'recent_appointments' => Appointment::with(['patient.user', 'doctor.user'])
                ->latest()
                ->take(5)
                ->get(),
        ];
    }

    private function getDoctorStats($user)
    {
        $doctor = $user->doctor;
        
        return [
            'appointments_today' => Appointment::today()->byDoctor($doctor->id)->count(),
            'appointments_week' => Appointment::whereBetween('appointment_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->byDoctor($doctor->id)->count(),
            'pending_appointments' => Appointment::byDoctor($doctor->id)->byStatus('programada')->count(),
            'completed_today' => Appointment::today()->byDoctor($doctor->id)->byStatus('completada')->count(),
            'my_appointments_today' => Appointment::today()
                ->byDoctor($doctor->id)
                ->with(['patient.user'])
                ->orderBy('appointment_date')
                ->get(),
        ];
    }

    private function getReceptionistStats()
    {
        return [
            'appointments_today' => Appointment::today()->count(),
            'pending_appointments' => Appointment::byStatus('programada')->count(),
            'confirmed_appointments' => Appointment::byStatus('confirmada')->count(),
            'available_doctors' => Doctor::available()->count(),
            'today_schedule' => Appointment::today()
                ->with(['patient.user', 'doctor.user'])
                ->orderBy('appointment_date')
                ->get(),
        ];
    }

    private function getPatientStats($user)
    {
        $patient = $user->patient;
        
        return [
            'upcoming_appointments' => Appointment::byPatient($patient->id)
                ->upcoming()
                ->count(),
            'completed_appointments' => Appointment::byPatient($patient->id)
                ->byStatus('completada')
                ->count(),
            'next_appointment' => Appointment::byPatient($patient->id)
                ->upcoming()
                ->with(['doctor.user', 'doctor.specialties'])
                ->first(),
            'recent_appointments' => Appointment::byPatient($patient->id)
                ->with(['doctor.user', 'doctor.specialties'])
                ->latest()
                ->take(5)
                ->get(),
        ];
    }
}
