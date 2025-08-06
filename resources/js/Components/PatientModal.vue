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

                                <!-- Nombre -->
                                <div class="mb-4">
                                    <label for="name" class="block text-sm font-medium text-gray-700">
                                        Nombre completo
                                    </label>
                                    <input
                                        id="name"
                                        type="text"
                                        v-model="form.name"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        required
                                    >
                                    <div v-if="errors.name" class="mt-1 text-sm text-red-600">
                                        {{ errors.name }}
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="mb-4">
                                    <label for="email" class="block text-sm font-medium text-gray-700">
                                        Email
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        v-model="form.email"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        required
                                    >
                                    <div v-if="errors.email" class="mt-1 text-sm text-red-600">
                                        {{ errors.email }}
                                    </div>
                                </div>

                                <!-- Tipo de Documento -->
                                <div class="mb-4">
                                    <label for="document_type" class="block text-sm font-medium text-gray-700">
                                        Tipo de Documento
                                    </label>
                                    <select
                                        id="document_type"
                                        v-model="form.document_type"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        required
                                    >
                                        <option value="">Seleccionar tipo</option>
                                        <option value="cedula">Cédula</option>
                                        <option value="pasaporte">Pasaporte</option>
                                        <option value="tarjeta_identidad">Tarjeta de Identidad</option>
                                        <option value="registro_civil">Registro Civil</option>
                                    </select>
                                    <div v-if="errors.document_type" class="mt-1 text-sm text-red-600">
                                        {{ errors.document_type }}
                                    </div>
                                </div>

                                <!-- Número de Documento -->
                                <div class="mb-4">
                                    <label for="document_number" class="block text-sm font-medium text-gray-700">
                                        Número de Documento
                                    </label>
                                    <input
                                        id="document_number"
                                        type="text"
                                        v-model="form.document_number"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        required
                                    >
                                    <div v-if="errors.document_number" class="mt-1 text-sm text-red-600">
                                        {{ errors.document_number }}
                                    </div>
                                </div>

                                <!-- Contraseña (solo para nuevos pacientes) -->
                                <div v-if="!isEditing" class="mb-4">
                                    <label for="password" class="block text-sm font-medium text-gray-700">
                                        Contraseña
                                    </label>
                                    <input
                                        id="password"
                                        type="password"
                                        v-model="form.password"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        required
                                    >
                                    <div v-if="errors.password" class="mt-1 text-sm text-red-600">
                                        {{ errors.password }}
                                    </div>
                                </div>

                                <!-- Teléfono -->
                                <div class="mb-4">
                                    <label for="phone" class="block text-sm font-medium text-gray-700">
                                        Teléfono
                                    </label>
                                    <input
                                        id="phone"
                                        type="tel"
                                        v-model="form.phone"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    >
                                    <div v-if="errors.phone" class="mt-1 text-sm text-red-600">
                                        {{ errors.phone }}
                                    </div>
                                </div>

                                <!-- Fecha de nacimiento -->
                                <div class="mb-4">
                                    <label for="birth_date" class="block text-sm font-medium text-gray-700">
                                        Fecha de nacimiento
                                    </label>
                                    <input
                                        id="birth_date"
                                        type="date"
                                        v-model="form.birth_date"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    >
                                    <div v-if="errors.birth_date" class="mt-1 text-sm text-red-600">
                                        {{ errors.birth_date }}
                                    </div>
                                </div>

                                <!-- Género -->
                                <div class="mb-4">
                                    <label for="gender" class="block text-sm font-medium text-gray-700">
                                        Género
                                    </label>
                                    <select
                                        id="gender"
                                        v-model="form.gender"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    >
                                        <option value="">Seleccionar género</option>
                                        <option value="masculino">Masculino</option>
                                        <option value="femenino">Femenino</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                    <div v-if="errors.gender" class="mt-1 text-sm text-red-600">
                                        {{ errors.gender }}
                                    </div>
                                </div>

                                <!-- Dirección -->
                                <div class="mb-4">
                                    <label for="address" class="block text-sm font-medium text-gray-700">
                                        Dirección
                                    </label>
                                    <textarea
                                        id="address"
                                        v-model="form.address"
                                        rows="2"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Dirección completa..."
                                    ></textarea>
                                    <div v-if="errors.address" class="mt-1 text-sm text-red-600">
                                        {{ errors.address }}
                                    </div>
                                </div>

                                <!-- Contacto de emergencia -->
                                <div class="mb-4">
                                    <label for="emergency_contact" class="block text-sm font-medium text-gray-700">
                                        Contacto de emergencia
                                    </label>
                                    <input
                                        id="emergency_contact"
                                        type="text"
                                        v-model="form.emergency_contact"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Nombre y teléfono del contacto de emergencia"
                                    >
                                    <div v-if="errors.emergency_contact" class="mt-1 text-sm text-red-600">
                                        {{ errors.emergency_contact }}
                                    </div>
                                </div>

                                <!-- Alergias -->
                                <div class="mb-4">
                                    <label for="allergies" class="block text-sm font-medium text-gray-700">
                                        Alergias conocidas
                                    </label>
                                    <textarea
                                        id="allergies"
                                        v-model="form.allergies"
                                        rows="2"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Liste las alergias conocidas..."
                                    ></textarea>
                                    <div v-if="errors.allergies" class="mt-1 text-sm text-red-600">
                                        {{ errors.allergies }}
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

const props = defineProps({
    show: Boolean,
    patient: Object,
})

const emit = defineEmits(['close', 'saved'])

const processing = ref(false)
const errors = ref({})

const form = ref({
    name: '',
    email: '',
    document_type: '',
    document_number: '',
    phone: '',
    birth_date: '',
    gender: '',
    address: '',
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
})

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
    }
    errors.value = {}
})

const resetForm = () => {
    form.value = {
        name: '',
        email: '',
        document_type: '',
        document_number: '',
        phone: '',
        birth_date: '',
        gender: '',
        address: '',
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
    }
}

const populateForm = () => {
    if (props.patient) {
        form.value = {
            name: props.patient.user?.name || '',
            email: props.patient.user?.email || '',
            document_type: props.patient.user?.document_type || '',
            document_number: props.patient.user?.document_number || '',
            phone: props.patient.user?.phone || '',
            birth_date: props.patient.user?.birth_date || '',
            gender: props.patient.user?.gender || '',
            address: props.patient.user?.address || '',
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
        }
    }
}

const submitForm = () => {
    processing.value = true
    errors.value = {}

    const url = isEditing.value 
        ? route('patients.update', props.patient.id)
        : route('patients.store')

    const method = isEditing.value ? 'put' : 'post'

    router[method](url, form.value, {
        onSuccess: () => {
            processing.value = false
            emit('saved')
        },
        onError: (errorResponse) => {
            processing.value = false
            errors.value = errorResponse
        }
    })
}
</script>
