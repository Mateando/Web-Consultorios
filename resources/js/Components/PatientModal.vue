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
                                    {{ isEditing ? 'Editar Paciente' : 'Nuevo Paciente' }}
                                </h3>

                                <!-- Indicador de pasos -->
                                <div class="flex items-center justify-center mb-4" v-if="steps.length > 1">
                                    <template v-for="(s,i) in steps" :key="s.key">
                                        <div class="flex flex-col items-center mx-1">
                                            <div :class="['h-3 w-3 rounded-full transition',
                                                stepHasErrors(s.key) ? 'bg-red-500 animate-pulse' :
                                                (currentStepIndex===i ? 'bg-blue-600' : completedStep(i) ? 'bg-blue-300' : 'bg-gray-300'),
                                                currentStepIndex===i ? 'ring-2 ring-offset-1 ring-blue-300' : ''
                                            ]" :title="stepTooltip(s.key)"></div>
                                        </div>
                                    </template>
                                </div>
                                <div v-if="errorSummary.length" class="mb-6 p-3 text-xs rounded border border-red-300 bg-red-50 text-red-700 max-h-32 overflow-auto">
                                    <ul class="list-disc ml-4 space-y-0.5">
                                        <li v-for="(msg,i) in errorSummary" :key="i">{{ msg }}</li>
                                    </ul>
                                </div>

                                                                <!-- Paso Identificación -->
                                                                <div v-if="currentStep.key==='identificacion'">
                                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                                        <div class="sm:col-span-2">
                                                                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                                                                            <input id="name" type="text" v-model="form.name" class="w-full rounded-md border-gray-300 shadow-sm" required />
                                                                            <InputError v-if="errors.name" :message="errors.name" />
                                                                        </div>
                                                                        <div>
                                                                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                                                            <input id="email" type="email" v-model="form.email" class="w-full rounded-md border-gray-300 shadow-sm" required />
                                                                            <InputError v-if="errors.email" :message="errors.email" />
                                                                        </div>
                                                                        <div>
                                                                            <label for="secondary_email" class="block text-sm font-medium text-gray-700 mb-1">Email Secundario</label>
                                                                            <input id="secondary_email" type="email" v-model="form.secondary_email" class="w-full rounded-md border-gray-300 shadow-sm" />
                                                                            <InputError v-if="errors.secondary_email" :message="errors.secondary_email" />
                                                                        </div>
                                                                        <div>
                                                                            <label for="document_type" class="block text-sm font-medium text-gray-700 mb-1">Tipo Documento *</label>
                                                                            <select id="document_type" v-model="form.document_type" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                                                                <option value="">Seleccionar</option>
                                                                                <option value="cedula">Cédula</option>
                                                                                <option value="pasaporte">Pasaporte</option>
                                                                                <option value="tarjeta_identidad">Tarjeta Identidad</option>
                                                                                <option value="registro_civil">Registro Civil</option>
                                                                            </select>
                                                                            <InputError v-if="errors.document_type" :message="errors.document_type" />
                                                                        </div>
                                                                        <div>
                                                                            <label for="document_number" class="block text-sm font-medium text-gray-700 mb-1">Número Documento *</label>
                                                                            <input id="document_number" type="text" v-model="form.document_number" class="w-full rounded-md border-gray-300 shadow-sm" required />
                                                                            <InputError v-if="errors.document_number" :message="errors.document_number" />
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Paso Contacto -->
                                                                <div v-else-if="currentStep.key==='contacto'">
                                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                                        <div v-if="!isEditing">
                                                                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña *</label>
                                                                            <input id="password" type="password" v-model="form.password" class="w-full rounded-md border-gray-300 shadow-sm" required />
                                                                            <InputError v-if="errors.password" :message="errors.password" />
                                                                        </div>
                                                                        <div>
                                                                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                                                                            <input id="phone" type="tel" v-model="form.phone" class="w-full rounded-md border-gray-300 shadow-sm" />
                                                                            <InputError v-if="errors.phone" :message="errors.phone" />
                                                                        </div>
                                                                        <div>
                                                                            <label for="landline_phone" class="block text-sm font-medium text-gray-700 mb-1">Teléfono Fijo</label>
                                                                            <input id="landline_phone" type="tel" v-model="form.landline_phone" class="w-full rounded-md border-gray-300 shadow-sm" />
                                                                            <InputError v-if="errors.landline_phone" :message="errors.landline_phone" />
                                                                        </div>
                                                                        <div>
                                                                            <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha Nacimiento *</label>
                                                                            <input id="birth_date" type="date" v-model="form.birth_date" class="w-full rounded-md border-gray-300 shadow-sm" required />
                                                                            <InputError v-if="errors.birth_date" :message="errors.birth_date" />
                                                                        </div>
                                                                        <div>
                                                                            <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Género *</label>
                                                                            <select id="gender" v-model="form.gender" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                                                                <option value="">Seleccionar</option>
                                                                                <option value="masculino">Masculino</option>
                                                                                <option value="femenino">Femenino</option>
                                                                                <option value="otro">Otro</option>
                                                                            </select>
                                                                            <InputError v-if="errors.gender" :message="errors.gender" />
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Paso Dirección -->
                                                                <div v-else-if="currentStep.key==='direccion'">
                                                                    <div class="space-y-4">
                                                                        <div>
                                                                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                                                                            <textarea id="address" v-model="form.address" rows="2" class="w-full rounded-md border-gray-300 shadow-sm"></textarea>
                                                                            <InputError v-if="errors.address" :message="errors.address" />
                                                                        </div>
                                                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                                                            <div>
                                                                                <label class="block text-sm font-medium text-gray-700 mb-1">País</label>
                                                                                <select v-model="selectedCountry" @change="loadProvinces" class="w-full rounded-md border-gray-300 shadow-sm">
                                                                                    <option value="">Seleccione</option>
                                                                                    <option v-for="c in countries" :key="c.id" :value="c.id">{{ c.name }}</option>
                                                                                </select>
                                                                            </div>
                                                                            <div>
                                                                                <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                                                                                <select v-model="selectedProvince" @change="loadCities" :disabled="!selectedCountry" class="w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100">
                                                                                    <option value="">Seleccione</option>
                                                                                    <option v-for="p in provinces" :key="p.id" :value="p.id">{{ p.name }}</option>
                                                                                </select>
                                                                            </div>
                                                                            <div>
                                                                                <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                                                                                <select v-model="selectedCity" :disabled="!selectedProvince" class="w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100">
                                                                                    <option value="">Seleccione</option>
                                                                                    <option v-for="ci in cities" :key="ci.id" :value="ci.id">{{ ci.name }}</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                                                <!-- Paso Clínicos 1 -->
                                                                                                <div v-else-if="currentStep.key==='clinicos1'">
                                                                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                                                                        <div>
                                                                                                            <label for="patient_type" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Paciente</label>
                                                                                                            <select id="patient_type" v-model="form.patient_type" class="w-full rounded-md border-gray-300 shadow-sm">
                                                                                                                <option value="">Seleccione</option>
                                                                                                                <option v-for="pt in patientTypes" :key="pt.id" :value="pt.id">{{ pt.name }}</option>
                                                                                                            </select>
                                                                                                            <InputError v-if="errors.patient_type" :message="errors.patient_type" />
                                                                                                        </div>
                                                                                                        <div>
                                                                                                            <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700 mb-1">Contacto Emergencia</label>
                                                                                                            <input id="emergency_contact_name" type="text" v-model="form.emergency_contact_name" class="w-full rounded-md border-gray-300 shadow-sm" />
                                                                                                        </div>
                                                                                                        <div>
                                                                                                            <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700 mb-1">Tel. Emergencia</label>
                                                                                                            <input id="emergency_contact_phone" type="text" v-model="form.emergency_contact_phone" class="w-full rounded-md border-gray-300 shadow-sm" />
                                                                                                        </div>
                                                                                                        <div>
                                                                                                            <label for="insurance_provider" class="block text-sm font-medium text-gray-700 mb-1">Obra Social</label>
                                                                                                            <select id="insurance_provider" v-model="form.insurance_provider" class="w-full rounded-md border-gray-300 shadow-sm">
                                                                                                                <option value="">Seleccione</option>
                                                                                                                <option v-for="ip in insuranceProviders" :key="ip.id" :value="ip.id">{{ ip.name }}</option>
                                                                                                            </select>
                                                                                                        </div>
                                                                                                        <div>
                                                                                                            <label for="insurance_number" class="block text-sm font-medium text-gray-700 mb-1">N° Afiliado</label>
                                                                                                            <input id="insurance_number" type="text" v-model="form.insurance_number" class="w-full rounded-md border-gray-300 shadow-sm" />
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>

                                                                <!-- Paso Clínicos 2 -->
                                                                <div v-else-if="currentStep.key==='clinicos2'">
                                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                                        <div class="sm:col-span-2">
                                                                            <label for="allergies" class="block text-sm font-medium text-gray-700 mb-1">Alergias</label>
                                                                            <textarea id="allergies" v-model="form.allergies" rows="2" class="w-full rounded-md border-gray-300 shadow-sm"></textarea>
                                                                        </div>
                                                                        <div>
                                                                            <label for="medical_conditions" class="block text-sm font-medium text-gray-700 mb-1">Condiciones</label>
                                                                            <textarea id="medical_conditions" v-model="form.medical_conditions" rows="2" class="w-full rounded-md border-gray-300 shadow-sm"></textarea>
                                                                        </div>
                                                                        <div>
                                                                            <label for="medications" class="block text-sm font-medium text-gray-700 mb-1">Medicaciones</label>
                                                                            <textarea id="medications" v-model="form.medications" rows="2" class="w-full rounded-md border-gray-300 shadow-sm"></textarea>
                                                                        </div>
                                                                        <div>
                                                                            <label for="blood_type" class="block text-sm font-medium text-gray-700 mb-1">Grupo Sanguíneo</label>
                                                                            <select id="blood_type" v-model="form.blood_type" class="w-full rounded-md border-gray-300 shadow-sm">
                                                                                <option value="">Seleccionar</option>
                                                                                <option v-for="b in ['A+','A-','B+','B-','AB+','AB-','O+','O-']" :key="b" :value="b">{{ b }}</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="grid grid-cols-2 gap-2">
                                                                            <div>
                                                                                <label for="height" class="block text-sm font-medium text-gray-700 mb-1">Altura (cm)</label>
                                                                                <input id="height" type="number" v-model="form.height" class="w-full rounded-md border-gray-300 shadow-sm" />
                                                                            </div>
                                                                            <div>
                                                                                <label for="weight" class="block text-sm font-medium text-gray-700 mb-1">Peso (kg)</label>
                                                                                <input id="weight" type="number" v-model="form.weight" class="w-full rounded-md border-gray-300 shadow-sm" />
                                                                            </div>
                                                                        </div>
                                                                        <div class="sm:col-span-2">
                                                                            <label for="observations" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                                                                            <textarea id="observations" v-model="form.observations" rows="2" class="w-full rounded-md border-gray-300 shadow-sm"></textarea>
                                                                        </div>
                                                                        <div class="sm:col-span-2">
                                                                            <label for="extra_observations" class="block text-sm font-medium text-gray-700 mb-1">Observaciones Extra</label>
                                                                            <textarea id="extra_observations" v-model="form.extra_observations" rows="2" class="w-full rounded-md border-gray-300 shadow-sm"></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Paso Confirmación -->
                                                                <div v-else-if="currentStep.key==='confirmacion'">
                                                                    <p class="text-sm text-gray-600 mb-4">Revisa los datos antes de guardar.</p>
                                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs max-h-60 overflow-auto pr-2">
                                                                        <div><strong>Nombre:</strong> {{ form.name }}</div>
                                                                        <div><strong>Email:</strong> {{ form.email }}</div>
                                                                        <div><strong>Documento:</strong> {{ form.document_type }} {{ form.document_number }}</div>
                                                                        <div><strong>Nacimiento:</strong> {{ form.birth_date }}</div>
                                                                        <div><strong>Género:</strong> {{ form.gender }}</div>
                                                                        <div class="sm:col-span-2"><strong>Dirección:</strong> {{ form.address }}</div>
                                                                        <div><strong>País:</strong> {{ displayCountry }}</div>
                                                                        <div><strong>Provincia:</strong> {{ displayProvince }}</div>
                                                                        <div><strong>Ciudad:</strong> {{ displayCity }}</div>
                                                                        <div><strong>Obra Social:</strong> {{ form.insurance_provider }}</div>
                                                                        <div><strong>N° Afiliado:</strong> {{ form.insurance_number }}</div>
                                                                        <div class="sm:col-span-2"><strong>Alergias:</strong> {{ form.allergies }}</div>
                                                                        <div class="sm:col-span-2"><strong>Observaciones:</strong> {{ form.observations }}</div>
                                                                    </div>
                                                                </div>
                            </div>
                        </div>
                    </div>
                                        <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-between items-center">
                                                <div>
                                                    <SecondaryButton v-if="hasPrev" type="button" @click="prevStep">Atrás</SecondaryButton>
                                                </div>
                                                <div class="flex gap-2 ml-auto">
                                                    <PrimaryButton v-if="hasNext" type="button" @click="nextStep" :disabled="processing">Siguiente</PrimaryButton>
                                                    <PrimaryButton v-else type="submit" :disabled="processing">{{ processing ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Crear') }}</PrimaryButton>
                                                    <SecondaryButton type="button" @click="$emit('close')">Cancelar</SecondaryButton>
                                                </div>
                                        </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3'
// usar la función global `route()` provista por ZiggyVue/@routes
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    show: Boolean,
    patient: Object,
})

