<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
  <div class="inline-block align-bottom bg-white rounded-lg overflow-hidden text-left shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-2xl h-[72vh] sm:h-[72vh] max-h-[80vh]">
        <form @submit.prevent="submitForm" class="h-full flex flex-col">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 overflow-y-auto flex-1 min-h-0">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Nueva Cita (Por Médico)</h3>

            <!-- Paso 1: Médico (nuevo buscador) -->
            <div v-show="step===1" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Médico</label>
              <div class="mt-1 relative" ref="doctorSearchBox"
                   @keydown.down.prevent="focusNextDoctorGlobal()"
                   @keydown.up.prevent="focusPrevDoctorGlobal()"
                   @keydown.enter.prevent="selectFocusedDoctorGlobal()"
                   @keydown.esc.prevent="closeDoctorSearchDropdown()">
                <div class="flex rounded-md shadow-sm">
        <input type="text" v-model="doctorSearchQuery" placeholder="Buscar médico..."
          @focus="openDoctorSearchDropdown" @click="openDoctorSearchDropdown"
          class="flex-1 block w-full rounded-l-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" />
                  <button type="button" @click="toggleDoctorSearchDropdown" class="px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 hover:bg-gray-100 text-gray-600 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" /></svg>
                  </button>
                </div>
                <ul v-if="showDoctorSearchDropdown" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto ring-1 ring-black ring-opacity-5 divide-y divide-gray-100">
                  <li v-if="filteredAllDoctors.length===0" class="px-3 py-2 text-sm text-gray-500">Sin resultados</li>
          <li v-for="(d,i) in filteredAllDoctors" :key="d.id" @mousedown.prevent="pickDoctorGlobal(d)"
            :class="['px-3 py-2 text-sm cursor-pointer flex justify-between items-center', i===focusedDoctorGlobalIndex ? 'bg-blue-600 text-white' : 'hover:bg-gray-100 text-gray-700']">
                    <span class="truncate">{{ d.name || d.user?.name }}</span>
                    <svg v-if="String(form.doctor_id)===String(d.id)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" :class="i===focusedDoctorGlobalIndex ? 'text-white' : 'text-blue-600'">
                      <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 00-1.408-1.42l-6.93 6.866-2.66-2.63a1 1 0 00-1.404 1.424l3.364 3.326a1 1 0 001.404 0l7.634-7.566z" clip-rule="evenodd" />
                    </svg>
                  </li>
                </ul>
              </div>
              <p v-if="form.doctor_id" class="mt-1 text-xs text-green-600">Seleccionado: {{ doctorName }}</p>
              <p v-if="errors.doctor_id" class="mt-1 text-xs text-red-600">{{ errors.doctor_id }}</p>
            </div>

            <!-- Paso 2: Paciente (sólo se muestra tras seleccionar médico) -->
            <div v-show="step===2 && form.doctor_id" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Paciente</label>
              <div class="mt-1 relative">
                <input type="search" v-model="patientQuery" @input="onPatientQueryChange" placeholder="Buscar paciente..." class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm pr-10" />
                <div v-if="loadingPatients" class="absolute right-2 top-2 text-xs text-gray-500">...</div>
                <ul v-if="showPatientDropdown" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto ring-1 ring-black ring-opacity-5 divide-y divide-gray-100">
                  <li v-if="patientResults.length===0" class="px-3 py-2 text-sm text-gray-500">Sin resultados</li>
                  <li v-for="p in patientResults" :key="p.id" @mousedown.prevent="selectPatient(p)" class="px-3 py-2 text-sm cursor-pointer hover:bg-indigo-50">
                    <div class="font-medium text-gray-800">{{ p.name }}</div>
                    <div class="text-xs text-gray-500">{{ (p.document_type ? p.document_type.toUpperCase()+ ' ' : '') + (p.document_number||'') }}</div>
                  </li>
                </ul>
              </div>
              <p v-if="form.patient_id" class="mt-1 text-xs text-green-600">Seleccionado: {{ patientName }}</p>
              <p v-if="errors.patient_id" class="mt-1 text-xs text-red-600">{{ errors.patient_id }}</p>
            </div>

            <!-- Paso 3: Especialidad (opcional si >1) con combobox -->
            <div v-show="step===3 && needsSpecialty" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Especialidad</label>
              <div class="mt-1 relative" ref="specialtyBox"
                   @keydown.down.prevent="focusNextSpecialty()"
                   @keydown.up.prevent="focusPrevSpecialty()"
                   @keydown.enter.prevent="selectFocusedSpecialty()"
                   @keydown.esc.prevent="closeSpecialtyDropdown()">
                <div class="flex rounded-md shadow-sm">
                  <input type="text" v-model="specialtyQuery" placeholder="Buscar especialidad..."
                         @focus="openSpecialtyDropdown" @click="openSpecialtyDropdown"
                         class="flex-1 block w-full rounded-l-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                  <button type="button" @click="toggleSpecialtyDropdown" class="px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 hover:bg-gray-100 text-gray-600 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" /></svg>
                  </button>
                </div>
                <ul v-if="showSpecialtyDropdown" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-auto ring-1 ring-black ring-opacity-5">
                  <li v-if="filteredSpecialties.length===0" class="px-3 py-2 text-sm text-gray-500">Sin resultados</li>
          <li v-for="(s,i) in filteredSpecialties" :key="s.id"
            @mousedown.prevent="pickSpecialty(s)"
            :class="['px-3 py-2 text-sm cursor-pointer flex justify-between items-center', i===focusedSpecialtyIndex ? 'bg-blue-600 text-white' : 'hover:bg-gray-100 text-gray-700']">
                    <span>{{ s.name }}</span>
                    <svg v-if="form.specialty_id && String(form.specialty_id)===String(s.id)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" :class="i===focusedSpecialtyIndex ? 'text-white' : 'text-blue-600'">
                      <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 00-1.408-1.42l-6.93 6.866-2.66-2.63a1 1 0 00-1.404 1.424l3.364 3.326a1 1 0 001.404 0l7.634-7.566z" clip-rule="evenodd" />
                    </svg>
                  </li>
                </ul>
              </div>
              <p v-if="form.specialty_id" class="mt-1 text-xs text-green-600">Seleccionada: {{ specialtyName }}</p>
              <p v-if="errors.specialty_id" class="mt-1 text-xs text-red-600">{{ errors.specialty_id }}</p>
            </div>

            <!-- Paso 4: Estudio (si el doctor tiene) con combobox (opcional, por defecto 'Ninguno') -->
            <div v-show="step===studyStep && showStudySelect" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Tipo de estudio</label>
              <div class="mt-1 relative" ref="studyBox"
                   @keydown.down.prevent="focusNextStudy()"
                   @keydown.up.prevent="focusPrevStudy()"
                   @keydown.enter.prevent="selectFocusedStudy()"
                   @keydown.esc.prevent="closeStudyDropdown()">
                <div class="relative">
                  <input type="text" v-model="studyQuery" placeholder="Buscar / seleccionar..."
                         @focus="openStudyDropdown()" @click="openStudyDropdown()"
                         class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm pr-8" />
                  <button type="button" @click="toggleStudyDropdown" class="absolute inset-y-0 right-0 px-2 text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                  </button>
                </div>
                <input type="hidden" v-model="form.study_type_id" />
                <ul v-if="showStudyDropdown" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md max-h-60 overflow-auto shadow-lg ring-1 ring-black/5 text-left" @mousedown.stop>
                  <!-- Opción Ninguno -->
                  <li
                    :class="['px-3 py-2 text-sm cursor-pointer flex items-center justify-between', focusedStudyIndex===-2 ? 'bg-blue-50 text-blue-700' : 'hover:bg-gray-50']"
                    @mouseenter="focusedStudyIndex=-2"
                    @mouseleave="focusedStudyIndex=-1"
                    @click="pickStudyNone()">
                    <span class="truncate">Ninguno</span>
                    <svg v-if="!form.study_type_id" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3.5-3.5a1 1 0 011.414-1.414l2.793 2.793 6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                  </li>
                  <li v-if="filteredStudyTypes.length===0" class="px-3 py-2 text-sm text-gray-500 select-none">Sin resultados</li>
                  <li v-for="(st,idx) in filteredStudyTypes" :key="st.id"
                      :class="['px-3 py-2 text-sm cursor-pointer flex items-center justify-between', idx===focusedStudyIndex ? 'bg-blue-50 text-blue-700' : 'hover:bg-gray-50']"
                      @mouseenter="focusedStudyIndex=idx"
                      @mouseleave="focusedStudyIndex=-1"
                      @click="pickStudy(st)">
                    <span class="truncate">{{ st.name }}</span>
                    <svg v-if="String(form.study_type_id)===String(st.id)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3.5-3.5a1 1 0 011.414-1.414l2.793 2.793 6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                  </li>
                </ul>
              </div>
              <div v-if="errors.study_type_id" class="mt-1 text-sm text-red-600">{{ errors.study_type_id }}</div>
            </div>

            <!-- Paso Fecha/Hora -->
            <div v-show="step===dateStep" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Fecha</label>
              <input
                type="date"
                v-model="form.appointment_date"
                @change="onDateChange"
                :min="minDate"
                :disabled="form.specialty_id && (loadingDays || availableDays.length === 0)"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                :class="{ 'bg-gray-100 cursor-not-allowed': form.specialty_id && availableDays.length === 0 }"
              />
              <p v-if="errors.appointment_date" class="mt-1 text-xs text-red-600">{{ errors.appointment_date }}</p>
              <p v-if="availableDaysMessage" class="mt-1 text-xs text-blue-600">{{ availableDaysMessage }}</p>
              <p v-if="form.specialty_id && availableDays.length===0 && !loadingDays" class="mt-1 text-xs text-red-600 font-medium">⚠️ Esta especialidad no tiene horarios de atención configurados. No es posible crear citas.</p>
              <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Hora</label>
                <select v-model="form.appointment_time" :disabled="loadingSlots || !form.appointment_date || (needsSpecialty && !form.specialty_id)" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <option value="">{{ !form.appointment_date ? 'Seleccione una fecha' : (needsSpecialty && !form.specialty_id) ? 'Seleccione una especialidad' : loadingSlots ? 'Cargando horarios...' : availableSlots.length===0 ? 'Sin horarios disponibles' : 'Seleccionar hora' }}</option>
                  <option v-for="slot in availableSlots" :key="slot" :value="slot">{{ slot }}</option>
                </select>
                <p v-if="errors.appointment_time" class="mt-1 text-xs text-red-600">{{ errors.appointment_time }}</p>
                <p v-if="form.appointment_date && !loadingSlots && availableSlots.length===0" class="mt-1 text-xs text-yellow-600">No hay horarios disponibles para esta fecha.</p>
                <p v-if="form.appointment_date && !loadingSlots && availableSlots.length>0" class="mt-1 text-xs text-green-600">{{ availableSlots.length }} horarios disponibles</p>
              </div>
            </div>

            <!-- Paso Detalles -->
            <div v-show="step===detailsStep" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Motivo</label>
              <select v-model="form.reason_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">Seleccionar motivo</option>
                <option v-for="r in reasons" :key="r.id" :value="r.id">{{ r.name }}</option>
              </select>
              <label class="block text-sm font-medium text-gray-700 mt-4">Notas</label>
              <textarea v-model="form.notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
            </div>

            <!-- Paso Resumen -->
            <div v-show="step===summaryStep" class="mb-2 space-y-1 text-sm text-gray-700">
              <h4 class="font-semibold mb-2 text-gray-900">Resumen</h4>
              <p>Paciente: <strong>{{ patientName }}</strong></p>
              <p>Médico: <strong>{{ doctorName }}</strong></p>
              <p v-if="form.specialty_id">Especialidad: <strong>{{ specialtyName }}</strong></p>
              <p>Estudio: <strong>{{ studyDisplayName }}</strong></p>
              <p>Fecha: <strong>{{ form.appointment_date }}</strong> Hora: <strong>{{ form.appointment_time }}</strong></p>
              <p>Estado: <strong>{{ form.status }}</strong></p>
              <p v-if="reasonName">Motivo: <strong>{{ reasonName }}</strong></p>
              <p v-if="form.notes">Notas: <span class="text-gray-600">{{ form.notes }}</span></p>
              <div class="mt-3 flex items-center gap-2">
                <input id="print_after_doc" type="checkbox" v-model="printAfter" class="h-4 w-4" />
                <label for="print_after_doc" class="select-none">Imprimir al guardar</label>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse items-center border-t gap-2">
            <div class="sm:ml-3 sm:w-auto w-full">
              <PrimaryButton
                v-if="step < summaryStep"
                type="button"
                :disabled="nextDisabled"
                @click="nextStep"
                class="w-full sm:w-auto"
              >
                Siguiente
              </PrimaryButton>
              <PrimaryButton
                v-else
                type="submit"
                :disabled="processing || (form.specialty_id && availableDays.length === 0) || !canSubmit"
                class="w-full sm:w-auto"
              >
                {{ processing? 'Guardando...' : 'Crear' }}
              </PrimaryButton>
            </div>
            <div class="mt-3 sm:mt-0 sm:ml-3 sm:w-auto w-full flex gap-2 items-center">
              <SecondaryButton v-if="step>1" type="button" @click="prevStep" class="w-full sm:w-auto">Atrás</SecondaryButton>
              <SecondaryButton type="button" @click="$emit('close')" class="w-full sm:w-auto">Cancelar</SecondaryButton>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { usePage } from '@inertiajs/vue3'
