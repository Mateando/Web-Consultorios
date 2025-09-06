<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
      <div class="inline-block align-bottom bg-white rounded-lg overflow-hidden text-left shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-2xl h-[72vh] sm:h-[72vh] max-h-[80vh]">
        <form @submit.prevent="submitForm" class="h-full flex flex-col">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 overflow-y-auto flex-1 min-h-0">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Nueva Cita (Por Especialidad)</h3>

            <!-- Paso 1: Especialidad (combobox) -->
            <div v-show="step===1" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Especialidad</label>
              <div class="mt-1 relative" ref="specialtyBox"
                   @keydown.down.prevent="focusNextSpecialty()"
                   @keydown.up.prevent="focusPrevSpecialty()"
                   @keydown.enter.prevent="selectFocusedSpecialty()"
                   @keydown.esc.prevent="closeSpecialtyDropdown()">
                <div class="relative">
                  <input
                    type="text"
                    v-model="specialtyQuery"
                    placeholder="Buscar / seleccionar..."
                    @focus="openSpecialtyDropdown()"
                    @click="openSpecialtyDropdown()"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm pr-8"
                  />
                  <button type="button" @click="toggleSpecialtyDropdown" class="absolute inset-y-0 right-0 px-2 text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                  </button>
                </div>
                <input type="hidden" v-model="form.specialty_id" />
                <ul v-if="showSpecialtyDropdown" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md max-h-60 overflow-auto shadow-lg ring-1 ring-black/5 text-left" @mousedown.stop>
                  <li v-if="filteredSpecialties.length===0" class="px-3 py-2 text-sm text-gray-500 select-none">Sin resultados</li>
                  <li v-for="(s,idx) in filteredSpecialties" :key="s.id"
                      :class="['px-3 py-2 text-sm cursor-pointer flex items-center justify-between', idx===focusedSpecialtyIndex ? 'bg-blue-50 text-blue-700' : 'hover:bg-gray-50']"
                      @mouseenter="focusedSpecialtyIndex=idx"
                      @mouseleave="focusedSpecialtyIndex=-1"
                      @click="pickSpecialty(s)">
                    <span class="truncate">{{ s.name }}</span>
                    <svg v-if="String(form.specialty_id)===String(s.id)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3.5-3.5a1 1 0 011.414-1.414l2.793 2.793 6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                  </li>
                </ul>
              </div>
              <p v-if="errors.specialty_id" class="mt-1 text-xs text-red-600">{{ errors.specialty_id }}</p>
            </div>

            <!-- Paso 2: Paciente -->
            <div v-show="step===2" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Paciente</label>
              <div class="mt-1 relative">
                <input type="search" v-model="patientQuery" @input="onPatientQueryChange" placeholder="Buscar paciente..." class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm pr-10" />
                <div v-if="loadingPatients" class="absolute right-2 top-2 text-xs text-gray-500">...</div>
                <ul v-if="showPatientDropdown" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md max-h-56 overflow-auto">
                  <li v-if="patientResults.length===0" class="px-3 py-2 text-sm text-gray-500">Sin resultados</li>
                  <li v-for="p in patientResults" :key="p.id" @click="selectPatient(p)" class="px-3 py-2 text-sm hover:bg-gray-100 cursor-pointer">
                    <div class="font-medium">{{ p.name }}</div>
                    <div class="text-xs text-gray-500">{{ (p.document_type ? p.document_type.toUpperCase()+ ' ' : '') + (p.document_number||'') }}</div>
                  </li>
                </ul>
              </div>
              <p v-if="form.patient_id" class="mt-1 text-xs text-green-600">Seleccionado: {{ patientName }}</p>
              <p v-if="errors.patient_id" class="mt-1 text-xs text-red-600">{{ errors.patient_id }}</p>
            </div>

            <!-- Paso 3: Doctor (filtrado por especialidad) - Combobox -->
            <div v-show="step===3" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Doctor</label>
              <div class="mt-1 relative" ref="doctorBox"
                   @keydown.down.prevent="focusNextDoctor()"
                   @keydown.up.prevent="focusPrevDoctor()"
                   @keydown.enter.prevent="selectFocusedDoctor()"
                   @keydown.esc.prevent="closeDoctorDropdown()">
                <div class="relative">
                  <input
                    type="text"
                    v-model="doctorQuery"
                    placeholder="Buscar / seleccionar..."
                    @focus="openDoctorDropdown()"
                    @click="openDoctorDropdown()"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm pr-8"
                  />
                  <button type="button" @click="toggleDoctorDropdown" class="absolute inset-y-0 right-0 px-2 text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                  </button>
                </div>
                <input type="hidden" v-model="form.doctor_id" />
                <ul v-if="showDoctorDropdown" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md max-h-60 overflow-auto shadow-lg ring-1 ring-black/5 text-left" @mousedown.stop>
                  <li v-if="doctorFilteredList.length===0" class="px-3 py-2 text-sm text-gray-500 select-none">Sin resultados</li>
                  <li v-for="(d,idx) in doctorFilteredList" :key="d.id"
                      :class="['px-3 py-2 text-sm cursor-pointer flex items-center justify-between', idx===focusedDoctorIndex ? 'bg-blue-50 text-blue-700' : 'hover:bg-gray-50']"
                      @mouseenter="focusedDoctorIndex=idx"
                      @mouseleave="focusedDoctorIndex=-1"
                      @click="pickDoctor(d)">
                    <span class="truncate">{{ d.user?.name || d.name }}</span>
                    <svg v-if="String(form.doctor_id)===String(d.id)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3.5-3.5a1 1 0 011.414-1.414l2.793 2.793 6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                  </li>
                </ul>
              </div>
              <p v-if="errors.doctor_id" class="mt-1 text-xs text-red-600">{{ errors.doctor_id }}</p>
            </div>

            <!-- Paso 4: Fecha/Hora -->
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
              <div v-if="availableDaysMessage" class="mt-1 text-sm text-blue-600">{{ availableDaysMessage }}</div>
              <div v-if="form.specialty_id && availableDays.length === 0 && !loadingDays" class="mt-1 text-sm text-red-600 font-medium">⚠️ Esta especialidad no tiene horarios de atención configurados. No es posible crear citas.</div>
              <p v-if="errors.appointment_date" class="mt-1 text-xs text-red-600">{{ errors.appointment_date }}</p>
              <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Hora</label>
                <select v-model="form.appointment_time" :disabled="!form.doctor_id || !form.appointment_date || loadingSlots" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <option value="">{{ !form.doctor_id ? 'Seleccione primero un doctor' : !form.appointment_date ? 'Seleccione primero una fecha' : loadingSlots ? 'Cargando horarios...' : 'Seleccionar hora' }}</option>
                  <option v-for="slot in availableSlots" :key="slot" :value="slot">{{ slot }}</option>
                </select>
                <p v-if="errors.appointment_time" class="mt-1 text-xs text-red-600">{{ errors.appointment_time }}</p>
                <div v-if="form.doctor_id && form.appointment_date && availableSlots.length === 0 && !loadingSlots" class="mt-1 text-sm text-yellow-600">No hay horarios disponibles para esta fecha</div>
                <div v-if="form.doctor_id && form.appointment_date && availableSlots.length > 0" class="mt-1 text-sm text-green-600">{{ availableSlots.length }} horarios disponibles</div>
              </div>
            </div>

            <!-- Paso 5: Detalles -->
            <div v-show="step===detailsStep" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Motivo</label>
              <select v-model="form.reason_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">Seleccionar motivo</option>
                <option v-for="r in reasons" :key="r.id" :value="r.id">{{ r.name }}</option>
              </select>
              <label class="block text-sm font-medium text-gray-700 mt-4">Notas</label>
              <textarea v-model="form.notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
            </div>

            <!-- Paso 6: Resumen -->
            <div v-show="step===summaryStep" class="mb-2 space-y-1 text-sm text-gray-700">
              <h4 class="font-semibold mb-2 text-gray-900">Resumen</h4>
              <p>Paciente: <strong>{{ patientName }}</strong></p>
              <p>Especialidad: <strong>{{ specialtyName }}</strong></p>
              <p>Doctor: <strong>{{ doctorName }}</strong></p>
              <p>Fecha: <strong>{{ form.appointment_date }}</strong> Hora: <strong>{{ form.appointment_time }}</strong></p>
              <p>Estado: <strong>{{ form.status }}</strong></p>
              <p v-if="reasonName">Motivo: <strong>{{ reasonName }}</strong></p>
              <p v-if="form.notes">Notas: <span class="text-gray-600">{{ form.notes }}</span></p>
              <div class="mt-3 flex items-center gap-2">
                <input id="print_after_spec" type="checkbox" v-model="printAfter" class="h-4 w-4" />
                <label for="print_after_spec" class="select-none">Imprimir al guardar</label>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse items-center border-t gap-2">
            <div class="sm:ml-3 sm:w-auto w-full">
              <PrimaryButton v-if="step < summaryStep" type="button" :disabled="nextDisabled" @click="nextStep" class="w-full sm:w-auto">Siguiente</PrimaryButton>
              <PrimaryButton v-else type="submit" :disabled="processing || (form.specialty_id && availableDays.length === 0) || !canSubmit" class="w-full sm:w-auto">{{ processing? 'Guardando...' : 'Crear' }}</PrimaryButton>
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
  initialSpecialtyId: [String, Number],
})
const emit = defineEmits(['close','saved'])

