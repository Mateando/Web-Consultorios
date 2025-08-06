<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Specialty;

class ValidateActiveEntities
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar validaciones en rutas POST y PUT de appointments
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH']) && 
            $request->routeIs('appointments.*')) {
            
            // Validar paciente activo
            if ($request->has('patient_id')) {
                $patient = Patient::with('user')->find($request->patient_id);
                if (!$patient || !$patient->user->is_active) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'El paciente seleccionado está inactivo.',
                            'errors' => ['patient_id' => ['El paciente seleccionado está inactivo.']]
                        ], 422);
                    }
                    return redirect()->back()->withErrors(['patient_id' => 'El paciente seleccionado está inactivo.']);
                }
            }

            // Validar doctor activo y sus especialidades
            if ($request->has('doctor_id')) {
                $doctor = Doctor::with(['user', 'specialties'])->find($request->doctor_id);
                if (!$doctor || !$doctor->user->is_active) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'El médico seleccionado está inactivo.',
                            'errors' => ['doctor_id' => ['El médico seleccionado está inactivo.']]
                        ], 422);
                    }
                    return redirect()->back()->withErrors(['doctor_id' => 'El médico seleccionado está inactivo.']);
                }

                // Validar que el doctor tenga al menos una especialidad activa
                $hasActiveSpecialties = $doctor->specialties()->where('is_active', true)->exists();
                if (!$hasActiveSpecialties) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'El médico seleccionado no tiene especialidades activas.',
                            'errors' => ['doctor_id' => ['El médico seleccionado no tiene especialidades activas.']]
                        ], 422);
                    }
                    return redirect()->back()->withErrors(['doctor_id' => 'El médico seleccionado no tiene especialidades activas.']);
                }

                // Si se especifica una especialidad, verificar que el doctor la tenga y esté activa
                if ($request->has('specialty_id') && $request->specialty_id) {
                    $specialty = $doctor->specialties()->where('specialties.id', $request->specialty_id)->where('is_active', true)->first();
                    if (!$specialty) {
                        if ($request->expectsJson()) {
                            return response()->json([
                                'message' => 'El médico seleccionado no tiene la especialidad especificada o la especialidad está inactiva.',
                                'errors' => ['specialty_id' => ['El médico seleccionado no tiene la especialidad especificada o la especialidad está inactiva.']]
                            ], 422);
                        }
                        return redirect()->back()->withErrors(['specialty_id' => 'El médico seleccionado no tiene la especialidad especificada o la especialidad está inactiva.']);
                    }
                }
            }

            // Validar especialidad activa (solo si no se validó ya en la sección del doctor)
            if ($request->has('specialty_id') && $request->specialty_id && !$request->has('doctor_id')) {
                $specialty = Specialty::find($request->specialty_id);
                if (!$specialty || !$specialty->is_active) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'La especialidad seleccionada está inactiva.',
                            'errors' => ['specialty_id' => ['La especialidad seleccionada está inactiva.']]
                        ], 422);
                    }
                    return redirect()->back()->withErrors(['specialty_id' => 'La especialidad seleccionada está inactiva.']);
                }
            }
        }

        return $next($request);
    }
}
