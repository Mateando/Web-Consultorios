<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="$emit('close')"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
        <form @submit.prevent="submit">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">Editar Cita</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Paciente</label>
                    <input type="text" :value="patientDisplayName" disabled class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-700 shadow-sm" />
                    <input type="hidden" v-model="form.patient_id" />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Doctor</label>
                    <select v-model="form.doctor_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                      <option value="">Seleccionar doctor</option>
                      <option v-for="d in doctorsList" :key="d.id" :value="d.id">{{ d.user?.name || d.name }}</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Especialidad</label>
                    <input type="text" :value="specialtyName" disabled class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-700 shadow-sm" />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Fecha</label>
                    <input type="date" v-model="form.appointment_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Hora</label>
                    <template v-if="loadingSlots">
                      <div class="mt-1 text-sm text-gray-500">Cargando horarios...</div>
                    </template>
                    <template v-else-if="availableSlots.length">
                      <select v-model="form.appointment_time" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">Seleccionar hora</option>
                        <option v-for="s in availableSlots" :key="s" :value="s">{{ s }}</option>
                      </select>
                      <p v-if="slotError" class="text-sm text-red-600 mt-1">{{ slotError }}</p>
                    </template>
                    <template v-else>
                      <input type="time" v-model="form.appointment_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                      <p class="text-sm text-gray-500 mt-1">No se han encontrado slots automáticos — puedes ingresar la hora manualmente.</p>
                    </template>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <select v-model="form.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                      <option value="programada">Programada</option>
                      <option value="confirmada">Confirmada</option>
                      <option value="en_curso">En Curso</option>
                      <option value="completada">Completada</option>
                      <option value="cancelada">Cancelada</option>
                      <option value="no_asistio">No asistió</option>
                    </select>
                  </div>

                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Motivo</label>
                    <textarea v-model="form.reason" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    <label class="block text-sm font-medium text-gray-700 mt-3">Notas adicionales</label>
                    <textarea v-model="form.notes" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm mt-2" placeholder="Notas adicionales..."></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="submit" :disabled="processing" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
              {{ processing ? 'Guardando...' : 'Actualizar Cita' }}
            </button>
            <button type="button" @click="$emit('close')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  show: Boolean,
  appointment: Object,
  doctors: Array,
  specialties: Array,
})
const emit = defineEmits(['close','saved'])

const processing = ref(false)

const form = useForm({
  patient_id: '',
  doctor_id: '',
  appointment_date: '',
  appointment_time: '',
  status: 'programada',
  reason: '',
  notes: ''
})

const availableSlots = ref([])
const loadingSlots = ref(false)
const slotError = ref(null)

const fetchAvailableSlots = async () => {
  slotError.value = null
  availableSlots.value = []
  if (!form.doctor_id || !form.appointment_date) return
  loadingSlots.value = true
  try {
    const params = new URLSearchParams({ doctor_id: form.doctor_id, specialty_id: props.appointment?.specialty_id || props.appointment?.specialty?.id || '', date: form.appointment_date })
    const res = await fetch(`/api/appointments/available-slots?${params.toString()}`)
    if (!res.ok) {
      slotError.value = 'No se pudieron cargar los horarios disponibles.'
      return
    }
    const data = await res.json()
    availableSlots.value = data.slots || []
    if (!availableSlots.value.length) slotError.value = 'No hay horarios disponibles para la combinación seleccionada.'
  } catch (e) {
    slotError.value = 'Error al consultar horarios disponibles.'
  } finally {
    loadingSlots.value = false
  }
}

// Watch doctor_id and appointment_date to refresh slots
watch(() => form.doctor_id, () => { fetchAvailableSlots() })
watch(() => form.appointment_date, () => { fetchAvailableSlots() })

const doctorsList = computed(() => props.doctors || [])

const patientDisplayName = computed(() => {
  return props.appointment?.patient?.user?.name || props.appointment?.patient_name || 'N/A'
})

const specialtyName = computed(() => {
  return props.appointment?.specialty?.name || props.appointment?.specialty_name || ''
})

// Prefill when modal opens; if appointment lacks full relations, fetch via API route
watch(() => props.show, async (v) => {
  if (v && props.appointment) {
    let data = props.appointment
    if (!props.appointment.patient || !props.appointment.patient.user) {
      try {
        const resp = await fetch(`/api/appointments/${props.appointment.id}`)
        if (resp.ok) data = await resp.json()
      } catch (e) {
        // fallback to minimal data
      }
    }

    const dt = new Date(data.appointment_date)
    const date = isNaN(dt.getTime()) ? '' : dt.toISOString().split('T')[0]
    const time = isNaN(dt.getTime()) ? '' : dt.toTimeString().slice(0,5)
    form.reset()
    form.set({
      patient_id: data.patient?.id || data.patient_id || '',
      doctor_id: data.doctor_id || data.doctor?.id || '',
      appointment_date: date,
      appointment_time: time,
      status: data.status || 'programada',
      reason: data.reason || '',
      notes: data.notes || ''
    })
  }
})

const submit = () => {
  if (!props.appointment || !props.appointment.id) return
  processing.value = true
  const payload = {
    patient_id: form.patient_id,
    doctor_id: form.doctor_id,
    appointment_date: `${form.appointment_date} ${form.appointment_time || '00:00'}:00`,
    status: form.status,
    reason: form.reason,
    notes: form.notes
  }
  form.put(`/appointments/${props.appointment.id}`, {
    data: payload,
    onSuccess: () => {
      processing.value = false
      emit('saved')
      emit('close')
    },
    onError: () => { processing.value = false }
  })
}
</script>
