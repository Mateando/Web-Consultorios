<template>
    <Head title="Plantillas de Ordenes médicas" />
    <AuthenticatedLayout>
        <div class="py-8 max-w-5xl mx-auto px-4">
            <div class="bg-white rounded shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold">Plantillas de Ordenes médicas</h2>
                    <PrimaryButton type="button" @click="openModal()">Nuevo</PrimaryButton>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500 uppercase text-xs">
                            <th class="py-2">Título</th>
                            <th class="py-2">Descripción</th>
                            <th class="py-2">Estado</th>
                            <th class="py-2 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b hover:bg-gray-50">
                            <td class="py-2">{{ item.title }}</td>
                            <td class="py-2">{{ item.description }}</td>
                            <td class="py-2"><span :class="item.is_active? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="px-2 py-0.5 rounded text-xs font-medium">{{ item.is_active? 'Activo':'Inactivo' }}</span></td>
                            <td class="py-2 text-right space-x-2">
                                <SecondaryButton type="button" @click="openModal(item)" class="text-indigo-600 hover:underline">Editar</SecondaryButton>
                                <SecondaryButton type="button" @click="toggle(item)" class="text-gray-600 hover:underline">{{ item.is_active? 'Desactivar':'Activar' }}</SecondaryButton>
                            </td>
                        </tr>
                        <tr v-if="items.data.length===0">
                            <td colspan="4" class="py-4 text-center text-gray-400">Sin registros</td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="items.links && items.links.length>3" class="mt-4 flex flex-wrap gap-2">
                    <Link v-for="l in items.links" :key="l.label" :href="l.url || '#'" v-html="l.label" :class="['px-3 py-1 rounded text-xs', l.active? 'bg-blue-600 text-white':'bg-gray-200 text-gray-700', !l.url? 'opacity-50 pointer-events-none':'']" />
                </div>
            </div>
        </div>

        <div v-if="show" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded shadow w-full max-w-2xl p-6">
                <h3 class="text-base font-semibold mb-4">{{ current? 'Editar Plantilla':'Nueva Plantilla' }}</h3>
                <form @submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Título *</label>
                        <input v-model="form.title" class="w-full border rounded px-3 py-2 text-sm focus:ring focus:border-blue-500" required />
                        <p v-if="errors.title" class="text-xs text-red-600 mt-1">{{ errors.title }}</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Descripción</label>
                        <textarea v-model="form.description" rows="6" class="w-full border rounded px-3 py-2 text-sm focus:ring focus:border-blue-500" />
                        <p v-if="errors.description" class="text-xs text-red-600 mt-1">{{ errors.description }}</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <SecondaryButton type="button" @click="close">Cancelar</SecondaryButton>
                        <PrimaryButton type="submit">Guardar</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({ items:Object })
const show = ref(false)
const current = ref(null)
const form = reactive({ title:'', description:'' })
const errors = ref({})

function openModal(item=null){
    current.value = item
    form.title = item? item.title: ''
    form.description = item? item.description: ''
    errors.value = {}
    show.value = true
}
function close(){ show.value=false; current.value=null }
function save(){
    const url = current.value? route('admin.config.medical-order-templates.update', current.value.id): route('admin.config.medical-order-templates.store')
    const method = current.value? 'put':'post'
    router[method](url, form, { onError:e=> errors.value=e, onSuccess:()=> close() })
}
function toggle(item){
    router.patch(`/admin/config/medical-order-templates/${item.id}/toggle`, {}, { preserveScroll:true })
}
</script>
