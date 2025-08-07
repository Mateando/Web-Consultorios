<template>
    <Head title="Editar Cita" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Confirmar o Cancelar Cita
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit">
                            <!-- Información de la cita (solo lectura) -->
                            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Información de la Cita</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Fecha y Hora</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ formatDateTime(appointment.appointment_date) }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Doctor</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ appointment.doctor.user.name }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Especialidad</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ appointment.specialty?.name || 'General' }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Estado Actual</label>
                                        <span :class="getStatusClass(appointment.status)" class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full">
                                            {{ getStatusText(appointment.status) }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div v-if="appointment.notes" class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700">Notas</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ appointment.notes }}</p>
                                </div>
                                
                                <div v-if="appointment.reason" class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700">Motivo de la Cita</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ appointment.reason }}</p>
                                </div>
                            </div>

                            <!-- Cambio de estado -->
                            <div class="mb-6">
                                <label for="status" class="block text-sm font-medium text-gray-700">
                                    Acción que deseas realizar
                                </label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Selecciona una opción</option>
                                    <option value="confirmada">Confirmar Cita</option>
                                    <option value="cancelada">Cancelar Cita</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>

                            <!-- Mensaje explicativo -->
                            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            <strong>Importante:</strong> Solo puedes modificar el estado de tus citas cuando falten más de 24 horas para la fecha programada.
                                        </p>
                                        <p class="text-sm text-blue-700 mt-1">
                                            • <strong>Confirmar:</strong> Confirmas tu asistencia a la cita.<br>
                                            • <strong>Cancelar:</strong> Cancelas la cita y liberás el horario para otros pacientes.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end">
                                <Link
                                    :href="route('appointments.index')"
                                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-3"
                                >
                                    Cancelar
                                </Link>

                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Actualizar Cita
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import InputError from '@/Components/InputError.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    appointment: Object,
})

const form = useForm({
    status: props.appointment.status === 'programada' ? '' : props.appointment.status,
})

const submit = () => {
    form.put(route('appointments.update', props.appointment.id))
}

const formatDateTime = (dateTime) => {
    return new Date(dateTime).toLocaleString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatusClass = (status) => {
    const classes = {
        'programada': 'bg-yellow-100 text-yellow-800',
        'confirmada': 'bg-blue-100 text-blue-800',
        'en_curso': 'bg-orange-100 text-orange-800',
        'completada': 'bg-green-100 text-green-800',
        'cancelada': 'bg-red-100 text-red-800',
        'no_asistio': 'bg-gray-100 text-gray-800',
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status) => {
    const texts = {
        'programada': 'Programada',
        'confirmada': 'Confirmada',
        'en_curso': 'En Curso',
        'completada': 'Completada',
        'cancelada': 'Cancelada',
        'no_asistio': 'No Asistió',
    }
    return texts[status] || status
}
</script>