const step = ref(1)
const processing = ref(false)
const errors = ref({})
const loadingSlots = ref(false)
const availableSlots = ref([])
const availableDays = ref([])
const loadingDays = ref(false)
const reasons = ref([])

const form = ref({
  patient_id:'',
  doctor_id:'',
  specialty_id:'',
  appointment_date:'',
  appointment_time:'',
  status:'programada',
  reason_id:'',
  notes:'',
})

const loadReasons = async()=>{ try{ const r=await axios.get(route('api.config.appointment-reasons')); reasons.value=r.data||[] }catch{ reasons.value=[] } }

// Patient search
const patientQuery=ref(''); const patientResults=ref([]); const showPatientDropdown=ref(false); const loadingPatients=ref(false); let debounceTimer=null
const onPatientQueryChange=()=>{ if(!patientQuery.value){ patientResults.value=[]; showPatientDropdown.value=false; return } if(debounceTimer) clearTimeout(debounceTimer); debounceTimer=setTimeout(()=>fetchPatients(patientQuery.value),300) }
const fetchPatients= async(q)=>{ loadingPatients.value=true; showPatientDropdown.value=true; try{ const r= await axios.get('/api/patients',{params:{q,limit:20}}); patientResults.value=r.data?.data||[] }catch{ patientResults.value=[] }finally{ loadingPatients.value=false } }
const selectPatient=(p)=>{ form.value.patient_id=p.id; patientQuery.value=p.name||''; showPatientDropdown.value=false }

