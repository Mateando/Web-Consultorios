<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
        <form @submit.prevent="submitForm">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Nueva Cita (Por Estudio)</h3>

            <!-- Paso 1: Paciente -->
            <div v-show="step===1" class="mb-4">
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

            <!-- Paso 2: Doctor (filtrados por estudio) -->
            <div v-show="step===2" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Doctor</label>
              <input type="text" v-model="doctorQuery" placeholder="Buscar doctor..." class="mb-2 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" />
              <select v-model="form.doctor_id" @change="onDoctorChange" class="block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">Seleccionar</option>
                <option v-for="d in filteredDoctors" :key="d.id" :value="d.id">{{ d.user?.name || d.name }}</option>
              </select>
              <p v-if="errors.doctor_id" class="mt-1 text-xs text-red-600">{{ errors.doctor_id }}</p>
            </div>

            <!-- Paso 3: Especialidad (derivada del doctor; si solo una, se salta) -->
            <div v-show="step===3 && needsSpecialty" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Especialidad</label>
              <select v-model="form.specialty_id" @change="onSpecialtyChange" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">Seleccionar</option>
                <option v-for="s in doctorSpecialties" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
              <p v-if="errors.specialty_id" class="mt-1 text-xs text-red-600">{{ errors.specialty_id }}</p>
            </div>

            <!-- Paso 4: Fecha/Hora -->
            <div v-show="step===dateStep" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Fecha</label>
              <input type="date" v-model="form.appointment_date" @change="onDateChange" :min="minDate" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
              <p v-if="errors.appointment_date" class="mt-1 text-xs text-red-600">{{ errors.appointment_date }}</p>
              <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Hora</label>
                <select v-model="form.appointment_time" :disabled="loadingSlots || !form.appointment_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <option value="">Seleccionar hora</option>
                  <option v-for="slot in availableSlots" :key="slot" :value="slot">{{ slot }}</option>
                </select>
                <p v-if="errors.appointment_time" class="mt-1 text-xs text-red-600">{{ errors.appointment_time }}</p>
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
          <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-row-reverse gap-2">
            <PrimaryButton v-if="step < summaryStep" type="button" :disabled="nextDisabled" @click="nextStep">Siguiente</PrimaryButton>
            <PrimaryButton v-else type="submit" :disabled="processing || !canSubmit">{{ processing? 'Guardando...' : 'Crear' }}</PrimaryButton>
            <SecondaryButton v-if="step>1" type="button" @click="prevStep">Atrás</SecondaryButton>
            <SecondaryButton type="button" @click="$emit('close')">Cancelar</SecondaryButton>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

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

// Filter doctors by study
const filteredDoctors = computed(()=> (props.doctors||[]).filter(d=> (d.study_types||[]).some(st=> String(st.id)===String(form.value.study_type_id))))
const doctorQuery=ref('')
const doctorFilteredList = computed(()=>{ if(!doctorQuery.value) return filteredDoctors.value; const q=doctorQuery.value.toLowerCase(); return filteredDoctors.value.filter(d=> (d.user?.name||'').toLowerCase().includes(q)) })

const doctor = computed(()=> (props.doctors||[]).find(d=> String(d.id)===String(form.value.doctor_id)))
const doctorName = computed(()=> doctor.value? (doctor.value.user?.name||doctor.value.name||''): '')

const doctorSpecialties = computed(()=> doctor.value?.specialties || [])
const needsSpecialty = computed(()=> doctorSpecialties.value.length>1)
const specialtyName = computed(()=> { const s=(props.specialties||[]).find(s=> String(s.id)===String(form.value.specialty_id)); return s? s.name:'' })

const studyName = computed(()=> (props.studyTypes||[]).find(s=> String(s.id)===String(form.value.study_type_id))?.name || '')

// Steps
const dateStep = computed(()=> needsSpecialty.value ? 4 : 3)
const detailsStep = computed(()=> dateStep.value+1)
const summaryStep = computed(()=> detailsStep.value+1)

const reasonName = computed(()=> { const r=reasons.value.find(x=> String(x.id)===String(form.value.reason_id)); return r? r.name:'' })
const patientName = computed(()=> { const p=(props.patients||[]).find(pt=> pt.id===form.value.patient_id); return p? (p.user?.name||p.name||'') : '' })

const minDate = computed(()=>{ const d=new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}` })

const nextDisabled = computed(()=>{
 if(step.value===1) return !form.value.patient_id
 if(step.value===2) return !form.value.doctor_id
 if(step.value===3 && needsSpecialty.value) return !form.value.specialty_id
 if(step.value===dateStep.value) return !form.value.appointment_date || !form.value.appointment_time
 return false
})
const canSubmit = computed(()=> form.value.patient_id && form.value.doctor_id && form.value.appointment_date && form.value.appointment_time && (!needsSpecialty.value || form.value.specialty_id || doctorSpecialties.value.length===1))

const nextStep=()=>{ if(!nextDisabled.value) step.value++ }
const prevStep=()=>{ if(step.value>1) step.value-- }

const loadSlots = async()=>{ if(!form.value.doctor_id || !form.value.appointment_date){ availableSlots.value=[]; return } loadingSlots.value=true; try{ const params={doctor_id: form.value.doctor_id, date: form.value.appointment_date, specialty_id: form.value.specialty_id}; const r=await axios.get('/api/appointments/available-slots',{params}); availableSlots.value=r.data.slots||[] }catch{ availableSlots.value=[] } finally{ loadingSlots.value=false } }
const onDateChange=()=>{ form.value.appointment_time=''; loadSlots() }
const onDoctorChange=()=>{ form.value.appointment_time=''; availableSlots.value=[]; if(form.value.appointment_date) loadSlots(); // auto-set specialty if only one
 if(!needsSpecialty.value && doctorSpecialties.value.length===1){ form.value.specialty_id=doctorSpecialties.value[0].id } }
const onSpecialtyChange=()=>{ form.value.appointment_time=''; availableSlots.value=[]; if(form.value.appointment_date) loadSlots() }

watch(()=>props.show,(v)=>{ if(v){ resetForm(); loadReasons(); if(props.initialStudyTypeId){ form.value.study_type_id=String(props.initialStudyTypeId) } setupDate() } })
const setupDate=()=>{ if(!form.value.appointment_date){ const d=new Date(); form.value.appointment_date = props.selectedDate || `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}` } }
const resetForm=()=>{ step.value=1; errors.value={}; form.value={ patient_id:'', doctor_id:'', specialty_id:'', study_type_id:'', appointment_date:'', appointment_time:'', status:'programada', reason_id:'', notes:'' }; availableSlots.value=[] }

const printAfter=ref(false)

const submitForm= async()=>{
 processing.value=true; errors.value={}
 const dateTime=`${form.value.appointment_date} ${form.value.appointment_time}:00`
 const payload={ ...form.value, appointment_date: dateTime, study_type_id: form.value.study_type_id }
 delete payload.appointment_time
 try { 
  await axios.post('/appointments', payload);
  Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Cita creada', timer:2000, width:320, timerProgressBar:true, showConfirmButton:false, customClass:{ popup:'swal-compact-toast' } });
  emit('saved')
 } catch(e){ errors.value=e.response?.data||{} } finally { processing.value=false }
}
</script>
