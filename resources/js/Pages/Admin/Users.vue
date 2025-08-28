<template>
    <Head title="Gestión de Usuarios" />

    <AuthenticatedLayout>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Filtros -->
                        <div class="mb-6 flex flex-col sm:flex-row gap-4">
                            <div class="flex-1">
                                <input 
                                    v-model="searchForm.search"
                                    @input="search"
                                    type="text" 
                                    placeholder="Buscar usuarios..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                            </div>
                            <div>
                                <select 
                                    v-model="searchForm.role"
                                    @change="search"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Todos los roles</option>
                                    <option value="administrador">Administrador</option>
                                    <option value="medico">Médico</option>
                                    <option value="recepcionista">Recepcionista</option>
                                    <option value="paciente">Paciente</option>
                                </select>
                            </div>
                        </div>

                        <!-- Tabla de usuarios -->
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
                                            Roles
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Estado
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Fecha Registro
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="user in users?.data || []" :key="user.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ user.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ user.email }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span v-for="role in user.roles" :key="role.id" 
                                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-1">
                                                {{ role.name }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="{
                                                'bg-green-100 text-green-800': user.is_active,
                                                'bg-red-100 text-red-800': !user.is_active
                                            }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                {{ user.is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(user.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <SecondaryButton type="button" @click="editUserRoles(user)" class="text-indigo-600 mr-3">Editar Roles</SecondaryButton>
                                            <SecondaryButton type="button" @click="toggleUserStatus(user)" :class="user.is_active ? 'text-red-600' : 'text-green-600'" :disabled="user.id === $page.props.auth.user.id">{{ user.is_active ? 'Desactivar' : 'Activar' }}</SecondaryButton>
                                        </td>
                                    </tr>
                                    <tr v-if="!users?.data || users.data.length === 0">
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            No hay usuarios registrados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div v-if="users?.links && users.links.length > 3" class="mt-4 flex justify-center">
                            <nav class="flex space-x-2">
                                <Link 
                                    v-for="link in users.links" 
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

        <!-- Modal para editar roles -->
        <div v-if="showRoleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Editar Roles - {{ selectedUser?.name }}
                    </h3>
                    <form @submit.prevent="saveUserRoles">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Seleccionar Roles
                            </label>
                            <div class="space-y-2">
                                <div v-for="role in allRoles" :key="role.id" class="flex items-center">
                                    <input
                                        :id="`role-${role.id}`"
                                        v-model="selectedRoles"
                                        type="checkbox"
                                        :value="role.name"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <label :for="`role-${role.id}`" class="ml-2 block text-sm text-gray-700 capitalize">
                                        {{ role.name }}
                                    </label>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                Nota: Al asignar el rol de médico se creará automáticamente el perfil de doctor. 
                                Al asignar el rol de paciente se creará automáticamente el perfil de paciente.
                            </p>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                @click="closeRoleModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="selectedRoles.length === 0"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Actualizar Roles
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
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
    users: Object,
    filters: Object,
    allRoles: Array
})

// Formulario de búsqueda
const searchForm = reactive({
    search: props.filters?.search || '',
    role: props.filters?.role || ''
})

// Estado del modal de roles
const showRoleModal = ref(false)
const selectedUser = ref(null)
const selectedRoles = ref([])

let searchTimeout = null

const search = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get('/admin/users', {
            search: searchForm.search,
            role: searchForm.role
        }, {
            preserveState: true,
            preserveScroll: true
        })
    }, 300)
}

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

// Funciones para gestión de roles
const editUserRoles = (user) => {
    selectedUser.value = user
    selectedRoles.value = user.roles.map(role => role.name)
    showRoleModal.value = true
}

const closeRoleModal = () => {
    showRoleModal.value = false
    selectedUser.value = null
    selectedRoles.value = []
}

const saveUserRoles = () => {
    if (!selectedUser.value || selectedRoles.value.length === 0) {
        return
    }

    router.patch(`/admin/users/${selectedUser.value.id}/roles`, {
        roles: selectedRoles.value
    }, {
        onSuccess: () => {
            closeRoleModal()
            Swal.fire('Éxito', 'Roles actualizados correctamente', 'success')
        },
        onError: (errors) => {
            console.error('Errores:', errors)
            Swal.fire('Error', 'Error al actualizar los roles', 'error')
        }
    })
}

const toggleUserStatus = (user) => {
    const currentStatus = user.is_active
    const action = currentStatus ? 'desactivar' : 'activar'
    
    // Verificar que no sea el usuario actual
    if (user.id === props.$page?.props?.auth?.user?.id) {
        Swal.fire('Error', 'No puedes cambiar tu propio estado', 'error')
        return
    }
    
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Se ${action}á al usuario ${user.name}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: currentStatus ? '#d33' : '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Sí, ${action}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(`/admin/users/${user.id}/toggle-status`, {}, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    user.is_active = !currentStatus
                    Swal.fire(
                        currentStatus ? 'Desactivado' : 'Activado', 
                        `Usuario ${action}do exitosamente`, 
                        'success'
                    )
                },
                onError: (errors) => {
                    console.error('Errores:', errors)
                    Swal.fire('Error', 'Error al cambiar el estado del usuario', 'error')
                }
            })
        }
    })
}
</script>
