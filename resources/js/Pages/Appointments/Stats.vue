<template>
  <Head title="Estadísticas de Citas" />
  <AuthenticatedLayout>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <h2 class="text-lg font-medium mb-6">Estadísticas de atención</h2>

          <!-- Filtros reorganizados -->
          <div class="flex flex-wrap items-end gap-4 mb-6">
            <div class="flex flex-col w-32 min-w-[7rem]">
              <label class="text-[11px] font-medium text-gray-500 tracking-wide uppercase mb-1">Desde</label>
              <input type="date" v-model="filters.start" class="h-9 border-gray-300 rounded-md text-sm px-2" />
            </div>
            <div class="flex flex-col w-32 min-w-[7rem]">
              <label class="text-[11px] font-medium text-gray-500 tracking-wide uppercase mb-1">Hasta</label>
              <input type="date" v-model="filters.end" class="h-9 border-gray-300 rounded-md text-sm px-2" />
            </div>
            <div class="flex flex-col w-48">
              <label class="text-[11px] font-medium text-gray-500 tracking-wide uppercase mb-1">Doctor</label>
              <select v-model="filters.doctor" class="h-9 border-gray-300 rounded-md text-sm px-2">
                <option value="">Todos</option>
                <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
              </select>
            </div>
            <div class="flex flex-col w-48">
              <label class="text-[11px] font-medium text-gray-500 tracking-wide uppercase mb-1">Especialidad</label>
              <select v-model="filters.specialty" class="h-9 border-gray-300 rounded-md text-sm px-2">
                <option value="">Todas</option>
                <option v-for="s in specialties" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
            <div class="flex flex-col w-40">
              <label class="text-[11px] font-medium text-gray-500 tracking-wide uppercase mb-1">Desglose</label>
              <select v-model="filters.by" class="h-9 border-gray-300 rounded-md text-sm px-2">
                <option value="">Total</option>
                <option value="doctor">Por doctor</option>
                <option value="specialty">Por especialidad</option>
              </select>
            </div>
            <div class="flex items-center gap-2 ml-auto pb-1">
              <SecondaryButton type="button" @click="clearFilters" class="!h-9 !px-3 flex items-center gap-1" :disabled="!hasAnyFilter">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6.28 5.22a.75.75 0 010 1.06L4.56 8l1.72 1.72a.75.75 0 11-1.06 1.06L3.5 9.06l-1.72 1.72a.75.75 0 01-1.06-1.06L2.44 8 .72 6.28A.75.75 0 011.78 5.22L3.5 6.94l1.72-1.72a.75.75 0 011.06 0z" clip-rule="evenodd"/></svg>
                Limpiar
              </SecondaryButton>
              <PrimaryButton type="button" @click="applyFilters" class="!h-9 !px-4 flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M4 8h16M6 12h12M10 16h4" /></svg>
                <span>Aplicar</span>
              </PrimaryButton>
              <div class="relative" @keydown.escape="closeExportMenus" data-export-container>
                <SecondaryButton type="button" @click="onExportButtonClick" class="!h-9 !px-4 flex items-center gap-1">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v4H4zM4 12h16v8H4z" /></svg>
                  <span>Exportar</span>
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                </SecondaryButton>
                <div v-if="ui.exportOpen" class="absolute right-0 mt-1 w-44 bg-white border border-gray-200 rounded-md shadow-lg z-20 py-1 text-sm">
                  <div v-if="ui.exportLoading" class="px-3 py-2 text-xs text-gray-500 flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-gray-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    Generando...
                  </div>
                  <button type="button" @click="doExport('csv')" :disabled="ui.exportLoading" class="w-full text-left px-3 py-2 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2" title="Exportar CSV (puede estar vacío)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" /></svg>
                    <span>CSV <span v-if="lastExport==='csv'" class="text-[10px] text-indigo-600 font-semibold">(último)</span></span>
                  </button>
                  <button type="button" @click="doExport('pdf')" :disabled="ui.exportLoading" class="w-full text-left px-3 py-2 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2" title="Exportar PDF (puede estar vacío)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 11V3m0 8a4 4 0 100 8 4 4 0 000-8zm6 4a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                    <span>PDF <span v-if="lastExport==='pdf'" class="text-[10px] text-indigo-600 font-semibold">(último)</span></span>
                  </button>
                  <button type="button" @click="doExport('json')" :disabled="ui.exportLoading" class="w-full text-left px-3 py-2 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2" title="Exportar JSON (API) - puede estar vacío">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 0117.9 9H19a3 3 0 010 6h-1" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 19l3 3m0 0l3-3m-3 3V10" /></svg>
                    <span>JSON <span v-if="lastExport==='json'" class="text-[10px] text-indigo-600 font-semibold">(último)</span></span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Metric sections -->
          <div class="space-y-6">
            <!-- A. Volumen de citas -->
            <section class="bg-white p-4 rounded shadow-sm">
              <h3 class="font-semibold mb-2">A. Volumen de citas</h3>
              <div class="text-sm text-gray-500 mb-4">Número de citas por periodo y por desglose (doctor / especialidad / motivo).</div>
              <div class="bg-gray-50 rounded p-2 overflow-hidden" style="height:280px; max-height:420px;">
                <canvas id="chart-volume-canvas" class="w-full" style="height:100% !important; max-height:100% !important;"></canvas>
              </div>
            </section>

            <!-- B. Productividad médica -->
            <section class="bg-white p-4 rounded shadow-sm">
              <h3 class="font-semibold mb-2">B. Productividad médica</h3>
              <div class="text-sm text-gray-500 mb-4">Citas atendidas, horas trabajadas estimadas y facturación por médico.</div>
              <div class="flex items-center gap-2 mb-2">
                <div class="text-sm">Mostrar top</div>
                <select v-model="ui.top" @change="applyFilters" class="border rounded px-2 py-1 pr-6 min-w-[56px] text-center">
                  <option :value="5">5</option>
                  <option :value="10">10</option>
                  <option :value="20">20</option>
                </select>
                  <button @click="toggleShowAll('productivity')" :disabled="ui.loading.productivity" class="px-2 py-1 border rounded disabled:opacity-60 disabled:cursor-not-allowed">
                  <template v-if="ui.loading.productivity">
                    <svg class="animate-spin h-4 w-4 inline-block mr-1" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    Cargando...
                  </template>
                  <template v-else>{{ ui.showAll.productivity ? 'Mostrar top' : 'Mostrar todo' }}</template>
                </button>
                  <div class="text-sm text-gray-500 ml-2">Mostrando {{ ui.meta.productivity.returned }} de {{ ui.meta.productivity.total }}</div>
              </div>
              <canvas id="chart-productivity-canvas" class="w-full" style="height:100% !important; max-height:100% !important;"></canvas>
            </section>

            <!-- C. Uso (aforo) -->
            <section class="bg-white p-4 rounded shadow-sm">
              <h3 class="font-semibold mb-2">C. Uso (aforo)</h3>
              <div class="text-sm text-gray-500 mb-4">Porcentaje de ocupación por sala/hora y ratio cita/slot.</div>
              <div class="relative">
                <canvas id="chart-usage-canvas" class="w-full" style="height:100% !important; max-height:100% !important;"></canvas>
                <div v-if="ui.loading.usage" class="absolute inset-0 bg-white/70 flex items-center justify-center">
                  <svg class="animate-spin h-6 w-6 text-gray-700" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                </div>
              </div>
            </section>

            <!-- D. Demografía -->
            <section class="bg-white p-4 rounded shadow-sm">
              <h3 class="font-semibold mb-2">D. Demografía</h3>
              <div class="text-sm text-gray-500 mb-4">Edad, sexo, aseguradora y tipo de paciente (frecuencia).</div>
              <div class="grid grid-cols-2 gap-2">
                <div class="relative">
                  <canvas id="chart-demo-gender" class="w-full" style="height:90% !important; max-height:100% !important;"></canvas>
                  <div v-if="ui.loading.demography" class="absolute inset-0 bg-white/70 flex items-center justify-center">
                    <svg class="animate-spin h-6 w-6 text-gray-700" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                  </div>
                </div>
                <div class="relative">
                  <canvas id="chart-demo-insurance" class="w-full" style="height:90% !important; max-height:100% !important;"></canvas>
                  <div v-if="ui.loading.demography" class="absolute inset-0 bg-white/70 flex items-center justify-center">
                    <svg class="animate-spin h-6 w-6 text-gray-700" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                  </div>
                </div>
              </div>
            </section>

            <!-- E. Indicadores administrativos -->
            <section class="bg-white p-4 rounded shadow-sm">
              <h3 class="font-semibold mb-2">E. Indicadores administrativos</h3>
              <div class="text-sm text-gray-500 mb-4">Tiempos de espera, tasa de no-show, cancelaciones.</div>
              <div class="flex items-center gap-2 mb-2">
                <div class="text-sm">Mostrar top</div>
                <select v-model="ui.top" @change="applyFilters" class="border rounded px-2 py-1 pr-6 min-w-[56px] text-center">
                  <option :value="5">5</option>
                  <option :value="10">10</option>
                  <option :value="20">20</option>
                </select>
                  <button @click="toggleShowAll('admin')" :disabled="ui.loading.admin" class="px-2 py-1 border rounded disabled:opacity-60 disabled:cursor-not-allowed">
                  <template v-if="ui.loading.admin">
                    <svg class="animate-spin h-4 w-4 inline-block mr-1" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    Cargando...
                  </template>
                  <template v-else>{{ ui.showAll.admin ? 'Mostrar top' : 'Mostrar todo' }}</template>
                </button>
                  <div class="text-sm text-gray-500 ml-2">Mostrando {{ ui.meta.admin.returned }} de {{ ui.meta.admin.total }}</div>
              </div>
              <canvas id="chart-admin-canvas" class="w-full" style="height:100% !important; max-height:100% !important;"></canvas>
            </section>

            <!-- F. Exportación / Reportes -->
            <section class="bg-white p-4 rounded shadow-sm">
              <h3 class="font-semibold mb-2">F. Exportación / Reportes</h3>
              <div class="text-sm text-gray-500 mb-4">Exportar datos filtrados en CSV/PDF y programar reportes.</div>
              <div class="flex gap-2 items-center">
                <div class="relative" @keydown.escape="closeExportMenus" data-export-container>
                  <PrimaryButton type="button" @click="ui.exportSectionOpen = !ui.exportSectionOpen" class="!py-2 !px-4 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8m-9 4h10m-7 4h4M5 4h14v16H5z" /></svg>
                    <span>Exportar</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                  </PrimaryButton>
                  <div v-if="ui.exportSectionOpen" class="absolute left-0 mt-1 w-48 bg-white border border-gray-200 rounded-md shadow-lg z-20 py-1 text-sm">
                    <div v-if="ui.exportLoading" class="px-3 py-2 text-xs text-gray-500 flex items-center gap-2">
                      <svg class="animate-spin h-4 w-4 text-gray-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                      Generando...
                    </div>
                    <button type="button" @click="doExport('csv')" :disabled="ui.exportLoading" class="w-full text-left px-3 py-2 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2" title="Exportar CSV (puede estar vacío)">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" /></svg>
                      <span>CSV <span v-if="lastExport==='csv'" class="text-[10px] text-indigo-600 font-semibold">(último)</span></span>
                    </button>
                    <button type="button" @click="doExport('pdf')" :disabled="ui.exportLoading" class="w-full text-left px-3 py-2 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2" title="Exportar PDF (puede estar vacío)">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 11V3m0 8a4 4 0 100 8 4 4 0 000-8zm6 4a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                      <span>PDF <span v-if="lastExport==='pdf'" class="text-[10px] text-indigo-600 font-semibold">(último)</span></span>
                    </button>
                    <button type="button" @click="doExport('json')" :disabled="ui.exportLoading" class="w-full text-left px-3 py-2 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2" title="Exportar JSON (puede estar vacío)">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 0117.9 9H19a3 3 0 010 6h-1" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 19l3 3m0 0l3-3m-3 3V10" /></svg>
                      <span>JSON <span v-if="lastExport==='json'" class="text-[10px] text-indigo-600 font-semibold">(último)</span></span>
                    </button>
                  </div>
                </div>
                <span v-if="!hasAnyData" class="text-xs text-gray-400 italic">(Actualmente sin datos, la exportación saldrá vacía)</span>
              </div>
            </section>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import { Chart, registerables } from 'chart.js'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
