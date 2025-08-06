<template>
    <Head title="Reportes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Reportes
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Filtros de fecha -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtros</h3>
                        <form @submit.prevent="filterReports" class="flex flex-col sm:flex-row gap-4">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">
                                    Fecha Inicio
                                </label>
                                <input
                                    id="start_date"
                                    v-model="dateFilters.start_date"
                                    type="date"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">
                                    Fecha Fin
                                </label>
                                <input
                                    id="end_date"
                                    v-model="dateFilters.end_date"
                                    type="date"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                            <div class="flex items-end">
                                <button
                                    type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Reporte de citas por estado -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Citas por Estado</h3>
                            <div class="space-y-3">
                                <div v-for="appointment in appointments_by_status" :key="appointment.status" 
                                     class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <span class="font-medium capitalize">{{ getStatusLabel(appointment.status) }}</span>
                                    <span class="text-2xl font-bold text-blue-600">{{ appointment.count }}</span>
                                </div>
                                <div v-if="!appointments_by_status || appointments_by_status.length === 0" 
                                     class="text-center text-gray-500 py-4">
                                    No hay datos disponibles
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de ingresos por doctor -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Ingresos por Doctor</h3>
                            <div class="space-y-3">
                                <div v-for="doctor in revenue_by_doctor" :key="doctor.id" 
                                     class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <div class="font-medium">{{ doctor.user?.name || 'N/A' }}</div>
                                        <div class="text-sm text-gray-500">{{ doctor.specialty?.name || 'Sin especialidad' }}</div>
                                    </div>
                                    <span class="text-lg font-bold text-green-600">
                                        ${{ formatCurrency(doctor.appointments_sum_consultation_fee || 0) }}
                                    </span>
                                </div>
                                <div v-if="!revenue_by_doctor || revenue_by_doctor.length === 0" 
                                     class="text-center text-gray-500 py-4">
                                    No hay datos disponibles
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas adicionales -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Resumen del Período</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-blue-600">
                                    {{ getTotalAppointments() }}
                                </div>
                                <div class="text-sm text-gray-500">Total Citas</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-green-600">
                                    ${{ formatCurrency(getTotalRevenue()) }}
                                </div>
                                <div class="text-sm text-gray-500">Ingresos Totales</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-purple-600">
                                    {{ getActiveDoctors() }}
                                </div>
                                <div class="text-sm text-gray-500">Doctores Activos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    appointments_by_status: Array,
    revenue_by_doctor: Array,
    filters: Object
})

// Filtros de fecha
const dateFilters = reactive({
    start_date: props.filters?.startDate ? formatDateForInput(props.filters.startDate) : '',
    end_date: props.filters?.endDate ? formatDateForInput(props.filters.endDate) : ''
})

const filterReports = () => {
    router.get('/admin/reports', {
        start_date: dateFilters.start_date,
        end_date: dateFilters.end_date
    }, {
        preserveState: true,
        preserveScroll: true
    })
}

const getStatusLabel = (status) => {
    const labels = {
        'programada': 'Programada',
        'confirmada': 'Confirmada',
        'completada': 'Completada',
        'cancelada': 'Cancelada',
        'no_asistio': 'No Asistió'
    }
    return labels[status] || status
}

const formatCurrency = (amount) => {
    if (!amount) return '0.00'
    return new Intl.NumberFormat('es-CO', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount)
}

const formatDateForInput = (dateString) => {
    if (!dateString) return ''
    return new Date(dateString).toISOString().split('T')[0]
}

// Cálculos del resumen
const getTotalAppointments = () => {
    if (!props.appointments_by_status) return 0
    return props.appointments_by_status.reduce((total, item) => total + item.count, 0)
}

const getTotalRevenue = () => {
    if (!props.revenue_by_doctor) return 0
    return props.revenue_by_doctor.reduce((total, doctor) => {
        return total + (doctor.appointments_sum_consultation_fee || 0)
    }, 0)
}

const getActiveDoctors = () => {
    if (!props.revenue_by_doctor) return 0
    return props.revenue_by_doctor.filter(doctor => 
        (doctor.appointments_sum_consultation_fee || 0) > 0
    ).length
}
</script>
