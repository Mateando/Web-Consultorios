<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use App\Models\AppointmentAudit;
use Carbon\Carbon;

class AppointmentAuditTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $doctor;
    protected $patient;
    protected $specialty;

    public function setUp(): void
    {
        parent::setUp();
        // Seed minimal data: users, doctor, patient, specialty
        // Asumimos que factories están disponibles
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Crear specialty, doctor y patient manualmente (sin factories)
    $this->specialty = Specialty::create(['name' => 'Test', 'is_active' => true]);

    // Crear users para doctor y patient
    $doctorUser = User::factory()->create();
    $patientUser = User::factory()->create();

        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'is_available' => true,
            'license_number' => 'TEST-1234',
        ]);
        $this->doctor->specialties()->attach($this->specialty->id);

        // Crear un horario activo para el doctor que cubra futuras fechas de prueba
        $day = strtolower(Carbon::now()->addDays(2)->format('l'));
        \App\Models\DoctorSchedule::create([
            'doctor_id' => $this->doctor->id,
            'specialty_id' => $this->specialty->id,
            'day_of_week' => $day,
            'start_time' => '08:00',
            'end_time' => '18:00',
            'appointment_duration' => 30,
            'is_active' => true,
        ]);

    $this->patient = Patient::create(['user_id' => $patientUser->id]);
    }

    public function test_create_generates_audit()
    {
    $dt = Carbon::now()->addDays(2)->toDateString() . ' 09:00';
        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'specialty_id' => $this->specialty->id,
            'appointment_date' => $dt,
            'duration' => 30,
            'notes' => 'test',
            'reason' => 'test reason',
            'status' => 'programada',
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('appointment_audits', [
            'appointment_id' => $appointment->id,
            'action' => 'created',
        ]);
    }

    public function test_update_generates_audit()
    {
    $dt = Carbon::now()->addDays(2)->toDateString() . ' 09:00';
        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'specialty_id' => $this->specialty->id,
            'appointment_date' => $dt,
            'duration' => 30,
            'notes' => 'test',
            'reason' => 'test reason',
            'status' => 'programada',
            'created_by' => $this->user->id,
        ]);

        $appointment->notes = 'updated note';
        $appointment->save();

        $this->assertDatabaseHas('appointment_audits', [
            'appointment_id' => $appointment->id,
            'action' => 'updated',
        ]);
    }

    public function test_delete_generates_audit()
    {
    $dt = Carbon::now()->addDays(2)->toDateString() . ' 09:00';
        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'specialty_id' => $this->specialty->id,
            'appointment_date' => $dt,
            'duration' => 30,
            'notes' => 'test',
            'reason' => 'test reason',
            'status' => 'programada',
            'created_by' => $this->user->id,
        ]);

        $appointment->delete();

        // Verificar que exista un audit con action 'deleted' y que su campo changes contenga el id de la cita
        $audit = \Illuminate\Support\Facades\DB::table('appointment_audits')
            ->where('action', 'deleted')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($audit, 'No se encontró registro de auditoría para deleted');
        $this->assertStringContainsString((string) $appointment->id, $audit->changes);
    }
}
