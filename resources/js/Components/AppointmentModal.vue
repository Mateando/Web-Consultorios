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
                                        @change="onDoctorChange"
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
                                        @change="onDateChange"
                                        :min="minDate"
                                        :disabled="form.specialty_id && (loadingDays || availableDays.length === 0)"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        :class="{ 'bg-gray-100 cursor-not-allowed': form.specialty_id && availableDays.length === 0 }"
                                        required
                                    >
                                    <div v-if="errors.appointment_date" class="mt-1 text-sm text-red-600">
                                        {{ errors.appointment_date }}
                                    </div>
                                    <div v-if="availableDaysMessage" class="mt-1 text-sm text-blue-600">
                                        {{ availableDaysMessage }}
                                    </div>
                                    <div v-if="form.specialty_id && availableDays.length === 0 && !loadingDays" class="mt-1 text-sm text-red-600 font-medium">
                                        ⚠️ Esta especialidad no tiene horarios de atención configurados. No es posible crear citas.
                                    </div>
                                </div>

                                <!-- Hora de la cita -->
                                <div class="mb-4">
                                    <label for="appointment_time" class="block text-sm font-medium text-gray-700">
                                        Hora
                                    </label>
                                    <select
                                        id="appointment_time"
                                        v-model="form.appointment_time"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        :disabled="!form.doctor_id || !form.appointment_date || loadingSlots"
                                        required
                                    >
                                        <option value="">
                                            {{ !form.doctor_id ? 'Seleccione primero un doctor' : 
                                               !form.appointment_date ? 'Seleccione primero una fecha' :
                                               loadingSlots ? 'Cargando horarios...' : 'Seleccionar hora' }}
                                        </option>
                                        <option
                                            v-for="slot in availableSlots"
                                            :key="`slot-${slot}`"
                                            :value="slot"
                                        >
                                            {{ slot }}
                                        </option>
                                    </select>
                                    <div v-if="errors.appointment_time" class="mt-1 text-sm text-red-600">
                                        {{ errors.appointment_time }}
                                    </div>
                                    <div v-if="form.doctor_id && form.appointment_date && availableSlots.length === 0 && !loadingSlots" class="mt-1 text-sm text-yellow-600">
                                        No hay horarios disponibles para esta fecha
                                    </div>
                                    <div v-if="form.doctor_id && form.appointment_date && availableSlots.length > 0" class="mt-1 text-sm text-green-600">
                                        {{ availableSlots.length }} horarios disponibles
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
                            :disabled="processing || (form.specialty_id && availableDays.length === 0)"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ processing ? 'Guardando...' : 
                               (form.specialty_id && availableDays.length === 0) ? 'Sin horarios disponibles' :
                               (isEditing ? 'Actualizar' : 'Crear') }}
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
import { ref, computed, watch, nextTick } from 'vue'
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
const availableSlots = ref([])
const loadingSlots = ref(false)
const availableDays = ref([])
const loadingDays = ref(false)

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

// Computed para generar el atributo min del input de fecha (solo fechas futuras)
const minDate = computed(() => {
    const today = new Date()
    return today.toISOString().split('T')[0]
})

