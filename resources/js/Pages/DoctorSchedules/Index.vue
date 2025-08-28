<template>
  <Head title="Horarios de Doctores" />

  <AuthenticatedLayout>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            
            <!-- Buscador y selector de doctor -->
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
              <div v-if="$page.props.auth.user.roles.some(role => role.name === 'administrador')" class="w-full md:max-w-md">
                <label for="doctor-select" class="block text-sm font-medium text-gray-700 mb-2">
                  Filtrar por Doctor:
                </label>
                <select 
                  id="doctor-select"
                  v-model="selectedDoctorId"
                  @change="applyFilters"
                  class="w-full rounded-md border-gray-300 shadow-sm"
                >
                  <option value="">Todos los doctores</option>
                  <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                    {{ doctor.user.name }}
                  </option>
                </select>
              </div>
              <div class="flex-1 md:max-w-md">
                <form @submit.prevent class="flex gap-2 items-start">
                  <input type="text" v-model="search" placeholder="Buscar por doctor" class="flex-1 rounded-md border-gray-300 shadow-sm text-sm" />
                  <SecondaryButton type="button" v-if="search" @click="clearSearch">X</SecondaryButton>
                </form>
                <p v-if="isSearching" class="mt-1 text-xs text-gray-400">Buscando...</p>
              </div>
              <div>
                <PrimaryButton @click="showCreateModal = true">Agregar Horario</PrimaryButton>
              </div>
            </div>

            <!-- Tabla de horarios -->
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th v-if="$page.props.auth.user.roles.some(role => role.name === 'administrador')" 
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Doctor
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Especialidad
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Día
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Horario
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Duración Cita
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
                  <tr v-for="schedule in schedulesList" :key="schedule.id">
                    <td v-if="$page.props.auth.user.roles.some(role => role.name === 'administrador')" 
                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ schedule.doctor.user.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                        {{ schedule.specialty ? schedule.specialty.name : 'Sin especialidad' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ daysOfWeek[schedule.day_of_week] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ schedule.appointment_duration }} min
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="schedule.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                        {{ schedule.is_active ? 'Activo' : 'Inactivo' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                      <SecondaryButton type="button" @click="editSchedule(schedule)" class="!px-2 !py-1 text-indigo-600 hover:text-indigo-900 bg-white border-gray-200">Editar</SecondaryButton>
                      <SecondaryButton type="button" @click="deleteSchedule(schedule)" class="!px-2 !py-1 text-red-600 hover:text-red-900 bg-white border-gray-200">Eliminar</SecondaryButton>
                    </td>
                  </tr>
                </tbody>
              </table>
              
              <div v-if="schedulesList.length === 0" class="text-center py-8 text-gray-500">
                No hay horarios configurados.
              </div>
            </div>

            <!-- Paginación -->
            <div v-if="schedules?.links && schedules.links.length > 3" class="mt-6 flex justify-center">
              <nav class="flex flex-wrap gap-2">
                <Link
                  v-for="link in schedules.links"
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

    <!-- Modal para crear/editar horario -->
    <Modal :show="showCreateModal || showEditModal" @close="closeModal">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          {{ showEditModal ? 'Editar Horario' : 'Crear Nuevo Horario' }}
        </h3>


        <form @submit.prevent="submitForm">
          <!-- Selector de doctor (solo para admin o cuando se crea) -->
          <div v-if="$page.props.auth.user.roles.some(role => role.name === 'administrador') && showCreateModal" class="mb-4">
            <label for="doctor" class="block text-sm font-medium text-gray-700 mb-1">
              Doctor *
            </label>
            <select v-model="form.doctor_id" 
                    id="doctor"
                    required
                    @change="onDoctorChange"
                    class="w-full rounded-md border-gray-300 shadow-sm">
              <option value="">Seleccionar doctor</option>
              <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                {{ doctor.user.name }}
              </option>
            </select>
            <InputError :message="form.errors.doctor_id" />
          </div>

          <!-- Selector de especialidad -->
          <div class="mb-4">
            <label for="specialty" class="block text-sm font-medium text-gray-700 mb-1">
              Especialidad ****
            </label>
            <select v-model="form.specialty_id" 
                    id="specialty"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm">
              <option value="">Seleccionar especialidad</option>
              <option v-for="specialty in availableSpecialties" :key="specialty.id" :value="specialty.id">
                {{ specialty.name }}
              </option>
            </select>
            <InputError :message="form.errors.specialty_id" />
            <p v-if="!selectedDoctorForForm" class="text-xs text-gray-500 mt-1">
              Primero seleccione un doctor para ver sus especialidades
            </p>
          </div>

          <!-- Día de la semana -->
          <div class="mb-4">
            <label for="day" class="block text-sm font-medium text-gray-700 mb-1">
              Día de la semana *
            </label>
            <select v-model="form.day_of_week" 
                    id="day"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm">
              <option value="">Seleccionar día</option>
              <option v-for="(label, value) in daysOfWeek" :key="value" :value="value">
                {{ label }}
              </option>
            </select>
            <InputError :message="form.errors.day_of_week" />
          </div>

          <!-- Hora de inicio -->
          <div class="mb-4">
            <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">
              Hora de inicio *
            </label>
            <input v-model="form.start_time" 
                   type="time" 
                   id="start_time"
                   required
                   class="w-full rounded-md border-gray-300 shadow-sm" />
            <InputError :message="form.errors.start_time" />
          </div>

          <!-- Hora de fin -->
          <div class="mb-4">
            <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">
              Hora de fin *
            </label>
            <input v-model="form.end_time" 
                   type="time" 
                   id="end_time"
                   required
                   class="w-full rounded-md border-gray-300 shadow-sm" />
            <InputError :message="form.errors.end_time" />
          </div>

          <!-- Duración de cita -->
          <div class="mb-4">
            <label for="duration" class="block text-sm font-medium text-gray-700 mb-1">
              Duración de cita (minutos) *
            </label>
            <select v-model="form.appointment_duration" 
                    id="duration"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm">
              <option value="15">15 minutos</option>
              <option value="20">20 minutos</option>
              <option value="30">30 minutos</option>
              <option value="45">45 minutos</option>
              <option value="60">60 minutos</option>
            </select>
            <InputError :message="form.errors.appointment_duration" />
          </div>

          <!-- Estado (solo en edición) -->
          <div v-if="showEditModal" class="mb-4">
            <label class="flex items-center">
              <input v-model="form.is_active" 
                     type="checkbox" 
                     class="rounded border-gray-300 text-indigo-600 shadow-sm" />
              <span class="ml-2 text-sm text-gray-600">Horario activo</span>
            </label>
          </div>

          <!-- Botones -->
          <div class="flex justify-end space-x-3">
            <SecondaryButton @click="closeModal">
              Cancelar
            </SecondaryButton>
            <PrimaryButton type="submit" :disabled="form.processing">
              {{ showEditModal ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { Head, useForm, Link, router } from '@inertiajs/vue3'
import { Inertia } from '@inertiajs/inertia'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import Modal from '@/Components/Modal.vue'
import InputError from '@/Components/InputError.vue'

// Props
const props = defineProps({
  schedules: [Array, Object],
  doctors: Array,
  specialties: Array,
  days_of_week: Object,
  filters: Object
})

// Estado
const showCreateModal = ref(false)
const showEditModal = ref(false)
const selectedDoctorId = ref(props.filters?.doctor_id || '')
const editingSchedule = ref(null)

// Búsqueda
const search = ref(props.filters?.search || '')
const isSearching = ref(false)
let debounceTimer = null
let lastSent = search.value

function triggerSearch(val) {
  const normalized = (val||'').trim()
  if (normalized === (lastSent||'').trim()) return
  lastSent = normalized
  isSearching.value = true
  Inertia.cancelActiveVisits?.()
  router.get(route('doctor-schedules.index'), { search: normalized, doctor_id: selectedDoctorId.value }, {
    only: ['schedules','filters'],
    preserveState: true,
    replace: true,
    onFinish: () => { isSearching.value = false }
  })
}

watch(search, (val, oldVal) => {
  if (debounceTimer) clearTimeout(debounceTimer)
  if (val === '') { triggerSearch(''); return }
  if (!oldVal) { triggerSearch(val); return }
  debounceTimer = setTimeout(() => { triggerSearch(val) }, 220)
})

const clearSearch = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  search.value = ''
  triggerSearch('')
}

const applyFilters = () => {
  // Enviar al servidor con el filtro seleccionado y el search actual
  triggerSearch(search.value)
}

// Computed: lista segura de schedules (soporta paginación o array simple)
const schedulesList = computed(() => {
  const list = props.schedules?.data ?? props.schedules ?? []
  if (!selectedDoctorId.value) return list
  return list.filter(s => String(s.doctor_id) === String(selectedDoctorId.value))
})

// Helpers de paginación y etiquetas
const paginationLinkClasses = (link) => {
  if (link.label === '...') return 'px-3 py-2 text-sm text-gray-400 cursor-default select-none'
  const base = 'inline-flex items-center rounded-md border border-transparent px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2'
  if (link.active) return base + ' bg-gray-800 hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900'
  if (!link.url) return 'inline-flex items-center justify-center px-3 py-2 rounded-md bg-gray-100 border border-gray-200 text-gray-400 cursor-not-allowed text-sm'
  return base + ' bg-gray-800/80 hover:bg-gray-700/90 text-white'
}

const sanitizeLabel = (label) => {
  if (!label) return ''
  return label.replace('&laquo;', '«').replace('&raquo;', '»')
}

// Otros
const daysOfWeek = computed(() => props.days_of_week)

// Formulario
const form = useForm({
  doctor_id: '',
  specialty_id: '',
  day_of_week: '',
  start_time: '',
  end_time: '',
  appointment_duration: 30,
  is_active: true,
})

const currentUser = computed(() => props.$page?.props?.auth?.user)
const userDoctor = computed(() => {
  if (currentUser.value?.roles?.some(role => role.name === 'medico')) {
    return props.doctors.find(doctor => doctor.user.id === currentUser.value.id)
  }
  return null
})

const selectedDoctorForForm = computed(() => {
  if (form.doctor_id) return props.doctors.find(doctor => doctor.id == form.doctor_id)
  if (userDoctor.value) return userDoctor.value
  return null
})

const availableSpecialties = computed(() => selectedDoctorForForm.value ? (selectedDoctorForForm.value.specialties || []) : [])

watch(() => form.doctor_id, () => { form.specialty_id = '' })

const formatTime = (time) => time.substring(0,5)

const onDoctorChange = () => { form.specialty_id = '' }

const editSchedule = (schedule) => {
  editingSchedule.value = schedule
  form.doctor_id = schedule.doctor_id
  form.specialty_id = schedule.specialty_id
  form.day_of_week = schedule.day_of_week
  form.start_time = formatTime(schedule.start_time)
  form.end_time = formatTime(schedule.end_time)
  form.appointment_duration = schedule.appointment_duration
  form.is_active = schedule.is_active
  showEditModal.value = true
}

const deleteSchedule = (schedule) => {
  if (confirm('¿Estás seguro de que quieres eliminar este horario?')) {
    form.delete(route('doctor-schedules.destroy', schedule.id))
  }
}

const submitForm = () => {
  if (userDoctor.value && showCreateModal.value) form.doctor_id = userDoctor.value.id
  if (showEditModal.value) {
    form.put(route('doctor-schedules.update', editingSchedule.value.id), { onSuccess: closeModal })
  } else {
    form.post(route('doctor-schedules.store'), { onSuccess: closeModal })
  }
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  editingSchedule.value = null
  form.reset()
  form.clearErrors()
}

if (userDoctor.value) form.doctor_id = userDoctor.value.id
</script>