// Especialidades combobox (filtra desde el modal)
const specialtyQuery = ref('')
const showSpecialtyDropdown = ref(false)
const focusedSpecialtyIndex = ref(-1)
const specialtyBox = ref(null)
const filteredSpecialties = computed(()=>{
  const list = props.specialties || []
  if(!specialtyQuery.value) return list
  const q = specialtyQuery.value.toLowerCase()
  return list.filter(s=> (s.name||'').toLowerCase().includes(q))
})
const openSpecialtyDropdown=()=>{ if(!showSpecialtyDropdown.value){ showSpecialtyDropdown.value=true; nextTick(()=>{ if(filteredSpecialties.value.length>0) focusedSpecialtyIndex.value=0 }) } }
const closeSpecialtyDropdown=()=>{ showSpecialtyDropdown.value=false; focusedSpecialtyIndex.value=-1 }
const toggleSpecialtyDropdown=()=>{ showSpecialtyDropdown.value? closeSpecialtyDropdown(): openSpecialtyDropdown() }
watch(()=>specialtyQuery.value,()=>{ if(!showSpecialtyDropdown.value) openSpecialtyDropdown(); if(filteredSpecialties.value.length>0) focusedSpecialtyIndex.value=0; else focusedSpecialtyIndex.value=-1 })
const focusNextSpecialty=()=>{ if(!showSpecialtyDropdown.value){ openSpecialtyDropdown(); return } if(filteredSpecialties.value.length===0) return; focusedSpecialtyIndex.value=(focusedSpecialtyIndex.value+1)%filteredSpecialties.value.length }
const focusPrevSpecialty=()=>{ if(!showSpecialtyDropdown.value){ openSpecialtyDropdown(); return } if(filteredSpecialties.value.length===0) return; focusedSpecialtyIndex.value=(focusedSpecialtyIndex.value-1+filteredSpecialties.value.length)%filteredSpecialties.value.length }
const selectFocusedSpecialty=()=>{ if(focusedSpecialtyIndex.value<0) return; const s=filteredSpecialties.value[focusedSpecialtyIndex.value]; if(s) pickSpecialty(s) }
const pickSpecialty=(s)=>{ form.value.specialty_id=s.id; specialtyQuery.value=s.name; closeSpecialtyDropdown(); onSpecialtyChange(); if(step.value===1) step.value=2 }
const handleClickOutsideSpecialty=(e)=>{ if(!showSpecialtyDropdown.value) return; const el=specialtyBox.value; if(el && !el.contains(e.target)) closeSpecialtyDropdown() }
onMounted(()=> document.addEventListener('mousedown', handleClickOutsideSpecialty))
onUnmounted(()=> document.removeEventListener('mousedown', handleClickOutsideSpecialty))

