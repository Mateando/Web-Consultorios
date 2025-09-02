<template>
    <div id="calendar" ref="calendarEl"></div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { getStatusColor } from '@/colors/appointmentColors.js'
import axios from 'axios'
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
const holidaysMap = ref({}) // key: 'YYYY-MM-DD' => { name, is_recurring }

const pad = (n) => String(n).padStart(2, '0')
const dateKeyFromDate = (d) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`
const dateKeyFromYmd = (ymd) => {
    if (!ymd) return ''
    const parts = String(ymd).split('-')
    if (parts.length < 3) return ymd
    return `${parts[0]}-${pad(parts[1])}-${pad(parts[2])}`
}

const isDateAvailable = (dateInfo) => {
    if (!props.filteredSpecialtyId) {
        return true
    }
    
    const date = new Date(dateInfo.date || dateInfo.dateStr || dateInfo)
    const dayOfWeek = date.getDay()
    
    return props.availableDays.includes(dayOfWeek)
}

// Helper: fetch holidays between two dates and populate holidaysMap
const fetchHolidaysForRange = async (from, to) => {
    try {
        const resp = await axios.get(route('api.config.holidays'), {
            params: { from, to }
        })
        const items = resp.data || []
        const map = {}

        // Build list of dates in the visible range to support recurring holidays
        const start = new Date(from)
        const end = new Date(to)
        const days = []
        for (let cur = new Date(start); cur <= end; cur.setDate(cur.getDate() + 1)) {
            days.push(new Date(cur))
        }

        items.forEach(h => {
            if (!h || !h.date) return
            // h.date is expected as 'YYYY-MM-DD' from the API
            if (h.is_recurring) {
                // Parse month/day from the provided date string without timezone effects
                const parts = String(h.date).split('-')
                const hy = parseInt(parts[0], 10)
                const hm = parseInt(parts[1], 10)
                const hd = parseInt(parts[2], 10)
                days.forEach(d => {
                    if ((d.getMonth() + 1) === hm && d.getDate() === hd) {
                        const key = dateKeyFromDate(d)
                        map[key] = { name: h.name, is_recurring: true }
                    }
                })
            } else {
                // Non-recurring: use the exact YMD string as key (normalized)
                const key = dateKeyFromYmd(h.date)
                map[key] = { name: h.name, is_recurring: false }
            }
        })

        holidaysMap.value = map
        // force calendar redraw of day cells
        if (calendar) calendar.render()
    } catch (e) {
        console.error('Error fetching holidays:', e)
    }
}

const initCalendar = () => {
    if (calendarEl.value && !calendar) {
    // Debug logs removed: avoid noisy console output in production
        
        calendar = new Calendar(calendarEl.value, {
            plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
            initialView: 'dayGridMonth',
            // Render events as full blocks so the whole entry gets the event color
            eventDisplay: 'block',
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
            // Called when the visible date range changes (e.g., user navigates month)
            datesSet: (info) => {
                const from = info.startStr
                const to = info.endStr
                fetchHolidaysForRange(from, to)
            },
            dayCellDidMount: (info) => {
                const dateStr = dateKeyFromDate(info.date)
                const holiday = holidaysMap.value[dateStr]
                if (holiday) {
                    // add title tooltip
                    info.el.title = `Feriado: ${holiday.name}`
                    info.el.classList.add('holiday-day')
                }
            },
            dayCellClassNames: (info) => {
                const dateStr = dateKeyFromDate(info.date)
                if (holidaysMap.value[dateStr]) {
                    return ['blocked-day', 'holiday-day']
                }
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
    const c = getStatusColor(status)
    return c.bg || '#3b82f6'
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

/* DayGrid (month) specific: make events render as colored blocks instead of a small dot + text */
:deep(.fc-daygrid-event) {
    display: block !important;
}

:deep(.fc-daygrid-event .fc-event-main) {
    display: block !important;
    padding: 3px 6px !important;
    border-radius: 6px !important;
    background-color: inherit !important;
    border-color: inherit !important;
    color: inherit !important;
}

:deep(.fc-event-dot) {
    display: none !important; /* hide the small colored dot */
}
</style>
