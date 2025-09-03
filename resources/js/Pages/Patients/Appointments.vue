<template>
  <Head :title="`Citas - ${patient.user?.name || 'Paciente'}`" />
  <AuthenticatedLayout>
    <div class="py-8 max-w-4xl mx-auto px-4">
      <div class="bg-white rounded shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-lg font-semibold">Citas de {{ patient.user?.name || 'Paciente' }}</h2>
            <p class="text-sm text-gray-500">Documento: {{ patient.user?.document_type || '' }} - {{ patient.user?.document_number || '' }}</p>
          </div>
          <div>
            <Link :href="route('patients.index')" class="inline-flex items-center rounded-md border border-transparent bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Volver</Link>
          </div>
        </div>

        <div v-if="!appointments?.data || appointments.data.length===0" class="p-6 text-gray-600">No se encontraron citas para este paciente.</div>

        <div v-else class="bg-white shadow overflow-hidden sm:rounded-md">
          <ul class="divide-y divide-gray-200">
            <li v-for="app in appointments.data" :key="app.id" class="px-4 py-4 sm:px-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-indigo-600">{{ app.doctor?.user?.name || 'Sin médico asignado' }}</p>
                  <p class="mt-1 text-sm text-gray-500">{{ formatDate(app.appointment_date) }} - {{ capitalize(app.status) }}</p>
                  <p v-if="app.reason" class="mt-1 text-sm text-gray-500">Motivo: {{ app.reason }}</p>
                </div>
                <div class="flex items-center gap-2">
                  <a :href="route('appointments.print', app.id)" target="_blank" class="text-sm text-gray-600 hover:text-gray-900 inline-flex items-center" title="Imprimir" aria-label="Imprimir">
                    <PrinterIcon class="h-5 w-5" :title="'Imprimir'" />
                  </a>
                </div>
              </div>
            </li>
          </ul>
        </div>

        <div v-if="appointments?.links && appointments.links.length > 3" class="mt-4 flex justify-center">
          <nav class="flex flex-wrap gap-2">
            <Link v-for="l in appointments.links" :key="l.label+(l.url||'')" :href="l.url || '#'" v-html="sanitizeLabel(l.label)" :class="paginationClass(l)" />
          </nav>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { computed } from 'vue'
import PrinterIcon from '@/Components/icons/PrinterIcon.vue'

const props = defineProps({ patient: Object, appointments: Object })

function formatDate(d){
  if(!d) return ''
  const dt = new Date(d)
  return dt.toLocaleString()
}

function capitalize(s){ return s? s.charAt(0).toUpperCase()+s.slice(1):'' }

function sanitizeLabel(label){ if(!label) return '' ; return label.replace('&laquo;','«').replace('&raquo;','»') }

function paginationClass(link){
  if(link.label==='...') return 'px-3 py-2 text-sm text-gray-400 cursor-default select-none'
  if(!link.url) return 'inline-flex items-center justify-center px-3 py-2 rounded text-sm bg-gray-100 text-gray-400 cursor-not-allowed'
  return link.active? 'px-3 py-2 rounded text-sm bg-gray-800 text-white': 'px-3 py-2 rounded text-sm bg-gray-200 text-gray-700'
}
</script>
