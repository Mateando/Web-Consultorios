<template>
    <Head title="Gestión de Pacientes" />

    <AuthenticatedLayout>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Header con buscador -->
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Lista de Pacientes</h3>
                            <div class="flex-1 md:max-w-md">
                                <form @submit.prevent class="flex gap-2 items-start">
                                    <input type="text" v-model="search" placeholder="Buscar por nombre, email o documento" class="flex-1 rounded-md border-gray-300 shadow-sm text-sm" />
                                    <SecondaryButton type="button" v-if="search" @click="clearSearch">X</SecondaryButton>
                                </form>
                                <p v-if="isSearching" class="mt-1 text-xs text-gray-400">Buscando...</p>
                            </div>
                            <div>
                                <PrimaryButton @click="openCreateModal">Nuevo Paciente</PrimaryButton>
                            </div>
                        </div>

                        <!-- Tabla de pacientes -->
                        <div class="overflow-x-auto relative">
                            <!-- Overlay de carga -->
                            <div v-if="isSearching" class="absolute inset-0 bg-white/60 backdrop-blur-[1px] flex items-center justify-center z-10 pointer-events-none">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="h-5 w-5 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                    </svg>
                                    <span>Actualizando...</span>
                                </div>
                            </div>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nombre
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Documento
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
                                    <tr v-for="patient in patients?.data || []" :key="patient.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ patient.user?.name || 'Sin nombre' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ patient.user?.email || 'Sin email' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ getDocumentTypeLabel(patient.user?.document_type) }} - {{ patient.user?.document_number || 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="{
                                                'bg-green-100 text-green-800': patient.user?.is_active !== false,
                                                'bg-red-100 text-red-800': patient.user?.is_active === false
                                            }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                {{ patient.user?.is_active === false ? 'Inactivo' : 'Activo' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <SecondaryButton type="button" @click="editPatient(patient)" class="!px-2 !py-1 mr-3 text-indigo-600 hover:text-indigo-900 bg-white border-gray-200">Editar</SecondaryButton>
                                            <SecondaryButton
                                                type="button"
                                                @click="togglePatientStatus(patient)"
                                                :class="patient.user?.is_active !== false ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'"
                                                class="!px-2 !py-1 bg-white border-gray-200"
                                            >
                                                {{ patient.user?.is_active === false ? 'Activar' : 'Desactivar' }}
                                            </SecondaryButton>
                                        </td>
                                    </tr>
                                    <tr v-if="!patients?.data || patients.data.length === 0">
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            No hay pacientes registrados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div v-if="patients?.links && patients.links.length > 3" class="mt-6 flex justify-center">
                            <nav class="flex flex-wrap gap-2">
                                <Link
                                    v-for="link in patients.links"
                                    :key="link.label + (link.url||'')"
                                    :href="link.url || '#'"
                                    :disabled="!link.url"
                                    :class="paginationLinkClasses(link)"
                                    v-html="sanitizeLabel(link.label)"
                                />
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para crear/editar paciente -->
        <PatientModal
            :show="showModal"
            :patient="selectedPatient"
            @close="closeModal"
            @saved="handlePatientSaved"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { Inertia } from '@inertiajs/inertia'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PatientModal from '@/Components/PatientModal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import Swal from 'sweetalert2'

const props = defineProps({
    patients: Object,
    filters: Object
})

const search = ref(props.filters?.search || '')
const isSearching = ref(false)
let debounceTimer = null
let lastSent = search.value // almacena último valor enviado para evitar peticiones duplicadas

function triggerSearch(val){
    const normalized = (val||'').trim()
    if (normalized === (lastSent||'').trim()) return // no hay cambio real
    lastSent = normalized
    Inertia.cancelActiveVisits?.()
    isSearching.value = true
    router.get(route('patients.index'), { search: normalized }, {
        only:['patients','filters'],
        preserveState:true,
        replace:true,
        onFinish: () => { isSearching.value = false }
    })
}

// Observa cambios del input: primera pulsación inmediata, siguientes con debounce corto
watch(search, (val, oldVal) => {
    if (debounceTimer) clearTimeout(debounceTimer)
    // Si se limpia el campo, dispara inmediatamente
    if (val === '') {
        triggerSearch('')
        return
    }
    // Primera entrada (cuando antes estaba vacío) => instantáneo
    if (!oldVal) {
        triggerSearch(val)
        return
    }
    // Cambios sucesivos: debounce corto
    debounceTimer = setTimeout(() => {
        triggerSearch(val)
    }, 220)
})

const clearSearch = () => {
    if (debounceTimer) clearTimeout(debounceTimer)
    search.value = ''
    triggerSearch('') // ejecución inmediata sin esperar debounce
}

// Estilos de paginación reutilizando la lógica de botones (estilo PrimaryButton)
const paginationLinkClasses = (link) => {
    if (link.label === '...') return 'px-3 py-2 text-sm text-gray-400 cursor-default select-none'
    // Basado en PrimaryButton styles
    const base = 'inline-flex items-center rounded-md border border-transparent px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2'
    if (link.active) {
        return base + ' bg-gray-800 hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900'
    }
    if (!link.url) {
        return 'inline-flex items-center justify-center px-3 py-2 rounded-md bg-gray-100 border border-gray-200 text-gray-400 cursor-not-allowed text-sm'
    }
    return base + ' bg-gray-800/80 hover:bg-gray-700/90 text-white'
}

const sanitizeLabel = (label) => {
    if (!label) return ''
    return label.replace('&laquo;', '«').replace('&raquo;', '»')
}

// Estado del modal
const showModal = ref(false)
const selectedPatient = ref(null)

// Métodos para el modal
const openCreateModal = () => {
    selectedPatient.value = null
    showModal.value = true
}

const editPatient = (patient) => {
    selectedPatient.value = patient
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    selectedPatient.value = null
}

const handlePatientSaved = (wasEditing) => {
    showModal.value = false
    const msg = wasEditing ? 'Paciente actualizado correctamente' : 'Paciente creado correctamente'
    Swal.fire({
        toast:true,
        position:'top-end',
        icon:'success',
        title: msg,
        showConfirmButton:false,
        timer:2500,
        timerProgressBar:true
    })
    router.reload({ only:['patients'] })
}

// Método para cambiar estado del paciente
const togglePatientStatus = (patient) => {
    const currentStatus = patient.user.is_active !== false
    const action = currentStatus ? 'desactivar' : 'activar'
    
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Se ${action}á al paciente ${patient.user.name}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: currentStatus ? '#d33' : '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Sí, ${action}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(`/patients/${patient.id}/toggle-status`, {}, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    patient.user.is_active = !currentStatus
                    Swal.fire(
                        currentStatus ? 'Desactivado' : 'Activado', 
                        `Paciente ${action}do exitosamente`, 
                        'success'
                    )
                }
            })
        }
    })
}

// Método para formatear el tipo de documento
const getDocumentTypeLabel = (type) => {
    const types = {
        'cedula': 'Cédula',
        'pasaporte': 'Pasaporte',
        'tarjeta_identidad': 'Tarjeta de Identidad',
        'registro_civil': 'Registro Civil'
    }
    return types[type] || 'N/A'
}
</script>
