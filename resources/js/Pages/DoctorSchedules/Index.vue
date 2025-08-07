<template>
  <Head title="Horarios de Doctores" />

  <AuthenticatedLayout>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            
            <!-- Botón para crear nuevo horario -->
            <div class="mb-6 flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-900">Gestión de Horarios</h3>
              <PrimaryButton @click="showCreateModal = true">
                Agregar Horario
              </PrimaryButton>
            </div>

            <!-- Selector de doctor (solo para admin) -->
            <div v-if="$page.props.auth.user.roles.some(role => role.name === 'administrador')" class="mb-6">
              <label for="doctor-select" class="block text-sm font-medium text-gray-700 mb-2">
                Filtrar por Doctor:
              </label>
              <select 
                id="doctor-select"
                v-model="selectedDoctorId"
                @change="filterByDoctor"
                class="w-full max-w-xs rounded-md border-gray-300 shadow-sm"
              >
                <option value="">Todos los doctores</option>
                <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                  {{ doctor.user.name }}
                </option>
              </select>
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
                  <tr v-for="schedule in filteredSchedules" :key="schedule.id">
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
                      <button @click="editSchedule(schedule)" 
                              class="text-indigo-600 hover:text-indigo-900">
                        Editar
                      </button>
                      <button @click="deleteSchedule(schedule)" 
                              class="text-red-600 hover:text-red-900">
                        Eliminar
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
              
              <div v-if="filteredSchedules.length === 0" class="text-center py-8 text-gray-500">
                No hay horarios configurados.
              </div>
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
              Especialidad *
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
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import Modal from '@/Components/Modal.vue'
import InputError from '@/Components/InputError.vue'

// Props
const props = defineProps({
  schedules: Array,
  doctors: Array,
  specialties: Array,
  days_of_week: Object,
})

// Estado del componente
const showCreateModal = ref(false)
const showEditModal = ref(false)
const selectedDoctorId = ref('')
const editingSchedule = ref(null)

// Datos computados
const daysOfWeek = computed(() => props.days_of_week)

const filteredSchedules = computed(() => {
  if (!selectedDoctorId.value) {
    return props.schedules
  }
  return props.schedules.filter(schedule => schedule.doctor_id == selectedDoctorId.value)
})

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

// Obtener doctor_id del usuario logueado si es doctor
const currentUser = computed(() => props.$page?.props?.auth?.user)
const userDoctor = computed(() => {
  if (currentUser.value?.roles?.some(role => role.name === 'medico')) {
    return props.doctors.find(doctor => doctor.user.id === currentUser.value.id)
  }
  return null
})

// Doctor seleccionado para el formulario
const selectedDoctorForForm = computed(() => {
  if (form.doctor_id) {
    return props.doctors.find(doctor => doctor.id == form.doctor_id)
  }
  if (userDoctor.value) {
    return userDoctor.value
  }
  return null
})

// Especialidades disponibles para el doctor seleccionado
const availableSpecialties = computed(() => {
  if (!selectedDoctorForForm.value) {
    return []
  }
  return selectedDoctorForForm.value.specialties || []
})

// Watcher para resetear specialty_id cuando cambia el doctor
watch(() => form.doctor_id, () => {
  form.specialty_id = ''
})

// Métodos
const filterByDoctor = () => {
  // Filtrado reactivo manejado por computed
}

const formatTime = (time) => {
  return time.substring(0, 5) // Remover segundos si existen
}

const onDoctorChange = () => {
  form.specialty_id = '' // Reset specialty when doctor changes
}

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
  // Si es doctor, establecer su ID automáticamente
  if (userDoctor.value && showCreateModal.value) {
    form.doctor_id = userDoctor.value.id
  }

  if (showEditModal.value) {
    form.put(route('doctor-schedules.update', editingSchedule.value.id), {
      onSuccess: () => {
        closeModal()
      }
    })
  } else {
    form.post(route('doctor-schedules.store'), {
      onSuccess: () => {
        closeModal()
      }
    })
  }
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  editingSchedule.value = null
  form.reset()
  form.clearErrors()
}

// Inicializar doctor_id si es médico
if (userDoctor.value) {
  form.doctor_id = userDoctor.value.id
}
</script>


