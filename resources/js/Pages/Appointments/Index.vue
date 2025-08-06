<template>
    <Head title="Gestión de Citas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Citas
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filtros y controles -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900 border-b border-gray-200">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium">Calendario de Citas</h3>
                            <div class="flex space-x-4">
                                <!-- Botón para nueva cita -->
                                <button
                                    @click="createNewAppointment"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                                >
                                    Nueva Cita
                                </button>
                                
                                <!-- Toggle vista -->
                                <div class="flex space-x-2">
                                    <button
                                        @click="currentView = 'calendar'"
                                        :class="[
                                            'px-3 py-1 rounded text-sm',
                                            currentView === 'calendar' 
                                                ? 'bg-blue-500 text-white' 
                                                : 'bg-gray-200 text-gray-700'
                                        ]"
                                    >
                                        Calendario
                                    </button>
                                    <button
                                        @click="currentView = 'list'"
                                        :class="[
                                            'px-3 py-1 rounded text-sm',
                                            currentView === 'list' 
                                                ? 'bg-blue-500 text-white' 
                                                : 'bg-gray-200 text-gray-700'
                                        ]"
                                    >
                                        Lista
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Filtros -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div>
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
                        <AppointmentCalendar
                            :appointments="calendarEvents"
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
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getStatusClass(appointment.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                {{ getStatusText(appointment.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button
                                                @click="editAppointment(appointment)"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                @click="deleteAppointment(appointment)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Eliminar
                                            </button>
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
import { ref, computed, onMounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import AppointmentCalendar from '@/Components/AppointmentCalendar.vue'
import AppointmentModal from '@/Components/AppointmentModal.vue'

const props = defineProps({
    appointments: Object,
    doctors: Array,
    patients: Array,
    specialties: Array,
    calendar_events: Array,
    filters: Object,
})

const currentView = ref('calendar')
const showCreateModal = ref(false)
const showEditModal = ref(false)
const selectedAppointment = ref(null)
const selectedDate = ref(null)

const filters = ref({
    doctor_id: props.filters?.doctor_id || '',
    status: props.filters?.status || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
})

const calendarEvents = computed(() => {
    return props.calendar_events || []
})

const applyFilters = () => {
    console.log('Applying filters:', filters.value)
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
    selectedAppointment.value = appointment
    selectedDate.value = null
    showEditModal.value = true
}

const createNewAppointment = () => {
    selectedAppointment.value = null
    selectedDate.value = null
    showCreateModal.value = true
}

const createAppointmentOnDate = (date) => {
    selectedDate.value = date
    selectedAppointment.value = null
    showCreateModal.value = true
}

const deleteAppointment = (appointment) => {
    if (confirm('¿Estás seguro de que quieres eliminar esta cita?')) {
        router.delete(`/appointments/${appointment.id}`, {
            onSuccess: () => {
                // La página se recargará automáticamente
            }
        })
    }
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
</script>
