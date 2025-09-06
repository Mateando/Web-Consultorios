<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="$emit('close')"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg overflow-hidden text-left shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-2xl h-[72vh] sm:h-[72vh] max-h-[80vh]">
                <form @submit.prevent="submitForm" class="h-full flex flex-col">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 overflow-y-auto flex-1 min-h-0">
                        <div class="sm:flex sm:items-start">
                            <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                                                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                                            {{ isEditing ? 'Editar Cita' : 'Nueva Cita' }}
                                                        </h3>

                                                        <!-- Step 1: Paciente -->
                                                        <div v-show="step === 1" class="mb-4">
                                                            <label for="patient_search" class="block text-sm font-medium text-gray-700">Paciente</label>
                                                            <div class="mt-1 relative">
                                                                <input
                                                                    id="patient_search"
                                                                    type="search"
                                                                    v-model="patientQuery"
                                                                    @input="onPatientQueryChange"
                                                                    placeholder="Buscar por nombre, documento o email..."
                                                                    class="block w-full pr-10 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                                />
                                                                <div v-if="loadingPatients" class="absolute right-2 top-2 text-sm text-gray-500">Cargando...</div>
                                                                <ul
                                                                    v-if="showPatientDropdown"
                                                                    class="absolute left-0 right-0 z-[120] mt-1 w-full bg-white border border-gray-200 rounded-md max-h-64 overflow-y-auto shadow-lg ring-1 ring-black/5 text-left"
                                                                    style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;"
                                                                >
                                                                    <li v-if="patientResults.length === 0" class="px-3 py-2 text-sm text-gray-500 select-none">Sin resultados</li>
                                                                    <li
                                                                        v-for="p in patientResults"
                                                                        :key="p.id"
                                                                        @click="selectPatient(p)"
                                                                        class="px-3 py-2 text-sm cursor-pointer transition-colors hover:bg-indigo-50 focus:bg-indigo-50 focus:outline-none"
                                                                    >
                                                                        <div class="font-medium text-gray-800 truncate">{{ p.name || 'Paciente sin nombre' }}</div>
                                                                        <div class="text-xs text-gray-500 flex flex-wrap gap-x-1">
                                                                            <span>{{ (p.document_type ? p.document_type.toUpperCase() + ' ' : '') + (p.document_number || '') }}</span>
                                                                            <span v-if="p.phone" class="hidden sm:inline">• {{ p.phone }}</span>
                                                                        </div>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                            <div v-if="form.patient_id" class="mt-2 text-sm text-green-600">Paciente seleccionado: {{ patientName }}</div>
                                                            <div v-if="errors.patient_id" class="mt-1 text-sm text-red-600">{{ errors.patient_id }}</div>
                                                        </div>

                                                        <!-- Step 2: Especialidad / Doctor con buscadores -->
                                                        <div v-show="step === 2" class="mb-4">
                                                            <label class="block text-sm font-medium text-gray-700">Especialidad</label>
                                                            <div class="mt-1 relative" ref="specialtyBox" @keydown.down.prevent="focusNextSpecialty()" @keydown.up.prevent="focusPrevSpecialty()" @keydown.enter.prevent="selectFocusedSpecialty()">
                                                                <div class="flex items-center gap-2">
                                                                    <div class="relative flex-1">
                                                                        <input
                                                                            type="text"
                                                                            v-model="specialtyQuery"
                                                                            @focus="openSpecialtyDropdown()"
                                                                            placeholder="Buscar / seleccionar..."
                                                                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm pr-8"
                                                                        />
                                                                        <button type="button" @click="toggleSpecialtyDropdown" class="absolute inset-y-0 right-0 px-2 text-gray-500 hover:text-gray-700 focus:outline-none">
                                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <input type="hidden" id="specialty_id" v-model="form.specialty_id" />
                                                                <ul
                                                                    v-if="showSpecialtyDropdown"
                                                                    class="absolute z-[120] mt-1 w-full bg-white border border-gray-200 rounded-md max-h-60 overflow-auto shadow-lg ring-1 ring-black/5 text-left"
                                                                    @mousedown.stop
                                                                >
                                                                    <li v-if="filteredSpecialties.length===0" class="px-3 py-2 text-sm text-gray-500 select-none">Sin resultados</li>
                                                                    <li
                                                                        v-for="(specialty,idx) in filteredSpecialties"
                                                                        :key="specialty.id"
                                                                        :class="['px-3 py-2 text-sm cursor-pointer flex items-center justify-between', idx===focusedSpecialtyIndex ? 'bg-blue-50 text-blue-700' : 'hover:bg-gray-50']"
                                                                        @mouseenter="focusedSpecialtyIndex=idx"
                                                                        @mouseleave="focusedSpecialtyIndex=-1"
                                                                        @click="pickSpecialty(specialty)"
                                                                    >
                                                                        <span class="truncate">{{ specialty.name }}</span>
                                                                        <svg v-if="String(form.specialty_id)===String(specialty.id)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3.5-3.5a1 1 0 011.414-1.414l2.793 2.793 6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                            <div v-if="errors.specialty_id" class="mt-1 text-sm text-red-600">{{ errors.specialty_id }}</div>

                                                            <div class="mt-4">
                                                                <label class="block text-sm font-medium text-gray-700">Doctor</label>
                                                                <div class="mt-1 relative" ref="doctorBox" @keydown.down.prevent="focusNextDoctor()" @keydown.up.prevent="focusPrevDoctor()" @keydown.enter.prevent="selectFocusedDoctor()">
                                                                    <div class="relative">
                                                                        <input
                                                                            type="text"
                                                                            v-model="doctorQuery"
                                                                            @focus="openDoctorDropdown()"
                                                                            :placeholder="!form.specialty_id ? 'Seleccione primero una especialidad' : 'Buscar / seleccionar...'"
                                                                            :disabled="!form.specialty_id"
                                                                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm pr-8 disabled:bg-gray-100 disabled:cursor-not-allowed"
                                                                        />
                                                                        <button type="button" @click="toggleDoctorDropdown" :disabled="!form.specialty_id" class="absolute inset-y-0 right-0 px-2 text-gray-500 hover:text-gray-700 focus:outline-none disabled:opacity-40">
                                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                                                        </button>
                                                                    </div>
                                                                    <input type="hidden" id="doctor_id" v-model="form.doctor_id" />
                                                                    <ul
                                                                        v-if="showDoctorDropdown && form.specialty_id"
                                                                        class="absolute z-[120] mt-1 w-full bg-white border border-gray-200 rounded-md max-h-60 overflow-auto shadow-lg ring-1 ring-black/5 text-left"
                                                                        @mousedown.stop
                                                                    >
                                                                        <li v-if="filteredDoctorsComputed.length===0" class="px-3 py-2 text-sm text-gray-500 select-none">Sin resultados</li>
                                                                        <li
                                                                            v-for="(doctor,idx) in filteredDoctorsComputed"
                                                                            :key="doctor.id"
                                                                            :class="['px-3 py-2 text-sm cursor-pointer flex items-center justify-between', idx===focusedDoctorIndex ? 'bg-blue-50 text-blue-700' : 'hover:bg-gray-50']"
                                                                            @mouseenter="focusedDoctorIndex=idx"
                                                                            @mouseleave="focusedDoctorIndex=-1"
                                                                            @click="pickDoctor(doctor)"
                                                                        >
                                                                            <span class="truncate">{{ doctor.name }} <span v-if="doctor.license_number" class="text-xs text-gray-500">- {{ doctor.license_number }}</span></span>
                                                                            <svg v-if="String(form.doctor_id)===String(doctor.id)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3.5-3.5a1 1 0 011.414-1.414l2.793 2.793 6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                                <div v-if="errors.doctor_id" class="mt-1 text-sm text-red-600">{{ errors.doctor_id }}</div>
                                                                <div v-if="form.specialty_id && filteredDoctorsComputed.length === 0" class="mt-1 text-sm text-yellow-600">No hay doctores disponibles para esta especialidad</div>
                                                            </div>
                                                        </div>

                                                        <!-- Step 3: Tipo de estudio (si aplica) -->
                                                        <div v-show="step === 3 && hasStudyTypes" class="mb-4">
                                                            <label for="study_type_id" class="block text-sm font-medium text-gray-700">Tipo de estudio</label>
                                                            <input
                                                                type="text"
                                                                v-model="studyQuery"
                                                                placeholder="Buscar estudio..."
                                                                class="mt-1 mb-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                            />
                                                            <select id="study_type_id" v-model="form.study_type_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                                                <option value="">Seleccionar tipo de estudio</option>
                                                                <option v-for="st in filteredDoctorStudyTypes" :key="st.id" :value="st.id">{{ st.name }}</option>
                                                            </select>
                                                            <div v-if="errors.study_type_id" class="mt-1 text-sm text-red-600">{{ errors.study_type_id }}</div>
                                                        </div>

                                                        <!-- Step 4: Fecha / Hora -->
                                                        <div v-show="step === (hasStudyTypes ? 4 : 3)" class="mb-4">
                                                            <label for="appointment_date" class="block text-sm font-medium text-gray-700">Fecha</label>
                                                            <input id="appointment_date" type="date" v-model="form.appointment_date" @change="onDateChange" :min="minDate" :disabled="form.specialty_id && (loadingDays || availableDays.length === 0)" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" :class="{ 'bg-gray-100 cursor-not-allowed': form.specialty_id && availableDays.length === 0 }">
                                                            <div v-if="errors.appointment_date" class="mt-1 text-sm text-red-600">{{ errors.appointment_date }}</div>
                                                            <div v-if="availableDaysMessage" class="mt-1 text-sm text-blue-600">{{ availableDaysMessage }}</div>
                                                            <div v-if="form.specialty_id && availableDays.length === 0 && !loadingDays" class="mt-1 text-sm text-red-600 font-medium">⚠️ Esta especialidad no tiene horarios de atención configurados. No es posible crear citas.</div>

                                                            <div class="mt-4">
                                                                <label for="appointment_time" class="block text-sm font-medium text-gray-700">Hora</label>
                                                                <select id="appointment_time" v-model="form.appointment_time" :disabled="!form.doctor_id || !form.appointment_date || loadingSlots" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                                                    <option value="">{{ !form.doctor_id ? 'Seleccione primero un doctor' : !form.appointment_date ? 'Seleccione primero una fecha' : loadingSlots ? 'Cargando horarios...' : 'Seleccionar hora' }}</option>
                                                                    <option v-for="slot in availableSlots" :key="`slot-${slot}`" :value="slot">{{ slot }}</option>
                                                                </select>
                                                                <div v-if="errors.appointment_time" class="mt-1 text-sm text-red-600">{{ errors.appointment_time }}</div>
                                                                <div v-if="form.doctor_id && form.appointment_date && availableSlots.length === 0 && !loadingSlots" class="mt-1 text-sm text-yellow-600">No hay horarios disponibles para esta fecha</div>
                                                                <div v-if="form.doctor_id && form.appointment_date && availableSlots.length > 0" class="mt-1 text-sm text-green-600">{{ availableSlots.length }} horarios disponibles</div>
                                                            </div>
                                                        </div>

                                                        <!-- Step 5: Detalles (estado deshabilitado en creación) -->
                                                        <div v-show="step === (hasStudyTypes ? 5 : 4)" class="mb-4">
                                                            <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
                                                            <select id="status" v-model="form.status" :disabled="!isEditing" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-gray-100 cursor-not-allowed">
                                                                <option value="programada">Programada</option>
                                                                <option value="confirmada">Confirmada</option>
                                                                <option value="en_curso">En Curso</option>
                                                                <option value="completada">Completada</option>
                                                                <option value="cancelada">Cancelada</option>
                                                                <option value="no_asistio">No Asistió</option>
                                                            </select>
                                                            <div v-if="errors.status" class="mt-1 text-sm text-red-600">{{ errors.status }}</div>

                                                            <div class="mt-4">
                                                                    <label for="reason" class="block text-sm font-medium text-gray-700">Motivo de la consulta</label>
                                                                    <select id="reason" v-model="form.reason_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                                                        <option value="">Seleccionar motivo</option>
                                                                        <option v-for="r in reasons" :key="r.id" :value="r.id">{{ r.name }}</option>
                                                                    </select>
                                                                    <div v-if="errors.reason" class="mt-1 text-sm text-red-600">{{ errors.reason }}</div>
                                                                </div>

                                                            <div class="mt-4">
                                                                <label for="notes" class="block text-sm font-medium text-gray-700">Notas adicionales</label>
                                                                <textarea id="notes" v-model="form.notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Notas adicionales..."></textarea>
                                                                <div v-if="errors.notes" class="mt-1 text-sm text-red-600">{{ errors.notes }}</div>
                                                            </div>

                                                            <div class="mt-4 border-t pt-4">
                                                                    <p class="text-sm text-gray-700">Estado: <strong>{{ form.status }}</strong></p>
                                                                </div>
                                                        </div>

                                                        <!-- Step 6: Resumen completo + opción de imprimir -->
                                                        <div v-show="step === maxStep" class="mb-4">
                                                            <div class="mt-0 border-b pb-2 mb-3">
                                                                <h4 class="text-lg font-medium text-gray-900">Resumen de la cita</h4>
                                                            </div>
                                                            <p class="text-sm text-gray-700">Paciente: <strong>{{ patientName }}</strong></p>
                                                            <p class="text-sm text-gray-700">Especialidad: <strong>{{ specialtyName }}</strong></p>
                                                            <p class="text-sm text-gray-700">Doctor: <strong>{{ doctorName }}</strong></p>
                                                            <p class="text-sm text-gray-700">Fecha y hora: <strong>{{ (form.appointment_date || '') + ' ' + (form.appointment_time || '') }}</strong></p>
                                                            <p class="text-sm text-gray-700">Duración: <strong>{{ referenceDurationText }}</strong></p>
                                                            <p class="text-sm text-gray-700">Estado: <strong>{{ form.status }}</strong></p>
                                                            <p class="text-sm text-gray-700" v-if="form.study_type_id">Estudio: <strong>{{ (doctorStudyTypes.find(s=>String(s.id)===String(form.study_type_id))||{}).name }}</strong></p>
                                                            <!-- Indicador de impresión -->
                                                            <div class="mt-3 flex items-center text-sm" :class="printAfter ? 'text-green-600' : 'text-gray-500'">
                                                                <span class="inline-flex items-center gap-1 font-medium">
                                                                    <span v-if="printAfter" class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-green-100 text-green-700">
                                                                        <!-- Check icon -->
                                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 00-1.408-1.42l-6.93 6.866-2.66-2.63a1 1 0 00-1.404 1.424l3.364 3.326a1 1 0 001.404 0l7.634-7.566z" clip-rule="evenodd" /></svg>
                                                                    </span>
                                                                    <span v-else class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-gray-200 text-gray-600">
                                                                        <!-- X icon -->
                                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                                                    </span>
                                                                    <span>{{ printAfter ? 'Se imprimirá al guardar' : 'No se imprimirá automáticamente' }}</span>
                                                                </span>
                                                            </div>
                                                            <div v-if="reasonName" class="mt-3">
                                                                <p class="text-sm font-medium text-gray-800">Motivo</p>
                                                                <p class="text-sm text-gray-700">{{ reasonName }}</p>
                                                            </div>
                                                            <div v-if="form.notes" class="mt-3">
                                                                <p class="text-sm font-medium text-gray-800">Notas</p>
                                                                <p class="text-sm text-gray-700">{{ form.notes }}</p>
                                                            </div>
                                                            <div class="mt-3" v-if="form.study_type_id">
                                                                <p class="text-sm font-medium text-gray-800">Estudio</p>
                                                                <p class="text-sm text-gray-700">{{ (doctorStudyTypes.find(s=>String(s.id)===String(form.study_type_id))||{}).name }}</p>
                                                            </div>
                                                            <div class="mt-4 flex items-center gap-2">
                                                                <input id="print_after" type="checkbox" v-model="printAfter" class="h-4 w-4 border-gray-300 rounded" />
                                                                <label for="print_after" class="text-sm text-gray-700 select-none">Imprimir al guardar</label>
                                                            </div>
                                                        </div>
                            </div>
                        </div>
                    </div>
                                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse items-center border-t">
                                                <div class="sm:ml-3 sm:w-auto w-full">
                                                    <PrimaryButton
                                                        v-if="step < maxStep"
                                                        type="button"
                                                        @click="nextStep"
                                                        :disabled="isNextDisabled"
                                                        class="w-full sm:w-auto"
                                                    >
                                                        Siguiente
                                                    </PrimaryButton>

                                                    <PrimaryButton
                                                        v-else
                                                        type="submit"
                                                        :disabled="processing || (form.specialty_id && availableDays.length === 0) || !canSubmit"
                                                        class="w-full sm:w-auto"
                                                    >
                                                        {{ processing ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Crear') }}
                                                    </PrimaryButton>
                                                </div>

                                                <div class="mt-3 sm:mt-0 sm:ml-3 sm:w-auto w-full flex gap-2 items-center">
                                                    <SecondaryButton v-if="step > 1" type="button" @click="prevStep" class="w-full sm:w-auto">Atrás</SecondaryButton>
                                                    <SecondaryButton v-if="step === maxStep" type="button" @click="printAppointment" :disabled="!savedOnce" class="w-full sm:w-auto">
                                                        <span class="inline-flex items-center gap-2">
                                                            <PrinterIcon class="h-4 w-4 text-gray-600" :title="'Imprimir'" />
                                                            <span class="sr-only">Imprimir</span>
                                                        </span>
                                                    </SecondaryButton>
                                                    <SecondaryButton type="button" @click="$emit('close')" class="w-full sm:w-auto">Cancelar</SecondaryButton>
                                                </div>
                                            </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import Swal from 'sweetalert2'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import PrinterIcon from '@/Components/icons/PrinterIcon.vue'
import { usePage } from '@inertiajs/vue3'
import { buildAppointmentPrintContent } from '@/utils/printAppointment'

const props = defineProps({
    show: Boolean,
    appointment: Object,
    doctors: Array,
    patients: Array,
    specialties: Array,
    selectedDate: String, // Fecha seleccionada del calendario
    initialSpecialtyId: [String, Number],
    initialDoctorId: [String, Number],
    initialStudyTypeId: [String, Number],
    studyTypes: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'saved'])

const processing = ref(false)
const errors = ref({})
const filteredDoctors = ref([]) // base recibidos por especialidad
const doctorQuery = ref('')
const specialtyQuery = ref('')
const studyQuery = ref('')
const availableSlots = ref([])
const loadingSlots = ref(false)
const availableDays = ref([])
const loadingDays = ref(false)
// Dropdown especialidad mejorado (combobox)
const showSpecialtyDropdown = ref(false)
const focusedSpecialtyIndex = ref(-1)
const specialtyBox = ref(null)
// Combobox doctor
const showDoctorDropdown = ref(false)
const focusedDoctorIndex = ref(-1)
const doctorBox = ref(null)
// Appointment reasons
const reasons = ref([])
// Pacientes búsqueda dinámica
const patientQuery = ref('')
const patientResults = ref([])
const loadingPatients = ref(false)
const showPatientDropdown = ref(false)
let patientDebounceTimer = null

// Wizard state
const step = ref(1)
// Si hay tipos de estudio se agrega un paso extra (resumen pasa a ser 6)
const maxStep = computed(() => hasStudyTypes.value ? 6 : 5)

const patientName = computed(() => {
    const p = props.patients?.find(pt => pt.id === form.value.patient_id)
    return p ? (p.user?.name || 'Paciente sin nombre') : ''
})

const specialtyName = computed(() => {
    const s = props.specialties?.find(sp => sp.id === form.value.specialty_id)
    return s ? s.name : ''
})

const doctorName = computed(() => {
    const d = filteredDoctors.value?.find(doc => doc.id === form.value.doctor_id) || props.doctors?.find(doc => doc.id === form.value.doctor_id)
    return d ? (d.name || '') : ''
})

const reasonName = computed(() => {
    // Prefer explicit reason text if present (backwards compatibility), otherwise map from reasons list
    if (form.value.reason) return form.value.reason
    const r = reasons.value?.find(x => String(x.id) === String(form.value.reason_id))
    return r ? r.name : ''
})

const hasStudyTypes = computed(() => (props.studyTypes && props.studyTypes.length > 0))

const doctorStudyTypes = computed(() => {
    const d = (props.doctors || []).find(x => String(x.id) === String(form.value.doctor_id))
    if (!d) return props.studyTypes || []
    return d.study_types || props.studyTypes || []
})

// --- Combobox especialidad ---
const openSpecialtyDropdown = () => {
    if (!showSpecialtyDropdown.value) {
        showSpecialtyDropdown.value = true
        // Si no hay query, igualmente mostrar todo y enfocar primer item
        nextTick(() => {
            if (filteredSpecialties.value.length > 0) focusedSpecialtyIndex.value = 0
        })
    }
}
const closeSpecialtyDropdown = () => {
    showSpecialtyDropdown.value = false
    focusedSpecialtyIndex.value = -1
}
const toggleSpecialtyDropdown = () => {
    showSpecialtyDropdown.value ? closeSpecialtyDropdown() : openSpecialtyDropdown()
}
watch(() => specialtyQuery.value, () => {
    if (!showSpecialtyDropdown.value) openSpecialtyDropdown()
    // Reiniciar índice sólo si hay resultados
    if (filteredSpecialties.value.length > 0) {
        focusedSpecialtyIndex.value = 0
    } else {
        focusedSpecialtyIndex.value = -1
    }
})
const focusNextSpecialty = () => {
    if (!showSpecialtyDropdown.value) { openSpecialtyDropdown(); return }
    if (filteredSpecialties.value.length === 0) return
    focusedSpecialtyIndex.value = (focusedSpecialtyIndex.value + 1) % filteredSpecialties.value.length
}
const focusPrevSpecialty = () => {
    if (!showSpecialtyDropdown.value) { openSpecialtyDropdown(); return }
    if (filteredSpecialties.value.length === 0) return
    focusedSpecialtyIndex.value = (focusedSpecialtyIndex.value - 1 + filteredSpecialties.value.length) % filteredSpecialties.value.length
}
const selectFocusedSpecialty = () => {
    if (focusedSpecialtyIndex.value < 0) return
    const s = filteredSpecialties.value[focusedSpecialtyIndex.value]
    if (s) pickSpecialty(s)
}
const pickSpecialty = (specialty) => {
    form.value.specialty_id = specialty.id
    specialtyQuery.value = specialty.name
    closeSpecialtyDropdown()
    onSpecialtyChange()
}

// --- Combobox Doctor ---
const openDoctorDropdown = () => {
    if (!form.value.specialty_id) return
    if (!showDoctorDropdown.value) {
        showDoctorDropdown.value = true
        nextTick(()=>{ if (filteredDoctorsComputed.value.length>0) focusedDoctorIndex.value=0 })
    }
}
const closeDoctorDropdown = () => { showDoctorDropdown.value = false; focusedDoctorIndex.value=-1 }
const toggleDoctorDropdown = () => { showDoctorDropdown.value ? closeDoctorDropdown() : openDoctorDropdown() }
watch(()=>doctorQuery.value, ()=>{ if(!showDoctorDropdown.value) openDoctorDropdown(); if(filteredDoctorsComputed.value.length>0) focusedDoctorIndex.value=0; else focusedDoctorIndex.value=-1 })
const focusNextDoctor = () => { if(!showDoctorDropdown.value) { openDoctorDropdown(); return } if(filteredDoctorsComputed.value.length===0) return; focusedDoctorIndex.value=(focusedDoctorIndex.value+1)%filteredDoctorsComputed.value.length }
const focusPrevDoctor = () => { if(!showDoctorDropdown.value) { openDoctorDropdown(); return } if(filteredDoctorsComputed.value.length===0) return; focusedDoctorIndex.value=(focusedDoctorIndex.value-1+filteredDoctorsComputed.value.length)%filteredDoctorsComputed.value.length }
const selectFocusedDoctor = () => { if(focusedDoctorIndex.value<0) return; const d=filteredDoctorsComputed.value[focusedDoctorIndex.value]; if(d) pickDoctor(d) }
const pickDoctor = (doctor) => { form.value.doctor_id = doctor.id; doctorQuery.value = doctor.name; closeDoctorDropdown(); onDoctorChange() }

// Cerrar al hacer clic fuera manualmente
const handleClickOutside = (e) => {
    if (!showSpecialtyDropdown.value) return
    const el = specialtyBox.value
    if (el && !el.contains(e.target)) {
        closeSpecialtyDropdown()
    }
}
const handleClickOutsideDoctor = (e) => {
    if (!showDoctorDropdown.value) return
    const el = doctorBox.value
    if (el && !el.contains(e.target)) closeDoctorDropdown()
}
onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside)
    document.addEventListener('mousedown', handleClickOutsideDoctor)
})
onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside)
    document.removeEventListener('mousedown', handleClickOutsideDoctor)
})

