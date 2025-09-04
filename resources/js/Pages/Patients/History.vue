<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { ref, watch, computed, onMounted } from 'vue'
import axios from 'axios'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
  filters: Object
})

const loading = ref(false)
const items = ref([])
const meta = ref({ current_page:1, last_page:1, total:0 })
const search = ref(props.filters?.search || '')
const patientId = ref(props.filters?.patient_id || '')
const type = ref(props.filters?.type || '')
const page = ref(1)

const dateFrom = ref('')
const dateTo = ref('')

const patientsOptions = ref([])
const patientQuery = ref('')
const showPatientDropdown = ref(false)
let debounceTimer = null

const fetchHistory = async () => {
  loading.value = true
  try {
    const res = await axios.get('/api/patients-history', { params: {
      patient_id: patientId.value || undefined,
      type: type.value || undefined,
      search: search.value || undefined,
      page: page.value
    }})
    items.value = res.data.data
    meta.value = res.data.meta
  } catch (e) {
    // noop
  } finally {
    loading.value = false
  }
}

const changePage = (p) => {
  if (p < 1 || p > meta.value.last_page) return
  page.value = p
  fetchHistory()
  scrollToTop()
}

const scrollToTop = () => {
  const el = document.getElementById('history-top')
  if (el) el.scrollIntoView({ behavior:'smooth', block:'start' })
}

watch([search, type, patientId], () => {
  page.value = 1
  fetchHistory()
})

watch([dateFrom, dateTo], () => { page.value = 1; fetchHistory() })

onMounted(() => {
  fetchHistory()
})

// Búsqueda de pacientes (para filtrar timeline)
const fetchPatients = async (q) => {
  try {
    const res = await axios.get('/api/patients', { params: { q, limit: 10 } })
    patientsOptions.value = res.data.data
  } catch (e) {
    patientsOptions.value = []
  }
}

const onPatientQueryChange = () => {
  if (!patientQuery.value) {
    patientsOptions.value = []
    showPatientDropdown.value = false
    patientId.value = ''
    return
  }
  showPatientDropdown.value = true
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchPatients(patientQuery.value), 300)
}

const selectPatient = (p) => {
  patientId.value = p.id
  patientQuery.value = p.name
  showPatientDropdown.value = false
}

const clearPatient = () => {
  patientId.value = ''
  patientQuery.value = ''
  patientsOptions.value = []
  fetchHistory()
}

const exportCsv = () => {
  const params = new URLSearchParams({
    patient_id: patientId.value || '',
    search: search.value || '',
    date_from: dateFrom.value || '',
    date_to: dateTo.value || ''
  })
  window.open(route('patients.history.export.csv') + '?' + params.toString(), '_blank')
}
const exportPdf = () => {
  const params = new URLSearchParams({
    patient_id: patientId.value || '',
    search: search.value || '',
    date_from: dateFrom.value || '',
    date_to: dateTo.value || ''
  })
  window.open(route('patients.history.export.pdf') + '?' + params.toString(), '_blank')
}

const typeOptions = [
  { value:'', label:'Todos' },
  { value:'appointment', label:'Citas' }
]

const expanded = ref({})
const toggleExpanded = (k) => { expanded.value[k] = !expanded.value[k] }