Chart.register(...registerables)

const props = defineProps({
  stats: Object
})

const stats = ref({
  past: props.stats?.past ?? null,
  today: props.stats?.today ?? null,
  future: props.stats?.future ?? null,
})
const lastUpdated = ref(null)

const filters = ref({
  start: null,
  end: null,
  doctor: '',
  specialty: '',
  by: ''
})

const doctors = ref([])
const specialties = ref([])

const ui = ref({
  top: 10,
  showAll: { productivity: false, admin: false },
  loading: { productivity: false, admin: false, usage: false, demography: false },
  meta: { productivity: { returned: 0, total: 0 }, admin: { returned: 0, total: 0 } },
  exportOpen: false,
  exportSectionOpen: false,
  exportLoading: false
})

// Computado para saber si hay algún dato en cualquiera de los bloques principales
const hasAnyData = computed(() => {
  try {
    const vol = volumeChart && volumeChart.data?.datasets?.some(ds => (ds.data || []).some(v => v > 0))
    const prod = productivityChart && productivityChart.data?.datasets?.some(ds => (ds.data || []).some(v => v > 0))
    const usage = usageChart && usageChart.data?.datasets?.some(ds => (ds.data || []).some(v => v > 0))
    const demoG = demoGenderChart && demoGenderChart.data?.datasets?.some(ds => (ds.data || []).some(v => v > 0))
    const demoI = demoInsuranceChart && demoInsuranceChart.data?.datasets?.some(ds => (ds.data || []).some(v => v > 0))
    const adminD = adminChart && adminChart.data?.datasets?.some(ds => (ds.data || []).some(v => v > 0))
    return !!(vol || prod || usage || demoG || demoI || adminD)
  } catch (e) { return false }
})