const filteredSpecialties = computed(() => {
    if (!specialtyQuery.value) return props.specialties || []
    const q = specialtyQuery.value.toLowerCase()
    return (props.specialties || []).filter(s => s.name.toLowerCase().includes(q))
})

const filteredDoctorsComputed = computed(() => {
    if (!doctorQuery.value) return filteredDoctors.value
    const q = doctorQuery.value.toLowerCase()
    return filteredDoctors.value.filter(d => (d.name || '').toLowerCase().includes(q) || (d.license_number || '').toLowerCase().includes(q))
})

const filteredDoctorStudyTypes = computed(() => {
    if (!studyQuery.value) return doctorStudyTypes.value
    const q = studyQuery.value.toLowerCase()
    return doctorStudyTypes.value.filter(st => st.name.toLowerCase().includes(q))
})

const isNextDisabled = computed(() => {
    if (step.value === 1) return !form.value.patient_id
    if (step.value === 2) return !form.value.specialty_id || !form.value.doctor_id
    if (hasStudyTypes.value && step.value === 3) return !form.value.study_type_id
    const dateStep = hasStudyTypes.value ? 4 : 3
    if (step.value === dateStep) return !form.value.appointment_date || !form.value.appointment_time
    return false
})

const canSubmit = computed(() => {
    if (!form.value.patient_id || !form.value.specialty_id || !form.value.doctor_id || !form.value.appointment_date || !form.value.appointment_time) return false
    if (hasStudyTypes.value && !form.value.study_type_id) return false
    return true
})

