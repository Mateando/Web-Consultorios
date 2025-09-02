<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="$emit('close')"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form @submit.prevent="submit">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Editar Cita</h3>

            <div class="grid grid-cols-1 gap-3">
              <div>
                <label class="block text-sm text-gray-500">Paciente</label>
                <div class="mt-1 text-gray-800">{{ patientDisplayName }}</div>
              </div>

              <div v-if="isStaff || editMode">
                <label class="block text-sm font-medium text-gray-700">Paciente</label>
                <select v-model="form.patient_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <option value="">Seleccionar paciente</option>
                  <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.user.name }}</option>
                </select>

                <label class="block text-sm font-medium text-gray-700 mt-2">Especialidad</label>
                <select v-model="form.specialty_id" @change="onSpecialtyOrDoctorChange" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <option value="">(Sin especificar)</option>
                  <option v-for="sp in specialties" :key="sp.id" :value="sp.id">{{ sp.name }}</option>
                </select>

                <label class="block text-sm font-medium text-gray-700 mt-2">Doctor</label>
                <select v-model="form.doctor_id" @change="onSpecialtyOrDoctorChange" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <option value="">Seleccionar doctor</option>
                  <option v-for="d in filteredDoctors" :key="d.id" :value="d.id">{{ d.user.name }}</option>
                </select>

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
                <!-- Pacientes ven sólo lectura: reutilizar visualización anterior -->
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
            <button type="button" @click="$emit('print', appointment)" class="inline-flex ml-2 justify-center rounded-md border border-gray-300 px-4 py-2 bg-white">Imprimir</button>
            <button type="button" :disabled="!hasPhone" @click="openWhatsApp" :class="['inline-flex ml-2 justify-center rounded-md px-4 py-2', hasPhone ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500']">WhatsApp</button>
            <button type="button" @click="$emit('close')" class="mt-3 sm:mt-0 ml-2 inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white">Cerrar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({ show: Boolean, appointment: Object, doctors: Array, patients: Array, specialties: Array, userPermissions: Object })
const emit = defineEmits(['close','saved','print','edit'])

const submitting = ref(false)
const editMode = ref(false)

const isStaff = computed(() => !props.userPermissions?.is_patient && props.userPermissions?.can_edit_appointments)

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

const slots = ref([])

const patientDisplayName = computed(() => props.appointment?.patient?.user?.name || props.appointment?.patient_name || 'N/A')
const doctorName = computed(() => props.appointment?.doctor?.user?.name || props.appointment?.doctor_name || props.appointment?.doctor?.name || 'N/A')
const appointmentDate = computed(() => {
  const a = props.appointment
  if (!a) return ''
  const dt = a.appointment_date ? new Date(a.appointment_date) : (a.start ? new Date(a.start) : null)
  return dt && !isNaN(dt.getTime()) ? dt.toLocaleDateString() : ''
})
const appointmentTime = computed(() => {
  const a = props.appointment
  if (!a) return ''
  const dt = a.appointment_date ? new Date(a.appointment_date) : (a.start ? new Date(a.start) : null)
  return dt && !isNaN(dt.getTime()) ? dt.toTimeString().slice(0,5) : ''
})
const reason = computed(() => props.appointment?.reason || props.appointment?.appointment_reason || '-')
const status = computed(() => props.appointment?.status || '-')
const notes = computed(() => props.appointment?.notes || '')

const phone = computed(() => props.appointment?.patient?.phone || props.appointment?.patient?.user?.phone || props.appointment?.patient_phone || '')
const hasPhone = computed(() => !!phone.value && phone.value.toString().trim().length > 0)

const openWhatsApp = () => {
  if (!hasPhone.value) return
  const num = phone.value.toString().replace(/[^+0-9]/g, '')
  const text = encodeURIComponent(`Hola, respecto a su cita el ${appointmentDate.value} a las ${appointmentTime.value}`)
  const url = `https://wa.me/${num}?text=${text}`
  window.open(url, '_blank')
}

