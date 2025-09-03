<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="fixed inset-0 bg-black opacity-50" @click="$emit('close')"></div>
    <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full z-10">
      <div class="px-6 py-4 border-b flex items-center justify-between">
        <h3 class="text-lg font-medium">Detalle de la cita</h3>
        <button class="text-gray-600 hover:text-gray-900" @click="$emit('close')">✕</button>
      </div>

      <div class="p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-gray-500">Paciente</p>
            <p class="text-base text-gray-900">
              {{
                // Priorizar objeto appointment completo si vino en extendedProps
                appointment.patient?.user?.name
                || appointment.extendedProps?.appointment?.patient?.user?.name
                || appointment.extendedProps?.patient
                || appointment.extendedProps?.patient_name
                || 'N/A'
              }}
            </p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Doctor</p>
            <p class="text-base text-gray-900">
              {{
                appointment.doctor?.user?.name
                || appointment.extendedProps?.appointment?.doctor?.user?.name
                || appointment.extendedProps?.doctor
                || appointment.extendedProps?.doctor_name
                || 'N/A'
              }}
            </p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Especialidad</p>
            <p class="text-base text-gray-900">{{ appointment.specialty?.name || appointment.extendedProps?.specialty_name || 'General' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Fecha y hora</p>
            <p class="text-base text-gray-900">{{ formatDate(appointment.appointment_date || appointment.start || appointment.extendedProps?.start) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Estado</p>
            <p class="text-base">
              <span :style="statusBadgeStyle" class="px-2 inline-flex items-center text-xs leading-5 font-semibold rounded-full">
                <!-- Iconos pequeños para consistencia visual -->
                <template v-if="statusKey === 'programada'">
                  <StatusProgramadaIcon />
                </template>
                <template v-else-if="statusKey === 'confirmada'">
                  <StatusConfirmadaIcon />
                </template>
                <template v-else-if="statusKey === 'en_curso'">
                  <StatusEnCursoIcon />
                </template>
                <template v-else-if="statusKey === 'completada'">
                  <StatusCompletadaIcon />
                </template>
                <template v-else-if="statusKey === 'cancelada'">
                  <StatusCanceladaIcon />
                </template>
                <template v-else-if="statusKey === 'no_asistio'">
                  <StatusNoAsistioIcon />
                </template>
                {{ statusText }}
              </span>
            </p>
          </div>
        </div>

        <div>
          <p class="text-sm text-gray-500">Motivo</p>
          <p class="text-base text-gray-900">{{ reasonText }}</p>
        </div>

        <div v-if="notesText">
          <p class="text-sm text-gray-500">Notas</p>
          <p class="text-base text-gray-900" style="white-space:pre-wrap">{{ notesText }}</p>
        </div>

        <div class="flex items-center space-x-3 pt-4">
          <PrimaryButton v-if="canEdit" @click="$emit('edit', appointment)">Editar</PrimaryButton>

          <!-- Botón WhatsApp: si hay teléfono habilitar, si no mostrar deshabilitado -->
          <template v-if="hasWhatsapp">
            <button @click="handleWhatsappButton" type="button" class="inline-flex items-center rounded-md border border-green-300 bg-white px-4 py-1 text-xs font-semibold uppercase tracking-widest text-green-600 shadow-sm transition duration-150 ease-in-out hover:bg-green-50">
              <WhatsAppIcon class="mr-1 text-green-600" />
              WhatsApp
            </button>
          </template>
          <template v-else>
            <span class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-1 text-xs font-semibold uppercase tracking-widest text-gray-400 shadow-sm transition duration-150 ease-in-out cursor-not-allowed opacity-50" title="Paciente sin teléfono">
              <WhatsAppIcon class="mr-1 text-gray-400" />
              WhatsApp
            </span>
          </template>

          <SecondaryButton @click="$emit('print', appointment)">
            <span class="inline-flex items-center gap-2">
              <PrinterIcon class="h-4 w-4 text-gray-600" :title="'Imprimir'" />
              <span class="sr-only">Imprimir</span>
            </span>
          </SecondaryButton>
          <PrimaryButton v-if="canDelete" class="bg-red-600 hover:bg-red-700" @click="$emit('delete', appointment)">Eliminar</PrimaryButton>
          <SecondaryButton class="ml-auto" @click="$emit('close')">Cerrar</SecondaryButton>
        </div>
      </div>
        </div>
      </div>

      <Modal :show="showWhatsappConfirm" @close="showWhatsappConfirm = false" maxWidth="sm">
        <template #default>
          <div class="px-6 py-4">
            <div class="text-lg font-medium mb-2">Enviar WhatsApp</div>
            <div class="text-sm text-gray-600 mb-4">Se abrirá WhatsApp en una nueva pestaña para contactar al paciente. ¿Deseas continuar?</div>
            <div class="flex justify-end space-x-2">
              <SecondaryButton @click="showWhatsappConfirm = false">Cancelar</SecondaryButton>
              <PrimaryButton @click="confirmAndSendWhatsapp">Continuar</PrimaryButton>
            </div>
          </div>
        </template>
      </Modal>

    </template>

    <script setup>
    import { computed, ref } from 'vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import PrinterIcon from '@/Components/icons/PrinterIcon.vue'
import WhatsAppIcon from '@/Components/icons/WhatsAppIcon.vue'
import axios from 'axios'
import { toastSuccess, toastError } from '@/utils/swal'
import Modal from '@/Components/Modal.vue'
import StatusProgramadaIcon from '@/Components/icons/StatusProgramadaIcon.vue'
import StatusConfirmadaIcon from '@/Components/icons/StatusConfirmadaIcon.vue'
import StatusEnCursoIcon from '@/Components/icons/StatusEnCursoIcon.vue'
import StatusCompletadaIcon from '@/Components/icons/StatusCompletadaIcon.vue'
import StatusCanceladaIcon from '@/Components/icons/StatusCanceladaIcon.vue'
import StatusNoAsistioIcon from '@/Components/icons/StatusNoAsistioIcon.vue'
const props = defineProps({
  show: Boolean,
  appointment: Object,
  userPermissions: Object
})
const emit = defineEmits(['close','edit','print','delete'])

const canEdit = computed(() => {
  if (!props.userPermissions) return false
  if (props.userPermissions.is_patient) {
    // pacientes pueden editar según reglas de la página; aquí devolvemos true y el padre validará
    return true
  }
  return props.userPermissions.can_edit_appointments
})

const canDelete = computed(() => {
  return props.userPermissions?.can_delete_appointments
})

const formatDate = (d) => {
  if (!d) return '-'
  const dt = new Date(d)
  return dt.toLocaleString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
}

// Formatea teléfono para WhatsApp (quita todo excepto dígitos y +, luego quita + para la URL)
const formatPhone = (phone) => {
  if (!phone) return ''
  const cleaned = String(phone).replace(/[^+\d]/g, '')
  return cleaned.replace(/^\+/, '')
}

const hasWhatsapp = computed(() => {
  const phone = props.appointment?.patient?.user?.phone
    || props.appointment?.extendedProps?.appointment?.patient?.user?.phone
    || props.appointment?.extendedProps?.patient_phone
    || props.appointment?.extendedProps?.patient?.phone
    || ''
  return !!phone
})

const whatsappUrl = computed(() => {
  const phoneRaw = props.appointment?.patient?.user?.phone
    || props.appointment?.extendedProps?.appointment?.patient?.user?.phone
    || props.appointment?.extendedProps?.patient_phone
    || props.appointment?.extendedProps?.patient?.phone
    || ''
  const phone = formatPhone(phoneRaw)
  if (!phone) return '#'
  const date = new Date(props.appointment?.appointment_date || props.appointment?.start || props.appointment?.extendedProps?.start || '')
  const dateText = isNaN(date.getTime()) ? '' : date.toLocaleString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
  const patientName = props.appointment?.patient?.user?.name || props.appointment?.extendedProps?.appointment?.patient?.user?.name || props.appointment?.extendedProps?.patient || ''
  const doctorName = props.appointment?.doctor?.user?.name || props.appointment?.extendedProps?.appointment?.doctor?.user?.name || props.appointment?.extendedProps?.doctor || ''
  const specialty = props.appointment?.specialty?.name || props.appointment?.extendedProps?.specialty_name || 'General'
  const message = `Hola ${patientName}, te contactamos desde el consultorio para confirmar tu cita con ${doctorName} (${specialty}) ${dateText ? 'el ' + dateText : ''}. Por favor responde "CONFIRMO" si asistirás.`
  return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`
})

const statusText = computed(() => {
  const s = props.appointment?.status || props.appointment?.extendedProps?.status || ''
  const map = {
    'programada': 'Programada',
    'confirmada': 'Confirmada',
    'en_curso': 'En Curso',
    'completada': 'Completada',
    'cancelada': 'Cancelada',
    'no_asistio': 'No Asistió'
  }
  return map[s] || s || '-'
})

// Inline color mapping to match calendar hex colors exactly
const statusColorHex = computed(() => {
  const s = props.appointment?.status || props.appointment?.extendedProps?.status || ''
  const map = {
    'programada': '#3b82f6',    // Azul
    'confirmada': '#10b981',    // Verde
    'en_curso': '#f59e0b',      // Amarillo
    'completada': '#6b7280',    // Gris
    'cancelada': '#ef4444'      // Rojo
  }
  return map[s] || '#6b7280'
})

const statusBadgeStyle = computed(() => {
  const bg = statusColorHex.value
  // For yellow background use dark text; otherwise white
  const textColor = (bg.toLowerCase() === '#f59e0b') ? '#1f2937' : '#ffffff'
  return { backgroundColor: bg, color: textColor }
})

const statusKey = computed(() => {
  return props.appointment?.status || props.appointment?.extendedProps?.status || ''
})

const reasonText = computed(() => {
  // Prefer appointment.reason, then extendedProps.appointment.reason, then extendedProps.reason
  return props.appointment?.reason
    || props.appointment?.extendedProps?.appointment?.reason
    || props.appointment?.extendedProps?.reason
    || props.appointment?.extendedProps?.appointment?.reason_id && ''
    || '-' 
})

const notesText = computed(() => {
  return props.appointment?.notes
    || props.appointment?.extendedProps?.appointment?.notes
    || props.appointment?.extendedProps?.notes
    || ''
})

// Handler que registra el intento y luego abre la URL de WhatsApp
const onWhatsappClick = async () => {
  try {
    const phoneRaw = props.appointment?.patient?.user?.phone
      || props.appointment?.extendedProps?.appointment?.patient?.user?.phone
      || props.appointment?.extendedProps?.patient_phone
      || props.appointment?.extendedProps?.patient?.phone
      || ''
    const phone = formatPhone(phoneRaw)
    if (!phone) return

    const payload = {
      appointment_id: props.appointment?.id || props.appointment?.extendedProps?.appointment?.id || null,
      recipient_phone: phone,
      message: whatsappUrl.value.split('?text=')[1] ? decodeURIComponent(whatsappUrl.value.split('?text=')[1]) : null,
      meta: { from: 'appointment_modal' }
    }

    // Fire-and-forget audit; still open wa.me even if audit fails
    await axios.post(route('api.whatsapp.audits.store'), payload)
  } catch (e) {
    // ignore errors but could show toast
    // console.error('Audit failed', e)
  } finally {
    // abrir en nueva pestaña
    const w = window.open(whatsappUrl.value, '_blank', 'noopener')
    if (w) w.focus()
  }
}

// Estado para mostrar modal de confirmación
const showWhatsappConfirm = ref(false)

// Al pulsar el botón WhatsApp, mostrar confirmación
const handleWhatsappButton = () => {
  showWhatsappConfirm.value = true
}

// Función que se llama al confirmar: ejecuta onWhatsappClick y cierra el confirm modal
const confirmAndSendWhatsapp = async () => {
  showWhatsappConfirm.value = false
  try {
    await onWhatsappClick()
    toastSuccess('Registro de WhatsApp guardado y WhatsApp abierto.')
  } catch (e) {
    toastError('No fue posible registrar el envío de WhatsApp, se abrirá la app.')
  }
}
</script>
