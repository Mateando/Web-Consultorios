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

                                                        <!-- Step 1: Paciente -->
                                                        <div v-show="step === 1" class="mb-4">
                                                            <label for="patient_id" class="block text-sm font-medium text-gray-700">Paciente</label>
                                                            <select
                                                                id="patient_id"
                                                                v-model="form.patient_id"
                                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                            >
                                                                <option value="">Seleccionar paciente</option>
                                                                <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                                                                    {{ patient.user?.name || 'Paciente sin nombre' }} - {{ patient.user?.document_type?.toUpperCase() }} {{ patient.user?.document_number }}
                                                                </option>
                                                            </select>
                                                            <div v-if="errors.patient_id" class="mt-1 text-sm text-red-600">{{ errors.patient_id }}</div>
                                                        </div>

                                                        <!-- Step 2: Especialidad / Doctor -->
                                                        <div v-show="step === 2" class="mb-4">
                                                            <label for="specialty_id" class="block text-sm font-medium text-gray-700">Especialidad</label>
                                                            <select id="specialty_id" v-model="form.specialty_id" @change="onSpecialtyChange" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                                                <option value="">Seleccionar especialidad</option>
                                                                <option v-for="specialty in specialties" :key="specialty.id" :value="specialty.id">{{ specialty.name }}</option>
                                                            </select>
                                                            <div v-if="errors.specialty_id" class="mt-1 text-sm text-red-600">{{ errors.specialty_id }}</div>

                                                            <div class="mt-4">
                                                                <label for="doctor_id" class="block text-sm font-medium text-gray-700">Doctor</label>
                                                                <select id="doctor_id" v-model="form.doctor_id" @change="onDoctorChange" :disabled="!form.specialty_id || filteredDoctors.length === 0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                                                    <option value="">{{ !form.specialty_id ? 'Seleccione primero una especialidad' : 'Seleccionar doctor' }}</option>
                                                                    <option v-for="doctor in filteredDoctors" :key="doctor.id" :value="doctor.id">{{ doctor.name }} - {{ doctor.license_number }}</option>
                                                                </select>
                                                                <div v-if="errors.doctor_id" class="mt-1 text-sm text-red-600">{{ errors.doctor_id }}</div>
                                                                <div v-if="form.specialty_id && filteredDoctors.length === 0" class="mt-1 text-sm text-yellow-600">No hay doctores disponibles para esta especialidad</div>
                                                            </div>
                                                        </div>

                                                        <!-- Step 3: Fecha / Hora -->
                                                        <div v-show="step === 3" class="mb-4">
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

                                                        <!-- Step 4: Detalles -->
                                                        <div v-show="step === 4" class="mb-4">
                                                            <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
                                                            <select id="status" v-model="form.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
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
                                                                <textarea id="reason" v-model="form.reason" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Describe el motivo de la consulta..."></textarea>
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

                                                        <!-- Step 5: Resumen completo -->
                                                        <div v-show="step === 5" class="mb-4">
                                                            <div class="mt-0 border-b pb-2 mb-3">
                                                                <h4 class="text-lg font-medium text-gray-900">Resumen de la cita</h4>
                                                            </div>
                                                            <p class="text-sm text-gray-700">Paciente: <strong>{{ patientName }}</strong></p>
                                                            <p class="text-sm text-gray-700">Especialidad: <strong>{{ specialtyName }}</strong></p>
                                                            <p class="text-sm text-gray-700">Doctor: <strong>{{ doctorName }}</strong></p>
                                                            <p class="text-sm text-gray-700">Fecha y hora: <strong>{{ form.appointment_date }} {{ form.appointment_time }}</strong></p>
                                                            <p class="text-sm text-gray-700">Duración: <strong>{{ referenceDurationText }}</strong></p>
                                                            <p class="text-sm text-gray-700">Estado: <strong>{{ form.status }}</strong></p>
                                                            <div v-if="form.reason" class="mt-3">
                                                                <p class="text-sm font-medium text-gray-800">Motivo</p>
                                                                <p class="text-sm text-gray-700">{{ form.reason }}</p>
                                                            </div>
                                                            <div v-if="form.notes" class="mt-3">
                                                                <p class="text-sm font-medium text-gray-800">Notas</p>
                                                                <p class="text-sm text-gray-700">{{ form.notes }}</p>
                                                            </div>
                                                        </div>
                            </div>
                        </div>
                    </div>
                                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse items-center">
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
                                                    <SecondaryButton v-if="step === maxStep" type="button" @click="printAppointment" class="w-full sm:w-auto">Imprimir</SecondaryButton>
                                                    <SecondaryButton type="button" @click="$emit('close')" class="w-full sm:w-auto">Cancelar</SecondaryButton>
                                                </div>
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
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { usePage } from '@inertiajs/vue3'

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

