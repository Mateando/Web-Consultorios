<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Historial clínico" />
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Historial clínico</h2>
        </template>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-700 text-sm">
                    <p>Esta vista se utilizará para explorar y gestionar el historial clínico de los pacientes. (Pendiente de implementación)</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
