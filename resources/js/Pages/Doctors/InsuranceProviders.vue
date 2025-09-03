<template>
    <AuthenticatedLayout>
        <div class="py-12">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Obras Sociales por Doctor</h3>
                            <p class="text-sm text-gray-500">Asigne las obras sociales que atiende cada doctor</p>
                        </div>

                        <div class="mb-4">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div class="flex-1 md:max-w-md">
                                    <form @submit.prevent class="flex gap-2 items-start">
                                        <input type="text" v-model="search" placeholder="Buscar por nombre o email" class="flex-1 rounded-md border-gray-300 shadow-sm text-sm" />
                                        <select v-model="specialtyFilter" @change="applyFilters" class="rounded-md border-gray-300 shadow-sm text-sm">
                                            <option value="">Todas las especialidades</option>
                                            <option v-for="spec in specialties" :key="spec.id" :value="spec.id">{{ spec.name }}</option>
                                        </select>
                                        <select @change="onDoctorFilterChange" v-model="filters.doctor_id" class="rounded-md border-gray-300 shadow-sm text-sm">
                                            <option value="">Todos los doctores</option>
                                            <option v-for="d in doctorsAll" :key="d.id" :value="d.id">{{ d.user.name }}</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                            <p v-if="isSearching" class="mt-1 text-xs text-gray-400">Buscando...</p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Obras Sociales asignadas</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="doctor in doctors.data" :key="doctor.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ doctor.user.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex flex-wrap gap-1">
                                                <span v-for="ip in doctor.insurance_providers" :key="ip.id" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ ip.name }}</span>
                                                <span v-if="!doctor.insurance_providers || doctor.insurance_providers.length===0" class="text-gray-400">Sin asignar</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <SecondaryButton type="button" @click="openAssignModal(doctor)" class="text-indigo-600 hover:underline">Asignar / Editar</SecondaryButton>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 flex justify-center">
                            <nav class="flex flex-wrap gap-2">
                                <Link v-for="link in doctors.links" :key="link.label + (link.url||'')" :href="link.url || '#'" v-html="sanitizeLabel(link.label)" :class="linkClass(link)" />
                            </nav>
                        </div>

                        <!-- Modal de asignación -->
                        <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                            <div class="bg-white rounded shadow w-full max-w-lg p-6">
                                <h3 class="text-base font-semibold mb-4">Asignar Obras Sociales a {{ currentDoctor?.user?.name }}</h3>
                                <form @submit.prevent="saveAssignments">
                                    <div class="grid grid-cols-1 gap-3 max-h-72 overflow-y-auto border p-3 rounded">
                                        <label v-for="ip in insuranceProviders" :key="ip.id" class="flex items-center gap-2">
                                            <input type="checkbox" :value="ip.id" v-model="selectedIds" class="h-4 w-4" />
                                            <span class="text-sm">{{ ip.name }}</span>
                                        </label>
                                    </div>
                                    <div class="flex justify-end gap-2 mt-4">
                                        <SecondaryButton type="button" @click="closeModal">Cancelar</SecondaryButton>
                                        <PrimaryButton type="submit">Guardar</PrimaryButton>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { Link, router } from '@inertiajs/vue3'
import { Inertia } from '@inertiajs/inertia'

const props = defineProps({ doctors: Object, doctorsAll: Array, insuranceProviders: Array, filters: Object, specialties: Array })

const showModal = ref(false)
const currentDoctor = ref(null)
const selectedIds = ref([])

const insuranceProviders = props.insuranceProviders || []
const doctorsAll = props.doctorsAll || []
const specialties = props.specialties || []
const filters = ref(props.filters || { doctor_id: '' })

const search = ref(props.filters?.search || '')
const specialtyFilter = ref(props.filters?.specialty_id || '')
const isSearching = ref(false)
let debounceTimer = null
let lastSent = search.value

function onDoctorFilterChange(){
    // Preserve other filters
    const params = new URLSearchParams(window.location.search)
    if (filters.value.doctor_id) params.set('doctor_id', filters.value.doctor_id)
    else params.delete('doctor_id')
    if (search.value) params.set('search', search.value.trim())
    else params.delete('search')
    if (specialtyFilter.value) params.set('specialty_id', specialtyFilter.value)
    else params.delete('specialty_id')
    const url = `${window.location.pathname}?${params.toString()}`
    router.get(url, {}, { replace: true, preserveState: true })
}

function triggerSearch(val){
    const normalized = (val||'').trim()
    if (normalized === (lastSent||'').trim()) return
    lastSent = normalized
    isSearching.value = true
    Inertia.cancelActiveVisits?.()
    router.get(route('doctors.insurance-providers.index'), { search: normalized, specialty_id: specialtyFilter.value, doctor_id: filters.value.doctor_id }, {
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

const applyFilters = () => {
    isSearching.value = true
    Inertia.cancelActiveVisits?.()
    router.get(route('doctors.insurance-providers.index'), { search: search.value.trim(), specialty_id: specialtyFilter.value, doctor_id: filters.value.doctor_id }, {
        only:['doctors','filters'],
        preserveState:true,
        replace:true,
        onFinish: () => { isSearching.value = false }
    })
}

function openAssignModal(doctor){
    currentDoctor.value = doctor
    selectedIds.value = (doctor.insurance_providers || []).map(ip => ip.id)
    showModal.value = true
}

function closeModal(){ showModal.value = false; currentDoctor.value = null; selectedIds.value = [] }

function saveAssignments(){
    if (!currentDoctor.value) return
    router.put(`/doctors/${currentDoctor.value.id}/insurance-providers`, { insurance_provider_ids: selectedIds.value }, {
        onFinish: () => { closeModal(); router.reload() }
    })
}

const sanitizeLabel = (l) => (l||'').replace('&laquo;','«').replace('&raquo;','»')
const linkClass = (link) => link.active ? 'px-3 py-2 rounded bg-gray-800 text-white text-xs' : 'px-3 py-2 rounded bg-gray-200 text-gray-700 text-xs'
</script>