const nextStep = () => {
    if (step.value < maxStep.value && !isNextDisabled.value) {
        step.value += 1
    }
}

const prevStep = () => {
    if (step.value > 1) step.value -= 1
}

const referenceDurationText = computed(() => {
    // If availableDays/duration info provided by API is present in form or props, use it; fallback to 30m
    // We don't have the duration in form, so default text
    return (form.value.duration || 30) + ' minutos'
})

const printAppointment = () => {
    // Prevent printing if the appointment hasn't been saved yet
    if (!savedOnce.value) {
        alert('Debe guardar la cita antes de imprimirla para evitar inconsistencias en el calendario.')
        return
    }

    const page = usePage()
    const clinic = page.props?.clinic || null
    const content = buildAppointmentPrintContent({
        clinic,
        title: 'Resumen de Cita',
        patientName: patientName.value,
        specialtyName: specialtyName.value,
        doctorName: doctorName.value,
        date: form.value.appointment_date,
        time: form.value.appointment_time,
        status: form.value.status,
        durationText: referenceDurationText.value,
        studyName: form.value.study_type_id ? (doctorStudyTypes.value.find(s=>String(s.id)===String(form.value.study_type_id))?.name || '') : '',
        reasonText: form.value.reason || '',
        notesText: form.value.notes || '',
    })

    const win = window.open('', '_blank')
    if (!win) {
        alert('No se pudo abrir la ventana de impresión. Revisa el bloqueador de ventanas emergentes.')
        return
    }
    win.document.open()
    win.document.write(content)
    win.document.close()
    win.focus()
    setTimeout(() => {
        win.print()
    }, 250)
}