const lastExport = ref(null)

function doExport(format){
  if(ui.value.exportLoading) return
  ui.value.exportLoading = true
  const base = '/api/appointments/stats/';
  let endpoint = ''
  if(format==='csv') endpoint='export'
  else if(format==='pdf') endpoint='export-pdf'
  else if(format==='json') endpoint='export-json'
  // Copia limpia de filtros (evita mandar null/undefined literal)
  const clean = {}
  Object.entries(filters.value).forEach(([k,v]) => {
    if(v !== null && v !== undefined && v !== '' ) clean[k]=v
  })
  const url = base + endpoint + '?' + new URLSearchParams(clean).toString()
  // abrir en nueva pestaña (para csv/pdf) - json igual abre raw
  window.open(url, '_blank')
  lastExport.value = format
  setTimeout(()=>{ ui.value.exportLoading = false }, 1200)
}

function onExportButtonClick(){
  // Si ya se abrió antes y hay un formato previo, segundo clic hace export rápido del último formato
  if(ui.value.exportOpen && lastExport.value){
    doExport(lastExport.value)
    return
  }
  ui.value.exportOpen = !ui.value.exportOpen
}

function closeExportMenus(){
  ui.value.exportOpen = false
  ui.value.exportSectionOpen = false
}

async function loadStats() {
  try {
  const res = await axios.get('/api/appointments/stats', { params: filters.value })
    if (res && res.data) {
      stats.value.past = res.data.past ?? stats.value.past
      stats.value.today = res.data.today ?? stats.value.today
      stats.value.future = res.data.future ?? stats.value.future
      lastUpdated.value = new Date().toLocaleString()
    }
  } catch (e) {
    // Silencioso, solo mostrar guiones si falla
  }
}