const emit = defineEmits(['close', 'saved'])

const processing = ref(false)
const errors = ref({})
const errorSummary = ref([])

const form = ref({
    name: '',
    email: '',
    secondary_email: '',
    document_type: '',
    document_number: '',
    phone: '',
    landline_phone: '',
    birth_date: '',
    gender: '',
    address: '',
    city: '',
    province: '',
    country: '',
    patient_type: '', // almacenará el id seleccionado (temporal: nombre legacy se enviará)
    emergency_contact_name: '',
    emergency_contact_phone: '',
    insurance_provider: '', // id seleccionado (se mapea a nombre legacy)
    insurance_number: '',
    allergies: '',
    medical_conditions: '',
    medications: '',
    blood_type: '',
    height: '',
    weight: '',
    observations: '',
    extra_observations: '',
})

// Catálogos
const patientTypes = ref([])
const insuranceProviders = ref([])

// Wizard (máx 5 campos por paso)
const steps = ref([
    { key:'identificacion', label:'Identificación' },
    { key:'contacto', label:'Contacto' },
    { key:'direccion', label:'Dirección' },
    { key:'clinicos1', label:'Clínicos 1' },
    { key:'clinicos2', label:'Clínicos 2' },
    { key:'confirmacion', label:'Confirmación' },
])
// Mapa de campos por paso para navegar a errores
const stepFieldMap = {
    identificacion: ['name','email','secondary_email','document_type','document_number'],
    contacto: ['password','phone','landline_phone','birth_date','gender'],
    direccion: ['address','country','province','city'],
    clinicos1: ['patient_type','emergency_contact_name','emergency_contact_phone','insurance_provider','insurance_number'],
    clinicos2: ['allergies','medical_conditions','medications','blood_type','height','weight','observations','extra_observations'],
    confirmacion: []
}
const currentStepIndex = ref(0)
const currentStep = computed(()=>steps.value[currentStepIndex.value])
const hasNext = computed(()=>currentStepIndex.value < steps.value.length -1)
const hasPrev = computed(()=>currentStepIndex.value > 0)
const nextStep = () => { if(hasNext.value) currentStepIndex.value++ }
const prevStep = () => { if(hasPrev.value) currentStepIndex.value-- }
const completedStep = (i)=> i < currentStepIndex.value
const stepHasErrors = (key) => Object.keys(errors.value).some(f => stepFieldMap[key]?.includes(f))
const stepTooltip = (key) => stepHasErrors(key) ? 'Revisar campos con errores' : ''