const onPatientQueryChange = () => {
    // If user clears query, hide dropdown
    if (!patientQuery.value) {
        patientResults.value = []
        showPatientDropdown.value = false
        return
    }

    // Debounce requests
    if (patientDebounceTimer) clearTimeout(patientDebounceTimer)
    patientDebounceTimer = setTimeout(() => {
        fetchPatients(patientQuery.value)
    }, 350)
}

const fetchPatients = async (q) => {
    loadingPatients.value = true
    showPatientDropdown.value = true
    try {
        const res = await axios.get('/api/patients', { params: { q, limit: 20 } })
        patientResults.value = res.data?.data || []
    } catch (e) {
        patientResults.value = []
    } finally {
        loadingPatients.value = false
    }
}

const selectPatient = (p) => {
    form.value.patient_id = p.id
    patientQuery.value = p.name || ''
    showPatientDropdown.value = false
}

const form = ref({
    patient_id: '',
    doctor_id: '',
    specialty_id: '',
    study_type_id: '',
    appointment_date: '',
    appointment_time: '',
    status: 'programada',
    reason: '',
    notes: '',
})

const isEditing = computed(() => {
    return props.appointment && props.appointment.id
})

// Track whether this appointment has been saved (existing appointments are already saved)
const savedOnce = ref(!!props.appointment?.id)

