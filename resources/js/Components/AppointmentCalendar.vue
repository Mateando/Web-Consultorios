<template>
    <div id="calendar" ref="calendarEl"></div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { Calendar } from '@fullcalendar/core'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'

const props = defineProps({
    appointments: {
        type: Array,
        default: () => []
    },
    userPermissions: {
        type: Object,
        default: () => ({})
    },
    filteredSpecialtyId: {
        type: [String, Number],
        default: null
    },
    availableDays: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['event-click', 'date-click'])

const calendarEl = ref(null)
let calendar = null

const isDateAvailable = (dateInfo) => {
    if (!props.filteredSpecialtyId) {
        return true
    }
    
    const date = new Date(dateInfo.date || dateInfo.dateStr || dateInfo)
    const dayOfWeek = date.getDay()
    
    return props.availableDays.includes(dayOfWeek)
}

const initCalendar = () => {
    if (calendarEl.value && !calendar) {
        console.log('🗓️ Inicializando calendario con appointments:', props.appointments)
        console.log('📊 Cantidad de appointments:', props.appointments?.length || 0)
        
        calendar = new Calendar(calendarEl.value, {
            plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
            initialView: 'dayGridMonth',
            locale: 'es',
            firstDay: 1,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            height: 'auto',
            events: props.appointments.map(appointment => {
                // Si el appointment ya viene formateado desde el backend (con start, title, etc.)
                if (appointment.start && appointment.title) {
                    console.log('✅ Evento formateado:', appointment.title, 'en', appointment.start)
                    return {
                        id: appointment.id,
                        title: appointment.title,
                        start: appointment.start,
                        end: appointment.end,
                        color: appointment.backgroundColor || getAppointmentColor(appointment.extendedProps?.status),
                        extendedProps: appointment.extendedProps || {}
                    }
                }
                // Si viene en formato raw (para compatibilidad)
                console.log('🔄 Evento raw:', appointment.patient_name, '-', appointment.doctor_name)
                return {
                    id: appointment.id,
                    title: `${appointment.patient_name || 'Paciente'} - ${appointment.doctor_name || 'Doctor'}`,
                    start: appointment.scheduled_at,
                    color: getAppointmentColor(appointment.status),
                    extendedProps: {
                        status: appointment.status,
                        patient_name: appointment.patient_name,
                        doctor_name: appointment.doctor_name,
                        specialty_name: appointment.specialty_name
                    }
                }
            }),
            dateClick: (info) => {
                if (!isDateAvailable(info)) {
                    return false
                }
                if (!props.userPermissions?.can_create_appointments) {
                    return false
                }
                emit('date-click', info.dateStr)
            },
            eventClick: (info) => {
                emit('event-click', {
                    event: info.event,
                    jsEvent: info.jsEvent,
                    view: info.view
                })
            },
            dayCellClassNames: (info) => {
                if (!isDateAvailable(info)) {
                    return ['blocked-day']
                }
                return []
            }
        })
        
        calendar.render()
    }
}

// Función para asignar colores según el estado de la cita
const getAppointmentColor = (status) => {
    const colors = {
        'programada': '#3b82f6',    // Azul
        'confirmada': '#10b981',    // Verde
        'en_curso': '#f59e0b',      // Amarillo
        'completada': '#6b7280',    // Gris
        'cancelada': '#ef4444'      // Rojo
    }
    return colors[status] || '#3b82f6'
}

watch([() => props.filteredSpecialtyId, () => props.availableDays], () => {
    if (calendar) {
        calendar.destroy()
        calendar = null
        setTimeout(() => {
            initCalendar()
            // Asegurar que las citas se muestren después de recrear el calendario
            if (props.appointments.length > 0) {
                setTimeout(() => {
                    updateCalendarEvents()
                }, 100)
            }
        }, 100)
    }
}, { deep: true })

// Función para actualizar eventos del calendario
const updateCalendarEvents = () => {
    if (calendar && props.appointments) {
        calendar.removeAllEvents()
        const events = props.appointments.map(appointment => {
            // Si el appointment ya viene formateado desde el backend
            if (appointment.start && appointment.title) {
                return {
                    id: appointment.id,
                    title: appointment.title,
                    start: appointment.start,
                    end: appointment.end,
                    color: appointment.backgroundColor || getAppointmentColor(appointment.extendedProps?.status),
                    extendedProps: appointment.extendedProps || {}
                }
            }
            // Si viene en formato raw (para compatibilidad)
            return {
                id: appointment.id,
                title: `${appointment.patient_name || 'Paciente'} - ${appointment.doctor_name || 'Doctor'}`,
                start: appointment.scheduled_at,
                color: getAppointmentColor(appointment.status),
                extendedProps: {
                    status: appointment.status,
                    patient_name: appointment.patient_name,
                    doctor_name: appointment.doctor_name,
                    specialty_name: appointment.specialty_name
                }
            }
        })
        calendar.addEventSource(events)
    }
}

watch(() => props.appointments, (newAppointments) => {
    updateCalendarEvents()
}, { deep: true })

onMounted(() => {
    initCalendar()
})
</script>

<style scoped>
#calendar {
    max-width: 100%;
}

:deep(.blocked-day) {
    background: repeating-linear-gradient(
        45deg,
        #f8f9fa,
        #f8f9fa 10px,
        #e9ecef 10px,
        #e9ecef 20px
    ) !important;
    opacity: 0.5 !important;
    cursor: not-allowed !important;
}

/* Estilos para las citas */
:deep(.fc-event) {
    border-radius: 4px;
    font-size: 12px;
    padding: 2px 4px;
    margin: 1px 0;
}

:deep(.fc-event-title) {
    font-weight: 500;
}

/* Estilos específicos por estado */
:deep(.fc-event[style*="rgb(59, 130, 246)"]) {
    /* Programada - Azul */
    background-color: #3b82f6 !important;
    border-color: #2563eb !important;
}

:deep(.fc-event[style*="rgb(16, 185, 129)"]) {
    /* Confirmada - Verde */
    background-color: #10b981 !important;
    border-color: #059669 !important;
}

:deep(.fc-event[style*="rgb(245, 158, 11)"]) {
    /* En curso - Amarillo */
    background-color: #f59e0b !important;
    border-color: #d97706 !important;
    color: #1f2937 !important;
}

:deep(.fc-event[style*="rgb(107, 114, 128)"]) {
    /* Completada - Gris */
    background-color: #6b7280 !important;
    border-color: #4b5563 !important;
}

:deep(.fc-event[style*="rgb(239, 68, 68)"]) {
    /* Cancelada - Rojo */
    background-color: #ef4444 !important;
    border-color: #dc2626 !important;
}
</style>