// Computed para generar mensaje de ayuda sobre días disponibles
const availableDaysMessage = computed(() => {
    if (!form.value.specialty_id || availableDays.value.length === 0) {
        return ''
    }
    
    const dayNames = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado']
    const availableDayNames = availableDays.value.map(dayNumber => dayNames[dayNumber])
    
    if (availableDayNames.length === 0) {
        return 'No hay días disponibles para esta especialidad'
    }
    
    return `Días disponibles: ${availableDayNames.join(', ')}`
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

// Validar fecha cuando cambian los días disponibles
watch(availableDays, () => {
    if (form.value.appointment_date && !isDateAvailable(form.value.appointment_date)) {
        form.value.appointment_date = ''
        form.value.appointment_time = ''
    }
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
    availableSlots.value = []
    availableDays.value = []
}

// Función para cargar doctores por especialidad (para nuevas citas)
const onSpecialtyChange = async () => {
    form.value.doctor_id = '' // Resetear doctor seleccionado
    availableSlots.value = [] // Resetear slots disponibles
    form.value.appointment_time = '' // Resetear hora seleccionada
    form.value.appointment_date = '' // Resetear fecha seleccionada
    
    if (!form.value.specialty_id) {
        filteredDoctors.value = []
        availableDays.value = []
        return
    }
    
    await Promise.all([
        loadDoctorsBySpecialty(form.value.specialty_id),
        loadAvailableDays(form.value.specialty_id)
    ])
}

// Función auxiliar para cargar doctores sin resetear campos (para edición)
const loadDoctorsBySpecialty = async (specialtyId) => {
    if (!specialtyId) {
        filteredDoctors.value = []
        return
    }
    
    try {
        const response = await axios.get('/api/doctors-by-specialty', {
            params: { specialty_id: specialtyId }
        })
        filteredDoctors.value = response.data.doctors
    } catch (error) {
        console.error('Error loading doctors:', error)
        filteredDoctors.value = []
    }
}

// Función para cargar días disponibles por especialidad
const loadAvailableDays = async (specialtyId) => {
    if (!specialtyId) {
        availableDays.value = []
        return
    }
    
    loadingDays.value = true
    
    try {
        const response = await axios.get('/api/specialty-available-days', {
            params: { specialty_id: specialtyId }
        })
        availableDays.value = response.data.available_days || []
    } catch (error) {
        console.error('Error loading available days:', error)
        availableDays.value = []
    } finally {
        loadingDays.value = false
    }
}

// Función para verificar si una fecha está disponible
const isDateAvailable = (dateString) => {
    if (!form.value.specialty_id || availableDays.value.length === 0) {
        return true // Si no hay especialidad seleccionada, permitir todas las fechas
    }
    
    const date = new Date(dateString)
    const dayOfWeek = date.getDay() // 0 = domingo, 1 = lunes, etc.
    return availableDays.value.includes(dayOfWeek)
}

// Función específica para cuando cambia el doctor
const onDoctorChange = () => {
    form.value.appointment_time = '' // Resetear hora
    loadAvailableSlots()
}

// Función específica para cuando cambia la fecha
const onDateChange = () => {
    // Verificar si la fecha seleccionada está disponible
    if (!isDateAvailable(form.value.appointment_date)) {
        // Si la fecha no está disponible, limpiarla
        form.value.appointment_date = ''
        form.value.appointment_time = ''
        return
    }
    
    form.value.appointment_time = '' // Resetear hora
    loadAvailableSlots()
}

// Función para cargar slots disponibles
const loadAvailableSlots = async () => {
    if (!form.value.doctor_id || !form.value.appointment_date) {
        availableSlots.value = []
        return
    }
    
    loadingSlots.value = true
    
    try {
        const params = {
            doctor_id: form.value.doctor_id,
            date: form.value.appointment_date
        }
        
        // Si estamos editando, pasar el ID de la cita para excluirla
        if (isEditing.value && props.appointment?.id) {
            params.editing_appointment_id = props.appointment.id
        }
        
        const response = await axios.get('/api/appointments/available-slots', { params })
        
        // Forzar reactividad limpiando primero el array
        availableSlots.value = []
        await nextTick() // Esperar a que Vue procese el cambio
        availableSlots.value = [...(response.data.slots || [])]
    } catch (error) {
        console.error('Error loading available slots:', error)
        availableSlots.value = []
    } finally {
        loadingSlots.value = false
    }
}

const populateForm = async () => {
    if (props.appointment) {
        const appointmentDate = new Date(props.appointment.appointment_date)
        
        // Primero guardamos los valores que queremos preservar
        const targetDoctorId = props.appointment.doctor_id
        const targetSpecialtyId = props.appointment.specialty_id || ''
        const targetAppointmentTime = appointmentDate.toTimeString().slice(0, 5)
        
        form.value = {
            patient_id: props.appointment.patient_id,
            doctor_id: '', // Se asigna después de cargar los doctores
            specialty_id: targetSpecialtyId,
            appointment_date: appointmentDate.toISOString().split('T')[0],
            appointment_time: '', // Se asigna después de cargar los slots
            status: props.appointment.status,
            reason: props.appointment.reason || '',
            notes: props.appointment.notes || '',
        }
        
        // Si hay especialidad, cargar los doctores de esa especialidad
        if (targetSpecialtyId) {
            await Promise.all([
                loadDoctorsBySpecialty(targetSpecialtyId),
                loadAvailableDays(targetSpecialtyId)
            ])
            // Asignar el doctor después de cargar la lista
            form.value.doctor_id = targetDoctorId
        }
        
        // Cargar slots disponibles para la fecha y doctor
        if (form.value.doctor_id && form.value.appointment_date) {
            await loadAvailableSlots()
            // Asignar la hora después de cargar los slots
            form.value.appointment_time = targetAppointmentTime
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
