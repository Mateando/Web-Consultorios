<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="$emit('close')"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="submitForm">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                    {{ isEditing ? 'Editar Cita' : 'Nueva Cita' }}
                                </h3>

                                <!-- Seleccionar Paciente -->
                                <div class="mb-4">
                                    <label for="patient_id" class="block text-sm font-medium text-gray-700">
                                        Paciente
                                    </label>
                                    <select
                                        id="patient_id"
                                        v-model="form.patient_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        required
                                    >
                                        <option value="">Seleccionar paciente</option>
                                        <option
                                            v-for="patient in patients"
                                            :key="patient.id"
                                            :value="patient.id"
                                        >
                                            {{ patient.user?.name || 'Paciente sin nombre' }} - {{ patient.user?.document_type?.toUpperCase() }} {{ patient.user?.document_number }}
                                        </option>
                                    </select>
                                    <div v-if="errors.patient_id" class="mt-1 text-sm text-red-600">
                                        {{ errors.patient_id }}
                                    </div>
                                </div>

                                <!-- Seleccionar Especialidad -->
                                <div class="mb-4">
                                    <label for="specialty_id" class="block text-sm font-medium text-gray-700">
                                        Especialidad
                                    </label>
                                    <select
                                        id="specialty_id"
                                        v-model="form.specialty_id"
                                        @change="onSpecialtyChange"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    >
                                        <option value="">Seleccionar especialidad</option>
                                        <option
                                            v-for="specialty in specialties"
                                            :key="specialty.id"
                                            :value="specialty.id"
                                        >
                                            {{ specialty.name }}
                                        </option>
                                    </select>
                                    <div v-if="errors.specialty_id" class="mt-1 text-sm text-red-600">
                                        {{ errors.specialty_id }}
                                    </div>
                                </div>

                                <!-- Seleccionar Doctor -->
                                <div class="mb-4">
                                    <label for="doctor_id" class="block text-sm font-medium text-gray-700">
                                        Doctor
                                    </label>
                                    <select
                                        id="doctor_id"
                                        v-model="form.doctor_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        :disabled="!form.specialty_id || filteredDoctors.length === 0"
                                        required
                                    >
                                        <option value="">{{ !form.specialty_id ? 'Seleccione primero una especialidad' : 'Seleccionar doctor' }}</option>
                                        <option
                                            v-for="doctor in filteredDoctors"
                                            :key="doctor.id"
                                            :value="doctor.id"
                                        >
                                            {{ doctor.name }} - {{ doctor.license_number }}
                                        </option>
                                    </select>
                                    <div v-if="errors.doctor_id" class="mt-1 text-sm text-red-600">
                                        {{ errors.doctor_id }}
                                    </div>
                                    <div v-if="form.specialty_id && filteredDoctors.length === 0" class="mt-1 text-sm text-yellow-600">
                                        No hay doctores disponibles para esta especialidad
                                    </div>
                                </div>

                                <!-- Fecha de la cita -->
                                <div class="mb-4">
                                    <label for="appointment_date" class="block text-sm font-medium text-gray-700">
                                        Fecha
                                    </label>
                                    <input
                                        id="appointment_date"
                                        type="date"
                                        v-model="form.appointment_date"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        required
                                    >
                                    <div v-if="errors.appointment_date" class="mt-1 text-sm text-red-600">
                                        {{ errors.appointment_date }}
                                    </div>
                                </div>

                                <!-- Hora de la cita -->
                                <div class="mb-4">
                                    <label for="appointment_time" class="block text-sm font-medium text-gray-700">
                                        Hora
                                    </label>
                                    <input
                                        id="appointment_time"
                                        type="time"
                                        v-model="form.appointment_time"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        required
                                    >
                                    <div v-if="errors.appointment_time" class="mt-1 text-sm text-red-600">
                                        {{ errors.appointment_time }}
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="mb-4">
                                    <label for="status" class="block text-sm font-medium text-gray-700">
                                        Estado
                                    </label>
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        required
                                    >
                                        <option value="programada">Programada</option>
                                        <option value="confirmada">Confirmada</option>
                                        <option value="en_curso">En Curso</option>
                                        <option value="completada">Completada</option>
                                        <option value="cancelada">Cancelada</option>
                                        <option value="no_asistio">No Asistió</option>
                                    </select>
                                    <div v-if="errors.status" class="mt-1 text-sm text-red-600">
                                        {{ errors.status }}
                                    </div>
                                </div>

                                <!-- Motivo -->
                                <div class="mb-4">
                                    <label for="reason" class="block text-sm font-medium text-gray-700">
                                        Motivo de la consulta
                                    </label>
                                    <textarea
                                        id="reason"
                                        v-model="form.reason"
                                        rows="3"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Describe el motivo de la consulta..."
                                    ></textarea>
                                    <div v-if="errors.reason" class="mt-1 text-sm text-red-600">
                                        {{ errors.reason }}
                                    </div>
                                </div>

                                <!-- Notas -->
                                <div class="mb-4">
                                    <label for="notes" class="block text-sm font-medium text-gray-700">
                                        Notas adicionales
                                    </label>
                                    <textarea
                                        id="notes"
                                        v-model="form.notes"
                                        rows="2"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Notas adicionales..."
                                    ></textarea>
                                    <div v-if="errors.notes" class="mt-1 text-sm text-red-600">
                                        {{ errors.notes }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            {{ processing ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Crear') }}
                        </button>
                        <button
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            @click="$emit('close')"
                        >
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    show: Boolean,
    appointment: Object,
    doctors: Array,
    patients: Array,
    specialties: Array,
    selectedDate: String, // Fecha seleccionada del calendario
})

const emit = defineEmits(['close', 'saved'])

const processing = ref(false)
const errors = ref({})
const filteredDoctors = ref([])

const form = ref({
    patient_id: '',
    doctor_id: '',
    specialty_id: '',
    appointment_date: '',
    appointment_time: '',
    status: 'programada',
    reason: '',
    notes: '',
})

const isEditing = computed(() => {
    return props.appointment && props.appointment.id
})

// Resetear formulario cuando se abre/cierra el modal
watch(() => props.show, (newValue) => {
    if (newValue) {
        resetForm()
        if (props.appointment) {
            populateForm()
        }
    }
    errors.value = {}
})

const resetForm = () => {
    const today = new Date().toISOString().split('T')[0]
    const selectedDateForForm = props.selectedDate || today
    
    form.value = {
        patient_id: '',
        doctor_id: '',
        specialty_id: '',
        appointment_date: selectedDateForForm,
        appointment_time: '',
        status: 'programada',
        reason: '',
        notes: '',
    }
    filteredDoctors.value = []
}

// Función para cargar doctores por especialidad
const onSpecialtyChange = async () => {
    form.value.doctor_id = '' // Resetear doctor seleccionado
    
    if (!form.value.specialty_id) {
        filteredDoctors.value = []
        return
    }
    
    try {
        const response = await axios.get('/api/doctors-by-specialty', {
            params: { specialty_id: form.value.specialty_id }
        })
        filteredDoctors.value = response.data.doctors
    } catch (error) {
        console.error('Error loading doctors:', error)
        filteredDoctors.value = []
    }
}

const populateForm = async () => {
    if (props.appointment) {
        const appointmentDate = new Date(props.appointment.appointment_date)
        
        form.value = {
            patient_id: props.appointment.patient_id,
            doctor_id: props.appointment.doctor_id,
            specialty_id: props.appointment.specialty_id || '',
            appointment_date: appointmentDate.toISOString().split('T')[0],
            appointment_time: appointmentDate.toTimeString().slice(0, 5),
            status: props.appointment.status,
            reason: props.appointment.reason || '',
            notes: props.appointment.notes || '',
        }
        
        // Si hay especialidad, cargar los doctores de esa especialidad
        if (form.value.specialty_id) {
            await onSpecialtyChange()
        }
    }
}

const submitForm = () => {
    processing.value = true
    errors.value = {}

    // Combinar fecha y hora
    const appointmentDateTime = `${form.value.appointment_date} ${form.value.appointment_time}:00`

    const data = {
        ...form.value,
        appointment_date: appointmentDateTime,
    }

    // Remover campos que no necesitamos enviar
    delete data.appointment_time

    const url = isEditing.value 
        ? `/appointments/${props.appointment.id}`
        : '/appointments'

    const method = isEditing.value ? 'put' : 'post'

    router[method](url, data, {
        onSuccess: () => {
            processing.value = false
            emit('saved')
        },
        onError: (errorResponse) => {
            console.log('Error al guardar la cita:', errorResponse)
            processing.value = false
            errors.value = errorResponse
        }
    })
}
</script>