const handleValidationErrors = () => {
    const fieldsWithErrors = Object.keys(errors.value)
    errorSummary.value = []
    if(!fieldsWithErrors.length) return
    // Armar resumen (puede haber arrays de mensajes)
    errorSummary.value = fieldsWithErrors.flatMap(f => Array.isArray(errors.value[f]) ? errors.value[f] : [errors.value[f]])
    // Encontrar primer paso con error y saltar
    for (let i=0;i<steps.value.length;i++) {
        const sk = steps.value[i].key
        if (stepFieldMap[sk]?.some(f => fieldsWithErrors.includes(f))) {
            currentStepIndex.value = i
            nextTick(() => {
                const firstField = stepFieldMap[sk].find(f => fieldsWithErrors.includes(f))
                if (firstField) {
                    const el = document.getElementById(firstField)
                    if (el) el.focus()
                }
                const modal = document.querySelector('.inline-block.align-bottom')
                if (modal) modal.scrollTop = 0
            })
            break
        }
    }
}

// Selects dependientes
const countries = ref([])
const provinces = ref([])
const cities = ref([])
const selectedCountry = ref('')
const selectedProvince = ref('')
const selectedCity = ref('')

const loadCountries = async () => {
    const { data } = await axios.get(route('api.config.countries'))
    countries.value = data
}
const loadPatientTypes = async () => {
    const { data } = await axios.get(route('api.config.patient-types'))
    patientTypes.value = data
}
const loadInsuranceProviders = async () => {
    const { data } = await axios.get(route('api.config.insurance-providers'))
    insuranceProviders.value = data
}
const loadProvinces = async () => {
    provinces.value = []
    cities.value = []
    selectedProvince.value=''
    selectedCity.value=''
    if(!selectedCountry.value) return
    const { data } = await axios.get(route('api.config.provinces', selectedCountry.value))
    provinces.value = data
}
const loadCities = async () => {
    cities.value = []
    selectedCity.value=''
    if(!selectedProvince.value) return
    const { data } = await axios.get(route('api.config.cities', selectedProvince.value))
    cities.value = data
}

