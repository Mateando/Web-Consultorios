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

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Link :href="route('admin.config.clinic')" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-12 h-12 bg-indigo-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7M7 7V4a2 2 0 012-2h6a2 2 0 012 2v3"/>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-lg font-medium text-gray-900">Datos del Consultorio</div>
                  <div class="text-sm text-gray-500">Configura el nombre, dirección y datos de contacto</div>
                </div>
              </div>
            </div>
          </Link>

          <Link :href="route('admin.config.study-types')" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-12 h-12 bg-pink-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6M9 16h6M9 8h6M7 20h10a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-lg font-medium text-gray-900">Tipos de Estudios</div>
                  <div class="text-sm text-gray-500">Configurar tipos de estudios y costos</div>
                </div>
              </div>
            </div>
          </Link>

          <Link :href="route('admin.config.patient-types')" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z"/>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-lg font-medium text-gray-900">Tipos de Paciente</div>
                  <div class="text-sm text-gray-500">Clasifica pacientes por tipo</div>
                </div>
              </div>
            </div>
          </Link>

          <Link :href="route('admin.config.insurance-providers')" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-lg font-medium text-gray-900">Obras Sociales</div>
                  <div class="text-sm text-gray-500">Administrar obras sociales y convenios</div>
                </div>
              </div>
            </div>
          </Link>

          <Link :href="route('admin.config.holidays')" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-lg font-medium text-gray-900">Feriados</div>
                  <div class="text-sm text-gray-500">Configurar días no laborables</div>
                </div>
              </div>
            </div>
          </Link>

          <Link :href="route('admin.config.appointment-reasons')" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-lg font-medium text-gray-900">Motivos de Turnos</div>
                  <div class="text-sm text-gray-500">Gestionar tipos de motivos para turnos</div>
                </div>
              </div>
            </div>
          </Link>

          <Link :href="route('admin.config.medical-order-templates')" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-12 h-12 bg-gray-700 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6M9 16h6M9 8h6"/>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-lg font-medium text-gray-900">Plantillas Ordenes</div>
                  <div class="text-sm text-gray-500">Administrar plantillas de órdenes médicas</div>
                </div>
              </div>
            </div>
          </Link>

          <Link :href="route('admin.config.countries')" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-12 h-12 bg-teal-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V5a2 2 0 00-2-2H8a2 2 0 00-2 2v6M8 13v6a2 2 0 002 2h6a2 2 0 002-2v-6"/>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-lg font-medium text-gray-900">Países</div>
                  <div class="text-sm text-gray-500">Gestionar países disponibles</div>
                </div>
              </div>
            </div>
          </Link>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
</script>
