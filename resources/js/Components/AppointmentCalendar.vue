<template>
    <div id="calendar" ref="calendarEl"></div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import { Calendar } from '@fullcalendar/core'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'

const props = defineProps({
    appointments: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['event-click', 'date-click'])

const calendarEl = ref(null)
let calendar = null

const initCalendar = () => {
    if (calendarEl.value && !calendar) {
        calendar = new Calendar(calendarEl.value, {
            plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            height: 'auto',
            events: props.appointments,
            eventClick: (info) => {
                // Si el evento tiene appointment en extendedProps, usarlo
                const appointment = info.event.extendedProps.appointment || info.event.extendedProps
                emit('event-click', appointment)
            },
            dateClick: (info) => {
                emit('date-click', info.dateStr)
            },
            eventDidMount: (info) => {
                // Personalizar la apariencia del evento
                const status = info.event.extendedProps.status
                const element = info.el
                
                // Aplicar clases CSS según el estado
                element.classList.add('cursor-pointer')
                
                switch (status) {
                    case 'programada':
                        element.style.backgroundColor = '#fbbf24'
                        element.style.borderColor = '#f59e0b'
                        break
                    case 'confirmada':
                        element.style.backgroundColor = '#3b82f6'
                        element.style.borderColor = '#2563eb'
                        break
                    case 'en_curso':
                        element.style.backgroundColor = '#f97316'
                        element.style.borderColor = '#ea580c'
                        break
                    case 'completada':
                        element.style.backgroundColor = '#10b981'
                        element.style.borderColor = '#059669'
                        break
                    case 'cancelada':
                        element.style.backgroundColor = '#ef4444'
                        element.style.borderColor = '#dc2626'
                        break
                    case 'no_asistio':
                        element.style.backgroundColor = '#6b7280'
                        element.style.borderColor = '#4b5563'
                        break
                    default:
                        element.style.backgroundColor = '#9ca3af'
                        element.style.borderColor = '#6b7280'
                }
                
                // Agregar tooltip
                element.title = `${info.event.title}\nEstado: ${getStatusText(status)}\nDoctor: ${info.event.extendedProps.doctor}\nPaciente: ${info.event.extendedProps.patient}`
            },
            businessHours: {
                daysOfWeek: [1, 2, 3, 4, 5], // Lunes a viernes
                startTime: '08:00',
                endTime: '18:00',
            },
            slotMinTime: '07:00:00',
            slotMaxTime: '20:00:00',
            nowIndicator: true,
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true,
            weekends: true,
            buttonText: {
                today: 'Hoy',
                month: 'Mes',
                week: 'Semana',
                day: 'Día',
                prev: 'Anterior',
                next: 'Siguiente'
            }
        })
        
        calendar.render()
    }
}

const getStatusText = (status) => {
    const texts = {
        'programada': 'Programada',
        'confirmada': 'Confirmada',
        'en_curso': 'En Curso',
        'completada': 'Completada',
        'cancelada': 'Cancelada',
        'no_asistio': 'No Asistió',
    }
    return texts[status] || status
}

// Actualizar eventos cuando cambien las props
watch(() => props.appointments, (newAppointments) => {
    if (calendar) {
        calendar.removeAllEvents()
        calendar.addEventSource(newAppointments)
    }
}, { deep: true })

onMounted(() => {
    nextTick(() => {
        initCalendar()
    })
})
</script>

<style scoped>
/* Estilos para FullCalendar */
:deep(.fc) {
    font-family: 'Inter', sans-serif;
}

:deep(.fc-button) {
    background-color: #3b82f6;
    border-color: #3b82f6;
}

:deep(.fc-button:hover) {
    background-color: #2563eb;
    border-color: #2563eb;
}

:deep(.fc-button:disabled) {
    background-color: #9ca3af;
    border-color: #9ca3af;
}

:deep(.fc-today-button) {
    background-color: #10b981;
    border-color: #10b981;
}

:deep(.fc-today-button:hover) {
    background-color: #059669;
    border-color: #059669;
}

:deep(.fc-daygrid-day-top) {
    justify-content: center;
}

:deep(.fc-event) {
    cursor: pointer;
    border-radius: 4px;
    font-size: 12px;
    padding: 2px 4px;
}

:deep(.fc-event:hover) {
    opacity: 0.8;
}

:deep(.fc-day-today) {
    background-color: rgba(59, 130, 246, 0.1) !important;
}

:deep(.fc-timegrid-slot) {
    height: 3em;
}

:deep(.fc-timegrid-event) {
    border-radius: 3px;
    padding: 1px 3px;
}

:deep(.fc-header-toolbar) {
    margin-bottom: 1.5em;
    padding: 0 0.5em;
}

:deep(.fc-toolbar-title) {
    font-size: 1.5em;
    font-weight: 600;
    color: #1f2937;
}

:deep(.fc-col-header-cell) {
    background-color: #f9fafb;
    border-color: #e5e7eb;
    font-weight: 600;
    color: #374151;
}

:deep(.fc-scrollgrid) {
    border-color: #e5e7eb;
}

:deep(.fc-scrollgrid td) {
    border-color: #e5e7eb;
}

:deep(.fc-daygrid-day-number) {
    color: #374151;
    font-weight: 500;
}

:deep(.fc-non-business) {
    background-color: #f3f4f6;
}
</style>
