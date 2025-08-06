<template>
    <!-- Modal overlay -->
    <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        {{ isEdit ? 'Editar Paciente' : 'Nuevo Paciente' }}
                    </h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Nombre -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre completo *
                        </label>
                        <input
                            type="text"
                            id="name"
                            v-model="form.name"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            :class="{ 'border-red-500': errors.name }"
                        />
                        <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Email *
                        </label>
                        <input
                            type="email"
                            id="email"
                            v-model="form.email"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            :class="{ 'border-red-500': errors.email }"
                        />
                        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                    </div>

                    <!-- Tipo de documento -->
                    <div>
                        <label for="document_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Tipo de documento *
                        </label>
                        <select
                            id="document_type"
                            v-model="form.document_type"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            :class="{ 'border-red-500': errors.document_type }"
                        >
                            <option value="">Seleccione un tipo</option>
                            <option value="cedula">Cédula</option>
                            <option value="pasaporte">Pasaporte</option>
                            <option value="tarjeta_identidad">Tarjeta de Identidad</option>
                            <option value="registro_civil">Registro Civil</option>
                        </select>
                        <p v-if="errors.document_type" class="mt-1 text-sm text-red-600">{{ errors.document_type }}</p>
                    </div>

                    <!-- Número de documento -->
                    <div>
                        <label for="document_number" class="block text-sm font-medium text-gray-700 mb-1">
                            Número de documento *
                        </label>
                        <input
                            type="text"
                            id="document_number"
                            v-model="form.document_number"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            :class="{ 'border-red-500': errors.document_number }"
                        />
                        <p v-if="errors.document_number" class="mt-1 text-sm text-red-600">{{ errors.document_number }}</p>
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                            Teléfono
                        </label>
                        <input
                            type="tel"
                            id="phone"
                            v-model="form.phone"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            :class="{ 'border-red-500': errors.phone }"
                        />
                        <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone }}</p>
                    </div>

                    <!-- Password (solo para nuevos pacientes) -->
                    <div v-if="!isEdit">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Contraseña *
                        </label>
                        <input
                            type="password"
                            id="password"
                            v-model="form.password"
                            :required="!isEdit"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            :class="{ 'border-red-500': errors.password }"
                        />
                        <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end space-x-3 pt-4">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                        >
                            {{ processing ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Crear Paciente') }}
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
    show: {
        type: Boolean,
        default: false
    },
    patient: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['close', 'saved'])

// Computed
const isEdit = computed(() => !!props.patient?.id)

// Form data
const form = ref({
    name: '',
    email: '',
    document_type: '',
    document_number: '',
    phone: '',
    password: ''
})

const errors = ref({})
const processing = ref(false)

// Watch for changes in patient prop
watch(() => props.patient, (newPatient) => {
    if (newPatient) {
        form.value = {
            name: newPatient.user?.name || '',
            email: newPatient.user?.email || '',
            document_type: newPatient.user?.document_type || '',
            document_number: newPatient.user?.document_number || '',
            phone: newPatient.user?.phone || '',
            password: ''
        }
    } else {
        resetForm()
    }
}, { immediate: true })

// Methods
const resetForm = () => {
    form.value = {
        name: '',
        email: '',
        document_type: '',
        document_number: '',
        phone: '',
        password: ''
    }
    errors.value = {}
}

const closeModal = () => {
    resetForm()
    emit('close')
}

const submitForm = () => {
    processing.value = true
    errors.value = {}

    const url = isEdit.value ? `/patients/${props.patient.id}` : '/patients'
    const method = isEdit.value ? 'put' : 'post'

    router[method](url, form.value, {
        onSuccess: () => {
            processing.value = false
            emit('saved')
            closeModal()
        },
        onError: (pageErrors) => {
            processing.value = false
            errors.value = pageErrors
        }
    })
}
</script>
