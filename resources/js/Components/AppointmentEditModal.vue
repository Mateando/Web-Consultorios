<template>
  <Modal :show="show" maxWidth="lg" @close="$emit('close')">
    <template #default>
      <div class="relative">
        <div v-if="loading" class="p-6 flex items-center justify-center">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
          </svg>
          <div class="text-gray-700">Cargando cita...</div>
        </div>

        <form v-else @submit.prevent="submit">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Editar Cita</h3>

            <div class="grid grid-cols-1 gap-3">
              <div>
                <label class="block text-sm text-gray-500">Paciente</label>
                <div class="mt-1 text-gray-800">{{ patientDisplayName }}</div>
                <input type="hidden" v-model="form.patient_id" />
              </div>

              <div v-if="isStaff || editMode">
                <div class="grid grid-cols-2 gap-2 mt-2">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Especialidad</label>
                    <select v-model="form.specialty_id" @change="onSpecialtyOrDoctorChange" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                      <option value="">(Sin especificar)</option>
                      <option v-for="sp in specialties" :key="sp.id" :value="sp.id">{{ sp.name }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Doctor</label>
                    <select v-model="form.doctor_id" @change="onSpecialtyOrDoctorChange" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                      <option value="">Seleccionar doctor</option>
                      <option v-for="d in filteredDoctors" :key="d.id" :value="d.id">{{ d.user.name }}</option>
                    </select>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-2 mt-2">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Fecha</label>
                    <input type="date" v-model="form.appointment_date" @change="onDateChange" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Hora</label>
                    <select v-model="form.appointment_time" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                      <option value="">Seleccionar hora</option>
                      <option v-for="slot in slots" :key="slot" :value="slot">{{ slot }}</option>
                    </select>
                    <div v-if="errors.appointment_time" class="mt-1 text-sm text-red-600">{{ errors.appointment_time }}</div>
                    <div v-if="form.doctor_id && form.appointment_date && slots.length === 0" class="mt-1 text-sm text-yellow-600">No hay horarios disponibles para esta fecha</div>
                    <div v-if="form.doctor_id && form.appointment_date && slots.length > 0" class="mt-1 text-sm text-green-600">{{ slots.length }} horarios disponibles</div>
                  </div>
                </div>

                <label class="block text-sm font-medium text-gray-700 mt-2">Estado</label>
                <select v-model="form.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <option value="programada">Programada</option>
                  <option value="confirmada">Confirmada</option>
                  <option value="en_curso">En Curso</option>
                  <option value="completada">Completada</option>
                  <option value="cancelada">Cancelada</option>
                  <option value="no_asistio">No Asistió</option>
                </select>

                <label class="block text-sm font-medium text-gray-700 mt-2">Motivo</label>
                <input type="text" v-model="form.reason" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />

                <label class="block text-sm font-medium text-gray-700 mt-2">Notas</label>
                <textarea v-model="form.notes" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="3"></textarea>
              </div>
              <div v-else>
                <div>
                  <div class="text-sm text-gray-500">Doctor</div>
                  <div class="mt-1 text-gray-800">{{ doctorName }}</div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <div class="text-sm text-gray-500">Fecha</div>
                    <div class="mt-1 text-gray-800">{{ appointmentDate }}</div>
                  </div>
                  <div>
                    <div class="text-sm text-gray-500">Hora</div>
                    <div class="mt-1 text-gray-800">{{ appointmentTime }}</div>
                  </div>
                </div>

                <div>
                  <div class="text-sm text-gray-500">Motivo</div>
                  <div class="mt-1 text-gray-800">{{ reason }}</div>
                </div>

                <div>
                  <div class="text-sm text-gray-500">Estado</div>
                  <div class="mt-1 text-gray-800">{{ status }}</div>
                </div>

                <div v-if="notes">
                  <div class="text-sm text-gray-500">Notas</div>
                  <div class="mt-1 text-gray-800">{{ notes }}</div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button v-if="isStaff || editMode" type="submit" :disabled="form.processing || submitting" class="inline-flex ml-2 justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-white">Actualizar</button>
            <button v-else type="button" @click.prevent="enterEditMode" class="inline-flex ml-2 justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-white">Editar</button>
            <button type="button" @click="$emit('close')" class="mt-3 sm:mt-0 ml-2 inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white">Cerrar</button>
          </div>
        </form>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import Modal from './Modal.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  show: Boolean,
  appointment: Object,
  doctors: Array,
  specialties: Array,
  isStaff: Boolean,
  forceEdit: Boolean
})
const emit = defineEmits(['close', 'saved', 'consumed-force-edit'])

