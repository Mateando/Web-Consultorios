<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    userRole: String,
});

const getRoleTitle = (role) => {
    const titles = {
        'administrador': 'Administrador del Sistema',
        'medico': 'Panel del Médico',
        'paciente': 'Portal del Paciente',
        'recepcionista': 'Panel de Recepción',
    };
    return titles[role] || 'Dashboard';
};

const getStatusColor = (status) => {
    const colors = {
        'programada': 'bg-yellow-100 text-yellow-800',
        'confirmada': 'bg-blue-100 text-blue-800',
        'completada': 'bg-green-100 text-green-800',
        'cancelada': 'bg-red-100 text-red-800',
        'no_asistio': 'bg-gray-100 text-gray-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ getRoleTitle(userRole) }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <!-- Panel Administrador -->
                <div v-if="userRole === 'administrador'" class="space-y-6">
                    <!-- Estadísticas principales -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Total Pacientes</dt>
                                            <dd class="text-lg font-medium text-gray-900">{{ stats.total_patients }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Total Doctores</dt>
                                            <dd class="text-lg font-medium text-gray-900">{{ stats.total_doctors }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Citas Hoy</dt>
                                            <dd class="text-lg font-medium text-gray-900">{{ stats.total_appointments_today }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 2L3 7v11a2 2 0 002 2h10a2 2 0 002-2V7l-7-5z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Citas Pendientes</dt>
                                            <dd class="text-lg font-medium text-gray-900">{{ stats.pending_appointments }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Citas recientes -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Citas Recientes</h3>
                            <div class="overflow-hidden">
                                <ul class="divide-y divide-gray-200">
                                    <li v-for="appointment in stats.recent_appointments" :key="appointment.id" class="py-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    {{ appointment.patient.user.name }}
                                                </p>
                                                <p class="text-sm text-gray-500 truncate">
                                                    Dr. {{ appointment.doctor.user.name }}
                                                </p>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span :class="getStatusColor(appointment.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                    {{ appointment.status }}
                                                </span>
                                                <p class="text-sm text-gray-500">
                                                    {{ new Date(appointment.appointment_date).toLocaleDateString() }}
                                                </p>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel Médico -->
                <div v-else-if="userRole === 'medico'" class="space-y-6">
                    <!-- Estadísticas del médico -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M6 2a1 1 0 000 2h8a1 1 0 000-2H6z"></path>
                                                <path d="M3 6a2 2 0 012-2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Citas Hoy</dt>
                                            <dd class="text-lg font-medium text-gray-900">{{ stats.appointments_today }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Completadas Hoy</dt>
                                            <dd class="text-lg font-medium text-gray-900">{{ stats.completed_today }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 2L3 7v11a2 2 0 002 2h10a2 2 0 002-2V7l-7-5z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Pendientes</dt>
                                            <dd class="text-lg font-medium text-gray-900">{{ stats.pending_appointments }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4z"></path>
                                                <path d="M3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Esta Semana</dt>
                                            <dd class="text-lg font-medium text-gray-900">{{ stats.appointments_week }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Agenda del día -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Mi Agenda de Hoy</h3>
                            <div class="overflow-hidden">
                                <ul class="divide-y divide-gray-200">
                                    <li v-for="appointment in stats.my_appointments_today" :key="appointment.id" class="py-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    {{ appointment.patient.user.name }}
                                                </p>
                                                <p class="text-sm text-gray-500 truncate">
                                                    {{ appointment.reason }}
                                                </p>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span :class="getStatusColor(appointment.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                    {{ appointment.status }}
                                                </span>
                                                <p class="text-sm text-gray-500">
                                                    {{ new Date(appointment.appointment_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                                                </p>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel Paciente -->
                <div v-else-if="userRole === 'paciente'" class="space-y-6">
                    <!-- Próxima cita -->
                    <div v-if="stats.next_appointment" class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <h3 class="text-lg font-medium text-blue-900 mb-2">Próxima Cita</h3>
                        <div class="space-y-2">
                            <p class="text-sm text-blue-800">
                                <span class="font-medium">Doctor:</span> Dr. {{ stats.next_appointment.doctor.user.name }}
                            </p>
                            <p class="text-sm text-blue-800">
                                <span class="font-medium">Especialidad:</span> {{ stats.next_appointment.doctor.specialty.name }}
                            </p>
                            <p class="text-sm text-blue-800">
                                <span class="font-medium">Fecha:</span> {{ new Date(stats.next_appointment.appointment_date).toLocaleString() }}
                            </p>
                        </div>
                    </div>

                    <!-- Estadísticas del paciente -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M6 2a1 1 0 000 2h8a1 1 0 000-2H6z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Próximas Citas</dt>
                                            <dd class="text-lg font-medium text-gray-900">{{ stats.upcoming_appointments }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Consultas Completadas</dt>
                                            <dd class="text-lg font-medium text-gray-900">{{ stats.completed_appointments }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historial reciente -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Historial Reciente</h3>
                            <div class="overflow-hidden">
                                <ul class="divide-y divide-gray-200">
                                    <li v-for="appointment in stats.recent_appointments" :key="appointment.id" class="py-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    Dr. {{ appointment.doctor.user.name }}
                                                </p>
                                                <p class="text-sm text-gray-500 truncate">
                                                    {{ appointment.doctor.specialty.name }}
                                                </p>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span :class="getStatusColor(appointment.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                    {{ appointment.status }}
                                                </span>
                                                <p class="text-sm text-gray-500">
                                                    {{ new Date(appointment.appointment_date).toLocaleDateString() }}
                                                </p>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel Recepcionista -->
                <div v-else-if="userRole === 'recepcionista'" class="space-y-6">
                    <!-- Estadísticas de recepción -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-4">
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M6 2a1 1 0 000 2h8a1 1 0 000-2H6z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Citas Hoy</dt>
                                            <dd class="text-lg font-medium text-gray-900">{{ stats.appointments_today }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 2L3 7v11a2 2 0 002 2h10a2 2 0 002-2V7l-7-5z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Pendientes</dt>
                                            <dd class="text-lg font-medium text-gray-900">{{ stats.pending_appointments }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Confirmadas</dt>
                                            <dd class="text-lg font-medium text-gray-900">{{ stats.confirmed_appointments }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Doctores Disponibles</dt>
                                            <dd class="text-lg font-medium text-gray-900">{{ stats.available_doctors }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Agenda del día -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Agenda del Día</h3>
                            <div class="overflow-hidden">
                                <ul class="divide-y divide-gray-200">
                                    <li v-for="appointment in stats.today_schedule" :key="appointment.id" class="py-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    {{ appointment.patient.user.name }} - Dr. {{ appointment.doctor.user.name }}
                                                </p>
                                                <p class="text-sm text-gray-500 truncate">
                                                    {{ appointment.reason }}
                                                </p>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span :class="getStatusColor(appointment.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                    {{ appointment.status }}
                                                </span>
                                                <p class="text-sm text-gray-500">
                                                    {{ new Date(appointment.appointment_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                                                </p>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensaje por defecto -->
                <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        Bienvenido al Sistema de Gestión del Consultorio Médico
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