import { buildAppointmentPrintContent } from '@/utils/printAppointment'

const props = defineProps({
  show: Boolean,
  patients: Array,
  doctors: Array,
  specialties: Array,
  selectedDate: String,
  initialDoctorId: [String, Number],
})
const emit = defineEmits(['close','saved'])

// State
const step = ref(1)
const processing = ref(false)
const errors = ref({})
const loadingSlots = ref(false)
const availableSlots = ref([])

// Form
const form = ref({
  patient_id: '',
  doctor_id: '',
  specialty_id: '',
  study_type_id: '',
  appointment_date: '',
  appointment_time: '',
  status: 'programada',
  reason_id: '',
  notes: '',
})

// Reasons
const reasons = ref([])
const loadReasons = async ()=>{ try { const r = await axios.get(route('api.config.appointment-reasons')); reasons.value = r.data||[] } catch { reasons.value=[] } }

// Patient search
const patientQuery = ref('')
const patientResults = ref([])
const showPatientDropdown = ref(false)
const loadingPatients = ref(false)
let debounceTimer=null
const onPatientQueryChange=()=>{ if(!patientQuery.value){ patientResults.value=[]; showPatientDropdown.value=false; return } if(debounceTimer) clearTimeout(debounceTimer); debounceTimer=setTimeout(()=>fetchPatients(patientQuery.value),300) }
const fetchPatients= async(q)=>{ loadingPatients.value=true; showPatientDropdown.value=true; try{ const r= await axios.get('/api/patients',{params:{q,limit:20}}); patientResults.value=r.data?.data||[] }catch{ patientResults.value=[] } finally{ loadingPatients.value=false } }
const selectPatient=(p)=>{ form.value.patient_id=p.id; patientQuery.value=p.name||''; showPatientDropdown.value=false }