const form = useForm({
  patient_id: '',
  doctor_id: '',
  specialty_id: '',
  appointment_date: '',
  appointment_time: '',
  duration: 30,
  reason: '',
  reason_id: null,
  notes: '',
  status: 'programada'
})

// Exponer errores para la plantilla; useForm puede proveer form.errors
const errors = computed(() => form.errors || {})

const slots = ref([])
const submitting = ref(false)
const editMode = ref(false)
const loading = ref(false)

const patientDisplayName = computed(() => props.appointment?.patient?.name || props.appointment?.patient_name || '')
const doctorName = computed(() => props.appointment?.doctor?.user?.name || props.appointment?.doctor_name || '')
const appointmentDate = computed(() => props.appointment?.appointment_date || props.appointment?.start || '')
const appointmentTime = computed(() => props.appointment?.appointment_time || '')
const reason = computed(() => props.appointment?.reason || '')
const status = computed(() => props.appointment?.status || '')
const notes = computed(() => props.appointment?.notes || '')
const hasPhone = computed(() => !!(props.appointment && (props.appointment.patient?.phone || props.appointment.patient_phone)))

const filteredDoctors = computed(() => {
  if (!form.specialty_id) return props.doctors || []
  return (props.doctors || []).filter(d => (d.specialties || []).some(s => String(s.id) === String(form.specialty_id)))
})

// Safe data assignment helper
function assignFormData(payload) {
  if (typeof form.setData === 'function') {
    form.setData(payload)
  } else if (typeof form.set === 'function') {
    form.set(payload)
  } else if (form && typeof form === 'object') {
    Object.keys(payload).forEach(k => {
      if (k in form) form[k] = payload[k]
      else if (form.data && (k in form.data)) form.data[k] = payload[k]
    })
  }
}

watch(() => props.show, async (v) => {
  if (!v) return
  // If parent provided a full appointment, prefill immediately
  if (props.appointment && props.appointment.id && props.appointment.patient) {
    prefillFromAppointment(props.appointment)
    // If parent forced edit, honor it
    if (props.forceEdit) {
      editMode.value = true
      emit('consumed-force-edit')
    }
    return
  }

  // If forced edit but we don't have full data yet, fetch it before showing
  if (props.forceEdit && props.appointment && props.appointment.id) {
    loading.value = true
    try {
      const res = await axios.get(`/api/appointments/${props.appointment.id}`)
      const data = res.data || {}
      if (data.appointment) {
        prefillFromAppointment(data.appointment)
      }
      // if backend says cannot edit, inform user
      if (data.can_edit === false) {
        alert('No está permitido editar esta cita (fecha pasada o permisos insuficientes).')
        emit('close')
        return
      }
      editMode.value = true
      emit('consumed-force-edit')
    } catch (e) {
      alert('No se pudo obtener la cita. Intente nuevamente.')
      emit('close')
    } finally {
      loading.value = false
    }
    return
  }

  // Otherwise, if we have an appointment id but limited data, still prefill minimal
  if (props.appointment && props.appointment.id) {
    prefillFromAppointment(props.appointment)
  }
})