// Filtrado de doctores por especialidad elegida
const filteredDoctors = computed(()=> (props.doctors||[]).filter(d=> (d.specialties||[]).some(s=> String(s.id)===String(form.value.specialty_id))))
const doctorQuery=ref('')
const doctorFilteredList = computed(()=>{ if(!doctorQuery.value) return filteredDoctors.value; const q=doctorQuery.value.toLowerCase(); return filteredDoctors.value.filter(d=> ((d.user?.name||d.name||'')+ ' ' +(d.license_number||'')).toLowerCase().includes(q)) })
watch(filteredDoctors, ()=>{ if(!filteredDoctors.value.some(d=> String(d.id)===String(form.value.doctor_id))) form.value.doctor_id='' })

// Combobox Doctor (UI/teclado)
const showDoctorDropdown = ref(false)
const focusedDoctorIndex = ref(-1)
const doctorBox = ref(null)
const openDoctorDropdown=()=>{ if(!showDoctorDropdown.value){ showDoctorDropdown.value=true; nextTick(()=>{ if(doctorFilteredList.value.length>0) focusedDoctorIndex.value=0 }) } }
const closeDoctorDropdown=()=>{ showDoctorDropdown.value=false; focusedDoctorIndex.value=-1 }
const toggleDoctorDropdown=()=>{ showDoctorDropdown.value? closeDoctorDropdown() : openDoctorDropdown() }
watch(()=>doctorQuery.value, ()=>{ if(!showDoctorDropdown.value) openDoctorDropdown(); if(doctorFilteredList.value.length>0) focusedDoctorIndex.value=0; else focusedDoctorIndex.value=-1 })
const focusNextDoctor=()=>{ if(!showDoctorDropdown.value){ openDoctorDropdown(); return } if(doctorFilteredList.value.length===0) return; focusedDoctorIndex.value=(focusedDoctorIndex.value+1)%doctorFilteredList.value.length }
const focusPrevDoctor=()=>{ if(!showDoctorDropdown.value){ openDoctorDropdown(); return } if(doctorFilteredList.value.length===0) return; focusedDoctorIndex.value=(focusedDoctorIndex.value-1+doctorFilteredList.value.length)%doctorFilteredList.value.length }
const selectFocusedDoctor=()=>{ if(focusedDoctorIndex.value<0) return; const d=doctorFilteredList.value[focusedDoctorIndex.value]; if(d) pickDoctor(d) }
const pickDoctor=(d)=>{ form.value.doctor_id=d.id; doctorQuery.value=d.user?.name || d.name || ''; closeDoctorDropdown(); onDoctorChange() }
const handleClickOutsideDoctor=(e)=>{ if(!showDoctorDropdown.value) return; const el=doctorBox.value; if(el && !el.contains(e.target)) closeDoctorDropdown() }
onMounted(()=> document.addEventListener('mousedown', handleClickOutsideDoctor))
onUnmounted(()=> document.removeEventListener('mousedown', handleClickOutsideDoctor))

const doctor = computed(()=> (props.doctors||[]).find(d=> String(d.id)===String(form.value.doctor_id)))
const doctorName = computed(()=> doctor.value? (doctor.value.user?.name||doctor.value.name||''): '')
const specialtyName = computed(()=> { const s=(props.specialties||[]).find(s=> String(s.id)===String(form.value.specialty_id)); return s? s.name:'' })

// Steps: 1 especialidad, 2 paciente, 3 doctor, 4 fecha, 5 detalles, 6 resumen
const dateStep = computed(()=> 4)
const detailsStep = computed(()=> 5)
const summaryStep = computed(()=> 6)

const reasonName = computed(()=> { const r=reasons.value.find(x=> String(x.id)===String(form.value.reason_id)); return r? r.name:'' })
const patientName = computed(()=> { const p=(props.patients||[]).find(pt=> pt.id===form.value.patient_id); return p? (p.user?.name||p.name||'') : '' })

