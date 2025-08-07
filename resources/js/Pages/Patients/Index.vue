<template>
    <Head title="Gestión de Pacientes" />

    <AuthenticatedLayout>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Header con botón -->
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Lista de Pacientes</h3>
                            <button 
                                @click="openCreateModal" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Nuevo Paciente
                            </button>
                        </div>

                        <!-- Tabla de pacientes -->
                        <div class="overflow-x-auto">
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
                                            <button 
                                                @click="editPatient(patient)" 
                                                class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                Editar
                                            </button>
                                            <button 
                                                @click="togglePatientStatus(patient)" 
                                                :class="{
                                                    'text-red-600 hover:text-red-900': patient.user?.is_active !== false,
                                                    'text-green-600 hover:text-green-900': patient.user?.is_active === false
                                                }">
                                                {{ patient.user?.is_active === false ? 'Activar' : 'Desactivar' }}
                                            </button>
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
                        <div v-if="patients?.links && patients.links.length > 3" class="mt-4 flex justify-center">
                            <nav class="flex space-x-2">
                                <Link 
                                    v-for="link in patients.links" 
                                    :key="link.label"
                                    :href="link.url"
                                    :class="{
                                        'bg-blue-500 text-white': link.active,
                                        'bg-gray-200 text-gray-700': !link.active,
                                        'opacity-50 cursor-not-allowed': !link.url
                                    }"
                                    class="px-3 py-2 rounded text-sm"
                                    v-html="link.label">
                                </Link>
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
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PatientModal from '@/Components/PatientModal.vue'
import Swal from 'sweetalert2'

const props = defineProps({
    patients: Object,
    filters: Object
})

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

const handlePatientSaved = () => {
    // Recargar la página para mostrar los cambios
    router.reload()
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