function prefillFromAppointment(a) {
  const dt = a.appointment_date ? new Date(a.appointment_date) : (a.start ? new Date(a.start) : null)
  const date = dt && !isNaN(dt.getTime()) ? dt.toISOString().split('T')[0] : ''
  const time = dt && !isNaN(dt.getTime()) ? dt.toTimeString().slice(0,5) : ''
  form.reset()
  const payload = {
    patient_id: a.patient_id || a.patient?.id || '',
    doctor_id: a.doctor_id || a.doctor?.id || '',
    specialty_id: a.specialty_id || a.specialty?.id || '',
    appointment_date: date,
    appointment_time: time,
    duration: a.duration || 30,
    reason: a.reason || '',
    reason_id: a.reason_id || null,
    notes: a.notes || '',
    status: a.status || 'programada'
  }
  assignFormData(payload)
  if (form.doctor_id && form.appointment_date) loadSlots()
  // If backend provided can_edit and user is not staff, honor it
  if (typeof a.can_edit !== 'undefined') {
    editMode.value = !!a.can_edit && !props.isStaff
  } else {
    editMode.value = false
  }
}

const enterEditMode = async () => {
  if (!props.appointment || !props.appointment.id) return
  if (typeof props.appointment.can_edit !== 'undefined') {
    if (!props.appointment.can_edit) {
      alert('No está permitido editar esta cita (fecha pasada o permisos insuficientes).')
      return
    }
    editMode.value = true
    return
  }

  // As fallback, ask backend
  try {
    const res = await axios.get(`/api/appointments/${props.appointment.id}`)
    const data = res.data || {}
    if (data.can_edit === false) {
      alert('No está permitido editar esta cita (fecha pasada o permisos insuficientes).')
      return
    }
    if (data.appointment) prefillFromAppointment(data.appointment)
    editMode.value = true
  } catch (e) {
    alert('No se pudo verificar permisos de edición. Intente nuevamente.')
  }
}

const onSpecialtyOrDoctorChange = () => {
  form.appointment_time = ''
  slots.value = []
  if (form.doctor_id && form.appointment_date) loadSlots()
}

const onDateChange = () => {
  form.appointment_time = ''
  slots.value = []
  if (form.doctor_id && form.appointment_date) loadSlots()
}

const loadSlots = async () => {
  try {
    const params = {
      doctor_id: form.doctor_id,
      specialty_id: form.specialty_id,
      date: form.appointment_date,
      editing_appointment_id: props.appointment?.id || null
    }
    const res = await axios.get('/api/appointments/available-slots', { params })
    slots.value = res.data.slots || []
  } catch (e) {
    slots.value = []
  }
}

const isFutureAppointment = () => {
  try {
    const a = form.appointment_date && form.appointment_time ? new Date(`${form.appointment_date}T${form.appointment_time}:00`) : null
    if (!a) return false
    return a.getTime() > Date.now()
  } catch (e) {
    return false
  }
}

const submit = async () => {
  if (!props.appointment || !props.appointment.id) return
  // Ensure a time is selected; backend will treat empty as 00:00 which may be invalid
  if (!form.appointment_time) {
    alert('Seleccione una hora para la cita antes de actualizar.')
    return
  }

  if (!isFutureAppointment()) {
    alert('La fecha y hora deben ser futuras. No es posible reasignar a una fecha pasada.');
    return
  }

  submitting.value = true
  try {
  // Combinar fecha y hora en appointment_date para enviar un datetime completo al backend
  const originalDate = form.appointment_date
  form.appointment_date = `${form.appointment_date} ${form.appointment_time}`
  await form.put(`/appointments/${props.appointment.id}`)
    emit('saved')
    emit('close')
  } catch (e) {
    // Los errores de validación son manejados por Inertia y se muestran en el formulario si aplica
  } finally {
  // Restaurar valor de fecha (solo fecha) para la UI
  try { form.appointment_date = originalDate } catch (e) {}
  submitting.value = false
  }
}

</script>

<style scoped>
/* estilos mínimos */
</style>
/* estilos mínimos */
