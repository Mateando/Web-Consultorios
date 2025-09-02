<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    // Sólo intentar enfocar si el input tiene el atributo autofocus
    // y no existe ya otro elemento enfocado en el documento.
    try {
        if (input.value && input.value.hasAttribute('autofocus')) {
            const active = document.activeElement;
            // Si no hay elemento activo, o el activo es el <body> o <html>, podemos enfocar.
            if (!active || active === document.body || active === document.documentElement) {
                input.value.focus();
            }
        }
    } catch (err) {
        // Silenciar cualquier error de acceso al DOM en entornos no soportados
    }
});

defineExpose({ focus: () => input.value && input.value.focus() });
</script>

<template>
    <input
        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        v-model="model"
        ref="input"
    />
</template>