const displayCountry = computed(()=> countries.value.find(c=>c.id==selectedCountry.value)?.name || form.value.country)
const displayProvince = computed(()=> provinces.value.find(p=>p.id==selectedProvince.value)?.name || form.value.province)
const displayCity = computed(()=> cities.value.find(ci=>ci.id==selectedCity.value)?.name || form.value.city)

const isEditing = computed(() => {
    return props.patient && props.patient.id
})

// Resetear formulario cuando se abre/cierra el modal
watch(() => props.show, (newValue) => {
    if (newValue) {
        resetForm()
        if (props.patient) {
            populateForm()
        }
        Promise.all([
            loadCountries(),
            loadPatientTypes(),
            loadInsuranceProviders()
        ])
    }
    errors.value = {}
    errorSummary.value = []
})

const resetForm = () => {
    form.value = {
        name: '',
        email: '',
    secondary_email: '',
        document_type: '',
        document_number: '',
        phone: '',
    landline_phone: '',
        birth_date: '',
        gender: '',
        address: '',
    city: '',
    province: '',
    country: '',
    patient_type: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        insurance_provider: '',
        insurance_number: '',
        allergies: '',
        medical_conditions: '',
        medications: '',
        blood_type: '',
        height: '',
        weight: '',
    observations: '',
    extra_observations: '',
    }
}