// Doctor combobox global
const doctor = computed(()=> (props.doctors||[]).find(d=> String(d.id)===String(form.value.doctor_id)))
const doctorName = computed(()=> doctor.value ? (doctor.value.user?.name || doctor.value.name || '') : '')
const doctorSearchQuery = ref('')
const showDoctorSearchDropdown = ref(false)
const focusedDoctorGlobalIndex = ref(-1)
const doctorSearchBox = ref(null)
const filteredAllDoctors = computed(()=>{
  const list = props.doctors || []
  if(!doctorSearchQuery.value) return list
  const q = doctorSearchQuery.value.toLowerCase()
  return list.filter(d=> (d.name||d.user?.name||'').toLowerCase().includes(q) || (d.license_number||'').toLowerCase().includes(q))
})
const openDoctorSearchDropdown=()=>{ if(!showDoctorSearchDropdown.value){ showDoctorSearchDropdown.value=true; nextTick(()=>{ if(filteredAllDoctors.value.length>0) focusedDoctorGlobalIndex.value=0 }) } }
const closeDoctorSearchDropdown=()=>{ showDoctorSearchDropdown.value=false; focusedDoctorGlobalIndex.value=-1 }
const toggleDoctorSearchDropdown=()=>{ showDoctorSearchDropdown.value? closeDoctorSearchDropdown(): openDoctorSearchDropdown() }
watch(()=>doctorSearchQuery.value,()=>{ if(!showDoctorSearchDropdown.value) openDoctorSearchDropdown(); if(filteredAllDoctors.value.length>0) focusedDoctorGlobalIndex.value=0; else focusedDoctorGlobalIndex.value=-1 })
const focusNextDoctorGlobal=()=>{ if(!showDoctorSearchDropdown.value){ openDoctorSearchDropdown(); return } if(filteredAllDoctors.value.length===0) return; focusedDoctorGlobalIndex.value=(focusedDoctorGlobalIndex.value+1)%filteredAllDoctors.value.length }
const focusPrevDoctorGlobal=()=>{ if(!showDoctorSearchDropdown.value){ openDoctorSearchDropdown(); return } if(filteredAllDoctors.value.length===0) return; focusedDoctorGlobalIndex.value=(focusedDoctorGlobalIndex.value-1+filteredAllDoctors.value.length)%filteredAllDoctors.value.length }
const selectFocusedDoctorGlobal=()=>{ if(focusedDoctorGlobalIndex.value<0) return; const d=filteredAllDoctors.value[focusedDoctorGlobalIndex.value]; if(d) pickDoctorGlobal(d) }
const pickDoctorGlobal=(d)=>{ 
  form.value.doctor_id=d.id; 
  doctorSearchQuery.value=d.name || d.user?.name || ''; 
  // Reset dependientes
  form.value.study_type_id=''; 
  form.value.appointment_time=''; 
  availableSlots.value=[]; 
  
  // Determinar especialidad: si el médico tiene exactamente 1, autoseleccionarla
  const specs = d.specialties || []
  if (specs.length === 1) {
    const only = specs[0]
    form.value.specialty_id = only.id
    specialtyQuery.value = only.name
    loadAvailableDays(only.id)
  } else {
    // Requerirá selección manual
    form.value.specialty_id=''
    specialtyQuery.value=''
  }

  // Avanzar automáticamente al paso de paciente
  if(step.value===1) step.value=2
}
const handleClickOutsideDoctorGlobal=(e)=>{ if(!showDoctorSearchDropdown.value) return; const el=doctorSearchBox.value; if(el && !el.contains(e.target)) closeDoctorSearchDropdown() }
onMounted(()=> document.addEventListener('mousedown', handleClickOutsideDoctorGlobal))
onUnmounted(()=> document.removeEventListener('mousedown', handleClickOutsideDoctorGlobal))
const doctorSpecialties = computed(()=> doctor.value?.specialties || [])
const needsSpecialty = computed(()=> doctorSpecialties.value.length>1)

