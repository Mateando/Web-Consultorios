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
                                        v-for="doctor in filteredDoctors"
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
                                        v-for="specialty in filteredSpecialties"
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
                        
                        <!-- Leyenda de colores para el calendario -->
                        <div class="mb-4 flex flex-wrap items-center gap-3" aria-hidden="true">
                            <div class="flex items-center space-x-2">
                                <span class="inline-block w-4 h-4 rounded-full" style="background:#3b82f6"></span>
                                <span class="text-sm text-gray-700">Programada</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-block w-4 h-4 rounded-full" style="background:#10b981"></span>
                                <span class="text-sm text-gray-700">Confirmada</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-block w-4 h-4 rounded-full" style="background:#f59e0b"></span>
                                <span class="text-sm text-gray-700">En curso</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-block w-4 h-4 rounded-full" style="background:#6b7280"></span>
                                <span class="text-sm text-gray-700">Completada</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-block w-4 h-4 rounded-full" style="background:#ef4444"></span>
                                <span class="text-sm text-gray-700">Cancelada</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-block w-4 h-4 rounded-full" style="background:#9ca3af"></span>
                                <span class="text-sm text-gray-700">No asistió</span>
                            </div>
                        </div>

                        <AppointmentCalendar
                            :appointments="calendarEvents"
                            :user-permissions="user_permissions"
                            :filtered-specialty-id="filters.specialty_id"
                            :available-days="availableDays"
                            @event-click="showAppointmentDetail"
                            @date-click="createAppointmentOnDate"
                        />
                    </div>
                </div>

                <!-- Vista de Lista -->
                <div v-if="currentView === 'list'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                            <!-- Botón para imprimir la lista con los filtros actuales -->
                            <div class="mb-4 flex justify-end no-print">
                                <SecondaryButton
                                    type="button"
                                    @click="printList"
                                    :disabled="!hasListResults"
                                    :title="hasListResults ? 'Imprimir lista' : 'No hay resultados para imprimir'"
                                    :class="['!px-3 !py-1 mr-3', !hasListResults ? 'opacity-50 cursor-not-allowed' : '']"
                                >
                                    Imprimir lista
                                </SecondaryButton>
                            </div>
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
                                            <span :style="getBadgeStyle(appointment.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
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

                                            <!-- Botón WhatsApp para confirmar (aparece en 'programada' y 'confirmada'; si falta teléfono aparece deshabilitado) -->
                                            <template v-if="['programada','confirmada'].includes(appointment.status) && user_permissions?.can_edit_appointments">
                                                <a v-if="hasWhatsapp(appointment)" :href="formatWhatsAppUrl(appointment)" target="_blank" rel="noopener" class="inline-flex items-center rounded-md border border-green-300 bg-white px-4 py-1 text-xs font-semibold uppercase tracking-widest text-green-600 shadow-sm transition duration-150 ease-in-out hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 mr-3">
                                                    <!-- Icono WhatsApp (icono + texto en verde) -->
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 mr-1 text-green-600">
                                                        <path d="M20.52 3.48A11.92 11.92 0 0012 0C5.373 0 .102 4.917.001 11.17a11.93 11.93 0 002.13 6.19L0 24l6.86-2.04A11.92 11.92 0 0012 24c6.627 0 11.999-4.917 12-11.17 0-1.86-.42-3.62-1.48-5.35zM12 21.5c-1.3 0-2.57-.3-3.7-.86l-.27-.14-4.08 1.21 1.15-3.98-.17-.33A9.5 9.5 0 012.5 11.17 9.5 9.5 0 1112 21.5z"/>
                                                        <path d="M17.16 14.03c-.28-.14-1.66-.82-1.92-.91-.26-.09-.45-.14-.64.14s-.73.91-.9 1.1c-.17.19-.34.21-.63.07-.3-.14-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.6.13-.13.3-.34.45-.51.15-.17.2-.28.3-.46.09-.17.04-.32-.02-.46-.06-.14-.64-1.54-.88-2.1-.23-.55-.46-.47-.63-.48-.16-.01-.35-.01-.54-.01s-.45.06-.69.31c-.24.24-.92.9-.92 2.2 0 1.29.94 2.54 1.07 2.72.14.17 1.86 2.84 4.51 3.87 2.66 1.03 2.66.69 3.14.65.48-.04 1.56-.63 1.78-1.24.22-.61.22-1.13.15-1.24-.07-.1-.26-.16-.55-.3z"/>
                                                    </svg>
                                                    <span class="text-sm">WhatsApp</span>
                                                </a>
                                                <button v-else disabled aria-disabled="true" title="Paciente sin teléfono" class="inline-flex items-center rounded-md border border-gray-300 bg-gray-100 px-4 py-1 text-xs font-semibold uppercase tracking-widest text-gray-400 shadow-sm transition duration-150 ease-in-out cursor-not-allowed opacity-70 mr-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 mr-1 text-gray-400">
                                                        <path d="M20.52 3.48A11.92 11.92 0 0012 0C5.373 0 .102 4.917.001 11.17a11.93 11.93 0 002.13 6.19L0 24l6.86-2.04A11.92 11.92 0 0012 24c6.627 0 11.999-4.917 12-11.17 0-1.86-.42-3.62-1.48-5.35zM12 21.5c-1.3 0-2.57-.3-3.7-.86l-.27-.14-4.08 1.21 1.15-3.98-.17-.33A9.5 9.5 0 012.5 11.17 9.5 9.5 0 1112 21.5z"/>
                                                        <path d="M17.16 14.03c-.28-.14-1.66-.82-1.92-.91-.26-.09-.45-.14-.64.14s-.73.91-.9 1.1c-.17.19-.34.21-.63.07-.3-.14-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.6.13-.13.3-.34.45-.51.15-.17.2-.28.3-.46.09-.17.04-.32-.02-.46-.06-.14-.64-1.54-.88-2.1-.23-.55-.46-.47-.63-.48-.16-.01-.35-.01-.54-.01s-.45.06-.69.31c-.24.24-.92.9-.92 2.2 0 1.29.94 2.54 1.07 2.72.14.17 1.86 2.84 4.51 3.87 2.66 1.03 2.66.69 3.14.65.48-.04 1.56-.63 1.78-1.24.22-.61.22-1.13.15-1.24-.07-.1-.26-.16-.55-.3z"/>
                                                    </svg>
                                                    <span class="text-sm">WhatsApp</span>
                                                </button>
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
                                <!-- Mobile: prev/next -->
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <Link
                                        v-if="appointments.prev_page_url"
                                        :href="(function(){ try { const u = new URL(appointments.prev_page_url, window.location.origin); u.searchParams.set('view', currentView.value); return u.toString() } catch(e){ return appointments.prev_page_url } })()"
                                        class="relative inline-flex items-center px-4 py-2 border border-transparent bg-gray-800 text-xs font-semibold uppercase tracking-widest text-white rounded-md hover:bg-gray-700"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M12.293 16.293a1 1 0 010 1.414l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L8.414 10l5.293 5.293a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
                                        Anterior
                                    </Link>
                                    <Link
                                        v-if="appointments.next_page_url"
                                        :href="(function(){ try { const u = new URL(appointments.next_page_url, window.location.origin); u.searchParams.set('view', currentView.value); return u.toString() } catch(e){ return appointments.next_page_url } })()"
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-transparent bg-gray-800 text-xs font-semibold uppercase tracking-widest text-white rounded-md hover:bg-gray-700"
                                    >
                                        Siguiente
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M7.707 3.707a1 1 0 010-1.414l6 6a1 1 0 010 1.414l-6 6A1 1 0 016.293 15.293L11.586 10 6.293 4.707a1 1 0 011.414-1.414z" clip-rule="evenodd"/></svg>
                                    </Link>
                                </div>

                                <!-- Desktop: numeric pagination -->
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-center">
                                    <div>
                                        <span class="relative z-0 inline-flex shadow-sm rounded-md">
                                            <template v-for="(link, idx) in appointments.links" :key="idx">
                                                <Link
                                                    v-if="link.url"
                                                    :href="(function(){ try { const u = new URL(link.url, window.location.origin); u.searchParams.set('view', currentView.value); return u.toString() } catch(e){ return link.url } })()"
                                                    :class="[ 'relative inline-flex items-center px-4 py-2 border text-sm font-medium', link.active ? 'bg-gray-800 text-white border-transparent' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' ]"
                                                >
                                                    {{ decodeHtml(link.label) }}
                                                </Link>
                                                <span v-else class="relative inline-flex items-center px-4 py-2 border bg-white text-gray-500 text-sm">{{ decodeHtml(link.label) }}</span>
                                            </template>
                                        </span>
                                    </div>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Modal para crear (nuevo) -->
                        <AppointmentModal
                            :show="showCreateModal"
                            :appointment="null"
                            :selected-date="selectedDate"
                            :doctors="doctors"
                            :patients="patients"
                            :specialties="specialties"
                            :initial-specialty-id="filters.specialty_id"
                            @close="closeModal"
                            @saved="appointmentSaved"
                        />

                        <!-- Modal independiente para editar -->
                                <AppointmentEditModal
                                    :show="showEditModal"
                                    :appointment="selectedAppointment"
                                    :doctors="doctors"
                                    :patients="patients"
                                    :specialties="specialties"
                                    :force-edit="directOpenEdit"
                                    @close="closeModal"
                                    @saved="appointmentSaved"
                                    @consumed-force-edit="directOpenEdit = false"
                                />
                        <!-- Modal detalle simple para citas (abre al click en el calendario) -->
                        <AppointmentDetailModal
                            :show="showDetailModal"
                            :appointment="selectedAppointment"
                            :user-permissions="user_permissions"
                            @close="() => { showDetailModal = false; selectedAppointment = null }"
                            @edit="openEditFromDetail"
                            @delete="(appt) => { showDetailModal = false; deleteAppointment(appt) }"
                            @print="(appt) => { printSingle(appt) }"
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
import AppointmentDetailModal from '@/Components/AppointmentDetailModal.vue'
import AppointmentEditModal from '@/Components/AppointmentEditModal.vue'
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
const showDetailModal = ref(false)
const selectedDate = ref(null)
const availableDays = ref([])
const loadingAvailableDays = ref(false)
const directOpenEdit = ref(false)

