<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
      <div class="inline-block align-bottom bg-white rounded-lg overflow-hidden text-left shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-2xl h-[72vh] sm:h-[72vh] max-h-[80vh]">
        <form @submit.prevent="submitForm" class="h-full flex flex-col">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 overflow-y-auto flex-1 min-h-0">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Nueva Cita (Por Estudio)</h3>

            <!-- Paso 1: Estudio (combobox interno) -->
            <div v-show="step===1" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Estudio</label>
              <div class="mt-1 relative" ref="studyTypeBox"
                   @keydown.down.prevent="focusNextStudyType()"
                   @keydown.up.prevent="focusPrevStudyType()"
                   @keydown.enter.prevent="selectFocusedStudyType()"
                   @keydown.esc.prevent="closeStudyTypeDropdown()">
                <div class="relative">
                  <input type="text" v-model="studyTypeQuery" placeholder="Buscar / seleccionar..." @focus="openStudyTypeDropdown()" @click="openStudyTypeDropdown()" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm pr-8" />
                  <button type="button" @click="toggleStudyTypeDropdown" class="absolute inset-y-0 right-0 px-2 text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                  </button>
                </div>
                <input type="hidden" v-model="form.study_type_id" />
                <ul v-if="showStudyTypeDropdown" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md max-h-60 overflow-auto shadow-lg ring-1 ring-black/5 text-left" @mousedown.stop>
                  <li v-if="filteredStudyTypes.length===0" class="px-3 py-2 text-sm text-gray-500 select-none">Sin resultados</li>
                  <li v-for="(st,idx) in filteredStudyTypes" :key="st.id"
                      :class="['px-3 py-2 text-sm cursor-pointer flex items-center justify-between', idx===focusedStudyTypeIndex ? 'bg-blue-50 text-blue-700' : 'hover:bg-gray-50']"
                      @mouseenter="focusedStudyTypeIndex=idx"
                      @mouseleave="focusedStudyTypeIndex=-1"
                      @click="pickStudyType(st)">
                    <span class="truncate">{{ st.name }}</span>
                    <svg v-if="String(form.study_type_id)===String(st.id)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3.5-3.5a1 1 0 011.414-1.414l2.793 2.793 6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                  </li>
                </ul>
              </div>
              <p v-if="errors.study_type_id" class="mt-1 text-xs text-red-600">{{ errors.study_type_id }}</p>
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

            <!-- Paso 3: Doctor (filtrado por estudio) - Combobox -->
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

            <!-- Paso 4: Especialidad (derivada del doctor; si solo una, se salta) -->
            <div v-show="step===4 && needsSpecialty" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Especialidad</label>
              <select v-model="form.specialty_id" @change="onSpecialtyChange" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">Seleccionar</option>
                <option v-for="s in doctorSpecialties" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
              <p v-if="errors.specialty_id" class="mt-1 text-xs text-red-600">{{ errors.specialty_id }}</p>
            </div>

            <!-- Paso 5: Fecha/Hora -->
            <div v-show="step===dateStep" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Fecha</label>
              <input type="date"
                     v-model="form.appointment_date"
                     @change="onDateChange"
                     :min="minDate"
                     :disabled="form.specialty_id && (loadingDays || availableDays.length === 0)"
                     class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                     :class="{ 'bg-gray-100 cursor-not-allowed': form.specialty_id && availableDays.length === 0 }" />
              <p v-if="errors.appointment_date" class="mt-1 text-xs text-red-600">{{ errors.appointment_date }}</p>
              <p v-if="availableDaysMessage" class="mt-1 text-xs text-blue-600">{{ availableDaysMessage }}</p>
              <p v-if="form.specialty_id && availableDays.length === 0 && !loadingDays" class="mt-1 text-xs text-red-600 font-medium">⚠️ Esta especialidad no tiene horarios de atención configurados. No es posible crear citas.</p>
              <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Hora</label>
                <select v-model="form.appointment_time" :disabled="loadingSlots || !form.appointment_date || !form.doctor_id || (needsSpecialty && !form.specialty_id)" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <option value="">{{ !form.doctor_id ? 'Seleccione primero un doctor' : !form.appointment_date ? 'Seleccione primero una fecha' : loadingSlots ? 'Cargando horarios...' : 'Seleccionar hora' }}</option>
                  <option v-for="slot in availableSlots" :key="slot" :value="slot">{{ slot }}</option>
                </select>
                <p v-if="errors.appointment_time" class="mt-1 text-xs text-red-600">{{ errors.appointment_time }}</p>
                <div v-if="form.doctor_id && form.appointment_date && availableSlots.length === 0 && !loadingSlots" class="mt-1 text-sm text-yellow-600">No hay horarios disponibles para esta fecha</div>
                <div v-if="form.doctor_id && form.appointment_date && availableSlots.length > 0" class="mt-1 text-sm text-green-600">{{ availableSlots.length }} horarios disponibles</div>
              </div>
            </div>

            <!-- Paso 6: Detalles -->
            <div v-show="step===detailsStep" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Motivo</label>
              <select v-model="form.reason_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">Seleccionar motivo</option>
                <option v-for="r in reasons" :key="r.id" :value="r.id">{{ r.name }}</option>
              </select>
              <label class="block text-sm font-medium text-gray-700 mt-4">Notas</label>
              <textarea v-model="form.notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
            </div>

            <!-- Paso 7: Resumen -->
            <div v-show="step===summaryStep" class="mb-2 space-y-1 text-sm text-gray-700">
              <h4 class="font-semibold mb-2 text-gray-900">Resumen</h4>
              <p>Paciente: <strong>{{ patientName }}</strong></p>
              <p>Estudio: <strong>{{ studyName }}</strong></p>
              <p>Doctor: <strong>{{ doctorName }}</strong></p>
              <p v-if="form.specialty_id">Especialidad: <strong>{{ specialtyName }}</strong></p>
              <p>Fecha: <strong>{{ form.appointment_date }}</strong> Hora: <strong>{{ form.appointment_time }}</strong></p>
              <p>Estado: <strong>{{ form.status }}</strong></p>
              <p v-if="reasonName">Motivo: <strong>{{ reasonName }}</strong></p>
              <p v-if="form.notes">Notas: <span class="text-gray-600">{{ form.notes }}</span></p>
              <div class="mt-3 flex items-center gap-2">
                <input id="print_after_study" type="checkbox" v-model="printAfter" class="h-4 w-4" />
                <label for="print_after_study" class="select-none">Imprimir al guardar</label>
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
  studyTypes: Array,
  specialties: Array,
  selectedDate: String,
  initialStudyTypeId: [String, Number],
})
const emit = defineEmits(['close','saved'])