// Specialty names
const specialtyName = computed(()=> { const s=(props.specialties||[]).find(s=> String(s.id)===String(form.value.specialty_id)); return s? s.name:'' })

// --- Combobox Especialidad ---
const specialtyQuery = ref('')
const showSpecialtyDropdown = ref(false)
const focusedSpecialtyIndex = ref(-1)
const specialtyBox = ref(null)
const filteredSpecialties = computed(()=>{
  const list = doctorSpecialties.value || []
  if(!specialtyQuery.value) return list
  const q = specialtyQuery.value.toLowerCase()
  return list.filter(s=> s.name.toLowerCase().includes(q))
})
const openSpecialtyDropdown=()=>{ if(!showSpecialtyDropdown.value){ showSpecialtyDropdown.value=true; nextTick(()=>{ if(filteredSpecialties.value.length>0) focusedSpecialtyIndex.value=0 }) } }
const closeSpecialtyDropdown=()=>{ showSpecialtyDropdown.value=false; focusedSpecialtyIndex.value=-1 }
const toggleSpecialtyDropdown=()=>{ showSpecialtyDropdown.value? closeSpecialtyDropdown(): openSpecialtyDropdown() }
watch(()=>specialtyQuery.value,()=>{ if(!showSpecialtyDropdown.value) openSpecialtyDropdown(); if(filteredSpecialties.value.length>0) focusedSpecialtyIndex.value=0; else focusedSpecialtyIndex.value=-1 })
const focusNextSpecialty=()=>{ if(!showSpecialtyDropdown.value){ openSpecialtyDropdown(); return } if(filteredSpecialties.value.length===0) return; focusedSpecialtyIndex.value=(focusedSpecialtyIndex.value+1)%filteredSpecialties.value.length }
const focusPrevSpecialty=()=>{ if(!showSpecialtyDropdown.value){ openSpecialtyDropdown(); return } if(filteredSpecialties.value.length===0) return; focusedSpecialtyIndex.value=(focusedSpecialtyIndex.value-1+filteredSpecialties.value.length)%filteredSpecialties.value.length }
const selectFocusedSpecialty=()=>{ if(focusedSpecialtyIndex.value<0) return; const s=filteredSpecialties.value[focusedSpecialtyIndex.value]; if(s) pickSpecialty(s) }
const pickSpecialty=(s)=>{ form.value.specialty_id=s.id; specialtyQuery.value=s.name; closeSpecialtyDropdown(); onSpecialtyChange() }
const handleClickOutside=(e)=>{ if(!showSpecialtyDropdown.value) return; const el=specialtyBox.value; if(el && !el.contains(e.target)) closeSpecialtyDropdown() }
onMounted(()=> document.addEventListener('mousedown', handleClickOutside))
onUnmounted(()=> document.removeEventListener('mousedown', handleClickOutside))