const minDate = computed(()=>{ const d=new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}` })

const availableDaysMessage = computed(()=>{
  if (!form.value.specialty_id || loadingDays.value) return ''
  if (availableDays.value.length === 0) return ''
  const dayNames = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado']
  const names = availableDays.value.map(n => dayNames[n]).filter(Boolean)
  return names.length ? `Días disponibles: ${names.join(', ')}` : ''
})

const nextDisabled = computed(()=>{
 if(step.value===1) return !form.value.specialty_id
 if(step.value===2) return !form.value.patient_id
 if(step.value===3) return !form.value.doctor_id
 if(step.value===dateStep.value) return !form.value.appointment_date || !form.value.appointment_time
 return false
})
const canSubmit = computed(()=> form.value.patient_id && form.value.specialty_id && form.value.doctor_id && form.value.appointment_date && form.value.appointment_time )

const nextStep=()=>{ if(!nextDisabled.value) step.value++ }
const prevStep=()=>{ if(step.value>1) step.value-- }

const loadSlots = async()=>{ if(!form.value.doctor_id || !form.value.appointment_date){ availableSlots.value=[]; return } loadingSlots.value=true; try{ const params={doctor_id: form.value.doctor_id, date: form.value.appointment_date, specialty_id: form.value.specialty_id}; const r=await axios.get('/api/appointments/available-slots',{params}); availableSlots.value=r.data.slots||[] }catch{ availableSlots.value=[] } finally{ loadingSlots.value=false } }
const loadAvailableDays = async (specialtyId)=>{
  if(!specialtyId){ availableDays.value=[]; return }
  loadingDays.value = true
  try {
    const r = await axios.get('/api/specialty-available-days',{ params:{ specialty_id: specialtyId } })
    availableDays.value = r.data?.available_days || []
  } catch {
    availableDays.value = []
  } finally { loadingDays.value = false }
}
const onDateChange=()=>{ form.value.appointment_time=''; loadSlots() }
const onDoctorChange=()=>{ form.value.appointment_time=''; availableSlots.value=[]; if(form.value.appointment_date) loadSlots() }
const onSpecialtyChange=()=>{
  // al cambiar especialidad: limpiar doctor/hora/slots, cargar días disponibles
  form.value.doctor_id=''
  form.value.appointment_time=''
  availableSlots.value=[]
  loadAvailableDays(form.value.specialty_id)
}

watch(()=>props.show,(v)=>{ if(v){ resetForm(); loadReasons(); if(props.initialSpecialtyId){ form.value.specialty_id=String(props.initialSpecialtyId); const s=(props.specialties||[]).find(x=> String(x.id)===String(form.value.specialty_id)); specialtyQuery.value=s?.name||''; loadAvailableDays(form.value.specialty_id) } setupDate() } })
const setupDate=()=>{ if(!form.value.appointment_date){ const d=new Date(); form.value.appointment_date = props.selectedDate || `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}` } }
const resetForm=()=>{
  step.value=1
  errors.value={}
  form.value={ patient_id:'', doctor_id:'', specialty_id:'', appointment_date:'', appointment_time:'', status:'programada', reason_id:'', notes:'' }
  // Búsqueda de paciente
  patientQuery.value=''
  patientResults.value=[]
  showPatientDropdown.value=false
  loadingPatients.value=false
  // Comboboxes y búsquedas
  specialtyQuery.value=''
  showSpecialtyDropdown.value=false
  focusedSpecialtyIndex.value=-1
  doctorQuery.value=''
  showDoctorDropdown.value=false
  focusedDoctorIndex.value=-1
  // Slots/días
  availableSlots.value=[]
  availableDays.value=[]
  loadingDays.value=false
}

const printAfter=ref(false)

const submitForm= async()=>{
 processing.value=true; errors.value={}
 const dateTime=`${form.value.appointment_date} ${form.value.appointment_time}:00`
 const payload={ ...form.value, appointment_date: dateTime }
 delete payload.appointment_time
 try { 
  await axios.post('/appointments', payload);
  Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Cita creada', timer:2000, width:320, timerProgressBar:true, showConfirmButton:false, customClass:{ popup:'swal-compact-toast' } });
  // imprimir si el usuario lo tildó
  if (printAfter.value) {
    setTimeout(() => { try { printAppointment() } catch(e){} }, 250)
  }
  emit('saved')
  // limpiar el modal para la próxima carga
  setTimeout(() => {
    resetForm()
    step.value = 1
    errors.value = {}
    printAfter.value = false
  }, 250)
 } catch(e){ errors.value=e.response?.data||{} } finally { processing.value=false }
}

// Impresión usando plantilla unificada
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
    studyName: '',
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