const populateForm = () => {
    if (props.patient) {
        form.value = {
            name: props.patient.user?.name || '',
            email: props.patient.user?.email || '',
            secondary_email: props.patient.user?.secondary_email || '',
            document_type: props.patient.user?.document_type || '',
            document_number: props.patient.user?.document_number || '',
            phone: props.patient.user?.phone || '',
            landline_phone: props.patient.user?.landline_phone || '',
            birth_date: props.patient.user?.birth_date || '',
            gender: props.patient.user?.gender || '',
            address: props.patient.user?.address || '',
            city: props.patient.user?.city || '',
            province: props.patient.user?.province || '',
            country: props.patient.user?.country || '',
            patient_type: props.patient.patient_type || '',
            emergency_contact_name: props.patient.emergency_contact_name || '',
            emergency_contact_phone: props.patient.emergency_contact_phone || '',
            insurance_provider: props.patient.insurance_provider || '',
            insurance_number: props.patient.insurance_number || '',
            allergies: props.patient.allergies || '',
            medical_conditions: props.patient.medical_conditions || '',
            medications: props.patient.medications || '',
            blood_type: props.patient.blood_type || '',
            height: props.patient.height || '',
            weight: props.patient.weight || '',
            observations: props.patient.observations || '',
            extra_observations: props.patient.extra_observations || '',
        }
    // Guardar display previos en selects (se buscarán por nombre al cargar) se deja manual
    }
}

const submitForm = () => {
    processing.value = true
    errors.value = {}

    // Mapear selects a campos texto legacy
        if(selectedCountry.value){ form.value.country = displayCountry.value }
        if(selectedProvince.value){ form.value.province = displayProvince.value }
        if(selectedCity.value){ form.value.city = displayCity.value }
        // Mapear ids de catálogos a nombre (legacy)
        if(form.value.patient_type){
            const pt = patientTypes.value.find(p=>p.id==form.value.patient_type)
            if(pt) form.value.patient_type = pt.name
        }
        if(form.value.insurance_provider){
            const ip = insuranceProviders.value.find(p=>p.id==form.value.insurance_provider)
            if(ip) form.value.insurance_provider = ip.name
        }

    const url = isEditing.value 
        ? route('patients.update', props.patient.id)
        : route('patients.store')

    const method = isEditing.value ? 'put' : 'post'

    router[method](url, form.value, {
        onSuccess: () => {
            processing.value = false
            errorSummary.value = []
            emit('saved', isEditing.value)
        },
        onError: (errorResponse) => {
            processing.value = false
            errors.value = errorResponse
            handleValidationErrors()
        }
    })
}
</script>