// Studies from doctor
const studyQuery = ref('')
const showStudySelect = computed(()=> (doctor.value?.study_types||[]).length>0 )
// Combobox estudio
const showStudyDropdown = ref(false)
const focusedStudyIndex = ref(-1)
const studyBox = ref(null)
const filteredStudyTypes = computed(()=>{ const list=doctor.value?.study_types||[]; if(!studyQuery.value) return list; const q=studyQuery.value.toLowerCase(); return list.filter(s=> (s.name||'').toLowerCase().includes(q)) })
const openStudyDropdown=()=>{ if(!showStudySelect.value) return; if(!showStudyDropdown.value){ showStudyDropdown.value=true; nextTick(()=>{ if(filteredStudyTypes.value.length>0) focusedStudyIndex.value=0 }) } }
const closeStudyDropdown=()=>{ showStudyDropdown.value=false; focusedStudyIndex.value=-1 }
const toggleStudyDropdown=()=>{ showStudyDropdown.value? closeStudyDropdown(): openStudyDropdown() }
watch(()=>studyQuery.value,()=>{ if(!showStudySelect.value) return; if(!showStudyDropdown.value) openStudyDropdown(); if(filteredStudyTypes.value.length>0) focusedStudyIndex.value=0; else focusedStudyIndex.value=-1 })
const focusNextStudy=()=>{
  if(!showStudyDropdown.value){ openStudyDropdown(); return }
  const n = filteredStudyTypes.value.length
  if(n===0) { focusedStudyIndex.value=-2; return }
  // incluir opción 'Ninguno' como índice -2
  const optionsCount = n + 1
  const current = focusedStudyIndex.value === -2 ? 0 : focusedStudyIndex.value + 1
  const next = (current + 1) % optionsCount
  focusedStudyIndex.value = (next === 0) ? -2 : next - 1
}
const focusPrevStudy=()=>{
  if(!showStudyDropdown.value){ openStudyDropdown(); return }
  const n = filteredStudyTypes.value.length
  if(n===0) { focusedStudyIndex.value=-2; return }
  const optionsCount = n + 1
  const current = focusedStudyIndex.value === -2 ? 0 : focusedStudyIndex.value + 1
  const prev = (current - 1 + optionsCount) % optionsCount
  focusedStudyIndex.value = (prev === 0) ? -2 : prev - 1
}
const selectFocusedStudy=()=>{
  if(focusedStudyIndex.value === -2) { pickStudyNone(); return }
  if(focusedStudyIndex.value<0) return; const s=filteredStudyTypes.value[focusedStudyIndex.value]; if(s) pickStudy(s)
}
const pickStudy=(s)=>{ form.value.study_type_id=s.id; studyQuery.value=s.name; closeStudyDropdown() }
const pickStudyNone=()=>{ form.value.study_type_id=''; studyQuery.value='Ninguno'; closeStudyDropdown() }
const handleClickOutsideStudy=(e)=>{ if(!showStudyDropdown.value) return; const el=studyBox.value; if(el && !el.contains(e.target)) closeStudyDropdown() }
onMounted(()=> document.addEventListener('mousedown', handleClickOutsideStudy))
onUnmounted(()=> document.removeEventListener('mousedown', handleClickOutsideStudy))
const studyName = computed(()=> (doctor.value?.study_types||[]).find(s=> String(s.id)===String(form.value.study_type_id))?.name || '')
const studyDisplayName = computed(()=> studyName.value || 'Ninguno')

