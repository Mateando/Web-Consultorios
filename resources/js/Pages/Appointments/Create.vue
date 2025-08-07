<template>
    <Head title="Crear Cita" />

    <AuthenticatedLayout>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submitForm">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Paciente -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Paciente *
                                    </label>
                                    <select
                                        v-model="form.patient_id"
                                        required
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    >
                                        <option value="">Seleccionar paciente</option>
                                        <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                                            {{ patient.user.name }}
                                        </option>
                                    </select>
                                    <div v-if="errors.patient_id" class="text-red-500 text-sm mt-1">{{ errors.patient_id }}</div>
                                </div>

                                <!-- Fecha -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Fecha *
                                    </label>
                                    <input
                                        type="date"
                                        v-model="form.date"
                                        @change="onDateChange"
                                        :min="today"
                                        required
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                    <div v-if="errors.appointment_date" class="text-red-500 text-sm mt-1">{{ errors.appointment_date }}</div>
                                </div>

                                <!-- Especialidad -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Especialidad
                                    </label>
                                    <select
                                        v-model="form.specialty_id"
                                        @change="onSpecialtyChange"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    >
                                        <option value="">Todas las especialidades</option>
                                        <option v-for="specialty in specialties" :key="specialty.id" :value="specialty.id">
                                            {{ specialty.name }}
                                        </option>
                                    </select>
                                    <div v-if="errors.specialty_id" class="text-red-500 text-sm mt-1">{{ errors.specialty_id }}</div>
                                </div>

                                <!-- Doctor -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Doctor *
                                    </label>
                                    <select
                                        v-model="form.doctor_id"
                                        @change="onDoctorChange"
                                        required
                                        :disabled="!form.date"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100"
                                    >
                                        <option value="">{{ form.date ? 'Seleccionar doctor' : 'Seleccione una fecha primero' }}</option>
                                        <option v-for="doctor in availableDoctors" :key="doctor.id" :value="doctor.id">
                                            {{ doctor.name }}
                                            <span v-if="doctor.schedule">
                                                ({{ doctor.schedule.start_time }} - {{ doctor.schedule.end_time }})
                                            </span>
                                        </option>
                                    </select>
                                    <div v-if="errors.doctor_id" class="text-red-500 text-sm mt-1">{{ errors.doctor_id }}</div>
                                    <div v-if="form.date && availableDoctors.length === 0" class="text-amber-600 text-sm mt-1">
                                        No hay doctores disponibles para la fecha y especialidad seleccionada
                                    </div>
                                </div>

                                <!-- Hora -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Hora Disponible *
                                    </label>
                                    <div v-if="loadingSlots" class="text-gray-500">
                                        Cargando horarios disponibles...
                                    </div>
                                    <div v-else-if="!form.doctor_id || !form.date" class="text-gray-500">
                                        Seleccione un doctor y fecha para ver horarios disponibles
                                    </div>
                                    <div v-else-if="availableSlots.length === 0" class="text-amber-600">
                                        No hay horarios disponibles para este doctor en la fecha seleccionada
                                    </div>
                                    <div v-else class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2">
                                        <button
                                            v-for="slot in availableSlots"
                                            :key="slot"
                                            type="button"
                                            @click="selectTimeSlot(slot)"
                                            :class="[
                                                'px-3 py-2 text-sm rounded-md border',
                                                form.time === slot 
                                                    ? 'bg-blue-500 text-white border-blue-500' 
                                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                            ]"
                                        >
                                            {{ slot }}
                                        </button>
                                    </div>
                                    <div v-if="errors.time" class="text-red-500 text-sm mt-1">{{ errors.time }}</div>
                                </div>

                                <!-- Duración -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Duración (minutos)
                                    </label>
                                    <input
                                        type="number"
                                        v-model="form.duration"
                                        :placeholder="scheduleDuration ? `Por defecto: ${scheduleDuration} min` : ''"
                                        min="15"
                                        max="240"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                    <div v-if="errors.duration" class="text-red-500 text-sm mt-1">{{ errors.duration }}</div>
                                </div>

                                <!-- Estado -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Estado
                                    </label>
                                    <select
                                        v-model="form.status"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    >
                                        <option value="programada">Programada</option>
                                        <option value="confirmada">Confirmada</option>
                                    </select>
                                    <div v-if="errors.status" class="text-red-500 text-sm mt-1">{{ errors.status }}</div>
                                </div>

                                <!-- Motivo de la consulta -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Motivo de la consulta
                                    </label>
                                    <textarea
                                        v-model="form.reason"
                                        rows="3"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Describa el motivo de la consulta..."
                                    ></textarea>
                                    <div v-if="errors.reason" class="text-red-500 text-sm mt-1">{{ errors.reason }}</div>
                                </div>

                                <!-- Notas adicionales -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Notas adicionales
                                    </label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="3"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Notas adicionales sobre la cita..."
                                    ></textarea>
                                    <div v-if="errors.notes" class="text-red-500 text-sm mt-1">{{ errors.notes }}</div>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="flex justify-end space-x-4 mt-8">
                                <Link
                                    :href="route('appointments.index')"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                                >
                                    Cancelar
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="processing || !form.patient_id || !form.doctor_id || !form.time"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                                >
                                    {{ processing ? 'Creando...' : 'Crear Cita' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

// Props
const props = defineProps({
    doctors: Array,
    patients: Array,
    specialties: Array,
});

// State
const availableDoctors = ref([]);
const availableSlots = ref([]);
const loadingSlots = ref(false);
const scheduleDuration = ref(null);
const processing = ref(false);
const errors = ref({});

// Form
const form = useForm({
    patient_id: '',
    doctor_id: '',
    specialty_id: '',
    date: '',
    time: '',
    duration: null,
    status: 'programada',
    reason: '',
    notes: '',
});

// Computed
const today = computed(() => {
    return new Date().toISOString().split('T')[0];
});

// Methods
const onDateChange = async () => {
    // Reset doctor and time when date changes
    form.doctor_id = '';
    form.time = '';
    availableSlots.value = [];
    
    if (form.date) {
        await loadAvailableDoctors();
    }
};

const onSpecialtyChange = async () => {
    // Reset doctor and time when specialty changes
    form.doctor_id = '';
    form.time = '';
    availableSlots.value = [];
    
    if (form.date) {
        await loadAvailableDoctors();
    }
};

const onDoctorChange = async () => {
    form.time = '';
    availableSlots.value = [];
    scheduleDuration.value = null;
    
    if (form.doctor_id && form.date) {
        await loadAvailableSlots();
    }
};

const loadAvailableDoctors = async () => {
    try {
        const params = {
            date: form.date,
        };
        
        if (form.specialty_id) {
            params.specialty_id = form.specialty_id;
        }
        
        const response = await axios.get(route('doctors.by-specialty'), { params });
        availableDoctors.value = response.data.doctors;
    } catch (error) {
        console.error('Error loading doctors:', error);
        availableDoctors.value = [];
    }
};

const loadAvailableSlots = async () => {
    loadingSlots.value = true;
    try {
        const response = await axios.get(route('appointments.available-slots'), {
            params: {
                doctor_id: form.doctor_id,
                date: form.date,
            }
        });
        
        availableSlots.value = response.data.slots;
        scheduleDuration.value = response.data.duration;
        
        // Si no hay duración personalizada, usar la del horario
        if (!form.duration && scheduleDuration.value) {
            form.duration = scheduleDuration.value;
        }
    } catch (error) {
        console.error('Error loading time slots:', error);
        availableSlots.value = [];
    } finally {
        loadingSlots.value = false;
    }
};

const selectTimeSlot = (slot) => {
    form.time = slot;
};

const submitForm = () => {
    processing.value = true;
    
    // Combinar fecha y hora
    const appointmentDateTime = `${form.date} ${form.time}:00`;
    
    const formData = {
        patient_id: form.patient_id,
        doctor_id: form.doctor_id,
        specialty_id: form.specialty_id || null,
        appointment_date: appointmentDateTime,
        duration: form.duration,
        status: form.status,
        reason: form.reason,
        notes: form.notes,
    };
    
    useForm(formData).post(route('appointments.store'), {
        onSuccess: () => {
            processing.value = false;
        },
        onError: (formErrors) => {
            errors.value = formErrors;
            processing.value = false;
        }
    });
};
</script>