// Computed para generar el atributo min del input de fecha (solo fechas futuras) usando fecha local
const minDate = computed(() => {
    const today = new Date()
    const y = today.getFullYear()
    const m = String(today.getMonth() + 1).padStart(2, '0')
    const d = String(today.getDate()).padStart(2, '0')
    return `${y}-${m}-${d}`
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
        // Si se abre el modal para crear y hay specialty inicial, aplicarla
        if (!props.appointment && props.initialSpecialtyId) {
            // Esperar un tick para asegurar que form fue reseteado
            nextTick(() => {
                form.value.specialty_id = props.initialSpecialtyId
                onSpecialtyChange()
            })
        }
    loadReasons()
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
    const now = new Date()
    const todayLocal = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
    const selectedDateForForm = props.selectedDate || todayLocal

    form.value = {
        patient_id: '',
        doctor_id: '',
        specialty_id: '',
        appointment_date: selectedDateForForm,
        appointment_time: '',
        status: 'programada',
    reason_id: '',
        notes: '',
    }
    filteredDoctors.value = []
    availableSlots.value = []
    availableDays.value = []
    reasons.value = [] // Initialize reasons array
}

// Función para cargar doctores por especialidad (para nuevas citas)
const onSpecialtyChange = async () => {
    form.value.doctor_id = '' // Resetear doctor seleccionado
    availableSlots.value = [] // Resetear slots disponibles
    form.value.appointment_time = '' // Resetear hora seleccionada
    // Nota: no resetear la fecha aquí; conservar la fecha seleccionada para permitir
    // filtrar doctores que atienden ese día. La validación posterior limpiará la fecha
    // si la especialidad no tiene días disponibles.
    
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
        const params = { specialty_id: specialtyId }
        // Prefer the explicit date selected in the form, fall back to the calendar's selectedDate
        if (form.value.appointment_date) {
            params.date = form.value.appointment_date
        } else if (props.selectedDate) {
            params.date = props.selectedDate
        }
        const response = await axios.get('/api/doctors-by-specialty', { params })
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

const loadReasons = async () => {
    try {
        const resp = await axios.get(route('api.config.appointment-reasons'))
        reasons.value = resp.data || []
    } catch (e) { reasons.value = [] }
}

// Función para verificar si una fecha está disponible
const isDateAvailable = (dateString) => {
    if (!form.value.specialty_id || availableDays.value.length === 0) {
        return true // Si no hay especialidad seleccionada, permitir todas las fechas
    }
    // Parse dateString (YYYY-MM-DD) as local date to avoid timezone shifts that move the day
    try {
        const parts = String(dateString).split('-')
        if (parts.length < 3) return true
        const year = parseInt(parts[0], 10)
        const month = parseInt(parts[1], 10) - 1
        const day = parseInt(parts[2], 10)
        const date = new Date(year, month, day)
        const dayOfWeek = date.getDay() // 0 = domingo, 1 = lunes, etc.
        return availableDays.value.includes(dayOfWeek)
    } catch (e) {
        // Si falla el parseo, permitir la fecha para no bloquear la UI
        return true
    }
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
            date: form.value.appointment_date,
            specialty_id: form.value.specialty_id
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
            reason_id: props.appointment.reason_id ?? '',
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

const printAfter = ref(false)

const submitForm = async () => {
    processing.value = true
    errors.value = {}

    // Before submitting, ensure the selected slot is still valid and inside doctor's schedule
    if (form.value.doctor_id && form.value.appointment_date && form.value.appointment_time) {
        try {
            await loadAvailableSlots()
            if (!availableSlots.value.includes(form.value.appointment_time)) {
                processing.value = false
                errors.value = { appointment_time: 'La hora seleccionada ya no está disponible o está fuera del horario del doctor. Por favor elija otra.' }
                return
            }
        } catch (e) {
            // Si falla la validación remota, mostrar mensaje genérico y abortar
            processing.value = false
            errors.value = { appointment_time: 'No se pudo validar la disponibilidad de la hora. Intenta nuevamente.' }
            return
        }
    }

    // Combinar fecha y hora
    const appointmentDateTime = `${form.value.appointment_date} ${form.value.appointment_time}:00`

    const data = {
        ...form.value,
        appointment_date: appointmentDateTime,
        reason_id: form.value.reason_id || null,
        reason: reasonName.value || null,
        study_type_id: form.value.study_type_id || null,
    }

    // Remover campos que no necesitamos enviar
    delete data.appointment_time

    // Forzar estado programada en creación
    if (!isEditing.value) data.status = 'programada'

    const url = isEditing.value 
        ? `/appointments/${props.appointment.id}`
        : '/appointments'

    const method = isEditing.value ? 'put' : 'post'

    router[method](url, data, {
        onSuccess: () => {
            processing.value = false
            // mark as saved so printing is allowed
            savedOnce.value = true
            emit('saved')
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: isEditing.value ? 'Cita actualizada' : 'Cita creada',
                timer: 2000,
                width: 320,
                timerProgressBar: true,
                showConfirmButton: false,
                customClass: { popup: 'swal-compact-toast' }
            })
            setTimeout(() => {
                if (printAfter.value) {
                    try { printAppointment() } catch (e) {}
                }
                savedOnce.value = false
                resetForm()
                step.value = 1
                errors.value = {}
                printAfter.value = false
            }, 250)
        },
        onError: (errorResponse) => {
            console.log('Error al guardar la cita:', errorResponse)
            processing.value = false
            errors.value = errorResponse
        }
    })
}
</script>