const filteredDoctors = computed(() => {
  if (!form.specialty_id) return props.doctors || []
  return (props.doctors || []).filter(d => (d.specialties || []).some(s => String(s.id) === String(form.specialty_id)))
})

watch(() => props.show, (v) => {
  if (v && props.appointment) {
    // Prefill form values
    const a = props.appointment
    const dt = a.appointment_date ? new Date(a.appointment_date) : (a.start ? new Date(a.start) : null)
    const date = dt && !isNaN(dt.getTime()) ? dt.toISOString().split('T')[0] : ''
    const time = dt && !isNaN(dt.getTime()) ? dt.toTimeString().slice(0,5) : ''
  form.reset()
    form.set({
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
    })
    // Load available slots for prefilled doctor/date
    if (form.doctor_id && form.appointment_date) {
      loadSlots()
    }
    // set edit mode automatically for patients if backend marked can_edit
    if (props.appointment && typeof props.appointment.can_edit !== 'undefined') {
      editMode.value = !!props.appointment.can_edit && !isStaff.value
    } else {
      editMode.value = false
    }
  }
})

// Try to enter edit mode: check permission via provided flag or api
const enterEditMode = async () => {
  if (!props.appointment || !props.appointment.id) return
  // if backend already provided a can_edit flag, use it
  if (typeof props.appointment.can_edit !== 'undefined') {
    if (!props.appointment.can_edit) {
      alert('No está permitido editar esta cita (fecha pasada o permisos insuficientes).')
      return
    }
    editMode.value = true
    return
  }

  // fallback: request apiShow to check can_edit
  try {
    const res = await axios.get(`/api/appointments/${props.appointment.id}`)
    const data = res.data || {}
    if (data.can_edit === false) {
      alert('No está permitido editar esta cita (fecha pasada o permisos insuficientes).')
      return
    }
    // merge any fresh appointment data
    if (data.appointment) {
      // update props.appointment in-place isn't allowed; instead prefill form with returned data
      const a = data.appointment
      const dt = a.appointment_date ? new Date(a.appointment_date) : (a.start ? new Date(a.start) : null)
      const date = dt && !isNaN(dt.getTime()) ? dt.toISOString().split('T')[0] : ''
      const time = dt && !isNaN(dt.getTime()) ? dt.toTimeString().slice(0,5) : ''
      form.set({
        doctor_id: a.doctor_id || a.doctor?.id || form.doctor_id,
        specialty_id: a.specialty_id || a.specialty?.id || form.specialty_id,
        appointment_date: date || form.appointment_date,
        appointment_time: time || form.appointment_time,
        duration: a.duration || form.duration,
        reason: a.reason || form.reason,
        reason_id: a.reason_id || form.reason_id,
        notes: a.notes || form.notes,
        status: a.status || form.status
      })
      if (form.doctor_id && form.appointment_date) loadSlots()
    }
    editMode.value = true
  } catch (e) {
    alert('No se pudo verificar permisos de edición. Intente nuevamente.')
  }
}

const onSpecialtyOrDoctorChange = () => {
  // Clear selected time when changing doctor/specialty
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
  // request available slots from backend; include editing_appointment_id to exclude current appointment
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

  // Validar localmente: la nueva fecha/hora debe ser futura
  if (!isFutureAppointment()) {
    alert('La fecha y hora deben ser futuras. No es posible reasignar a una fecha pasada.');
    return
  }

  submitting.value = true
  const payload = {
    doctor_id: form.doctor_id,
    specialty_id: form.specialty_id,
    appointment_date: `${form.appointment_date} ${form.appointment_time || '00:00'}`,
    duration: form.duration,
    reason: form.reason,
    reason_id: form.reason_id,
    notes: form.notes,
    status: form.status,
  }

  try {
    await form.put(`/appointments/${props.appointment.id}`, { data: payload })
    emit('saved')
    emit('close')
  } catch (e) {
    // Errors handled by Inertia form
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
/* estilos mínimos */
</style>