const filters = ref({
    doctor_id: props.filters?.doctor_id || '',
    specialty_id: props.filters?.specialty_id || '',
    status: props.filters?.status || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
})

// Computed: doctores filtrados según specialty selecccionada
const filteredDoctors = computed(() => {
    if (!filters.value.specialty_id) {
        return props.doctors || []
    }

    return (props.doctors || []).filter(d => {
        return d.specialties && d.specialties.some(s => String(s.id) === String(filters.value.specialty_id))
    })
})

// Computed: especialidades filtradas según doctor seleccionado
const filteredSpecialties = computed(() => {
    if (!filters.value.doctor_id) {
        return props.specialties || []
    }

    const doc = (props.doctors || []).find(d => String(d.id) === String(filters.value.doctor_id))
    if (!doc || !doc.specialties) return []
    // Devolver solo especialidades activas del doctor (props.specialties tiene is_active)
    return doc.specialties.filter(s => s.is_active)
})

// Watchers: si la selección de doctor cambia, y la specialty actual no pertenece al doctor, resetearla
watch(() => filters.value.doctor_id, (newDoctor) => {
    if (!newDoctor) return
    const doc = (props.doctors || []).find(d => String(d.id) === String(newDoctor))
    if (!doc) {
        filters.value.specialty_id = ''
        applyFilters()
        return
    }
    const specialtyIds = (doc.specialties || []).map(s => String(s.id))
    if (filters.value.specialty_id && !specialtyIds.includes(String(filters.value.specialty_id))) {
        filters.value.specialty_id = ''
        applyFilters()
    }
})

