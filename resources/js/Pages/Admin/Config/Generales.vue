<template>
  <Head title="Generales" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">Configuración</h2>
          <div class="text-sm text-gray-500">Generales</div>
        </div>
        <div>
          <Link :href="route('admin.config.study-types')" class="text-sm text-blue-600 hover:underline mr-4">Tipos de Estudios</Link>
          <Link :href="route('admin.config.appointment-reasons')" class="text-sm text-blue-600 hover:underline">Motivos de Turnos</Link>
        </div>
      </div>
    </template>

    <div class="py-8 max-w-6xl mx-auto px-4">
      <div class="bg-white rounded shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Ajustes Generales</h3>
        <p class="text-sm text-gray-600">Aquí puedes agrupar configuraciones generales del sistema. Por ahora es una página base similar al panel de administración pero específica para la sección Configuración.</p>

        <!-- Placeholder: agregar secciones según necesites -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
          <Link :href="route('admin.config.clinic')" class="block p-4 bg-gray-50 rounded hover:bg-gray-100">Datos del Consultorio</Link>
          <Link :href="route('admin.config.study-types')" class="block p-4 bg-gray-50 rounded hover:bg-gray-100">Tipos de Estudios</Link>
          <Link :href="route('admin.config.appointment-reasons')" class="block p-4 bg-gray-50 rounded hover:bg-gray-100">Motivos de Turnos</Link>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
</script>