let volumeChart = null
let productivityChart = null
let usageChart = null
let demoGenderChart = null
let demoInsuranceChart = null
let adminChart = null

function safeDestroy(chartInstance, canvasId) {
  try {
    if (chartInstance) chartInstance.destroy()
  } catch(e){}
  try {
    const c = document.getElementById(canvasId)
    if (c) {
      c.removeAttribute('width')
      c.removeAttribute('height')
      c.style.height = ''
      c.style.maxHeight = ''
    }
  } catch(e){}
}

function prepareCanvas(canvasId, cssHeightPx, options = {}) {
  try {
    const c = document.getElementById(canvasId)
    if (!c) return
    // set CSS height so layout constrains it
    // if forceSquare, enforce width == height (useful for pie/donut)
    if (options.forceSquare) {
      const parent = c.parentElement || c
      const side = Math.max(120, Math.round(Math.min(parent.clientWidth / 2, cssHeightPx)))
      c.style.width = side + 'px'
      c.style.height = side + 'px'
    } else {
      c.style.height = cssHeightPx + 'px'
      c.style.maxHeight = '100%'
    }
    // set pixel dimensions to match device pixel ratio to avoid Chart.js auto-resize issues
    const ratio = window.devicePixelRatio || 1
    // ensure parent width available
    const parent = c.parentElement || c
    const widthPx = Math.max(300, Math.round(parent.clientWidth))
    // if forceSquare and we set explicit width, use computed widthPx from c.clientWidth
    const computedWidth = options.forceSquare ? Math.round(c.clientWidth) : widthPx
    c.width = Math.round(computedWidth * ratio)
    const computedHeight = options.forceSquare ? Math.round(c.clientWidth) : cssHeightPx
    c.height = Math.round(computedHeight * ratio)
    // force canvas layout update
    c.getContext && c.getContext('2d') && c.getContext('2d').scale && c.getContext('2d').setTransform(ratio,0,0,ratio,0,0)
  } catch(e) {
    // ignore
  }
}
async function loadVolumeChart() {
  try {
    const params = Object.assign({}, filters.value)
    params.group = 'day'
    // pedir series si hay desglose
    if (params.by) params.format = 'series'
    const res = await axios.get('/api/appointments/stats/volume', { params })
    // detectar si la respuesta es JSON (si vino HTML, por ejemplo redirect a login, lo tratamos)
    const ctype = res.headers?.['content-type'] || ''
    if (!ctype.includes('application/json')) {
      console.warn('API de volumen no devolvió JSON. Posible sesión expirada o redirect:', res.request?.responseURL || res.request)
      // limpiar gráfico mostrando datos vacíos
      const labels = []
      const datasets = [{ label: 'Citas', data: [] }]
      if (volumeChart) {
        volumeChart.data.labels = labels
        volumeChart.data.datasets = datasets
        volumeChart.update()
      }
      return
    }
    const payload = res.data || {}
    let labels = payload.labels || []
    let datasets = (payload.datasets || []).map((ds, idx) => ({
      label: ds.label,
      data: ds.data,
      backgroundColor: `hsl(${(idx*60)%360} 70% 50% / 0.75)`
    }))

    // Soporte para formato legacy: payload.data = [{period,total},...]
    if ((!labels || labels.length === 0) && Array.isArray(payload.data) && payload.data.length) {
      labels = payload.data.map(d => d.period || d.day)
      datasets = [{ label: 'Citas', data: payload.data.map(d => d.total || 0) }]
    }

    // Soporte para formato actual del API: payload.volumen.by_day = [{day,total},...]
    if ((!labels || labels.length === 0) && payload.volumen && Array.isArray(payload.volumen.by_day) && payload.volumen.by_day.length) {
      const rows = payload.volumen.by_day
      labels = rows.map(r => r.day || r.period)
      datasets = [{ label: 'Citas', data: rows.map(r => r.total || 0), backgroundColor: 'rgba(37,99,235,0.75)' }]
    }

    if (!labels || labels.length === 0) {
      labels = []
      datasets = [{ label: 'Citas', data: [] }]
    }

  const ctx = document.getElementById('chart-volume-canvas')
    if (!ctx) return
    // Forzar altura en píxeles para evitar que Chart.js calcule un height enorme
  try { prepareCanvas('chart-volume-canvas', 280) } catch(e){}

    if (volumeChart) {
      volumeChart.data.labels = labels
      volumeChart.data.datasets = datasets
      volumeChart.update()
      return
    }

  volumeChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets
      },
      options: {
    responsive: false,
        maintainAspectRatio: false,
        elements: { bar: { borderRadius: 4 } },
        datasets: { bar: { maxBarThickness: 48 } },
        scales: {
          x: { stacked: false },
          y: { beginAtZero: true }
        }
      }
    })
  } catch (e) {
    // ignore
  }
}