// Steps dynamic (1 doctor, 2 paciente, 3 specialty (if needed), 4 study (if any), 5 date, 6 details, 7 summary)
const studyStep = computed(()=> showStudySelect.value ? (needsSpecialty.value ? 4 : 3) : null)
const dateStep = computed(()=> {
  if(needsSpecialty.value && showStudySelect.value) return 5
  if(needsSpecialty.value && !showStudySelect.value) return 4
  if(!needsSpecialty.value && showStudySelect.value) return 4
  return 3
})
const detailsStep = computed(()=> dateStep.value + 1)
const summaryStep = computed(()=> detailsStep.value + 1)

// Reasons mapping
const reasonName = computed(()=> { const r = reasons.value.find(x=> String(x.id)===String(form.value.reason_id)); return r? r.name:'' })

// Min date
const minDate = computed(()=>{ const d=new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}` })

// Validation for steps
const patientName = computed(()=> { const p=(props.patients||[]).find(pt=> pt.id===form.value.patient_id); return p? (p.user?.name||p.name||'') : '' })
const nextDisabled = computed(()=>{
  if(step.value===1) return !form.value.doctor_id
  if(step.value===2) return !form.value.patient_id
  if(step.value===3 && needsSpecialty.value) return !form.value.specialty_id
  if(step.value===dateStep.value) return !form.value.appointment_date || !form.value.appointment_time
  return false
})
const canSubmit = computed(()=> form.value.patient_id && form.value.doctor_id && form.value.appointment_date && form.value.appointment_time && ( !needsSpecialty.value || form.value.specialty_id || doctorSpecialties.value.length===1))

// Navigation
const nextStep=()=>{ if(nextDisabled.value) return; step.value++ }
const prevStep=()=>{ if(step.value>1) step.value-- }

// Slot loading
const availableDays = ref([])
const loadingDays = ref(false)
const loadSlots = async()=>{
 // Evitar llamadas inválidas: requiere doctor, fecha y especialidad
 if(!form.value.doctor_id || !form.value.appointment_date || !form.value.specialty_id) { availableSlots.value=[]; return }
 loadingSlots.value=true
 try {
  const params={ doctor_id: form.value.doctor_id, date: form.value.appointment_date, specialty_id: form.value.specialty_id }
  const r = await axios.get('/api/appointments/available-slots',{params})
  availableSlots.value=r.data.slots||[]
 } catch { availableSlots.value=[] } finally { loadingSlots.value=false }
}
const isDateAvailable=(dateString)=>{ if(!form.value.specialty_id || availableDays.value.length===0) return true; try { const p=String(dateString).split('-'); if(p.length<3) return true; const dt=new Date(parseInt(p[0]), parseInt(p[1])-1, parseInt(p[2])); return availableDays.value.includes(dt.getDay()) } catch { return true } }
const onDateChange=()=>{ 
  if(form.value.appointment_date && !isDateAvailable(form.value.appointment_date)){
    form.value.appointment_date=''; 
    form.value.appointment_time=''; 
    return 
  }
  form.value.appointment_time=''; 
  // Solo cargar cuando haya especialidad definida
  if(form.value.specialty_id) loadSlots() 
}

// Specialty change resets slots
const onSpecialtyChange=()=>{ form.value.appointment_time=''; availableSlots.value=[]; if(form.value.specialty_id) loadAvailableDays(form.value.specialty_id); if(form.value.appointment_date) loadSlots() }
const loadAvailableDays = async (specialtyId)=>{ if(!specialtyId){ availableDays.value=[]; return } loadingDays.value=true; try { const r= await axios.get('/api/specialty-available-days',{ params:{ specialty_id: specialtyId } }); availableDays.value=r.data.available_days||[] } catch { availableDays.value=[] } finally { loadingDays.value=false } }

// Reset when show
watch(()=>props.show, (v)=>{ if(v){ resetForm(); loadReasons(); setupDoctor(); } })

const setupDoctor=()=>{ 
  if(props.initialDoctorId){ 
    form.value.doctor_id=String(props.initialDoctorId); 
    doctorSearchQuery.value=doctorName.value; 
    if(doctorSpecialties.value.length===1){ 
      const only=doctorSpecialties.value[0]; 
      form.value.specialty_id=only.id; 
      specialtyQuery.value=only.name; 
      loadAvailableDays(only.id) 
    }
  }
  if(!form.value.appointment_date){ 
    const now=new Date(); 
    form.value.appointment_date = props.selectedDate || `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}` 
  }
  if(form.value.specialty_id) loadAvailableDays(form.value.specialty_id)
}

const resetForm=()=>{
  step.value=1
  errors.value={}
  form.value={
    patient_id:'', doctor_id:'', specialty_id:'', study_type_id:'', appointment_date:'', appointment_time:'', status:'programada', reason_id:'', notes:''
  }
  // Búsqueda de paciente
  patientQuery.value=''
  patientResults.value=[]
  showPatientDropdown.value=false
  loadingPatients.value=false
  // Búsquedas y dropdowns de doctor/especialidad/estudio
  availableSlots.value=[]
  doctorSearchQuery.value=''
  showDoctorSearchDropdown.value=false
  focusedDoctorGlobalIndex.value=-1
  specialtyQuery.value=''
  showSpecialtyDropdown.value=false
  focusedSpecialtyIndex.value=-1
  studyQuery.value='Ninguno'
  showStudyDropdown.value=false
  focusedStudyIndex.value=-1
  // Días disponibles
  availableDays.value=[]
  loadingDays.value=false
}

// Watch for step progression auto-skip when specialty not needed
watch(step,(val)=>{ if(val===3 && !needsSpecialty.value){ step.value = studyStep.value || dateStep.value } })

// Recompute slots when doctor fixed and date set
watch(()=>form.value.appointment_date, ()=>{ if(form.value.appointment_date) loadSlots() })
watch(()=>form.value.specialty_id, (v)=>{ if(!v) availableDays.value=[] })

const printAfter = ref(false)
// Leyenda días disponibles
const dayNames=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado']
const availableDaysMessage = computed(()=>{ if(!form.value.specialty_id) return ''; if(loadingDays.value) return 'Cargando días disponibles...'; if(availableDays.value.length===0) return ''; const u=[...new Set(availableDays.value)].filter(n=>n>=0&&n<=6).sort((a,b)=>a-b); return `Días disponibles: ${u.map(n=>dayNames[n]).join(', ')}` })

const submitForm = async()=>{
 processing.value=true; errors.value={}
 // Compose datetime
 const dateTime=`${form.value.appointment_date} ${form.value.appointment_time}:00`
 const payload={ ...form.value, appointment_date: dateTime, study_type_id: form.value.study_type_id || null }
 delete payload.appointment_time
 try {
  await axios.post('/appointments', payload)
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: 'Cita creada',
    timer: 2000,
    width: 320,
    timerProgressBar: true,
    showConfirmButton: false,
    customClass: { popup: 'swal-compact-toast' }
  })
  // imprimir si el usuario lo tildó
  if (printAfter.value) {
    setTimeout(() => { try { printAppointment() } catch(e){} }, 250)
  }
  // notificar al padre y limpiar el modal para próxima carga
  emit('saved')
  setTimeout(() => {
    resetForm()
    step.value = 1
    errors.value = {}
    printAfter.value = false
  }, 250)
 } catch(e){ errors.value=e.response?.data||{} } finally { processing.value=false }
}

// Impresión usando plantilla unificada y datos de consultorio
const printAppointment = () => {
  const page = usePage()
  const clinic = page.props?.clinic || null
  const content = buildAppointmentPrintContent({
    clinic,
    title: 'Resumen de Cita',
    patientName: patientName.value,
    specialtyName: specialtyName.value,
    doctorName: doctorName.value,
    date: form.value.appointment_date,
    time: form.value.appointment_time,
    status: form.value.status,
    durationText: '',
    studyName: studyName.value,
    reasonText: reasonName.value,
    notesText: form.value.notes,
  })
  const win = window.open('', '_blank')
  if (!win) return
  win.document.open()
  win.document.write(content)
  win.document.close()
  win.focus()
  setTimeout(() => { try { win.print(); win.close() } catch(e){} }, 250)
}
</script>
