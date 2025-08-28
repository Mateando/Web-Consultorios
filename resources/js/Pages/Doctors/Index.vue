<template>
    <AuthenticatedLayout>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header con buscador -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Lista de Doctores</h3>
                            <div class="flex-1 md:max-w-md">
                                <form @submit.prevent class="flex gap-2 items-start">
                                    <input type="text" v-model="search" placeholder="Buscar por nombre o email" class="flex-1 rounded-md border-gray-300 shadow-sm text-sm" />
                                    <SecondaryButton type="button" v-if="search" @click="clearSearch">X</SecondaryButton>
                                </form>
                                <p v-if="isSearching" class="mt-1 text-xs text-gray-400">Buscando...</p>
                            </div>
                            <PrimaryButton @click="openCreateModal">Nuevo Doctor</PrimaryButton>
                        </div>
                    </div>
                </div>
                <!-- Tabla de doctores -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
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
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Especialidades</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matrícula</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="doctor in doctors.data" :key="doctor.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ doctor.user.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ doctor.user.email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex flex-wrap gap-1">
                                                <span v-for="specialty in doctor.specialties" :key="specialty.id" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ specialty.name }}</span>
                                                <span v-if="!doctor.specialties || doctor.specialties.length === 0" class="text-gray-400">Sin especialidades</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ doctor.phone || 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ doctor.license_number }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="{'bg-green-100 text-green-800': doctor.user.is_active !== false,'bg-red-100 text-red-800': doctor.user.is_active === false}" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">{{ doctor.user.is_active === false ? 'Inactivo' : 'Activo' }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <SecondaryButton type="button" @click="editDoctor(doctor)" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</SecondaryButton>
                                            <SecondaryButton type="button" @click="toggleDoctorStatus(doctor)" :class="{'text-red-600 hover:text-red-900': doctor.user.is_active !== false,'text-green-600 hover:text-green-900': doctor.user.is_active === false}">{{ doctor.user.is_active === false ? 'Activar' : 'Desactivar' }}</SecondaryButton>
                                        </td>
                                    </tr>
                                    <tr v-if="doctors.data && doctors.data.length === 0">
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No hay doctores registrados</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Paginación -->
                        <div v-if="doctors?.links && doctors.links.length > 3" class="mt-6 flex justify-center">
                            <nav class="flex flex-wrap gap-2">
                                <Link
                                    v-for="link in doctors.links"
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
                <!-- Modal para crear/editar doctor -->
                <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="closeModal"></div>
                        <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 relative">
                            <h3 class="text-lg font-medium mb-4">{{ showEditModal ? 'Editar Doctor' : 'Nuevo Doctor' }}</h3>
                            <form @submit.prevent="submitDoctorForm" class="space-y-4">
                                <!-- Nombre -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nombre completo</label>
                                    <input v-model="doctorForm.name" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" :class="{'border-red-500': errors.name}" />
                                    <p v-if="errors.name" class="text-red-600 text-sm mt-1">{{ errors.name }}</p>
                                </div>
                                <!-- Email -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <input v-model="doctorForm.email" type="email" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" :class="{'border-red-500': errors.email}" />
                                    <p v-if="errors.email" class="text-red-600 text-sm mt-1">{{ errors.email }}</p>
                                </div>
                                <!-- Contraseña -->
                                <div v-if="!showEditModal">
                                    <label class="block text-sm font-medium text-gray-700">Contraseña</label>
                                    <input v-model="doctorForm.password" type="password" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" :class="{'border-red-500': errors.password}" />
                                    <p v-if="errors.password" class="text-red-600 text-sm mt-1">{{ errors.password }}</p>
                                </div>
                                <!-- Teléfono -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                                    <input v-model="doctorForm.phone" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" :class="{'border-red-500': errors.phone}" />
                                    <p v-if="errors.phone" class="text-red-600 text-sm mt-1">{{ errors.phone }}</p>
                                </div>
                                <!-- Especialidades -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Especialidades</label>
                                    <div class="mt-2 space-y-2 max-h-32 overflow-y-auto border border-gray-300 rounded-md p-2" :class="{'border-red-500': errors.specialties}">
                                        <div v-for="spec in specialties" :key="spec.id" class="flex items-center">
                                            <input :id="`specialty-${spec.id}`" v-model="doctorForm.specialties" :value="spec.id" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
                                            <label :for="`specialty-${spec.id}`" class="ml-2 text-sm text-gray-700">{{ spec.name }}</label>
                                        </div>
                                    </div>
                                    <p v-if="errors.specialties" class="text-red-600 text-sm mt-1">{{ errors.specialties }}</p>
                                    <p class="text-gray-500 text-sm mt-1">Seleccione al menos una especialidad</p>
                                </div>
                                <!-- Matrícula -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Número de matrícula</label>
                                    <input v-model="doctorForm.license_number" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" :class="{'border-red-500': errors.license_number}" />
                                    <p v-if="errors.license_number" class="text-red-600 text-sm mt-1">{{ errors.license_number }}</p>
                                </div>
                                <!-- Cuota -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Cuota de consulta</label>
                                    <input v-model="doctorForm.consultation_fee" type="number" min="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" :class="{'border-red-500': errors.consultation_fee}" />
                                    <p v-if="errors.consultation_fee" class="text-red-600 text-sm mt-1">{{ errors.consultation_fee }}</p>
                                </div>
                                <!-- Botones -->
                                <div class="flex justify-end space-x-2 mt-4">
                                    <SecondaryButton type="button" @click="closeModal">Cancelar</SecondaryButton>
                                    <PrimaryButton type="submit" :disabled="processing">{{ processing ? 'Guardando...' : (showEditModal ? 'Actualizar' : 'Crear') }}</PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { Inertia } from '@inertiajs/inertia'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import Swal from 'sweetalert2'

