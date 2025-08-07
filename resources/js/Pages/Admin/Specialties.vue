<template>
    <Head title="Gestión de Especialidades" />

    <AuthenticatedLayout>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Header con botón -->
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Lista de Especialidades</h3>
                            <button 
                                @click="openCreateModal" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Nueva Especialidad
                            </button>
                        </div>

                        <!-- Tabla de especialidades -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nombre
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Descripción
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Doctores
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
                                    <tr v-for="specialty in specialties?.data || []" :key="specialty.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ specialty.name }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ specialty.description || 'Sin descripción' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ specialty.doctors_count }} doctores
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="{
                                                'bg-green-100 text-green-800': specialty.is_active,
                                                'bg-red-100 text-red-800': !specialty.is_active
                                            }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                {{ specialty.is_active ? 'Activa' : 'Inactiva' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button 
                                                @click="editSpecialty(specialty)" 
                                                class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                Editar
                                            </button>
                                            <button 
                                                @click="toggleSpecialtyStatus(specialty)" 
                                                :class="{
                                                    'text-red-600 hover:text-red-900': specialty.is_active,
                                                    'text-green-600 hover:text-green-900': !specialty.is_active
                                                }">
                                                {{ specialty.is_active ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="!specialties?.data || specialties.data.length === 0">
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            No hay especialidades registradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div v-if="specialties?.links && specialties.links.length > 3" class="mt-4 flex justify-center">
                            <nav class="flex space-x-2">
                                <Link 
                                    v-for="link in specialties.links" 
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

        <!-- Modal para crear/editar especialidad -->
        <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ selectedSpecialty ? 'Editar Especialidad' : 'Nueva Especialidad' }}
                    </h3>
                    <form @submit.prevent="saveSpecialty">
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre *
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Ej: Cardiología"
                            />
                        </div>
                        
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Descripción
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Descripción de la especialidad"
                            ></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                {{ selectedSpecialty ? 'Actualizar' : 'Crear' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Swal from 'sweetalert2'

const props = defineProps({
    specialties: Object
})

// Estado del modal
const showModal = ref(false)
const selectedSpecialty = ref(null)

// Formulario
const form = reactive({
    name: '',
    description: ''
})

// Métodos para el modal
const openCreateModal = () => {
    selectedSpecialty.value = null
    form.name = ''
    form.description = ''
    showModal.value = true
}

const editSpecialty = (specialty) => {
    selectedSpecialty.value = specialty
    form.name = specialty.name
    form.description = specialty.description || ''
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    selectedSpecialty.value = null
    form.name = ''
    form.description = ''
}

const saveSpecialty = () => {
    const url = selectedSpecialty.value 
        ? `/admin/specialties/${selectedSpecialty.value.id}`
        : '/admin/specialties'
    
    const method = selectedSpecialty.value ? 'put' : 'post'
    
    router[method](url, form, {
        onSuccess: () => {
            closeModal()
            Swal.fire('Éxito', 'Especialidad guardada correctamente', 'success')
        },
        onError: (errors) => {
            console.error('Errores:', errors)
        }
    })
}

const deleteSpecialty = (specialty) => {
    if (specialty.doctors_count > 0) {
        Swal.fire('Error', 'No se puede eliminar una especialidad que tiene doctores asignados', 'error')
        return
    }
    
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Se eliminará la especialidad "${specialty.name}"`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/specialties/${specialty.id}`, {
                onSuccess: () => {
                    Swal.fire('Eliminado', 'Especialidad eliminada exitosamente', 'success')
                }
            })
        }
    })
}

const toggleSpecialtyStatus = (specialty) => {
    const currentStatus = specialty.is_active
    const action = currentStatus ? 'desactivar' : 'activar'
    
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Se ${action}á la especialidad "${specialty.name}".`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: currentStatus ? '#d33' : '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Sí, ${action}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(`/admin/specialties/${specialty.id}/toggle-status`, {}, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    specialty.is_active = !currentStatus
                    Swal.fire(
                        currentStatus ? 'Desactivada' : 'Activada', 
                        `Especialidad ${action}da exitosamente`, 
                        'success'
                    )
                },
                onError: (errors) => {
                    console.error('Errores:', errors)
                    Swal.fire('Error', 'Error al cambiar el estado de la especialidad', 'error')
                }
            })
        }
    })
}
</script>