const step = ref(1)
const processing = ref(false)
const errors = ref({})
const loadingSlots = ref(false)
const availableSlots = ref([])
const reasons = ref([])

const form = ref({
  patient_id:'',
  doctor_id:'',
  specialty_id:'',
  study_type_id:'',
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

// Combobox Estudio (interno)
const studyTypeQuery = ref('')
const showStudyTypeDropdown = ref(false)
const focusedStudyTypeIndex = ref(-1)
const studyTypeBox = ref(null)
const filteredStudyTypes = computed(()=>{ const list=props.studyTypes||[]; if(!studyTypeQuery.value) return list; const q=studyTypeQuery.value.toLowerCase(); return list.filter(s=> (s.name||'').toLowerCase().includes(q)) })
const openStudyTypeDropdown=()=>{ if(!showStudyTypeDropdown.value){ showStudyTypeDropdown.value=true; nextTick(()=>{ if(filteredStudyTypes.value.length>0) focusedStudyTypeIndex.value=0 }) } }
const closeStudyTypeDropdown=()=>{ showStudyTypeDropdown.value=false; focusedStudyTypeIndex.value=-1 }
const toggleStudyTypeDropdown=()=>{ showStudyTypeDropdown.value? closeStudyTypeDropdown(): openStudyTypeDropdown() }
watch(()=>studyTypeQuery.value,()=>{ if(!showStudyTypeDropdown.value) openStudyTypeDropdown(); if(filteredStudyTypes.value.length>0) focusedStudyTypeIndex.value=0; else focusedStudyTypeIndex.value=-1 })
const focusNextStudyType=()=>{ if(!showStudyTypeDropdown.value){ openStudyTypeDropdown(); return } if(filteredStudyTypes.value.length===0) return; focusedStudyTypeIndex.value=(focusedStudyTypeIndex.value+1)%filteredStudyTypes.value.length }
const focusPrevStudyType=()=>{ if(!showStudyTypeDropdown.value){ openStudyTypeDropdown(); return } if(filteredStudyTypes.value.length===0) return; focusedStudyTypeIndex.value=(focusedStudyTypeIndex.value-1+filteredStudyTypes.value.length)%filteredStudyTypes.value.length }
const selectFocusedStudyType=()=>{ if(focusedStudyTypeIndex.value<0) return; const s=filteredStudyTypes.value[focusedStudyTypeIndex.value]; if(s) pickStudyType(s) }
const pickStudyType=(s)=>{ form.value.study_type_id=s.id; studyTypeQuery.value=s.name; closeStudyTypeDropdown(); if(step.value===1) step.value=2 }
const handleClickOutsideStudyType=(e)=>{ if(!showStudyTypeDropdown.value) return; const el=studyTypeBox.value; if(el && !el.contains(e.target)) closeStudyTypeDropdown() }
onMounted(()=> document.addEventListener('mousedown', handleClickOutsideStudyType))
onUnmounted(()=> document.removeEventListener('mousedown', handleClickOutsideStudyType))

// Doctores filtrados por estudio seleccionado
const filteredDoctors = computed(()=> (props.doctors||[]).filter(d=> (d.study_types||[]).some(st=> String(st.id)===String(form.value.study_type_id))))
const doctorQuery=ref('')
const doctorFilteredList = computed(()=>{ if(!doctorQuery.value) return filteredDoctors.value; const q=doctorQuery.value.toLowerCase(); return filteredDoctors.value.filter(d=> ((d.user?.name||d.name||'')+' '+(d.license_number||'')).toLowerCase().includes(q)) })
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

const doctorSpecialties = computed(()=> doctor.value?.specialties || [])
const needsSpecialty = computed(()=> doctorSpecialties.value.length>1)
const specialtyName = computed(()=> { const s=(props.specialties||[]).find(s=> String(s.id)===String(form.value.specialty_id)); return s? s.name:'' })

const studyName = computed(()=> (props.studyTypes||[]).find(s=> String(s.id)===String(form.value.study_type_id))?.name || '')

// Steps: 1 estudio, 2 paciente, 3 doctor, 4 especialidad (si hace falta), 5 fecha, 6 detalles, 7 resumen
const dateStep = computed(()=> needsSpecialty.value ? 5 : 4)
const detailsStep = computed(()=> dateStep.value+1)
const summaryStep = computed(()=> detailsStep.value+1)

const reasonName = computed(()=> { const r=reasons.value.find(x=> String(x.id)===String(form.value.reason_id)); return r? r.name:'' })
const patientName = computed(()=> { const p=(props.patients||[]).find(pt=> pt.id===form.value.patient_id); return p? (p.user?.name||p.name||'') : '' })

const minDate = computed(()=>{ const d=new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}` })
// Días disponibles por especialidad (para bloquear fecha cuando no hay días)
const availableDays = ref([])
const loadingDays = ref(false)
const dayNames=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado']
const availableDaysMessage = computed(()=>{
  if(!form.value.specialty_id) return ''
  if(loadingDays.value) return 'Cargando días disponibles...'
  if(availableDays.value.length===0) return ''
  const names = [...new Set(availableDays.value)].filter(n=>n>=0&&n<=6).sort((a,b)=>a-b).map(n=>dayNames[n])
  return names.length? `Días disponibles: ${names.join(', ')}` : ''
})
const loadAvailableDays = async (specialtyId)=>{
  if(!specialtyId){ availableDays.value=[]; return }
  loadingDays.value=true
  try {
    const r = await axios.get('/api/specialty-available-days',{ params:{ specialty_id: specialtyId } })
    availableDays.value = r.data?.available_days || []
  } catch { availableDays.value=[] } finally { loadingDays.value=false }
}

const nextDisabled = computed(()=>{
 if(step.value===1) return !form.value.study_type_id
 if(step.value===2) return !form.value.patient_id
 if(step.value===3) return !form.value.doctor_id
 if(step.value===4 && needsSpecialty.value) return !form.value.specialty_id
 if(step.value===dateStep.value) return !form.value.appointment_date || !form.value.appointment_time
 return false
})
const canSubmit = computed(()=> form.value.study_type_id && form.value.patient_id && form.value.doctor_id && form.value.appointment_date && form.value.appointment_time && (!needsSpecialty.value || form.value.specialty_id || doctorSpecialties.value.length===1))

const nextStep=()=>{ if(!nextDisabled.value) step.value++ }
const prevStep=()=>{ if(step.value>1) step.value-- }

const loadSlots = async()=>{ if(!form.value.doctor_id || !form.value.appointment_date){ availableSlots.value=[]; return } loadingSlots.value=true; try{ const params={doctor_id: form.value.doctor_id, date: form.value.appointment_date, specialty_id: form.value.specialty_id}; const r=await axios.get('/api/appointments/available-slots',{params}); availableSlots.value=r.data.slots||[] }catch{ availableSlots.value=[] } finally{ loadingSlots.value=false } }
const onDoctorChange=()=>{ 
  form.value.appointment_time=''; 
  availableSlots.value=[]; 
  if(!needsSpecialty.value && doctorSpecialties.value.length===1){ 
    form.value.specialty_id=doctorSpecialties.value[0].id
    loadAvailableDays(form.value.specialty_id)
  }
  if(form.value.appointment_date) loadSlots();
}
const onSpecialtyChange=()=>{ 
  form.value.appointment_time=''; 
  availableSlots.value=[]; 
  loadAvailableDays(form.value.specialty_id)
  if(form.value.appointment_date) loadSlots() 
}

watch(()=>props.show,(v)=>{ if(v){ resetForm(); loadReasons(); if(props.initialStudyTypeId){ form.value.study_type_id=String(props.initialStudyTypeId); const st=(props.studyTypes||[]).find(x=> String(x.id)===String(form.value.study_type_id)); studyTypeQuery.value=st?.name||'' } setupDate() } })
const setupDate=()=>{ if(!form.value.appointment_date){ const d=new Date(); form.value.appointment_date = props.selectedDate || `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}` } }
const resetForm=()=>{
  step.value=1
  errors.value={}
  form.value={ patient_id:'', doctor_id:'', specialty_id:'', study_type_id:'', appointment_date:'', appointment_time:'', status:'programada', reason_id:'', notes:'' }
  // Paciente
  patientQuery.value=''
  patientResults.value=[]
  showPatientDropdown.value=false
  loadingPatients.value=false
  // Estudio combobox
  studyTypeQuery.value=''
  showStudyTypeDropdown.value=false
  focusedStudyTypeIndex.value=-1
  // Doctor combobox
  doctorQuery.value=''
  showDoctorDropdown.value=false
  focusedDoctorIndex.value=-1
  // Slots y días
  availableSlots.value=[]
  availableDays.value=[]
  loadingDays.value=false
}
const onDateChange=()=>{ form.value.appointment_time=''; loadSlots() }

const printAfter=ref(false)

const submitForm= async()=>{
 processing.value=true; errors.value={}
 const dateTime=`${form.value.appointment_date} ${form.value.appointment_time}:00`
 const payload={ ...form.value, appointment_date: dateTime, study_type_id: form.value.study_type_id }
 delete payload.appointment_time
 try { 
  await axios.post('/appointments', payload);
  Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Cita creada', timer:2000, width:320, timerProgressBar:true, showConfirmButton:false, customClass:{ popup:'swal-compact-toast' } });
  if (printAfter.value) {
    setTimeout(() => { try { printAppointment() } catch(e){} }, 250)
  }
  emit('saved')
  // Limpiar modal para la próxima creación
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