const props = defineProps({
    doctors: Object,
    specialties: Array,
    filters: Object
})

const search = ref(props.filters?.search || '')
const isSearching = ref(false)
let debounceTimer = null
let lastSent = search.value

function triggerSearch(val){
    const normalized = (val||'').trim()
    if (normalized === (lastSent||'').trim()) return
    lastSent = normalized
    isSearching.value = true
    Inertia.cancelActiveVisits?.()
    router.get(route('doctors.index'), { search: normalized }, {
        only:['doctors','filters'],
        preserveState:true,
        replace:true,
        onFinish: () => { isSearching.value = false }
    })
}

watch(search, (val, oldVal) => {
    if (debounceTimer) clearTimeout(debounceTimer)
    if (val === '') {
        triggerSearch('')
        return
    }
    if (!oldVal) {
        triggerSearch(val)
        return
    }
    debounceTimer = setTimeout(() => {
        triggerSearch(val)
    }, 220)
})

const clearSearch = () => {
    if (debounceTimer) clearTimeout(debounceTimer)
    search.value = ''
    triggerSearch('')
}

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

const showCreateModal = ref(false)
const showEditModal = ref(false)
const selectedDoctor = ref(null)

// Formulario de doctor
const doctorForm = ref({
  name: '',
  email: '',
  password: '',
  phone: '',
  specialties: [],
  license_number: '',
  consultation_fee: ''
})
const errors = ref({})
const processing = ref(false)

// Abrir modal para crear
const openCreateModal = () => {
  selectedDoctor.value = null
  errors.value = {}
  doctorForm.value = { name: '', email: '', password: '', phone: '', specialties: [], license_number: '', consultation_fee: '' }
  showCreateModal.value = true
}
// Abrir modal para editar
const editDoctor = (doctor) => {
  selectedDoctor.value = doctor
  errors.value = {}
  doctorForm.value = {
    name: doctor.user.name,
    email: doctor.user.email,
    password: '', // no cambiar password
    phone: doctor.phone,
    specialties: doctor.specialties ? doctor.specialties.map(s => s.id) : [],
    license_number: doctor.license_number,
    consultation_fee: doctor.consultation_fee
  }
  showEditModal.value = true
}

const toggleDoctorStatus = (doctor) => {
    const currentStatus = doctor.user.is_active !== false
    const action = currentStatus ? 'desactivar' : 'activar'
    
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Se ${action}á al doctor ${doctor.user.name}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: currentStatus ? '#d33' : '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Sí, ${action}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(`/doctors/${doctor.id}/toggle-status`, {}, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    doctor.user.is_active = !currentStatus
                    Swal.fire(
                        currentStatus ? 'Desactivado' : 'Activado', 
                        `Doctor ${action}do exitosamente`, 
                        'success'
                    )
                }
            })
        }
    })
}

const deleteDoctor = (doctor) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Se desactivará al doctor ${doctor.user.name}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('doctors.destroy', doctor.id), {
                onSuccess: () => {
                    Swal.fire('Desactivado', 'Doctor desactivado exitosamente', 'success')
                }
            })
        }
    })
}

const closeModal = () => {
    showCreateModal.value = false
    showEditModal.value = false
    selectedDoctor.value = null
}

const submitDoctorForm = () => {
  processing.value = true
  errors.value = {}
  const isEdit = showEditModal.value && selectedDoctor.value
  const url = isEdit ? `/doctors/${selectedDoctor.value.id}` : '/doctors'
  const method = isEdit ? 'put' : 'post'
  router.visit(url, {
    method: method,
    data: doctorForm.value,
    onSuccess: () => {
      processing.value = false
      closeModal()
      // Recargar lista de doctores
      router.reload()
    },
    onError: (serverErrors) => {
      processing.value = false
      errors.value = serverErrors
    }
  })
}
</script>
