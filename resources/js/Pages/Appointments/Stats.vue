<template>
  <Head title="Estadísticas de Citas" />
  <AuthenticatedLayout>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <h2 class="text-lg font-medium mb-6">Estadísticas de atención</h2>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 rounded-lg border bg-gray-50">
              <div class="text-sm text-gray-500">Citas pasadas</div>
              <div class="mt-3 text-3xl font-semibold text-gray-800">{{ stats.past ?? '—' }}</div>
            </div>

            <div class="p-4 rounded-lg border bg-white">
              <div class="text-sm text-gray-500">Citas del día</div>
              <div class="mt-3 text-3xl font-semibold text-gray-800">{{ stats.today ?? '—' }}</div>
            </div>

            <div class="p-4 rounded-lg border bg-gray-50">
              <div class="text-sm text-gray-500">Citas futuras</div>
              <div class="mt-3 text-3xl font-semibold text-gray-800">{{ stats.future ?? '—' }}</div>
            </div>
          </div>

          <div class="mt-6">
            <p class="text-sm text-gray-500">Última actualización: <span class="text-gray-700">{{ lastUpdated || '—' }}</span></p>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  stats: Object
})

const stats = ref({
  past: props.stats?.past ?? null,
  today: props.stats?.today ?? null,
  future: props.stats?.future ?? null,
})
const lastUpdated = ref(null)

async function loadStats() {
  try {
    const res = await axios.get('/api/appointments/stats')
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

onMounted(() => {
  if (!props.stats) loadStats()
  else lastUpdated.value = new Date().toLocaleString()
})
</script>