// Si cambia la specialty y el doctor seleccionado no pertenece, resetear doctor
watch(() => filters.value.specialty_id, (newSpecialty) => {
    if (!newSpecialty) return
    const matchingDoctors = filteredDoctors.value.map(d => String(d.id))
    if (filters.value.doctor_id && !matchingDoctors.includes(String(filters.value.doctor_id))) {
        filters.value.doctor_id = ''
        applyFilters()
    }
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

// Decode HTML entities like &laquo; coming from paginator labels
const decodeHtml = (html) => {
    const txt = document.createElement('textarea')
    txt.innerHTML = html || ''
    return txt.value
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
    
    // If the appointment object is a lightweight calendar event (doesn't include patient/doctor objects),
    // fetch the full appointment from the API before opening the edit modal so the edit form is prefilled.
    const hasFullData = appointment && (appointment.patient || appointment.doctor || appointment.specialty)
    if (hasFullData) {
        selectedAppointment.value = appointment
        selectedDate.value = null
        showEditModal.value = true
        return
    }

    // Fetch full appointment via API and then open modal
    (async () => {
        try {
            const res = await axios.get(`/api/appointments/${appointment.id}`)
            const data = res.data || {}
            // Prefer full appointment object from API if present
            const full = data.appointment || appointment
            // Attach can_edit flag if provided
            if (typeof data.can_edit !== 'undefined') full.can_edit = data.can_edit
            selectedAppointment.value = full
            selectedDate.value = null
            showEditModal.value = true
        } catch (e) {
            console.error('Error fetching full appointment for edit:', e)
            // Fallback: open modal with provided object (best-effort)
            selectedAppointment.value = appointment
            selectedDate.value = null
            showEditModal.value = true
        }
    })()
}

// Called from AppointmentDetailModal when user clicks Edit there.
// This ensures we close the detail modal first and then open the edit modal
// avoiding the extra intermediate modal appearance.
const openEditFromDetail = async (appointment) => {
    // Close detail modal immediately
    showDetailModal.value = false

    // Try to fetch full appointment data via API so the edit modal receives the complete object
    try {
        const res = await axios.get(`/api/appointments/${appointment.id}`)
        const data = res.data || {}
        const full = data.appointment || appointment
        if (typeof data.can_edit !== 'undefined') full.can_edit = data.can_edit
        selectedAppointment.value = full
        // Mark force edit so modal opens in edit mode
        directOpenEdit.value = true
        showEditModal.value = true
    } catch (e) {
        console.error('Error fetching appointment for edit from detail:', e)
        // Fallback: open with lightweight object — user can press Edit inside to trigger permission check
        selectedAppointment.value = appointment
        directOpenEdit.value = true
        showEditModal.value = true
    }
}

// Mostrar detalle de cita (invocado desde el calendario)
const showAppointmentDetail = ({ event }) => {
    if (!event) return
    // Normalizar un objeto liviano para pasar al modal
    const appt = {
        id: event.id,
        title: event.title,
        start: event.start ? event.start.toISOString() : event.startStr || null,
        end: event.end ? event.end.toISOString() : event.endStr || null,
        appointment_date: event.start ? event.start.toISOString() : event.startStr || event.extendedProps?.start || null,
        extendedProps: event.extendedProps || {}
    }
    selectedAppointment.value = appt
    showDetailModal.value = true
}

const printSingle = (appointment) => {
    if (!appointment || !appointment.id) return
    const url = `/appointments/${appointment.id}/print`
    window.open(url, '_blank')
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
    loadingAvailableDays.value = true
    
    try {
        const response = await axios.get('/api/specialty-available-days', {
            params: { specialty_id: specialtyId }
        })
        availableDays.value = response.data.available_days || []
    } catch (error) {
        console.error('❌ Error loading available days:', error)
        availableDays.value = []
    } finally {
        loadingAvailableDays.value = false
    }
}

// Formatea teléfono: elimina espacios, paréntesis, guiones y signos '+' (preservar country code si ya incluido)
const formatPhone = (phone) => {
    if (!phone) return ''
    // Mantener sólo dígitos y el prefijo '+' si existe
    const cleaned = phone.replace(/[^+\d]/g, '')
    // WhatsApp requiere el formato internacional sin signos '+' en la URL
    return cleaned.replace(/^\+/, '')
}

// Construye la URL de WhatsApp para confirmar la cita
const formatWhatsAppUrl = (appointment) => {
    const phone = getPatientPhone(appointment)
    if (!phone) return '#'
    const phoneClean = formatPhone(phone)
    const date = new Date(appointment.appointment_date)
    const dateText = date.toLocaleString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
    const patientName = appointment.patient?.user?.name || ''
    const doctorName = appointment.doctor?.user?.name || ''
    const specialty = appointment.specialty?.name || 'General'
    const message = `Hola ${patientName}, te contactamos desde el consultorio para confirmar tu cita con ${doctorName} (${specialty}) el ${dateText}. Por favor responde "CONFIRMO" si asistirás.`
    const encoded = encodeURIComponent(message)
    return `https://wa.me/${phoneClean}?text=${encoded}`
}

// Abre la vista imprimible de la lista con los filtros actuales como query string
const printList = () => {
    // Evitar abrir cuando no hay resultados
    if (!hasListResults.value) {
        return
    }

    // Construir query string desde filters.value
    const params = new URLSearchParams()
    for (const [k, v] of Object.entries(filters.value)) {
        if (v !== null && v !== undefined && String(v) !== '') {
            params.append(k, String(v))
        }
    }

    // Si la cantidad de resultados es muy grande, pasar un indicador para que la vista muestre aviso
    const total = appointments?.data?.length || 0
    if (total > 500) {
        params.append('large', '1')
    }

    const url = `/appointments/print-list${params.toString() ? `?${params.toString()}` : ''}`
    // Abrir en nueva ventana para que el usuario pueda usar control de impresión del navegador
    window.open(url, '_blank')
}

// Computed para decidir si hay resultados en la lista actualmente
const appointments = computed(() => props.appointments || null)
const hasListResults = computed(() => {
    try {
        return appointments.value && appointments.value.data && appointments.value.data.length > 0
    } catch (e) {
        return false
    }
})

import { STATUS_COLORS, getStatusColor } from '@/colors/appointmentColors.js'

// Obtener el número de teléfono del paciente probando varios campos y estructuras comunes
const getPatientPhone = (appointment) => {
    if (!appointment) return ''

    // posibles ubicaciones
    const patient = appointment.patient || {}

    // direct field on patient
    if (patient.phone) return String(patient.phone)

    // user object
    const user = patient.user || {}
    const candidates = [
        user.phone,
        user.phone_number,
        user.mobile,
        user.whatsapp,
        user.telefono,
        user.contact_phone,
    ]

    for (const c of candidates) {
        if (c && String(c).trim() !== '') return String(c)
    }

    // arrays of phones (por ejemplo phones: [{number: '...'}])
    if (Array.isArray(user.phones) && user.phones.length) {
        const first = user.phones[0]
        if (first) {
            if (first.number) return String(first.number)
            if (first.phone) return String(first.phone)
        }
    }

    // fallback: any other possible top-level phone-like props on user
    for (const key of Object.keys(user)) {
        if (/phone|tel|mobile|contact|whatsapp/i.test(key) && user[key]) {
            return String(user[key])
        }
    }

    return ''
}

const hasWhatsapp = (appointment) => {
    const raw = getPatientPhone(appointment)
    if (!raw) return false
    // limpiar y mantener sólo dígitos
    const digits = String(raw).replace(/\D/g, '')
    // requerir al menos 6 dígitos para considerar válido
    return digits.length >= 6
}

// Badge style (only the status pill will be colored)
const getBadgeStyle = (status) => {
    const c = getStatusColor(status)
    return {
        backgroundColor: c.bg,
        color: c.text
    }
}

// Fallback: asegurar que cualquier enlace del paginador en el DOM incluya el param 'view'
const updatePaginationLinks = () => {
    try {
        // buscar dentro del contenedor de paginación
        const container = document.querySelector('.mt-4')
        if (!container) return
        const anchors = container.querySelectorAll('a[href]')
        anchors.forEach(a => {
            try {
                const href = a.getAttribute('href') || ''
                const u = new URL(href, window.location.origin)
                u.searchParams.set('view', currentView.value)
                // Usar ruta relativa si el origin coincide con el actual
                const final = u.toString()
                a.setAttribute('href', final)
            } catch (e) {
                // ignore malformed urls
            }
        })
    } catch (e) {
        // ignore
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
    // Inicializar vista desde query param 'view' para preservar vista al paginar
    try {
        const params = new URLSearchParams(window.location.search)
        const v = params.get('view')
        if (v === 'list' || v === 'calendar') currentView.value = v
    } catch (e) {
        // ignore
    }
    // aplicar parche runtime a links de paginación
    updatePaginationLinks()
})

// Mantener el parámetro 'view' en la URL cuando cambia la vista (no recarga)
watch(currentView, (v) => {
    try {
        const url = new URL(window.location.href)
        const params = url.searchParams
        if (v) params.set('view', v)
        url.search = params.toString()
        history.replaceState(null, '', url.toString())
    } catch (e) {
        // ignore
    }
    // cuando cambie la vista, asegurarse que los links de paginación en el DOM se actualicen
    updatePaginationLinks()
})
</script>

// Nota: diagnósticos temporales removidos

<style scoped>
/* Estilos para los badges de estado: fondo con algo de opacidad y bordes suaves */
td > span {
    box-shadow: 0 1px 0 rgba(0,0,0,0.03) inset;
    border: 1px solid rgba(0,0,0,0.06);
}

/* Si el badge tiene color oscuro, asegurar texto en blanco */
.badge-dark {
    color: #ffffff !important;
}
</style>
