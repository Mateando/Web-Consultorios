<template>
    <Head title="Gestión de Citas" />

    <AuthenticatedLayout>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filtros y controles -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900 border-b border-gray-200">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium">
                                {{ user_permissions?.is_patient ? 'Mis Citas' : 'Calendario de Citas' }}
                            </h3>
                            <div class="flex space-x-4">
                                <!-- Botón para nueva cita (solo para admin, doctores y recepcionistas) -->
                                <PrimaryButton
                                    v-if="user_permissions?.can_create_appointments"
                                    @click="createNewAppointment"
                                    :title="newAppointmentTooltip"
                                    :disabled="!canCreateNewAppointment || loadingAvailableDays"
                                >
                                    {{ loadingAvailableDays ? 'Verificando...' : 'Nueva Cita' }}
                                </PrimaryButton>
                                
                                <!-- Toggle vista -->
                                <div class="flex space-x-2">
                                    <!-- Calendario: usar SecondaryButton v-if/v-else para clases estáticas -->
                                    <SecondaryButton v-if="currentView === 'calendar'" type="button" @click="currentView = 'calendar'" class="px-3 py-1 text-sm bg-blue-500 text-white border-transparent hover:bg-blue-600">Calendario</SecondaryButton>
                                    <SecondaryButton v-else type="button" @click="currentView = 'calendar'" class="px-3 py-1 text-sm">Calendario</SecondaryButton>

                                    <!-- Lista: usar SecondaryButton v-if/v-else para clases estáticas -->
                                    <SecondaryButton v-if="currentView === 'list'" type="button" @click="currentView = 'list'" class="px-3 py-1 text-sm bg-blue-500 text-white border-transparent hover:bg-blue-600">Lista</SecondaryButton>
                                    <SecondaryButton v-else type="button" @click="currentView = 'list'" class="px-3 py-1 text-sm">Lista</SecondaryButton>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Filtros (ocultar filtro de doctor para pacientes) -->
                        <div class="grid grid-cols-1 gap-4 mb-4" :class="user_permissions?.is_patient ? 'md:grid-cols-4' : 'md:grid-cols-5'">
                            <div v-if="!user_permissions?.is_patient">
                                <label class="block text-sm font-medium text-gray-700">Doctor</label>
                                <select
                                    v-model="filters.doctor_id"
                                    @change="applyFilters"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                >
                                    <option value="">Todos los doctores</option>
                                    <option
                                        v-for="doctor in doctors"
                                        :key="doctor.id"
                                        :value="doctor.id"
                                    >
                                        {{ doctor.user.name }}
                                    </option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Especialidad</label>
                                <select
                                    v-model="filters.specialty_id"
                                    @change="applyFilters"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                >
                                    <option value="">Todas las especialidades</option>
                                    <option
                                        v-for="specialty in specialties"
                                        :key="specialty.id"
                                        :value="specialty.id"
                                    >
                                        {{ specialty.name }}
                                    </option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Estado</label>
                                <select
                                    v-model="filters.status"
                                    @change="applyFilters"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                >
                                    <option value="">Todos los estados</option>
                                    <option value="programada">Programada</option>
                                    <option value="confirmada">Confirmada</option>
                                    <option value="en_curso">En Curso</option>
                                    <option value="completada">Completada</option>
                                    <option value="cancelada">Cancelada</option>
                                    <option value="no_asistio">No Asistió</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha desde</label>
                                <input
                                    type="date"
                                    v-model="filters.start_date"
                                    @change="applyFilters"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                >
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha hasta</label>
                                <input
                                    type="date"
                                    v-model="filters.end_date"
                                    @change="applyFilters"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vista de Calendario -->
                <div v-if="currentView === 'calendar'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Mensaje informativo cuando hay filtro de especialidad -->
                        <div v-if="filters.specialty_id" class="mb-4 p-4 rounded-lg" :class="availableDays.length === 0 ? 'bg-red-50 border border-red-200' : 'bg-blue-50 border border-blue-200'">
                            <div class="flex items-center">
                                <svg v-if="availableDays.length === 0" class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                                </svg>
                                <svg v-else class="w-5 h-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm" :class="availableDays.length === 0 ? 'text-red-700' : 'text-blue-700'">
                                    <template v-if="availableDays.length === 0">
                                        ⚠️ Esta especialidad no tiene horarios de atención configurados. No es posible crear citas ni hacer clic en el calendario.
                                    </template>
                                    <template v-else>
                                        Los días bloqueados en el calendario indican que la especialidad seleccionada no atiende en esos días.
                                    </template>
                                </span>
                            </div>
                        </div>
                        
                        <AppointmentCalendar
                            :appointments="calendarEvents"
                            :user-permissions="user_permissions"
                            :filtered-specialty-id="filters.specialty_id"
                            :available-days="availableDays"
                            @event-click="editAppointment"
                            @date-click="createAppointmentOnDate"
                        />
                    </div>
                </div>

                <!-- Vista de Lista -->
                <div v-if="currentView === 'list'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Fecha y Hora
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Paciente
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Doctor
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Especialidad
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Estado
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="appointment in appointments.data" :key="appointment.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ formatDateTime(appointment.appointment_date) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ appointment.patient.user.name }}
                                            <br>
                                            <span class="text-xs text-gray-500">{{ appointment.patient.user.document_type?.toUpperCase() }} {{ appointment.patient.user.document_number }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ appointment.doctor.user.name }}
                                            <br>
                                            <span class="text-xs text-gray-500">{{ appointment.doctor.user.document_type?.toUpperCase() }} {{ appointment.doctor.user.document_number }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ appointment.specialty?.name || 'General' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getStatusClass(appointment.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                {{ getStatusText(appointment.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <!-- Botones para admin, doctores y recepcionistas -->
                                            <template v-if="user_permissions?.can_edit_appointments">
                                                <SecondaryButton type="button" @click="editAppointment(appointment)" class="!px-2 !py-1 mr-3 text-indigo-600 hover:text-indigo-900 bg-white border-gray-200">Editar</SecondaryButton>
                                            </template>

                                            <!-- Botón editar para pacientes (solo si pueden editar) -->
                                            <template v-if="user_permissions?.is_patient && canPatientEditAppointment(appointment)">
                                                <SecondaryButton type="button" @click="editAppointment(appointment)" class="!px-2 !py-1 mr-3 text-indigo-600 hover:text-indigo-900 bg-white border-gray-200">Editar</SecondaryButton>
                                            </template>

                                            <template v-if="user_permissions?.can_delete_appointments">
                                                <SecondaryButton type="button" @click="deleteAppointment(appointment)" class="!px-2 !py-1 mr-3 text-red-600 hover:text-red-900 bg-white border-gray-200">Eliminar</SecondaryButton>
                                            </template>

                                            <!-- Botón cancelar para pacientes (solo si no pueden editar) -->
                                            <template v-if="user_permissions?.can_cancel_own_appointments && !canPatientEditAppointment(appointment) && canCancelAppointment(appointment)">
                                                <SecondaryButton type="button" @click="cancelAppointment(appointment)" class="!px-3 !py-1 text-sm !bg-white text-orange-600 border-transparent hover:bg-orange-50">Cancelar</SecondaryButton>
                                            </template>
                                            
                                            <!-- Mensaje para pacientes cuando no pueden cancelar -->
                                            <template v-if="user_permissions?.is_patient && !canCancelAppointment(appointment) && appointment.status !== 'cancelada'">
                                                <span class="text-gray-400 text-sm">
                                                    No se puede cancelar
                                                </span>
                                            </template>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div class="mt-4" v-if="appointments && appointments.links">
                            <nav class="flex items-center justify-between">
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <Link
                                        v-if="appointments.prev_page_url"
                                        :href="appointments.prev_page_url"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Anterior
                                    </Link>
                                    <Link
                                        v-if="appointments.next_page_url"
                                        :href="appointments.next_page_url"
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Siguiente
                                    </Link>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Modal para crear/editar cita -->
                        <AppointmentModal
                            :show="showCreateModal || showEditModal"
                            :appointment="selectedAppointment"
                            :selected-date="selectedDate"
                            :doctors="doctors"
                            :patients="patients"
                            :specialties="specialties"
                            @close="closeModal"
                            @saved="appointmentSaved"
                        />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import AppointmentCalendar from '@/Components/AppointmentCalendar.vue'
import AppointmentModal from '@/Components/AppointmentModal.vue'
import axios from 'axios'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
    appointments: Object,
    doctors: Array,
    patients: Array,
    specialties: Array,
    calendar_events: Array,
    filters: Object,
    user_permissions: Object,
})

const currentView = ref('calendar')
const showCreateModal = ref(false)
const showEditModal = ref(false)
const selectedAppointment = ref(null)
const selectedDate = ref(null)
const availableDays = ref([])
const loadingAvailableDays = ref(false)

const filters = ref({
    doctor_id: props.filters?.doctor_id || '',
    specialty_id: props.filters?.specialty_id || '',
    status: props.filters?.status || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
})

const calendarEvents = computed(() => {
    return props.calendar_events || []
})

// Computed para verificar si el botón "Nueva Cita" debe estar habilitado
const canCreateNewAppointment = computed(() => {
    // Si no hay permisos, no puede crear
    if (!props.user_permissions?.can_create_appointments) {
        return false
    }
    
    // Si no hay filtro de especialidad, puede crear
    if (!filters.value.specialty_id) {
        return true
    }
    
    // Si hay filtro de especialidad, verificar que tenga días disponibles
    return availableDays.value.length > 0
})

// Computed para el mensaje de ayuda cuando no se puede crear cita
const newAppointmentTooltip = computed(() => {
    if (!props.user_permissions?.can_create_appointments) {
        return 'No tienes permisos para crear citas'
    }
    
    if (filters.value.specialty_id && availableDays.value.length === 0) {
        return 'Esta especialidad no tiene horarios de atención configurados'
    }
    
    return 'Crear nueva cita'
})

const applyFilters = () => {
    router.get('/appointments', filters.value, {
        preserveState: true,
        preserveScroll: true,
    })
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

const editAppointment = (appointment) => {
    // Verificar permisos antes de abrir modal de edición
    if (props.user_permissions?.is_patient) {
        // Los pacientes pueden editar sus citas si faltan más de 24 horas
        if (!canPatientEditAppointment(appointment)) {
            return
        }
        // Redirigir a la página de edición específica para pacientes
        router.get(`/appointments/${appointment.id}/edit`)
        return
    } else if (!props.user_permissions?.can_edit_appointments) {
        return
    }
    
    selectedAppointment.value = appointment
    selectedDate.value = null
    showEditModal.value = true
}

// Función para verificar si un paciente puede editar una cita
const canPatientEditAppointment = (appointment) => {
    if (!props.user_permissions?.is_patient) {
        return false
    }
    
    if (appointment.status === 'cancelada' || appointment.status === 'completada') {
        return false
    }
    
    const appointmentDate = new Date(appointment.appointment_date)
    const now = new Date()
    const hoursUntilAppointment = (appointmentDate - now) / (1000 * 60 * 60)
    
    return hoursUntilAppointment >= 24
}

const createNewAppointment = () => {
    // Verificar permisos antes de crear cita
    if (!props.user_permissions?.can_create_appointments) {
        return
    }
    
    // Verificar que hay días disponibles si hay filtro de especialidad
    if (filters.value.specialty_id && availableDays.value.length === 0) {
        return
    }
    
    selectedAppointment.value = null
    selectedDate.value = null
    showCreateModal.value = true
}

const createAppointmentOnDate = (date) => {
    // Verificar permisos antes de crear cita en fecha específica
    if (!props.user_permissions?.can_create_appointments) {
        return
    }
    
    selectedDate.value = date
    selectedAppointment.value = null
    showCreateModal.value = true
}

const deleteAppointment = (appointment) => {
    // Verificar permisos antes de eliminar
    if (!props.user_permissions?.can_delete_appointments) {
        return
    }
    
    if (confirm('¿Estás seguro de que quieres eliminar esta cita?')) {
        router.delete(`/appointments/${appointment.id}`, {
            onSuccess: () => {
                // La página se recargará automáticamente
            }
        })
    }
}

// Función para cancelar cita (para pacientes)
const cancelAppointment = (appointment) => {
    if (confirm('¿Estás seguro de que quieres cancelar esta cita? Esta acción no se puede deshacer.')) {
        router.patch(`/appointments/${appointment.id}/cancel`, {}, {
            onSuccess: () => {
                // La página se recargará automáticamente
            }
        })
    }
}

// Verificar si una cita puede ser cancelada por el paciente
const canCancelAppointment = (appointment) => {
    if (appointment.status === 'cancelada' || appointment.status === 'completada') {
        return false
    }
    
    const appointmentDate = new Date(appointment.appointment_date)
    const now = new Date()
    const hoursUntilAppointment = (appointmentDate - now) / (1000 * 60 * 60)
    
    return hoursUntilAppointment >= 24
}

const closeModal = () => {
    showCreateModal.value = false
    showEditModal.value = false
    selectedAppointment.value = null
    selectedDate.value = null
}

const appointmentSaved = () => {
    closeModal()
    // Recargar la página para actualizar los datos
    router.reload()
}

// Función para cargar días disponibles por especialidad
const loadAvailableDays = async (specialtyId) => {
    console.log('🚀 Index.vue - Cargando días disponibles para especialidad:', specialtyId)
    
    if (!specialtyId) {
        availableDays.value = []
        console.log('❌ No hay especialidad, limpiando días disponibles')
        return
    }
    
    loadingAvailableDays.value = true
    
    try {
        const response = await axios.get('/api/specialty-available-days', {
            params: { specialty_id: specialtyId }
        })
        
        console.log('📡 Respuesta completa del backend:', response.data)
        console.log('📅 Días disponibles recibidos:', response.data.available_days)
        console.log('🔢 Tipo de datos:', typeof response.data.available_days)
        console.log('📊 Es array?:', Array.isArray(response.data.available_days))
        
        availableDays.value = response.data.available_days || []
        
        console.log('✅ Días disponibles guardados en state:', availableDays.value)
    } catch (error) {
        console.error('❌ Error loading available days:', error)
        availableDays.value = []
    } finally {
        loadingAvailableDays.value = false
    }
}

// Watcher para cargar días disponibles cuando cambia el filtro de especialidad
watch(() => filters.value.specialty_id, (newSpecialtyId) => {
    loadAvailableDays(newSpecialtyId)
})

// Cargar días disponibles al montar si hay filtro de especialidad
onMounted(() => {
    if (filters.value.specialty_id) {
        loadAvailableDays(filters.value.specialty_id)
    }
})
</script>