async function loadDashboardStats() {
  try {
  const params = Object.assign({}, filters.value)
  params.top = ui.value.top
  // send per-section flags so the server can limit each list independently
  params.show_all_productivity = ui.value.showAll?.productivity ? 1 : 0
  params.show_all_admin = ui.value.showAll?.admin ? 1 : 0
  const res = await axios.get('/api/appointments/stats', { params })
    const payload = res.data || {}

    // store meta counts for UI badges
    try {
      ui.value.meta.productivity.returned = (payload.productividad?.completed_by_doc || []).length
      ui.value.meta.productivity.total = payload.productividad?.meta?.total_doctors || ui.value.meta.productivity.returned
    } catch (e) {}

    // Productividad: usar payload.productividad.completed_by_doc
  const completed = payload.productividad?.completed_by_doc || []
  // prefer doctor_name if API provided it
  const prodLabels = completed.map(c => c.doctor_name || c.doctor_id)
  const prodData = completed.map(c => c.completed)
    const prodCtx = document.getElementById('chart-productivity-canvas')
    if (prodCtx) {
      try { prepareCanvas('chart-productivity-canvas', 220) } catch(e){}
        if (productivityChart) {
          productivityChart.data.labels = prodLabels
          productivityChart.data.datasets[0].data = prodData
          productivityChart.update()
          try { productivityChart.resize() } catch(e){}
        } else {
          // Ensure previous chart/canvas cleaned
          safeDestroy(productivityChart, 'chart-productivity-canvas')
          productivityChart = new Chart(prodCtx, { type: 'bar', data: { labels: prodLabels, datasets: [{ label: 'Citas completadas', data: prodData, backgroundColor: 'rgba(16,185,129,0.8)'}] }, options: { responsive:false, maintainAspectRatio:false, elements:{bar:{borderRadius:4}}, datasets:{bar:{maxBarThickness:40}} } })
          try { productivityChart.resize() } catch(e){}
        }
    }

    // Uso: by_hour
    const byHour = payload.uso?.by_hour || []
    const hours = byHour.map(h => h.hour)
    const hoursData = byHour.map(h => h.total)
    const usageCtx = document.getElementById('chart-usage-canvas')
    if (usageCtx) {
      ui.value.loading.usage = true
      try { prepareCanvas('chart-usage-canvas', 220) } catch(e){}
      if (usageChart) {
        usageChart.data.labels = hours
        usageChart.data.datasets[0].data = hoursData
        usageChart.update()
      } else {
        safeDestroy(usageChart, 'chart-usage-canvas')
  usageChart = new Chart(usageCtx, { type: 'bar', data: { labels: hours, datasets: [{ label: 'Citas por hora', data: hoursData, backgroundColor: 'rgba(255,159,64,0.8)'}] }, options: { responsive:false, maintainAspectRatio:false, elements:{bar:{borderRadius:4}}, datasets:{bar:{maxBarThickness:36}} } })
      }
      ui.value.loading.usage = false
    }

    // Demografía: gender & insurance
    const genders = payload.demografia?.by_gender || []
    const gLabels = genders.map(g => g.gender || g[0])
    const gData = genders.map(g => g.total)
    const gCtx = document.getElementById('chart-demo-gender')
    if (gCtx) {
      ui.value.loading.demography = true
      try { prepareCanvas('chart-demo-gender', 160, { forceSquare: true }) } catch(e){}
      if (demoGenderChart) {
        demoGenderChart.data.labels = gLabels
        demoGenderChart.data.datasets[0].data = gData
        demoGenderChart.update()
      } else {
        safeDestroy(demoGenderChart, 'chart-demo-gender')
  demoGenderChart = new Chart(gCtx, { type: 'pie', data: { labels: gLabels, datasets:[{ data: gData, backgroundColor: ['#60A5FA','#FB7185','#FBBF24'] }] }, options: { responsive:false, maintainAspectRatio:false } })
      }
      ui.value.loading.demography = false
    }

    const ins = payload.demografia?.by_insurance || []
    const insLabels = ins.slice(0,10).map(i => i.insurance_provider || i[0])
    const insData = ins.slice(0,10).map(i => i.total)
    const insCtx = document.getElementById('chart-demo-insurance')
    if (insCtx) {
      try { prepareCanvas('chart-demo-insurance', 160) } catch(e){}
      if (demoInsuranceChart) {
        demoInsuranceChart.data.labels = insLabels
        demoInsuranceChart.data.datasets[0].data = insData
        demoInsuranceChart.update()
      } else {
        safeDestroy(demoInsuranceChart, 'chart-demo-insurance')
  demoInsuranceChart = new Chart(insCtx, { type: 'bar', data: { labels: insLabels, datasets:[{ label: 'Obras sociales', data: insData, backgroundColor: 'rgba(99,102,241,0.8)'}] }, options:{ responsive:false, maintainAspectRatio:false, elements:{bar:{borderRadius:4}}, datasets:{bar:{maxBarThickness:36}} } })
      }
    }

    // Admin: by_creator
  const creators = payload.admin?.by_creator || []
  const crLabels = creators.map(c => c.creator_name || c.created_by)
  const crData = creators.map(c => c.total)
    try {
      ui.value.meta.admin.returned = creators.length
      ui.value.meta.admin.total = payload.admin?.meta?.total_creators || ui.value.meta.admin.returned
    } catch (e) {}
    const adminCtx = document.getElementById('chart-admin-canvas')
    if (adminCtx) {
      try { prepareCanvas('chart-admin-canvas', 220) } catch(e){}
      if (adminChart) {
        adminChart.data.labels = crLabels
        adminChart.data.datasets[0].data = crData
        adminChart.update()
      } else {
        safeDestroy(adminChart, 'chart-admin-canvas')
  adminChart = new Chart(adminCtx, { type: 'bar', data: { labels: crLabels, datasets:[{ label: 'Citas creadas', data: crData, backgroundColor: 'rgba(107,114,128,0.8)'}] }, options:{ responsive:false, maintainAspectRatio:false, elements:{bar:{borderRadius:4}}, datasets:{bar:{maxBarThickness:36}} } })
      }
    }

  } catch (e) {
    // ignore
  }
}