// Wizard state
const step = ref(1)
const maxStep = 5

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

const isNextDisabled = computed(() => {
    if (step.value === 1) return !form.value.patient_id
    if (step.value === 2) return !form.value.specialty_id || !form.value.doctor_id
    if (step.value === 3) return !form.value.appointment_date || !form.value.appointment_time
    return false
})

const canSubmit = computed(() => {
    // final check before submit
    return form.value.patient_id && form.value.specialty_id && form.value.doctor_id && form.value.appointment_date && form.value.appointment_time
})

const nextStep = () => {
    if (step.value < maxStep && !isNextDisabled.value) {
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
    const page = usePage()
    const clinic = page.props.value.clinic || null
    // Abrir nueva ventana con contenido simple para imprimir
    // Inline SVG from ApplicationLogo.vue
    const logoSvg = `<svg viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg" style="height:72px; width:72px"><path d="M305.8 81.125C305.77 80.995 305.69 80.885 305.65 80.755C305.56 80.525 305.49 80.285 305.37 80.075C305.29 79.935 305.17 79.815 305.07 79.685C304.94 79.515 304.83 79.325 304.68 79.175C304.55 79.045 304.39 78.955 304.25 78.845C304.09 78.715 303.95 78.575 303.77 78.475L251.32 48.275C249.97 47.495 248.31 47.495 246.96 48.275L194.51 78.475C194.33 78.575 194.19 78.725 194.03 78.845C193.89 78.955 193.73 79.045 193.6 79.175C193.45 79.325 193.34 79.515 193.21 79.685C193.11 79.815 192.99 79.935 192.91 80.075C192.79 80.285 192.71 80.525 192.63 80.755C192.58 80.875 192.51 80.995 192.48 81.125C192.38 81.495 192.33 81.875 192.33 82.265V139.625L148.62 164.795V52.575C148.62 52.185 148.57 51.805 148.47 51.435C148.44 51.305 148.36 51.195 148.32 51.065C148.23 50.835 148.16 50.595 148.04 50.385C147.96 50.245 147.84 50.125 147.74 49.995C147.61 49.825 147.5 49.635 147.35 49.485C147.22 49.355 147.06 49.265 146.92 49.155C146.76 49.025 146.62 48.885 146.44 48.785L93.99 18.585C92.64 17.805 90.98 17.805 89.63 18.585L37.18 48.785C37 48.885 36.86 49.035 36.7 49.155C36.56 49.265 36.4 49.355 36.27 49.485C36.12 49.635 36.01 49.825 35.88 49.995C35.78 50.125 35.66 50.245 35.58 50.385C35.46 50.595 35.38 50.835 35.3 51.065C35.25 51.185 35.18 51.305 35.15 51.435C35.05 51.805 35 52.185 35 52.575V232.235C35 233.795 35.84 235.245 37.19 236.025L142.1 296.425C142.33 296.555 142.58 296.635 142.82 296.725C142.93 296.765 143.04 296.835 143.16 296.865C143.53 296.965 143.9 297.015 144.28 297.015C144.66 297.015 145.03 296.965 145.4 296.865C145.5 296.835 145.59 296.775 145.69 296.745C145.95 296.655 146.21 296.565 146.45 296.435L251.36 236.035C252.72 235.255 253.55 233.815 253.55 232.245V174.885L303.81 145.945C305.17 145.165 306 143.725 306 142.155V82.265C305.95 81.875 305.89 81.495 305.8 81.125ZM144.2 227.205L100.57 202.515L146.39 176.135L196.66 147.195L240.33 172.335L208.29 190.625L144.2 227.205ZM244.75 114.995V164.795L226.39 154.225L201.03 139.625V89.825L219.39 100.395L244.75 114.995ZM249.12 57.105L292.81 82.265L249.12 107.425L205.43 82.265L249.12 57.105ZM114.49 184.425L96.13 194.995V85.305L121.49 70.705L139.85 60.135V169.815L114.49 184.425ZM91.76 27.425L135.45 52.585L91.76 77.745L48.07 52.585L91.76 27.425ZM43.67 60.135L62.03 70.705L87.39 85.305V202.545V202.555V202.565C87.39 202.735 87.44 202.895 87.46 203.055C87.49 203.265 87.49 203.485 87.55 203.695V203.705C87.6 203.875 87.69 204.035 87.76 204.195C87.84 204.375 87.89 204.575 87.99 204.745C87.99 204.745 87.99 204.755 88 204.755C88.09 204.905 88.22 205.035 88.33 205.175C88.45 205.335 88.55 205.495 88.69 205.635L88.7 205.645C88.82 205.765 88.98 205.855 89.12 205.965C89.28 206.085 89.42 206.225 89.59 206.325C89.6 206.325 89.6 206.325 89.61 206.335C89.62 206.335 89.62 206.345 89.63 206.345L139.87 234.775V285.065L43.67 229.705V60.135ZM244.75 229.705L148.58 285.075V234.775L219.8 194.115L244.75 179.875V229.705ZM297.2 139.625L253.49 164.795V114.995L278.85 100.395L297.21 89.825V139.625H297.2Z"/></svg>`

    const content = `
        <html>
            <head>
                <title>Resumen de Cita</title>
                <style>
                    @page { size: A4; margin: 20mm }
                    body { font-family: Arial, Helvetica, sans-serif; color: #111827; }
                    .container { max-width: 800px; margin: 0 auto; }
                    .header { display:flex; align-items:center; gap:16px; margin-bottom: 12px }
                    .logo { flex: 0 0 72px }
                    h1 { font-size: 18px; margin: 0 0 4px 0 }
                    .meta { font-size: 13px; color: #374151 }
                    .section { margin-top: 12px }
                    .label { font-weight: 700; color: #111827 }
                    .value { margin-left: 6px }
                    .notes { white-space: pre-wrap; background: #f9fafb; padding: 8px; border-radius: 6px; border: 1px solid #e5e7eb }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <div class="logo">${logoSvg}</div>
                        <div>
                            <h1>Resumen de Cita</h1>
                            <div class="meta">Generado: ${new Date().toLocaleString()}</div>
                        </div>
                    </div>

                    <div class="section">
                        <div><span class="label">Paciente:</span><span class="value"> ${patientName.value}</span></div>
                        <div><span class="label">Especialidad:</span><span class="value"> ${specialtyName.value}</span></div>
                        <div><span class="label">Doctor:</span><span class="value"> ${doctorName.value}</span></div>
                        <div><span class="label">Fecha y hora:</span><span class="value"> ${form.value.appointment_date} ${form.value.appointment_time}</span></div>
                        <div><span class="label">Duración:</span><span class="value"> ${referenceDurationText.value}</span></div>
                        <div><span class="label">Estado:</span><span class="value"> ${form.value.status}</span></div>
                    </div>

                    ${form.value.reason ? `<div class="section"><div class="label">Motivo</div><div class="notes">${form.value.reason}</div></div>` : ''}
                    ${form.value.notes ? `<div class="section"><div class="label">Notas</div><div class="notes">${form.value.notes}</div></div>` : ''}

                    ${clinic ? `
                        <div class="section" style="margin-top:20px; border-top:1px solid #e5e7eb; padding-top:8px; font-size:13px; color:#374151">
                            <div style="font-weight:700">${clinic.name || ''}</div>
                            <div>${clinic.address || ''}</div>
                            <div>${clinic.phone ? 'Tel: ' + clinic.phone : ''} ${clinic.email ? ' • ' + clinic.email : ''}</div>
                            <div>${clinic.tax_id ? 'CUIT/NIF: ' + clinic.tax_id : ''}</div>
                            ${clinic.footer_notes ? `<div style="margin-top:6px; white-space:pre-wrap">${clinic.footer_notes}</div>` : ''}
                        </div>
                    ` : ''}
                </div>
            </body>
        </html>
    `

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