const groupedByDate = computed(() => {
  const groups = {}
  for (const it of items.value) {
    const d = (it.date || '').split(' ')[0]
    if (!groups[d]) groups[d] = []
    groups[d].push(it)
  }
  return Object.entries(groups).sort((a,b) => a[0] < b[0] ? 1 : -1)
})
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Historial clínico" />
    <template #header>
      <div id="history-top" class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Historial clínico</h2>
      </div>
    </template>
    <div class="py-4">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <!-- Filtros -->
        <div class="bg-white p-4 shadow-sm rounded-lg border">
          <div class="grid md:grid-cols-7 gap-4">
            <!-- paciente -->
            <div class="md:col-span-2 relative">
              <label class="block text-xs font-semibold text-gray-500 mb-1">Paciente</label>
              <input v-model="patientQuery" @input="onPatientQueryChange" type="text" class="w-full border-gray-300 rounded-md text-sm" placeholder="Buscar paciente..." />
              <button v-if="patientId" @click="clearPatient" class="absolute right-2 top-7 text-gray-400 hover:text-gray-600" title="Limpiar">&times;</button>
              <div v-if="showPatientDropdown" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-56 overflow-auto text-sm">
                <div v-if="!patientsOptions.length" class="p-2 text-gray-400">Sin resultados</div>
                <button v-for="p in patientsOptions" :key="p.id" type="button" @click="selectPatient(p)" class="w-full text-left px-2 py-1 hover:bg-gray-100">
                  <span class="font-medium">{{ p.name }}</span>
                  <span v-if="p.document_number" class="text-gray-500 text-xs ml-1">{{ p.document_number }}</span>
                </button>
              </div>
            </div>
            <!-- tipo -->
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Tipo</label>
              <select v-model="type" class="w-full border-gray-300 rounded-md text-sm">
                <option v-for="t in typeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
              </select>
            </div>
            <!-- búsqueda texto -->
            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-500 mb-1">Buscar texto</label>
              <input v-model.lazy="search" type="text" placeholder="Motivo, notas, doctor..." class="w-full border-gray-300 rounded-md text-sm" />
            </div>
            <!-- fechas -->
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Desde</label>
              <input type="date" v-model="dateFrom" class="w-full border-gray-300 rounded-md text-sm" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Hasta</label>
              <input type="date" v-model="dateTo" class="w-full border-gray-300 rounded-md text-sm" />
            </div>
            <!-- exportar, ir a ficha -->
            <div class="md:col-span-2 flex items-end gap-2 flex-wrap">
              <PrimaryButton type="button" @click="exportPdf" class="!py-2 !px-4" title="Exportar a PDF">PDF</PrimaryButton>
              <SecondaryButton type="button" @click="exportCsv" class="!py-2 !px-4" title="Exportar a CSV">CSV</SecondaryButton>
              <SecondaryButton v-if="patientId" type="button" @click="() => window.location = route('patients.index') + '#patient-' + patientId" class="!py-2 !px-4" title="Ir a listado del paciente">Ir a ficha</SecondaryButton>
            </div>
          </div>
        </div>

        <!-- Timeline -->
        <div class="bg-white p-4 shadow-sm rounded-lg border min-h-[300px] relative">
          <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white/60">
            <div class="animate-spin h-8 w-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
          </div>
          <div v-if="!loading && !items.length" class="text-center py-16 text-gray-500 text-sm">Sin eventos para los filtros seleccionados.</div>
          <div v-else class="space-y-8">
            <div v-for="([date, dayItems], gi) in groupedByDate" :key="date" class="relative">
              <div class="text-xs uppercase tracking-wide font-semibold text-gray-500 mb-3 flex items-center gap-2">
                <span>{{ new Date(date).toLocaleDateString('es-AR', { weekday:'long', year:'numeric', month:'short', day:'numeric'}) }}</span>
                <span class="h-px flex-1 bg-gradient-to-r from-gray-300 to-transparent"></span>
              </div>
              <ol class="relative border-s border-gray-200 ml-2">
                <li v-for="it in dayItems" :key="it.type + '-' + it.id" class="mb-6 ms-4">
                  <div class="absolute -start-1.5 mt-1 w-3 h-3 rounded-full border border-white bg-blue-500 shadow"></div>
                  <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2">
                    <div class="flex-1">
                      <div class="flex items-center gap-2 text-sm">
                        <span class="font-semibold text-gray-800">{{ it.reason || 'Cita' }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="{
                          'bg-green-100 text-green-700': it.status==='completada',
                          'bg-yellow-100 text-yellow-700': it.status==='programada',
                          'bg-red-100 text-red-700': it.status==='cancelada'
                        }">{{ it.status }}</span>
                      </div>
                      <div class="text-xs text-gray-500 mt-0.5">{{ it.date }} • {{ it.specialty || 'Sin especialidad' }} • Dr(a). {{ it.doctor || 'No asignado' }}</div>
                      <div v-if="it.notes" class="mt-1 text-xs text-gray-600 whitespace-pre-line">{{ it.notes }}</div>
                    </div>
                    <div class="text-xs text-gray-400 whitespace-nowrap">{{ it.type }}</div>
                  </div>
                  <div class="flex items-center gap-2 text-sm">
                    <button @click="toggleExpanded(it.type+'-'+it.id)" class="text-blue-600 hover:underline">{{ expanded[it.type+'-'+it.id] ? 'Ocultar' : 'Detalle' }}</button>
                  </div>
                  <div v-if="expanded[it.type+'-'+it.id]" class="mt-2 grid md:grid-cols-2 gap-3 bg-gray-50 p-3 rounded border border-gray-200 text-xs">
                    <div>
                      <div class="font-semibold mb-0.5">Síntomas</div>
                      <div class="whitespace-pre-line">{{ it.symptoms || '-' }}</div>
                    </div>
                    <div>
                      <div class="font-semibold mb-0.5">Diagnóstico</div>
                      <div class="whitespace-pre-line">{{ it.diagnosis || '-' }}</div>
                    </div>
                    <div>
                      <div class="font-semibold mb-0.5">Tratamiento</div>
                      <div class="whitespace-pre-line">{{ it.treatment || '-' }}</div>
                    </div>
                    <div>
                      <div class="font-semibold mb-0.5">Prescripción</div>
                      <div class="whitespace-pre-line">{{ it.prescription || '-' }}</div>
                    </div>
                  </div>
                </li>
              </ol>
            </div>
          </div>
        </div>

        <!-- Paginación -->
        <div v-if="meta.total > meta.per_page" class="flex items-center justify-between text-sm gap-3 flex-wrap">
          <div class="text-gray-500">Página {{ meta.current_page }} de {{ meta.last_page }} ({{ meta.total }} registros)</div>
          <div class="flex items-center gap-1">
            <button @click="changePage(meta.current_page - 1)" :disabled="meta.current_page===1" class="px-2 py-1 border rounded disabled:opacity-30">&laquo;</button>
            <button v-for="p in [meta.current_page-1, meta.current_page, meta.current_page+1]" v-if="p>=1 && p<=meta.last_page" :key="p" @click="changePage(p)" :class="['px-2 py-1 border rounded', p===meta.current_page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white hover:bg-gray-50']">{{ p }}</button>
            <button @click="changePage(meta.current_page + 1)" :disabled="meta.current_page===meta.last_page" class="px-2 py-1 border rounded disabled:opacity-30">&raquo;</button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