async function toggleShowAll(section) {
  try {
    // flip and set loading
    if (!ui.value.showAll || typeof ui.value.showAll[section] === 'undefined') ui.value.showAll = Object.assign({}, ui.value.showAll || {}, { [section]: true })
    else ui.value.showAll[section] = !ui.value.showAll[section]

    ui.value.loading[section] = true
    // reload metrics to reflect show_all change
    await loadDashboardStats()
  } catch (e) {
    // noop
  } finally {
    ui.value.loading[section] = false
  }
}

onMounted(() => {
  if (!props.stats) loadStats()
  else lastUpdated.value = new Date().toLocaleString()

  // Cargar listas para filtros (livianas)
  axios.get('/api/doctors/list').then(r => { doctors.value = r.data || [] }).catch(()=>{})
  axios.get('/api/specialties').then(r => { specialties.value = r.data || [] }).catch(()=>{})
  // Cargar y dibujar gráfico de volumen
  loadVolumeChart()
  // Cargar otras métricas
  loadDashboardStats()

  // Cerrar dropdowns al hacer clic fuera manualmente (sin directiva externa)
  document.addEventListener('click', handleGlobalClick)
})

onBeforeUnmount(() => {
  const charts = [volumeChart, productivityChart, usageChart, demoGenderChart, demoInsuranceChart, adminChart]
  charts.forEach(c => {
    if (c) {
      try { c.destroy() } catch(e){}
    }
  })
  volumeChart = null
  productivityChart = null
  usageChart = null
  demoGenderChart = null
  demoInsuranceChart = null
  adminChart = null
  document.removeEventListener('click', handleGlobalClick)
})

function handleGlobalClick(e){
  try {
    const exportContainers = document.querySelectorAll('[data-export-container]')
    let inside = false
    exportContainers.forEach(el => { if(el.contains(e.target)) inside = true })
    if(!inside) closeExportMenus()
  } catch(err){}
}

function applyFilters() {
  loadStats()
  // recargar también el gráfico de volumen
  loadVolumeChart()
  // recargar métricas
  loadDashboardStats()
}

function exportCsv() {
  // descarga simple: abrir endpoint con mismos filtros
  const url = `/api/appointments/stats/export?` + new URLSearchParams(filters.value).toString()
  window.open(url, '_blank')
}

function exportPdf() {
  const url = `/api/appointments/stats/export-pdf?` + new URLSearchParams(filters.value).toString()
  window.open(url, '_blank')
}

const hasAnyFilter = computed(() => !!(filters.value.start || filters.value.end || filters.value.doctor || filters.value.specialty || filters.value.by))

function clearFilters(){
  filters.value.start = null
  filters.value.end = null
  filters.value.doctor = ''
  filters.value.specialty = ''
  filters.value.by = ''
  applyFilters()
}
</script>
