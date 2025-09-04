<template>
  <Head title="Estadísticas de Citas" />
  <AuthenticatedLayout>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <h2 class="text-lg font-medium mb-6">Estadísticas de atención</h2>

          <!-- Filters -->
          <div class="flex flex-col sm:flex-row sm:items-end gap-4 mb-4">
            <div class="flex items-center gap-2">
              <label class="text-sm text-gray-600">Desde</label>
              <input type="date" v-model="filters.from" class="border rounded px-2 py-1" />
            </div>
            <div class="flex items-center gap-2">
              <label class="text-sm text-gray-600">Hasta</label>
              <input type="date" v-model="filters.to" class="border rounded px-2 py-1" />
            </div>
            <div class="flex items-center gap-2">
              <label class="text-sm text-gray-600">Doctor</label>
              <select v-model="filters.doctor" class="border rounded px-2 py-1">
                <option value="">Todos</option>
                <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
              </select>
            </div>
            <div class="flex items-center gap-2">
              <label class="text-sm text-gray-600">Especialidad</label>
              <select v-model="filters.specialty" class="border rounded px-2 py-1">
                <option value="">Todas</option>
                <option v-for="s in specialties" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
            <div class="flex items-center gap-2">
              <label class="text-sm text-gray-600">Desglose</label>
              <select v-model="filters.by" class="border rounded px-2 py-1">
                <option value="">Total</option>
                <option value="doctor">Por doctor</option>
                <option value="specialty">Por especialidad</option>
              </select>
            </div>

            <div class="ml-auto flex items-center gap-2">
              <button @click="applyFilters" class="px-3 py-1 bg-blue-600 text-white rounded">Aplicar</button>
              <button @click="exportCsv" class="px-3 py-1 border rounded">Exportar CSV</button>
            </div>
          </div>

          <!-- Metric sections -->
          <div class="space-y-6">
            <!-- A. Volumen de citas -->
            <section class="bg-white p-4 rounded shadow-sm">
              <h3 class="font-semibold mb-2">A. Volumen de citas</h3>
              <div class="text-sm text-gray-500 mb-4">Número de citas por periodo y por desglose (doctor / especialidad / motivo).</div>
              <div class="h-64 bg-gray-50 rounded p-2">
                <canvas id="chart-volume-canvas" class="w-full h-full"></canvas>
              </div>
            </section>

            <!-- B. Productividad médica -->
            <section class="bg-white p-4 rounded shadow-sm">
              <h3 class="font-semibold mb-2">B. Productividad médica</h3>
              <div class="text-sm text-gray-500 mb-4">Citas atendidas, horas trabajadas estimadas y facturación por médico.</div>
              <div id="chart-productivity" class="h-48 bg-gray-50 rounded flex items-center justify-center">[Gráfico productividad]</div>
            </section>

            <!-- C. Uso (aforo) -->
            <section class="bg-white p-4 rounded shadow-sm">
              <h3 class="font-semibold mb-2">C. Uso (aforo)</h3>
              <div class="text-sm text-gray-500 mb-4">Porcentaje de ocupación por sala/hora y ratio cita/slot.</div>
              <div id="chart-usage" class="h-48 bg-gray-50 rounded flex items-center justify-center">[Gráfico uso]</div>
            </section>

            <!-- D. Demografía -->
            <section class="bg-white p-4 rounded shadow-sm">
              <h3 class="font-semibold mb-2">D. Demografía</h3>
              <div class="text-sm text-gray-500 mb-4">Edad, sexo, aseguradora y tipo de paciente (frecuencia).</div>
              <div id="chart-demo" class="h-48 bg-gray-50 rounded flex items-center justify-center">[Gráficos demografía]</div>
            </section>

            <!-- E. Indicadores administrativos -->
            <section class="bg-white p-4 rounded shadow-sm">
              <h3 class="font-semibold mb-2">E. Indicadores administrativos</h3>
              <div class="text-sm text-gray-500 mb-4">Tiempos de espera, tasa de no-show, cancelaciones.</div>
              <div id="chart-admin" class="h-48 bg-gray-50 rounded flex items-center justify-center">[Indicadores admin]</div>
            </section>

            <!-- F. Exportación / Reportes -->
            <section class="bg-white p-4 rounded shadow-sm">
              <h3 class="font-semibold mb-2">F. Exportación / Reportes</h3>
              <div class="text-sm text-gray-500 mb-4">Exportar datos filtrados en CSV/PDF y programar reportes.</div>
              <div class="flex gap-2">
                <button @click="exportCsv" class="px-3 py-1 bg-green-600 text-white rounded">Exportar CSV</button>
                <button @click="exportPdf" class="px-3 py-1 border rounded">Exportar PDF</button>
              </div>
            </section>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import { Chart, registerables } from 'chart.js'
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
  from: null,
  to: null,
  doctor: '',
  specialty: '',
})

const doctors = ref([])
const specialties = ref([])

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
        maintainAspectRatio: false,
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

onMounted(() => {
  if (!props.stats) loadStats()
  else lastUpdated.value = new Date().toLocaleString()

  // Cargar listas para filtros (livianas)
  axios.get('/api/doctors/list').then(r => { doctors.value = r.data || [] }).catch(()=>{})
  axios.get('/api/specialties').then(r => { specialties.value = r.data || [] }).catch(()=>{})
  // Cargar y dibujar gráfico de volumen
  loadVolumeChart()
})

onBeforeUnmount(() => {
  if (volumeChart) {
    volumeChart.destroy()
    volumeChart = null
  }
})

function applyFilters() {
  loadStats()
  // recargar también el gráfico de volumen
  loadVolumeChart()
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
</script>
